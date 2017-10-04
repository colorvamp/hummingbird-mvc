<?php
	trait __html_socket{
		public $agent       = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:44.0) Gecko/20100101 Firefox/44.0';
		public $lang        = 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3';
		public $referer     = false;
		public $proxy       = [];
		public $cookies     = [];
		public $max_retries = 1;
		function query($url = '',$params = []){
			return $this->_query($url,$params);
		}
		function _query($url = '',$params = []){
			$context = stream_context_create();
			$uinfo   = parse_url(trim($url));
			$uinfo['port'] = 80;
			$scheme = 'tcp';
			if( empty($uinfo['scheme']) ){
				//return array('pageHeader'=>'HTTP/1.1 400 BAD REQUEST','pageContent'=>'');
				exit;
			}
			if( $uinfo['scheme'] == 'https' ){
				$uinfo['port'] = 443;
				$scheme = 'ssl';
				$r = stream_context_set_option($context,'ssl','verify_host',true);
				$r = stream_context_set_option($context,'ssl','allow_self_signed',true);
			}
			if( empty($uinfo['path']) ){$uinfo['path'] = '/';}
			if( $this->proxy ){
				$this->proxy_header_auth = base64_encode($this->proxy['user'].':'.$this->proxy['pass']);
				$data['ip']   = $this->proxy['host'];
				$data['port'] = $this->proxy['port'];
			}



			$host = isset($data['ip']) ? $data['ip'] : $uinfo['host'];
			$port = isset($data['port']) ? $data['port'] : $uinfo['port'];
			$fp = stream_socket_client($scheme.'://'.$host.':'.$port,$errno,$errstr,10,STREAM_CLIENT_CONNECT,$context);
			if( !$fp ){
				switch( $errno ){
					case   0: return ['errorDescription'=>'DNS_FAILURE','file'=>__FILE__,'line'=>__LINE__];
					case 101: return ['errorDescription'=>'NETWORK_UNREACHABLE','file'=>__FILE__,'line'=>__LINE__];
					case 110: return ['errorDescription'=>'TIMEOUT','file'=>__FILE__,'line'=>__LINE__];
					case 111: return ['errorDescription'=>'CONNECTION_REFUSED','file'=>__FILE__,'line'=>__LINE__];
					case 113: return ['errorDescription'=>'NO_ROUTE_TO_HOST','file'=>__FILE__,'line'=>__LINE__];
				}
				echo $errstr.' ('.$errno.')';return false;
			}

			$CR = "\r\n";
			$header = ( !empty($data['post']) ? 'POST' : 'GET' )
				.' '.$url.( !empty($uinfo['query']) ? '?'.$uinfo['query'] : '' ).' HTTP/1.1'.$CR
				.'Host: '.$uinfo['host'].$CR
				.'User-Agent: '.$this->agent.$CR
				.'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'.$CR
				.'Accept-Language: '.$this->lang.$CR
				.'Accept-Encoding: gzip,deflate'.$CR
				.'Connection: Close'.$CR
				.( !empty($this->referer) ? 'Referer: '.$this->referer.$CR : '' )
				.( !empty($this->proxy_header_auth) ? 'Proxy-Authorization: Basic '.$this->proxy_header_auth.$CR : '' )
				//(isset($data['header']) ? implode($CR,     array_map(function($n,$m){return $n.': '.$m;},array_keys($data['header']),array_values($data['header']))     ).$CR : '').
				.'';

if( false && isset($data['cookies']) && count($data['cookies']) > 0){
	$cookieData = '';foreach($data['cookies'] as $cookie){list($key,$value) = each($cookie);$cookieData .= $key.'='.$value.'; ';}
	if($cookieData == ''){$cookieData = substr($cookieData,0,-2);}
	$header .= 'Cookie: '.$cookieData.$CR;
}

if( false && isset($data['post'])){
	$postData = http_build_query($data['post']);
	$header .= 'Content-Type: application/x-www-form-urlencoded;charset=UTF-8'.$CR.
	'Content-Length: '.strlen($postData).$CR;
	unset($data['post']);
}

			$header .= $CR;
			//if(isset($postData)){$header .= $postData;}
			//print_r($header)."\n\n\n\n";exit;

			$return = [
				 'page-code'=>0
				,'page-message'=>''
				,'page-next'=>false
				,'page-header'=>false
				,'page-content'=>false
			];

			$buffer = '';
			$retry = 0;
			$length = 0;
			$headers = false;
			while( !$buffer ){
				/* Retry to send the header, sometimes (almost with PHP 5.5.9-1ubuntu4.3) the remote server
				 * timeouts, it seems that header didnt reach destiny, so send it twice seems to fix the problem */
				if( $retry ){break;}
				if( fwrite($fp,$header) === false ){
					echo 'Unable to write';return false;
				}

				/* Wait for initial bytes, then loop */
				$buffer .= stream_socket_recvfrom($fp,2048,STREAM_OOB);
				$length  = strlen($buffer);
				$t = microtime(true);

				while( empty($headers)
				 || !$buffer
				 || !feof($fp)
				 || ( isset($headers['content-length']) && $headers['content-length'] < $length )
				){
					/* Break if timeout */
//FIXME: poner timeout en $this
					if( (microtime(true) - $t) > 10 ){break;}
					if( $headers && isset($headers['content-length']) && $headers['content-length'] == $length ){break;}
					$chunk = stream_get_line($fp,1024);
					$buffer .= $chunk;
					$length += strlen($chunk);

					/* Check for header, this idea comes from mutable buffers used in NodeJS */
					if( empty($headers) && ($headers = $this->_parse_header($buffer)) ){
						$return['page-header'] = substr($buffer,0,$headers['header-length']);
						$buffer = substr($buffer,$headers['header-length']);
						$length -= $headers['header-length'];
					}
				}
				$retry++;
			}
			fclose($fp);

			/* INI-Content decoding */
			if( isset($headers['transfer-encoding']) && $headers['transfer-encoding'] == 'chunked' ){$buffer = $this->_unchunkHttp11($buffer);}
			if( isset($headers['content-encoding'])
			 && $headers['content-encoding'] == 'gzip'
			 && ( ($try = @gzdecode($buffer)) !== false || !empty($try = @gzdecode2($buffer)) ) ){
				/* Sometimes 'gzdecode' returns false for free, but gzdecode2 is able to decode content */
				$buffer = $try;
				unset($try);
			}
			$return['page-content'] = $buffer;
			unset($buffer);
			/* END-Content decoding */

/* INI-Saving cookies */
//FIXME: necesitamos ponerle domain
$cookies = array();$m = preg_match_all('/[Ss]et-[Cc]ookie: (.*)/',$header,$arr);
if($m){foreach($arr[0] as $k=>$v){
	$cookie = array();$m = preg_match_all('/([a-zA-Z0-9\-_\.]*)=([^;]+)/',$arr[1][$k],$c);foreach($c[0] as $k=>$v){$cookie[$c[1][$k]] = $c[2][$k];}$cookies[] = $cookie;
}}
if(isset($data['cookies'])){$cookies = array_merge($data['cookies'],$cookies);}
$data['cookies'] = $cookies;
/* END-Saving cookies */

/* INI-Follow Location */
$m = preg_match('/[Ll]ocation: (.*)/',$header,$arr);
if($m && isset($data['followLocation']) && $data['followLocation']){
	if(is_int($data['followLocation'])){$data['followLocation']--;}
	$uri = $arr[1];if(substr($uri,0,4) != 'http'){$uri = $uinfo['scheme'].'://'.$uinfo['host'].((strpos($arr[1],0,1) == '/') ? '' : '/').$arr[1];}
	return html_petition($uri,$data);
}
/* END-Follow Location */

			return $return;
		}
		function _parse_header($blob = ''){
			$CR = "\r\n";
			$break = strpos($blob,$CR.$CR)+4;
			if($break == 4){return [];}
			$blob = substr($blob,0,$break);

			$ret = ['header-length'=>$break];
			if(preg_match('/[Tt]ransfer\-[Ee]ncoding:[ ]*chunked/',$blob)){		$ret['transfer-encoding'] = 'chunked';}
			if(preg_match('/[Cc]ontent\-[Ee]ncoding:[ ]*gzip/',$blob)){		$ret['content-encoding']  = 'gzip';}
			if(preg_match('/[Cc]ontent-[Ll]ength:[ ]*(?<v>[0-9]+)/',$blob,$m)){	$ret['content-length']    = $m['v'];}
			return $ret;
		}
		function _unchunkHttp11($data = ''){
			$fp = 0;$outData = '';$CR = "\r\n";
			while($fp < strlen($data)){$rawnum = substr($data,$fp,strpos(substr($data,$fp),$CR)+2);$num = hexdec(trim($rawnum));$fp += strlen($rawnum);$chunk = substr($data,$fp,$num);$outData .= $chunk;$fp += strlen($chunk);}
			return $outData;
		}
		function cleanup(){
			$this->referer = false;
			$this->proxy = [];
			$this->cookies = [];
		}
		function _header($header = []){
			$h = '';
			foreach( $header as $k=>$v ){$h .= $k.': '.$v."\r\n";}
			return trim($h);
		}
	}
	class _html_socket{
		use __html_socket;
	}

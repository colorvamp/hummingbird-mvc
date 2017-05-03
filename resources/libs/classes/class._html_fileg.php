<?php
	if( !isset($GLOBALS['config']) ){$GLOBALS['config'] = [];}
	$GLOBALS['config'] = array_merge([
		 'user.agent'=>false
		,'dns.cache'=>[]
	],$GLOBALS['config']);

	trait __html_fileg{
		public $agent     = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:44.0) Gecko/20100101 Firefox/44.0';
		public $lang      = 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3';
		public $referer   = false;
		public $proxy     = [];
		public $cookies   = [];
		function query($url = '',$params = []){
			$opts['http']['header']['user-agent'] = $this->agent;
			$opts['http']['header']['accept-language'] = $this->lang;
			$opts['http']['timeout'] = 4;
			$opts['http']['request_fulluri'] = true;
			$opts['http']['ignore_errors']   = true;
			$opts['http']['follow_location'] = false;
			
			if( !empty($params['header']) ){
				/* Normalize headers */
				foreach( $params['header'] as $key=>$value ){
					$lower = strtolower($key);
					if( $lower == $key ){continue;}
					$params['header'][$lower] = $value;
					unset($params['header'][$key]);
				}
			}

			if( empty($params['header']['cookie']) && !empty($this->cookies) ){
				/* Recover cookies */
				$params['header']['cookie'] = array_map(function($c){
					return $c['name'].'='.$c['value'].';';
				},$this->cookies);
				$params['header']['cookie'] = implode(' ',$params['header']['cookie']);
			}

			if( isset($params['post']) ){
				$opts['http']['method'] = 'POST';
				$opts['http']['content'] = http_build_query($params['post']);
				$opts['http']['header']['content-type'] = 'application/x-www-form-urlencoded';
			}
			if( isset($params['header']) ){$opts['http']['header'] += $params['header'];}

			if( $this->proxy ){
				$auth = base64_encode($this->proxy['user'].':'.$this->proxy['pass']);
				$opts['http']['header']['proxy-authorization'] = 'Basic '.$auth;
				$opts['http']['proxy'] = 'tcp://'.$this->proxy['host'].':'.$this->proxy['port'];
			}

			$opts['http']['header'] = trim($this->_header($opts['http']['header']));
			$context = stream_context_create($opts);
			$html    = @file_get_contents($url,false,$context);
			if( $html === false ){
				$error_ = error_get_last();
				$error  = $error_['message'];
				switch( true ){
					case preg_match('!failed to open stream: Connection refused!',$error): return ['errorDescription'=>'CONNECTION_REFUSED','file'=>__FILE__,'line'=>__LINE__];
					case preg_match('!failed to open stream: Cannot connect to HTTPS server through proxy!',$error): return ['errorDescription'=>'PROXY_ERROR','file'=>__FILE__,'line'=>__LINE__];
					case preg_match('!failed to open stream: HTTP request failed\!!',$error): return ['errorDescription'=>'REQUEST_FAILED','file'=>__FILE__,'line'=>__LINE__];
					case preg_match('!failed to open stream: Connection timed out!',$error): return ['errorDescription'=>'TIME_OUT','file'=>__FILE__,'line'=>__LINE__];
					case preg_match('!failed to open stream: No route to host!',$error): return ['errorDescription'=>'NO_ROUTE_TO_HOST','file'=>__FILE__,'line'=>__LINE__];
					default: return ['errorDescription'=>$error,'file'=>__FILE__,'line'=>__LINE__];
				}
			}

			$return = [
				 'page-code'=>0
				,'page-message'=>''
				,'page-next'=>false
				,'page-header'=>implode("\r\n",$http_response_header)
				,'page-content'=>$html
			];
			if( preg_match('/HTTP\/1\.[0-9]+ (?<code>[0-9]+) (?<msg>[a-zA-Z ]+)/',$return['page-header'],$m) ){
				$return['page-code'] = $m['code'];
				$return['page-message'] = $m['msg'];
			}
			if( preg_match('![Ll]ocation: (?<url>.*)!i',$return['page-header'],$m) ){
				$return['page-next'] = trim($m['url']);
			}
			if( preg_match_all('![Ss]et-[Cc]ookie: (?<cookie>.*)!',$return['page-header'],$m) ){
				/* Processing cookies */
				foreach( $m[0] as $k=>$dummy ){
					$cookie = [];
					$r = preg_match_all('!(?<key>[a-zA-Z0-9\-_\.]*)=(?<value>[^;]*)!i',$m['cookie'][$k],$c);
					if( ($p = array_search('path',$c['key'])) !== false ){
						$cookie['path'] = $c['value'][$p];
						unset($c['key'][$p],$c['value'][$p]);
					}
					if( ($p = array_search('domain',$c['key'])) !== false ){
						$cookie['domain'] = $c['value'][$p];
						unset($c['key'][$p],$c['value'][$p]);
					}
					$cookie['name']  = reset($c['key']);
					$cookie['value'] = reset($c['value']);
					$this->cookies[$cookie['name']] = $cookie;
					$return['page-cookies'][$cookie['name']] = $cookie;
				}
			}
			return $return;
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
	class _html_fileg{
		use __html_fileg;
	}



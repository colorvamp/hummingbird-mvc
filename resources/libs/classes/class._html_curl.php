<?php
	trait __html_curl{
		public $agent     = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:44.0) Gecko/20100101 Firefox/44.0';
		public $lang      = 'es-ES,es;q=0.8,en-US;q=0.5,en;q=0.3';
		public $referer   = false;
		public $proxy     = [];
		public $cookies   = [];
		public $timeout   = 10;
		function query($url = '',$params = []){
			$ip = false;
			if (is_array($url)) {
				if( isset($url['ip']) ){$ip = $url['ip'];}
				if( isset($url['url']) ){$url = $url['url'];}
			}

			$url = trim($url);

			$ch = curl_init();
			$ops = [
				 CURLOPT_URL            => $url
				,CURLOPT_RETURNTRANSFER => true
				,CURLOPT_HEADER         => false
				,CURLOPT_SSL_VERIFYHOST => 2
				,CURLOPT_SSL_VERIFYPEER => true
				,CURLOPT_ENCODING       => 'gzip,deflate'
				,CURLOPT_USERAGENT	=> $this->agent
				,CURLOPT_TIMEOUT	=> $this->timeout
			];

			if (!empty($this->proxy)) {
				$ops[CURLOPT_PROXYTYPE] = CURLPROXY_HTTP;
				if (isset($this->proxy['type'])) {$ops[CURLOPT_PROXYTYPE] = $this->proxy['type'];}
				$ops[CURLOPT_PROXY] = $this->proxy['host'].':'.$this->proxy['port'];
				if (isset($this->proxy['user'],$this->proxy['pass']) ){$ops[CURLOPT_PROXYUSERPWD] = $this->proxy['user'].':'.$this->proxy['pass'];}
			}

			if( false && isset($data['post']) ){do{
				$postString = $data['post'];
				if(  isset($data['post.raw']) ){
					if( is_string($data['post']) ){$postString = $data['post'];}
					if( !isset($data['headers']['Content-Type']) ){$data['headers']['Content-Type'] = 'text/plain';}
				}
				if( !isset($data['post.multipart']) && is_array($data['post']) ){
					/* Hay determinados tipos de servidores que si no se les envía como 
					 * string no funcionan correctamente :S */
					$isString = true;foreach($data['post'] as $item){if( !is_string($item) ){$isString = false;}}
					if( $isString ){$postString = http_build_query($data['post']);}
				}
				$ops[CURLOPT_POSTFIELDS] = $postString;
				//$ops[CURLOPT_POST] = count($data['post']);
				//$ops[CURLOPT_POSTFIELDS] = $data['post'];
				/* We send post here, remove it for latter queries */
				unset($data['post']);
			}while(false);}

			if ($this->referer) {
				$ops[CURLOPT_REFERER] = $this->referer;
			}

			if (empty($params['header']['cookie']) && !empty($this->cookies)) {
				$parts = parse_url($url);
				/* Recover cookies */
				$params['header']['cookie'] = array_map(function($c) use ($parts,$params){
					if( !empty($c['domain'])
					 && !empty($parts['host'])
					 && strpos($parts['host'],trim($c['domain'])) === false ){
						//echo $parts['host'].' - '.$c['domain'].' - '.strpos($parts['host'],$c['domain']).PHP_EOL;
						return '';
					}
					return $c['name'].'='.$c['value'].';';
				},$this->cookies);
				$ops[CURLOPT_COOKIE] = trim(implode(' ',$params['header']['cookie']));
				if (empty($ops[CURLOPT_COOKIE])) {unset($ops[CURLOPT_COOKIE]);}
			}

			if (isset($data['debug'])) {$ops[CURLINFO_HEADER_OUT] = true;}
			curl_setopt_array($ch,$ops);

			$header = '';
			curl_setopt($ch, CURLOPT_HEADERFUNCTION,function($curl, $h) use (&$header){
				$header .= $h;
				return strlen($h);
			});

			$html = curl_exec($ch);
			if (!$html) {return ['errorDescription'=>curl_error($ch),'file'=>__FILE__,'line'=>__LINE__];}
			if (isset($data['debug'])) {print_r(curl_getinfo($ch));}

			$return = [
				 'page-code'=>0
				,'page-message'=>''
				,'page-next'=>false
				,'page-header'=>$header
				,'page-content'=>$html
			];
			if( preg_match_all('!HTTP/[0-9]+\.[0-9]+ (?<code>[0-9]+) (?<msg>[a-zA-Z ]+)!i',$return['page-header'],$m)
			 || preg_match_all('!HTTP/[0-9]+\.[0-9]+ (?<code>[0-9]+)!i',$return['page-header'],$m) ){
				$return['page-code']    = end($m['code']);
				$return['page-message'] = !empty($m['msg']) ? end($m['msg']) : '';
			}
			if( preg_match('![Ll]ocation: (?<url>.*)!i',$return['page-header'],$m) ){
				$return['page-next'] = trim($m['url']);
			}
			if( preg_match_all('![Ss]et-[Cc]ookie: (?<cookie>.*)!',$return['page-header'],$m) ){
				/* Processing cookies */
				foreach( $m[0] as $k=>$dummy ){
					$cookie = [];
					$r = preg_match_all('!(?<key>[a-zA-Z0-9\-_\.\[\]]*)=(?<value>[^;]*)!i',$m['cookie'][$k],$c);
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
			foreach ($header as $k=>$v) {$h .= $k.': '.trim($v)."\r\n";}
			return trim($h);
		}
	}
	class _html_curl{
		use __html_curl;
	}

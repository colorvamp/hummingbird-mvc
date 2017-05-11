<?php
	class _params{
		function validate($params = [],$valid = []){
			$tmp = [];
			foreach($params as $k=>$v){
				if( !strpos($v,'=') ){continue;}
				list($key,$value) = explode('=',$v);
				$tmp[$key] = $value;
			}
			$params = $tmp;

			foreach($params as $k=>$v){
				if( $v === '' ){unset($params[$k]);continue;}
				if( $v === 'false' ){unset($params[$k]);continue;}
				if( !isset($valid[$k]) ){unset($params[$k]);continue;}
				if( $valid[$k] === 'skip' ){continue;}
				if( is_string($valid[$k]) && $valid[$k][0] == '/' && !preg_match($valid[$k],$v) ){unset($params[$k]);continue;}
				if( is_array($valid[$k]) && !in_array($v,$valid[$k]) ){unset($params[$k]);continue;}
			}
			return $params;
		}
		function query($query = ''){
			$query = preg_replace_callback('!url:(?<url>http:\/\/[^ \'\"]+)!',function($m){
				return 'url:"'.$m['url'].'"';
			},$query);

			$literals = [];
			$query = preg_replace_callback('!([\'\"]{1})(?<literal>.*?)\1!',function($m) use (&$literals){
				$hash = md5($m['literal']);
				$literals[$hash] = $m['literal'];
				return $hash;
			},$query);

			$words = [];
			$query = preg_replace_callback('!(?<name>[^ :]+):(?<value>[^ :]+)!',function($m) use (&$words,&$literals){
				if( isset($literals[$m['value']]) ){$m['value'] = $literals[$m['value']];}
				$words[$m['name']]['$in'][] = $m['value'];
				return '';
			},$query);

			return [
				 'words'=>$words
				,'query'=>trim($query)
			];
		}
	}

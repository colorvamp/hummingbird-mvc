<?php
	trait __strings{
		public $chars_specials = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü'];
		public $chars_normal   = ['a','e','i','o','u','a','e','i','o','u','n','n','a','e','i','o','u','a','e','i','o','u'];
		function strings_fix($str = ''){
			return str_replace($this->chars_specials,$this->chars_normal,strtolower($str));
		}
		function strings_toURL($str){
			return preg_replace(['/[ |\.|_]/','/[^a-zA-Z0-9\-]*/','(^\-|[\-]*$)','/[\-]{2,}/'],['-','','','-'],$this->strings_fix($str));
		}
		function strings_clean($str = '',$lenth = false){
			$str = str_replace([PHP_EOL,'</p><p>'],[' ',' '],$str);
			$str = str_replace(['&nbsp;','<br>','<br/>'],' ',$str);
			$str = html_entity_decode($str);
			$str = strip_tags($str);
			$str = preg_replace('/[ \n\r\t]+/',' ',$str);
			/* Eliminamos los enlaces de markdown */
			$str = preg_replace('!\[([^\]]+)\]\([^\)]+\)!','$1',$str);
			if($lenth && mb_strlen($str) > $lenth){
				$str = mb_substr($str,0,$lenth);
				if( ($char = substr($str,-1)) && (ord($char) == 195/* Ã */ || ord($char) == 194/* � */) ){$str = substr($str,0,-1);}
				$str .= ' ...';
			}
			return trim($str);
		}
		function strings_url_params($url = ''){
			$parts = parse_url($url);
			if( !isset($parts['query']) ){return false;}
			parse_str($parts['query'],$params);
			if( !isset($params['id']) ){return false;}
			return $params;
		}
	}

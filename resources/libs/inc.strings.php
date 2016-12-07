<?php
	trait __strings{
		public $chars_specials = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü'];
		public $chars_normal   = ['a','e','i','o','u','a','e','i','o','u','n','n','a','e','i','o','u','a','e','i','o','u'];
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

	$GLOBALS['strings_specials'] = ['á','é','í','ó','ú','Á','É','Í','Ó','Ú','ñ','Ñ','ä','ë','ï','ö','ü','Ä','Ë','Ï','Ö','Ü'];
	$GLOBALS['strings_normals'] =  ['a','e','i','o','u','a','e','i','o','u','n','n','a','e','i','o','u','a','e','i','o','u'];
	function strings_UTF8Encode($str){if(!strings_detect_UTF8($str)){$str = utf8_encode($str);}return $str;}
	function strings_fix($str){return str_replace($GLOBALS['strings_specials'],$GLOBALS['strings_normals'],strtolower($str));}
	function strings_toURL($str){return preg_replace(array('/[ |\.|_]/','/[^a-zA-Z0-9\-]*/','(^\-|[\-]*$)','/[\-]{2,}/'),array('-','','','-'),strings_fix($str));}
	function strings_clean($str = '',$lenth = false){
		$str = str_replace([PHP_EOL,'</p><p>'],[' ',' '],$str);
		$str = str_replace(['&nbsp;','<br>','<br/>'],' ',$str);
		$str = html_entity_decode($str,ENT_QUOTES);
		$str = strip_tags($str);
		$str = preg_replace('/[ \n\r\t]+/',' ',$str);
		/* Eliminamos los enlaces de markdown */
		$str = preg_replace('!\[([^\]]+)\]\([^\)]+\)!','$1',$str);
		if($lenth && mb_strlen($str) > $lenth){
			$str = mb_substr($str,0,$lenth);
			if( ($char = substr($str,-1)) && (ord($char) == 195/* Ã */ || ord($char) == 194/* � */) ){$str = substr($str,0,-1);}
			$str .= ' ...';
		}
		return strings_toUTF8(trim($str));
	}
	function strings_meta_description($str = ''){
		$str = strings_clean($str,160);
		return str_replace(['"'],['\''],$str);
	}
	function strings_tags_clean($tags = false){
		if(is_string($tags)){$tags = explode(',',$tags);}
		foreach($tags as $k=>$tag){$tags[$k] = strings_toURL($tag);}
		return array_diff(array_unique($tags),['']);
	}

	function strings_text_clean($str = ''){
		$str = str_replace(['&nbsp;','<br>','<br/>'],' ',$str);
		$str = html_entity_decode($str);
		$str = strip_tags($str);
		$str = preg_replace('/[ \n\r\t]+/',' ',$str);
		return strings_toUTF8(trim($str));
	}

	function strings_jsToArray($js = ''){
		$js = str_replace(['\\\'','\n'],['&#39;','<br>'],$js);
		/* This one changes {site:asd} for {"site":asd} */
		$js = preg_replace('/(,|\{|\[)[ \t\n\r]*(\w+)[ ]*:[ ]*/','$1"$2":',$js);
		/* Support for arrays instead of objects */
		//$js = preg_replace('/(,|\[)[ \t\n]*(\w+)[ ]*:[ ]*/','$1"$2":',$js);
		//preg_match('/(,|\{|\[)[ \t\n\r]*(.)/',$js,$m);
		//print_r($m);

		/* This one changes { 'site' :  for {"site": */
		$js = preg_replace_callback('/(?<prefix>,|\{)[ \t\n\r]*\'(?<value>\w+)\'[ \t\n]*:[ \t\n\r]*/',function($n){
			return $n['prefix'].'"'.$n['value'].'":';
		},$js);

		/* This one changes ['site',30] for ["site",30] */
		$js = preg_replace_callback('/(?<prefix>,|\[)[ \t\n\r]*\'(?<value>\w+)\'[ ]*(?<sufix>,|\])/',function($n){
			return $n['prefix'].'"'.$n['value'].'"'.$n['sufix'];
		},$js);

		$js = preg_replace_callback('/"[ ]*:[ ]*\"(?<value>.*?)\"[ \n\t\r]*(?<separator>,"|\}$|]$|\}]|]\}|\}|])/',function($n){
			$v = str_replace('\\"','"',$n['value']);
			$v = str_replace('"','\\"',$v);
			return '":"'.$v.'"'.$n['separator'];
		},$js);

		/* This one changes {"site":'asd'} for {"site":"asd"} */
		$js = preg_replace_callback('/":\'(?<value>[^\']*)\'[ \n\t\r]*(?<separator>,"|\}$|]$|\}]|]\}|\}|])/',function($n){
			return '":'.json_encode(stripslashes($n['value'])).$n['separator'];
		},$js);

		/* Integer expresions */
		$js = preg_replace_callback('/parseInt\((?<eval>[^\)]+)\)/',function($n){
			eval('$result = '.$n['eval'].';');
			return intval($result);
		},$js);

		/* Boolean expresions */
		$js = preg_replace_callback('/":(?<eval>[falsetrue\&\!\|\(\) ]+)(?<separator>,)/',function($n){
			if( $n['eval'] == 'false' || $n['eval'] == 'true' ){return $n[0];}
			eval('$result = '.$n['eval'].';');
			return '":'.($result ? 'true' : 'false').$n['separator'];
		},$js);

		return json_decode($js,1);
	}

	function strings_discard_spanish($str){
		return str_replace(array('-con-','-al-','-del-','-el-','-en-'),'-',$str);
	}

	function strings_detect_UTF8($string){
		return preg_match('%(?:
		[\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
		|\xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
		|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2} # straight 3-byte
		|\xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
		|\xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
		|[\xF1-\xF3][\x80-\xBF]{3}         # planes 4-15
		|\xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
		)+%xs', 
		$string);
	}
//strings_createSnippetWithTags(file_get_contents('../../_article.html'),300,array('br','img'));
	function strings_createSnippetWithTags($str,$limit,$forbiddenTags = array()){
		$forbiddenTags = array_fill_keys($forbiddenTags,'');
		$str = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$str);
		$str = preg_replace('/[\r\n?]/',"\n",$str);
		$len = strlen($str);$cache = '';$N = "\n";
		$tags = array();
		$p = 0;while($p < $len){
			$o = $str[$p];if($o == $N){$p++;continue;}
			if($o == '<'){
				if($cache != ''){$tags[] = array('tag'=>'text','value'=>$cache);}
				$cache = $o;
				while($o != '>'){$p++;$o = $str[$p];if($o == $N){$p++;continue;}$cache .= $o;}
				$tags[] = array('tag'=>$cache);
				$cache = '';$p++;continue;
			}
			$cache .= $o;
			$p++;
		}
		if(!empty($cache)){$tags[] = array('tag'=>'text','value'=>$cache);}

		$cache = '';
		$currentTags = array();
		foreach($tags as $tag){
			if(mb_strlen($cache) > $limit){break;}
			if($tag['tag'] == 'text'){
				$cachelen = mb_strlen($cache);
				$taglen = mb_strlen($tag['value']);
				if($cachelen+$taglen > $limit){
					$cut = ($limit-$cachelen);if(isset($tag['value'][$cut-1]) && ord($tag['value'][$cut-1]) == 195/* Ã */ || ord($tag['value'][$cut-1]) == 194/* � */){$cut+=1;}
					$tag['value'] = mb_substr($tag['value'],0,$cut).' ...';
				}
				$cache .= $tag['value'];
				if(mb_strlen($cache) > $limit){break;}
				continue;
			}
			$tagName = '';
			if($pos = mb_strpos($tag['tag'],' ')){$tagName = mb_substr($tag['tag'],1,$pos-1);}else{$tagName = mb_substr($tag['tag'],1,-1);}
			$nakedTag = $tagName;if($nakedTag[0] == '/'){$nakedTag = mb_substr($nakedTag,1);}
			if(isset($forbiddenTags[$nakedTag])){continue;}

			if($tagName[0] != '/'){
				$currentTags[] = $tagName;
				$cache .= $tag['tag'];
			}else{
				$tagName = mb_substr($tagName,1);
				$pop = 1;while($pop && $pop != $tagName){$pop = array_pop($currentTags);}
				$cache .= $tag['tag'];
			}
		}
		$currentTags = array_reverse($currentTags);
		foreach($currentTags as $tag){$cache .= '</'.$tag.'>';}
		return $cache;
	}
	function strings_toUTF8($text){
		/**
		* Function Encoding::toUTF8
		* This function leaves UTF8 characters alone, while converting almost all non-UTF8 to UTF8.
		* It assumes that the encoding of the original string is either Windows-1252 or ISO 8859-1.
		* It may fail to convert characters to UTF-8 if they fall into one of these scenarios:
		* 1) when any of these characters:   ÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖ×ØÙÚÛÜÝÞß
		*    are followed by any of these:  ("group B")
		*                                    ¡¢£¤¥¦§¨©ª«¬­®¯°±²³´µ¶•¸¹º»¼½¾¿
		* For example:   %ABREPRESENT%C9%BB. «REPRESENTÉ»
		* The "«" (%AB) character will be converted, but the "É" followed by "»" (%C9%BB) 
		* is also a valid unicode character, and will be left unchanged.
		* 2) when any of these: àáâãäåæçèéêëìíîï  are followed by TWO chars from group B,
		* 3) when any of these: ðñòó  are followed by THREE chars from group B.
		*/

		if(is_array($text)){foreach($text as $k=>$v){$text[$k] = strings_toUTF8($v);}return $text;}
		if(!is_string($text)){return $text;}
		$max = strlen($text);
		$buf = '';
		for($i = 0;$i < $max;$i++){
			$c1 = $text{$i};
			if($c1>="\xc0"){ /* Should be converted to UTF8, if it's not UTF8 already */
				$c2 = $i+1 >= $max? "\x00" : $text{$i+1};$c3 = $i+2 >= $max? "\x00" : $text{$i+2};$c4 = $i+3 >= $max? "\x00" : $text{$i+3};
				if($c1 >= "\xc0" & $c1 <= "\xdf"){ /* looks like 2 bytes UTF8 */
					if($c2 >= "\x80" && $c2 <= "\xbf"){ /* yeah, almost sure it's UTF8 already */ $buf .= $c1.$c2;$i++;continue;}
					/* not valid UTF8.  Convert it. */
					$cc1 = (chr(ord($c1)/64) | "\xc0");$cc2 = ($c1 & "\x3f") | "\x80";$buf .= $cc1.$cc2;continue;
				}
				if($c1 >= "\xe0" & $c1 <= "\xef"){ /* looks like 3 bytes UTF8 */
					if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf"){ /* yeah, almost sure it's UTF8 already */ $buf .= $c1.$c2.$c3;$i = $i+2;continue;}
					/* not valid UTF8.  Convert it. */
					$cc1 = (chr(ord($c1)/64) | "\xc0");$cc2 = ($c1 & "\x3f") | "\x80";$buf .= $cc1.$cc2;continue;
				}
				if($c1 >= "\xf0" & $c1 <= "\xf7"){ /* looks like 4 bytes UTF8 */
					if($c2 >= "\x80" && $c2 <= "\xbf" && $c3 >= "\x80" && $c3 <= "\xbf" && $c4 >= "\x80" && $c4 <= "\xbf"){ /* yeah, almost sure it's UTF8 already */ $buf .= $c1.$c2.$c3;$i = $i+2;continue;}
					/* not valid UTF8.  Convert it. */
					$cc1 = (chr(ord($c1)/64) | "\xc0");$cc2 = ($c1 & "\x3f") | "\x80";$buf .= $cc1.$cc2;continue;
				}
				/* doesn't look like UTF8, but should be converted */
				$cc1 = (chr(ord($c1)/64) | "\xc0");$cc2 = (($c1 & "\x3f") | "\x80");$buf .= $cc1 . $cc2;continue;
			}
			if(($c1 & "\xc0") == "\x80"){ // needs conversion
				if(isset($GLOBALS['strings_win1252ToUtf8'][ord($c1)])){ /* found in Windows-1252 special cases */$buf .= $GLOBALS['strings_win1252ToUtf8'][ord($c1)];}
				$cc1 = (chr(ord($c1)/64) | "\xc0");$cc2 = (($c1 & "\x3f") | "\x80");$buf .= $cc1.$cc2;continue;
			}
			$buf .= $c1;
		}
		$buf = str_replace(array('Â¿'),array('¿'),$buf);
		return $buf;
	}
	function strings_toWin1252($text) {
		if(is_array($text)){foreach($text as $k => $v){$text[$k] = strings_toWin1252($v);}return $text;}
		if(is_string($text)){return utf8_decode(str_replace(array_keys($GLOBALS['strings_utf8ToWin1252']),array_values($GLOBALS['strings_utf8ToWin1252']),strings_toUTF8($text)));}
		return $text;
	}
	function strings_fixUTF8($text){
		if(is_array($text)){foreach($text as $k => $v){$text[$k] = strings_fixUTF8($v);}return $text;}
		$last = '';while($last <> $text){$last = $text;$text = strings_toUTF8(utf8_decode(str_replace(array_keys($GLOBALS['strings_utf8ToWin1252']), array_values($GLOBALS['strings_utf8ToWin1252']),$text)));}
		$text = strings_toUTF8(utf8_decode(str_replace(array_keys($GLOBALS['strings_utf8ToWin1252']),array_values($GLOBALS['strings_utf8ToWin1252']),$text)));
		return $text;
	}
	function strings_UTF8FixWin1252Chars($text){
		// If you received an UTF-8 string that was converted from Windows-1252 as it was ISO8859-1 
		// (ignoring Windows-1252 chars from 80 to 9F) use this function to fix it.
		// See: http://en.wikipedia.org/wiki/Windows-1252
		return str_replace(array_keys($GLOBALS['strings_brokenUtf8ToUtf8']),array_values($GLOBALS['strings_brokenUtf8ToUtf8']),$text);
	}
	function strings_removeBOM($str=''){if(substr($str,0,3) == pack("CCC",0xef,0xbb,0xbf)){$str = substr($str,3);}return $str;}
	$GLOBALS['strings_win1252ToUtf8'] = array(
		128 => "\xe2\x82\xac",

		130 => "\xe2\x80\x9a",
		131 => "\xc6\x92",
		132 => "\xe2\x80\x9e",
		133 => "\xe2\x80\xa6",
		134 => "\xe2\x80\xa0",
		135 => "\xe2\x80\xa1",
		136 => "\xcb\x86",
		137 => "\xe2\x80\xb0",
		138 => "\xc5\xa0",
		139 => "\xe2\x80\xb9",
		140 => "\xc5\x92",

		142 => "\xc5\xbd",


		145 => "\xe2\x80\x98",
		146 => "\xe2\x80\x99",
		147 => "\xe2\x80\x9c",
		148 => "\xe2\x80\x9d",
		149 => "\xe2\x80\xa2",
		150 => "\xe2\x80\x93",
		151 => "\xe2\x80\x94",
		152 => "\xcb\x9c",
		153 => "\xe2\x84\xa2",
		154 => "\xc5\xa1",
		155 => "\xe2\x80\xba",
		156 => "\xc5\x93",

		158 => "\xc5\xbe",
		159 => "\xc5\xb8"
	);
	$GLOBALS['strings_brokenUtf8ToUtf8'] = array(
		"\xc2\x80" => "\xe2\x82\xac",

		"\xc2\x82" => "\xe2\x80\x9a",
		"\xc2\x83" => "\xc6\x92",
		"\xc2\x84" => "\xe2\x80\x9e",
		"\xc2\x85" => "\xe2\x80\xa6",
		"\xc2\x86" => "\xe2\x80\xa0",
		"\xc2\x87" => "\xe2\x80\xa1",
		"\xc2\x88" => "\xcb\x86",
		"\xc2\x89" => "\xe2\x80\xb0",
		"\xc2\x8a" => "\xc5\xa0",
		"\xc2\x8b" => "\xe2\x80\xb9",
		"\xc2\x8c" => "\xc5\x92",

		"\xc2\x8e" => "\xc5\xbd",


		"\xc2\x91" => "\xe2\x80\x98",
		"\xc2\x92" => "\xe2\x80\x99",
		"\xc2\x93" => "\xe2\x80\x9c",
		"\xc2\x94" => "\xe2\x80\x9d",
		"\xc2\x95" => "\xe2\x80\xa2",
		"\xc2\x96" => "\xe2\x80\x93",
		"\xc2\x97" => "\xe2\x80\x94",
		"\xc2\x98" => "\xcb\x9c",
		"\xc2\x99" => "\xe2\x84\xa2",
		"\xc2\x9a" => "\xc5\xa1",
		"\xc2\x9b" => "\xe2\x80\xba",
		"\xc2\x9c" => "\xc5\x93",

		"\xc2\x9e" => "\xc5\xbe",
		"\xc2\x9f" => "\xc5\xb8"
	);
	$GLOBALS['strings_utf8ToWin1252'] = array(
		"\xe2\x82\xac" => "\x80",

		"\xe2\x80\x9a" => "\x82",
		"\xc6\x92"     => "\x83",
		"\xe2\x80\x9e" => "\x84",
		"\xe2\x80\xa6" => "\x85",
		"\xe2\x80\xa0" => "\x86",
		"\xe2\x80\xa1" => "\x87",
		"\xcb\x86"     => "\x88",
		"\xe2\x80\xb0" => "\x89",
		"\xc5\xa0"     => "\x8a",
		"\xe2\x80\xb9" => "\x8b",
		"\xc5\x92"     => "\x8c",

		"\xc5\xbd"     => "\x8e",


		"\xe2\x80\x98" => "\x91",
		"\xe2\x80\x99" => "\x92",
		"\xe2\x80\x9c" => "\x93",
		"\xe2\x80\x9d" => "\x94",
		"\xe2\x80\xa2" => "\x95",
		"\xe2\x80\x93" => "\x96",
		"\xe2\x80\x94" => "\x97",
		"\xcb\x9c"     => "\x98",
		"\xe2\x84\xa2" => "\x99",
		"\xc5\xa1"     => "\x9a",
		"\xe2\x80\xba" => "\x9b",
		"\xc5\x93"     => "\x9c",

		"\xc5\xbe"     => "\x9e",
		"\xc5\xb8"     => "\x9f"
	);


<?php
	//$t = file_get_contents('markdown.test.html');
	//$a = markdown_toHTML($t);echo $a;
	function markdown_toHTML($text = ''){
		/* Salvamos los enlaces de referencia */
		$referenceLinks = array();
		$rgx = '/^[ ]{0,3}\[([0-9a-z]+)\]: ([^ \n]+)( .([^\'\"]+).|)/m';
		$r = preg_match_all($rgx,$text,$m);
		foreach($m[0] as $k=>$v){$referenceLinks[$m[1][$k]] = array('link'=>$m[2][$k],'title'=>$m[4][$k] ? $m[4][$k] : '');}
		$text = preg_replace($rgx,'',$text);

		$text = preg_replace('/^[\xEF\xBB\xBF|\x1A]/','',$text);
		$text = preg_replace('/\n[\n]+/',"\n\n",$text);
		$text = preg_replace('/\r\n/',PHP_EOL,$text);

		/* Párrafos */
		$text = explode("\n\n",$text);
		$text = '<p>'.implode('</p>'.PHP_EOL.'<p>',str_replace(array('<','>'),array('&lt;','&gt;'),$text)).'</p>'.PHP_EOL;
		/* INI-hr */
		$text = preg_replace('/<p>\*[\* ]+<\/p>[\n\t ]*/m','<hr/>'.PHP_EOL,$text);
		$text = preg_replace('/<p>\-[\- ]+<\/p>[\n\t ]*/m','<hr/>'.PHP_EOL,$text);
		/* INI-Blockquote */
		$text = preg_replace('/<p>&gt; ([^<]+)<\/p>[\n\t ]*/m','<blockquote><p>$1</p></blockquote>'.PHP_EOL,$text);
		$text = preg_replace_callback('/<blockquote><p>([^<]+)<\/p><\/blockquote>/m',function($m){return '<blockquote><p>'.str_replace(PHP_EOL.'&gt; ',' ',$m[1]).'</p></blockquote>';},$text);
		/* INI-ol */$text = preg_replace_callback('/<p>[ ]{0,3}[0-9]+\. ([^<]+)<\/p>/m',function($m){$t = preg_split('/\n[ ]{0,3}(?:[\-\+\*]|[0-9]+\.)[ ]*/m',$m[1]);return '<ol><li>'.implode('</li><li>',$t).'</li></ol>';},$text);
		/* INI-ul */$text = preg_replace_callback('/<p>[ ]{0,3}[\-\+\*] ([^<]+)<\/p>/m',function($m){$t = preg_split('/\n[ ]{0,3}[\-\+\*][ ]*/m',$m[1]);return '<ul><li>'.implode('</li><li>',$t).'</li></ul>';},$text);
		/* INI-h1 */$text = preg_replace('/<p>([^\n<]+)\n[\=]+<\/p>/m','<h1>$1</h1>',$text);
		/* INI-h2 */$text = preg_replace('/<p>([^\n<]+)\n[\-]+<\/p>/m','<h2>$1</h2>',$text);
		/* INI-generic headers */$text = preg_replace_callback('/<p>[ ]*([#]+)[ ]*([^<]+[^#])[ ]*[#]+[ ]*<\/p>/m',function($m){$l = strlen($m[1]);return '<h'.$l.'>'.$m[2].'</h'.$l.'>';},$text);
		/* Images */
		$text = preg_replace('/\!\[([^\]]*)\]\((?<imgSrc>[^\) ]+|)( .([^\'\"]*).|)\)/m','<img src="$2" alt="$1" title="$4"/>',$text);
		/* Links */
		$text = preg_replace('/<(http:[^>]+)>/m','<a href="$1">$1</a>',$text);
		$text = preg_replace('/\[([^\]]+)\]\((http:[^\) ]+|)( .([^\'\"]*).|)\)/m','<a href="$2" alt="$4" title="$4">$1</a>',$text);
		/* Reference Links */$text = preg_replace_callback('/\[([^\]]+)\]\[([^\]]*)\]/m',function($m) use ($referenceLinks){
			if(isset($referenceLinks[$m[2]])){return '<a href="'.$referenceLinks[$m[2]]['link'].'" alt="'.$referenceLinks[$m[2]]['title'].'" title="'.$referenceLinks[$m[2]]['title'].'">'.$m[1].'</a>';}
			return '<a href="">'.$m[1].'</a>';
		},$text);
		/* INI-Table */
		$text = preg_replace_callback('/[^\|<>]+\|[^\n<>]+/m',function($m){$t = explode('|',$m[0]);foreach($t as $k=>$v){$t[$k] = trim($v);}return '<tr><td>'.implode('</td><td>',$t).'</td></tr>'.PHP_EOL;},$text);
		$text = preg_replace('/<p>[^<]*<tr>/m','<table><tbody>'.PHP_EOL.'<tr>',$text);
		$text = preg_replace('/<\/tr>[^<]*<\/p>/m','</tr>'.PHP_EOL.'</tbody></table>',$text);
		$text = preg_replace_callback('/<table[^>]*>[^<]*<tbody[^>]*>[^<]*(<tr>[^\n]+<\/tr>[^<]*)<tr><td>[:\-]+<\/td>.*?<\/tr>/m',function($m){return '<table><thead>'.PHP_EOL.str_replace(array('<td','td>'),array('<th','th>'),$m[1]).'</thead><tbody>';},$text);
		/* END-Table */

		/* Bold */$text = preg_replace('/\*\*([^*\n]+)\*\*/m','<strong>$1</strong>',$text);
		/* Bold */$text = preg_replace('/__([^_\n]+)__/m','<strong>$1</strong>',$text);
		/* Italic */$text = preg_replace('/\*([^*\n]+)\*/m','<em>$1</em>',$text);
		/* Italic */$text = preg_replace('/_([^_\n]+)_/m','<em>$1</em>',$text);

		/* Cleanup */
		$text = preg_replace('/<p>[ \n\t]*<\/p>/','',$text);
		$text = preg_replace('/[ \n\t]*<\/p>/','</p>',$text);

		return $text;
	}
?>

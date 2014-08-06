<?php
	$GLOBALS['inc']['common'] = array(
		'path'=>'../views/',
		'ext'=>'.php',
		'base'=>'base/a',
		'replace'=>0,
		'css.files'=>array(),
		'js.files'=>array(),
		'output'=>''
	);

	function common_setPath($path = ''){$GLOBALS['inc']['common']['path'] = $path;}
	function common_setBase($base = ''){$GLOBALS['inc']['common']['base'] = $base;}
	function common_setExt($ext = ''){$GLOBALS['inc']['common']['ext'] = $ext;}
	function common_loadScript($script = ''){$GLOBALS['inc']['common']['js.files'][] = $script;}
	function common_findKword($kword,$pool = false){if($pool == false){$pool = &$GLOBALS;}while(!isset($pool[$kword]) && ($b = strpos($kword,'_'))){$poolName = substr($kword,0,$b);$kword = substr($kword,$b+1);if(!isset($pool[$poolName])){return false;}$pool = &$pool[$poolName];}return (isset($pool[$kword])) ? $pool[$kword] : false;}
	function common_resetReplaceIteration(){$GLOBALS['inc']['common']['replace'] = 0;}
	function common_replaceInTemplate($blob,$pool = false,$reps = false){
		if($reps === false){$hasElems = preg_match_all('/{%[a-zA-Z0-9_\.]+%}/',$blob,$reps);if(!$hasElems){return $blob;}$reps = array_unique($reps[0]);}
		//if(isset($GLOBALS['debug'])){print_r($reps);exit;}
		$notFound = array();
		foreach($reps as $rep){$kword = substr($rep,2,-2);
			$word = common_findKword($kword,$pool);
			if($word === false){$notFound[] = $kword;continue;}
			//if(is_array($word)){echo $kword;echo 1;exit;}
			$blob = str_replace($rep,$word,$blob);continue;
		}
		/* Una vez hecho el reemplazo, comprobamos si hay nuevas palabras a ser reemplazadas */
		$hasElems = preg_match_all('/{%[a-zA-Z0-9_\.]+%}/',$blob,$reps);if(!$hasElems){return $blob;}
		$reps = array_unique($reps[0]);
		$notFound = array_fill_keys($notFound,'');
		foreach($reps as $k=>$rep){$kword = substr($rep,2,-2);if(isset($notFound[$kword])){unset($reps[$k]);continue;}}
		if($GLOBALS['inc']['common']['replace'] > 20){print_r($notFound);print_r($reps);exit;}
		if(count($reps)){$GLOBALS['inc']['common']['replace']++;return common_replaceInTemplate($blob,$pool);}
		return $blob;
	}
	function common_renderTemplate($t = false){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		$pathTemp = $GLOBALS['inc']['common']['path'].$t.$GLOBALS['inc']['common']['ext'];
		$pathBase = $GLOBALS['inc']['common']['path'].$GLOBALS['inc']['common']['base'].$GLOBALS['inc']['common']['ext'];
		if($GLOBALS['inc']['common']['ext'] == '.php'){
			ob_start();include($pathTemp);$TEMPLATE['MAIN'] = ob_get_contents();ob_end_clean();
			ob_start();include($pathBase);$GLOBALS['inc']['common']['output'] = ob_get_contents();ob_end_clean();
		}else{
			if($t){$TEMPLATE['MAIN'] = file_get_contents($pathTemp);}
			$GLOBALS['inc']['common']['output'] = file_get_contents($pathBase);
		}

		$GLOBALS['debug'] = false;
		/* INI-BLOG_SCRIPT_VARS */
		if(count($GLOBALS['inc']['common']['js.files'])){$TEMPLATE['PAGE.SCRIPT'] = array_map(function($n){return '<script type="text/javascript" src="'.$n.'"></script>';},$GLOBALS['inc']['common']['js.files']);$TEMPLATE['PAGE.SCRIPT'] = implode(N,$TEMPLATE['PAGE.SCRIPT']);}
		if(count($GLOBALS['inc']['common']['css.files'])){$TEMPLATE['PAGE.STYLE'] = array_map(function($n){return '<link href="'.$n.'" rel="stylesheet"/>';},$GLOBALS['inc']['common']['css.files']);$TEMPLATE['PAGE.STYLE'] = implode(N,$TEMPLATE['PAGE.STYLE']);}
		/* END-BLOG_SCRIPT_VARS */
		/* INI-META */
		if(isset($TEMPLATE['META.DESCRIPTION'])){$TEMPLATE['META.DESCRIPTION'] = str_replace('"','\'',$TEMPLATE['META.DESCRIPTION']);}
		if(isset($TEMPLATE['META.OG.IMAGE'])){$TEMPLATE['META.OG.IMAGE'] = '<meta property="og:image" content="'.$TEMPLATE['META.OG.IMAGE'].'"/>'.PHP_EOL;}
		/* END-META */
		$GLOBALS['inc']['common']['output'] = common_replaceInTemplate($GLOBALS['inc']['common']['output'],$TEMPLATE);
		$GLOBALS['inc']['common']['output'] = preg_replace('/{%[a-zA-Z0-9_\.]+%}/','',$GLOBALS['inc']['common']['output']);
		return $GLOBALS['inc']['common']['output'];
	}
	$GLOBALS['COMMON']['SNIPPETCACHE'] = array();
	function common_loadSnippet($s = false,$pool = false,$sname = false){
		$file = $GLOBALS['inc']['common']['path'].$s.$GLOBALS['inc']['common']['ext'];
		if(!$sname){$sname = $s;}
		if(!isset($GLOBALS['COMMON']['SNIPPETCACHE'][$s])){
			if(!file_exists($file)){return false;}
			ob_start();$_PARAMS = $pool;include($file);$blob = ob_get_contents();ob_end_clean();
			$GLOBALS['COMMON']['SNIPPETCACHE'][$s] = $blob;
		}
		if(!isset($blob)){$blob = $GLOBALS['COMMON']['SNIPPETCACHE'][$s];}
		if($pool){$blob = common_replaceInTemplate($blob,$pool);}
		$GLOBALS['TEMPLATE']['SNIPPETS'][$sname] = $blob;
		return $GLOBALS['TEMPLATE']['SNIPPETS'][$sname];
	}
	function common_r($hash = '',$code = false){
		if(!$code){$code = 302;}
		if(substr($hash,0,4) == 'http'){header('Location: '.$hash,true,$code);exit;}
		header('Location: http://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'].$hash,true,$code);exit;
	}
	/* INI-Pager */
	function common_pagerByElements($currentPage = 1,$totalElements = 0,$perPage = 10,$buttons = 5){
		$totalPages = ceil($totalElements/$perPage);
		return common_pagerByPages($currentPage,$totalPages,$buttons);
	}
	function common_pagerByPages($currentPage = 1,$totalPages = 5,$buttons = 5){		
		$currentPage = $lowerLimit = $upperLimit = min($currentPage,$totalPages);

		for($b = 1; $b < $buttons && $b < $totalPages;){
			if($lowerLimit > 1){$lowerLimit--;$b++;}
			if($b < $buttons && $upperLimit < $totalPages){$upperLimit++;$b++;}
		}

		$items = array();
		$items[0] = ($currentPage > 1) ? 'pe' : 'pd';

		for($i = $lowerLimit; $i <= $upperLimit; $i++) {
			if($i == $currentPage){$items[$i] = 'c';continue;}
			$items[$i] = $i;
		}

		$items[$i] = ($currentPage < $totalPages) ? 'ne' : 'nd';
		return $items;
	}
	function common_pagerArrayToHTML($arr,$pool = false){
		$current = array_search('c',$arr);

		$p = '<ul class="pager">';
		foreach($arr as $k=>$v){
			switch($v){
				case 'pd':$p .= '<li>{%prev%}</li>';break;
				case 'pe':$p .= '<li><a href="{%url%}'.(($current-1 > 1) ? '{%add%}'.($current-1) : '').'">{%prev%}</a></li>';break;
				case 'nd':$p .= '<li>{%next%}</li>';break;
				case 'ne':$p .= '<li><a href="{%url%}{%add%}'.($current+1).'">{%next%}</a></li>';break;
				case 'c':$p .= '<li class="current">'.$k.'</li>';break;
				default:$p .= '<li><a href="{%url%}'.(($k > 1) ? '{%add%}'.$k : '').'">'.$k.'</a></li>';
			}
		}
		$p .= '</ul>';
		return ($pool) ? common_replaceInTemplate($p,$pool) : $p;
	}
	/* END-Pager */


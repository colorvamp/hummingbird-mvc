<?php
	error_reporting(E_STRICT | E_ALL);
	$GLOBALS['w.localhost'] = $_SERVER['SERVER_NAME'] == 'localhost';
	if( substr($_SERVER['SERVER_NAME'],0,7) == '192.168'
	 || (!empty($_SERVER['SERVER_ADDR']) && $_SERVER['SERVER_ADDR'] == '127.0.0.1') ){$GLOBALS['w.localhost'] = true;}

	$protocol = 'http';
	if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') {
		$protocol .= 's';
	}
	$GLOBALS['w.indexURL']   = $protocol.'://'.$_SERVER['SERVER_NAME'];
	$GLOBALS['w.currentURL'] = $protocol.'://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
	$GLOBALS['w.page'] = 1;

	/* INI-Mobile detection */
	$GLOBALS['w.isMobile'] = false;
	if (isset($_SERVER['HTTP_USER_AGENT'])) {
		if (stripos($_SERVER['HTTP_USER_AGENT'],'android') !== false
		 || stripos($_SERVER['HTTP_USER_AGENT'],'iphone') !== false 
		 || stripos($_SERVER['HTTP_USER_AGENT'],'Windows NT') !== false
		 || stripos($_SERVER['HTTP_USER_AGENT'],'X11; Ubuntu; Linux') !== false
		 || stripos($_SERVER['HTTP_USER_AGENT'],'X11; Linux x86_64') !== false) {
			$GLOBALS['w.isMobile'] = false;
		}
	}
	//FIXME: more cases ...
	/* END-Mobile detection */

	$params = parse_url($_SERVER['REQUEST_URI']);
	$params = $params['path'] ?? '';

	/* INI-loading resources */
	if (preg_match('/(css|js|images|fonts)\/.*?\.([a-z]{2,4}$)/',$params,$m)) {
		$m[0] = $_SERVER['DOCUMENT_ROOT'].'/resources/'.urldecode($m[0]);if(!file_exists($m[0])){exit;}
		switch ($m[2]) {
			case 'css': header('Content-type: text/css');ob_start('ob_gzhandler');break;
			case 'js':  header('Content-type: application/javascript');ob_start('ob_gzhandler');break;
			case 'jpg':
			case 'jpeg':header('Content-type: image/jpeg');break;
			case 'png': header('Content-type: image/png');break;
			case 'gif': header('Content-type: image/gif');break;
			case 'ttf':
			case 'woff':
			case 'otf':
			case 'eot': header('Content-type: application/x-unknown-content-type');break;
		}
		readfile($m[0]);
		exit;
	}
	/* END-loading resources */

	/* INI-dispatcher */
	chdir($_SERVER['DOCUMENT_ROOT'].'/resources/libs/');
	$controllersBase = '../controllers/';
	include_once('../init.php');
	do{
		/* Get pagination if any */
		$d = 0;
		while (preg_match('/page\/([0-9]+)$/',$params,$m) && ++$d) {
			if ($d > 1) {common_r($GLOBALS['w.currentURL'],301);}
			$c = strlen($m[0])+1;
			$params = substr($params,0,-$c);
			$GLOBALS['w.currentURL'] = substr($GLOBALS['w.currentURL'],0,-$c);
			$GLOBALS['w.page'] = $m[1];
			if ($GLOBALS['w.page'] < 1) {$GLOBALS['w.page'] = 1;}
		}

		$params = explode('/',$params);
		$params = array_diff($params,['']);

		/* Get the callback */
		$controller = array_shift($params);
		if ($controller == NULL) {$controller = 'index';}
		$controllerPath = $controllersBase.$controller.'.php';
		if (!file_exists($controllerPath)) {
			array_unshift($params,$controller);
			$controller = 'index';
			$controllerPath = $controllersBase.$controller.'.php';
		}

		include_once($controllerPath);
		$command = $unshift = array_shift($params);
		if ($command == NULL) {
			$command = $controller.'_main';
			break;
		}

		$command = $controller.'_'.$command;
		if (function_exists($command)) {break;}
		if (isset($unshift)) {array_unshift($params,$unshift);}
		$command = $controller.'_main';
		if (function_exists($command)) {break;}
	}while(false);
	/* END-dispatcher */

	/* INI-template */
	$GLOBALS['TEMPLATE']['w.indexURL']   = $GLOBALS['w.indexURL'];
	$GLOBALS['TEMPLATE']['w.currentURL'] = $GLOBALS['w.currentURL'];
	$GLOBALS['inc']['common']['output']  = '';
	/* END-template */

	$t = microtime(1);
	$r = call_user_func_array($command,$params);
	echo $GLOBALS['inc']['common']['output'];
	$totalTime = microtime(1)-$t;
	exit;

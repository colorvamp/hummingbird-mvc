<?php
	function redfox_main(){
		common_setPath('../views/redfox/');
		common_setBase('base');

		$params = implode('/',func_get_args());
		$controllersBase = '../controllers/redfox/';
		$GLOBALS['w.redfoxURL'] = $GLOBALS['w.indexURL'].'/redfox';
		$GLOBALS['TEMPLATE']['w.redfoxURL'] = $GLOBALS['w.redfoxURL'];

		/* INI-dispatcher */
		do{
			/* Get pagination if any */
			$d = 0;while(preg_match('/page\/([0-9]+)$/',$params,$m) && ++$d){
				if($d > 1){common_r($GLOBALS['w.currentURL'],301);}
				$c = strlen($m[0])+1;
				$params = substr($params,0,-$c);
				$GLOBALS['w.currentURL'] = substr($GLOBALS['w.currentURL'],0,-$c);
				$GLOBALS['w.page'] = $m[1];if($GLOBALS['w.page'] < 1){$GLOBALS['w.page'] = 1;}
			}

			$params = explode('/',$params);
			$params = array_diff($params,array(''));

			/* Get the callback */
			$controller = array_shift($params);
			if($controller == NULL){$controller = 'index';}
			$controllerPath = $controllersBase.$controller.'.php';
			if(!file_exists($controllerPath)){array_unshift($params,$controller);$controller = 'index';$controllerPath = $controllersBase.$controller.'.php';}

			include_once($controllerPath);
			$command = $unshift = array_shift($params);
			if($command == NULL){$command = $controller.'_main';break;}

			$command = $controller.'_'.$command;if(function_exists($command)){break;}
			if(isset($unshift)){array_unshift($params,$unshift);}
			$command = $controller.'_main';if(function_exists($command)){break;}
		}while(false);
		/* END-dispatcher */

		return call_user_func_array($command,$params);
	}

<?php
	function index_main($param = false){
		$TEMPLATE = &$GLOBALS['TEMPLATE'];
		include_once('api.shoutbox.php');
		include_once('inc.date.php');

		if(isset($_POST['subcommand'])){switch($_POST['subcommand']){
			case 'comment.add':
				$shout = array(
					'shoutText'=>$_POST['shoutText'],
					'shoutAuthor'=>1 /* FIXME: hardcoded */
				);
				$r = shoutbox_save($shout);
				if(isset($r['errorDescription'])){
					print_r($r);
					exit;
				}
				common_r();
			default:
				common_r();
		}}

		/* INI-Checking modes */
		if(($d = dirname($GLOBALS['api']['shoutbox']['db'])) && ( !file_exists($d) || !is_writable($d) || !is_writable($GLOBALS['api']['shoutbox']['db']) )){
			$TEMPLATE['permission.warning'] = common_loadSnippet('snippets/permission.warning');
		}
		/* END-Checking modes */

		/* INI-Painting the shouts */
		if( class_exists('SQLite3') ){
			$shoutOBs = shoutbox_getWhere('shoutResponseTo = 0');
			$TEMPLATE['html.thread'] = '';
			foreach($shoutOBs as $shoutOB){
				$shoutOB['html.shoutDiff'] = date_humanReadable($shoutOB['shoutStamp']);
				$TEMPLATE['html.thread'] .= common_loadSnippet('snippets/shout.node',$shoutOB);
			}
		}
		/* END-Painting the shouts */


		$TEMPLATE['PAGE.TITLE'] = 'colorvamp.com';
		return common_renderTemplate('index');
	}

	function index_view($id = false){
		
	}


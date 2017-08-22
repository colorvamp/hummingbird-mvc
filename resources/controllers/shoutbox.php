<?php
	$TEMPLATE['main.section.shoutbox'] = true;

	function shoutbox_main(){
		global $TEMPLATE;
		$_shoutbox = new _shoutbox_sqlite3();

		if( isset($_POST['subcommand']) ){switch( $_POST['subcommand'] ){
			case 'comment.add':
				$shout = [
					'shoutText'=>$_POST['shoutText']
				];
				$r = $_shoutbox->save($shout);
				if( isset($r['errorDescription']) ){
					print_r($r);
					exit;
				}
				common_r();
			default:
				common_r();
		}}

		if( ($shoutOBs = $_shoutbox->getWhere([])) ){
			$TEMPLATE['shoutOBs'] = $shoutOBs;
		}

		$TEMPLATE['PAGE.TITLE'] = 'shoutbox';
		return common_renderTemplate('shoutbox/main');
	}

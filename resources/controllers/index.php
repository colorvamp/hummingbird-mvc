<?php
	$TEMPLATE['main.section.main'] = true;

	function index_main($param = false){
		global $TEMPLATE;
		include_once('inc.date.php');

		/* INI-Checking modes */
		if( ($d = '../db/.') && ( !file_exists($d) || !is_writable($d) )){
			$TEMPLATE['permission.warning'] = common_loadSnippet('snippets/permission.warning');
		}
		/* END-Checking modes */

		$TEMPLATE['PAGE.TITLE'] = 'Hummingbird';
		return common_renderTemplate('index');
	}

	function index_view($id = false){
		
	}


<?php
	$GLOBALS['tables']['shouts'] = [
		 '_id_'=>'INTEGER AUTOINCREMENT','shoutChannel'=>'TEXT','shoutResponseTo'=>'INTEGER DEFAULT 0'
		,'shoutTitle'=>'TEXT','shoutTitleFixed'=>'TEXT','shoutText'=>'TEXT NOT NULL','shoutTextClean'=>'TEXT NOT NULL'
		,'shoutFollowersCount'=>'INTEGER DEFAULT 0','shoutMailing'=>'TEXT'
		,'shoutAuthor'=>'INTEGER NOT NULL','shoutTags'=>'TEXT','shoutModes'=>'TEXT'
		,'shoutIP'=>'TEXT NOT NULL','shoutImages'=>'TEXT','shoutRating'=>'TEXT','shoutVotesCount'=>'INTEGER'
		,'shoutDate'=>'TEXT NOT NULL','shoutTime'=>'INTEGER NOT NULL','shoutStamp'=>'INTEGER NOT NULL'
		,'shoutStatus'=>'INTEGER DEFAULT 0'];

	$GLOBALS['indexes']['shouts'] = [
		 ['fields'=>array('shoutChannel'=>''),'params'=>[]]
		,['fields'=>array('shoutResponseTo'=>''),'params'=>[]]
		,['fields'=>array('shoutAuthor'=>''),'params'=>[]]
	];

	if(!isset($GLOBALS['api']['shoutbox'])){$GLOBALS['api']['shoutbox'] = [];}
	$GLOBALS['api']['shoutbox'] = array_merge([
		'db'=>'../db/api.shoutbox.db',
		'table.shouts'=>'shouts'
	],$GLOBALS['api']['shoutbox']);

	function shoutbox_save($data = [],$params = []){
		include_once('inc.strings.php');

		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}
		$shoutOB = sqlite3_save($GLOBALS['api']['shoutbox']['table.shouts'],
			$data,
			function($data,$params){
				if(!isset($data['shoutIP']) && isset($_SERVER['SERVER_ADDR'])){$data['shoutIP'] = $_SERVER['SERVER_ADDR'];}
				if(!isset($data['shoutDate'])){$data['shoutDate'] = date('Y-m-d');}
				if(!isset($data['shoutTime'])){$data['shoutTime'] = date('H:i:s');}
				if(!isset($data['shoutStamp'])){$data['shoutStamp'] = time();}

				if(strpos($data['shoutText'],'<') === false){
					if(!function_exists('markdown_toHTML')){include_once('inc.markdown.php');}
					$data['shoutText'] = markdown_toHTML($data['shoutText']);
					$data['shoutText'] = trim($data['shoutText']);
					/* yiutub links */
					$reps = array();
					$reps['youtu.be'] = array('regex'=>'/<p>http:\/\/youtu.be\/([^&<]+)<\/p>/','replacement'=>'<p class="youtube"><object><param name="movie" value="http://www.youtube.com/v/$1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><embed src="http://www.youtube.com/v/$1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true"></embed></object></p>');
					foreach($reps as $k=>$r){$data['shoutText'] = preg_replace($r['regex'],$r['replacement'],$data['shoutText']);}
				}else{
					//FIXME: TODO
				}

				$data['shoutTextClean'] = strings_clean($data['shoutText']);

				/* If shoutResponseTo exists, we must validate */
				if(isset($data['shoutResponseTo'])){do{
					$data['shoutResponseTo'] = preg_replace('/[^0-9]*/','',$data['shoutResponseTo']);
					if(empty($data['shoutResponseTo'])){unset($data['shoutResponseTo']);break;}
					if( !($parentShout = shoutbox_getByID($data['shoutResponseTo'],$params)) ){unset($data['shoutResponseTo']);break;}
					if( !empty($parentShout['shoutChannel']) ){$data['shoutChannel'] = $parentShout['shoutChannel'];}

					/* Si el padre es un maestro no necesitamos contrastar nada más */
					if(empty($parentShout['shoutResponseTo'])){break;}
					/* Obtenemos el hilo para sacar el padre absoluto, necesitamos hacer algunas comprobaciones, 
					 * si el primer padre tiene channelView entonces "shoutResponseTo" solo puede ser uno de los 
					 * padres de canal */
					$firstParent = shoutbox_getThread($parentShout['shoutResponseTo'],$params);
					if(!$firstParent){unset($data['shoutResponseTo']);break;}
					$modes_isChannelView = (strpos($firstParent['shoutModes'],',channelView,') !== false);
					//FIXME: $modes_isChannelView
				}while(false);}

				if(isset($data['shoutTags'])){
					if(is_array($data['shoutTags'])){$data['shoutTags'] = ','.implode(',',$data['shoutTags']).',';}
				}
				return $data;
			},
			$params);

		if(isset($shoutOB['errorDescription'])){return array_merge($shoutOB,['file'=>__FILE__,'line'=>__LINE__]);}
		return $shoutOB;
	}

	function shoutbox_getSingle($whereClause = false,$params = array()){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}
		$table = isset($params['table.name']) ? $params['table.name'] : $GLOBALS['api']['shoutbox']['table.shouts'];

		return sqlite3_getSingle($table,$whereClause,$params);
	}
	function shoutbox_getWhere($whereClause = false,$params = array()){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}
		$table = isset($params['table.name']) ? $params['table.name'] : $GLOBALS['api']['shoutbox']['table.shouts'];

		return sqlite3_getWhere($table,$whereClause,$params);
	}
	function shoutbox_getByID($id = false,$params = []){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}
		$table = isset($params['table.name']) ? $params['table.name'] : $GLOBALS['api']['shoutbox']['table.shouts'];

		$id = preg_replace('/[^0-9]*/','',$id);
		return shoutbox_getSingle('(id = '.$id.')',$params);
	}
	function shoutbox_removeByID($id = false,$params = []){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}
		$table = isset($params['table.name']) ? $params['table.name'] : $GLOBALS['api']['shoutbox']['table.shouts'];

		$id = preg_replace('/[^0-9]*/','',$id);
		return sqlite3_deleteWhere($table,'(id = '.$id.')',$params);
	}
	function shoutbox_getChannel($channelID,$params = array()){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}
		if(!isset($params['order'])){$params['order'] = 'id ASC';}

		$rows = shoutbox_getWhere('(shoutChannel = \''.$channelID.'\')',$params);
		$tree = $index = array();foreach($rows as &$row){$index[$row['id']] = &$row;}
		foreach($index as &$row){
			if(empty($row['shoutResponseTo'])){$tree[$row['id']] = &$row;continue;}
			if(isset($index[$row['shoutResponseTo']])){$index[$row['shoutResponseTo']]['childs'][$row['id']] = &$row;continue;}
			$tree[$row['id']] = &$row;
		}
		return $tree;
	}
	function shoutbox_getThread($shoutID,$params = array()){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}

		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['shoutbox']['db'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$parent = shoutbox_getByID($shoutID,$params);if(!$parent){if($shouldClose){sqlite3_close($params['db']);}return false;}
		/* If is not a parent we get the upper one */
		if($parent['shoutResponseTo'] > 0){$r = shoutbox_getThread($parent['shoutResponseTo'],$params);if($shouldClose){sqlite3_close($params['db']);}return $r;}
		$parent['childs'] = ($parent['shoutFollowersCount'] < 1) ? array() : shoutbox_getFollowers($shoutID,$params);
		if($shouldClose){sqlite3_close($params['db']);}
		return $parent;
	}
	function shoutbox_getFollowers($shoutID,$params = array()){
		if(!function_exists('sqlite3_open')){include_once('inc.sqlite3.php');}
		if(!isset($params['db.file'])){$params['db.file'] = $GLOBALS['api']['shoutbox']['db'];}
		if(!isset($params['order'])){$params['order'] = 'id ASC';}

		return shoutbox_getWhere('(shoutResponseTo = '.$shoutID.')',$params);
	}

	function shoutbox_updateFollowers($shoutID,$change = '+1',$params = array()){
//FIXME: db.file
		$shouldClose = false;if(!$params['db']){$params['db'] = sqlite3_open($GLOBALS['api']['shoutbox']['db']);$r = sqlite3_exec('BEGIN;',$params['db']);$shouldClose = true;}
		$r = sqlite3_exec('UPDATE '.$GLOBALS['api']['shoutbox']['table.shouts'].' SET shoutFollowersCount=shoutFollowersCount'.$change.' WHERE id = '.$shoutID.';',$params['db']);
		if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($params['db']);}return $r;}
		$r = sqlite3_cache_destroy($params['db'],$GLOBALS['api']['shoutbox']['table.shouts']);
		if($shouldClose){$r = sqlite3_close($params['db'],true);if(!$r){return array('errorCode'=>$GLOBALS['DB_LAST_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}
		return true;
	}
	function shoutbox_helper_text_clean($text){
		$clean = str_replace(array(PHP_EOL,'</p><p>'),array(' ',' '),$text);
		$clean = preg_replace('/<\/?[^>]+>/','',$clean);
		return $clean;
	}


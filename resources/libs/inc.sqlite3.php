<?php
	/* inc.sqlite3.php v3.0 */
	//FIXME: poner shutdown callback que cierre todas las bases de datos abiertas
	$GLOBALS['SQLITE3'] = array('queryRetries'=>20);
	if(!isset($GLOBALS['api']['sqlite3'])){$GLOBALS['api']['sqlite3'] = array();}
	$GLOBALS['api']['sqlite3'] = array_merge(array(
		'dir.lock'=>'',
		'dir.cache'=>'../db/cache/sqlite3/',
		'database'=>'../db/database.db',
		'databases'=>array(),
		'use.cache'=>true,
		'iv.padding'=>25
	),$GLOBALS['api']['sqlite3']);

	function sqlite3_open($filePath = false,$mode = 6,$password = null){
		if(is_array($filePath)){do{
			$params = $filePath;
			if(isset($params['db.file'])){$filePath = $params['db.file'];}

			if(!is_string($filePath)){$filePath = $GLOBALS['api']['sqlite3']['database'];}
		}while(false);}

		//FIXME: si se intenta abrir una base de datos que ya esté abierta, se podría enviar la conexion cacheada, aunq habría problemas con los close
		/* Mode 6 = (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE) */
		$oldmask = umask(0);
		if(!file_exists($filePath)){$r = file_put_contents($filePath,'',LOCK_EX);chmod($filePath,0777);if($r === false){return false;}}
		$filePath = realpath($filePath);
		$fileSum = md5($filePath);
		$fileName = basename($filePath);

		try{$db = new SQLite3($filePath,$mode,$password);}
		catch(Exception $e){sqlite3_close($db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = 14;$GLOBALS['DB_LAST_QUERY_ERROR'] = 'unable to open database file';return false;}
		$GLOBALS['api']['sqlite3']['databases'][$fileSum] = array('resource'=>$db,'fileMode'=>$mode,'filePath'=>$filePath,'fileName'=>$fileName,'fileSum'=>$fileSum);
		if($mode == 6){
			if(!is_writable($filePath)){sqlite3_close($db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = 14;$GLOBALS['DB_LAST_QUERY_ERROR'] = 'unable to open database file';return false;}
			if(!filesize($filePath)){$r = sqlite3_exec('PRAGMA main.page_size = 8192;PRAGMA main.cache_size=10000;PRAGMA main.locking_mode=EXCLUSIVE;PRAGMA main.synchronous=NORMAL;PRAGMA main.journal_mode=WAL;PRAGMA temp_store=MEMORY;',$db);}
			else{$r = sqlite3_exec('PRAGMA main.page_size = 8192;PRAGMA temp_store=MEMORY;',$db);}
		}
		$db->busyTimeout(60);
		umask($oldmask);
		if($password){
			$db->password = $password;
			$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC);
			$iv = mcrypt_create_iv($iv_size,MCRYPT_RAND);
			$db->createFunction('encrypt',function($data) use ($password,$iv){
				return base64_encode($iv).'.'.openssl_encrypt($data,'AES-256-CBC',$password,0,$iv);
			});
			$db->createFunction('decrypt',function($data) use ($password){
				$iv = base64_decode(substr($data,0,$GLOBALS['api']['sqlite3']['iv.padding']-1));
				return openssl_decrypt(substr($data,$GLOBALS['api']['sqlite3']['iv.padding']),'AES-256-CBC',$password,0,$iv);
			});
		}
		return $db;
	}
	function sqlite3_close(&$db = false,$shouldCommit = false){
		if($shouldCommit){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();}
		$sqliteOB = sqlite3_get($db);
		/* Si la base de datos estaba abierta, liberamos el lock, pero solo si este proceso es quien ha registrado este lock ($checkpid = true) */
		if($sqliteOB && $sqliteOB['fileMode'] == 6){$r = sqlite3_lock_release($db,true);}
		$db->close();
		$db = false;
		return $shouldCommit ? $r : true;
	}
	function sqlite3_get($f){
		$sqliteOB = false;$t = gettype($f);
		if($t == 'object'){foreach($GLOBALS['api']['sqlite3']['databases'] as $sum=>$database){if($database['resource'] === $f){$sqliteOB = $database;}}}
		else if($t == 'string'){$f = realpath($f);foreach($GLOBALS['api']['sqlite3']['databases'] as $sum=>$database){if($database['filePath'] === $f){$sqliteOB = $database;}}}	
		return $sqliteOB;
	}
	function sqlite3_lock_acquire($f,$wait = false){
		if(!($sqliteOB = sqlite3_get($f))){return false;}
		$lock = $sqliteOB['filePath'].'.lock';
		$pid = ($isLocked = file_exists($lock)) ? file_get_contents($lock) : false;

		/* Si la base de datos está bloqueada pero el proceso que la bloqueó ya no existe */
		if($isLocked && !file_exists('/proc/'.$pid)){$isLocked = !sqlite3_lock_release($f);}
		if($isLocked && !$wait){return false;}
		while($isLocked && $wait){
//FIXME: esto puede entrar en bucle infinito
			usleep(200000/* 2 x Décima parte de un segundo */);
		}
		$pid = getmypid();
//FIXME: registrar un tick que vaya actualizando esto
		$oldmask = umask(0);$r = file_put_contents($lock,$pid,LOCK_EX);umask($oldmask);
		return $r;
	}
	function sqlite3_lock_release($f,$checkpid = false){
		if(!($sqliteOB = sqlite3_get($f))){return false;}
		$lock = $sqliteOB['filePath'].'.lock';
		if(!file_exists($lock)){return true;}
		/* Si el pid del proceso que estableción el bloqueo no coincide con el del proceso actual y no
		 * queremos liberarlo por la fuerza salimos */
		if($checkpid){$pid = file_get_contents($lock);if($pid != getmypid()){return false;}}
		$r = unlink($lock);
		return $r;
	}
	function sqlite3_unlock($db){
		//FIXME: $db debe poder ser resource o el path
		if(is_string($db)){$filePath = $db;}
//FIXME: intentar sacar las tablas
		/* INI-timeout Controlamos los ficheros -shm y -wal, si hay algún fallo o lo que sea y quedan olvidados, 
		 * que no mantengan una base de datos bloqueada infinitamente, el timeout es de 1 minuto (1*60) */
		foreach(array('-shm','-wal') as $ext){
			$f = $filePath.$ext;
			if(!file_exists($f)){continue;}
			$stat = stat($f);$diff = time()-$stat['mtime'];
			if($diff < 1*60){/*sqlite3_close($db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = 14;$GLOBALS['DB_LAST_QUERY_ERROR'] = 'unable to open database file';*/return false;}
			unlink($f);
		}
		/* END-timeout */
		return true;
	}

	function sqlite3_cache_set($db,$table,$query,$data){
		$dbObj = false;foreach($GLOBALS['api']['sqlite3']['databases'] as $sum=>$database){if($database['resource'] === $db){$dbObj = $database;}}
		if(!$dbObj){return false;}
		$cachePath = $GLOBALS['api']['sqlite3']['dir.cache'].$dbObj['fileSum'].'/'.md5($table).'/';
		if(!file_exists($cachePath)){$oldmask = umask(0);$r = mkdir($cachePath,0777,1);umask($oldmask);}
		$cacheFile = $cachePath.md5($query);
		$r = file_put_contents($cacheFile,json_encode($data));
		return true;
	}
	function sqlite3_cache_get($db,$table,$query){
		$dbObj = false;foreach($GLOBALS['api']['sqlite3']['databases'] as $sum=>$database){if($database['resource'] === $db){$dbObj = $database;}}
		if(!$dbObj){return false;}
		$cacheFile = $GLOBALS['api']['sqlite3']['dir.cache'].$dbObj['fileSum'].'/'.md5($table).'/'.md5($query);
		if(!file_exists($cacheFile)){return false;}
		return json_decode(file_get_contents($cacheFile),1);
	}
	function sqlite3_cache_destroy($db,$table = false,$query = false){
		$dbObj = false;foreach($GLOBALS['api']['sqlite3']['databases'] as $sum=>$database){if($database['resource'] === $db){$dbObj = $database;}}
		if(!$dbObj){return false;}
		$cachePath = $GLOBALS['api']['sqlite3']['dir.cache'].$dbObj['fileSum'].'/';
		if($table != false){$cachePath .= md5($table).'/';}
		if($query != false){$cachePath .= md5($query);}
		if(!file_exists($cachePath)){return false;}
		sqlite3_helper_rm($cachePath);
		return true;
	}
	function sqlite3_config_cacheDisable(){
		if(!$GLOBALS['api']['sqlite3']['use.cache']){return true;}
		$GLOBALS['api']['sqlite3']['use.cache.old'] = $GLOBALS['api']['sqlite3']['use.cache'];
		$GLOBALS['api']['sqlite3']['use.cache'] = false;
	}
	function sqlite3_config_cacheRestore(){
		if(!isset($GLOBALS['api']['sqlite3']['use.cache.old'])){return true;}
		$GLOBALS['api']['sqlite3']['use.cache'] = $GLOBALS['api']['sqlite3']['use.cache.old'];
		unset($GLOBALS['api']['sqlite3']['use.cache.old']);
	}
	function sqlite3_helper_rm($path,$avoidCheck=false){
		//FIXME: el preg_Replace no tiene sentido, debería ser [\/]*$
		if(!$avoidCheck){$path = preg_replace('/\/$/','/',$path);if(!file_exists($path)){return;}}
		if(!is_dir($path)){unlink($path);}
		if($handle = opendir($path)){while(false !== ($file = readdir($handle))){if(in_array($file,array('.','..'))){continue;}if(is_dir($path.$file)){sqlite3_helper_rm($path.$file.'/',true);continue;}unlink($path.$file);}closedir($handle);}
		rmdir($path);
	}

	function sqlite3_query($q,$db){$oldmask = umask(0);$r = @$db->query($q);$secure = 0;while($secure < 5 && !$r && $db->lastErrorCode() == 5){usleep(200000);$r = @$db->query($q);$secure++;}umask($oldmask);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();return $r;}
	function sqlite3_querySingle($q,$db){$oldmask = umask(0);$r = @$db->querySingle($q,1);$secure = 0;while($secure < $GLOBALS['SQLITE3']['queryRetries'] && !$r && $db->lastErrorCode() == 5){usleep(200000);$r = @$db->querySingle($q,1);$secure++;}umask($oldmask);return $r;}
	function sqlite3_exec($q,$db){$oldmask = umask(0);$r = @$db->exec($q);$secure = 0;while($secure < $GLOBALS['SQLITE3']['queryRetries'] && !$r && $db->lastErrorCode() == 5){usleep(200000);$r = @$db->exec($q);$secure++;}umask($oldmask);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();return $r;}
	function sqlite3_fetchArray($r,$db){
		$row = @$r->fetchArray(SQLITE3_ASSOC);
		if($row === null){$secure = 0;while($secure < $GLOBALS['SQLITE3']['queryRetries'] && $row === null && $db->lastErrorCode() == 5){usleep(200000);$row = @$r->fetchArray(SQLITE3_ASSOC);$secure++;}}
		$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();return $row;
	}
	function sqlite3_r($query,$file = __FILE__,$line = __LINE__){return array('query'=>$query,'errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>$file,'line'=>$line);}
	function sqlite3_tableExists($tableName,$db = false){$row = sqlite3_querySingle('SELECT * FROM sqlite_master WHERE name = \''.$tableName.'\';',$db);return $row;}

	function sqlite3_createTable($tableName,$array,$db){
		$query = 'CREATE TABLE ['.$tableName.'] (';
		$tableKeys = array();
		$hasAutoIncrement = false;
		$tableKeys = array();foreach($array as $key=>$value){$array[$key] = $db->escapeString($value);if($key[0] == '_' && $key[strlen($key)-1] == '_'){$key = substr($key,1,-1);if(strpos($value,'INTEGER AUTOINCREMENT') !== false){$query .= '\''.$key.'\' INTEGER PRIMARY KEY AUTOINCREMENT,';continue;}$query .= '\''.$key.'\' '.$value.',';$tableKeys[] = $key;continue;}$query .= '\''.$key.'\' '.$value.',';}
		if(count($tableKeys) > 0){$query .= 'PRIMARY KEY ('.implode(',',$tableKeys).'),';}
		$query = substr($query,0,-1).');';

		$q = sqlite3_exec($query,$db);
		if(!$q){return sqlite3_r($query,__FILE__,__LINE__);}
		return true;
	}

	function sqlite3_createIndex($tableName = '',$indexes = array(),$db = false){
		foreach($indexes as $index){
			$indexname = 'idx';foreach($index['fields'] as $n=>$p){$indexname .= '-'.$n;if(strlen($p)){$indexname .= '.'.$p;}}
			$query = 'CREATE '.(isset($index['params']['unique']) ? 'UNIQUE' : '').' INDEX ['.$indexname.'] ON ['.$tableName.'] (';
			foreach($index['fields'] as $n=>$p){$query .= '\''.$n.'\' '.$p.',';}
			$query = substr($query,0,-1).');';

			$q = sqlite3_exec($query,$db);
			if(!$q){return sqlite3_r($query,__FILE__,__LINE__);}
		}
		return true;
	}

	function sqlite3_insertIntoTable($tableName,$array,$db = false,$aTableName = false){
		return sqlite3_insertIntoTable2($tableName,$array,array('db'=>$db),$aTableName);
	}
	function sqlite3_insertIntoTable2($tableName,$array,$params = array(),$aTableName = false){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open( (isset($params['db.file']) ? $params['db.file'] : $GLOBALS['api']['sqlite3']['database']) ,6, (isset($params['db.password']) ? $params['db.password'] : false) );$shouldClose = true;}
		$tableKeys = array();foreach($array as $key=>$value){$array[$key] = $params['db']->escapeString($value);if($key[0] == '_' && $key[strlen($key)-1] == '_'){$newkey = substr($key,1,-1);$tableKeys[$newkey] = $array[$newkey] = $value;unset($array[$key]);}}
		if(!isset($params['db.encrypt'])){$params['db.encrypt'] = array();}

		$query = 'INSERT INTO ['.$tableName.'] ';
		$tableIds = $tableValues = '(';
		/* SQL uses single quotes to delimit string literals. */
		foreach($array as $key=>&$value){
			$tableIds .= '\''.$key.'\',';
			/* INI-password If a password was supplied and the field is encryptable we cipher it */
			if(  isset($params['db.encrypt'][$key]) ){$tableValues .= 'encrypt(\''.$value.'\'),';continue;}
			/* END-password */
			$tableValues .= '\''.$value.'\',';
		}
		$tableIds = substr($tableIds,0,-1).')';$tableValues = substr($tableValues,0,-1).')';
		$query .= $tableIds.' VALUES '.$tableValues;

		$r = sqlite3_exec($query,$params['db']);
		if(!$r && $params['db']->lastErrorCode() == 1){
			if(strpos($params['db']->lastErrorMsg(),'has no column named')){	if($shouldClose){sqlite3_close($params['db']);}return sqlite3_r($query,__FILE__,__LINE__);}
			if(strpos($params['db']->lastErrorMsg(),'may not be NULL')){		if($shouldClose){sqlite3_close($params['db']);}return sqlite3_r($query,__FILE__,__LINE__);}
			if(!isset($GLOBALS['tables'][$tableName]) && !isset($GLOBALS['tables'][$aTableName])){if($shouldClose){sqlite3_close($params['db']);}return sqlite3_r($query,__FILE__,__LINE__);}
			$r = sqlite3_createTable($tableName,($aTableName ? $GLOBALS['tables'][$aTableName] : $GLOBALS['tables'][$tableName]),$params['db']);
			if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($params['db']);}return sqlite3_r($query,__FILE__,__LINE__);}
			if(isset($GLOBALS['indexes'][$tableName])){$r = sqlite3_createIndex($tableName,$GLOBALS['indexes'][$tableName],$params['db']);if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($params['db']);}return $r;}}
			if(($tableName != $aTableName) && isset($GLOBALS['indexes'][$aTableName])){$r = sqlite3_createIndex($tableName,$GLOBALS['indexes'][$aTableName],$params['db']);if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($params['db']);}return $r;}}
			$r = sqlite3_exec($query,$params['db']);
		}

		$GLOBALS['DB_LAST_QUERY_ID'] = $params['db']->lastInsertRowID();
		if(!$r && $params['db']->lastErrorCode() == 19 && count($tableKeys)){
			/* Hay errores que pueden ser significativos, como una contraseña que no puede ser null, pero saltarán incluso si quiero actualizar */
			if(substr($params['db']->lastErrorMsg(),-15) == 'may not be NULL'){	if($shouldClose){sqlite3_close($params['db']);}return sqlite3_r($query,__FILE__,__LINE__);}
			if(substr($params['db']->lastErrorMsg(),0,7) == 'column ' && count($tableKeys) < 2){
				$columnName = substr($params['db']->lastErrorMsg(),7,-14);
				if(!isset($tableKeys[$columnName])){if($shouldClose){sqlite3_close($params['db']);}return sqlite3_r($query,__FILE__,__LINE__);}
			}
			$query = 'UPDATE \''.$tableName.'\' SET ';
			$tableKeysValues = array_keys($tableKeys);
			foreach($array as $key=>$value){
				if(isset($tableKeys[$key])){continue;}
				if(  isset($params['db.encrypt'][$key]) ){$query .= '\''.$key.'\' = encrypt(\''.$value.'\'),';continue;}
				$query .= '\''.$key.'\'=\''.$value.'\',';
			}
			$query = substr($query,0,-1).' WHERE';
			foreach($tableKeys as $k=>$v){$query .= ' '.$k.' = \''.$v.'\' AND';}
			$query = substr($query,0,-4).';';

			$r = sqlite3_exec($query,$params['db']);
			if(!$r){if($shouldClose){sqlite3_close($params['db']);}return sqlite3_r($query,__FILE__,__LINE__);}
			$GLOBALS['DB_LAST_QUERY_ID'] = array_shift($tableKeys);
		}

		if(!$r){return sqlite3_r($query,__FILE__,__LINE__);}
		$r = sqlite3_cache_destroy($params['db'],$tableName);
		/* Da lo mismo que no se esté usando caché explícitamente, si se actualiza esta tabla debemos
		 * eliminar cualquier rastro de caché para evitar datos inválido al hacer consultas que podrian estar cacheadas */
		if($shouldClose && !($r = sqlite3_close($params['db'],true))){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}
		return array('id'=>$GLOBALS['DB_LAST_QUERY_ID'],'error'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'errno'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'query'=>$query);
	}

	function sqlite3_getFullText($tableName = false,$criteria = '',$fields = array(),$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open((isset($params['db.file'])) ? $params['db.file'] : $GLOBALS['api']['sqlite3']['database'],SQLITE3_OPEN_READONLY);$shouldClose = true;}
		$selectString = '*';if(isset($params['selectString'])){$selectString = $params['selectString'];}
		//FIXME: dar soporte a los 2 tipos de limit
		$limitRows = 500;if(isset($params['row.limit'])){$limitRows = $params['row.limit'];}
		$words = explode(' ',$criteria);foreach($words as $k=>$word){$words[$k] = $params['db']->escapeString($word);}
		$countWords = count($words);
		$modeMultipleWords = ($countWords > 1);
		$GLOBALS['DB_LAST_QUERY'] = 'SELECT '.$selectString.' FROM ['.$tableName.'] WHERE ';
		foreach($fields as $field){$GLOBALS['DB_LAST_QUERY'] .= '('.$field.' LIKE \'%'.implode('%\' OR '.$field.' LIKE \'%',$words).'%\') OR ';}
		$GLOBALS['DB_LAST_QUERY'] = substr($GLOBALS['DB_LAST_QUERY'],0,-4).';';

		$result = array();
		$r = sqlite3_query($GLOBALS['DB_LAST_QUERY'],$params['db']);
		if($GLOBALS['DB_LAST_QUERY_ERRNO']){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}
		$i = 0;while($row = sqlite3_fetchArray($r,$params['db'])){
			$i++;
			$score = 0;
			foreach($fields as $k=>$field){
				if($modeMultipleWords && stripos($row[$field],$criteria) !== false){$score += (2*strlen($criteria))+$countWords;continue;}
				$row[$field] = ' '.$row[$field].' ';
				$total = $countWords;
				foreach($words as $word){
					if(stripos($row[$field],' '.$word.' ') !== false){$score += strlen($word)+$total;continue;}
					if(stripos($row[$field],$word) !== false){$score += (0.5*strlen($word))+$total;continue;}
					$total--;
				}
			}
			$result[ceil($score).'.'.$i] = $row;
			krsort($result);
			if(count($result) > $limitRows){array_splice($result,$limitRows);}
		}
		return array_values($result);
	}

	function sqlite3_getSingle($tableName = false,$whereClause = false,$params = array()){
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open( (isset($params['db.file']) ? $params['db.file'] : $GLOBALS['api']['sqlite3']['database']) ,6, (isset($params['db.password']) ? $params['db.password'] : false) );$shouldClose = true;}
		$selectString = '*';if(isset($params['selectString'])){$selectString = $params['selectString'];}
		$GLOBALS['DB_LAST_QUERY'] = 'SELECT '.$selectString.' FROM ['.$tableName.'] '.(($whereClause !== false) ? 'WHERE '.$whereClause : '');
		if(isset($params['group'])){$GLOBALS['DB_LAST_QUERY'] .= ' GROUP BY '.$params['db']->escapeString($params['group']);}
		if(isset($params['order'])){$GLOBALS['DB_LAST_QUERY'] .= ' ORDER BY '.$params['db']->escapeString($params['order']);}
		if(isset($params['limit'])){$GLOBALS['DB_LAST_QUERY'] .= ' LIMIT '.$params['db']->escapeString($params['limit']);}
		$row = sqlite3_querySingle($GLOBALS['DB_LAST_QUERY'],$params['db']);
		if( isset($params['db.encrypt']) ){sqlite3_rowDecrypt($row,$params);}
		$GLOBALS['DB_LAST_QUERY_ERRNO'] = $params['db']->lastErrorCode();
		$GLOBALS['DB_LAST_QUERY_ERROR'] = $params['db']->lastErrorMsg();
		if($shouldClose){sqlite3_close($params['db']);}
		return $row;
	}
	function sqlite3_getWhere($tableName = false,$whereClause = false,$params = array()){
		if(!isset($params['indexBy'])){$params['indexBy'] = 'id';}
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open( (isset($params['db.file']) ? $params['db.file'] : $GLOBALS['api']['sqlite3']['database']) ,6, (isset($params['db.password']) ? $params['db.password'] : false) );$shouldClose = true;}
		$selectString = '*';if(isset($params['selectString'])){$selectString = $params['selectString'];}
		$GLOBALS['DB_LAST_QUERY'] = 'SELECT '.$selectString.' FROM ['.$tableName.'] '.(($whereClause !== false) ? 'WHERE '.$whereClause : '');
		if(isset($params['group'])){$GLOBALS['DB_LAST_QUERY'] .= ' GROUP BY '.$params['db']->escapeString($params['group']);}
		if(isset($params['order'])){$GLOBALS['DB_LAST_QUERY'] .= ' ORDER BY '.$params['db']->escapeString($params['order']);}
		if(isset($params['limit'])){$GLOBALS['DB_LAST_QUERY'] .= ' LIMIT '.$params['db']->escapeString($params['limit']);}

		if($GLOBALS['api']['sqlite3']['use.cache'] && ($rows = sqlite3_cache_get($params['db'],$tableName,$GLOBALS['DB_LAST_QUERY']))){
			if( isset($params['db.encrypt']) ){sqlite3_rowsDecrypt($rows,$params);}
			if($shouldClose){sqlite3_close($params['db']);}
			return $rows;
		}
		$r = sqlite3_query($GLOBALS['DB_LAST_QUERY'],$params['db']);
		$rows = array();

		if($r && $params['indexBy'] !== false){while($row = sqlite3_fetchArray($r,$params['db'])){$rows[$row[$params['indexBy']]] = $row;}}
		if($r && $params['indexBy'] === false){while($row = sqlite3_fetchArray($r,$params['db'])){$rows[] = $row;}}
		if($GLOBALS['api']['sqlite3']['use.cache']){sqlite3_cache_set($params['db'],$tableName,$GLOBALS['DB_LAST_QUERY'],$rows);}

		if( isset($params['db.encrypt']) ){sqlite3_rowsDecrypt($rows,$params);}
		if($shouldClose){sqlite3_close($params['db']);}

		return $rows;
	}
	function sqlite3_deleteWhere($tableName = false,$whereClause = false,$params = array()){
		$shouldClose = false;if(!isset($params['db']) || !$params['db']){$params['db'] = sqlite3_open((isset($params['db.file'])) ? $params['db.file'] : $GLOBALS['api']['sqlite3']['database']);sqlite3_exec('BEGIN',$params['db']);$shouldClose = true;}
		$GLOBALS['DB_LAST_QUERY'] = 'DELETE FROM ['.$tableName.'] '.(($whereClause !== false) ? 'WHERE '.$whereClause : '');
		$r = sqlite3_exec($GLOBALS['DB_LAST_QUERY'],$params['db']);
		$GLOBALS['DB_LAST_QUERY_CHANG'] = $params['db']->changes();
		$r = sqlite3_cache_destroy($params['db'],$tableName);
		if($shouldClose){$r = sqlite3_close($params['db'],true);if(!$r){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}}
		return $r;
	}
	/* INI-Decrypt row data */
	function sqlite3_rowDecrypt(&$row,&$params = array()){
		if(isset($params['db']->password)){$params['db.password'] = $params['db']->password;}
		if(!isset($params['db.password']) || !isset($params['db.encrypt'])){return $row;}
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC);
		foreach($row as $f=>&$data){
			if(isset($params['db.encrypt'][$f])){
				$iv = base64_decode(substr($data,0,$GLOBALS['api']['sqlite3']['iv.padding']-1));
				if(strlen($iv) < 16){continue;}
				$data = openssl_decrypt(substr($data,$GLOBALS['api']['sqlite3']['iv.padding']),'AES-256-CBC',$params['db.password'],0,$iv);
			}
		}
		return $row;
	}
	function sqlite3_rowsDecrypt(&$rows,&$params = array()){
		if(isset($params['db']->password)){$params['db.password'] = $params['db']->password;}
		if(!isset($params['db.password']) || !isset($params['db.encrypt'])){return $rows;}
		$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128,MCRYPT_MODE_CBC);
		foreach($rows as &$row){
			foreach($row as $f=>&$data){
				if(isset($params['db.encrypt'][$f])){
					$iv = base64_decode(substr($data,0,$GLOBALS['api']['sqlite3']['iv.padding']-1));
					if(strlen($iv) < 16){continue;}
					$data = openssl_decrypt(substr($data,$GLOBALS['api']['sqlite3']['iv.padding']),'AES-256-CBC',$params['db.password'],0,$iv);
				}
			}
		}
		return $rows;
	}
	/* END-Decrypt row data */

	//FIXME: rehacer
	/* $origTableName = string - $tableSchema = string ($GLOBALS['tables'][$tableSchema]) */
	function sqlite3_updateTableSchema($origTableName,$db = false,$tableID = 'id',$schemaName = false){
		$tableName = $origTableName;if(!$schemaName){$schemaName = $tableName;}
		$tableSchema = $GLOBALS['tables'][$schemaName];

		/* Averiguamos las keys automáticamente */
		$tableKeys = array();foreach($tableSchema as $key=>$value){if($key[0] == '_' && $key[strlen($key)-1] == '_'){$newkey = substr($key,1,-1);$tableKeys[$newkey] = $key;}}
		//print_r($tableKeys);

		$shouldClose = false;
		$fields = implode(',',array_diff(array_keys($tableSchema),array_values($tableKeys)));
		foreach($tableKeys as $k=>$v){$fields .= ','.$k.' as '.$v;}
		$continue = true;while($continue){
			$r = sqlite3_querySingle('SELECT '.$fields.' FROM ['.$origTableName.']',$db);
			if($r){break;}
			if(!$r && substr($db->lastErrorMsg(),0,14) == 'no such column'){
				$errorField = substr($db->lastErrorMsg(),16);
				$fields = preg_replace('/(^|,)'.$errorField.'(,|$)/',',',$fields);
				if($fields[0] == ','){$fields = substr($fields,1);}
				continue;
			}
			if(!$r){
				echo 'error '.$db->lastErrorMsg();exit;
			}
		}

		sqlite3_exec('BEGIN;',$db);
		$GLOBALS['DB_LAST_QUERY'] = 'ALTER TABLE ['.$origTableName.'] RENAME TO ['.$origTableName.'_backup];';
		$r = sqlite3_exec($GLOBALS['DB_LAST_QUERY'],$db);
		if($db->lastErrorCode() > 0){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>$db->lastErrorCode(),'errorDescription'=>$db->lastErrorMsg(),'file'=>__FILE__,'line'=>__LINE__);}

		$a = sqlite3_query('SELECT '.$fields.' FROM ['.$origTableName.'_backup];',$db);
		$rows = array();if($r){while($row = $a->fetchArray(SQLITE3_ASSOC)){
			$r = sqlite3_insertIntoTable($tableName,$row,$db,$schemaName);
			if(!$r['OK']){if($shouldClose){$db->close();}return array('errorCode'=>$r['errno'],'errorDescription'=>$r['error'],'query'=>$r['query'],'file'=>__FILE__,'line'=>__LINE__);}
		}}

		$oldCount = sqlite3_querySingle('SELECT count(*) as count FROM ['.$origTableName.'_backup];',$db);
		$newCount = sqlite3_querySingle('SELECT count(*) as count FROM ['.$origTableName.'];',$db);
		if($oldCount['count'] != $newCount['count']){if($shouldClose){sqlite3_close($db);}return array('errorCode'=>3,'errorDescription'=>'COUNT_ERROR','file'=>__FILE__,'line'=>__LINE__);}
		$r = $db->exec('DROP TABLE IF EXISTS ['.$origTableName.'_backup];');
		$db->exec('COMMIT;');

		$r = sqlite3_cache_destroy($db,$origTableName);
		if($shouldClose){$db->close();}
		return true;
	}

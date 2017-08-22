<?php
	$GLOBALS['SQLITE3'] = ['queryRetries'=>20];
	if( !isset($GLOBALS['api']['sqlite3']) ){$GLOBALS['api']['sqlite3'] = [];}
	$GLOBALS['api']['sqlite3'] = array_merge([
		'dir.lock'=>'',
		'dir.cache'=>'../db/cache/sqlite3/',
		'databases'=>[],
		'use.cache'=>true,
		'iv.padding'=>25
	],$GLOBALS['api']['sqlite3']);

	class _sqlite3{
		public $db       = '../db/_sqlite3.db';
		public $table    = 'hummingbird';
		public $otable   = 'hummingbird';
		public $client   = false;
		public $password = false;
		public $useCache = true;
		public $indexBy  = '_id';
		function __construct($params = []){
			$this->file_path  = realpath($this->db);
			$this->file_sum   = md5($this->file_path);
			$this->path_cache = $GLOBALS['api']['sqlite3']['dir.cache'].$this->file_sum.'/'.md5($this->table).'/';
		}
		function instance($mode = 'r'){
			if( $this->client ){return true;}
			if( isset($GLOBALS['api']['sqlite3']['db'][$this->file_sum]['client']) ){
				$this->client = &$GLOBALS['api']['sqlite3']['db'][$this->file_sum]['client'];
				return true;
			}

			if( $mode == 'w' ){$mode = (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE);}
			if( $mode == 'r' ){$mode = (SQLITE3_OPEN_READONLY);}
			$oldmask = umask(0);
			if( !file_exists($this->db) ){
				$r = file_put_contents($this->db,'',LOCK_EX);
				if( $r === false ){return false;}
				chmod($this->db,0777);
			}
			$this->file_path  = realpath($this->db);
			$this->file_sum   = md5($this->file_path);
			$this->file_name  = basename($this->file_path);
			$this->path_cache = $GLOBALS['api']['sqlite3']['dir.cache'].$this->file_sum.'/'.md5($this->table).'/';

			try{
				$this->client = new SQLite3($this->file_path,$mode,$this->password);
			}catch( Exception $e ){
				sqlite3_close($this->client);
				$this->db_last_errno = 14;
				$this->db_last_error = 'unable to open database file';
				return false;
			}
			$GLOBALS['api']['sqlite3']['databases'][$this->file_sum] = [
				 'client'=>$this->client
				,'mode'=>$mode
				,'path'=>$this->file_path
				,'mame'=>$this->file_name
				,'sum'=>$this->file_sum
			];
			if( $mode == 6 ){
				/* Mode 6 = (SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE) */
				if( !is_writable($this->file_path) ){
					sqlite3_close($db);
					$this->db_last_errno = 14;
					$this->db_last_error = 'unable to open database file';
					return false;
				}
				if( !filesize($this->file_path) ){
					$r = $this->client->exec('PRAGMA main.page_size = 8192;PRAGMA main.cache_size=10000;PRAGMA main.locking_mode=EXCLUSIVE;PRAGMA main.synchronous=NORMAL;PRAGMA main.journal_mode=WAL;PRAGMA temp_store=MEMORY;');
				}else{
					$r = $this->client->exec('PRAGMA main.page_size = 8192;PRAGMA temp_store=MEMORY;');
				}
			}
			$this->client->busyTimeout(60);
			umask($oldmask);
			if( false && !empty($this->password) ){
				//FIXME: arreglar
				$db->password = $this->password;
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

			return true;
		}

		function count($clause = false,$params = []){return $this->_count($clause,$params);}
		function distinct($field = '',$clause = false,$params = []){return $this->_distinct($field,$clause,$params);}
		function getByID($id = false,$params = []){return $this->_getByID($id,$params);}
		function getByIDs($ids = [],$params = []){return $this->_getByIDs($ids,$params);}
		function getSingle($clause = false,$params = []){return $this->_getSingle($clause,$params);}
		function getWhere($clause = false,$params = []){return $this->_getWhere($clause,$params);}
		function removeWhere($clause = false,$params = []){return $this->_removeWhere($clause,$params);}
		function removeByID($id = false,$params = []){return $this->_removeByID($id,$params);}
		function updateWhere($clause = false,$data = [],$params = []){return $this->_updateWhere($clause,$data,$params);}
		function tables($params = []){return $this->_tables($params);}
		function results($q = false,$params = []){return $this->_results($q,$params);}
		function exec($query = '',$params = []){return $this->_exec($query,$params);}
		function query($query = '',$params = []){return $this->_query($query,$params);}
		function iterator($clause = false,$callback = false,$params = []){return $this->_iterator($clause,$callback,$params);}
		function validate(&$data = [],&$oldData = []){return $data;}
		function save(&$data = [],$params = []){return $this->_save($data,$params);}
		function search($criteria = '',$params = []){return $this->_search($criteria,$params);}
		function log(&$data = [],&$oldData = []){}

		function _clause($clause = [],$field = ''){
			$query = ' ( ';
			foreach( $clause as $k=>$c ){
				if( $k == '$or' ){
					$s = [];
					foreach( $c as $j ){$s[] = $this->_clause($j);}
					$query .= '('.implode(' OR ',$s).') AND ';
					continue;
				}
				if( $k == '$in' ){
					/* FIXME: Quiero array map */
					foreach( $c as &$j ){if( !is_integer($j) ){$j = '"'.$j.'"';}}
					$query .= $field.' IN ('.implode(',',$c).') AND ';
					continue;
				}
				if( $k == '$nin' ){
					/* FIXME: Quiero array map */
					foreach( $c as &$j ){if( !is_integer($j) ){$j = '"'.$j.'"';}}
					$query .= $field.' NOT IN ('.implode(',',$c).') AND ';
					continue;
				}
				if( $k == '$lt' ){
					if( !is_integer($c) ){$c = '"'.$c.'"';}
					$query .= $field.' < '.$c.' AND ';
					continue;
				}
				if( $k == '$gt' ){
					if( !is_integer($c) ){$c = '"'.$c.'"';}
					$query .= $field.' > '.$c.' AND ';
					continue;
				}
				if( $k == '$ne' ){
					if( !is_integer($c) ){$c = '"'.$c.'"';}
					$query .= $field.' != '.$c.' AND ';
					continue;
				}

				if( is_array($c) ){$query .= $this->_clause($c,$k).' AND ';continue;}
				if( is_string($c) ){$c = '"'.str_replace('"','\\"',$c).'"';}

				if( $k == '$regex' ){
					if( isset($clause['$options']) && $clause['$options'] == 'i' ){$field = 'LOWER('.$field.')';$c = strtolower($c);}
					$query .= $field.' REGEXP BINARY '.$c.' AND ';continue;
				}
				if( $k == '$options' ){continue;}
				if( $k == '$like' ){$query .= $field.' LIKE '.$c.' AND ';continue;}
				if( $k == '$nlike' ){$query .= $field.' NOT LIKE '.$c.' AND ';continue;}

				$query .= ' '.$k.' = '.$c.' AND ';
			}
			$query = substr($query,0,-4).' )';
			if( $query == ' )' ){$query = false;}
			return $query;
		}
		function _close(&$db = false,$shouldCommit = false){
			//FIXME: no creo que funcione
			if($shouldCommit){$r = sqlite3_exec('COMMIT;',$db);$GLOBALS['DB_LAST_QUERY_ERRNO'] = $db->lastErrorCode();$GLOBALS['DB_LAST_QUERY_ERROR'] = $db->lastErrorMsg();}
			$sqliteOB = sqlite3_get($db);
			/* Si la base de datos estaba abierta, liberamos el lock, pero solo si este proceso es quien ha registrado este lock ($checkpid = true) */
			if($sqliteOB && $sqliteOB['fileMode'] == 6){$r = sqlite3_lock_release($db,true);}
			$db->close();
			$db = false;
			return $shouldCommit ? $r : true;
		}
		function _count($clause = false,$params = []){
			if( !isset($params['indexBy']) ){$params['indexBy'] = false;}
			$params['fields'] = ['count(*) as count'];

			$r = $this->getWhere($clause,$params);
			if( isset($r['errorDescription']) ){return $r;}
			$count = reset($r);
			return $count['count'];
		}
		function _getByID($id = false,$params = []){
			/* Get element by the table id */
			$key = $this->indexBy ? $this->indexBy : '_id';
			$clause = [$key=>$id];

			return $this->getSingle($clause,$params);
		}
		function _getByIDs($ids = [],$params = []){
			/* Get elements by multiple table ids */
			$ids = array_filter($ids);
			$ids = array_unique($ids);
			$ids = array_values($ids);
			if( !$ids ){return [];}
			$key = $this->indexBy ? $this->indexBy : '_id';
			$clause = [$key=>['$in'=>$ids]];

			return $this->getWhere($clause,$params);
		}
		function _getSingle($clause = false,$params = []){
			$params['limit']   = 1;
			$params['indexBy'] = false;
			$r = $this->getWhere($clause,$params);
			return $r ? reset($r) : $r;
		}
		function _getWhere($clause = false,$params = []){
			/* Return multiple elements matching the clause */
			if( !($r = $this->instance()) ){return false;}
			if( is_array($clause) ){
				$clause = $this->_clause($clause);
			}

			$fields = '*';
			if( !empty($params['fields']) ){
				if( is_array($params['fields']) ){$params['fields'] = implode(',',$params['fields']);}
				$fields = $params['fields'];
			}
			$this->db_last_query = 'SELECT '.$fields.' FROM ['.$this->table.'] '.( $clause ? 'WHERE '.$clause : '');
			if( !empty($params['group']) ){$this->db_last_query .= ' GROUP BY '.$this->client->escapeString($params['group']);}
			if( !empty($params['order']) ){$this->db_last_query .= ' ORDER BY '.$this->client->escapeString($params['order']);}
			if( !empty($params['limit']) ){$this->db_last_query .= ' LIMIT '.$this->client->escapeString($params['limit']);}

			if( $this->useCache && ($rows = $this->_cache_get($this->db_last_query)) !== false ){
				/* If cache is available, return cached data */
				//if( isset($params['db.encrypt']) ){sqlite3_rowsDecrypt($rows,$params);}
				return $rows;
			}

			$r = $this->client->query($this->db_last_query);
			$rows = [];

			if( !isset($params['indexBy']) && isset($this->indexBy) ){$params['indexBy'] = $this->indexBy;}
			if( $r && $params['indexBy'] !== false ){
				while( $row = $r->fetchArray(SQLITE3_ASSOC) ){$rows[$row[$params['indexBy']]] = $row;}
			}
			if( $r && $params['indexBy'] === false ){
				while( $row = $r->fetchArray(SQLITE3_ASSOC) ){$rows[] = $row;}
			}

			/* If cache is enabled save this to cache */
			if( $this->useCache ){$this->_cache_set($this->db_last_query,$rows);}

			//if( isset($params['db.encrypt']) ){sqlite3_rowsDecrypt($rows,$params);}

			return $rows;
		}
		function _removeWhere($clause = false,$params = []){
			if( !($r = $this->instance('w')) ){return false;}
			if( is_array($clause) ){
				$clause = $this->_clause($clause);
			}
			if( !$clause ){
				echo 'No te olvides de poner el where en el delete from!!'.PHP_EOL;exit;
			}

			$this->db_last_query = 'DELETE FROM `'.$this->table.'` WHERE '.$clause.';';

			$r = $this->client->exec($this->db_last_query);
			return $r;
		}
		function _removeByID($id = false,$params = []){
			$key = $this->indexBy ? $this->indexBy : 'id';
			$clause = [$key=>$id];
			return $this->removeWhere($clause,$params);
		}
		function _save(&$data = [],$params = []){
			if( !($r = $this->instance('w')) ){return false;}

			/* INI-Remove invalid params */
			if( isset($GLOBALS['api']['sqlite3']['tables'][$this->table]) ){
				foreach( $data as $k=>$v ){
					if( !isset($GLOBALS['api']['sqlite3']['tables'][$this->table][$k]) ){unset($data[$k]);}
				}
			}
			/* END-Remove invalid params */

			if( !isset($params['indexBy']) && isset($this->indexBy) ){$params['indexBy'] = $this->indexBy;}
			$oldData = [];
			if( empty($params['update.disabled'])
			 && isset($data[$params['indexBy']])
			 && !($oldData = $this->_getByID($data[$params['indexBy']],$params)) ){
				$oldData = [];
			}

			$data = $data + $oldData;

			/* INI-validations */
			$data = $this->validate($data,$oldData);
			if( !is_array($data) ){return ['errorDescription'=>'DATA_NOT_ARRAY','file'=>__FILE__,'line'=>__LINE__];}
			if(  isset($data['errorDescription']) ){return $data;}
			/* END-validations */

			if( !empty($this->jsonFields) ){$this->jsonEnc->__invoke($data);}

			$this->db_last_query = 'INSERT OR REPLACE INTO `'.$this->table.'` ';
			$tableFields = $tableValues = '(';
			$tableUpdate = '';
			foreach( $data as $key=>$value ){
				$tableFields .= '`'.$key.'`,';
				$tableValues .= '\''.$this->client->escapeString($value).'\',';
				$tableUpdate .= $key.' = \''.$this->client->escapeString($value).'\',';
			}
			$tableFields = substr($tableFields,0,-1).')';
			$tableValues = substr($tableValues,0,-1).')';
			$tableUpdate = substr($tableUpdate,0,-1);
			$this->db_last_query .= $tableFields.' VALUES '.$tableValues;
			if( !empty($params['show.query']) ){return $this->db_last_query;}

			$r = @$this->client->exec($this->db_last_query);
			if( $r === false && $this->client->lastErrorCode() == 1 ){
				if( strpos($this->client->lastErrorMsg(),'has no column named')
				 || strpos($this->client->lastErrorMsg(),'may not be NULL')
				 || strpos($this->client->lastErrorMsg(),'NULL constraint failed') ){
					return ($data = $this->_r(__FILE__,__LINE__));
				}
				if( empty($GLOBALS['api']['sqlite3']['tables'][$this->table])
				 && empty($GLOBALS['api']['sqlite3']['tables'][$this->otable]) ){
					return ($data = $this->_r(__FILE__,__LINE__));
				}
				$r = $this->_createTable();
				if( isset($r['errorDescription']) ){return $r;}
				$r = @$this->client->exec($this->db_last_query);
				if( $r === false ){return ($data = $this->_r(__FILE__,__LINE__));}
			}
			if( $r === false ){
				return ($data = $this->_r(__FILE__,__LINE__));
			}

			if( !empty($this->jsonFields) ){$this->jsonDec->__invoke($data);}


			if( ($id = $this->client->lastInsertRowID()) ){
				$data[$params['indexBy']] = $id;
			}

			/* Remove inconsistent cache if necesary */
			if( $this->useCache ){$this->_cache_destroy();}
			$this->log($data,$oldData);

			return true;
		}
		function _createTable(){
			$definition = $GLOBALS['api']['sqlite3']['tables'][$this->table] ?? false;
			if( !$definition && !empty($this->otable) ){$definition = $GLOBALS['api']['sqlite3']['tables'][$this->otable] ?? false;}
			if( !$definition ){return false;}

			$this->db_last_query = 'CREATE TABLE ['.$this->table.'] (';
			$tableKeys = [];
			$hasAutoIncrement = false;
			$tableKeys = [];
			foreach( $definition as $key=>$value ){
				$value = $this->client->escapeString($value);
				if( $key == '_id' ){
					if( strpos($value,'INTEGER AUTOINCREMENT') !== false ){
						$this->db_last_query .= '\''.$key.'\' INTEGER PRIMARY KEY AUTOINCREMENT,';
						continue;
					}
					$this->db_last_query .= '\''.$key.'\' '.$value.',';
					$tableKeys[] = $key;
					continue;
				}
				$this->db_last_query .= '\''.$key.'\' '.$value.',';
			}
			if( count($tableKeys) > 0 ){$this->db_last_query .= 'PRIMARY KEY ('.implode(',',$tableKeys).'),';}
			$this->db_last_query = substr($this->db_last_query,0,-1).');';

			$q = $this->client->exec($this->db_last_query);
			if( $q === false ){return $this->_r(__FILE__,__LINE__);}

			$this->_cache_destroy();
			//FIXME: dejamos los indices para luego
			//if(isset($GLOBALS['indexes'][$tableName])){$r = sqlite3_createIndex($tableName,$GLOBALS['indexes'][$tableName],$params['db']);if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($params['db']);}return $r;}}
			//if(($tableName != $aTableName) && isset($GLOBALS['indexes'][$aTableName])){$r = sqlite3_createIndex($tableName,$GLOBALS['indexes'][$aTableName],$params['db']);if(isset($r['errorDescription'])){if($shouldClose){sqlite3_close($params['db']);}return $r;}}
				
			return true;
		}
		function _cache_set($query = '',$data = ''){
			if( !file_exists($this->path_cache) ){$oldmask = umask(0);$r = mkdir($this->path_cache,0777,1);umask($oldmask);}
			$file_cache = $this->path_cache.md5($query);
			$r = file_put_contents($file_cache,json_encode($data));
			return true;
		}
		function _cache_get($query = ''){
			$file_cache = $this->path_cache.md5($query);
			if( !file_exists($file_cache) ){return false;}
			return json_decode(file_get_contents($file_cache),true);
		}
		function _cache_destroy($query = ''){
			$file_cache = $this->path_cache;
			if( $query ){$file_cache .= md5($query);}
			if( !file_exists($file_cache) ){return false;}
			$this->_rm($file_cache);
			return true;
		}
		function _rm($path = ''){
			/* Helper for remove a path recursively */
			if( !is_dir($path) ){unlink($path);}
			if( $handle = opendir($path) ){
				while( false !== ($file = readdir($handle)) ){
					if( in_array($file,['.','..']) ){continue;}
					if( is_dir($path.$file) ){
						$this->_rm($path.$file.'/');
						continue;
					}
					unlink($path.$file);
				}
				closedir($handle);
			}
			rmdir($path);
		}
		function _r($file = __FILE__,$line = __LINE__){
			return [
				 'query'=>$this->db_last_query
				,'errorCode'=>$this->client->lastErrorCode()
				,'errorDescription'=>$this->client->lastErrorMsg()
				,'file'=>$file
				,'line'=>$line
			];}
	}

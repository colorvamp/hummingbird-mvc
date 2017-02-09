<?php
	if( !isset($GLOBALS['api']['mysql']) ){$GLOBALS['api']['mysql'] = [];}
	$GLOBALS['api']['mysql'] = array_merge([
		 'db'=>[]
		,'collection'=>[] // ?? no se si aquí será necesario
	],$GLOBALS['api']['mysql']);

	class _mysql{
		public $table   = '';
		public $server  = '';
		public $db      = '';
		public $user    = '';
		public $pass    = '';
		public $client  = false;
		public $indexBy = false;
		public $db_last_query = '';
		public $jsonFields = [];
		public $search_fields = [];
		function __construct($server = false,$user = false,$pass = false){
			if( $server ){$this->server = $server;}
			if( $user   ){$this->user = $user;}
			if( $pass   ){$this->pass = $pass;}

			if( $this->jsonFields ){
				$string  = '$this->jsonDec = function(&$row){';
				foreach( $this->jsonFields as $field ){$string .= 'if( isset($row[\''.$field.'\']) && is_string($row[\''.$field.'\']) ){$row[\''.$field.'\'] = json_decode($row[\''.$field.'\'],1);}';}
				$string .= 'return $row;};';
				eval($string);
				$string  = '$this->jsonEnc = function(&$row){';
				foreach( $this->jsonFields as $field ){$string .= 'if( isset($row[\''.$field.'\']) && is_array($row[\''.$field.'\']) ){$row[\''.$field.'\'] = json_encode($row[\''.$field.'\']);}';}
				$string .= '};';
				eval($string);
			}
		}
		function instance(){
			if( $this->client ){return true;}
			if( isset($GLOBALS['api']['mysql']['db'][$this->server][$this->db]) ){
				$this->client = &$GLOBALS['api']['mysql']['db'][$this->server][$this->db];
				return true;
			}

			$GLOBALS['api']['mysql']['db'][$this->server][$this->db] = new mysqli( $this->server,$this->user,$this->pass,$this->db );
			$this->client = &$GLOBALS['api']['mysql']['db'][$this->server][$this->db];
			if( !method_exists($this->client,'query') ){return false;}
			return true;
		}
		function instanceWrite(){
			if( isset($this->clientWrite) && $this->clientWrite ){return true;}
			$this->serverWrite = '10.13.120.217';
			if( isset($GLOBALS['api']['mysql']['db'][$this->serverWrite][$this->db]) ){
				$this->clientWrite = &$GLOBALS['api']['mysql']['db'][$this->serverWrite][$this->db];
				return true;
			}

			$GLOBALS['api']['mysql']['db'][$this->serverWrite][$this->db] = new mysqli( $this->serverWrite,$this->user,$this->pass,$this->db );
			$this->clientWrite = &$GLOBALS['api']['mysql']['db'][$this->serverWrite][$this->db];
			if( !method_exists($this->clientWrite,'query') ){return false;}
			/* Marcos: Esto es el alpha y el omega de la vulgaridad */
			$this->client = $this->clientWrite;
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

		function _count($clause = false,$params = []){
			if( !isset($params['indexBy']) ){$params['indexBy'] = false;}
			$params['fields'] = ['count(*) as count'];

			$r = $this->getWhere($clause,$params);
			if( isset($r['errorDescription']) ){return $r;}
			$count = reset($r);
			return $count['count'];
		}
		function _distinct($field = '',$clause = false,$params = []){
			if( !($r = $this->instance()) ){return false;}
			if( !isset($params['indexBy']) ){$params['indexBy'] = false;}
			if( is_array($clause) ){$clause = $this->_clause($clause);}

			$this->db_last_query = 'SELECT DISTINCT '.$field.' FROM `'.$this->table.'` '.(($clause !== false) ? 'WHERE '.$clause : '').';';

			$r = $this->client->query($this->db_last_query);
			return $this->results($r,$params);
		}
		function _sum($field = '',$clause = false,$params = []){
			if( !isset($params['indexBy']) ){$params['indexBy'] = false;}
			$params['fields'] = ['sum('.$field.') as sum'];

			$r = $this->getWhere($clause,$params);
			if( isset($r['errorDescription']) ){return $r;}
print_r($r);
exit;
			$count = current($r);
			return $count['count'];
		}
		function _getByID($id = false,$params = []){
			if( !($r = $this->instance()) ){return false;}

			$key = $this->indexBy ? $this->indexBy : 'id';
			$clause = [$key=>$id];

			return $this->getSingle($clause,$params);
		}
		function _getByIDs($ids = [],$params = []){
			$ids = array_filter($ids);
			$ids = array_unique($ids);
			$ids = array_values($ids);
			if( !$ids ){return [];}
			$key = $this->indexBy ? $this->indexBy : 'id';
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
			if( !($r = $this->instance()) ){return false;}

			if( is_array($clause) ){
				$clause = $this->_clause($clause);
				if( isset($GLOBASL['debug']) ){echo $clause;exit;}
			}

			$fields = '*';if( isset($params['fields']) ){
				if( is_array($params['fields']) ){$params['fields'] = implode(',',$params['fields']);}
				$fields = $params['fields'];
			}
			$this->db_last_query = 'SELECT '.$fields.' FROM `'.$this->table.'` '.(($clause !== false) ? 'WHERE '.$clause : '');
			if( isset($params['group']) ){$this->db_last_query .= ' GROUP BY '.$this->client->real_escape_string($params['group']);}
			if( isset($params['order']) ){$this->db_last_query .= ' ORDER BY '.$this->client->real_escape_string($params['order']);}
			if( isset($params['limit']) ){$this->db_last_query .= ' LIMIT '.$this->client->real_escape_string($params['limit']);}
			$this->db_last_query .= ';';
			if( isset($params['debug']) && $params['debug'] ){echo $this->db_last_query.PHP_EOL;exit;}

			return $this->query($this->db_last_query,$params);
		}
		function _removeWhere($clause = false,$params = []){
			if( is_array($clause) ){
				$clause = $this->_clause($clause);
				if( isset($GLOBASL['debug']) ){echo $clause;exit;}
			}
			if( !$clause ){
				echo 'No te olvides de poner el where en el delete from!!'.PHP_EOL;exit;
			}

			$this->db_last_query = 'DELETE FROM `'.$this->table.'` WHERE '.$clause.';';

			$r = $this->exec($this->db_last_query);
			return $r;
		}
		function _removeByID($id = false,$params = []){
			$key = $this->indexBy ? $this->indexBy : 'id';
			$clause = [$key=>$id];
			return $this->removeWhere($clause,$params);
		}
		function _updateWhere($clause = false,$data = [],$params = []){
			if( is_array($clause) ){$clause = $this->_clause($clause);}
			if( !$clause ){echo 'No te olvides de poner el where en el delete from!!'.PHP_EOL;exit;}
			if( $this->jsonFields ){$this->jsonEnc->__invoke($data);}

			$tableUpdate = '';
			foreach( $data as $key=>$value ){
				$tableUpdate .= $key.' = "'.str_replace('"','\\"',$value).'",';
			}
			$tableUpdate = substr($tableUpdate,0,-1);

//FIXME: falta limit
			$this->db_last_query = 'UPDATE `'.$this->table.'` SET '.$tableUpdate.' WHERE '.$clause.';';
			if( isset($GLOBASL['debug']) || isset($params['debug']) ){echo $this->db_last_query;exit;}
			$r = $this->exec($this->db_last_query);
			return $r;
		}
		function _tables($params = []){
			if( !($r = $this->instance()) ){return false;}
			if( !isset($params['indexBy']) ){$params['indexBy'] = false;}

			$this->db_last_query = 'SHOW TABLES;';
			$r = $this->client->query($this->db_last_query);
			return $this->results($r,$params);
		}
		function _results($q = false,$params = []){
			if( !$q || !method_exists($q,'fetch_assoc') ){
				echo $this->db_last_query;
				echo 'error on '.__FILE__.' line '.__LINE__;
				exit;
			}
			if( !isset($params['indexBy']) && isset($this->indexBy) ){$params['indexBy'] = $this->indexBy;}
			if( !isset($params['indexBy']) ){$params['indexBy'] = 'id';}

			$rows = [];
			if( $q && $row = $q->fetch_assoc() ){do{
				if( !isset($row[$params['indexBy']]) ){$params['indexBy'] = false;}
				if( $params['indexBy'] !== false ){$rows[$row[$params['indexBy']]] = $row;break;}
				$rows[] = $row;
			}while(false);}
			if( $q && $params['indexBy'] !== false ){while( $row = $q->fetch_assoc() ){$rows[$row[$params['indexBy']]] = $row;}}
			if( $q && $params['indexBy'] === false ){while( $row = $q->fetch_assoc() ){$rows[] = $row;}}

			if( $this->jsonFields ){
				/* http://stackoverflow.com/a/29076170/662752 */
				$rows = array_map($this->jsonDec,$rows);

				/* Probado con muchos $rows, array_map con la función estática gana por 
				 * goleada, mantengo la otra estrategia por si alguien la quiere medir
				 * en algún momento */
				/*foreach( $rows2 as &$row ){
					foreach( $this->jsonFields as $field ){
						if( isset($row[$field]) ){$row[$field] = json_decode($row[$field],1);}
					}
				}*/
			}
			return $rows;
		}
		function _exec($query = '',$params = []){
			if( !($r = $this->instanceWrite()) ){return false;}
			$this->db_last_query = $query;
			$r = $this->clientWrite->query($this->db_last_query);
			return $r;
		}
		function _query($query = '',$params = []){
			if( !($r = $this->instance()) ){return false;}
			if( !isset($params['indexBy']) && isset($this->indexBy) ){$params['indexBy'] = $this->indexBy;}
			if( !isset($params['indexBy']) ){$params['indexBy'] = false;}

			if( is_array($query) ){$query = $this->_clause($query);}

			$this->db_last_query = $query;
			$q = $this->client->query($this->db_last_query);
			return $this->results($q,$params);
		}
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
		function _iterator($clause = false,$callback = false,$params = []){
			if( !($r = $this->instance()) ){return false;}
			if( !$callback || !is_callable($callback) ){return ['errorDescription'=>'NO_CALLBACK','file'=>__FILE__,'line'=>__LINE__];}
			if( !isset($params['iterator.type']) ){$params['iterator.type'] = 'where';}

			//$skip = 0;if(isset($params['skip'])){$skip = $params['skip'];}
			//$chunk = 500;if(isset($params['chunk'])){$chunk = $params['chunk'];}
			$bar   = function_exists('cli_pbar') && isset($params['bar']) ? 'cli_pbar' : false;
			$total = $this->_count($clause);
			$c     = 0;
			if( $bar ){cli_pbar($c,$total,$size=30);}

			if( $params['iterator.type'] == 'where' ){
				$order = $this->indexBy ? $this->indexBy.' ASC' : 'id ASC';if( isset($params['order']) ){$order = $params['order'];}
				$chunk = isset($params['chunk']) ? $params['chunk'] : 5000;
				$limit = 0;
				while( $objectOBs = $this->_getWhere($clause,['limit'=>$limit.','.$chunk,'order'=>$order,'indexBy'=>false]) ){
					$limit += $chunk;
					foreach( $objectOBs as $row ){
						$c++;if( $bar ){cli_pbar($c,$total,$size=30);}
						$r = $callback($row,$this->client);
						if( $r === 'break' ){break;}
					}
				}
			}else{
				$fields = '*';if( isset($params['fields']) ){
					if( is_array($params['fields']) ){$params['fields'] = implode(',',$params['fields']);}
					$fields = $params['fields'];
				}
				$this->db_last_query = 'SELECT '.$selectString.' FROM '.$this->table.' '.(($clause !== false) ? 'WHERE '.$clause : '');
				if( isset($params['group']) ){$this->db_last_query .= ' GROUP BY '.$this->client->real_escape_string($params['group']);}
				if( isset($params['order']) ){$this->db_last_query .= ' ORDER BY '.$this->client->real_escape_string($params['order']);}
				if( isset($params['limit']) ){$this->db_last_query .= ' LIMIT '.$this->client->real_escape_string($params['limit']);}
				$this->db_last_query .= ';';

				$q = $this->client->query($this->db_last_query);
				while( ($row = $q->fetch_assoc()) ){
					$c++;if( $bar ){$bar($c,$total,$size=30);}
					$r = $callback($row,$this->client);
					if( $r === 'break' ){break;}
				}
			}

			return true;
		}
		function _save(&$data = [],$params = []){
			if( !($r = $this->instanceWrite()) ){return false;}

			/* INI-Remove invalid params */
			if( isset($GLOBALS['api']['mysql']['tables'][$this->table]) ){
				foreach($data as $k=>$v){if( !isset($GLOBALS['api']['mysql']['tables'][$this->table][$k]) ){unset($data[$k]);}}
			}
			/* END-Remove invalid params */

			if( !isset($params['indexBy']) && isset($this->indexBy) ){$params['indexBy'] = $this->indexBy;}
//FIXME: 
			$oldData = [];
			if( isset($data[$params['indexBy']]) && !($oldData = $this->_getByID($data[$params['indexBy']],$params)) ){
				$oldData = [];
			}

			//$data = array_replace_recursive($oldData,$data);
			$data = $data+$oldData;

			/* INI-validations */
			$data = $this->validate($data,$oldData);
			if( !is_array($data) ){return ['errorDescription'=>'DATA_NOT_ARRAY','file'=>__FILE__,'line'=>__LINE__];}
			if(  isset($data['errorDescription']) ){return $data;}
			/* END-validations */

			if( $this->jsonFields ){$this->jsonEnc->__invoke($data);}

			$this->db_last_query = 'INSERT INTO `'.$this->table.'` ';
			$tableFields = $tableValues = '(';
			$tableUpdate = '';
			foreach( $data as $key=>$value ){
				$tableFields .= '`'.$key.'`,';
				$tableValues .= '\''.$this->clientWrite->real_escape_string($value).'\',';
				$tableUpdate .= $key.' = \''.$this->clientWrite->real_escape_string($value).'\',';
			}
			$tableFields = substr($tableFields,0,-1).')';
			$tableValues = substr($tableValues,0,-1).')';
			$tableUpdate = substr($tableUpdate,0,-1);
			$this->db_last_query .= $tableFields.' VALUES '.$tableValues;
			$this->db_last_query .= ' ON DUPLICATE KEY UPDATE '.$tableUpdate.';';
			if( isset($params['show.query']) && $params['show.query'] ){return $this->db_last_query;}

			$q = $this->clientWrite->query($this->db_last_query);
			if( $this->jsonFields ){$this->jsonDec->__invoke($data);}


			if( ($id = $this->clientWrite->insert_id) ){
				$data[$params['indexBy']] = $id;
			}
			$this->log($data,$oldData);
			//echo $this->clientWrite->error;
			//var_dump($q);
			//TODO

			return true;
		}
		function _search($criteria = '',$params = []){
			if( !($r = $this->instance()) ){return false;}

			if( !$this->search_fields ){return [];}
			$limitRows = 500;if(isset($params['row.limit'])){$limitRows = $params['row.limit'];}
			$match = false;if(isset($params['match'])){$match = $params['match'];}

			$words = explode(' ',$criteria);
			$countWords = count($words);
			$modeMultipleWords = ($countWords > 1);
			$criteriaLength = strlen($criteria);

			$cnd = [];
			foreach($this->search_fields as $field){
				foreach($words as $word){
					$cnd[] = [$field=>['$regex'=>$word,'$options'=>'i']];
				}
			}
			$clause = ['$or'=>$cnd];
			if( $match ){$clause = ['$and'=>[$match,['$or'=>$cnd]]];}

			$result = [];
			$i = 0;
			$this->iterator($clause,function($row) use (&$result,&$i,$criteria,$limitRows,$words,$countWords,$modeMultipleWords,$criteriaLength){
				$i++;
				$score = 0;
				foreach($this->search_fields as $k=>$field){
					if(!isset($row[$field])){continue;}
					if($modeMultipleWords && stripos($row[$field],$criteria) !== false){$score += (2*$criteriaLength)+$countWords;continue;}
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
			});

			return array_values($result);
		}
		function _innerSave($array,$params = []){
			if( !($r = $this->instance()) ){return false;}

			/* INI-INSERT INTO */
			$this->db_last_query = 'INSERT INTO `'.$this->table.'` ';
			$tableIDs = $tableValues = '(';
			$update   = '';
			/* SQL uses single quotes to delimit string literals. */
			foreach( $array as $key=>&$value ){
				$tableIDs    .= '`'.$key.'`,';
				$tableValues .= '\''.$value.'\',';
				$update      .= $key.' = \''.$value.'\',';
			}
			$tableIDs = substr($tableIDs,0,-1).')';$tableValues = substr($tableValues,0,-1).')';
			$this->db_last_query .= $tableIDs.' VALUES '.$tableValues;
			$this->db_last_query .= ' on duplicate key update '.substr($update,0,-1);
			$this->db_last_query .= ';';
			/* END-INSERT INTO */


			$q = $this->client->query($this->db_last_query);
			if( $q === false ){
				//return false;
				echo $this->db_last_query.PHP_EOL.PHP_EOL;
				echo $this->client->error.PHP_EOL.PHP_EOL;
				exit;
			}
			return true;
echo $query;
exit;

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
				/* Hay errores que pueden ser significativos, como una contraseÃ±a que no puede ser null, pero saltarÃ¡n incluso si quiero actualizar */
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
			/* Da lo mismo que no se estÃ© usando cachÃ© explÃ­citamente, si se actualiza esta tabla debemos
			 * eliminar cualquier rastro de cachÃ© para evitar datos invÃ¡lido al hacer consultas que podrian estar cacheadas */
			if($shouldClose && !($r = sqlite3_close($params['db'],true))){return array('errorCode'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'errorDescription'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'file'=>__FILE__,'line'=>__LINE__);}
			return array('id'=>$GLOBALS['DB_LAST_QUERY_ID'],'error'=>$GLOBALS['DB_LAST_QUERY_ERROR'],'errno'=>$GLOBALS['DB_LAST_QUERY_ERRNO'],'query'=>$query);
		}
	}


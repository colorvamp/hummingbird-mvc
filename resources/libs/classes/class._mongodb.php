<?php
	/**
	 * Mongodb database API
	 *
	 * @author	Franciso Licerán <pakitometal@gmail.com>
	 * @author	Marcos Fernández <sombra2eternity@gmail.com>
	 */

	if( !isset($GLOBALS['api']['mongo']) ){$GLOBALS['api']['mongo'] = [];}
	$GLOBALS['api']['mongo'] = array_merge([
		 'db'=>[]
		,'collection'=>[]
	],$GLOBALS['api']['mongo']);

	/* INI - Sobrecarga de clases de driver Mongo obsoleto */
	if ( !class_exists('MongoId') ) {
		class MongoId {
			//private $_object_id;
			function __construct($id = NULL) {
				if( $id && !is_string($id) ){
	//var_dump($id);
	//echo 11;exit;
	//$id = strval($id);
				}
				$tmp = new MongoDB\BSON\ObjectID($id);
				$this->{'$id'} = strval($tmp);
			}
			function __call($method, $args) { return $this->_object_id->$method($args); }
			function __toString() { return $this->{'$id'};}
		}
	}

	if ( !class_exists('MongoException') ) { class MongoException extends Exception {}; }
	/* END - Sobrecarga de clases de driver Mongo obsoleto */

	class _mongodb extends _mongo{}

	class _mongo{
		public $db     = 'hummingbird';
		public $server = 'db';
		public $table  = '';
		public $otable = '';
		public $client = false;
		public $collection = false;
		public $search_fields = [];
		public $pool = 'mongo';
		public $timeout = 800000;
		public $typemap = [ 'root' => 'array', 'document' => 'array', 'array' => 'array' ];
		public $timestamp_diff = false;

		function __construct($table = false,$otable = false){
			if( $table ){$this->table = $table;}
			if( $otable ){$this->otable = $otable;}
			if( $this->otable && !isset($GLOBALS['api'][$this->pool]['tables'][$this->table]) ){
//FIXME: en vez de hacer asi, dar soporte completo a collection_get y _save
				$GLOBALS['api'][$this->pool]['tables'][$this->table]  = $GLOBALS['api'][$this->pool]['tables'][$this->otable];
				$GLOBALS['api'][$this->pool]['indexes'][$this->table] = $GLOBALS['api'][$this->pool]['indexes'][$this->otable];
			}
		}
		function client_get(){
			if( $this->client ){return true;}
			if( isset($GLOBALS['api'][$this->pool]['db'][$this->server]) ){
				$this->client = &$GLOBALS['api'][$this->pool]['db'][$this->server];
				return true;
			}

			try{
				$manager = new MongoDB\Driver\Manager( ($this->server != 'db' ? $this->server : null) );
				$manager->executeCommand($this->db, new MongoDB\Driver\Command(['ping' => 1]));
				$this->client = $GLOBALS['api'][$this->pool]['db'][$this->server] = $manager->selectServer(new MongoDB\Driver\ReadPreference(MongoDB\Driver\ReadPreference::RP_PRIMARY));
				if ( !$this->client ) { return ['errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__]; }
				return true;
			}catch(MongoDB\Driver\Exception\Exception $e){
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
		}
		function collection_get(){
			$r = $this->client_get();
			if( $this->collection ){return true;}
			if( isset($GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table]) ){
				$this->collection = &$GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table];
				return true;
			}

			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			$GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table] = $this->table;
			$this->collection = &$GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table];

			if( isset($GLOBALS['api']['mongo']['indexes'][$this->table]) ){
				foreach($GLOBALS['api']['mongo']['indexes'][$this->table] as $index){
					$command = [
						 'createIndexes' => $this->table
						,'indexes'  => [[
							  'key'  => $index['fields']
							 ,'name' => $this->_generate_index_name($index['fields'])
						]]
					];
					if( isset($index['props']) && is_array($index['props']) ) { $command['indexes'][0] += $index['props']; }
					try{
						$c = new MongoDB\Driver\Command($command);
						$this->client->executeCommand($this->db, $c);
					}catch(MongoDB\Driver\Exception\Exception $e){
						$errorCode        = $e->getCode();
						$errorDescription = $e->getMessage();
						if( preg_match('/Index with name: (?<indexName>[^_]+)_1 already exists with different options/',$errorDescription,$m) ){
							/* Ante este tipo de error, volvemos a generar los índices */
							$c = new MongoDB\Driver\Command([ 'dropIndexes' => $this->table, 'index' => '*' ]);
							try{
								$this->client->executeCommand($this->db, $c);
								return $this->collection_get();
							}catch(MongoDB\Driver\Exception\Exception $e){
								return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
							}
						}
						return ['errorCode'=>$errorCode,'errorDescription'=>$errorDescription,'file'=>__FILE__,'line'=>__LINE__];
					}
				}
			}
			return true;
		}
		function count($clause = [],$params = []){return $this->_count($clause,$params);}
		function distinct($field = '',$clause = [],$params = []){return $this->_distinct($field,$clause,$params);}
		function getByID($id = false,$params = []){return $this->_getByID($id,$params);}
		function getByIDs($ids = [],$params = []){return $this->_getByIDs($ids,$params);}
		function getSingle($clause = [],$params = []){return $this->_getSingle($clause,$params);}
		function getWhere($clause = [],$params = []){return $this->_getWhere($clause,$params);}
		function removeWhere($clause = [],$params = []){return $this->_removeWhere($clause,$params);}
		function removeByID($id = false,$params = []){return $this->_removeByID($id,$params);}
		function updateWhere($clause = false,$data = [],$params = []){return $this->_updateWhere($clause,$data,$params);}
		function aggregate($plan = [],$params = []){return $this->_aggregate($plan,$params);}
		function findAndModify($query = [],$update = [],$fields = [],$options = []){return $this->_findAndModify($query,$update,$fields,$options);}
		function validate(&$data = [],&$oldData = []){return $data;}
		function save(&$data = [],$params = []){return $this->_save($data,$params);}
		function iterator($clause = [],$callback = false,$params = []){return $this->_iterator($clause,$callback,$params);}
		function search($criteria = '',$params = []){return $this->_search($criteria,$params);}
		function log(&$data = [],&$oldData = []){}
		function _clause(&$clause = []){
			array_walk_recursive($clause,function(&$value,$key){
				if( is_object($value) && get_class($value) == 'MongoId' ){
					$value = new MongoDB\BSON\ObjectID($value);
				}
			});
		}
		function _row(&$row = []){
			if( !is_array($row) ){return;}
			array_walk_recursive($row,function(&$value,$key){
				if( is_object($value) && get_class($value) == 'MongoDB\BSON\ObjectID' ){
					$value = new MongoId($value);
				}
			});
		}
		function _count($clause = [], $params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ) { return $r; }

			$command = [ 'count' => $this->table ];
			if ( $clause ) {
				$this->_clause($clause);
				$command['query'] = $clause;
			}
			foreach (['hint', 'limit', 'maxTimeMS', 'skip'] as $option) {
				if ( isset($params[$option]) ) { $command[$option] = (int)$params[$option]; }
			}

			$c = new MongoDB\Driver\Command($command);
			try {
				$r = $this->client->executeCommand($this->db, $c);
				$r->setTypeMap($this->typemap);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			$r = current($r->toArray());
			return (int)$r['n'];
		}
		function _distinct($field = '',$clause = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ) { return $r; }

			$command = [ 'distinct' => $this->table, 'key' => $field ];
			if ( $clause ) { $command['query'] = $clause; }
			if ( isset($params['maxTimeMS']) ) { $command['maxTimeMS'] = (int)$params['maxTimeMS']; }

			$c = new MongoDB\Driver\Command($command);
			try {
				$r = $this->client->executeCommand( $this->db, $c );
				$r->setTypeMap($this->typemap);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			$r = current($r->toArray());
			return $r['values'];
		}
		function _getByID($id = false,$params = []){
			if( is_object($id) && get_class($id) == 'MongoId' ){$id = strval($id);}
			if( isset($id) && is_string($id) && strlen($id) == 24 && preg_match('!^[a-zA-Z0-9]+$!',$id) ){
				try {
					$id = new MongoDB\BSON\ObjectID($id);
				}catch(MongoDB\Driver\Exception\Exception $e){
					return false;
				}
			}
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return false;}
			try{
				$q = new MongoDB\Driver\Query(['_id'=>$id], ['limit' => 1]);
				$r = $this->client->executeQuery($this->db.'.'.$this->table, $q);
				$r->setTypeMap($this->typemap);
			}catch(MongoDB\Driver\Exception\Exception $e){
				return false;
			}
			$r = current($r->toArray());
			$this->_row($r);
			return $r;
		}
		function _getByIDs($ids = [],$params = []){
			$ids = array_diff($ids,['']);
			$ids = array_unique($ids);
			$ids = array_map(function($id){
				if( is_object($id) && get_class($id) == 'MongoId' ){$id = strval($id);}
				if( is_string($id) && strlen($id) == 24 && preg_match('!^[a-zA-Z0-9]+$!',$id) ){
					try {
						$id = new MongoDB\BSON\ObjectID($id);
					}catch(MongoDB\Driver\Exception\Exception $e){
						return false;
					}
				}
				return $id;
			},$ids);
			$ids = array_values(array_filter($ids));
			$clause = ['_id'=>['$in'=>$ids]];
			return $this->getWhere($clause,$params);
		}
		function _getSingle($clause = [],$params = []){
			$params['limit'] = 1;
			$r = $this->_getWhere($clause, $params);
			if( isset($r['errorDescription'],$r['errorCode']) ){return $r;}
			return $r ? current($r) : $r;
		}
		function _getWhere($clause = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			if( !isset($params['indexBy']) ){$params['indexBy'] = '_id';}
			$options = [
				 'batchSize' => 100
				,'limit' => 2000
			];
			if( isset($params['limit']) && $params['limit'] ){
				$limit = $params['limit'];
				if ( strpos($params['limit'], ',') ) {
					list($skip, $limit) = explode(',',$params['limit']);
					$options['skip'] = (int)$skip;
				}
				$options['limit'] = (int)$limit;
			}
			if ( isset($params['fields']) ) { $options['projection'] = array_fill_keys($params['fields'], 1); }
			foreach (['hint', 'maxTimeMS'] as $option) {
				if ( !empty($params[$option]) ) { $options[$option] = (int)$params[$option]; }
			}
			if( isset($params['order']) && is_string($params['order']) ){
				$sort = [$params['order']=>1];
				if(($p = strpos($params['order'],' '))){
					/* Support for 'ORDER field (ASC|DESC)' */
					$field = substr($params['order'],0,$p);
					$o = substr($params['order'],$p+1);
					$sort = [$field=>($o == 'ASC') ? 1 : -1];
				}
				$options['sort'] = $sort;
			}
			try {
				$this->_clause($clause);
				$q = new MongoDB\Driver\Query($clause, $options);
				$r = $this->client->executeQuery($this->db.'.'.$this->table, $q);
				$r->setTypeMap($this->typemap);
			} catch(MongoDB\Driver\Exception\Exception $e) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			$rows = [];
			if( $r && $params['indexBy'] !== false ){foreach( $r as $row ){
				if( !isset($row[$params['indexBy']]) ){
					$this->_row($row);
					$rows[] = $row;
					continue;
				}
				$k = is_array($row[$params['indexBy']]) ? implode('.',$row[$params['indexBy']]) : strval($row[$params['indexBy']]);
				$this->_row($row);
				$rows[$k] = $row;
			}}
			else{foreach($r as $row){
				$this->_row($row);
				$rows[] = $row;
			}}
			return $rows;
		}
		function _removeWhere($clause = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			$this->_clause($clause);

			$options = [];
			if ( !empty($params['limit']) ) { $options['limit'] = intval((bool)$params['limit']); }

			try {
				$bulk = new MongoDB\Driver\BulkWrite([ 'ordered' => true ]);
				$bulk->delete($clause, $options);
				$r = $this->client->executeBulkWrite($this->db.'.'.$this->table, $bulk);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return false;
			}
			return (int)$r->getDeletedCount();
		}
		function _removeByID($id = false,$params = []){
			if( is_object($id) && get_class($id) == 'MongoId' ){$id = strval($id);}
			if( isset($id) && is_string($id) && strlen($id) == 24 && preg_match('!^[a-zA-Z0-9]+$!',$id) ){
				try {
					$id = new MongoDB\BSON\ObjectID($id);
				}catch(MongoDB\Driver\Exception\Exception $e){
					return false;
				}
			}
			return $this->_removeWhere(['_id'=>$id], ['limit'=>1]);
		}
		function _updateWhere($clause = [],$data = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			if( !isset($data['$set']) || !isset($data['$inc']) ){ $data = ['$set'=>$data]; }

			try {
				$bulk = new MongoDB\Driver\BulkWrite([ 'ordered' => true ]);
				$bulk->update($clause, $data, ['multi'=>true]);
				$r = $this->client->executeBulkWrite($this->db.'.'.$this->table, $bulk);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return false;
			}
			return $r->getModifiedCount();
		}
		function _aggregate($plan = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			$this->_clause($plan);

			$command = [
				 'aggregate'    => $this->table
				,'pipeline'     => $plan
				,'allowDiskUse' => true
				,'explain'      => isset($params['explain'])
				,'maxTimeMS'    => $this->timeout
			];

			try {
				$c = new MongoDB\Driver\Command($command);
				$r = $this->client->executeCommand($this->db, $c);
				$r->setTypeMap($this->typemap);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			$r = current($r->toArray());
			foreach( $r['result'] as &$row ){
				$this->_row($row);
			}
			unset($row);
//FIXME:
			return $r;
		}
		function _findAndModify( $query = [], $update = [], $fields = [], $params = [] ) {
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			$this->_clause($query);

			$command = [
				 'findAndModify' => $this->table
				,'query'         => $query
				,'update'        => $update
				,'new'           => isset($params['new'])    ? (bool)$params['new']    : true
				,'remove'        => isset($params['remove']) ? (bool)$params['remove'] : false
				,'upsert'        => isset($params['upsert']) ? (bool)$params['upsert'] : false
			];
			if ( $command['remove'] ) { unset($command['update']); }
			if ( $fields ) { $command['fields'] = $fields; }

			if ( isset($params['sort']) ) { $command['sort'] = $params['sort']; }
			else if( isset($params['order']) && is_string($params['order']) ){
				$sort = [$params['order']=>1];
				if(($p = strpos($params['order'],' '))){
					/* Support for 'ORDER field (ASC|DESC)' */
					$field = substr($params['order'],0,$p);
					$o = substr($params['order'],$p+1);
					$sort = [$field=>($o == 'ASC') ? 1 : -1];
				}
				$command['sort'] = $sort;
			}

			$c = new MongoDB\Driver\Command($command);
			try {
				$r = $this->client->executeCommand($this->db, $c);
				$r->setTypeMap($this->typemap);
				$rs = current($r->toArray());
				/* Porque puede ser NULL e isset fallaría */
				if( array_key_exists('value',$rs) ){$this->_row($rs['value']);$rs = $rs['value'];}
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			return $rs;
		}
		function _mapReduce($plan = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			$command = [
				 'mapreduce' => $this->table
				,'map'       => $plan['map']
				,'reduce'    => $plan['reduce']
				,'scope'     => $plan['scope']
				,'query'     => $plan['query']
				,'out'       => $plan['out']
			];
			try {
				$c = new MongoDB\Driver\Command($command);
				$r = $this->client->executeCommand($this->db, $c);
				$r->setTypeMap($this->typemap);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			$r = current($r->toArray());
			return $r;
		}
		function _save(&$data = [],$params = []){
			/* INI-Remove invalid params */
			if( isset($GLOBALS['api']['mongo']['tables'][$this->table]) ){
				foreach($data as $k=>$v){if( !isset($GLOBALS['api']['mongo']['tables'][$this->table][$k]) ){unset($data[$k]);}}
			}
			/* END-Remove invalid params */

			$oldData = [];
			if ( !isset($params['update.disabled']) && isset($data['_id']) && !($oldData = $this->_getByID($data['_id'],$params)) ){ $oldData = []; }
			$data = $data+$oldData;

			if( !isset($data['_id']) ){$data['_id'] = new MongoId();}
			if( isset($data['_id']) && is_string($data['_id']) && strlen($data['_id']) == 24 && preg_match('!^[a-zA-Z0-9]+$!',$data['_id']) ){$data['_id'] = new MongoId($data['_id']);}

			/* INI-validations */
			$data = $this->validate($data,$oldData);
			if( isset($data['errorDescription']) ){return $data;}
			/* _clause convierte los MongoId en MongoDB\BSON\ObjectID */
			$this->_clause($data);
			/* END-validations */

			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			try {
				$bulk = new MongoDB\Driver\BulkWrite(['ordered' => true]);
				$bulk->update([ '_id' => $data['_id'] ], $data, [ 'multi' => false, 'upsert' => true ]);
				$r = $this->client->executeBulkWrite($this->db.'.'.$this->table, $bulk);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}

			$this->_row($data);
//FIXME: volver a convertir _id?
			$this->log($data, $oldData);
			return $r->getModifiedCount();
		}
		function _iterator($clause = [],$callback = false,$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			if( !$callback || !is_callable($callback) ){return ['errorDescription'=>'NO_CALLBACK','file'=>__FILE__,'line'=>__LINE__];}

			$bar = function_exists('cli_pbar') && isset($params['bar']) ? 'cli_pbar' : false;
			if( $bar ){$total = $this->count($clause);}

			$c = 0;
			try{
				$this->_clause($clause);
				if( isset($params['iterator.type']) && $params['iterator.type'] == 'where' ){
					$skip  = 0;  if( isset($params['skip']) ){$skip = $params['skip'];}
					$chunk = 2000;if( isset($params['chunk']) ){$chunk = $params['chunk'];}
					while($objectOBs = $this->getWhere($clause,['limit'=>$skip.','.$chunk])){
						$skip += $chunk;
						foreach($objectOBs as $objectOB){
							$c++;
							if($bar){$bar($c,$total,$size=30);}
							$callback($objectOB, $this->client);
						}
					}
				}else{
					$options = [];
					$params['cursor'] = true;
					$params['limit']  = false;
					if ( isset($params['order']) && is_string($params['order']) ){
						$sort = [$params['order']=>1];
						if(($p = strpos($params['order'],' '))){
							/* Support for 'ORDER field (ASC|DESC)' */
							$field = substr($params['order'],0,$p);
							$o = substr($params['order'],$p+1);
							$sort = [$field=>($o == 'ASC') ? 1 : -1];
						}
						$options['sort'] = $sort;
					}
					if ( isset($params['fields']) ) { $options['projection'] = array_fill_keys($params['fields'], 1); }

					try {
						$query = new MongoDB\Driver\Query($clause,$options);
						$r = $this->client->executeQuery($this->db.'.'.$this->table,$query);
						$r->setTypeMap($this->typemap);
					} catch(MongoDB\Driver\Exception\Exception $e) {
						return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
					}
					foreach ($r as $row ){
						$c++;if( $bar ){$bar($c,$total,$size=30);}
						$this->_row($row);
						$result = $callback($row, $this->client);
						if( $result === 'break' ){break;}
					}
				}
			}catch(MongoDB\Driver\Exception\Exception $e){
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			return true;
		}
		function _bulk(&$dataArray = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			$dataArray = array_map([$this,'validate'],$dataArray);

			try {
				$bulk = new MongoDB\Driver\BulkWrite(['ordered' => false]);
				foreach ($dataArray as $data) { $bulk->insert($data); }
				$r = $this->client->executeBulkWrite($this->db.'.'.$this->table, $bulk);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				print_r(['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__]);
			}
			return $r->getInsertedCount();
		}
		function _search($criteria = '',$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			if( !isset($params['indexBy']) ){$params['indexBy'] = '_id';}

			if( !$this->search_fields ){return [];}
			if( !isset($params['fields']) ){$params['fields'] = [];}
			$limitRows = 500;if(isset($params['row.limit'])){$limitRows = $params['row.limit'];}
			$match = false;if(isset($params['match'])){$match = $this->_clause($params['match']);}

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

			try {
				$query = new MongoDB\Driver\Query($clause);
				$r = $this->client->executeQuery($this->db.'.'.$this->table, $query);
				$r->setTypeMap($this->typemap);
			} catch(MongoDB\Driver\Exception\Exception $e) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			$result = [];
			$i = 0;
			foreach ($r as $row) {
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
			}

			$rows = [];
			if( $result && $params['indexBy'] !== false ){foreach( $result as $row ){
				if( !isset($row[$params['indexBy']]) ){$rows[] = $row;continue;}
				$k = is_array($row[$params['indexBy']]) ? implode('.',$row[$params['indexBy']]) : strval($row[$params['indexBy']]);
				$rows[$k] = $row;
			}}
			else{$rows = $result;}
			return $rows;
		}
		function ps(){
			$r = $this->client_get();
			if ( is_array($r) && isset($r['errorDescription']) ) { return $r; }

			try{
				if( $this->server == 'db' ){$command = new \MongoDB\Driver\Command(['eval'=>'db.currentOP();']);}
				else{$command = new MongoDB\Driver\Command(['currentOp'=>true]);}
				$r = $this->client->executeCommand('admin',$command);
				$r->setTypeMap($this->typemap);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return [ 'errorCode' => $e->getCode(), 'errorDescription' => $e->getMessage(), 'file'=>__FILE__, 'line'=>__LINE__ ];
			}

			$r = iterator_to_array($r);
			return isset($r[0]['retval']['inprog']) ? $r[0]['retval']['inprog'] : $r[0]['inprog'];
		}
		function psKill($id = false){
			$r = $this->client_get();
			if ( is_array($r) && isset($r['errorDescription']) ) { return $r; }

			try{
				if( $this->server == 'db' ){$command = new \MongoDB\Driver\Command(['eval'=>'db.killOp('.intval($id).');']);}
				else{$command = new MongoDB\Driver\Command(['killOp'=>true,'op'=>intval($id)]);}
				$r = $this->client->executeCommand('admin',$command);
				$r->setTypeMap($this->typemap);
			} catch ( MongoDB\Driver\Exception\Exception $e ) {
				return [ 'errorCode' => $e->getCode(), 'errorDescription' => $e->getMessage(), 'file'=>__FILE__, 'line'=>__LINE__ ];
			}

			return iterator_to_array($r);
		}
		protected function _generate_index_name($document){
			if ( is_object($document) ) { $document = get_object_vars($document); }
			if ( ! is_array($document) ) { throw InvalidArgumentException::invalidType('$document', $document, 'array or object'); }
			$name = '';
			foreach ($document as $field => $type) { $name .= ($name != '' ? '_' : '') . $field . '_' . $type; }
			return $name;
		}
	}

<?php
	if( !isset($GLOBALS['api']['mongo']) ){$GLOBALS['api']['mongo'] = [];}
	$GLOBALS['api']['mongo'] = array_merge([
		 'db'=>[]
		,'collection'=>[]
	],$GLOBALS['api']['mongo']);

	class _mongo{
		public $db     = 'hummingbird';
		public $server = 'db';
		public $table  = '';
		public $otable = '';
		public $client = false;
		public $collection = false;
		public $search_fields = [];
		public $timeout = 800000;
		function __construct($table = false,$otable = false){
			if( isset($GLOBALS['w.localhost']) && $GLOBALS['w.localhost'] ){
				//$this->server = '10.13.120.186:27017';
			}

			if( $table ){$this->table = $table;}
			if( $otable ){$this->otable = $otable;}
			if( $this->otable && !isset($GLOBALS['api']['mongo']['tables'][$this->table]) ){
//FIXME: en vez de hacer asi, dar soporte completo a collection_get y _save
				$GLOBALS['api']['mongo']['tables'][$this->table]  = $GLOBALS['api']['mongo']['tables'][$this->otable];
				$GLOBALS['api']['mongo']['indexes'][$this->table] = $GLOBALS['api']['mongo']['indexes'][$this->otable];
			}
		}
		function client_get(){
			if( $this->client ){return true;}
			if( isset($GLOBALS['api']['mongo']['db'][$this->server]) ){
				$this->client = &$GLOBALS['api']['mongo']['db'][$this->server];
				return true;
			}

			try{
				$GLOBALS['api']['mongo']['db'][$this->server] = new MongoClient( ($this->server != 'db' ? $this->server : null) , ['socketTimeoutMS'=>$this->timeout,'connectTimeoutMS'=>$this->timeout] );
				$this->client = &$GLOBALS['api']['mongo']['db'][$this->server];
				if( !method_exists($this->client,'selectCollection') ){return ['errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__];}
				return true;
			}catch(MongoException $e){
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
		}
		function collection_get(){
			if( $this->collection ){return true;}
			if( isset($GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table]) ){
				$this->collection = &$GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table];
				return true;
			}

			$r = $this->client_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			try{
				$GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table] = $this->client->selectCollection($this->db,$this->table);
				$this->collection = &$GLOBALS['api']['mongo']['collection'][$this->server][$this->db][$this->table];
			}catch(MongoException $e){
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}

			if( isset($GLOBALS['api']['mongo']['indexes'][$this->table]) ){
				foreach($GLOBALS['api']['mongo']['indexes'][$this->table] as $index){
					$params = [$index['fields']];
					if( isset($index['props']) ){$params[] = $index['props'];}
					try{
						$this->collection->ensureIndex($index['fields'],isset($index['props']) ? $index['props'] : []);
					}catch(MongoException $e){
						$errorCode        = $e->getCode();
						$errorDescription = $e->getMessage();
						if( preg_match('/Index with name: (?<indexName>[^_]+)_1 already exists with different options/',$errorDescription,$m) ){
							/* Ante este tipo de error, volvemos a generar los índices */
							$this->collection->deleteIndexes([]);
							return $this->collection_get();
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
		function removeByIDs($ids = false,$params = []){return $this->_removeByIDs($ids,$params);}
		function updateWhere($clause = false,$data = [],$params = []){return $this->_updateWhere($clause,$data,$params);}
		function aggregate($plan = [],$params = []){return $this->_aggregate($plan,$params);}
		function findAndModify($query = [],$update = [],$fields = [],$options = []){return $this->_findAndModify($query,$update,$fields,$options);}
		function validate(&$data = [],&$oldData = []){return $data;}
		function save(&$data = [],$params = []){return $this->_save($data,$params);}
		function iterator($clause = [],$callback = false,$params = []){return $this->_iterator($clause,$callback,$params);}
		function search($criteria = '',$params = []){return $this->_search($criteria,$params);}
		function log(&$data = [],&$oldData = []){}
		function _count($clause = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			if( isset($params['explain']) && $params['explain'] ){return $this->getWhere($clause,$params);}
			return $this->collection->count($clause,$params);
		}
		function _distinct($field = '',$clause = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			if( !$clause ){$clause = null;}
			return $this->collection->distinct($field,$clause);
		}
		function _getByID($id = false,$params = []){
			if( isset($id) && is_string($id) && strlen($id) == 24 && preg_match('/^[a-z0-9]+$/',$id) ){
				try{$id = new MongoId($id);}
				catch(MongoException $e){return false;}
			}
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return false;}
			try{
				if( !isset($params['fields']) ){$params['fields'] = [];}
				return $this->collection->findOne(['_id'=>$id],$params['fields']);
			}catch(MongoException $e){
				return false;
			}
		}
		function _getByIDs($ids = [],$params = []){
			$ids = array_diff($ids,['']);
			$ids = array_unique($ids);
			$ids = array_map(function($id){
				if( is_string($id) && (preg_match('!^[a-zA-Z0-9]+$!',$id)) && strlen($id) == 24 ){$id = new MongoId($id);}
				return $id;
			},$ids);
			$ids = array_values($ids);
			$clause = ['_id'=>['$in'=>$ids]];
			return $this->getWhere($clause,$params);
		}
		function _getSingle($clause = [],$params = []){
			/* Lo cambiamos a find (getWhere) porque debido a un
			 * bug de mongo, si existe $query en la clausula, los indices
			 * se van a la mierda. Por otro lado, hasta el driver oficial
			 * de nodejs usa find para findOne (https://github.com/mongodb/node-mongodb-native/blob/c41966c1b1834c33390922650e582842dbad2934/lib/collection.js#L833) */
			$params['limit'] = 1;
			$r = $this->_getWhere($clause,$params);
			if( isset($r['errorDescription'],$r['errorCode']) ){return $r;}
			return $r ? current($r) : $r;


			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			$data = ['$query'=>$clause];

			/* INI-Soporte para ordenación */
			if( isset($params['order']) ){do{
				if( is_array($params['order']) ){$data['$orderby'] = $params['order'];break;}
				if( ($p = strpos($params['order'],' ')) ){
					/* Support for 'ORDER field (ASC|DESC)' */
					$field = substr($params['order'],0,$p);
					$o = substr($params['order'],$p+1);
					$data['$orderby'] = [$field=>($o == 'ASC') ? 1 : -1];
					break;
				}
				$data['$orderby'] = [$params['order']=>1];
			}while(false);}
			/* END-Soporte para ordenación */

			$row = $this->collection->findOne($data);
			return $row;
		}
		function _getWhere($clause = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			if( !isset($params['indexBy']) ){$params['indexBy'] = '_id';}
			$skip  = null;
			$limit = 2000;
			if( isset($params['limit']) && $params['limit'] ){
				$limit = $params['limit'];
				if(strpos($params['limit'],',')){list($skip,$limit) = explode(',',$params['limit']);}
			}
			if( isset($params['limit']) && !$params['limit'] ){
				$limit = null;
			}
			if( isset($params['order']) && is_string($params['order']) ){
				$sort = [$params['order']=>1];
				if(($p = strpos($params['order'],' '))){
					/* Support for 'ORDER field (ASC|DESC)' */
					$field = substr($params['order'],0,$p);
					$o = substr($params['order'],$p+1);
					$sort = [$field=>($o == 'ASC') ? 1 : -1];
				}
				$params['order'] = $sort;
			}

			if( !isset($params['fields']) ){$params['fields'] = [];}
			$r = $this->collection->find($clause,$params['fields'])->timeout($this->timeout);
			if( isset($params['hint']) ){$r->hint($params['hint']);}
			if( isset($params['order']) ){$r->sort($params['order']);}
			if( isset($params['explain']) && $params['explain'] ){print_r($r->explain());exit;}
			if( $skip ){$r->skip($skip);}
			if( $limit ){$r->limit($limit);}

			if( isset($params['cursor']) && $params['cursor'] ){return $r;}

			$rows = [];
			if( $r && $params['indexBy'] !== false ){foreach( $r as $row ){
				if( !isset($row[$params['indexBy']]) ){$rows[] = $row;continue;}
				$k = is_array($row[$params['indexBy']]) ? implode('.',$row[$params['indexBy']]) : strval($row[$params['indexBy']]);
				$rows[$k] = $row;
			}}
			else{foreach($r as $row){$rows[] = $row;}}
			return $rows;
		}
		function _removeWhere($clause = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			try{
				return $this->collection->remove($clause);
			}catch(MongoException $e){
				return false;
			}
		}
		function _removeByID($id = false,$params = []){
			if( isset($id) && is_string($id) && strlen($id) == 24 && preg_match('/^[a-z0-9]+$/',$id) ){
				try{$id = new MongoId($id);}
				catch(MongoException $e){return false;}
			}
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return false;}
			try{
				return $this->collection->remove(['_id'=>$id]);
			}catch(MongoException $e){
				return false;
			}
		}
		function _removeByIDs($ids = [],$params = []){
			$ids = array_diff($ids,['']);
			$ids = array_unique($ids);
			$ids = array_map(function($id){
				if( is_string($id) && (preg_match('!^[a-zA-Z0-9]+$!',$id)) && strlen($id) == 24 ){$id = new MongoId($id);}
				return $id;
			},$ids);
			$ids = array_values($ids);
			$clause = ['_id'=>['$in'=>$ids]];
			return $this->removeWhere($clause,$params);
		}
		function _updateWhere($clause = [],$data = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			if( !isset($data['$set']) || !isset($data['$inc']) ){
				$data = ['$set'=>$data];
			}

			try{
				return $this->collection->update($clause,$data,[
					 'multiple'=>true
				]);
			}catch(MongoException $e){
				return false;
			}
		}
		function _aggregate($plan = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			$params['allowDiskUse'] = true;
			$rs = $this->collection->aggregate($plan,$params);
			return $rs;

			/* Hay que hacer el aggregate a través de command para
			 * poder establecer el timeout en el cursor */
			$rs = $this->client->selectDB($this->db)->command([
				 'aggregate'=>$this->table
				,'pipeline'=>$plan
				,'explain'=>isset($params['explain']) ? true : false
			],[
				 'socketTimeoutMS'=>$this->timeout
			]);
			return $rs;
		}
		function _findAndModify( $query = [], $update = [], $fields = [], $params = [] ) {
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			$options = [
				 'new'           => isset($params['new'])    ? (bool)$params['new']    : true
				,'remove'        => isset($params['remove']) ? (bool)$params['remove'] : false
				,'upsert'        => isset($params['upsert']) ? (bool)$params['upsert'] : false
			];
			if ( $options['remove'] ) { $update = []; }

			if ( isset($params['sort']) ) { $options['sort'] = $params['sort']; }
			else if( isset($params['order']) && is_string($params['order']) ){
				$sort = [$params['order']=>1];
				if(($p = strpos($params['order'],' '))){
					/* Support for 'ORDER field (ASC|DESC)' */
					$field = substr($params['order'],0,$p);
					$o = substr($params['order'],$p+1);
					$sort = [$field=>($o == 'ASC') ? 1 : -1];
				}
				$options['sort'] = $sort;
			}

			try{
				return $this->collection->findAndModify($query, $update, $fields, $options);
			}catch ( MongoException $e ) {
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
		}
		function _mapReduce($plan = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}

			$rs = $this->client->selectDB($this->db)->command([
				 'mapreduce'=>$this->table
				,'map'=>$plan['map']
				,'reduce'=>$plan['reduce']
				,'scope'=>$plan['scope']
				,'query'=>$plan['query']
				,'out'=>$plan['out']
			]);
			return $rs;
		}
		function _save(&$data = [],$params = []){
			/* INI-Remove invalid params */
			if( isset($GLOBALS['api']['mongo']['tables'][$this->table]) ){
				foreach($data as $k=>$v){if( !isset($GLOBALS['api']['mongo']['tables'][$this->table][$k]) ){unset($data[$k]);}}
			}
			/* END-Remove invalid params */

			$oldData = [];
			if( !isset($params['update.disabled']) && isset($data['_id']) && !($oldData = $this->_getByID($data['_id'],$params)) ){
				$oldData = [];
			}

			//$data = array_replace_recursive($oldData,$data);
			$data = $data+$oldData;
			if( isset($data['_id']) && is_string($data['_id']) && strlen($data['_id']) == 24 && preg_match('/^[a-z0-9]+$/',$data['_id']) ){
				try{$data['_id'] = new MongoId($data['_id']);}
				catch(MongoException $e){return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];}
			}
			if( !isset($data['_id']) ){$data['_id'] = new MongoId();}

			/* INI-validations */
			$data = $this->validate($data,$oldData);
			if( isset($data['errorDescription']) ){return $data;}
			/* END-validations */

			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			try{
				$this->collection->save($data);
			}catch(MongoException $e){
				$data = ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
				return $data;
			}
			$this->log($data, $oldData);
			return true;
		}
		function _iterator($clause = [],$callback = false,$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			if( !$callback || !is_callable($callback) ){return ['errorDescription'=>'NO_CALLBACK','file'=>__FILE__,'line'=>__LINE__];}

			$bar = function_exists('cli_pbar') && isset($params['bar']) ? 'cli_pbar' : false;
			try{
				$total = $this->collection->count($clause);
			}catch(MongoException $e){
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			$params['cursor'] = true;
			$params['limit']  = false;
			if( isset($params['order']) && is_string($params['order']) ){
				$sort = [$params['order']=>1];
				if(($p = strpos($params['order'],' '))){
					/* Support for 'ORDER field (ASC|DESC)' */
					$field = substr($params['order'],0,$p);
					$o = substr($params['order'],$p+1);
					$sort = [$field=>($o == 'ASC') ? 1 : -1];
				}
				$params['order'] = $sort;
			}
			$c = 0;

			try{
				if( isset($params['iterator.type']) && $params['iterator.type'] == 'where' ){
					$skip  = 0;  if( isset($params['skip']) ){$skip = $params['skip'];}
					$chunk = 20000;if( isset($params['chunk']) ){$chunk = $params['chunk'];}
					while($objectOBs = $this->getWhere($clause,['limit'=>$skip.','.$chunk,'order'=>'_id ASC'])){
						$skip += $chunk;
						foreach($objectOBs as $objectOB){
							$c++;
							if($bar){$bar($c,$total,$size=30);}
							$callback($objectOB,$this->collection);
						}
					}
				}else{
					$cursor = $this->collection->find($clause);
					if( isset($params['hint']) ){$cursor->hint($params['hint']);}
					if( isset($params['order']) ){$cursor->sort($params['order']);}
					if( isset($params['explain']) && $params['explain'] ){print_r($cursor->explain());exit;}
					$cursor->timeout(-1);
					while( ($row = $cursor->getNext()) ){
						$c++;if( $bar ){$bar($c,$total,$size=30);}
						$r = $callback($row,$this->collection);
						if( $r === 'break' ){break;}
					}
				}
			}catch(MongoException $e){
				return ['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__];
			}
			return true;
		}
		function _bulk(&$dataArray = [],$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			$dataArray  = array_map([$this,'validate'],$dataArray);
			$updtArray  = [];
//FIXME:  try catch
			try{
				$this->collection->batchInsert($dataArray,['continueOnError'=>true]);
			}catch(MongoException $e){
				print_r(['errorCode'=>$e->getCode(),'errorDescription'=>$e->getMessage(),'file'=>__FILE__,'line'=>__LINE__]);
exit;
			}
			return true;
		}
		function _search($criteria = '',$params = []){
			$r = $this->collection_get();
			if( is_array($r) && isset($r['errorDescription']) ){return $r;}
			if( !isset($params['indexBy']) ){$params['indexBy'] = '_id';}

			if( !$this->search_fields ){return [];}
			if( !isset($params['fields']) ){$params['fields'] = [];}
			$limitRows = 500;if(isset($params['row.limit'])){$limitRows = $params['row.limit'];}
			$match = false;if(isset($params['match'])){$match = $params['match'];}

			$words = explode(' ',$criteria);
			$countWords = count($words);
			$modeMultipleWords = ($countWords > 1);
			$criteriaLength = strlen($criteria);

			$cnd = [];
			foreach($this->search_fields as $field){
				foreach($words as $word){
					if( empty($word) ){continue;}
					$cnd[] = [$field=>['$regex'=>$word,'$options'=>'i']];
				}
			}
			$clause = ['$or'=>$cnd];
			if( $match ){$clause = ['$and'=>[$match,['$or'=>$cnd]]];}

			$cursor = $this->collection->find($clause,$params['fields']);
			$result = [];
			$i = 0;
			while($row = $cursor->getNext()){
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
	}

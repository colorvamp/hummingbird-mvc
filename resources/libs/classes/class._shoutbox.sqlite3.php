<?php
	/* INI-sqlite3 tables */
	$GLOBALS['api']['sqlite3']['tables']['shouts'] = [
		 '_id'=>'INTEGER AUTOINCREMENT'
		,'shoutChannel'=>'TEXT'
		,'shoutResponseTo'=>'INTEGER DEFAULT 0'
		,'shoutTitle'=>'TEXT'
		,'shoutTitleFixed'=>'TEXT'
		,'shoutText'=>'TEXT NOT NULL'
		,'shoutTextClean'=>'TEXT NOT NULL'
		,'shoutAuthor'=>'TEXT'
		,'shoutFollowersCount'=>'INTEGER DEFAULT 0'
		,'shoutTags'=>'TEXT'
		,'shoutModes'=>'TEXT'
		,'shoutMail'=>'TEXT'
		,'shoutIP'=>'TEXT NOT NULL'
		,'shoutImages'=>'TEXT'
		,'shoutRating'=>'TEXT'
		,'shoutVotes'=>'INTEGER'
		,'shoutDate'=>'TEXT NOT NULL'
		,'shoutTime'=>'INTEGER NOT NULL'
		,'shoutStamp'=>'INTEGER NOT NULL'
		,'shoutStatus'=>'INTEGER DEFAULT 0'
	];
	/* END-sqlite3 tables */
	/* INI-sqlite3 indexes */
	$GLOBALS['api']['sqlite3']['indexes']['shouts'] = [

	];
	/* END-sqlite3 indexes */

	class _shoutbox_sqlite3 extends _sqlite3{
		use __strings;
		public $table   = 'shouts';
		public $indexBy = '_id';
		function validate(&$data = [],&$oldData = []){
			if( empty($data['shoutIP']) && isset($_SERVER['SERVER_ADDR']) ){$data['shoutIP'] = $_SERVER['SERVER_ADDR'];}
			if( empty($data['shoutIP']) ){$data['shoutIP'] = 'unknown';}
			if( !isset($data['shoutAuthor']) && isset($GLOBALS['user']['_id']) ){$data['shoutAuthor'] = $GLOBALS['user']['_id'];}
			if( !isset($data['shoutStatus']) ){$data['shoutStatus'] = 'draft';}
			if( !isset($data['shoutStamp']) ){
				$data['shoutStamp'] = time();
				$data['shoutDate']  = date('Y-m-d',$data['shoutStamp']);
				$data['shoutTime']  = date('H:i:s',$data['shoutStamp']);
			}

			if( !empty($data['shoutChannel']['shoutChannel']['type']) && $data['shoutChannel']['shoutChannel']['type'] == 'shout' ){
				/* Todos los shouts cuelgan del mismo shout */
				$data['shoutChannel'] = $data['shoutChannel']['shoutChannel'];
			}
			if( !empty($data['shoutChannel']) && !empty($data['id']) ){unset($data['id']);}

			if( !isset($data['shoutTitle']) || empty($data['shoutTitle']) ){$data['shoutTitle'] = $this->strings_clean($data['shoutText'],60);}
			$data['shoutText'] = trim($data['shoutText']);
			$data['shoutText'] = stripslashes($data['shoutText']);
			$data['shoutText'] = str_replace(['‘','’'],['\'','\''],$data['shoutText']);
			$data['shoutTextClean'] = $this->strings_clean($data['shoutText']);

			if( isset($data['shoutTitle']) && $data['shoutTitle'] ){
				$data['shoutTitleFixed'] = strings_toURL($data['shoutTitle']);
			}

			return $data;
		}
		function log(&$data = [],&$oldData = []){
			if( !empty($data['shoutChannel']['type']) && $data['shoutChannel']['type'] == 'shout' ){
				//FIXME: hay que incrementar el contador de ese shout con un findandmodify
				// y pones cuales son los comentarios más votados etc para fast lookup
			}

			if( !empty($data['shoutText'])
			 && preg_match_all('!//image(\.src\.[0-9x]+|).(?<id>[a-z0-9]+)!',$data['shoutText'],$m)
			 && class_exists('catalog_images_TB') ){
				$catalog_images_TB = new catalog_images_TB();
				if( ($imageOBs = $catalog_images_TB->getByIDs($m['id'])) ){
					foreach( $imageOBs as $imageOB ){
						$imageOB['imageObjectData'][] = ['_id'=>$data['_id'],'type'=>'shout'];
						$imageOB['imageObjectData'][] = ['_id'=>$data['shoutAuthor'],'type'=>'user'];
						$catalog_images_TB->save($imageOB);
					}
				}
			}

			if( !empty($data['shoutChannel']['type'])
			 && $data['shoutChannel']['type'] == 'user'
			 && class_exists('users_notifications_TB') ){
				$users_notifications_TB = new users_notifications_TB();
				$notifOB = [
					 '_id'=>$data['_id']
					,'notifUser'=>$data['shoutChannel']['_id']
					,'notifType'=>'shout.created'
					,'notifData'=>[
						 'shoutChannel'=>$data['shoutChannel']
						,'shoutOB'=>$data['_id']
						,'userOB'=>$data['shoutAuthor']
					]
				];
				$users_notifications_TB->save($notifOB);
			}

			if( !empty($data['shoutChannel']) && class_exists('users_dashboard_TB') ){
				$users_dashboard_TB = new users_dashboard_TB();
				$dashOB = [
					 '_id'=>$data['_id']
					,'dashUser'=>$data['shoutAuthor']
					,'dashEvent'=>'shout.created'
					,'dashStamp'=>$data['shoutStamp']
					,'dashData'=>[
						 'shoutChannel'=>$data['shoutChannel']
						,'shoutOB'=>$data['_id']
					]
				];
				$users_dashboard_TB->save($dashOB);
			}
		}
//FIXME: esto debería loguear cambios
//FIXME: el removeByID tiene que quitar los votos
		function _channel_clause($channel = ''){
			$clause = ['shoutChannel'=>$channel];
			if( is_array($channel) ){
				$keys = array_keys($channel);
				$keys = array_diff($keys,['_id','id']);
				$key  = array_shift($keys);
				if( !preg_match('/^[a-z]+/',$key,$m) ){
					$clause = ['shoutChannel._id'=>$channel['_id']];
				}else{
					$clause = [
						 'shoutChannel._id'=>$channel['_id']
						,'shoutChannel.type'=>$m[0]
					];
				}
			}
			return $clause;
		}
		function countByChannel($channel = '',$params = ''){
			$clause = $this->_channel_clause($channel);
			return $this->count($clause,$params);
		}
		function getByID2($id = false,$params = []){
			return $this->getSingle(['id'=>intval($id)],$params);
		}
		function getByChannel($channel = '',$params = ''){
			$clause = $this->_channel_clause($channel);
			return $this->getWhere($clause,$params);
		}
		function getByChannels($channels = [],$params = ''){
			$channels = array_values($channels);
			return $this->getWhere(['shoutChannel'=>['$in'=>$channels]],$params);
		}
		function getFollowers($shoutID,$params = []){
			return $this->getWhere(['shoutResponseTo'=>$shoutID],$params);
		}
		function getThread($shoutID,$params = []){
			$parent = strlen($shoutID) > 10 ? $this->getByID($shoutID,$params) : $this->getByID2($shoutID,$params);
			if( !$parent ){return false;}

			/* If is not a parent we get the upper one */
			//if( isset($parent['shoutResponseTo']) && $parent['shoutResponseTo'] > 0 ){return $this->getThread($parent['shoutResponseTo'],$params);}
			$parent['childs'] = $this->getByChannel($parent,$params);
			return $parent;
		}
		function removeByChannel($channel = '',$params = ''){
			$clause = $this->_channel_clause($channel);
			return $this->removeWhere($clause,$params);
		}
		function channels_getThread(&$channelOBs = [],$params = []){
			$channelIDs = array_map(function($n){return $n['_id'];},$channelOBs);
			$channelIDs = array_values($channelIDs);
			$shoutOBs = $this->getWhere(['shoutChannel._id'=>['$in'=>$channelIDs]]);

			if( $shoutOBs && empty($params['no.resolve.users']) && class_exists('users_TB') ){
				/* Resolvemos los usuarios si hay posibilidad */
				$users_TB = new users_TB();
				$userIDs = array_map(function($n){return $n['shoutAuthor'];},$shoutOBs);
				$userOBs = $users_TB->getByIDs($userIDs);
				$userOBs = array_map(function($n){
					//FIXME: function_exists
					$n['url.user.profile'] = presentation_user_profile($n);
					$n['src.user.32'] = presentation_user_src($n,32);
					return $n;
				},$userOBs);
				foreach( $shoutOBs as &$shoutOB ){
					$userID = strval($shoutOB['shoutAuthor']);
					if( isset($userOBs[$userID]) ){$shoutOB['userOB'] = $userOBs[$userID];}
				}
				unset($shoutOB);
			}

			foreach( $shoutOBs as $id=>$shoutOB ){
				$channel = strval($shoutOB['shoutChannel']['_id']);
				if( isset($channelOBs[$channel]) ){
					$channelOBs[$channel]['channelShoutOBs'][$id] = $shoutOB;
				}
			}
			return true;
		}
	}

	/* INI-mongo tables */
	$GLOBALS['api']['mongo']['tables']['shouts.votes'] = [
		 '_id'=>'INTEGER AUTOINCREMENT'
		,'voteShout'=>'TEXT'
		,'voteUser'=>'TEXT'
		,'voteShoutAuthor'=>'TEXT'
		,'voteValue'=>'INTEGER DEFAULT 0'
		,'voteDate'=>'INTEGER DEFAULT 0'
	];
	/* END-mongo tables */
	/* INI-mongo indexes */
	$GLOBALS['api']['mongo']['indexes']['shouts.votes'] = [
		 ['fields'=>['voteShout'=>1,'voteUser'=>1],'props'=>['unique'=>true,'background'=>true]]
	];
	/* END-mongo indexes */
	class _shoutbox_votes extends _mongo{
		public $table  = 'shouts.votes';
		public $otable = 'shouts.votes';
		public $_shoutbox = false;
		public $_shoutbox_reputation = false;
		function validate(&$data = [],&$oldData = []){
			if( empty($data['voteShout']) ){return ['errorDescription'=>'INVALID_CHANNEL','file'=>__FILE__,'line'=>__LINE__];}

			if( empty($data['voteDate']) ){
				$data['voteDate']['stamp'] = time();
				$data['voteDate']['date']  = date('Y-m-d',$data['voteDate']['stamp']);
				$data['voteDate']['time']  = date('H:i:s',$data['voteDate']['stamp']);
			}

			return $data;
		}
		function log(&$data = [],&$oldData = []){
			if( empty($this->_shoutbox) ){$this->_shoutbox = new _shoutbox();}
			/* Actualizamos el shout original */
			$shoutOB = [
				 '_id'=>$data['voteShout']
				,'shoutVotes'=>[
					 'count'=>$this->count(['voteShout'=>$data['voteShout']])
					,'positive'=>$this->count(['voteShout'=>$data['voteShout'],'voteValue'=>1])
					,'negative'=>$this->count(['voteShout'=>$data['voteShout'],'voteValue'=>-1])
				]
			];
			$shoutOB['shoutVotes']['sum'] = $shoutOB['shoutVotes']['positive'] - $shoutOB['shoutVotes']['negative'];
			$this->_shoutbox->save($shoutOB);

			if( empty($this->_shoutbox_reputation) ){$this->_shoutbox_reputation = new _shoutbox_reputation();}
			$positive = $this->count(['voteShoutAuthor'=>$data['voteShoutAuthor'],'voteValue'=>1]);
			$negative = $this->count(['voteShoutAuthor'=>$data['voteShoutAuthor'],'voteValue'=>-1]);
			$reputationOB = ['_id'=>$data['voteShoutAuthor'],'reputationTotal'=>($positive - ($negative * 0.9))];
			$this->_shoutbox_reputation->save($reputationOB);
			//FIXME: ahora hay que actualizar el karma del dueño del comentario
		}
	}

	/* INI-mongo tables */
	$GLOBALS['api']['mongo']['tables']['shouts.reputation'] = [
		 '_id'=>'INTEGER AUTOINCREMENT'
		,'reputationTotal'=>'TEXT'
		,'reputationStats'=>'TEXT'
	];
	/* END-mongo tables */
	class _shoutbox_reputation extends _mongo{
		public $table  = 'shouts.reputation';
		function validate(&$data = [],&$oldData = []){
			if( !isset($data['reputationStats']) && isset($oldData['reputationStats']) ){$data += $oldData['reputationStats'];}
			$data['reputationTotal'] = intval($data['reputationTotal']);
			$data['reputationStats'][date('Y')]['months'][date('m')]['days'][date('d')] = $data['reputationTotal'];
			return $data;
		}
	}


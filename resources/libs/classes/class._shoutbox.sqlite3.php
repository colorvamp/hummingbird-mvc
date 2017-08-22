<?php
	/* INI-tables */
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
	/* END-tables */
	/* INI-indexes */
	$GLOBALS['api']['sqlite3']['indexes']['shouts'] = [

	];
	/* END-indexes */

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

		}
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
			$parent = $this->getByID($shoutID,$params);
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



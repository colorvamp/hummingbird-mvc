<?php
	/* INI-mongo tables */
	$GLOBALS['api']['mongo']['tables']['users'] = [
		 '_id'=>'INTEGER AUTOINCREMENT'
		,'userMail'=>'TEXT'
		,'userPass'=>'TEXT'
		,'userSalt'=>'TEXT'
		,'userName'=>'TEXT'
		,'userAddr'=>'TEXT'
		,'userCode'=>'TEXT'
		,'userNick'=>'TEXT'
		,'userPhone'=>'TEXT'
		,'userDate'=>'TEXT'
		,'userIP'=>'TEXT'
		,'userStatus'=>'TEXT'
		,'userModes'=>'TEXT'
		,'userLastLogin'=>'TEXT'
		,'userData'=>'TEXT'
	];
	/* END-mongo tables */
	/* INI-mongo indexes */
	$GLOBALS['api']['mongo']['indexes']['users'] = [
		 ['fields'=>['userMail'=>1],'props'=>['unique'=>true]]
		,['fields'=>['userNick'=>1],'props'=>['unique'=>true]]
	];
	/* END-mongo indexes */

	/* INI-User */
	class users_TB extends _mongo{
		public $table = 'users';
		public $search_fields = ['userName'];
		public $reg_mail_clear = '/[^a-z0-9\._\+\-\@]*/';
		public $reg_mail_match = '/^[a-z0-9\._\+\-]+@[a-z0-9\.\-]+\.[a-z]{2,6}$/';
		function validate (&$data = [],&$oldData = []) {
			if( isset($data['userSalt']) && !$data['userSalt'] ){unset($data['userSalt']);}
			if( isset($data['userCode']) && !$data['userCode'] ){unset($data['userCode']);}

			if( !isset($data['userMail']) || !preg_match($this->reg_mail_match,$data['userMail']) ){return ['errorDescription'=>'EMAIL_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			if( !isset($data['userStatus']) ){$data['userStatus'] = 0;}
			if( !isset($data['userNick']) ){$data['userNick'] = strval($data['_id']);}
			if( !isset($data['userCode']) ){$data['userCode'] = [$this->generateCode($data['userMail'])=>time(),'confirm'=>$this->generateCode($data['userMail'])];}
			if( !isset($data['userDate']) ){$data['userDate'] = ['stamp'=>time(),'date'=>date('Y-m-d'),'time'=>date('H:i:s')];}
			if( !isset($data['userModes']) ){$data['userModes'] = [];}

			if( isset($data['userPass']) && !isset($data['userSalt']) ){
				if( $data['userPass'] == '12345678' ){return ['errorDescription'=>'PASSWORD_NOT_SECURE','file'=>__FILE__,'line'=>__LINE__];}
				$data['userSalt'] = $this->generateSalt();
				$data['userPass'] = sha1($data['userSalt'].$data['userPass']);
			}
			if( isset($data['userStatus']) ){$data['userStatus'] = intval($data['userStatus']);}

			if( isset($data['userModes']) && is_string($data['userModes']) ){$data['userModes'] = explode(',',$data['userModes']);}
			if( isset($data['userModes']) ){foreach( $data['userModes'] as $k=>$v){
				if( !$v ){unset($data['userModes'][$k]);}
			}}

			if( isset($data['userPhone']) ){$data['userPhone'] = preg_replace('/[^0-9,\+]*/','',$data['userPhone']);}

			/* Si es el usuario actual tenemos que actualizar los datos en el momento */
			if( isset($data['_id'],$GLOBALS['user']['_id']) && $data['_id'] == $GLOBALS['user']['_id'] ){
				$_SESSION['user'] = $GLOBALS['user'] = $data;
			}
			return $data;
		}
		function login ($userOB = false,$userPass = '') {
			if( is_string($userOB) && strpos($userOB,'@') ){
				if( !($userOB = $this->getByMail($userOB)) ){
					return ['errorDescription'=>'USER_ERROR','file'=>__FILE__,'line'=>__LINE__];
				}
			}
			if( !isset($userOB['userSalt']) ){return ['errorDescription'=>'USER_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			$hashPass = sha1($userOB['userSalt'].$userPass);
			if( $hashPass != $userOB['userPass'] ){$shouldSalt = true;$hashPass = $userPass;}
			if( $hashPass != $userOB['userPass'] ){$shouldSalt = true;$hashPass = sha1($userPass);}
			if( $hashPass != $userOB['userPass'] ){return ['errorDescription'=>'PASSWORD_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			/* User must be validated */
			if( !isset($userOB['userStatus']) || !$userOB['userStatus'] ){return ['errorDescription'=>'USER_NOT_ACTIVE','file'=>__FILE__,'line'=>__LINE__];}
			if( !isset($userOB['userNick']) ){$userOB['userNick'] = strval($userOB['_id']);}

			$userOB  = ['userIP'=>$_SERVER['REMOTE_ADDR'],'userLastLogin'=>time(),'userCode'=>false]+$userOB;
			if( isset($shouldSalt) ){$userOB = ['userPass'=>$userPass,'userSalt'=>false]+$userOB;}
			$r = $this->save($userOB);
			if( isset($r['errorDescription']) ){return $r;}

			return $this->impersonate($userOB);
		}
		function logout () {
			session_destroy();
			setcookie('u','',-1,'/');
		}
		function getByMail ($mail = '',$params = []) {
			return $this->getSingle(['userMail'=>$mail],$params);
		}
		function removeByID ($id = false,$params = []) {
			$userOB = $this->getByID($id);

			return $this->_removeByID($id,$params);
		}
		function removeWhere ($clause = [],$params = []) {
//FIXME: iterar sobre removeByID
			return $this->_removeWhere($clause,$params);
		}
		function impersonate ($userOB = []) {
			if( empty($userOB['_id']) ){return false;}
			if( !session_id() ){session_start();}
			reset($userOB['userCode']);
			setcookie('u',key($userOB['userCode']),time()+360000,'/');
			$_SESSION['user'] = $GLOBALS['user'] = $userOB;
			$this->findAndModify(['_id'=>$userOB['_id']],['$set'=>['userIP'=>$_SERVER['REMOTE_ADDR']]],['_id'=>true]);
			return $userOB;
		}
		function generateCode ($userMail = '') {
			return sha1($userMail.time().date('Y-m-d H:i:s'));
		}
		function generateSalt () {
			$pass_a = ['?','$','¿','!','¡','{','}'];
		    	$pass_b = ['a','e','i','o','u','b','c','d','f','g','h','j','k','l','m','n','p','q','r','s','t','v','w','x','y','z'];
			$salt = '';for($a=0; $a<4; $a++){$salt .= $pass_a[array_rand($pass_a)];$salt .= $pass_b[array_rand($pass_b)];}
			return $salt;
		}
		function isLogged () {
			if( isset($GLOBALS['user']) && is_array($GLOBALS['user']) ){return true;}
			if( isset($_SESSION['user']) && is_array($_SESSION['user']) ){$GLOBALS['user'] = $_SESSION['user'];return true;}
			if( isset($_COOKIE['u']) && strlen($_COOKIE['u']) == 40 ){
				$_COOKIE['u'] = preg_replace('/[^0-9a-zA-Z]*/','',$_COOKIE['u']);
				$userOB = $this->getSingle(['userIP'=>$_SERVER['REMOTE_ADDR'],'userCode.'.$_COOKIE['u']=>['$exists'=>true]]);
				if( !$userOB || !isset($userOB['_id']) ){setcookie('u','',-1,'/');return false;}
				if( !session_id() ){session_start();}
				$_SESSION['user'] = $GLOBALS['user'] = $userOB;
				return true;
			}
			return false;
		}
		function checkModes ($mode = '',$userOB = []) {
			if( isset($GLOBALS['user']) && !$userOB ){$userOB = $GLOBALS['user'];}
			if( !isset($userOB['userModes']) ){return false;}
			if(  isset($userOB['userModes'][$mode]) ){return true;}
			/* El campo no se podía indexar en mongo, asique hay que buscar en los valores */
			return (array_search($mode,$userOB['userModes']) !== false);
		}
	}
	/* END-User */

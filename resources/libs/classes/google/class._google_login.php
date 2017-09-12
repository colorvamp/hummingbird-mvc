<?php
	class _google_login extends _google{
		public $redirect = 'http://example.com/auth';
		public $scope = 'email+profile';
		public $file  = '../db/google.creds/';
		function __construct($id = ''){
			parent::__construct();
			$this->file .= $id.'.json';
		}
		function getInfo($id = 'me'){
			if( ($r = $this->account()) && is_array($r) ){return $r;}
			$url    = 'https://www.googleapis.com/plus/v1/people/'.$id;
			$header = ['Authorization'=>'OAuth '.$this->account['access_token']];
			$data   = ['header'=>$header];
			$data   = $this->query($url,$data);
			$resp   = json_decode($data['page-content'],1);
			if( isset($resp['error']['code']) ){
				if( $resp['error']['code'] == 401 ){
					$r = $this->oauth2_refresh();
					if( isset($r['errorDescription']) ){print_r($r);exit;}
					return call_user_func_array([$this,__FUNCTION__],func_get_args());
				}
				return ['errorDescription'=>strtoupper($json['error']['message']),'file'=>__FILE__,'line'=>__LINE__];
			}
			return $resp;
		}
		function login(){
			$info = $this->getInfo();
			if(  isset($info['errorDescription']) ){return $info;}
			if( !isset($info['emails'][0]['value']) ){return false;}
			//if( !preg_match('!@gmail.com$!',$info['emails'][0]['value']) ){return false;}

			$users_TB = new users_TB();
			$userOB  = $users_TB->getByMail($info['emails'][0]['value']);
			if( !$userOB ){
				/* Registramos un nuevo usuario */
				$userOB = [
					 'userMail'=>$info['emails'][0]['value']
					,'userName'=>$info['displayName']
					,'userIP'=>$_SERVER['REMOTE_ADDR']
					,'userStatus'=>1
				];
				$users_TB->save($userOB);
			}else{
				$userOB['userIP'] = $_SERVER['REMOTE_ADDR'];
				$users_TB->save($userOB);
			}

			/* INI-Salvamos la image */
			/*$mongoimages = new mongoimages();
			if( !($imageOB = $mongoimages->getSingle(['imageObject'=>['_id'=>$userOB['_id'],'type'=>'user']])) ){do{
				$tmpFile = '/tmp/'.uniqid();
				$blob = @file_get_contents(str_replace(['?sz=50'],[''],$info['image']['url']));
				if( !$blob ){break;}
				file_put_contents($tmpFile,$blob);
				$imageOB = [
					 'imageName'=>$userOB['userName']
					,'imageObject'=>['_id'=>$userOB['_id'],'type'=>'user']
				];
				$mongoimages->blob_store($imageOB,$tmpFile);
				unlink($tmpFile);
			}while(false);}
			/* END-Salvamos la image */

			$users_TB->impersonate($userOB);
		}
	}

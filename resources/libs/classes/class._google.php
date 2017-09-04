<?php
	class _google{
		use __html_fileg;
		public $clientID = '';
		public $secret   = '';
		public $redirect = '';
		public $scope    = 'https://www.googleapis.com/auth/analytics.readonly';
		public $path     = '../db/google.creds/';
		public $file     = '../db/google.creds/analytics.json';
		public $account  = false;
		function __construct(){
			new _path($this->path);
		}
		function account(){
			if( $this->account ){return true;}
			if( !file_exists($this->file) ){return ['errorDescription'=>'INVALID_CREDENTIALS','file'=>__FILE__,'line'=>__LINE__];}
			$this->account = json_decode(file_get_contents($this->file),true);
			if( !$this->account ){
				unlink($this->file);
				return ['errorDescription'=>'INVALID_CREDENTIALS','file'=>__FILE__,'line'=>__LINE__];
			}
			return true;
		}
		function oauth2_info(){
			if( ($r = $this->account()) && is_array($r) ){return $r;}
			$url = 'https://www.googleapis.com/oauth2/v1/tokeninfo?access_token='.$this->account['access_token'];
			$r = file_get_contents($url);
print_r($r);
exit;
		}
		function oauth2_exists(){
			return file_exists($this->file);
		}
		function oauth2_code($hint = false){
//FIXME: meter el hint
			$url = 'https://accounts.google.com/o/oauth2/auth?'
				.'response_type=code&'
				.'client_id='.$this->clientID.'&'
				.'redirect_uri='.$this->redirect.'&'
				.'scope='.$this->scope.'&'
				.'access_type=offline&'
				.'approval_prompt=force'
			;
			if( $hint ){$url .= '&login_hint='.$hint;}

			return $url;
		}
		function oauth2_token($code){
			$post = [
				 'code'=>$code
				,'client_id'=>$this->clientID
				,'client_secret'=>$this->secret
				,'redirect_uri'=>$this->redirect
				,'grant_type'=>'authorization_code'
			];
			$url  = 'https://accounts.google.com/o/oauth2/token';
			$data = $this->query($url,['post'=>$post]);
			if( $data['page-code'] != 200 && $data['page-code'] != 302 ){return ['errorCode'=>$data['page-code'],'errorDescription'=>'AUTH_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			$json = json_decode($data['page-content'],true);
			if(isset($json['error'])){return ['errorDescription'=>strtoupper($json['error']),'file'=>__FILE__,'line'=>__LINE__];}
			//$this->file = path_check($this->file);
			file_put_contents($this->file,$data['page-content']);
			return true;
		}
		function oauth2_refresh(){
			$post = [
				 'client_id'=>$this->clientID
				,'client_secret'=>$this->secret
				,'refresh_token'=>$this->account['refresh_token']
				,'grant_type'=>'refresh_token'
			];
			$url  = 'https://accounts.google.com/o/oauth2/token';
			$data = $this->query($url,['post'=>$post]);
			if( isset($data['errorDescription']) ){return $data;}

			$json = json_decode($data['page-content'],1);
			if(  isset($json['error']) ){return ['errorDescription'=>strtoupper($json['error']),'file'=>__FILE__,'line'=>__LINE__];}
			if( !isset($json['access_token']) ){
				echo 'invalid:';
				print_r($data);exit;
			}

			$this->account['access_token'] = $json['access_token'];
			file_put_contents($this->file,json_encode($this->account));
			return true;
		}
	}

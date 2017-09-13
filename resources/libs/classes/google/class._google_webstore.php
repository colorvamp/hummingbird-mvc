<?php
	class _google_webstore extends _google{
		public $scope = 'https://www.googleapis.com/auth/chromewebstore';
		public $path  = '../db/google.webstore.creds/';

		function __construct($mail = ''){
			parent::__construct();
			$this->mail = $mail;
			$this->file = $this->path.$this->mail.'.json';
		}
		function items_get($id = ''){
			if( ($r = $this->account()) && is_array($r) ){return $r;}
			$url    = 'https://www.googleapis.com/chromewebstore/v1.1/items/'.$id.'?projection=draft';
			$header = ['Authorization'=>'OAuth '.$this->account['access_token'],'x-goog-api-version'=>2];
			$params = ['header'=>$header];
			$data   = $this->query($url,$params);
print_r($data);
		}
	}

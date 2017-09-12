<?php
	class _google_analytics extends _google{
		/* https://www.googleapis.com/auth/analytics */
		public $scope = 'https://www.googleapis.com/auth/analytics.readonly';
		public $path  = '../db/google.analytics.creds/';
		function __construct($mail = ''){
			parent::__construct();
			$this->mail = $mail;
			$this->file = $this->path.$this->mail.'.json';
		}
		function accounts_list(){
			if( ($r = $this->account()) && is_array($r) ){return $r;}
			$url    = 'https://www.googleapis.com/analytics/v3/management/accounts';
			$header = ['Authorization'=>'OAuth '.$this->account['access_token']];
			$data   = ['headers'=>$header];
			$data   = html_query($url,$data);
			$resp   = json_decode($data['pageContent'],1);
			if( isset($resp['error']['code']) && $resp['error']['code'] == 401 ){
				$r = $this->oauth2_refresh();
				if( isset($r['errorDescription']) ){print_r($r);exit;}
				return call_user_func_array([$this,__FUNCTION__],func_get_args());
			}
			return $resp;
		}
		function ga($profileID = '',$params = []){
			/* Las fechas son inclusivas */
			if( !isset($params['date.start']) ){$params['date.start'] = date('Y-m-d',strtotime('-1 day'));}
			if( !isset($params['date.end']) ){$params['date.end'] = $params['date.start'];}
			if( !isset($params['metrics']) ){$params['metrics'] = 'ga:pageviews';}
			if( !isset($params['dimensions']) ){$params['dimensions'] = 'ga:pagePath';}
			/* /visitors-overview/a59441w94953p98228720/ -> 98228720 */
			//$_valid_metrics = ['ga:pageviews'=>1,'ga:sessions'=>1];
			//if( !isset($_valid_metrics[$metrics]) ){return ['errorDescription'=>'INVALID_METRICS','file'=>__FILE__,'line'=>__LINE__];}
			if( ($r = $this->account()) && is_array($r) ){return $r;}
			$url    = 'https://www.googleapis.com/analytics/v3/data/ga?ids=ga:'.$profileID.'&start-date='.$params['date.start'].'&end-date='.$params['date.end'].'&metrics='.$params['metrics'].'&dimensions='.$params['dimensions'];
			$header = ['Authorization'=>'OAuth '.$this->account['access_token']];
			$data   = ['headers'=>$header];
			$data   = html_query($url,$data);
			$resp   = json_decode($data['pageContent'],1);
			if( isset($resp['error']['code']) && $resp['error']['code'] == 401 ){
				$r = $this->oauth2_refresh();
				if( isset($r['errorDescription']) ){print_r($r);exit;}
				return call_user_func_array([$this,__FUNCTION__],func_get_args());
			}
			return $resp;
		}
	}

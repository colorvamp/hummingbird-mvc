<?php
	class _google_gmail extends _google{
		public $scope = 'https://www.googleapis.com/auth/gmail.compose';
		public $path  = '../db/gmail.creds/';
		public $mail  = '';
		public $file  = '';
		function __construct($mail = ''){
			parent::__construct();
			$this->mail = $mail;
			$this->file = $this->path.$this->mail.'.json';
		}
		function send($params = []){
			if( !function_exists('html_query') ){include_once('inc.html.curl.php');}
			if( ($r = $this->account()) && isset($r['errorDescription']) ){return $r;}
			$token = $this->account['access_token'];

			if( !isset($params['from']) ){$params['from'] = $this->mail;}

			$raw = $this->compose($params);
			$url = 'https://www.googleapis.com/upload/gmail/v1/users/me/messages/send';
			$header = [
				 'Content-Type'=>'message/rfc822'
				,'Authorization'=>'OAuth '.$token
			];
			$data = ['headers'=>$header,'post'=>$raw];
			$data = html_query($url,$data);
			if( isset($data['errorDescription']) ){return $data;}
			//if( $data['pageCode'] != 200 ){print_r($data);exit;}
			$resp = json_decode($data['pageContent'],1);
			if( isset($resp['error']['code']) && $resp['error']['code'] == 401 ){
				$r = $this->oauth2_refresh();
				if( isset($r['errorDescription']) ){print_r($r);exit;}
				return call_user_func_array([$this,__FUNCTION__],func_get_args());
			}
			return $resp;
		}
		function compose($params = []){
			$CR = "\r\n";
			$boundary = md5(time());

			if( isset($params['files']) ){
				$cids  = [];
				$files = '';
				$finfo = finfo_open(FILEINFO_MIME_TYPE);
				foreach($params['files'] as $file){
					$isArray = is_array($file);
					if( !$isArray && !file_exists($file) ){continue;}

					$name = $isArray ? $file['name'] : basename($file);
					$mime = $isArray ? $file['mime'] : finfo_file($finfo,$file);
					$uniq = uniqid();

					$files .= '--'.$boundary.$CR;
					$files .= 'Content-Type: '.$mime.'; name="'.$name.'"'.$CR;
					$files .= 'Content-ID: <'.$uniq.'>'.$CR;
					$files .= 'Content-Transfer-Encoding: base64'.$CR;
					$files .= 'Content-Disposition: inline; filename="'.$name.'"'.$CR.$CR;
					if( !$isArray ){$files .= base64_encode(file_get_contents($file)).$CR.$CR;}
					if( $isArray && isset($file['blob']) ){$files .= base64_encode($file['blob']).$CR.$CR;}
					$cids[] = $uniq;
				}
				finfo_close($finfo);

				if( isset($params['body']) ){foreach($cids as $k=>$cid){$params['body'] = str_replace('{%image.'.$k.'%}','cid:'.$cid,$params['body']);}}
			}

			if( !isset($params['body']) ){$params['body'] = '';}
			$raw =
				 'MIME-Version: 1.0'.$CR
				.( isset($params['subject']) ? 'Subject: '.utf8_decode($params['subject']).$CR : '' )
				.( isset($params['to']) ? 'To: '.$params['to'].$CR : '')
				.( isset($params['bcc']) ? 'Bcc: '.$params['bcc'].$CR : '')
				.( isset($params['from']) ? 'From: '.$params['from'].$CR : '' )
				.'Content-Type: multipart/related; boundary='.$boundary.$CR.$CR
				.'--'.$boundary.$CR
				.'Content-Type: text/html; charset="UTF-8"'.$CR.$CR
				.$params['body'].$CR.$CR
				.( isset($params['files']) && $files ? $files.$CR : '' );
			//echo $raw;exit;
			return $raw;
		}
	}

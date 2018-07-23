<?php
	class _ssh2{
		public $ip   = false;
		public $port = 22;
		public $conn = false;
		public $user = false;
		public $pass = false;
		
		function __construct($ip = false,$params = []){
			$this->ip = $ip;
			if( !empty($params['port']) ){$this->port = $params['port'];}
			if( !empty($params['user']) ){$this->user = $params['user'];}
			if( !empty($params['pass']) ){$this->pass = $params['pass'];}
		}
		function connect(){
			if( ($this->conn = @ssh2_connect($this->ip,$this->port)) === false ){return ['errorDescription'=>'CONN_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			if( $this->user && $this->pass ){
				$r = @ssh2_auth_password($this->conn,$this->user,$this->pass);
				if( !$r ){
					$this->conn = false;
					return ['errorDescription'=>'AUTH_ERROR','file'=>__FILE__,'line'=>__LINE__];
				}
			}
		}
		function disconnect(){
			
		}
		function command($comm = ''){
			$stream = ssh2_exec($this->conn,$comm);
			stream_set_blocking($stream,true);
			stream_set_timeout($stream,10);
			return trim(stream_get_contents($stream));
		}
		function directory_exists($directory = ''){
			$r = $this->command('if [ -d "'.$directory.'" ]; then echo 1;fi');
			if( $r == 1 ){return true;}
			return false;
		}
		function directory_create($directory = ''){
			$r = $this->command('if [ ! -d "'.$directory.'" ]; then mkdir -p '.$directory.';chmod 777 '.$directory.'; fi;if [ -d "'.$directory.'" ]; then echo 1;fi');
			if( $r == 1 ){return true;}
			return false;
		}
		function file_exists($file = ''){
			$r = $this->command('if [ -f "'.$file.'" ]; then echo 1;fi');
			if( $r == 1 ){return true;}
			return false;
		}
		function file_copy($file = '',$dest = ''){
			$r = $this->command('if [ ! -f "'.$file.'" ]; then echo 1; else cp "'.$file.'" "'.$dest.'";fi');
			if( $r == 1 ){return false;}
			return true;
		}
		function file_replace_callback($remoteFile = '',$callback = false){
			$r = $this->command('if [ ! -f "'.$remoteFile.'" ]; then echo 1; else cat '.$remoteFile.';fi');
			if( $r == 1 ){return false;}
			$blob = $r;
			if( $callback ){$blob = $callback($blob);}
			$tmp = '/run/shm/'.uniqid();
			file_put_contents($tmp,$blob);
			$r = $this->file_send($tmp,$remoteFile,0644);
			unlink($tmp);
			return $r;
		}
		function file_send($localFile = '',$remoteFile = '',$permision = 0644){
			if( !is_file($localFile) ){return false;}
			$remotePath = dirname($remoteFile);
			$this->directory_create($remotePath);

			$sftp = ssh2_sftp($this->conn);
			$sftpStream = @fopen('ssh2.sftp://'.$sftp.$remoteFile,'w');
			if( !$sftpStream ){
			    //  if 1 method failes try the other one
			    /*if ( ! @ssh2_scp_send ( $this->conn, $localFile, $remoteFile, $permision ) ) {
				throw new Exception ( "Could not open remote file: $remoteFile" );
			    }
			    else {
				return true;
			    }*/
				echo 'error on file send';
				exit;
			}

			$data_to_send = file_get_contents($localFile);
			if( @fwrite($sftpStream,$data_to_send) === false ){return false;}
			fclose($sftpStream);

			return true;
		}	
	}

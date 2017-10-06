<?php
	class _file{
		public $path = false;
		public $name = false;
		public $file = false;
		public $fp   = false;
		public $len  = 0;
		function __construct(...$args){
			/* args variables -> (':rooms','17591','17-03-2015',true) */
			$exists = false;
			$last = array_pop($args);
			if( is_bool($last) ){$exists = array_pop($args);}
			if( !preg_match('![^/]+$!',$last,$_name) ){return false;}
			$this->name = $_name[0];

			$last = substr($last,0, -1 * strlen($this->name) );
			if( $exists ){$args[] = $exists;}
			$this->path = new _path(...$args);

			$this->file = $this->path.$this->name;
		}
		function __destruct(){
			if( $this->fp ){fclose($this->fp);}
		}
		function __toString(){
			return $this->file;
		}
		function _open(){
			if(  $this->fp ){return true;}
			if( !$this->file ){return false;}
			//FIXME: not always a+
			$this->fp = fopen($this->file,'a+');
			fseek($this->fp,0,SEEK_END);
			$this->len = ftell($this->fp);
			return $this->fp;
		}
		function close(){
			if( $this->fp ){
				fclose($this->fp);
				$this->fp = false;
			}
		}
		function stat(){
			if( !file_exists($this->file) ){
				return false;
			}
			$stat = stat($this->file);
			$stat['diff'] = time() - $stat['mtime'];
			$stat['diff.days'] = round( $stat['diff'] / 86400 );
			return $stat;
		}
		function len(){
			return $this->len;
		}
		function outln($line = ''){
			if( !$this->_open() ){return ['errorDescription'=>'FILE_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			$len = strlen($line);
			if( $line[$len - 1] != PHP_EOL ){
				$line .= PHP_EOL;
				$len += 1;
			}
			if( ($r = fwrite($this->fp,$line)) ){
				fflush($this->fp);
				$this->len += $len;
			}
			return $r;
		}
		function truncate(){
			if( !$this->_open() ){return ['errorDescription'=>'FILE_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			if( ($r = ftruncate($this->fp,0)) ){
				fflush($this->fp);
				fseek($this->fp,0);
				$this->len = 0;
			}
			return $r;			
		}
		function remove(){
			$this->close();
			if( $this->file
			 && file_exists($this->file) ){unlink($this->file);}
		}
		function iterator($glob = '*',$callback = false,$params = []){
			if( !$this->_open() ){return ['errorDescription'=>'FILE_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			//FIXME: TODO
		}
	}

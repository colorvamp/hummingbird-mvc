<?php
	class _zip{
		public $zip  = false;
		public $path = false;
		public $file = false;
		function __construct(...$args){
			/* args variables -> (':rooms','17591','17-03-2015',true) */
			$exists = false;
			$last = array_pop($args);
			if( is_bool($last) ){$exists = array_pop($args);}
			if( substr($last,-4) !== '.zip' ){return false;}
			if( !preg_match('![^/]+\.zip$!',$last,$_name) ){return false;}
			$_name = $_name[0];

			$last = substr($last,0, -1 * strlen($_name) );
			if( $exists ){$args[] = $exists;}
			$this->path = new _path($last);

			$this->file = $this->path.$_name;
		}
		function __destruct(){
			if( $this->zip ){$this->close();}
		}
		function __toString(){
			return $this->file;
		}
		function _open(){
			if(  $this->zip ){return true;}
			if( !$this->file ){return false;}
			if( !($zip = new ZipArchive()) || $zip->open($this->file,ZipArchive::CREATE) !== true ){
				if( file_exists($this->file) ){$r = unlink($this->file);}
				return false;
			}
			$this->zip = $zip;
			return true;
		}
		function close(){
			if( $this->zip ){
				$this->zip->close();
				$this->zip = false;
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
		function ls(){
			if( !$this->_open() ){return ['errorDescription'=>'ZIP_ERROR','file'=>__FILE__,'line'=>__LINE__];}

			$filenames = [];
			for( $i = 0; $i < $this->zip->numFiles; $i++ ){
				$filenames[] = $filename = $this->zip->getNameIndex($i);
			}
			return $filenames;
		}
		function blob($name = ''){
			if( !$this->_open() ){return ['errorDescription'=>'ZIP_ERROR','file'=>__FILE__,'line'=>__LINE__];}

			/* Devuelve 'false' si el fichero no existe */
			$blob = $this->zip->getFromName($name);
			return $blob;
		}
		function remove($path = ''){
			if( !$this->_open() ){return ['errorDescription'=>'ZIP_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			return $this->zip->deleteName($path);
		}
		function file_put_contents($path = '',$blob = ''){
			if( !$this->_open() ){return ['errorDescription'=>'ZIP_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			if( $path[0] == '/' ){$path = substr($path,1);}
			return $this->zip->addFromString($path,$blob);
		}
		function iterator($glob = '*',$callback = false,$params = []){
			if( !$this->_open() ){return ['errorDescription'=>'ZIP_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			//FIXME: TODO
		}
		function addFile($file = '',$path = ''){
			if( !$this->_open() ){return ['errorDescription'=>'ZIP_ERROR','file'=>__FILE__,'line'=>__LINE__];}
			$file = strval($file);
			if( !file_exists($file) ){return ['errorDescription'=>'FILE_NOT_FOUND','file'=>__FILE__,'line'=>__LINE__];}
			return $this->zip->addFile($file,$path);
		}
	}

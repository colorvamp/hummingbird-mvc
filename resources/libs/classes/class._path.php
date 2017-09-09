<?php
	class _path{
		public $path = false;
		public $writable = true;
		function __construct(){
			/* args variables -> (':rooms','17591','17-03-2015',true) */
			$args   = func_get_args();
			$exists = false;
			$this->path = '';
			if( ($p = current($args)) && is_string($p) && $p[0] == ':' ){$this->path = array_shift($args);}
			if( is_bool(end($args)) ){$exists = array_pop($args);}

			switch( $this->path ){
				case ':tmp':    $this->path = '../db/tmp/';break;
				case ':images': $this->path = '../db/images/';break;
				case ':cache':  $this->path = '../db/cache/';break;
			}

			$args   = array_map(function($n){
				if( substr($n,-1) == '/' ){$n = substr($n,0,-1);}
				return $n;
			},$args);

			$this->path .= ($args) ? implode('/',$args).'/' : '';
			$this->path = preg_replace('![/]+$!','/',$this->path);
			if( !$exists && !file_exists($this->path) ){
				umask(0);$r = mkdir($this->path,0777,1);chmod($this->path,0777);
				if( !file_exists($this->path) ){$this->writable = false;return false;}
			}
		}
		function __toString(){
			return $this->path;
		}
		function writable(){
			return $this->writable;
		}
		function realPath(){
			return realpath($this->path).'/';
		}
		function checkBase($base = ''){
			if( ($base = realpath($base)) === false ){return false;}
			$path = realpath($this->path);
			if( strpos($path,$base) !== 0 ){return false;}
			return true;
		}
		function getNames($glob = '*'){
			$files = glob($this->path.$glob);
			$files = array_map(function($f){return basename($f);},$files);
			return $files;
		}
		function iterator($glob = '*',$callback = false,$params = []){
			$files = glob($this->path.$glob);
			foreach( $files as &$file ){
				$file = $callback($file);
			}
			unset($file);
			return $files;
		}
		function rename($name = ''){
			while( strpos($name,'/') !== false
			 || strpos($name,'..') !== false
			){$name = str_replace(['/','..'],'',$name);}
			$path = dirname($this->path).'/'.$name;
			//FIXME: is_writable?
			if( ($r = rename($this->path,$path)) ){
				$this->path = $path;
			}
			return $r;
		}
		function remove(){
			return $this->_remove($this->path);
		}
		function _remove($path = '',$avoidCheck = false){
			if( !$avoidCheck ){
				$path = preg_replace('![/]*$!','/',$path);
				if( !file_exists($path) || !is_dir($path) ){return false;}
			}
			if( $handle = opendir($path) ){
				while( false !== ($file = readdir($handle)) ){
					if( $file == '.' || $file == '..' ){continue;}
					if( is_dir($path.$file) ){$this->_remove($path.$file.'/',true);continue;}
					unlink($path.$file);
				}
				closedir($handle);
			}
			rmdir($path);
			return true;
		}
	}

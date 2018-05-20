<?php
	class _git{
		public $path = false;
		private $bin = '/usr/bin/git';
		function __construct(){
//FIXME: use path class
			/* args variables -> (':rooms','17591','17-03-2015',true) */
			$args   = func_get_args();
			$exists = false;
			$this->path = '';
			if( ($p = current($args)) && is_string($p) && $p[0] == ':' ){$this->path = array_shift($args);}
			if( is_bool(end($args)) ){$exists = array_pop($args);}

			switch( $this->path ){
				case ':tmp':			$this->path = '../db/tmp/';break;
				case ':images':			$this->path = '../db/images/';break;
				case ':db':			$this->path = '../db/';break;
			}

			$args   = array_map(function($n){
				if( substr($n,-1) == '/' ){$n = substr($n,0,-1);}
				return $n;
			},$args);

			$this->path .= ($args) ? implode('/',$args).'/' : '';
		}
		function _status(){
			$data = shell_exec('cd "'.$this->path.'" && git status 2>&1');
			if (preg_match('!fatal: This operation must be run in a work tree!',$data)) {
				return ['errorDescription'=>'INVALID_REPO','file'=>__FILE__,'line'=>__LINE__];
			}
			if (!preg_match('!On branch (?<branch>[^\n]+)!',$data)) {
				return ['errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__];
			}

			$modified = [];
			//FIXME: maybe separate deleted from modified?
			if (preg_match('!Changes not staged for commit:[ \n]*\(use[^\)]*\)[ \n]*\(use[^\)]*\)[ \n]*([\t ]*(modified|deleted):[\t ]*(?<file>[^\n]+)\n)+!',$data,$m)
			 && preg_match_all('![\t ]*modified:[\t ]*(?<file>[^\n]+)\n!',$m[0],$m)) {
				$modified = $m['file'];
			}

			$untracked = [];
			if (preg_match('!Untracked files:[ \n]*\(use[^\)]*\)[ \n]*(?<body>([\t ]*(?<file>[^\n]+)\n)+)!',$data,$m)
			 && preg_match_all('![\t ]*(?<file>[^\n]+)\n!',$m['body'],$m)) {
				$untracked = $m['file'];
			}

			return [
				 'modified'=>$modified
				,'untracked'=>$untracked
			];
		}
		function _pull($remote = '',$branch = ''){
			$data = shell_exec('cd "'.$this->path.'" && git pull '.$remote.' '.$branch.' 2>&1');
print_r($data);
exit;
		}
	}

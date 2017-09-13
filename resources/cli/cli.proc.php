<?php
	include_once('cli.php');
	if(isset($argv[1])
		&& ($lib = str_replace('.','_',substr(basename($argv[0]),4,-3)))
		&& ($function = 'cli_'.$lib.str_replace('.','_',$argv[1]))
		&& function_exists($function)){

		array_shift($argv);
		array_shift($argv);
		call_user_func_array($function,$argv);
	}

	function cli_proc_daemon(){
		$_proc = new _proc();
		$_proc->daemon();
	}

	function cli_proc_worker($worker = '',$id = false){
		if( substr($worker,-4) == '.php' ){$worker = substr($worker,0,-4);}
		$worker = '../cli/workers/'.$worker.'.php';
		if( !file_exists($worker) ){return false;}
		include_once($worker);
		if( !class_exists('worker') ){return false;}
		$worker = new worker($id);
		$worker->start();

		pcntl_signal(SIGTERM,function($signo) use (&$worker){
			unset($worker);
		});
	}

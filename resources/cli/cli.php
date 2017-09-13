<?php
	$currentdir = dirname(__FILE__);
	chdir($currentdir);
	chdir('../libs/');
	$GLOBALS['w.cli'] = [];
	$GLOBALS['w.indexURL'] = '';
	include_once('../init.php');
	
	/*for($x=1;$x<=100;$x++){cli_pbar($x,100);usleep(100000);}*/
	function cli_pbar($done,$total,$size=30){
		static $startTime;
		/* Si superamos los límites, algo ha ido mal */
		if($done > $total){return false;}
		if(!$startTime){$startTime = time();}
		$now = time();
		$perc = floatval($done/$total);
		$bar = floor($perc*$size);
		$status_bar = "\r[";
		$status_bar .= str_repeat('=',$bar);
		if($bar<$size){$status_bar .= '>'.str_repeat(' ',$size-$bar);}
		else{$status_bar .= '=';}
		$disp = number_format($perc*100,0);
		$status_bar .= '] '.$disp.'% '.$done.'/'.$total;
		$rate = ($done) ? ($now-$startTime)/$done : 0;
		$left = $total-$done;
		$eta = round($rate*$left,2);
		$elapsed = $now-$startTime;
		$status_bar.= ' remain: '.number_format($eta).' sec. elap: '.number_format($elapsed).' sec.';
		echo $status_bar.' ';flush();
		/* Cuando terminamos, pintamos una nueva line y reseteamos el tiempo */
		if($done == $total){$startTime = false;echo PHP_EOL;}
	}

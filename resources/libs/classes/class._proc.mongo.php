<?php
	/* INI-mongo tables */
	$GLOBALS['api']['mongo']['tables']['sys.process'] = [
		 '_id'=>'INTEGER AUTOINCREMENT'
		,'pid'=>'INTEGER DEFAULT 0'
		,'procLock'=>'TEXT'
		,'procLockMode'=>'TEXT' // shared | exclusive
		,'procStatus'=>'TEXT'

		,'procWorker'=>'TEXT'
		,'procFile'=>'TEXT'
		,'procModule'=>'TEXT'
		,'procCall'=>'TEXT'
		,'procParams'=>'TEXT'

		,'procDependency'=>'TEXT'
		,'procUser'=>'INTEGER'
		,'procStamp'=>'TEXT'
		,'procDate'=>'TEXT'
		,'procTime'=>'TEXT'
		,'procEndStamp'=>'TEXT'
		,'procEndDate'=>'TEXT'
		,'procEndTime'=>'TEXT'
		,'procTimeLimit'=>'TEXT'
		,'procProgress'=>'TEXT'
		,'procCurrent'=>'TEXT'
		,'procScheduled'=>'TEXT'
		,'procMsg'=>'TEXT'
		,'procMsgLines'=>'TEXT'
	];
	/* END-mongo tables */
	/* INI-mongo indexes */
	$GLOBALS['api']['mongo']['indexes']['sys.process'] = [
		 ['fields'=>['procLock'=>1]]
		,['fields'=>['pid'=>1]]
		,['fields'=>['procStatus'=>1],'props'=>['background'=>true]]
	];
	/* END-mongo indexes */

	class _proc extends _mongo{
		public $table = 'sys.process';
		function validate(&$data = [],&$oldData = []){
			if( !isset($data['procStatus']) ){$data['procStatus'] = ( isset($data['pid']) && proc_os_running($data['pid']) ) ? 'running' : 'awaiting';}
			if( !isset($data['procLockMode']) ){$data['procLockMode'] = 'shared';}
			if( !isset($data['procProgress']) ){$data['procProgress'] = 0;}
			if( $data['procStatus'] == 'scheduled' && !isset($data['procScheduled']['minutes'],$data['procScheduled']['hours']) ){return ['errorDescription'=>'NO_SCHEDULE','file'=>__FILE__,'line'=>__LINE__];}
			if( !in_array($data['procStatus'],['scheduled','scheduled.disabled']) &&  isset($data['procScheduled']) ){unset($data['procScheduled']);}

			/* Si la tarea está pendiente no puede tomar pID aún */
			if( $data['procStatus'] == 'awaiting' && isset($data['pid']) ){unset($data['pid']);}
			if( $data['procStatus'] == 'awaiting' && $data['procLockMode'] == 'scheduled' ){$data['procLockMode'] = 'exclusive';}
			if( $data['procStatus'] == 'running' ){
				$this->cleanup();
				$data['pid'] = getmypid();
				if( ($oldProcess = $this->getSingle(['pid'=>$data['pid']])) ){
					//FIXME:
					//$r = $this->finished($proc);
				}
				if( !isset($data['procStamp']) ){
					$data['procStamp'] = time();
					$data['procDate']  = date('Y-m-d',$data['procStamp']);
					$data['procTime']  = date('H:i:s',$data['procStamp']);
				}
			}
			if( $data['procStatus'] == 'finished' ){
				if( !isset($data['procEndStamp']) ){
					$data['procEndStamp'] = time();
					$data['procEndDate']  = date('Y-m-d',$data['procEndStamp']);
					$data['procEndTime']  = date('H:i:s',$data['procEndStamp']);
				}
			}
			if( $data['procStatus'] == 'scheduled' ){
				$data['procStatus'] = 'scheduled';
				/* procScheduled -> minutes = range(0,59), hours = range(0,23) */
				$next = $this->schedule($data['procScheduled']);
				$data['procScheduled']['next'] = $next;
			}

			if( isset($data['procWorker']) && substr($data['procWorker'],-4) == '.php' ){$data['procWorker'] = substr($data['procWorker'],0,-4);}
			if( isset($data['procParams']) && is_array($data['procParams']) ){$data['procParams'] = $data['procParams'];}
			return $data;
		}
		function cleanup(){
			$procOBs = $this->getWhere(['procStatus'=>'running']);
			foreach( $procOBs as $procOB ){
				if( $procOB['pid'] && !proc_os_running($procOB['pid']) ){
					$this->finished($procOB);
				}
			}
		}
		function running(&$proc = []){
			$proc['procStatus'] = 'running';
			return $this->save($proc);
		}
		function finished(&$proc = []){
			$proc['procStatus'] = 'finished';
			return $this->save($proc);
		}
		function schedule($sch = [],$time = false,$debug = false){
			if( !$time ){$time = time();}
			if( isset($sch['procScheduled']) ){$sch = $sch['procScheduled'];}
			$time = strtotime('+1 minute',$time);
			$hour = $minute = $day = $month = $year = 0;
			$gtime = function($time) use (&$hour,&$minute,&$day,&$month,&$year){
				$hour   = date('G',$time);
				$minute = intval(date('i',$time));
				$day    = intval(date('j',$time));
				$month  = intval(date('n',$time));
				$year   = intval(date('Y',$time));
			};
			$gtime($time);

			//$sch = ['minutes'=>[37],'hours'=>[1,2]];
			if( !isset($sch['hours'],$sch['minutes']) ){return false;}
			if( !is_array($sch['hours']) || !is_array($sch['minutes']) ){return false;}

			$nextHour   = false;
			$nextMinute = false;
			$collition  = false;
			$secure = 10;
			do{
				$stop = true;

				/* INI-Buscamos la hora */
				$hours = $sch['hours'];
				foreach( $hours as $k=>$h ){if( $h < $hour ){unset($hours[$k]);}}
				if( !$hours ){
					/* Si no hay horas, nos vamos hasta las 00:00 del día siguiente
					 * y volvemos a intentar */
					$time = strtotime(date('Y-m-d',strtotime('+1 day',$time)));
					$gtime($time);
					$stop = false;
					continue;
				}
				$nextHour = reset($hours);
				/* END-Buscamos la hora */

				/* INI-Buscamos los minutos */
				$minutes = $sch['minutes'];
				foreach( $minutes as $k=>$m ){if( $m < $minute ){unset($minutes[$k]);}}
				if( !$minutes ){
					/* Si no hay horas, nos vamos hasta las 00:00 del día siguiente
					 * y volvemos a intentar */
					$time = strtotime(date('Y-m-d H:00:00',strtotime('+1 hour',$time)));
					$gtime($time);
					$stop = false;
					continue;
				}
				$nextMinute = reset($minutes);
				/* END-Buscamos los minutos */

				$dateString = $year.'-'
					.str_pad($month,2,'0',STR_PAD_LEFT).'-'
					.str_pad($day,2,'0',STR_PAD_LEFT).' '
					.str_pad($nextHour,2,'0',STR_PAD_LEFT).':'
					.str_pad($nextMinute,2,'0',STR_PAD_LEFT).':'
					.'00';
				
			}while( !$stop && ($secure-- > 0) );

			if( $nextHour === false || $nextMinute === false ){return false;}
			$nextTime = strtotime($dateString);
			if( $debug ){var_dump($dateString);exit;}
			return $nextTime;
		}
		function daemon( $debug = false ){
			/* INI-PROC-Registramos el proceso */
			$this->cleanup();
//FIXME: esto hay que hacerlo en "casa"
			if( !$debug ){proc_lock();}
			/* END-PROC */

			$tasks = [];
			$task = false;
			while(1){
				$time = time();
				$scheduledOBs = $this->getWhere(['procStatus'=>'scheduled','procScheduled.next'=>['$lt'=>$time]]);
				foreach( $scheduledOBs as $procOB ){
					/* Locking - evitamos que se lancen 2 procesos incompatibles */
					if( $procOB['procLockMode'] == 'exclusive' && $this->getSingle(['procStatus'=>['$in'=>['awaiting','running']],'procLock'=>$procOB['procLock']]) ){continue;}

					$procCopy = ['procStatus'=>'awaiting']+$procOB;
					unset($procCopy['_id']);

					$next = $this->schedule($procOB['procScheduled']);
					$procOB['procScheduled']['next'] = $next;
					$this->save($procOB);

					$this->save($procCopy);
				}


				$procOBs = $this->getWhere(['procStatus'=>'awaiting']);
				foreach( $procOBs as $procOB ){
					$id = strval($procOB['_id']);
					$tasks[$id] = new _task($procOB);
					$tasks[$id]->start();
					sleep(1);
					echo $tasks[$id]->listen();
				}
				foreach( $tasks as $k=>$task ){
					if( !$task->isRunning() ){
						unset($tasks[$k]);
						continue;
					}
					echo $task->listen();
				}
				sleep(20);
				$this->cleanup();
			}
		}
		function paintScheduledSchema($params = []){
			$colors = [
				 '#f4584b','#1fc36a','#14b9fb','#9d86d7'
				,'#71ffba','#66b2a8','#351559','#0df0fe'
				,'#dbb9fc','#5d9624','#fffa96','#ab7992','#4d6d9e','#ffa936'
				,'#592140','#ff6619','#f24885','#900093','#3e7e4f','#f78e77'
				,'#a89afc','#a60000','#dc80c4'
			];
			$colorIndex = 0;
			$top = 0;
			$params += [
				 'lapse'=>24
				,'width'=>24*60
				,'cell.height'=>20
				,'cell.width'=>60
				,'legend.width'=>100
			];

			$workerOBs = $this->getWhere(['procStatus'=>'scheduled']);

			/* INI-Leyenda */
			$legend  = '<g class="fragment legend">'.PHP_EOL;
			/* END-Leyenda */

			$startTime = strtotime('-1 minute',strtotime(date('Y-m-d')));
			$today     = date('Y-m-d');

			$svg  = '<svg xmlns="http://www.w3.org/2000/svg" width="'.($params['legend.width']+$params['width']+1).'" height="{%height%}">'.PHP_EOL;
			$left = $params['legend.width'];
			foreach( range(0,24) as $hour ){
				$svg  .= '<rect x="'.$left.'" y="0" width="1" height="100%" style="fill:#eee"></rect>'.PHP_EOL;
				$left += $params['cell.width'];
			}
			foreach( $workerOBs as $workerOB ){
				$svg .= '<rect x="0" y="'.($top).'" width="100%" height="1" style="fill:#eee"></rect>'.PHP_EOL;
				//$svg .= '<rect x="0" y="'.($top).'" width="100%" height="18" style="fill:#fff"></rect>'.PHP_EOL;
				$svg .= '<text x="10" y="'.($top+3).'" height="'.$params['cell.height'].'" style="fill:#555;font-size:10;font-family:arial;dominant-baseline:hanging;">'.$workerOB['procWorker'].'</text>'.PHP_EOL;

				$launchs = [];
				$time = $startTime;
				do{
					$time = $this->schedule($workerOB,$time);
					$day  = date('Y-m-d',$time);
					if( $day == $today && $time && !in_array($day,$launchs) ){$launchs[] = $time;}
				}while( $day == $today );

				$left = $params['legend.width'];
				foreach( $launchs as $k=>$launch ){
					$d = date('H',$launch);
					$m = date('m',$launch);
					$x = $params['legend.width']+($d*$params['cell.width'])+$m;
					$w = 10;

					$svg .= '<g transform="translate(0,'.$top.')">'.PHP_EOL;
					$svg .= '<title>'.date('H:i:s',$launch).'</title>'.PHP_EOL;
					$svg .= '<rect x="'.($x).'" y="1" width="'.$w.'" height="'.($params['cell.height']).'" style="fill:'.$colors[$colorIndex].'"></rect>'.PHP_EOL;
					$svg .= '<rect x="'.($x+1).'" y="2" width="'.($w-2).'" height="'.($params['cell.height']-2).'" style="fill:#fff;fill-opacity:.2;"></rect>'.PHP_EOL;
					$svg .= '</g>'.PHP_EOL;
				}
				$top += $params['cell.height'];
				$colorIndex++;
			}

			$svg .= $legend;
			$svg .= '</svg>';
			$svg = str_replace('{%height%}',$top+1,$svg);
			return $svg;
		}
	}

	class _task{
		public $work    = false;
		public $command = false;
		public $output  = '';
		public $res     = false;
		public $pipes   = [];
		function __construct($work = []){
			$bin_php = 'php';
			if( file_exists('/usr/bin/php5.6') ){$bin_php = '/usr/bin/php5.6';}

			$this->work = $work;
			if( isset($this->work['procWorker']) && $this->work['procWorker'] ){
				$this->command = $bin_php.' ../cli/cli.proc.php worker '.$this->work['procWorker'].' '.$this->work['_id'];
			}
		}
		function start(){
			$descriptor = [
				 ['pipe','r']
				,['pipe','w']
				,['pipe','w']
			];
			/* Open the resource to execute $command */
			$this->res = proc_open($this->command,$descriptor,$this->pipes);
			/* Set STDOUT and STDERR to non-blocking */
			stream_set_blocking($this->pipes[1],0);
			stream_set_blocking($this->pipes[2],0);
			return $this->res; 
		}
		function isRunning(){
			$info = proc_get_status($this->res);
			return $info['running'];
		}
		function listen(){
			
			while( $r = stream_get_contents($this->pipes[1]) ){$this->output .= $r;}
			return $this->output;
		}
		//FIXME: faltan muchas cosas
	}

	class _worker{
		public $work    = false;
		public $proc    = false;
		public $lock    = false;
		public $vervose = false;
		public $params  = [];
		public $include = [];
		function __construct($hash = ''){
			$this->proc   = new _proc();
			$this->work   = $this->proc->getByID($hash);
			$this->params = $this->work['procParams'];
			foreach( $this->include as $file ){
				if( file_exists($file) ){include_once($file);}
			}
			if( trim(shell_exec('whoami')) == 'root' ){$this->vervose = true;}
		}
		function __destruct(){
			$this->clean();
		}
		function task(){

		}
		function clean(){

		}
		function start(){
			if( !$this->work ){return false;}

			$this->work['procMsgLines'] = [];
			$this->proc->save($this->work);

			$this->proc->running($this->work);
			$this->update(0);
			$this->task();
			$this->update(100);
			$this->proc->finished($this->work);
		}
		function update($current,$total = false){
			$perc = round($current,2);
			if( $total ){
				$perc = floatval($current/$total);
				$perc = round($perc*100,2);
				$this->work['procCurrent'] = $current;
			}
			$this->work['procProgress'] = $perc;
			$this->proc->save($this->work);
		}
		function out($str = '',$timestamp = false){
			if( !isset($this->work['procMsg']) ){$this->work['procMsg'] = '';}
			if( $timestamp ){$str = '['.date('Y-m-d H:i:s').'] '.$str;}
			$this->work['procMsg'] = $str;
			$this->proc->save($this->work);
		}
		function outln($str = '',$timestamp = true){
			if( !isset($this->work['procMsgLines']) ){$this->work['procMsgLines'] = [];}
			if( $timestamp ){$str = '['.date('Y-m-d H:i:s').'] '.$str;}
			if( $this->verbose ){echo $str;}
			$this->work['procMsgLines'][] = $str;
			$this->work['procMsgLines'] = array_slice($this->work['procMsgLines'],0,500);
			$r = $this->proc->save($this->work);
			if( isset($r['errorDescription']) ){return $r;}
		}
		function error($error = []){
			if( !isset($this->work['procError']) ){$this->work['procError'] = [];}
			$this->work['procError'] = $error;
			$this->work['procStatus'] = 'error';
			$this->proc->save($this->work);
		}
	}

	class _proc_utils{
		function ps_parse( $blob = '',$params = [] ){
			/* $params = [
			 * 	'user'=>'filtro de usuario'
			 * ]; */
			$lines  = explode(PHP_EOL,$blob);

			/* INI-Generamos la regex */
			$header = array_shift($lines);
			preg_match_all('![ ]*[^ ]+!',$header,$fields);
			$user  = array_shift($fields[0]);
			$pid   = array_shift($fields[0]);
			$end   = array_pop($fields[0]);
			$regex = '!^(?<'.trim(strtolower($user)).'>[^ ]+)(?<'.trim(strtolower($pid)).'>[ ]*[0-9]+)';
			foreach( $fields[0] as $field ){
				$name = str_replace('%','',trim(strtolower($field)));
				$regex .= '(?<'.$name.'>.{'.strlen($field).'})';
			}
			$regex .= '(?<'.trim(strtolower($end)).'>.*)$!';
			/* END-Generamos la regex */

			$rows = [];
			foreach( $lines as $line ){
				if( !preg_match($regex,$line,$data) ){continue;}
				foreach( $data as $k=>$v ){
					if( preg_match('!^[0-9]+!',$k) ){unset($data[$k]);continue;}
					$data[$k] = trim($v);
				}
				if( isset($params['user']) && $params['user'] != $data['user'] ){continue;}
				$rows[$data['pid']] = $data;
			}
			return $rows;
		}
	}

	$r = register_shutdown_function('proc_on_shutdown');

	function proc_lock(){
		$proc = new _proc();
		$proc->cleanup();
		$e = new Exception();
		$trace = $e->getTrace();
		//FIXME: si no existe la posicion 'file' vamos hasta 2 o 3 si hace falta
		$function = ( isset($trace[1]['class']) ? $trace[1]['class'].'.' : '' ).$trace[1]['function'];
		if( $proc->getSingle(['procLock'=>$function,'procLockMode'=>'exclusive','procStatus'=>'running']) ){echo 'locked';exit;}
		$process = [
			 'procLock'=>$function
			,'procLockMode'=>'exclusive'
			,'procModule'=>''
			,'procCall'=>$function
			,'procParams'=>[]
			,'procStatus'=>'running'
		];
		return $proc->save($process);
	}
	function proc_os_running($pid = ''){
		return file_exists('/proc/'.$pid);
	}
	function proc_on_shutdown(){
		chdir(dirname(__FILE__));
		$proc = new _proc();
		$pid  = getmypid();
		if( ($procOBs = $proc->getWhere(['pid'=>$pid])) ){foreach( $procOBs as $procOB ){
			$r = $proc->finished($procOB);
		}}
		return true;
	}

	class proctime{
		public $time = '';
		public function __construct(){
			$this->time = time();
		}
		public function isBetween($down = '',$up = ''){
			$strtotime = function($str){
				if( preg_match('/[0-9]{2}:[0-9]{2}/',$str) ){return strtotime(date('Y-m-d ').$str);}
			};

			if( $this->time > $strtotime($down) && $this->time < $strtotime($up) ){return true;}
			return false;
		}
	}


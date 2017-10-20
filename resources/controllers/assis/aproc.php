<?php
	function _aproc_main(){
		global $TEMPLATE;

		$TEMPLATE['url.aproc.main'] = presentation_assis_aproc_main();
		$TEMPLATE['url.aproc.list'] = presentation_assis_aproc_list();
	}

	function aproc_main(){
		global $TEMPLATE;
		$_proc = new _proc();
		$workerFolder = '../cli/workers/';
		$cliPath   = realPath(getcwd().'/'.$workerFolder).'/';

		if( isset($_POST['subcommand']) ){switch($_POST['subcommand']){
			case 'worker.schedule.launch':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] != 'scheduled' && $workerOB['procStatus'] != 'scheduled.disabled' ){common_r();}
				$workerCopy = $workerOB;
				unset($workerCopy['_id']);
				$workerCopy['procStatus'] = 'awaiting';
				$r = $_proc->save($workerCopy);
				if( isset($workerCopy['errorDescription']) ){print_r($workerCopy);exit;}
				common_r();
			case 'worker.schedule.launch.fix':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] != 'scheduled' ){common_r();}
				$workerOB['procScheduled']['next'] = $_proc->schedule($workerOB);
				$r = $_proc->save($workerOB);
				if( isset($workerCopy['errorDescription']) ){print_r($workerCopy);exit;}
				common_r();
			case 'worker.schedule.disable':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] != 'scheduled' ){common_r();}
				$workerOB['procStatus'] = 'scheduled.disabled';
				$r = $_proc->save($workerOB);
				common_r();
			case 'worker.schedule.enable':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] != 'scheduled.disabled' ){common_r();}
				$workerOB['procStatus'] = 'scheduled';
				$r = $_proc->save($workerOB);
				common_r();
			case 'worker.launch':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] != 'test' ){common_r();}
				$workerOB['procStatus'] = 'awaiting';
				$r = $_proc->save($workerOB);
				common_r();
			case 'worker.save':
				$_POST['worker'] = str_replace('/','',$_POST['worker']);
				if( !file_exists($workerFolder.$_POST['worker']) ){common_r();}
				if( isset($_POST['status']) && !in_array($_POST['status'],['test','scheduled','awaiting']) ){$_POST['status'] = 'test';}
				if( isset($_POST['lockMode'])   && !in_array($_POST['lockMode'],['shared','exclusive']) ){$_POST['lockMode'] = 'shared';}
				if( isset($_POST['params']) && !($_POST['params'] = json_decode($_POST['params'],1)) ){$_POST['params'] = [];}

				if( !isset($_POST['minutes']) ){$_POST['minutes'] = '*';}
				if( !isset($_POST['hours']) ){$_POST['hours'] = '*';}
				if( !isset($_POST['params']) ){$_POST['params'] = '';}

				if( $_POST['minutes'] === '*' ){$_POST['minutes'] = range(0,59);}
				elseif( is_numeric($_POST['minutes']) ){$_POST['minutes'] = [$_POST['minutes']];}
				else{$_POST['minutes'] = range(0,59,substr($_POST['minutes'],strpos($_POST['minutes'],'/') + 1));}

				if( $_POST['hours']   === '*' ){$_POST['hours'] = range(0,23);}
				elseif( is_numeric($_POST['hours']) ){$_POST['hours'] = [$_POST['hours']];}
				else{$_POST['hours'] = range(0,23,substr($_POST['hours'],strpos($_POST['hours'],'/') + 1));}

				$_POST['worker'] = preg_replace('!\.php$!','',$_POST['worker']);
				if( isset($_POST['params']) && is_string($_POST['params']) && isset($_POST['params'][0]) && $_POST['params'][0] == '{' ){
					$_POST['params'] = json_decode($_POST['params'],true);
				}

				$workerOB = [
					 'procStatus'=>$_POST['status']
					,'procLock'=>$_POST['worker']
					,'procLockMode'=>$_POST['lockMode']
					,'procWorker'=>$_POST['worker']
					,'procParams'=>$_POST['params']
					,'procScheduled'=>[
						 'minutes'=>$_POST['minutes']
						,'hours'=>$_POST['hours']
						,'next'=>false
					]
				];
				$r = $_proc->save($workerOB);
				if( isset($r['errorDescription']) ){print_r($r);exit;}
				common_r();
			case 'worker.remove':
				if( !isset($_POST['_id']) ){common_r();}
				$_proc->removeByID($_POST['_id']);
				common_r();
		}}

		$workers = glob($workerFolder.'*');
		foreach( $workers as &$worker ){$worker = ['name'=>basename($worker)];}
		unset($worker);

		$workerOBs = $_proc->getWhere(['procStatus'=>['$in'=>['awaiting','test','scheduled','scheduled.disabled']]]);
		$daemon    = $_proc->getSingle(['procLock'=>'_proc.daemon','procStatus'=>'running']);
		foreach( $workerOBs as &$workerOB ){
			$workerOB['is.'.$workerOB['procStatus']] = true;
			$workerOB['html.params'] = json_encode($workerOB['procParams']);
			if( $workerOB['procStatus'] == 'scheduled' ){
				$workerOB['html.next.launch'] = date('Y-m-d H:i:s',$workerOB['procScheduled']['next']);
			}
		}
		unset($workerOB);

		$TEMPLATE['workerOBs'] = $workerOBs;
		$TEMPLATE['workers'] = $workers;
		$TEMPLATE['daemon'] = $daemon;
		_aproc_main();
		$TEMPLATE['tab.main'] = true;
		$TEMPLATE['PAGE.TITLE'] = '[C] Tareas del servidor';
		return common_renderTemplate('aproc/main');
	}

	function aproc_list(){
		global $TEMPLATE;
		$_proc = new _proc();

		if( isset($_POST['subcommand']) ){switch( $_POST['subcommand'] ){
			case 'cron.kill':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] != 'running' ){common_r();}
				$sigterm = 15;
				$sigkill = 9;

				posix_kill($workerOB['pid'],$sigterm);

				sleep(2);
				if( proc_os_running($workerOB['pid']) ){
					$r = shell_exec('pkill -TERM -P '.$workerOB['pid']);
					posix_kill($workerOB['pid'],$sigkill);
				}
echo 'the process is running: '.proc_os_running($workerOB['pid']).PHP_EOL;
exit;
				common_r();
			case 'worker.remove':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] == 'running' ){common_r();}
				$r = $_proc->removeByID($_POST['_id']);
				if( isset($r['errorDescription']) ){print_r($r);exit;}
				common_r();
			case 'worker.launch':
				if( !isset($_POST['_id']) || !($workerOB = $_proc->getByID($_POST['_id'])) ){common_r();}
				if( !in_array($workerOB['procStatus'],['test','finished']) ){common_r();}
				$workerOB['procStatus'] = 'awaiting';
				$r = $_proc->save($workerOB);
				common_r();
			case 'cron.finished.cleanup':
				$r = $_proc->removeWhere(['procStatus'=>'finished']);
				if( isset($r['errorDescription']) ){print_r($r);exit;}
				common_r();
		}}

		$get_proc_start_datetime = function ( $pid ) {
			$target = '/proc/'.$pid.'/stat';
			if( !file_exists($target) ){return false;}
			$data = file_get_contents($target);
			$data = explode(' ',$data);

			$seed = explode(' ',file_get_contents('/proc/uptime'));
			$time = (int)round($data[21] / 100, 0, PHP_ROUND_HALF_UP);
			$diff = time() - ($seed[0] - $time);
			return date('Y-m-d H:i:s', $diff);
		};

		$_proc->cleanup();
		$plan = [];
		$plan[] = ['$match'=>['procStatus'=>['$in'=>['awaiting','running','finished']]]];
		$plan[] = ['$group'=>['_id'=>['procWorker'=>'$procWorker','procStatus'=>'$procStatus'],'last'=>['$last'=>'$_id'],'count'=>['$sum'=>1]]];
		$result = $_proc->aggregate($plan);
		if( !isset($result['result']) ){echo 'unknown error';exit;}
		$procIDs = array_map(function($n){return $n['last'];},$result['result']);
		/* INI-Indexing count processes */
		$count_indexed = [];
		foreach( $result['result'] as $item ){
			$count_indexed[implode('.',$item['_id'])] = $item['count'];
		}
		/* END-Indexing count processes */

		$procOBs = $_proc->getByIDs($procIDs);
		foreach( $procOBs as &$procOB ){
			$procOB['is.'.$procOB['procStatus']] = true;
			if( isset($procOB['procProgress']) && $procOB['procProgress'] > 100 ){$procOB['procProgress'] = 100;}
			if( !empty($procOB['procParams']) && is_array($procOB['procParams']) ){$procOB['procParams'] = json_encode($procOB['procParams']);}
			if( isset($procOB['pid']) ){$procOB['html.procDatetime'] = $get_proc_start_datetime($procOB['pid']);}

			if( !empty($procOB['procWorker']) ){
				$count_index = $procOB['procWorker'].'.'.$procOB['procStatus'];
				if( !empty($count_indexed[$count_index]) ){$procOB['count.processes'] = $count_indexed[$count_index];}
			}
		}
		unset($procOB);

		$TEMPLATE['procOBs'] = $procOBs;
		_aproc_main();
		$TEMPLATE['tab.list'] = true;
		$TEMPLATE['PAGE.TITLE'] = '[C] Tasks List';
		return common_renderTemplate('aproc/list');
	}

	function _aproc_status(){
		global $TEMPLATE;
		include_once('inc.cron.php');
		$proc = new proc();

		if( isset($_POST['subcommand']) ){switch($_POST['subcommand']){
			case 'worker.kill':
				if( !isset($_POST['_id']) ){common_r();}
				if( !($workerOB = $proc->getByID($_POST['_id'])) ){common_r();}
				if( $workerOB['procStatus'] != 'running' ){common_r();}

				posix_kill($workerOB['pid'],15);
				sleep(2);
				if( proc_os_running($workerOB['pid']) ){
					posix_kill($workerOB['pid'],9);
				}
				echo 'Proceso corriendo: '.proc_os_running($workerOB['pid']);
				exit;
			case 'worker.clean':
				$proc->removeWhere(['procStatus'=>'finished']);
				common_r();
		}}

		$workerOBs = $proc->getWhere(['procStatus'=>['$ne'=>'scheduled']],['order'=>'procStatus']);
		foreach( $workerOBs as &$workerOB ){
			$workerOB['is.'.$workerOB['procStatus']] = true;
			if( isset($workerOB['procMsg']) ){$workerOB['procMsg'] = str_replace(['<','>',PHP_EOL],['&lt;','&gt;','<br>'],$workerOB['procMsg']);}
		}
		unset($workerOB);

		$TEMPLATE['workerOBs'] = $workerOBs;
		$TEMPLATE['url.cron.main'] = presentation_cron_main();
		$TEMPLATE['url.cron.status'] = presentation_cron_status();
		$TEMPLATE['tab.status'] = true;
		$TEMPLATE['PAGE.TITLE'] = '[C] Tareas del servidor';
		return common_renderTemplate('aproc/status');
	}

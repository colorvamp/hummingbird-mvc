<?php
	function blob_get($id = '',$size = 'orig',$mime = 'jpeg',$params = []){
		include_once('inc.path.php');
		$_valid_mime      = ['jpeg'=>0,'png'=>0,'gif'=>0];
		$_default_quality = ['jpeg'=>90,'png'=>90,'gif'=>false];
		if( !isset($_valid_mime[$mime]) ){return false;}

		$prefix  = substr($id,0,4);
		$folder  = path_get(':images',$prefix,$id);
		$quality = isset($params['quality']) ? $params['quality'] : $_default_quality[$mime];

		//if( $size == 'orig' ){return $folder.'orig';}
		$path    = $folder.$size.'.'.intval($quality).'.'.$mime;

		if( !file_exists($path) ){
			include_once('inc.images.php');
			$files = glob($folder.'orig.*');
			//FIXME: decidir prioridades mejor
			$orig  = current($files);
			$parts = explode('.',$orig);
			if( ($parts[4] != 'gif') || !image_gif_is_animated($orig) ){
				$res   = image_mimeDecider('image/'.end($parts),$orig);
				$res   = image_resource_resize($res,$size);
				if( $res === false ){return ['errorDescription'=>'INVALID_SIZE','file'=>__FILE__,'line'=>__LINE__];}

				$q = $quality;
				if( $mime == 'png' ){$q = intval($q/10);}
				$funcSave = 'image'.$mime;
				$funcSave($res,$path,$q);
				if( !file_exists($path) ){return ['errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__];}
				chmod($path,0777);
			}else{
				$tmpPath = '/run/shm/'.uniqid().'/';/* la ram */
				if( !file_exists($tmpPath) ){
					$oldmask = umask(0);
					$r = mkdir($tmpPath,0777,1);
					umask($oldmask);
					if(!$r){echo $r;}
				}
				shell_exec('convert -coalesce '.$orig.' '.$tmpPath.'%03d.gif');
				$delay = shell_exec('identify -format "%T\n" '.$orig);
				$delay = explode(PHP_EOL,$delay);
				$delay = array_diff($delay,['']);
				$files = glob($tmpPath.'*');
				$cmd = 'convert ';
				foreach($files as $file){
					if($file == $orig){continue;}
					$res = image_getResource($file);
					$res2 = image_resource_resize($res,$size);
					imageResource_save($res2,$file);
					$cmd .= ' -delay '.current($delay).' '.$file.' ';
					next($delay);
				}
				shell_exec($cmd.' -loop 0 -layers Optimize '.$path);
				chmod($path,0777);
				/* INI-cleanup */
				foreach($files as $file){unlink($file);}
				rmdir($tmpPath);
				/* END-cleanup */
			}
		}
		return $path;
	}
	function blob_store($id = '',$path = ''){
			include_once('inc.path.php');
			if( !($imgProp = @getimagesize($path)) ){return false;}
			$prefix  = substr($id,0,4);
			$folder  = path_get(':images',$prefix,$id);
			$quality = '100';
			$hash    = md5_file($path);
			$mime    = substr($imgProp['mime'],6);
			$target = $folder.'orig.'.$quality.'.'.$mime;
			$r = copy($path,$target);
			chmod($target,0777);
			return true;
	}


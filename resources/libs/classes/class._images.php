<?php
	trait __images{
		function image_mimeDecider($mime,$path){
			if( !function_exists('imagecreatefromjpeg') ){echo 'No está instalada la libreria php5_gd';exit;}
			switch($mime){
		    		case 'image/gif':if(!($image = @imagecreatefromgif($path))){return false;}; break;
				case 'image/jpeg':if(!($image = @imagecreatefromjpeg($path))){return false;}; break;
				case 'image/png':if(!($image = @imagecreatefrompng($path))){return false;}; break;
				default: return false;
			}
			$white = imagecolorallocate($image,255,255,255);
			return $image;
		}
		function image_resource_resize($res,$size = false){
			if( !is_numeric($size[0]) ){return false;}
			if( strpos($size,'x') !== false ){return $this->image_resource_scale($res,$size);}
			return $this->image_resource_square($res,$size);
		}
		function image_resource_square($res,$size = false){
			if(!$size){return false;}
			$res = $this->op_resize($res,$size,$size,'min');
			$res = $this->op_crop($res,$size,$size);
			return $res;
		}
		function image_resource_scale($res,$size){
			if(!$size){return false;}
			list($w,$h) = explode('x',$size);
			$res = $this->op_resize($res,$w,$h,'min');
			if($w != 0 && $h != 0){$res = $this->op_crop($res,$w,$h);}
			return $res;
		}

		function op_resize($res,$maxWidth = 0,$maxHeight = 0,$adjust = 'max'){
			$imgWidth = imagesx($res);
			$imgHeight = imagesy($res);
			if($imgWidth === false || $imgHeight === false){return false;}

			$imgRatio = $imgWidth/$imgHeight;
			if($maxWidth != 0 && $maxHeight != 0){$maxRatio = $maxWidth/$maxHeight;}

			switch(true){
				case ($maxWidth == 0):$maxWidth = $imgWidth * ($maxHeight/$imgHeight);break;
				case ($maxHeight == 0):$maxHeight = $imgHeight * ($maxWidth/$imgWidth);break;
				case ($adjust == 'max'):if($imgRatio>$maxRatio){$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}else{$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}break;
				case ($adjust == 'min'):if($imgRatio>$maxRatio){$maxWidth = $imgWidth * ($maxHeight/$imgHeight);}else{$maxHeight = $imgHeight * ($maxWidth/$imgWidth);}break;
				default:return false;
			}

			$new = imagecreatetruecolor($maxWidth,$maxHeight);
			imagealphablending($new,false);
			imagesavealpha($new,true);
			imagecopyresampled($new,$res,0,0,0,0,$maxWidth,$maxHeight,$imgWidth,$imgHeight);
			return $new;
		}
		function op_scale($res,$w,$h){
			$imgW = imagesx($res);$imgH = imagesy($res);
			$new = imagecreatetruecolor($w,$h);
			imagecopyresampled($new,$res,0,0,0,0,$w,$h,$imgW,$imgH);
			return $new;
		}

		function op_crop($res,$width,$height){
			$imgWidth = imagesx($res);
			$imgHeight = imagesy($res);

			$xini=floor(($imgWidth-$width)/2);
			$yini=floor(($imgHeight-$height)/2);

			$image = imagecreatetruecolor($width,$height);
			imagealphablending($image,false);
			imagesavealpha($image,true);
			imagecopy($image,$res,0,0,$xini,$yini,$width,$height);
			return $image;
		}
	}

	class _images_storage{
		use __images;
		public $storage = '../db/static.images/';
		function __construct(){

		}
		function validate(&$data = [],&$oldData = []){return $data;}
		function save(&$data = [],$params = []){return $this->_save($data,$params);}
		function _save(&$data = [],$params = []){
			if( empty($data['_id']) ){
				$data['_id'] = md5(microtime(true).uniqid());
			}
			$path    = $this->blob_folder($data).'image.json';
			$exists  = file_exists($path);
			$locking = LOCK_EX | LOCK_NB;
			if( !empty($params['wait']) ){$locking = LOCK_EX;}

			$fp = fopen($path,'a');
			if( !flock($fp,$locking) ){return $data = ['errorDescription'=>'UNABLE_TO_LOCK','file'=>__FILE__,'line'=>__LINE__];}

			$oldData = [];
			if( !isset($params['update.disabled']) && $exists && ($test = file_get_contents($path)) ){$oldData = json_decode($test,true);}
			$data = $data + $oldData;

			$data = $this->validate($data,$oldData);
			if( isset($data['errorDescription']) ){
				flock($fp,LOCK_UN);
				return $data;
			}

			ftruncate($fp,0);
			fwrite($fp,json_encode($data));
			fflush($fp);
			flock($fp,LOCK_UN);
			return $data;
		}
//FIXME: removeByID
		function blob_folder(&$imageOB = []){
			$prefix = substr($imageOB['_id'],0,4);
			return new _path($this->storage,$prefix,$imageOB['_id']);
		}
		function blob_get(&$imageOB = [],$size = 'orig',$mime = 'jpeg',$params = []){
			$_valid_mime      = ['jpeg'=>0,'png'=>0,'gif'=>0];
			$_default_quality = ['jpeg'=>90,'png'=>90,'gif'=>false];
			if( !isset($imageOB['_id']) ){return false;}
			if( !isset($_valid_mime[$mime]) ){return false;}

			$prefix  = substr($imageOB['_id'],0,4);
			$folder  = $this->blob_folder($imageOB);
			$quality = isset($params['quality']) ? $params['quality'] : $_default_quality[$mime];

			//if( $size == 'orig' ){return $folder.'orig';}
			$path = $folder.$size.'.'.intval($quality).'.'.$mime;

			if( !file_exists($path) ){
				$files = glob($folder.'orig.*');
				//FIXME: decidir prioridades mejor
				$orig  = current($files);
				$parts_orig = explode('.',$orig);
				$parts_dest = explode('.',$path);
				$mime_orig  = end($parts_orig);
				$mime_dest  = end($parts_dest);
				if( $size == 'orig' ){
					$path = $orig;
				}elseif( $mime_orig != 'gif' || $mime_dest != 'gif' || !image_gif_is_animated($orig) ){
					if( ($res = $this->image_mimeDecider('image/'.$mime_orig,$orig)) === false ){return ['errorDescription'=>'INVALID_SIZE','file'=>__FILE__,'line'=>__LINE__];}
					if( ($res = $this->image_resource_resize($res,$size)) === false ){return ['errorDescription'=>'INVALID_SIZE','file'=>__FILE__,'line'=>__LINE__];}

					$q = $quality;
					if( $mime_dest == 'png' ){$q = intval($q/10);}
					if( $mime_orig == 'png' && $mime_dest != 'png' ){
						$tmp   = imagecreatetruecolor(imagesx($res),imagesy($res));
						$white = imagecolorallocate($tmp,255,255,255);
						imagefill($tmp,0,0,$white);
						imagecopy($tmp,$res,0,0,0,0,imagesx($res),imagesy($res));
						imagedestroy($res);
						$res = $tmp;
					}

					$funcSave = 'image'.$mime_dest;
					$funcSave($res,$path,$q);
					if( !file_exists($path) ){return ['errorDescription'=>'UNKNOWN_ERROR','file'=>__FILE__,'line'=>__LINE__];}
					chmod($path,0777);
				}else{
echo 11;exit;
					if( !class_exists('_gif_frame_extractor') ){include_once('classes/class._gif_frame_extractor.php');}
					$_gif_frame_extractor = new _gif_frame_extractor();
					$frames = $_gif_frame_extractor->extract($orig,false);
					foreach( $frames as $i=>&$frame ){
						$frame['image'] = image_resource_resize($frame['image'],$size);
						//imageResource_save($frame['image'],$tmpPath.$i.'.gif');
						//echo $tmpPath.$i.'.gif'.PHP_EOL;
					}

					$animGif = new AnimGif();
					$frmes = array_map(function($n){return $n['image'];},$frames);
					$dura = array_map(function($n){return $n['duration'];},$frames);
					$animGif->create($frmes, $dura);
					$gif = $animGif->get();
					file_put_contents($path,$gif);
					if( !file_exists($path) ){
						return ['errorDescription'=>'UNKNOWN_ERROR','debug'=>$cmd.' -loop 0 -layers Optimize '.$path.' 2>&1',$output,'file'=>__FILE__,'line'=>__LINE__];
					}
					chmod($path,0777);
				}

				/* INI-Registramos en base de datos el nuevo tamaño */
				$imgProp = getimagesize($path);
				$hash    = md5_file($path);
				$imageOB['imageThumbs'][$size][$quality][$mime] = [
					 'width'=>$imgProp[0]
					,'height'=>$imgProp[1]
					,'mime'=>$imgProp['mime']
					,'hash'=>$hash
				];
				$this->_save($imageOB);
				/* INI-Registramos en base de datos el nuevo tamaño */
			}
			return $path;
		}
		function blob_cleanup(&$imageOB = []){
			if( !isset($imageOB['_id']) ){return false;}
			$folder = $this->blob_folder($imageOB);
			$files  = glob($folder.'*',GLOB_NOSORT);
			foreach($files as $file){
				if( substr($file,-4) == 'json' ){continue;}
				unlink($file);
			}
			$imageOB['imageThumbs'] = [];
			rmdir($folder);
			return $this->_save($imageOB);
		}
		function blob_store(&$imageOB = [],$path = '',$params = []){
			if( !isset($imageOB['_id']) ){$this->_save($imageOB);}
			if( !($imgProp = @getimagesize($path)) ){return false;}
			$hash    = md5_file($path);
			$mime    = substr($imgProp['mime'],6);
			if( isset($imageOB['imageThumbs']) && $imageOB['imageThumbs'] ){
				/* Si la imagen ya tiene ficheros relacionados */
				if( isset($imageOB['imageThumbs']['orig'][$mime]['hash'])
				 && $imageOB['imageThumbs']['orig'][$mime]['hash'] == $hash ){
					/* Si ya está almacenada exactamente la misma imagen 
					 * retornamos como si la inserción hubiera ido correctamente */
					return true;
				}
			}
			$this->blob_cleanup($imageOB);

			/* INI-Path de inserción */
			$folder  = $this->blob_folder($imageOB);
			/* END-Path de inserción */

			$quality = '100';
			$target  = $folder.'orig.'.$quality.'.'.$mime;
			$r = copy($path,$target);
			chmod($target,0777);

			$imageOB['imageThumbs']['orig'][$quality][$mime] = [
				 'width'=>$imgProp[0]
				,'height'=>$imgProp[1]
				,'mime'=>$imgProp['mime']
				,'hash'=>$hash
			];
			$this->_save($imageOB);
			return $imageOB;
		}
		/* END-Blob */
	}

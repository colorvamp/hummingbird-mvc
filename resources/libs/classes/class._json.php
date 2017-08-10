<?php
	class _json_file{
		public $file  = false;
		public $fp = false;
		public $level_array  = 0;
		public $level_object = 0;
		public $literal  = false;
		public $pointer  = 0;
		public $current  = '';
		public $maxbytes = 100 * 1024;
		function __construct($file){
			$this->file = $file;
			$this->fp = fopen($file,'r');
		}
		function iterator($path = [],$callback = false,$params = []){return $this->_iterator($path,$callback,$params);}
		function _iterator($path = [],$callback = false,$params = []){
			/* Rewind the cursor */
			rewind($this->fp);
			$this->pointer = 0;

			/* $path takes two params, the array level and object level */
			$target_array  = $path['array'] ?? 1;
			$target_object = $path['object'] ?? 0;
			$items = 0;

			/* Progress bar support */
			$bar = function_exists('cli_pbar') && !empty($params['bar']) ? 'cli_pbar' : false;
			if( $bar ){$total = filesize($this->file);}

			while( !feof($this->fp) ){
				$chunk = fread($this->fp,6144);
				$len   = strlen($chunk);
				$cur   = 0;
				$read  = false;
				$item  = false;
				for( $i = 0; $i < $len; $i++ ){
					if( !$read && $this->level_array >= $target_array && $this->level_object >= $target_object ){$read = true;}
					if(  $read && $this->level_array < $target_array && $this->level_object < $target_object ){
						$read = false;
					}
					if(  $read ){
						$this->current .= $chunk[$i];
						$cur++;
					}
					if( $chunk[$i] == '[' ){$this->level_array++;continue;}
					if( $chunk[$i] == ']' ){$this->level_array--;$item = true;}

					if( $chunk[$i] == '{' ){$this->level_object++;continue;}
					if( $chunk[$i] == '}' ){$this->level_object--;$item = true;}

					if( $item && $this->level_array == $target_array && $this->level_object == $target_object ){
						$key = false;
						if( $this->current[0] == ',' ){
							$this->current = substr($this->current,1);
							$cur--;
						}
						if( substr($this->current,-1) == ',' ){
							$this->current = substr($this->current,0,-1);
							$cur--;
						}
						if( preg_match('!^"[^"]+":!',$this->current,$m) ){
							$key = substr($m[0],1,-2);
							$this->current = substr($this->current,strlen($m[0]));
						}
						if( !$cur ){continue;}
						if( !($this->current = json_decode($this->current,true)) ){
							return ['errorDescription'=>'DECODE_FAILED','file'=>__FILE__,'line'=>__LINE__];
						}
						$callback($this->current,$key);
						$this->current = '';
						$cur   = 0;
						$read = false;
						$items++;
					}

					$item = false;
				}
				$this->pointer += $len;
				if( $items == 0 && $this->pointer > $this->maxbytes ){
					/* if the base item is not found and maxbytes is reached
					 * then exit before memory limit */
					return ['errorDescription'=>'MAXBYTES_ERROR','file'=>__FILE__,'line'=>__LINE__];
				}

				if( $bar ){
					$bar($this->pointer,$total,$size=30);
				}
			}
		}
	}

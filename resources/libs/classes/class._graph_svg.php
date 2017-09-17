<?php
	if( !defined('PHP_TAB') ){define('PHP_TAB',"\n");}

	class _graph_svg{
		public $graph = [
			 'height'        => 0 /* false = Se calculará */
			,'width'         => false /* false = Se calculará */
			,'line.color'    => '#e3e3e3'
			,'text.color'    => '#444444'
			,'background.color' => '#ffffff'
			,'background.color.alternative' => '#f8f8f8'
			,'header.height'    => 32
			,'header.is.date'   => false
			,'header.is.month'  => false
			,'header.background.even' => false
			,'header.background.odd'  => false
			,'click.data' => false
		];
		public $defs  = [
			 /* Patrón de rayado */
			 'pattern.lined'=>'<pattern id="lined" patternUnits="userSpaceOnUse" width="4" height="4"><g style="fill:none;stroke:#666;stroke-width:1;stroke-opacity:.4;"><path d="M-1,1 l2,-2 M0,4 l4,-4 M3,5 l2,-2"/></g></pattern>'
			,'linear.gradient.grad1'=>'<radialGradient id="grad1" cx="50%" cy="50%" r="50%" fx="50%" fy="50%"><stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:.2"/><stop offset="100%" style="stop-color:rgb(255,255,255);stop-opacity:0"/></radialGradient>'
			,'linear.gradient.grad2'=>'<linearGradient id="grad2" x1="0%" y1="0%" x2="0%" y2="100%"><stop offset="0%" style="stop-color:rgb(255,255,255);stop-opacity:1"/><stop offset="100%" style="stop-color:rgb(0,0,0);stop-opacity:1"/></linearGradient>'
		];
		public $cell  = [
			 'width'         => 60
		];
		public $row    = [
			 'height'        => 20
		];
		public $legend = [
			 'width'         => 120
			,'position'      => 'left'
		];
		public $graphs = [/* Data to render */];
		public $header = [/* Header to render */];

		private $count = 0;
		private $i   = 0;
		private $top = 0;
		private $fragments = [];
		function __construct($params = false){
			if( isset($params['legend']['width']) ){$this->legend['width'] = $params['legend']['width'];}
			if( isset($params['graph.legend.width']) ){$this->legend['width'] = $params['graph.legend.width'];}
			if( isset($params['graph.legend.position']) ){$this->legend['position'] = $params['graph.legend.position'];}
			if( isset($params['graph.legend.slider.bordercolor']) ){$this->legend['slider.bordercolor'] = $params['graph.legend.slider.bordercolor'];}
			if( isset($params['graph.background.color']) ){$this->graph['background.color'] = $params['graph.background.color'];}
			if( isset($params['graph.background.color.alternative']) ){$this->graph['background.color.alternative'] = $params['graph.background.color.alternative'];}
			if( isset($params['graph.line.color']) ){$this->graph['line.color'] = $params['graph.line.color'];}
			if( isset($params['graph.text.color']) ){$this->graph['text.color'] = $params['graph.text.color'];}
			if( isset($params['header.height']) ){$this->graph['header.height'] = $params['header.height'];}
			if( isset($params['click.data']) ){$this->graph['click.data'] = $params['click.data'];}
			if( isset($params['header.background.even']) ){$this->graph['header.background.even'] = $params['header.background.even'];}
			if( isset($params['header.background.odd']) ){$this->graph['header.background.odd'] = $params['header.background.odd'];}
			if( isset($params['graph.cell.width']) ){$this->cell['width'] = $params['graph.cell.width'];}
			//if( !$this->graph['width'] && $this->cell['width'] ){}
		}
		function header($data = []){
			$this->header = $data;

			$this->graph['header.is.date']  = ( isset($this->header) && reset($this->header) && ($k = reset($this->header)) && preg_match('/[0-9]{4}\-[0-9]{2}\-[0-9]{2}/',$k) );
			$this->graph['header.is.month'] = ( isset($this->header) && reset($this->header) && ($k = reset($this->header)) && preg_match('/[0-9]{4}\-[0-9]{2}/',$k) );
		}
		function lines($data = [],$params = []){
			$row = current($data);
			if( !isset($row['v']) ){$row['v'] = $row;}
			$count = count($row['v']);
			if( !$this->count || $count > $this->count ){$this->count = $count;}
			$width = ($this->legend['width'] ? $this->legend['width'] + 1 : 0) + ($this->cell['width'] + 1) * (count($row['v']));
			if( !$this->graph['width'] || $width > $this->graph['width'] ){$this->graph['width'] = $width;}

			$this->graphs[] = new _graph_svg_config([
				 'type'=>'lines'
				,'data'=>$data
			]+$params);
		}
		function bars($data = [],$params = []){
			$row   = current($data);
			$count = count($row['v']);
			if( !$this->count || $count > $this->count ){$this->count = $count;}
			if( !$this->header && !empty($row['v']) && is_string(key($row['v'])) ){$this->header(array_keys($row['v']));}
			$width = ($this->legend['width'] ? $this->legend['width'] + 1 : 0) + ($this->cell['width'] + 1) * (count($row['v']));
			if( !$this->graph['width'] || $width > $this->graph['width'] ){$this->graph['width'] = $width;}

			$this->graphs[] = new _graph_svg_config([
				 'type'=>'bars'
				,'data'=>$data
			]+$params+$this->graph);
		}
		function pie($data = [],$params = []){
			if( !isset($params['radius']) ){$params['radius'] = 100;}

			$count = count($data);
			if( !$this->count || $count > $this->count ){$this->count = $count;}
			$width = ($this->legend['width'] ? $this->legend['width'] + 1 : 0) + ($params['radius'] * 2);
			if( !$this->graph['width'] || $width > $this->graph['width'] ){$this->graph['width'] = $width;}

			$this->graphs[] = new _graph_svg_config([
				 'type'=>'pie'
				,'data'=>$data
			]+$params);
		}
		function render(){
			$this->i = 0;
			foreach( $this->graphs as &$graph ){
				$this->colors($graph);
				if( !empty($graph->elements['header']) ){$this->render_header($graph);}
				$method = 'render_'.$graph->type;
				$this->{$method}($graph);
			}

			$svg = '<svg
				xmlns="http://www.w3.org/2000/svg"
				xmlns:data="http://www.w3.org"
				xmlns:xlink="http://www.w3.org/1999/xlink"
				width="'.($this->graph['width']).'"
				height="'.($this->graph['height']+1).'"
				data-legend-position="'.$this->legend['position'].'"
				>'.PHP_EOL;
			$svg .= PHP_TAB.'<defs>'.PHP_EOL
				.implode('',$this->defs)
				.PHP_TAB.'</defs>'.PHP_EOL;
			$svg .= PHP_TAB.'<rect width="100%" height="100%" style="fill:'.$this->graph['line.color'].';" />'.PHP_EOL;

			return $svg.implode(PHP_EOL,$this->fragments).'</svg>';
		}
		function render_lines($config = []){
			$uid = uniqid();
			$absoluteLeft = ($this->legend['position'] == 'left' ? $this->legend['width'] : 0);
			$svg = '<g class="fragment graph" transform="translate('.$absoluteLeft.','.$this->top.')" clip-path="url(#clip-'.$uid.')">'.PHP_EOL;

			/* INI-Clipping */
			$this->defs['clip.'.$uid] = PHP_TAB.PHP_TAB.'<rect id="'.$uid.'" x="0" y="0" width="'.($this->graph['width']).'" height="'.($config->graph['height']).'" fill="blue" fill-opacity="0.1" />'.PHP_EOL
				.PHP_TAB.PHP_TAB.'<clipPath id="clip-'.$uid.'"><use xlink:href="#'.$uid.'"/></clipPath>'.PHP_EOL;
			/* END-Clipping */

			$incr = ( $config->graph['max'] && $config->graph['max'] != $config->graph['min'] )
			? ($config->graph['height']-$config->graph['margin.top']-$config->graph['margin.bottom'])/($config->graph['max'] - $config->graph['min'])
			: 0;

			$getTop       = function($h) use (&$config,$incr){return $config->graph['height']-$config->graph['margin.bottom']-(($h-$config->graph['min'])*$incr);};
			$getArrayNext = function($arr,$k){
				$keys = array_keys($arr);
				$index = array_search($k,$keys);
				if($index === false){return false;}
				$v = isset($keys[$index+1]) ? $arr[$keys[$index+1]] : false;
				if( is_array($v) ){$v = $v['v'];}
				return $v;
			};

			/* INI-background */
			if( $config->graph['background.color'] !== false ){
				$bg  = '<g class="fragment background" transform="translate('.$absoluteLeft.','.$this->top.')">'.PHP_EOL;
				$left = 1;for($i = 0;$i < $this->count;$i++,$left += $this->cell['width']+1){
					$color = $config->graph['background.color'];
					if( $this->graph['header.is.date'] && isset($this->header[$i]) ){
						$d = date('w',strtotime($this->header[$i]));
						if( $d == 0 || $d == 6 ){$color = $config->graph['background.color.alternative'];}
					}
					$bg .= '<rect width="'.$this->cell['width'].'" height="'.($config->graph['height']).'" x="'.$left.'" y="1" style="fill:'.$color.';" />'.PHP_EOL;
				}
				$left = ($this->legend['position'] == 'left' ? -$this->legend['width']+1 : $this->graph['width']-$this->legend['width']);
				$bg .= '<rect x="'.$left.'" y="1" width="'.($this->legend['width']-1).'" height="'.($config->graph['height']).'" style="fill:'.$config->graph['background.color'].';" />'.PHP_EOL;
				$bg .= '</g>'.PHP_EOL;
				$this->fragments[] = $bg;
			}
			/* END-background */

			$keys = array_keys($config->data);
			$keys = array_reverse($keys);
			$total = count($keys);
			$half  = ($this->cell['width']/2);
			$path  = false;

			foreach( $keys as $j=>$key ){
				$elem  = &$config->data[$key];
				if( isset($elem['no.graph']) ){continue;}
				$left  = -$half;
				if( !isset($config->colors[$total-$j-1][0]) ){$config->colors[$total-$j-1][0] = '#555555';}
				$color = isset($elem['c']) ? $elem['c'] : is_string($config->colors[$total-$j-1]) ? $config->colors[$total-$j-1] : $config->colors[$total-$j-1][0];
				$svg  .= PHP_TAB.'<g class="row" style="fill:#fff;stroke:'.$color.';stroke-width:2;">'.PHP_EOL;
				foreach( $elem['v'] as $k=>$cell ){
					$left += $this->cell['width']+1;
					$value = $cell;
					//if( $j > $data['items.count'] ){break;}
					if( is_array($value) && isset($value['v']) ){$value = $value['v'];}
					if( $value === false || ( is_string($value) && !$value ) ){continue;}

					$v = floatval($value);
					$top = $getTop($v);
					$n = $getArrayNext($elem['v'],$k);

					if( $n !== false ){
						$topNext  = $getTop($n);
						$leftNext = ($left+$this->cell['width']+1);
						if( $config->graph['line.algorithm'] == 'normal' ){
							$svg .= PHP_TAB.PHP_TAB.'<line x1="'.$left.'" y1="'.$top.'" x2="'.($left+$this->cell['width']+1).'" y2="'.$topNext.'" />'.PHP_EOL;
						}

						if( $config->graph['line.algorithm'] == 'smooth' ){
							$min = min($top,$topNext);
							$max = max($top,$topNext);
							$midx = ($left+$half);
							$midy = ($min+($max-$min)/2);
							$svg .= PHP_TAB.PHP_TAB.'<circle cx="'.$midx.'" cy="'.$midy.'" r="2" />'.PHP_EOL;
							$svg .= PHP_TAB.PHP_TAB.'<circle cx="'.$midx.'" cy="'.$top.'" r="2" style="stroke:blue;"/>'.PHP_EOL;
							if( !$path ){$path .= '<path style="stroke:'.$color.';stroke-width:2;fill:none;" d="M'.$left.','.$top.'';}
							$path .= ' C'.$left.','.$top.' '.$midx.','.$midy.' '.$midx.','.$midy.' ';
						}

						if( $config->graph['line.algorithm'] == 'midhill' ){
							if( !$path ){$path .= '<path style="stroke:'.$color.';stroke-width:2;fill:none;" d="M'.$left.','.$top.'';}
							$path .= ' C'.($left+$half).','.($top).' '.($leftNext-$half).','.($topNext).' '.($leftNext).','.$topNext.' ';
						}
					}


					if( $config->graph['line.algorithm'] == 'thomas' ){
						/* Este está fuera porque no solo tiene que calcular hasta n-1 */
						$px[] = $left;
						$py[] = $top;
					}
					if( $config->graph['points'] ){
						$svg .= PHP_TAB.PHP_TAB.'<circle cx="'.$left.'" cy="'.$top.'" r="4" />'.PHP_EOL;
					}
				}
				$svg .= PHP_TAB.'</g>'.PHP_EOL;

				if( $config->graph['line.algorithm'] == 'thomas' && isset($px,$py) ){
					$pax  = $this->_thomas_control_pointsfunction($px);
					$pay  = $this->_thomas_control_pointsfunction($py);
					$path = '<path style="stroke:'.$color.';stroke-width:2;fill:none;" d="';
					foreach( $px as $k=>$dummy ){
						if( !isset($px[$k+1]) ){continue;}
						$x = $px[$k];
						$y = $py[$k];
						$path .= 'M'.($x).','.($y).' C'.($pax['p1'][$k]).','.($pay['p1'][$k]).' '.($pax['p2'][$k]).','.($pay['p2'][$k]).' '.($px[$k+1]).','.($py[$k+1]).' ';
					}
				}

				if( $path ){
					$path .= '"/>';
					$svg  .= $path;
					$path = false;
				}
			}

			/* INI-Clickable elements */
			if( $this->graph['click.data'] ){
				$left = 1;for($i = 0;$i < $this->count;$i++,$left += $this->cell['width']+1){
					$d = isset($this->graph['click.data'][$i]) ? ' data:data=\'{"data":"'.$this->graph['click.data'][$i].'"}\' ' : '';
					$svg .= '<rect class="clickable" '.$d.' width="'.$this->cell['width'].'" height="'.($config->graph['height']).'" x="'.$left.'" y="1" style="fill:red;fill-opacity:0;"/>'.PHP_EOL;
				}
			}
			/* END-Clickable elements */

			$svg .= '</g>'.PHP_EOL;

			$this->graph['height'] += $config->graph['height']+1;
			$this->top += $config->graph['height']+1;
			$this->fragments[] = $svg;

			if( $config->elements['legend'] ){$this->render_legend($config);}
			if( $config->elements['header'] && $config->elements['header'] ){$this->render_header($config);}
			if( $config->elements['table'] ){$this->render_table($config);}
		}
		function render_bars($config = []){
			$barIndicator = true;
			$absoluteLeft = ($this->legend['position'] == 'left' ? $this->legend['width'] : 0);
			$svg  = '<g class="fragment graph" transform="translate('.$absoluteLeft.','.$this->top.')">'.PHP_EOL;

			if( $barIndicator ){
				$config->graph['margin.top'] += 12;
				$config->graph['margin.bottom'] += 12;
			}

			$incr = ( $config->graph['max'] && $config->graph['max'] != $config->graph['min'] )
			? ($config->graph['height']-$config->graph['margin.top']-$config->graph['margin.bottom'])/($config->graph['max'] - $config->graph['min'])
			: 0;

			$getHeight = function($h) use (&$config,$incr){return ($h-$config->graph['min'])*$incr;};
			$getTop    = function($h) use (&$config,$incr){return $config->graph['height']-$config->graph['margin.bottom']-(($h-$config->graph['min'])*$incr);};

			/* INI-background */
			if( $config->graph['background.color'] !== false ){
				$bg  = '<g class="fragment background" transform="translate('.$absoluteLeft.','.$this->top.')">'.PHP_EOL;
				$left = 1;for($i = 0;$i < $this->count;$i++,$left += $this->cell['width']+1){
					$color = $config->graph['background.color'];
					if( $this->graph['header.is.date'] ){
						$d = date('w',strtotime($this->header[$i]));
						if( $d == 0 || $d == 6 ){$color = $config->graph['background.color.alternative'];}
					}
					$bg .= '<rect width="'.$this->cell['width'].'" height="'.($config->graph['height']).'" x="'.$left.'" y="1" style="fill:'.$color.';" />'.PHP_EOL;
				}
				$left = ($this->legend['position'] == 'left' ? -$this->legend['width']+1 : $this->graph['width']-$this->legend['width']);
				$bg .= '<rect x="'.$left.'" y="1" width="'.($this->legend['width']-1).'" height="'.($config->graph['height']).'" style="fill:'.$config->graph['background.color'].';" />'.PHP_EOL;
				$bg .= '</g>'.PHP_EOL;
				$this->fragments[] = $bg;
			}
			/* END-background */

			$svg .= '<g class="graph">'.PHP_EOL;
			$left  = $config->cell['margin.left'];
			$left -= $this->cell['width'];

			foreach( $config->data as $j=>$elem ){
				foreach( $elem['v'] as $k=>$cell ){
					$left += $this->cell['width']+1;
					$color = isset($cell['c']) ? $cell['c'] : false;
					if( !is_array($cell) ){$cell = ['v'=>$cell];}

					$c = $color ? $color : $config->colors[0][$j];
					$v = floatval($cell['v']);

					$h = $getHeight($v);
					$t = $getTop($v);
					if( $c[0] != '#' ){$c = '#'.$c;}
					$svg .= '<rect width="'.($this->cell['width']-($config->cell['margin.left']*2)).'" height="'.$h.'" x="'.$left.'" y="'.$t.'" style="fill:'.$c.';" rx="2" ry="2"/>'.PHP_EOL;
					if( $barIndicator ){
						$m = 4;
						if( $v ){$svg .= '<path d="M'.($left+$m).' '.($t+$h).' l'.(($this->cell['width']/2)-$config->cell['margin.left']-$m).' 6 l'.(($this->cell['width']/2)-$config->cell['margin.left']-$m).' -6 Z" style="fill:'.$c.';" />'.PHP_EOL;}
						$svg .= '<text x="'.($left+($this->cell['width']/2)-$config->cell['margin.left']).'" y="'.($t+$h+16).'" text-anchor="middle" style="fill:'.$config->graph['text.color'].';font-size:10px;">'.round($v,2).'</text>'.PHP_EOL;
					}
				}
				//FIXME: solo uno de momento
				break;
			}
			$svg .= '</g>'.PHP_EOL;

			/* INI-Clickable elements */
			if( $this->graph['click.data'] ){
				$left = 1;for($i = 0;$i < $this->count;$i++,$left += $this->cell['width']+1){
					$data = json_encode($this->graph['click.data'][$i]);
					$d = isset($this->graph['click.data'][$i]) ? ' data:data=\''.$data.'\' ' : '';
					$svg .= '<rect class="clickable" '.$d.' width="'.$this->cell['width'].'" height="'.($config->graph['height']).'" x="'.$left.'" y="1" style="fill:red;fill-opacity:0;"/>'.PHP_EOL;
				}
			}
			/* END-Clickable elements */

			$svg .= '</g>'.PHP_EOL;

			$this->graph['height'] += $config->graph['height']+1;
			$this->top += $config->graph['height']+1;
			$this->fragments[] = $svg;

			if( $config->elements['legend'] ){$this->render_legend($config);}
			if( $config->elements['table'] && $config->elements['header'] ){$this->render_header($config);}
			if( $config->elements['table'] ){$this->render_table($config);}
		}
		function render_header($config = []){
			$height = $this->graph['header.height'];
			$cellWidthHalf = $this->cell['width']/2;

			$this->header = array_values($this->header);
			$getBackground = function($k){
				$isEven = ($k%2);/* $k+1 because keys start in 0 */
				if(  $isEven && $this->graph['header.background.even'] ){return $this->graph['header.background.even'];}
				if( !$isEven && $this->graph['header.background.odd']  ){return $this->graph['header.background.odd'];}
				return $this->graph['background.color'];
			};

			$left = 1+($this->legend['position'] == 'left' ? $this->legend['width'] : 0);
			$svg  = '<g class="header" transform="translate('.$left.','.$this->top.')">'.PHP_EOL;
			$left = 0;
			for($i = 0;$i < $this->count;$i++,$left += $this->cell['width']+1){
				$label = isset($this->header[$i]) ? $this->header[$i] : '';
				$bg   = $getBackground($i);
				$svg .= '<rect width="'.$this->cell['width'].'" height="'.($height).'" x="'.$left.'" y="1" style="fill:'.$bg.';" />'.PHP_EOL;
				if( $this->graph['header.is.date'] ){
					$svg .= '<text x="'.($left+$cellWidthHalf+3).'" y="'.( ($height/2)+1 ).'" text-anchor="end" dominant-baseline="central" style="fill:'.$this->graph['text.color'].';font-size:14px;">'.substr($label,-2).'</text>'.PHP_EOL;
					$svg .= '<text x="'.($left+$cellWidthHalf+4).'" y="'.( ($height/2) ).'" text-anchor="start" dominant-baseline="central" style="fill:#ccc;font-size:8px;font-weight:bold;">'.strtoupper(date('M',strtotime($label))).'</text>'.PHP_EOL;
					//$svg .= '<text x="'.($left+$data['cell.width.half']+6).'" y="'.( ($data['header.height']/2)+$data['header.top']+4 ).'" text-anchor="start" dominant-baseline="central" style="fill:#888;font-size:8px;">'.substr($label,2,2).'</text>'.PHP_EOL;
				}elseif( $this->graph['header.is.month'] ){
					$svg .= '<text x="'.($left+$cellWidthHalf+9).'" y="'.( ($height/2) ).'" text-anchor="end" dominant-baseline="central" style="fill:#666;font-size:14px;">'.strtoupper(date('M',strtotime($label))).'</text>'.PHP_EOL;
					$svg .= '<text x="'.($left+$cellWidthHalf+10).'" y="'.( ($height/2)-1 ).'" text-anchor="start" dominant-baseline="central" style="fill:#ccc;font-size:8px;font-weight:bold;">'.substr($label,2,2).'</text>'.PHP_EOL;
				}else{
					$svg .= '<text x="'.($left+$cellWidthHalf).'" y="'.( ($height/2) ).'" text-anchor="middle" dominant-baseline="central" style="fill:#444;font-size:10px;">'.$label.'</text>'.PHP_EOL;
				}
			}
			$left = ($this->legend['position'] == 'left' ? -$this->legend['width'] : $this->graph['width']-$this->legend['width']-1);
			$svg .= '<rect x="'.$left.'" y="1" width="'.($this->legend['width']-1).'" height="'.($height).'" style="fill:'.$config->graph['background.color'].';" />';
			$svg .= '</g>'.PHP_EOL;

			$this->graph['height'] += $height+1;
			$this->top += $height+1;
			$this->fragments[] = $svg;
		}
		function render_table($config = []){
			$absoluteLeft = ($this->legend['position'] == 'left' ? $this->legend['width'] : 0);
			$height = (count($config->data)*($this->row['height']+1));

			$arialPath   = '../fonts/arial.ttf';
			$arialExists = file_exists('../fonts/arial.ttf');

			$getBackground = function($k) use (&$config){
				$isEven = (($k-1)%2);/* $k+1 because keys start in 0 */
				if(  $isEven && $config->table['background.even'] ){return $config->table['background.even'];}
				if( !$isEven && $config->table['background.odd']  ){return $config->table['background.odd'];}
				return $this->graph['background.color'];
			};
			$getTextSize = function($text = '',$size = 10) use ($arialPath){
				$bbox = imagettfbbox($size,0,$arialPath,$text);
				return $width = abs($bbox[2] - $bbox[0]);
			};

			$svg  = '<g class="fragment table" transform="translate('.$absoluteLeft.','.($this->top+1).')">'.PHP_EOL;
			$top  = 0;
			$i = -1;foreach($config->data as $id=>&$row){$i++;
				$name     = $id;
				$colorpad = 4;
				$c        = isset($row['c']) ? $row['c'] : ( isset($config->colors[$i][0]) ? $config->colors[$i][0] : '#000' );
				$c        = isset($row['c']) ? $row['c'] : ( isset($config->colors[$i]) ? is_string($config->colors[$i]) ? $config->colors[$i] : $config->colors[$i][0] : '#000' );
				$format   = isset($row['f']) ? $row['f'] : $config->table['value.format'];
				if( isset($row['t']) ){$name = $row['t'];}
				if( isset($row['id']) ){$id = $row['id'];}

				if( $arialExists && ($size = $getTextSize($name)) ){
					$sizeLimit = $this->legend['width']+10;
					while( strlen($name) && $size > $sizeLimit ){
						$name = substr($name,0,-1);
						$size = $getTextSize($name);
					}
				}

				$left = ($this->legend['position'] == 'left' ? 0 : $this->graph['width']-1);
				$svg .= '<g transform="translate('.($left-$this->legend['width']).',0)">'.PHP_EOL;
				$svg .= '<rect width="'.($this->legend['width']-1).'" height="'.($this->row['height']).'" x="1" y="'.$top.'" style="fill:'.$config->graph['background.color'].';" />'.PHP_EOL;
				$svg .= '<rect x="'.(1+$colorpad).'" y="'.($top+$colorpad).'" width="'.($colorpad*2).'" height="'.($this->row['height']-$colorpad*2).'" style="fill:'.$c.';" />'.PHP_EOL;
				$svg .= '<text x="'.(22).'" y="'.($top+($this->row['height']/2)).'" text-anchor="left" dominant-baseline="central" style="fill:'.$config->table['text.color'].';font-size:10px;">'.$name.'</text>'.PHP_EOL;
				$svg .= '</g>'.PHP_EOL;

				$left = 1;
				foreach($row['v'] as $k=>$label){
					$bg      = $getBackground($k);
					$click   = '';
					$color   = $config->table['text.color'];
					$weight  = 'normal';
					$pattern = false;
					if( is_array($label) && isset($label['v']) ){
						if( isset($label['table.bg']) && $label['table.bg'] ){$bg = $label['table.bg'];}
						if( isset($label['table.c']) && $label['table.c'] ){$color = $label['table.c'];$weight = 'bold';}
						if( isset($label['cell.pattern']) ){$pattern = $label['cell.pattern'];}
						if( isset($label['click']) ){$click = $label['click'];}
						$label = isset($label['t']) ? $label['t'] : $label['v'];
					}
					if( strlen($label) && $format ){$label = sprintf($format,$label);}

					$svg .= '<rect width="'.($this->cell['width']).'" height="'.($this->row['height']).'" x="'.$left.'" y="'.($top).'" style="fill:'.$bg.';" />'.PHP_EOL;
					if( $pattern ){$svg .= '<rect width="'.($this->cell['width']).'" height="'.($this->row['height']).'" x="'.$left.'" y="'.($top).'" style="fill:url(#lined);" />'.PHP_EOL;}
					$svg .= '<text x="'.($left+($this->cell['width']/2)).'" y="'.($top+($this->row['height']/2)).'" dominant-baseline="central" text-anchor="middle" style="fill:'.$color.';font-weight:'.$weight.';font-size:10px;">'.$label.'</text>'.PHP_EOL;
					if( $this->graph['click.data'] ){
						$svg .= '<rect class="clickable" data:data=\'{"data":"'.$click.'","id":"'.$id.'"}\' width="'.$this->cell['width'].'" height="'.($this->row['height']).'" x="'.$left.'" y="'.($top).'" style="opacity:0;"/>'.PHP_EOL;
					}
					$left += $this->cell['width']+1;
				}
				$top += $this->row['height']+1;
			}
			$svg .= '</g>'.PHP_EOL;

			$this->graph['height'] += $height;
			$this->top += $height;
			$this->fragments[] = $svg;
		}
		function render_pie($config = []){
			$absoluteLeft = ($this->legend['position'] == 'left' ? $this->legend['width'] : 0);
			//FIXME: tener en cuenta el marginLeft y marginTop, va a ser una puta locura
			$cx = $config->pie['radius'];
			$cy = $config->pie['radius'];

			$polarToCartesian = function($cx,$cy,$radius,$angleInDegrees){
				if( $angleInDegrees >= 360 ){
					$f = floor($angleInDegrees/360);
					$angleInDegrees = $angleInDegrees - ( $f * 360 );
				}
				$angleInRadians = $angleInDegrees * pi() / 180;
				$x = $cx + $radius * cos($angleInRadians);
				$y = $cy + $radius * sin($angleInRadians);
				return [$x,$y];
			};

			$svg = '<g class="fragment pie" transform="translate('.$absoluteLeft.','.($this->top+1).')">'.PHP_EOL;

			$chartelem = '';
			$cx = floatval($cx);

			$sum = array_map(function($n){return $n['v'];},$config->data);
			$sum = array_sum($sum);
			$part = $sum / 360; // one degree
			$jung = $sum / 2; // necessary to test for arc type
			$oldangle = 180;

			$i = -1;
			/* Loop through the slices */
			foreach( $config->data as $slide ){$i++;
				$firstangle = $oldangle;
				$pointStart = $polarToCartesian($cx,$cy,$config->pie['radius'],$firstangle);
				list($sx,$sy) = $pointStart;
				$oldangle += ($slide['v']/$sum)*360;
				$pointEnd = $polarToCartesian($cx,$cy,$config->pie['radius'],$oldangle);
				list($ex,$ey) = $pointEnd;
//FIXME: soporte para la posición 'c'
				$color = $config->colors[$i][0];
				$epsilon = 0.00001;
				$stroke = 1;

				$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$sx.','.$sy.' A'.$config->pie['radius'].' '.$config->pie['radius'].' 0 0 1 '.$ex.' '.$ey.' z" '.
				' fill="'.$color.'" stroke="transparent" stroke-width="'.$stroke.'" fill-opacity=".6" stroke-linejoin="round"/>'.PHP_EOL;
				switch(true){
					case(abs($sx-$cx) < $epsilon && $sy < $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$sx.','.$sy.'" transform="translate(.5,.5)" stroke="white" stroke-width="'.$stroke.'" opacity=".4"/>';break;
					case(abs($sx-$cx) < $epsilon && $sy > $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$sx.','.$sy.'" transform="translate(-.5,.5)" stroke="black" stroke-width="'.$stroke.'" opacity=".3"/>';break;
					case($sx <= $cx && $sy >= $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$sx.','.$sy.'" transform="translate(-.5,-.5)" stroke="black" stroke-width="'.$stroke.'" opacity=".3"/>';break;
					case($sx < $cx && $sy < $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$sx.','.$sy.'" transform="translate(.5,-.5)" stroke="black" stroke-width="'.$stroke.'" opacity=".3"/>';break;
					case($sx > $cx && $sy >= $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$sx.','.$sy.'" transform="translate(-.5,.5)" stroke="white" stroke-width="'.$stroke.'" opacity=".4"/>';break;
					case($sx >= $cx && $sy < $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$sx.','.$sy.'" transform="translate(.5,-.5)" stroke="white" stroke-width="'.$stroke.'" opacity=".4"/>';break;
				}
				switch(true){
					case(abs($ex-$cx) < $epsilon && $ey < $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$ex.','.$ey.'" transform="translate(-.5,.5)" stroke="black" stroke-width="'.$stroke.'" opacity=".3"/>';break;
					case(abs($ex-$cx) < $epsilon && $ey > $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$ex.','.$ey.'" transform="translate(.5,.5)" stroke="white" stroke-width="'.$stroke.'" opacity=".4"/>';break;
					case($ex <= $cx && $ey >= $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$ex.','.$ey.'" transform="translate(.5,.5)" stroke="white" stroke-width="'.$stroke.'" opacity=".4"/>';break;
					case($ex < $cx && $ey < $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$ex.','.$ey.'" transform="translate(-.5,.5)" stroke="white" stroke-width="'.$stroke.'" opacity=".4"/>';break;
					case($ex > $cx && $ey >= $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$ex.','.$ey.'" transform="translate(.5,-.5)" stroke="black" stroke-width="'.$stroke.'" opacity=".3"/>';break;
					case($ex > $cx && $ey < $cy):$chartelem .= '<path d="M'.$cx.','.$cx.' L'.$ex.','.$ey.'" transform="translate(-.5,-.5)" stroke="black" stroke-width="'.$stroke.'" opacity=".3"/>';break;

				}

				$svg .= $chartelem;
			}

			$svg .= '</g>'.PHP_EOL;
			$this->graph['height'] += $config->pie['radius'] * 2;
			$this->fragments[] = $svg;
		}
		function render_legend($config = []){
			if( !isset($config->legend['count']) ){
				//FIXME: realmente se puede basar en $data['graph.height']
				$config->legend['count'] = 2;
			}
			if( !$config->legend['line.color'] && $this->graph['line.color'] ){$config->legend['line.color'] = $this->graph['line.color'];}
			if( !$config->legend['line.color'] ){$config->legend['line.color'] = '#ccc';}

			$incr   = ($config->graph['max']-$config->graph['min'])/($config->legend['count']+1);
			$steps  = [];$top = $config->graph['margin.top'];$i = $config->legend['count']+1;while(--$i){$steps[] = ($i*$incr);}
			$height = $config->graph['height']-$config->graph['margin.top']-$config->graph['margin.bottom'];
			$diff   = ($config->graph['max']-$config->graph['min']);
			$incr   = $diff ? $height/$diff : 0;

			$svg  = '<g class="fragment legend" transform="translate(0,'.($this->top-$config->graph['height']).')">'.PHP_EOL;

			if( $config->legend['range'] ){
				$left = ($this->legend['position'] == 'left' ? 24 : $this->graph['width']-38);
				$svg .= '<g class="legend-range" '
					.'transform="translate('.$left.',0)" '
					.'data-min="'.$config->graph['min.real'].'" '
					.'data-max="'.$config->graph['max.real'].'" '
					.'height="'.$height.'">'.PHP_EOL;
				$transform = '';
				$current   = 0;
				if( $config->graph['max'] != $config->graph['max.real'] ){
					$perc      = round(($config->graph['max']-$config->graph['min.real'])/($config->graph['max.real']-$config->graph['min.real']),2);
					$current   = ($height-round($perc*$height,2));
					$transform = 'transform="translate(0,'.$current.')"';
				}

				$svg .= '<rect x="6" y="'.($config->graph['margin.top']).'" width="4" height="'.$height.'" style="fill:#bbb;" />'.PHP_EOL;
				$indH = 9;
				$indW = 15;
				$maxT = ($config->graph['margin.top']-3);
				$svg .= '<g class="legend-max" '.$transform.' data-current="'.$current.'">'.PHP_EOL;
				$svg .= '<rect x="0" y="'.($maxT-1).'" width="'.$indW.'" height="'.$indH.'" style="fill:'.$this->legend['slider.bordercolor'].';" />'.PHP_EOL;
				$svg .= '<rect x="1" y="'.$maxT.'" width="'.($indW-2).'" height="'.($indH-2).'" style="fill:#fff;" />'.PHP_EOL;
				$svg .= '</g>'.PHP_EOL;

				$svg .= '</g>'.PHP_EOL;
			}

			$leftLine = ($this->legend['position'] == 'left' ? $this->legend['width'] : 0);
			$leftText = ($this->legend['position'] == 'left' ? $this->legend['width']-2 : $this->graph['width']-$this->legend['width']+2);
			$anchor = ($this->legend['position'] == 'left' ? 'end' : 'start');
			$svg .= PHP_TAB.'<rect x="'.($leftLine).'" y="'.($config->graph['margin.top']-1).'" width="'.($this->graph['width']-$this->legend['width']).'" height="1" style="fill:'.$config->legend['line.color'].';" />'.PHP_EOL;
			$svg .= PHP_TAB.'<text x="'.($leftText).'" y="'.($config->graph['margin.top']-1+4).'" text-anchor="'.$anchor.'" style="fill:'.$config->legend['text.color'].';font-size:10px;">'.round($config->graph['max'],2).'</text>'.PHP_EOL;

			foreach($steps as $step){
				$t = floor($height+$config->graph['margin.top']-($step*$incr));
				$svg .= PHP_TAB.'<rect x="'.($leftLine).'" y="'.($t).'" width="'.($this->graph['width']-$this->legend['width']).'" height="1" style="fill:'.$config->legend['line.color'].';" />'.PHP_EOL;
				$svg .= PHP_TAB.'<text x="'.($leftText).'" y="'.($t+4).'" text-anchor="'.$anchor.'" style="fill:#777;font-size:10px;">'.round($step+$config->graph['min'],2).'</text>'.PHP_EOL;
			}

			$svg .= PHP_TAB.'<rect x="'.($leftLine).'" y="'.($config->graph['height']-$config->graph['margin.top']-2).'" width="'.($this->graph['width']-$this->legend['width']).'" height="1" style="fill:'.$config->legend['line.color'].';" />'.PHP_EOL;
			$svg .= PHP_TAB.'<text x="'.($leftText).'" y="'.($config->graph['height']-$config->graph['margin.top']-2+4).'" text-anchor="'.$anchor.'" style="fill:'.$config->legend['text.color'].';font-size:10px;">'.round($config->graph['min'],2).'</text>'.PHP_EOL;

			$svg .= '</g>'.PHP_EOL;

			$lastElem = array_pop($this->fragments);
			$this->fragments[] = $svg;
			$this->fragments[] = $lastElem;
		}
		function colors(&$config = []){
			for($i = 0;$i < $this->count;$i++){
				if( !isset($config->colors[$i]) ){
					$config->colors[$i] = graph_gradient('000000','000000',$this->count);
					continue;
				}
				switch(true){
					case is_array($config->colors[$i]):
						$config->colors[$i] = graph_gradient(reset($config->colors[$i]),end($config->colors[$i]),$this->count);
						break;
					case is_string($config->colors[$i]):
						$config->colors[$i] = array_fill(0,$this->count,$config->colors[$i]);
						break;
					default:
						$config->colors[$i] = graph_gradient('000000','000000',$this->count);
				}
			}
			$keys = array_keys($config->data);
			$i = -1;foreach($keys as $key){$i++;
				$row = &$config->data[$key];
				if( isset($row['c']) ){array_splice($config->colors,$i,0,$row['c']);}
			}
		}
		function _thomas_control_pointsfunction($K){
			/* https://en.wikipedia.org/wiki/Tridiagonal_matrix_algorithm */
			$p1 = $p2 = [];
			$n  = count($K)-1;

			/*rhs vector*/
			$a = $b = $c = $r = [];

			/*left most segment*/
			$a[0] = 0;
			$b[0] = 2;
			$c[0] = 1;
			$r[0] = $K[0]+2*$K[1];

			/*internal segments*/
			for ($i = 1; $i < $n - 1; $i++) {
				$a[$i] = 1;
				$b[$i] = 4;
				$c[$i] = 1;
				$r[$i] = 4 * $K[$i] + 2 * $K[$i+1];
			}

			/*right segment*/
			$a[$n-1] = 2;
			$b[$n-1] = 7;
			$c[$n-1] = 0;
			$r[$n-1] = 8 * $K[$n - 1] + $K[$n];

			/* solves Ax=b with the Thomas algorithm (from Wikipedia) */
			for ($i = 1; $i < $n; $i++) {
				$m = $a[$i] / $b[$i-1];
				$b[$i] = $b[$i] - $m * $c[$i - 1];
				$r[$i] = $r[$i] - $m * $r[$i - 1];
			}

			$p1[$n - 1] = $r[$n - 1] / $b[$n - 1];
			for ($i = $n - 2; $i >= 0; --$i) {
				$p1[$i] = ($r[$i] - $c[$i] * $p1[$i+1]) / $b[$i];
			}

			/* we have p1, now compute p2 */
			for ($i=0;$i<$n-1;$i++) {
				$p2[$i] = 2 * $K[$i + 1] - $p1[$i + 1];
			}

			$p2[$n - 1] = 0.5 * ($K[$n] + $p1[$n - 1]);

			return ['p1'=>$p1,'p2'=>$p2];
		}
	}

	class _graph_svg_config{
		public $type  = false;
		public $graph = [
			 'height'        => 140
			,'width'         => false /* false = Se calculará */
			,'margin.top'    => 10
			,'margin.bottom' => 10
			,'margin.left'   => 4
			,'margin.right'  => 4
			,'text.color'    => '#444444'
			,'background.color' => '#ffffff'
			,'background.color.alternative' => '#f8f8f8'
			,'background.opacity' => 1
			,'avoid.zero' => false
			,'max' => false
			,'min' => 0
			,'max.real' => false
			,'min.real' => false
			,'points'   => true
			,'line.algorithm'=>'normal'
		];
		public $legend = [
			 'count'         => 2
			,'range'         => false
			,'line.color'    => false
			,'text.color'	 => '#444444'
		];
		public $table = [
			 'value.format'    => false
			,'background.even' => '#f8f8f8'
			,'background.odd'  => '#ffffff'
			,'text.color'      => '#444444'
		];
		public $cell = [
			 'margin.left'   => 0
			,'margin.right'  => 0
		];
		public $pie = [
			 'radius'	=> false
		];
		public $elements = [
			 'graph'=>true
			,'header'=>true
			,'table'=>true
			,'legend'=>true
		];
		public $colors = [];
		public $data   = [/* Data to render */];
		function __construct($params = false){
			if( isset($params['type']) ){$this->type    = $params['type'];}
			if( isset($params['data']) ){$this->data    = $params['data'];}
			if( isset($params['graph']) ){$this->graph += $params['graph'];}
			if( isset($params['graph.height']) ){$this->graph['height'] = $params['graph.height'];}
			if( isset($params['graph.max']) ){$this->graph['max'] = $params['graph.max'];}
			if( isset($params['graph.colors']) ){$this->colors = $params['graph.colors'];}
			if( isset($params['graph.points']) ){$this->graph['points'] = $params['graph.points'];}
			if( isset($params['graph.legend.count']) ){$this->legend['count'] = $params['graph.legend.count'];}
			if( isset($params['graph.legend.range']) ){$this->legend['range'] = $params['graph.legend.range'];}
			if( isset($params['graph.margin.top']) ){$this->graph['margin.top'] = $params['graph.margin.top'];}
			if( isset($params['graph.margin.bottom']) ){$this->graph['margin.bottom'] = $params['graph.margin.bottom'];}
			if( isset($params['graph.line.algorithm']) ){$this->graph['line.algorithm'] = $params['graph.line.algorithm'];}
			if( isset($params['background.color']) ){$this->table['background.even'] = $this->graph['background.color'] = $params['background.color'];}
			if( isset($params['background.color.alternative']) ){$this->table['background.odd'] = $this->graph['background.color.alternative'] = $params['background.color.alternative'];}
			if( isset($params['text.color']) ){$this->table['text.color'] = $this->graph['text.color'] = $params['text.color'];}
			if( isset($params['graph.background.color']) ){$this->table['background.even'] = $this->graph['background.color'] = $params['graph.background.color'];}
			if( isset($params['graph.background.color.alternative']) ){$this->table['background.odd'] = $this->graph['background.color.alternative'] = $params['graph.background.color.alternative'];}
			if( isset($params['cell.margin.left']) ){$this->cell['margin.left'] = $params['cell.margin.left'];}
			if( isset($params['cell.margin.right']) ){$this->cell['margin.right'] = $params['cell.margin.right'];}
			if( isset($params['table.value.format']) ){$this->table['value.format'] = $params['table.value.format'];}
			if( isset($params['elements']) ){$this->elements = $params['elements'] + $this->elements;}

			if( !in_array($this->graph['line.algorithm'],[
				 'normal'
				,'smooth'
				,'thomas'
				,'midhill'
			]) ){$this->graph['line.algorithm'] = 'normal';}

			if( $this->type == 'bars' && (!$this->cell['margin.left'] || !$this->cell['margin.right']) ){
				$this->cell['margin.left']  = 20;
				$this->cell['margin.right'] = 20;
			}

			if( $this->type == 'pie' && (!$this->pie['radius'] || !$this->pie['radius']) ){
				$this->pie['radius'] = 100;
				$this->elements = ['graph'=>true];
			}

			if( $this->data ){
				foreach( $this->data as $j=>&$row ){
					if( !is_array($row) || !isset($row['v']) ){$row = ['v'=>$row];}
					if( !isset($row['t']) ){$row['t'] = $j;}
					if(  isset($row['v']) && !$row['v'] ){$row['v'] = [];}
					if(  is_array($row['v']) ){$row['v'] = array_values($row['v']);}
				}

				foreach( $this->data as $j=>&$row ){
					if(  isset($row['no.graph']) ){continue;}
					if( !is_array($row['v']) ){continue;}
					foreach($row['v'] as $k=>$v){
						if( is_array($v) ){$v = $v['v'];}
						if( $this->graph['avoid.zero'] && !$v ){continue;}
						if( $this->graph['max.real'] === false || $v > $this->graph['max.real'] ){$this->graph['max.real'] = $v;}
						if( $this->graph['min.real'] === false || $v < $this->graph['min.real'] ){$this->graph['min.real'] = $v;}
					}
				}
				if( $this->graph['max'] === false ){$this->graph['max'] = $this->graph['max.real'];}
				$this->data = array_values($this->data);
			}


//FIXME: calcular aquí el incremento
		}
	}

	function graph_gradient($hexFrom,$hexTo,$steps){
		$fromRGB['r'] = hexdec(substr($hexFrom, 0, 2));
		$fromRGB['g'] = hexdec(substr($hexFrom, 2, 2));
		$fromRGB['b'] = hexdec(substr($hexFrom, 4, 2));

		$toRGB['r'] = hexdec(substr($hexTo, 0, 2));
		$toRGB['g'] = hexdec(substr($hexTo, 2, 2));
		$toRGB['b'] = hexdec(substr($hexTo, 4, 2));

		$stepRGB['r'] = ($fromRGB['r'] - $toRGB['r']) / ($steps - 1);
		$stepRGB['g'] = ($fromRGB['g'] - $toRGB['g']) / ($steps - 1);
		$stepRGB['b'] = ($fromRGB['b'] - $toRGB['b']) / ($steps - 1);

		$gradientColors = array();

		for($i = 0; $i <= $steps; $i++){
			$RGB['r'] = floor($fromRGB['r'] - ($stepRGB['r'] * $i));
			$RGB['g'] = floor($fromRGB['g'] - ($stepRGB['g'] * $i));
			$RGB['b'] = floor($fromRGB['b'] - ($stepRGB['b'] * $i));

			$hexRGB['r'] = sprintf('%02x', ($RGB['r']));
			$hexRGB['g'] = sprintf('%02x', ($RGB['g']));
			$hexRGB['b'] = sprintf('%02x', ($RGB['b']));

			$gradientColors[] = '#'.implode(NULL,$hexRGB);
		}
		return $gradientColors;
	}

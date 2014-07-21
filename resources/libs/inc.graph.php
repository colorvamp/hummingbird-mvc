<?php
	function graph_lines($data = array()){
		if(!isset($data['graph.height'])){$data['graph.height'] = 140;}
		if(!isset($data['cell.width'])){$data['cell.width'] = 30;}
		if(!isset($data['cell.marginx'])){$data['cell.marginx'] = 0;}
		if(!isset($data['cell.marginy'])){$data['cell.marginy'] = 2;}
		if(isset($data['header'])){$data['cell.marginy'] += isset($data['header.height']) ? $data['header.height'] : 22;}
		if(!isset($data['graph.legend.width'])){$data['graph.legend.width'] = 30;}

		$data['bar.indicator'] = (isset($data['bar.indicator'])) ? 16 : 0;
		$data['cell.width.half'] = $data['cell.width']/2;
		$data['items.count'] = key($data['graph']);$data['items.count'] = count($data['graph'][$data['items.count']]);
		$data['graph.width'] = ($data['items.count']*$data['cell.width'])+$data['items.count']+1;
		if(isset($data['graph.gradient.from']) && isset($data['graph.gradient.to'])){$data['graph.gradient'] = graph_gradient($data['graph.gradient.from'],$data['graph.gradient.to'],$data['items.count']);}
		if(!isset($data['graph.gradient'])){$data['graph.gradient'] = array_fill(0,$data['items.count'],'f00');}
		if(!isset($data['graph.background'])){$data['graph.background'] = 'fff';}
		/* INI-Graph-colors */
		for($i = 0;$i < $data['items.count'];$i++){
			if(!isset($data['graph.colors'][$i])){
				$data['graph.colors'][$i] = graph_gradient('000000','000000',$data['items.count']);
				continue;
			}
			switch(true){
				case is_array($data['graph.colors'][$i]):
					$data['graph.colors'][$i] = graph_gradient(reset($data['graph.colors'][$i]),end($data['graph.colors'][$i]),$data['items.count']);
					break;
				case is_string($data['graph.colors'][$i]):
					$data['graph.colors'][$i] = array_fill(0,$data['items.count'],$data['graph.colors'][$i]);
					break;
				default:
					$data['graph.colors'][$i] = graph_gradient('000000','000000',$data['items.count']);
			}
		}
		/* END-Graph-colors */

		if(!isset($data['graph.min'])){
			$data['graph.min'] = false;
			foreach($data['graph'] as $name=>&$row){foreach($row as $k=>&$v){if($data['graph.min'] === false || $v < $data['graph.min']){$data['graph.min'] = $v;}}}
		}
		if(!isset($data['graph.max'])){
			$data['graph.max'] = false;
			foreach($data['graph'] as $name=>&$row){foreach($row as $k=>&$v){if($data['graph.max'] === false || $v > $data['graph.max']){$data['graph.max'] = $v;}}}
		}

		$data['graph.incr'] = ($data['graph.max']) ? ($data['graph.height']-2-($data['cell.marginy']*2)-$data['bar.indicator'])/($data['graph.max']-$data['graph.min']) : 0;
		$getHeight = function($h) use (&$data){return ($h-$data['graph.min'])*$data['graph.incr'];};
		$getTop = function($h) use (&$data){return $data['graph.height']-(($h-$data['graph.min'])*$data['graph.incr'])-1-$data['cell.marginy']-$data['bar.indicator'];};
		$getArrayNext = function($arr,$k){
			$keys = array_keys($arr);
			$index = array_search($k,$keys);
			if($index === false){return false;}
			return isset($keys[$index+1]) ? $arr[$keys[$index+1]] : false;
		};

		$svg = '<svg width="'.($data['graph.width']+$data['graph.legend.width']).'" height="'.($data['graph.height']).'">'.PHP_EOL.
		'<rect width="100%" height="100%" style="fill:#aaa;" />'.PHP_EOL;

		$svg .= '<g class="graph">'.PHP_EOL;
		$left = 1+$data['graph.legend.width'];for($i = 0;$i < $data['items.count'];$i++,$left += $data['cell.width']+1){
			$svg .= '<rect width="'.$data['cell.width'].'" height="'.($data['graph.height']-2).'" x="'.$left.'" y="1" style="fill:#'.$data['graph.background'].';" />'.PHP_EOL;
		}
		if($data['graph.legend.width']){
			$svg .= graph_fragment_legend($data);
		}
		$i = -1;foreach($data['graph'] as $name=>&$row){$i++;
			$left = 1+$data['graph.legend.width']+$data['cell.width.half'];
			$j = -1;foreach($row as $k=>&$v){$j++;
				if(!is_array($v) && ($v = floatval($v)) ){
					$h = $getHeight($v);
					$t = $getTop($v);
					$n = $getArrayNext($row,$k);
					if($n !== false){
						$t2 = $getTop($n);
						$svg .= '<line x1="'.$left.'" y1="'.$t.'" x2="'.($left+$data['cell.width']+1).'" y2="'.$t2.'" style="fill:#fff;stroke:#'.$data['graph.colors'][$i][$j].';stroke-width:2;" />';
					}
					$svg .= '<circle cx="'.$left.'" cy="'.$t.'" r="4" style="fill:#fff;stroke:#'.$data['graph.colors'][$i][$j].';stroke-width:2;" />';
				}
				$left += $data['cell.width']+1;
			}
		}
		$svg .= '</g>'.PHP_EOL;

		$svg .= graph_fragment_header($data);
		if(!isset($data['header.height'])){$data['header.height'] = 22;}
		$data['header.top'] = ($data['graph.height']-$data['header.height']);
		$svg .= graph_fragment_header($data);

		if(isset($data['table'])){
			$data['table'] = &$data['graph'];
			$svg .= graph_fragment_table($data);
			if(isset($data['table.height'])){
				/* Update svg's height */
				$svg = preg_replace('/^(<svg width=.[^\"\']+. height=.)[^\"\']+(.[^>]*>)/','${1}'.($data['graph.height']+$data['table.height']).'${2}',$svg);
			}
		}

		$svg .= '</svg>';

		return $svg;
	}

	function graph_bars($data = array()){
		if(!isset($data['graph.height'])){$data['graph.height'] = 140;}
		if(!isset($data['cell.width'])){$data['cell.width'] = 30;}
		if(!isset($data['cell.marginx'])){$data['cell.marginx'] = 0;}
		if(!isset($data['cell.marginy'])){$data['cell.marginy'] = 2;}
		if(isset($data['header'])){$data['cell.marginy'] += isset($data['header.height']) ? $data['header.height'] : 22;}
		if(!isset($data['graph.legend.width'])){$data['graph.legend.width'] = 30;}

		$data['bar.indicator'] = (isset($data['bar.indicator'])) ? 16 : 0;
		$data['cell.width.half'] = $data['cell.width']/2;
		$data['items.count'] = key($data['graph']);$data['items.count'] = count($data['graph'][$data['items.count']]);
		$data['graph.width'] = ($data['items.count']*$data['cell.width'])+$data['items.count']+1;
		if(isset($data['graph.gradient.from']) && isset($data['graph.gradient.to'])){$data['graph.gradient'] = graph_gradient($data['graph.gradient.from'],$data['graph.gradient.to'],$data['items.count']);}
		if(!isset($data['graph.gradient'])){$data['graph.gradient'] = array_fill(0,$data['items.count'],'f00');}
		if(!isset($data['graph.background'])){$data['graph.background'] = 'fff';}

		if(!isset($data['graph.min'])){
			$data['graph.min'] = false;
			foreach($data['graph'] as $name=>&$row){foreach($row as $k=>&$v){if($data['graph.min'] === false || $v < $data['graph.min']){$data['graph.min'] = $v;}}}
		}
		if(!isset($data['graph.max'])){
			$data['graph.max'] = false;
			foreach($data['graph'] as $name=>&$row){foreach($row as $k=>&$v){if($data['graph.max'] === false || $v > $data['graph.max']){$data['graph.max'] = $v;}}}
		}

		$data['graph.incr'] = ($data['graph.max']) ? ($data['graph.height']-2-($data['cell.marginy']*2)-$data['bar.indicator'])/($data['graph.max']-$data['graph.min']) : 0;
		$getHeight = function($h) use (&$data){return ($h-$data['graph.min'])*$data['graph.incr'];};
		$getTop = function($h) use (&$data){return $data['graph.height']-(($h-$data['graph.min'])*$data['graph.incr'])-1-$data['cell.marginy']-$data['bar.indicator'];};

		$svg = '<svg width="'.($data['graph.width']+$data['graph.legend.width']).'" height="'.($data['graph.height']).'">'.PHP_EOL.
		'<rect width="100%" height="100%" style="fill:#aaa;" />'.PHP_EOL;

		$svg .= '<g class="graph">'.PHP_EOL;
		$left = 1+$data['graph.legend.width'];for($i = 0;$i < $data['items.count'];$i++,$left += $data['cell.width']+1){
			$svg .= '<rect width="'.$data['cell.width'].'" height="'.($data['graph.height']-2).'" x="'.$left.'" y="1" style="fill:#'.$data['graph.background'].';" />'.PHP_EOL;
		}
		if($data['graph.legend.width']){
			$svg .= graph_fragment_legend($data);
		}
		foreach($data['graph'] as $name=>&$row){
			$left = 1+$data['cell.marginx']+$data['graph.legend.width'];
			$i = -1;foreach($row as $k=>&$v){$i++;
				if(!is_array($v) && ($v = floatval($v)) ){
					$h = $getHeight($v);
					$t = $getTop($v);
					$svg .= '<rect width="'.($data['cell.width']-($data['cell.marginx']*2)).'" height="'.$h.'" x="'.$left.'" y="'.$t.'" style="fill:#'.$data['graph.gradient'][$i].';" rx="2" ry="2"/>'.PHP_EOL;
					if($data['bar.indicator']){
						$m = 4;
						$svg .= '<path d="M'.($left+$m).' '.($t+$h).' l'.($data['cell.width.half']-$data['cell.marginx']-$m).' 6 l'.($data['cell.width.half']-$data['cell.marginx']-$m).' -6 Z" style="fill:#'.$data['graph.gradient'][$i].';" />'.PHP_EOL;
						$svg .= '<text x="'.($left+$data['cell.width.half']-$data['cell.marginx']).'" y="'.($t+$h+16).'" text-anchor="middle" style="fill:#444;font-size:10px;">'.round($v,2).'</text>'.PHP_EOL;
					}
				}
				$left += $data['cell.width']+1;
			}
		}
		$svg .= '</g>'.PHP_EOL;

		$svg .= graph_fragment_header($data);
		if(!isset($data['header.height'])){$data['header.height'] = 22;}
		$data['header.top'] = ($data['graph.height']-$data['header.height']);
		$svg .= graph_fragment_header($data);

		if(isset($data['table'])){
			$svg .= graph_fragment_table($data);
			if(isset($data['table.height'])){
				/* Update svg's height */
				$svg = preg_replace('/^(<svg width=.[^\"\']+. height=.)[^\"\']+(.[^>]*>)/','${1}'.($data['graph.height']+$data['table.height']).'${2}',$svg);
			}
		}

		$svg .= '</svg>';

		return $svg;
	}
	function graph_fragment_table(&$data = array()){
		if(!isset($data['table.row.height'])){$data['table.row.height'] = 20;}
		$data['table.row.count'] = count($data['table']);
		$data['table.height'] = ($data['table.row.height']+1)*$data['table.row.count'];
		if(!isset($data['cell.width'])){$data['cell.width'] = 30;}
		if(!isset($data['graph.legend.width'])){$data['graph.legend.width'] = 0;}
		if(!isset($data['graph.background'])){$data['graph.background'] = 'fff';}
		if(!isset($data['table.background']) && isset($data['graph.background'])){$data['table.background'] = $data['graph.background'];}
		$getBackground = function($k) use (&$data){
			$isEven = ($k%2);/* $k+1 because keys start in 0 */
			if($isEven && isset($data['table.even.background'])){return $data['table.even.background'];}
			if(!$isEven && isset($data['table.odd.background'])){return $data['table.odd.background'];}
			return $data['table.background'];
		};


		$svg = '<g class="table">'.PHP_EOL;
		$svg .= '<rect x="1" y="'.$data['graph.height'].'" width="'.($data['graph.legend.width']+$data['graph.width']).'" height="'.$data['table.height'].'" style="fill:#aaa;" />';
		$top = $data['graph.height'];
		foreach($data['table'] as $name=>&$row){
			$svg .= '<rect width="'.($data['graph.legend.width']-1).'" height="'.($data['table.row.height']).'" x="1" y="'.($top).'" style="fill:#'.$data['table.background'].';" />'.PHP_EOL;
			$svg .= '<text x="'.(2).'" y="'.($data['header.height']/2+(10/2/* font-size */)-2+$top).'" text-anchor="left" style="fill:#444;font-size:10px;">'.$name.'</text>'.PHP_EOL;			

			$left = 1+$data['graph.legend.width'];foreach($row as $k=>$label){
				$bg = $getBackground($k);
				$svg .= '<rect width="'.$data['cell.width'].'" height="'.($data['table.row.height']).'" x="'.$left.'" y="'.($top).'" style="fill:#'.$bg.';" />'.PHP_EOL;
				$svg .= '<text x="'.($left+$data['cell.width.half']).'" y="'.($data['header.height']/2+(10/2/* font-size */)-2+$top).'" text-anchor="middle" style="fill:#444;font-size:10px;">'.$label.'</text>'.PHP_EOL;
				$left += $data['cell.width']+1;
			}
			$top += $data['table.row.height']+1;
		}
		$svg .= '</g>'.PHP_EOL;
		return $svg;
	}
	function graph_fragment_legend(&$data = array()){
		if(!isset($data['graph.background'])){$data['graph.background'] = 'fff';}
		if(!isset($data['graph.legend.count'])){
			//FIXME: realmente se puede basar en $data['graph.height']
			$data['graph.legend.count'] = 2;
		}
		if(!isset($data['graph.legend.line.color'])){$data['graph.legend.line.color'] = 'ccc';}

		$incr = ($data['graph.max']-$data['graph.min'])/($data['graph.legend.count']+1);
		$steps = array();$top = $data['cell.marginy']+1;$i = $data['graph.legend.count']+1;while(--$i){$steps[] = $i*$incr;}
		$height = ($data['graph.height']-($data['cell.marginy']*2)-$data['bar.indicator']-2);
		$diff = ($data['graph.max']-$data['graph.min']);
		$incr = $diff ? $height/$diff : 0;

		$svg = '<g class="legend">'.PHP_EOL;
		$svg .= '<rect x="1" y="1" width="'.($data['graph.legend.width']-1).'" height="'.($data['graph.height']-2).'" style="fill:#'.$data['graph.background'].';" />';

		$svg .= '<rect x="'.($data['graph.legend.width']).'" y="'.($data['cell.marginy']+1).'" width="'.($data['graph.width']).'" height="1" style="fill:#'.$data['graph.legend.line.color'].';" />';
		$svg .= '<text x="'.($data['graph.legend.width']-2).'" y="'.($data['cell.marginy']+5).'" text-anchor="end" style="fill:#444;font-size:10px;">'.round($data['graph.max'],2).'</text>';

		foreach($steps as $step){
			$t = floor($height+$data['cell.marginy']-($step*$incr));
			$svg .= '<rect x="'.($data['graph.legend.width']).'" y="'.($t).'" width="'.($data['graph.width']).'" height="1" style="fill:#'.$data['graph.legend.line.color'].';" />';
			$svg .= '<text x="'.($data['graph.legend.width']-2).'" y="'.($t+4).'" text-anchor="end" style="fill:#444;font-size:10px;">'.round($step,2).'</text>';
		}

		$svg .= '<rect x="'.($data['graph.legend.width']).'" y="'.($data['graph.height']-$data['cell.marginy']-$data['bar.indicator']-2).'" width="'.($data['graph.width']).'" height="1" style="fill:#'.$data['graph.legend.line.color'].';" />';
		$svg .= '<text x="'.($data['graph.legend.width']-2).'" y="'.($data['graph.height']-$data['cell.marginy']-$data['bar.indicator']-2+4).'" text-anchor="end" style="fill:#444;font-size:10px;">'.round($data['graph.min'],2).'</text>';

		$svg .= '</g>'.PHP_EOL;
		return $svg;
	}
	function graph_fragment_header(&$data = array()){
		if(!isset($data['cell.width'])){$data['cell.width'] = 30;}
		if(!isset($data['header.height'])){$data['header.height'] = 22;}
		if(!isset($data['header.top'])){$data['header.top'] = 0;}
		if(!isset($data['graph.background'])){$data['graph.background'] = 'fff';}
		if(!isset($data['graph.legend.width'])){$data['graph.legend.width'] = 0;}
		$data['header'] = array_values($data['header']);
		$getBackground = function($k) use (&$data){
			$isEven = ($k%2);/* $k+1 because keys start in 0 */
			if($isEven && isset($data['header.even.background'])){return $data['header.even.background'];}
			if(!$isEven && isset($data['header.odd.background'])){return $data['header.odd.background'];}
			return $data['graph.background'];
		};

		$svg = '<g class="header">'.PHP_EOL;
		$svg .= '<rect x="'.$data['graph.legend.width'].'" y="'.$data['header.top'].'" width="'.($data['graph.width']).'" height="'.$data['header.height'].'" style="fill:#aaa;" />';
		$left = 1+$data['graph.legend.width'];foreach($data['header'] as $k=>$label){
			$bg = $getBackground($k);
			$svg .= '<rect width="'.$data['cell.width'].'" height="'.($data['header.height']-2).'" x="'.$left.'" y="'.(1+$data['header.top']).'" style="fill:#'.$bg.';" />'.PHP_EOL;
			$svg .= '<text x="'.($left+$data['cell.width.half']).'" y="'.($data['header.height']/2+(10/2/* font-size */)-1+$data['header.top']).'" text-anchor="middle" style="fill:#444;font-size:10px;">'.$label.'</text>'.PHP_EOL;
			$left += $data['cell.width']+1;
		}
		$svg .= '</g>'.PHP_EOL;
		return $svg;
	}
	function graph_gradient($hexFrom, $hexTo, $steps){
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

			$gradientColors[] = implode(NULL,$hexRGB);
		}
		return $gradientColors;
	}



<?php
	/* This controllers is for images only */
	function images_main(){

	}

	function images_avatar(){
		include_once('inc.graph.php');
		$gradient = graph_gradient('8cc277','6fa85b',6);

		$svg = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
		$svg .= '<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN" "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">'.PHP_EOL;
		$svg .= '<svg width="48" height="48" version="1.1" xmlns="http://www.w3.org/2000/svg">'.PHP_EOL;
		for($i = 0; $i < 48; $i+=6){
			for($j = 0; $j < 48; $j+=6){
				$index = array_rand($gradient); /* Get a random index */
				$color = $gradient[$index]; /* Grab a color */
				$svg .= '<rect x="'.$i.'" y="'.$j.'" width="6" height="6" style="fill:#'.$color.';" />'.PHP_EOL;
			}
		}
		$svg .= '</svg>'.PHP_EOL;

		header('Content-type: image/svg+xml');
		echo $svg;
		exit;
	}

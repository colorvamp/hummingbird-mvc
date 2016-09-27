<?php
	if( $GLOBALS['w.localhost'] ){ini_set('display_errors',1);}
	date_default_timezone_set('Europe/Madrid');

	include_once('inc.common.php');
	common_setBase('base');

	function __autoload( $name ){
		switch( $name ){
			case '_mongo':
			case '_mongodb':
				if( class_exists('MongoId') ){include('classes/class._mongo.php');break;}
				include('classes/class._mongodb.php');break;
			case '__strings':include('inc.strings.php');break;
		}
	}

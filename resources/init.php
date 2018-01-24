<?php
	if( $GLOBALS['w.localhost'] ){ini_set('display_errors',1);}
	date_default_timezone_set('Europe/Madrid');

	include_once('inc.common.php');
	common_setBase('base');
	if( !function_exists('mb_strlen') ){echo 'Please install php-mbstring'.PHP_EOL;exit;}

	spl_autoload_register(function( $name ){
		switch( $name ){
			/* INI-Databases */
			case '_mongo':
			case '_mongodb':
				if( class_exists('MongoId') ){include('classes/class._mongo.php');break;}
				include('classes/class._mongodb.php');break;
			case '_sqlite3':
				if( !class_exists('SQLite3') ){echo 'Please install php-sqlite3'.PHP_EOL;exit;}
				include('classes/class._sqlite3.php');break;
			case '_mysql':		include('classes/class._mysql.php');break;
			/* END-Databases */

			case  '_html_fileg':
			case '__html_fileg':	include('classes/class._html_fileg.php');break;
			case '__strings':	include('inc.strings.php');break;
			case  '_date':
			case '__date':		include('classes/class._date.php');break;
			case  '_params':	include('classes/class._params.php');break;
			case '__images':	include('classes/class._images.php');break;
			case  '_zip':
				if( !class_exists('ZipArchive') ){echo 'Please install php-zip'.PHP_EOL;exit;}
				include('classes/class._zip.php');break;

			case '_shoutbox_sqlite3':	include('classes/class._shoutbox.sqlite3.php');break;
				
		}
	});

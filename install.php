#!/usr/bin/php
<?php
	if( !is_writable('/etc/hosts') ){
		echo 'PERMISSION_DENIED: try with "sudo"'.PHP_EOL;
		exit;
	}

	$name = isset($argv[1]) && $argv[1] ? $argv[1] : readline('Project name: ');
	$path = '/var/www/html/'.$name;
	if( file_exists($path) ){
		//echo 'FOLDER_ALREADY_IN_USE: '.$path.PHP_EOL;
		//exit;
	}

	//FIXME: move the folder

	$domain = '
		<VirtualHost *:80>
		ServerName mydomain.localhost
		ServerAlias www.mydomain.localhost
		DirectoryIndex index.php
		DocumentRoot /var/www/html/mydomain.localhost

		<Directory /var/www/html/mydomain.localhost/>
			AddOutputFilterByType DEFLATE text/html
			DirectoryIndex index.php
			Options Indexes FollowSymLinks
			AllowOverride All
			Require all granted
		</Directory>

		</VirtualHost>
	';
	$domain   = str_replace('mydomain.localhost',$name,$domain);
	$confFile = $name.'.conf';
	file_put_contents('/etc/apache2/sites-available/'.$confFile,$domain);
	$r = shell_exec('a2ensite '.$confFile);
	$r = shell_exec('/etc/init.d/apache2 restart');

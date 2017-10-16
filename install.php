#!/usr/bin/php
<?php
	$server = 'apache2';
	$server = 'nginx';

	if( !function_exists('readline') ){
		function readline($prompt = null){
			if($prompt){echo $prompt;}
			$fp   = fopen('php://stdin','r');
			$line = rtrim(fgets($fp,1024));
			return $line;
		}
	}

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

	/* INI-Dependencies */
	if( !class_exists('SQLite3') ){
		$r = shell_exec('sudo apt-get install php-sqlite3');
	}
	/* END-Dependencies */

	$nameCurrent = basename(dirname(__FILE__));
	$pathCurrent = dirname(__FILE__);
	if( $nameCurrent != $name ){
		rename($pathCurrent,$path);
	}

	switch( $server ){
		case 'apache2':
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
			break;
		case 'nginx':
			$domain = '
				server {
					listen 443 ssl http2 default_server;
					listen [::]:443 ssl http2 default_server;

					root /var/www/html/mydomain.localhost/;

					# Add index.php to the list if you are using PHP
					index index.html index.htm index.php;

					server_name mydomain.localhost;

					location / {
						# First attempt to serve request as file, then
						# as directory, then fall back to displaying a 404.
						try_files $uri $uri/ =404;
					}

					# pass the PHP scripts to FastCGI server listening on 127.0.0.1:9000
					location ~ \.php$ {
						include snippets/fastcgi-php.conf;
					}

					# deny access to .htaccess files, if Apaches document root
					# concurs with nginxs one
					location ~ /\.ht {
						deny all;
					}

					#ssl_certificate /etc/ssl/certs/localhost.crt;
					#ssl_certificate_key /etc/ssl/private/localhost.key;
					#ssl_dhparam  /etc/nginx/ssl/dhparam.pem;
				}
			';
			$domain   = str_replace('mydomain.localhost',$name,$domain);
			$confFile = $name;
			file_put_contents('/etc/nginx/sites-available/'.$confFile,$domain);
			break;
	}


hummingbird-mvc
===============

Basic PHP Model-View-Controller skeleton

Installation with apache 2.4 in ubuntu
--------------------------------------

Firstly we need to install apache2 server, and php5 dependencies.

```
apt-get install apache2 php5 php5-sqlite
```

Allow apache to rewrite.

```
a2enmod rewrite
```

Clone the project, and then rename the folder with your project
name. In my case I will choose **mydomain.localhost**.

```
cd /var/www/html/
git clone https://github.com/sombra2eternity/hummingbird-mvc.git
mv hummingbird-mvc mydomain.localhost
```

Now we must create an entry in apache to receive the new domain.

```
cd /etc/apache2/sites-available/
nano mydomain.localhost.conf
```

And paste the followng code on the nano document, remember to 
replace all **mydomain.localhost** ocurrences with your own
domain name.

```
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
```

Now we must activate this entry conf.

```
a2ensite mydomain.localhost
```

Finally, we only need to point this fake host to our computer.
So we need to edit the hosts file.


```
nano /etc/hosts
```

And add somewhere in the file the next line

```
127.0.0.1       mydomain.localhost
```

Just restart apache, and open the domain in your browser, it should
work now.

```
/etc/init.d/apache2 restart
```

Automatic Installation (WIP)
----------------------------

```
php install.php
```


![Hummingbird-mvc](http://i.imgur.com/TEvoujO.png "Hummingbird-mvc")

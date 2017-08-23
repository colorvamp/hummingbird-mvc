hummingbird-mvc
===============

Basic PHP Model-View-Controller skeleton. Made with performance in mind, it uses a
lightweight dispatcher of about 60 lines of code. The controllers are builds upon
static functions to avoid class initialization penalties at large scale but trying
to keep as much cleanness and structuration as possible.

Init
----
Before any controller, the file 'resources/init.php' will be loaded, here is located 
the autoloader (using __autoload instead of spl\_autoload\_register) with a set of
basic clases for base functionality. In this file you can add user validation, url 
routing hacks and any kind of dirty things :-)

Controllers
-----------

Controllers are located in 'resources/controllers/' and will map his name as an url
entrypoint. The controller 'index.php' with the function 'index_main' inside is a 
must for fallback. If you created a new controller, it needs to start with a 
controller_main function (replace 'controller' with the name of the controller).


```
              controller
                   │
                   │   method   params
               ┌───┴──┐ ┌─┴┐ ┌─────┴─────┐
               │      │ │  │ │           │
domain.example/shoutbox/post/param1/param2
```

The concept is simple, the dispatcher will try to match the most accurate entrypoint
for the current url depending on things like if the controller file exists or if the
related function exists. The previous example will try to match entrypoint using this order:

```
 'shoutbox_post'  function inside 'shoutbox.php' controller with params (param1,param2)
 'shoudbox_main'  function inside 'shoutbox.php' controller with params (post,param1,param2)
 'index_shoutbox' function inside 'index.php'    controller with params (post,param1,param2)
 'index_main'     function inside 'index.php'    controller with params (shoutbox,post,param1,param2)
```

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

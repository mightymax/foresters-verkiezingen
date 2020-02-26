# Verkiezingstool Bestuur De Foresters

## Install
    mkdir [subdir-of-your-webroot]
    cd [subdir-of-your-webroot]
    git clone https://github.com/mightymax/foresters-verkiezingen.git
    cp config.dist.php config.php
    php composer.phar install
    cat init.sql | sqlite3 verkiezingen.sqlite3
	
This install is just to give you the rough idea. As you can see there is 
a 'public' directory which should be the root of your website or website-url.
Files and folders outside of the public should definitely *NOT* be accessible
by your webserver. Easiest way is to clone repo outside webserver's DocRoot
and symlink to 'public' dir:

    cd /usr/local/include/
    git clone https://github.com/mightymax/foresters-verkiezingen.git
    cp config.dist.php config.php
    php composer.phar install
    cat init.sql | sqlite3 verkiezingen.sqlite3
    cd [DocRoot]/[WebRoot]
	ln -s /usr/local/include/foresters-verkiezingen/public [subdir-of-your-webroot]
	

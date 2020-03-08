# Verkiezingstool Bestuur De Foresters

## Install
This install is just to give you the rough idea. As you can see there is 
a 'public' directory which should be the root of your website or website-url.
Files and folders outside of the public should definitely *NOT* be accessible
by your webserver. Easiest way is to clone repo outside webserver's DocRoot
and symlink to 'public' dir:

    cd /usr/local/include/
    git clone https://github.com/mightymax/foresters-verkiezingen.git
	cd foresters-verkiezingen
    cp config.dist.php config.php
    php composer.phar install
    cat init.sql | sqlite3 verkiezingen.sqlite3
    cd [DocRoot]/[WebRoot]
    ln -s /usr/local/include/foresters-verkiezingen/public [subdir-of-your-webroot]
    cd [DocRoot]/[WebRoot]/[subdir-of-your-webroot]/api/
    cp defines.dist.php defines.php

After installation check and modify two files:
1. /usr/local/include/config.php
2. [DocRoot]/[WebRoot]/[subdir-of-your-webroot]/api/defines.php
Modifications should be self-explanatory, but feel free to ask for help.

## Updating to latest changes
    cd /usr/local/include/foresters-verkiezinge
    git pull origin master

## Tags
 - v1.0 Version based on multiple candidates, both individuals as well as groups
 
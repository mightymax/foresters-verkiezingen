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

Also make sure your web server has write access to the sqlite3 DB in /usr/local/include/foresters-verkiezingen/

## Updating to latest changes
    cd /usr/local/include/foresters-verkiezingen
    git pull origin master
	
## About emails
This system uses Google's GMail API to send e-mails. You do need a GMail/GSuite account for this.
Enable the GMail API and save the 'AuthConfig' data in your config.php. You also need an AccessToken.
To fetch on vist the api/admin.php?cmd=chechGmailClient, follow the link and copy/pastre the AuthToken.
It is wise to repeat this before sending large batches of mails, since tokens do expire!

## Tags
 - v1.0 Version based on multiple candidates, both individuals as well as groups
 
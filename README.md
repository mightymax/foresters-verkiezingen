# Verkiezingstool Bestuur De Foresters

## Install
    mkdir [subdir-of-your-webroot]
    cd [subdir-of-your-webroot]
    git clone https://github.com/mightymax/foresters-verkiezingen.git
    cp config.dist.php config.php
    php composer.phar install
    cat init.sql | sqlite3 verkiezingen.sqlite3
	

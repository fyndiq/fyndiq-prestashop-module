#!/usr/bin/env bash

PS_VERSION=prestashop_1.4.11.0.zip

DOMAIN=prestashop.local
ADMIN_EMAIL=admin@example.com
ADMIN_PASS=password123123
COUNTRY=se

apt-get update
apt-get install -y build-essential vim-nox
apt-get install -y unzip

## Setup locales
locale-gen en_GB.UTF-8
dpkg-reconfigure locales

## Install MySQL and PHP
echo "mysql-server-5.5 mysql-server/root_password password 123" | sudo debconf-set-selections
echo "mysql-server-5.5 mysql-server/root_password_again password 123" | sudo debconf-set-selections
apt-get install -y mysql-server
apt-get install -y apache2 php5 php5-mysql php5-gd php5-mcrypt php5-curl

# Install scss
sudo gem install sass

## COMPOSER
if [ ! -e '/usr/local/bin/composer' ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

## PHP error_log
if [ ! -f '/etc/php5/apache2/conf.d/30-error_log.ini' ]; then
    echo 'error_log=/tmp/php_error.log' > /etc/php5/apache2/conf.d/30-error_log.ini
fi

composer self-update

## Download and setup Prestashop
if [ ! -f "/var/www/html/prestashop/index.php" ]; then
    echo "=== Installing Prestashop from $PS_VERSION ==="
    cd /tmp
    echo "Downloading $PS_VERSION ..."
    wget --quiet http://www.prestashop.com/download/old/$PS_VERSION
    echo "Unzipping $PS_VERSION ..."
    unzip -o -q $PS_VERSION
    sudo rm ./$PS_VERSION
    mv prestashop /var/www/html/

    ## Setup virtual host
    ln -s /vagrant/assets/001-prestashop.conf /etc/apache2/sites-enabled/001-prestashop.conf
    service apache2 restart

    ## Create database
    mysql -uroot -p123 -e 'create database prestashop'

    ## Link module
    ln -s /opt/fyndiq-prestashop-module/src /var/www/html/prestashop/modules/fyndiqmerchant

    ## Fix ownership and permissions
    chown -R vagrant:www-data /var/www/html/prestashop/
    chmod -R 775 /var/www/html/prestashop/

    ## Add hosts to file
    echo "192.168.44.44  fyndiq.local" >> /etc/hosts
    echo "127.0.0.1  prestashop.local" >> /etc/hosts
fi

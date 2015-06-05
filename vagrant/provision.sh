#!/usr/bin/env bash

PS_VERSION=prestashop_1.6.0.14.zip

apt-get update
apt-get install -y build-essential vim-nox
apt-get install -y unzip

echo "mysql-server-5.5 mysql-server/root_password password 123" | sudo debconf-set-selections
echo "mysql-server-5.5 mysql-server/root_password_again password 123" | sudo debconf-set-selections
apt-get install -y mysql-server
apt-get install -y apache2 php5 php5-mysql php5-gd php5-mcrypt


###########################################################
# COMPOSER
###########################################################

if [ ! -e '/usr/local/bin/composer' ]; then
    curl -sS https://getcomposer.org/installer | php
    mv composer.phar /usr/local/bin/composer
fi

composer self-update

## Download Prestashop
if [ ! -f "/var/www/html/prestashop/index.php" ]; then
    cd /tmp
    wget http://www.prestashop.com/download/old/$PS_VERSION
    unzip -o $PS_VERSION
    sudo rm ./$PS_VERSION
    mv prestashop /var/www/html/
fi

## Setup virtual host
ln -s /vagrant/assets/001-prestashop.conf /etc/apache2/sites-enabled/001-prestashop.conf
service apache2 restart

## Create database
mysql -uroot -p123 -e 'create database prestashop'

php /var/www/html/prestashop/install/index_cli.php --domain=prestashop.local --db_server=localhost --db_name=prestashop --db_user=root --db_password=123 --email=admin@example.com --password=password123123 --send_email=0 --country=se
mv /var/www/html/prestashop/admin /var/www/html/prestashop/admin1234

chown -R vagrant:www-data /var/www/html/prestashop/
chmod -R 775 /var/www/html/prestashop/

## Link module
ln -s /opt/fyndiq-prestashop-module/src /var/www/html/prestashop/modules/fyndiqmerchant

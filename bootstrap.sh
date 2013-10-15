#!/usr/bin/env bash

# This is a work-in-progress provisioning script for Vagrant.
# It's not done yet.  Don't use it.

# Configuration
DBUSER=phpwebsite
DBPASS=phpwebsite
DBNAME=phpwebsite

echo "Installing Packages..."
yum -y install httpd php-cli php-pgsql php-pecl-xdebug php-pdo php php-mbstring php-common php-mysql php-soap php-gd php-xml php-pecl-apc mysql-server mysql postgresql-server postgresql > /dev/null 2>&1

# MySQL
echo "Setting up MySQL..."
service mysqld start > /dev/null 2>&1
mysql -u root <<MySQL
CREATE DATABASE '$DBNAME';
GRANT ALL ON '$DBNAME'.* TO '$DBUSER'@localhost IDENTIFIED BY '$DBPASS';
MySQL

# PostgreSQL
echo "Setting up PostgreSQL..."
# TODO

# Apache
echo "Configuring Apache..."
service httpd start > /dev/null 2>&1
rm /etc/httpd/conf.d/welcome.conf
rm -rf /var/www/html
ln -sf /vagrant /var/www/html

# IPTables
echo "Configuring Firewall..."
iptables -I INPUT 5 -p tcp -m state --state=NEW --dport 80 -j ACCEPT

# Helpful Information
cat << USAGE
===============================================================================

 Thanks for trying phpWebSite!

 The server instance is now set up for you, but you will need to go through
 the phpWebSite "setup" process.

 Database Connection Information for this VM:

          Database Type: [either MySQL or PostgreSQL, both work]
          Database Name: phpwebsite
          Database User: phpwebsite
      Database Password: phpwebsite
     Host Specification: localhost

 So head on over to http://localhost:7479 and get started!

===============================================================================
USAGE
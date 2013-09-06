#!/bin/bash

# Build script that automates the clean compilation of new phar file.

PWD=`pwd`
cd /tmp
TMP_DIR=`mktemp -d`
echo "Created temp directory $TMP_DIR."
cd $TMP_DIR
git clone https://github.com/zendtech/ZendServerSDK.git
cd ZendServerSDK/
wget http://getcomposer.org/composer.phar
php composer.phar install --no-dev
php bin/create-phar.php 

git commit -a -m "Compiled new phar file."
if [ $? == 0 ]; then
	git push origin master
else 
	echo "Nothing to commit" 	 
fi

read -p "Do you want to delete the temp directory(Y/n)?" RESULT
if [ "$RESULT" eq "Y"]; then
	rm -rf $TMP_DIR	
fi 
cd $PWD
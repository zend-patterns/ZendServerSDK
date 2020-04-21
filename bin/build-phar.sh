#!/bin/bash

PWD=`pwd`

DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
APP_BASE_DIR=$(dirname $DIR)

cd $APP_BASE_DIR

# Build script that automates the clean compilation of new phar file.

BRANCH=`git rev-parse --abbrev-ref HEAD`
if [ "$BRANCH" == "" ]; then
	read -p "Which branch to use(master/composer)?" BRANCH
fi

echo "Using branch: $BRANCH";

cd /tmp
TMP_DIR=`mktemp -d`
echo "Created temp directory $TMP_DIR."
cd $TMP_DIR
git clone $APP_BASE_DIR
cd ZendServerSDK/
git checkout $BRANCH
wget http://getcomposer.org/composer.phar
php composer.phar global require fxp/composer-asset-plugin --no-plugins
php composer.phar install --no-dev
# Update the library to the latest git version from master
(cd vendor/zenddevops/webapi/; git pull origin master)
# Manually fix the zend-stdlib issue
(cd vendor/zendframework/zend-stdlib/; patch -p1 < ../../../.patches/zend-stdlib.patch)
php -d phar.readonly=0 bin/create-phar.php 

read -p "Do you want to commit-n-push the newly compiled phar file (Y/n)?" RESULT
if [ "$RESULT" != "n" ]; then
	git commit -a --amend
	if [ $? -eq 0 ]; then
		git push https://github.com/zend-patterns/ZendServerSDK.git $BRANCH
		if [ $? -ne 0 ]; then
			read -p "Do you want to force-push (Y/n)?" RESULT
			if [ "$RESULT" != "n" ]; then
				git push -f https://github.com/zend-patterns/ZendServerSDK.git $BRANCH
			fi
		fi 
	else 
		echo "Nothing to commit" 	 
	fi	
fi

read -p "Do you want to delete the temp directory(Y/n)?" RESULT
if [ "$RESULT" != "n" ]; then
	rm -rf $TMP_DIR	
fi 
cd $PWD

Installation
============


Stable Version
--------
You can copy locally the latest stable in a stand-alone file from:
https://github.com/zendtech/ZendServerSDK/raw/master/bin/zs-client.phar

Development Version
--------
Clone the latest source code
```
git clone https://github.com/zendtech/ZendServerSDK.git
```

Then change the directory to the newly created one
```
cd ZendServerSDK
```
	
Install composer
```
wget http://getcomposer.org/composer.phar
```

Get all dependant packages.
```
php composer.phar install --no-dev
```


Usage
============

Stable Version
---------

Run the phar file with --help to see the available commands:
```
php zs-client.phar  --help
```

See below for more options. 
Notice: When using the stable version remember to use zs-client.phar instead of bin/zs-client.php.

Development Version
---------
Run the following, from the directory where this file is located,  to see all commands:

```
php bin/zs-client.php  --help
```

If you want to see information about certain command only, then run:

```
php bin/zs-client.php  <commandName> --help
```

Compile
============
You can pack the source code into one stand-alone file that php can read. 
Run the following command to produce the zs-client.phar file.

```
php bin/create-phar.php
```

The generated file should be saved under bin/zs-client.phar. You can copy it
and use it without the need to have the other PHP files.

Use Cases
============

Adding Target
-------------
A target is representing the information needed to connect to a Zend Server.
Every target contains unique name and must have URL that points to
the location of the Zend Server, WebAPI key and secret and optionally a target 
can contain information about the version of Zend Server.

To add a target run the following command:
```
php bin/zs-client.php addTarget --target="<put-here-unique-name>" \
                                --zskey="<put-here-the-webapi-key-name>" \
                                --zssecret="<put-here-the-webapi-key-hash>" \
                                --zsurl="<(optional)put-here-valid-url>" \
                                --zsver="<(optional)put-here-the-version>"
```
To update a target run the command with the same --target value and provide the 
new values.

The information about the available targets is saved in the home directory of 
the current user in a file named .zsapi.ini.

Deploying PHP application
-------------
You have a PHP application that you want to deploy to Zend Server. 
In order to use the deployment you will have to enable deployment support,
create a package and upload it to the remote server.

Below are the steps that you need to take:

### Enable Deployment Support
```
php bin/zs-client.php createZpk --folder="<folder-where-the-PHP-code-is>"
```

This will add two new files in the specified folder: deployment.xml and deployment.properties.

### Configure the Deployment
Using Zend Studio 10 or normal text editor edit the deployment.xml file and change 
the XML data to match your application name, version, etc.

### Create Package
Run the following command.
```
php bin/zs-client.php packZpk --folder="<folder-where-the-PHP-code-is>" --destination="<folder-where-the-package-will-be-created>"
```
It will output the name of the newly created package file. You have to use this name to install
or update an existing application on Zend Server. If you want to use other name for
the output file you can use the --name="{desired-zpk-name}" option.

#### Composer Integration ####
There is experimental integration with composer that allows you to download the dependant packages, as described in composer.json,
download them and convert them to zpk files. The above command with composer integration wull look like this

```
php bin/zs-client.php packZpk --folder="<folder-where-the-PHP-code-is>" \
			      --destination="<folder-where-the-package-will-be-created>" \
                              --composer 2>/dev/null
```
The result from the command will be list of packages in the order that they have to be installed (top first, bottom last). 
Example:

/tmp/zendframework.zend-stdlib-2.2.4.zpk
/tmp/zendframework.zend-console-2.2.4.zpk
/tmp/zendframework.zend-file-2.2.4.zpk
/tmp/zendframework.zend-mvc-2.2.4.zpk
/tmp/zendframework.zend-text-2.2.4.zpk
/tmp/zendframework.zend-math-2.2.4.zpk
/tmp/zendframework.zend-json-2.2.4.zpk
/tmp/zendframework.zend-serializer-2.2.4.zpk
/tmp/zenddevops.webapi-dev-dev.zpk
/tmp/AddYourUniqueApplicationName-1.0.0.zpk

### Deploy Package
Run the following command to install a package.
```
php bin/zs-client.php installApp --zpk="<location-of-the-zpk-file>" \
                                 --target="<the-name-of-the-target>" \
                                 --baseUri="<baseUri>"
```
You can use the same command to update a package. User parameters during the 
installation can be passed using --userParams="{provide-params-as-query-string}".
For example if you want to pass parameter APPLICATION_ENV and DB_TYPE then you can 
use the following 
```
php bin/zs-client.php installApp --zpk="<location-of-the-zpk-file>" \
                                 --target="<the-name-of-the-target>" \
                                 --baseUri="<baseUri>" \
                                 --userParams="APPLICATION_ENV=staging&DB_TYPE=mysql"
```

#### Deploy Multiple Packages
If you use the composer integration then packZpk can create multiple packages, instead of one. Below is a suggestion how you can
deploy these newly created packages in the correct order.

```
ZPKS=`php bin/zs-client.php packZpk --folder="<folder-where-the-PHP-code-is>" \
			      	        --destination="<folder-where-the-package-will-be-created>" \
	                                --composer 2>/dev/null`;

for i in $ZPKS; do 
	php bin/zs-client.php installApp --zpk="$i" \
                                 --target="<the-name-of-the-target>" \
                                 --baseUri="<baseUri>" \
                                 --userParams="APPLICATION_ENV=staging&DB_TYPE=mysql"
done;
```


For questions and feedback write to slavey (at) zend DOT com.

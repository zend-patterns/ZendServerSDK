Table of Contents
=================
- [Intro](#intro)
- [Requirements](#requirements)
- [Installation](#installation)
- [Contributing](#contributing)
- [Usage](#usage)
- [Use Cases](#use-cases)
- [HTTP tuning](#http-tuning)
- [Output formats](#output-formats)
- [Providing array values](#providing-array-values)
- [Deployment properties syntax](#deployment-properties-syntax)
- [Feedback](#feedback)

Intro
====
ZendServerSDK, aka zs-client, is a command line application that communicates with
Zend Server's WebAPI. It can run on all modern Operating Systems(OS) such
as Linux, Mac OS X and Windows.

Requirements
============
PHP version >=5.3.3 with enabled phar module. 

Installation
============

You can copy locally the latest stable version as a stand-alone file from:
https://github.com/zend-patterns/ZendServerSDK/raw/master/bin/zs-client.phar

On Linux you can execute the following command:

```sh
wget https://github.com/zend-patterns/ZendServerSDK/raw/master/bin/zs-client.phar
```

Contributing
===========
[![Build Status](https://travis-ci.org/zend-patterns/ZendServerSDK.svg?branch=master)](https://travis-ci.org/zend-patterns/ZendServerSDK)
If you wish to contribute to the project, please read the 
[CONTRIBUTING.md](CONTRIBUTING.md) file.

Usage
============

Run the phar file with --help to see the available commands:
```sh
php zs-client.phar  --help
```

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
php bin/zs-client.phar addTarget --target="<put-here-unique-name>" \
                                --zskey="<put-here-the-webapi-key-name>" \
                                --zssecret="<put-here-the-webapi-key-hash>" \
                                --zsurl="<(optional)put-here-valid-url>" \
                                --zsversion="<(optional)put-here-the-version>"
```
To update a target run the command with the same --target value and provide the 
new values.

zsurl: if not specified, the value `http://localhost:10081` is used.
zsversion: if not specified, the latest Zend Server version will be used.

The information about the available targets is saved in the home directory of 
the current user in a file named .zsapi.ini.

Using Targets
-------------
Once a target has been defined, specify it in subsequent commands by adding
its unique name to the argument list, as in this example:

```
php bin/zs-client.phar monitorGetIssuesListPredefinedFilter \
                             --target="<unique-name-from-addTarget-command>" \
                             --filterId="Resources Issues"
```

Deploying PHP application
-------------
You have a PHP application that you want to deploy to Zend Server. 
In order to use the deployment you will have to enable deployment support,
create a package and upload it to the remote server.

Below are the steps that you need to take:

### Enable Deployment Support
```
php bin/zs-client.phar initZpk --folder="<folder-where-the-PHP-code-is>"
```

This will add two new files in the specified folder: deployment.xml and deployment.properties.

### Configure the Deployment
Using Zend Studio 10 or normal text editor edit the deployment.xml file and change 
the XML data to match your application name, version, etc.

### Create Package
Run the following command.
```
php bin/zs-client.phar packZpk --folder="<folder-where-the-PHP-code-is>" --destination="<folder-where-the-package-will-be-created>"
```
It will output the name of the newly created package file. You have to use this name to install
or update an existing application on Zend Server. If you want to use other name for
the output file you can use the --name="{desired-zpk-name}" option.

#### Composer Integration ####
There is experimental integration with composer that allows you to download the dependant packages, as described in composer.json,
download them and convert them to zpk files. The above command with composer integration will look like this

```
php bin/zs-client.phar packZpk --folder="<folder-where-the-PHP-code-is>" \
			      --destination="<folder-where-the-package-will-be-created>" \
                              --composer
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
php bin/zs-client.phar installApp --zpk="<location-of-the-zpk-file>" \
                                 --target="<the-name-of-the-target>" \
                                 --baseUri="<baseUri>"
```
You can use the same command to update a package. User parameters during the 
installation can be passed using --userParams="{provide-params-as-query-string}".
For example if you want to pass parameter APPLICATION_ENV and DB_TYPE then you can 
use the following 
```
php bin/zs-client.phar installApp --zpk="<location-of-the-zpk-file>" \
                                 --target="<the-name-of-the-target>" \
                                 --baseUri="<baseUri>" \
                                 --userParams="APPLICATION_ENV=staging&DB_TYPE=mysql"
```
#### Deploy Multiple Packages
If you use the composer integration then packZpk can create multiple packages, instead of one. Below is a suggestion how you can
deploy these newly created packages in the correct order.

```
ZPKS=`php bin/zs-client.phar packZpk --folder="<folder-where-the-PHP-code-is>" \
			      	        --destination="<folder-where-the-package-will-be-created>" \
	                                --composer`;

for i in $ZPKS; do 
	php bin/zs-client.phar installApp --zpk="$i" \
                                 --target="<the-name-of-the-target>" \
                                 --baseUri="<baseUri>" \
                                 --userParams="APPLICATION_ENV=staging&DB_TYPE=mysql"
done;
```

HTTP tuning
============

### Changing Connection Timeout
In some cases we may expect slower communication between the client and the server.
In that case we can set explicitly the http timeout to a bigger value. The example below shows how to set it to 40 seconds.

```
php bin/zs-client.phar getSystemInfo --target="<name-of-the-target> \
                                    --http="timeout=40" 

```

### Accepting Self-Signed SSL Certificates
In most cases the HTTPS access to your Zend Server will use self-signed certificate. 
In order to instruct the client to accept the SSL certificate you can do the following.

```
php bin/zs-client.phar getSystemInfo --target="<name-of-the-target> \
                                    --http="sslverify=0" 

```

### Combining Multiple HTTP options
If you want to combine multiple HTTP options in the same request then you can format the value of the http parameter as a valid 
HTTP query string. Request with timeout of 40 seconds and acceptance of self-signed certificates will look like this.
```
php bin/zs-client.phar getSystemInfo --target="<name-of-the-target> \
                                    --http="timeout=40&sslverify=0"
```

### Persisting the HTTP Options
If you want to keep the http options saved to a target then when defining or updating the target define also the http parameter. 
Format the value as valid HTTP query string. Take a look at the following example.
```
php bin/zs-client.phar addTarget --target="<name-of-the-target> \
                                --zsurl="http://x.y.z" \
                                --zskey="admin" \
                                --zssecret="<secret-hash>" \
                                --http="timeout=40&sslverify=0"
```

Output formats
==============
In the cases where the output is text zs-client supports three formats: xml, which is the default one,
json and kv(key value). To specify the desired format you have to use the --output-format parameter. 

Example:
```
php bin/zs-client.phar getSystemInfo --target=zserver --output-format=kv
```

Will return
```
status=Error
edition=ZendServerCluster
zendServerVersion=7.0.0
supportedApiVersions[0]=application/vnd.zend.serverapi;version=1.2
supportedApiVersions[1]=application/vnd.zend.serverapi;version=1.3
...
supportedApiVersions[6]=application/vnd.zend.serverapi;version=1.8
phpVersion=5.5.13
operatingSystem=Linux
deploymentVersion=2.0
serverLicenseInfo[status]=OK
...
serverLicenseInfo[nodeLimit]=100
serverLicenseInfo[edition]=ENTERPRISE
...
managerLicenseInfo[evaluation]=
````

Providing array values
======================
Some commands accept arrays as arguments. 

For example configurationExtensionsOn allows you turn on multiple extensions at
the same time. The code below turns on bcmath and tidy:
```
php bin/zs-client.phar configurationExtensionsOn --target=zserver --extensions=bcmath,tidy
``` 

Notice the syntax of the extensions value. It is a list of items having comma as delimiter.
If for some reason you want to replace the comma with another delimiter this can be done by 
adding the new delimiter enclosed in smaller than (<) and bigger than (>). 
In the example below we use semicolon as delimiter:
```
php bin/zs-client.phar configurationExtensionsOn --target=zserver --extensions=bcmath;tidy<;>
``` 

If needed you  can pass more complicated array data. For example a PHP array like the one below ...
```php
$data = array (
   'x' => array(
      'a' => 1,
      'b' => 2,
   ),
   y => 3
);
```

... can be represented in the command line using the following syntax.
```
php bin/zs-client.phar installApp --userParams='x[a]=1&x[b]=2&y=3' --target=zserver ...
```

Here the default delimiter is &. If you want to use custom delimiter, 
semicolon for example, then you can specify it similar to the previous example:
```
php bin/zs-client.phar installApp --userParams='x[a]=1;x[b]=2;y=3<;>' --target=zserver ...
```

Notice about delimiters: Choose your delimiter wisely. 
* Choose custom delimiter only if the default ones ("," and "&") do not work for you.
* A delimiter should not be special character that is interpreted by your shell.
* A delimiter should not be special character that is removed silently by your shell.
* A delimiter should not be one of the characters that are already in the data.

Deployment properties syntax
============================
In the `deployment.properties` file one can specify the files that will become part of the
application or part of the deployment scripts. 
Read [this document](https://github.com/zendtech/zendserver-sdk-java/wiki/Deployment-Properties-File) for more information.
Make sure to read about the way [scriptsdir.includes](https://github.com/zend-patterns/ZendServerSDK/issues/60) values are processed.  

For application excludes we support following wildcard syntax:

* `**/{something}` - will exclude all entries that have base name {something}, 
  no matter if they are files or folders, 
  Example: **/.svn excludes all folders and files with the name `.svn`. This is true
  for `/.svn`, `public/.svn` or `module/folder-with-file/.svn`. 
  If the entry is folder then the content of that folder and sub-folders will be excluded too.
* `*{something}` - will exclude all entries ending with {something} in their name.
   Example: `*.fla`  will exclude all files ending with `.fla`, like  `video.fla`.
   or directories, like `more.fla`.
   
Feedback
========
For questions and feedback write to slavey (at) zend DOT com.

 

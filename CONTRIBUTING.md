# CONTRIBUTING

## RESOURCES

If you wish to contribute to ZendServerSDK, please be sure to read the following 
resources:

 -  [Coding Standards](https://github.com/zendframework/zf2/wiki/Coding-Standards)
 We use the same coding standards as Zend Framework 2.
 -  [Git Pre-commit Hook](https://github.com/zendframework/zf2/blob/master/README-GIT.md#pre-commit-hook-optional)
 This hook will make sure that the code that you have changed is meeting the coding standards.


If you are working on new features, or refactoring an existing
component, please [create a proposal](https://github.com/zend-patterns/ZendServerSDK/issues/new).

## REPORTING ISSUES

To report an issue, please, use our [issue tracker on github](https://github.com/zend-patterns/ZendServerSDK/issues).

If you have found a potential security issue, please **DO NOT** report it on the public
issue tracker: send it to us at [slavey@zend.com](mailto:slavey@zend.com) instead.
We will work with you to verify the vulnerability and patch it as soon as possible.

When reporting issues, please provide the following information:

- Component(s) affected
- A description indicating how to reproduce the issue
- (Relevant to security issues) A summary of the security vulnerability and impact

## DEVELOPMENT CODE

Clone the latest source code
```sh
git clone https://github.com/zend-patterns/ZendServerSDK.git
```

Then change the directory to the newly created one
```sh
cd ZendServerSDK
```
	
Install composer
```sh
wget http://getcomposer.org/composer.phar
```

Get all dependant packages.
```sh
php composer.phar install --no-dev
```

Run the following, from the directory where this file is located,  to see all commands:

```
php bin/zs-client.php  --help
```

If you want to see information about certain command only, then run:

```
php bin/zs-client.php  <commandName> --help
```

Run Tests
=========

To run tests:

- Get the latest source code as shown above.
- Instruct composer to install the ```dev``` requirements.
```
php composer.phar install --dev --prefer-source
```
- Run the tests via `phpunit` and the provided PHPUnit config, like in this example:

```sh
vendor/bin/phpunit -c module/Client/tests/phpunit.xml
```

Compile
============
You can pack the source code into one stand-alone file that php can read. 
Run the following command to produce the zs-client.phar file.

```sh
php bin/create-phar.php
```

The generated file should be saved under bin/zs-client.phar. You can copy it
and use it without the need to have the other PHP files.

## CONTIBUTE NEW CODE
If you want to contribute new changes to the code the fastest way will be to [fork](https://github.com/zend-patterns/ZendServerSDK/#fork-destination-box) our 
repository then make the changes in the fork and create a [pull request](https://help.github.com/articles/using-pull-requests/). 

Make sure that you have PHPUnit tests that cover the changed code.
Pull requests with bad coding style and missing PHPUnit tests are more likely
to be rejected.


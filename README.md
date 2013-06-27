Installation
-------------

Run 

```
php composer.phar install
```

to get the dependant packages.


Usage
-----
Run the following, from the directory where this file is located,  to see all commands:

```
php bin/zs-client.php  --help
```

If you want to see information only about certain command then run:

```
php bin/zs-client.php  <commandName> --help
```

Compile
-------
You can pack the source code into one stand-alone file that php can read. 
Run the following command to produce the zs-client.phar file.

```
php bin/create-phar.php
```

The generated file should be saved under bin/zs-client.phar. You can copy it
and use it without the need to have the other PHP files.

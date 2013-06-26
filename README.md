== Installation ==
Run 

--
php composer.phar install
--

to get the dependant packages.

== Run ==
From the directory where this file is located run

--
php bin/zs-client.php 
--

This will give you list of available commands. 

== Usage ==
Run the following to see all commands:

--
php bin/zs-client.php  --help
--

If you want to see information only about certain command then run:

--
php bin/zs-client.php  <commandName> --help
--

== Compile ==
You can pack the source code into one stand-alone file that php can read. 
Run the following command to produce the zs-client.phar file.

--
php bin/create-phar.php
--

The generated file should be saved under bin/zs-client.php. You can copy it
and use it without the need to have the other PHP files.
## Introduction ##
The zs-client tool allows you to automate a lot of tasks needed to:
- Bootstrap Zend Server
- Adding new target
- Add it to a cluster
- Configure it
- Deploy an application
- Do more
	
With its help you can automate the complete delivering and configuring of a machine.

## Bootstrap Zend Server ##
Your boss told you that he needs new machine with Zend Server for staging or production.
What you can do is use tools like Chef, Puppet, Amazon EC or VMware instances to provision
a brand new machine with newly installed Zend Server.

Once you have the newly installed Zend Server you can start using zs-client tool to bootstrap it.

From a remote or local machine you can execute:
```
php zs-client.phar bootstrapSingleServer --zsurl=http://<ip-of-zend-server>:10081 \
                                         --production \
                                         --adminPassword="<your-most-secret-pass>" \
                                         --orderNumber="<order-number-from-zend>" \
                                         --licenseKey="<order-key-from-zend>" \
                                         --acceptEula --simple-output
```
This command will set the admin password, the order number, the license key and production settings.
As a result you will get back web api key and secret that you can use further to communicate remotely with this Zend Server.

##Adding new target##
You can get a Web API key for a server either by bootstrapping it, or if it was already boostrapped
you can go to the web interface and from there to Administration->WebAPI to see list with available keys.

If you don't want to provide constantly in the command line the
Zend Server URL, key and secret you can save them as a named target.  
```
php zs-client.phar addTarget --target="new-production-server" \
                             --zsurl="http://<ip-of-zend-server>:10081" \
                             --zskey="<the-key-that-bootstrap-returned>" \
                             --zssecret="<the-secret-that-bootstrap-returned>"
```
This command will create a new target called "new-production-server" that can be used for further
communication with the server. The information is stored in the local home directory in a file with name .zsapi.ini.

## Add it to a cluster ##
Once you have defined a target description from your new Zend Server you can add it to an existing cluster.
A pre-condition is to know the information about the MySQL server where the cluster is connected and valid credentials for it. 
This can be done using the following command:
```
php zs-client.phar serverAddToCluster --target="new-production-server" \
                                      --serverName="server101" \
                                      --dbHost="<mysql5-host>" \
                                      --dbUsername="<existing-db-user>" \
                                      --dbPassword="<db-pass-for-the-existing-user>" \
                                      --nodeIp="<ip-of-the-ZendServerMachine-to-be-added" \
                                      --dbName="<monitoring-db-name>"	  

```
Once the new Zend Server has joined the cluster it will have the same configuration settings as the other Zend Servers in the cluster.

You can see all commands related to server and cluster management by typing:

```
php zs-client.phar command:server

```

## Configure it ##
If you need to configure a server you can use one of the many configuration commands. 
To get an idea about them run:
```
php zs-client.phar command:configuration
```

If you want to set a specific directive you can take a look at the following example
```
php zs-client.phar configurationStoreDirectives --directives="date.timezone=Europe/Berlin&allow_url_include=Off" \
                                                --target="new-production-server"
```

If you forget the correct parameters for a command just add the --help switch. Below is an example for the 
configurationStoreDirectives command.

```
php zs-client.phar configurationStoreDirectives --help
```

If you want to enable specific configuration module you can take a look at the following example
```
php zs-client.phar configurationExtensionsOn --extensions="mysql,gd" \
                                             --target="new-production-server"
```

If the server is part of a cluster, then the changes will be propagated to
the other Zend Servers in the cluster. 

## Deploy an application ##
To deploy an application to a Zend Server you can use the high-level installApp command. It will
try to install the zpk file and if the application was already installed it will try to update it 
with the new version.

```
php zs-client.phar installApp --zpk=/<path-to>/application.zpk \
                              --baseUri="http://<application-host-name>/path" \
                              --userParams="APPLICATION_ENV=production&DB_NAME=test&test[a]=1&test[b]=2" \
                              --target="new-production-server"
```
In the command above the value for userParams is valid HTTP query string. You can use this format to pass parameters
that are described to accept associative arrays as values. Using comma separated values is useful for passing values that
are described arrays or index arrays.

If the server is part of a cluster this application will be installed or updated on all other servers in the cluster. 
Event better: if you add a new server to the cluster then all deployed applications in the cluster will be deployed to the new server
and you do not need to do anything else.

## Do more ##
There a lot more commands in the Zend Server WebAPI that can help you control all aspects of your server/cluster installation.
To list the groups in which the commands are organized type:

```
php zs-client.phar
```

If you want to get information about all available commands do:
```
php zs-client.phar command:all
```

Additional information about all available WebAPI commands can be found here:

http://files.zend.com/help/Zend-Server-6/zend-server.htm#supported_methods.htm 

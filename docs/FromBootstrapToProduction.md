## Introduction ##
The zs-client tool allows you to automate a lot of tasks needed to:
	- Bootstrap Zend Server
	- Adding new target
	- Add it to a cluster
	- Configure it
	- Deploy an application
	
With it help you can automate the complete delivering and configuring of a machine.

## Bootstrap Zend Server ##
You boss told you that he need new machine with Zend Server for staging or production.
What you can do is use tools like Chef, Puppet, Amazon EC or VMware instances to provision
a brand new machine with newly installed Zend Server.

Once you have the newly installed Zend Server you can start using zs-client tool to bootstrap it.

From a remote or local machine you can execute:
```
php zs-client.phar bootstrapSingleServer --zsurl=http://<ip-of-zend-server>:10081 --production --adminPassword="<your-most-secret-pass>" \
	                                      --orderNumber="<order-number-from-zend>" --licenseKey="<order-key-from-zend>" --acceptEula --simple-output
```
This command will set the admin password, the orderNumber and license key and will use production environment settings.
As a result you will get back web api key and secret that you can use further to communicate remotely with this Zend Server.

## Adding new target ## 
You can get a Web API key for a server either by bootstrapping it, or if it was already boostrapped
you can go to the web interface and from there to Administration->WebAPI to see list with available keys.

If you don't want to provide constantly in the command line the
Zend Server URL, key and secret you can save them as a named target.  
```
php zs-client.phar addTarget --target="new-production-server" --zsurl="http://<ip-of-zend-server>:10081" \
							 --zskey="<the-key-that-bootstrap-returned>" --zssecret="<the-secret-that-bootstrap-returned>"
```
This command will create a new target called "new-production-server" that can be used for further
communication with the server. The information is stored in the local home directory in a file with name .zsapi.ini.

## Add it to a cluster ##
Once you have defined a target description from your new Zend Server you can add it to an existing cluster.
A pre-condition is to know the information about the MySQL server where the cluster is connected. As long as valid credentials for it. 
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

## Configure it ##
If you need to configure a serve you can use one of the many configuration commands. 
To get an idea about them run:
```
php zs-client.phar command:configuration
```

If you want to set a specific directive you can take a look at the following example
```
php zs-client.phar configurationStoreDirectives --directives="date.timezone=Europe/Berlin&allow_url_include=Off"\
												--target="new-production-server"
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
php zs-client.phar installApp --zpk=/<path-to>/application.zpk --baseUri="http://<application-host-name>/path" \
		   --target="new-production-server" \
		   --userParams="APPLICATION_ENV=production&DB_NAME=test&test[a]=1&test[b]=2"
```


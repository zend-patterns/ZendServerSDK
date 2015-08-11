<?php
return array(
        'controllers' => array(
                'invokables' => array(
                        'webapi-target-controller' => 'Client\Controller\TargetController',
                        'webapi-zpk-controller' => 'Client\Controller\ZpkController',
                        'webapi-app-controller' => 'Client\Controller\AppController',
                        'webapi-api-controller' => 'Client\Controller\ApiController',
                        'webapi-lib-controller' => 'Client\Controller\LibController',
                        'webapi-plugin-controller' => 'Client\Controller\PluginController',
                        'client-update-controller' => 'Client\Controller\UpdateController',
                        'client-manual-controller' => 'Client\Controller\ManualController',
                 )
        ),
        'console' => array(
                'router' => array(
                        'routes' => array(
                            'addTarget' => array(
                                'options' => array(
                                        'route' => 'addTarget --target= [--zsurl=] --zskey= --zssecret= [--zsversion=] [--http=]',
                                        'defaults' => array(
                                        'controller' => 'webapi-target-controller',
                                        'action' => 'add',
                                        'zsurl'     => "http://localhost:10081",
                                    ),
                                    'arrays' => array(
                                          'http'
                                    ),
                                    'info' => array(
                                        'This command has to be executed first if you do not want to pass always the zskey zssecret and zsurl.',
                                        array('--target', 'The unique name of the target'),
                                        array('--zsurl','The Zend Server URL. If not specified then it will be http://localhost:10081'),
                                        array('--zskey', 'The name of the API key'),
                                        array('--zssecret', 'The hash of the API key'),
                                        array('--http', 'Optional array with additional HTTP client parameters. Example: --http="timeout=60&sslverifypeer=0" ')
                                    ),
                                    'group'=>'target',
                                    'ingore-target-load' => true,
                                )
                            ),
                            'updateTarget' => array(
                                'options' => array(
                                    'route' => 'updateTarget --target= [--zsurl=] [--zskey=] [--zssecret=] [--zsversion=] [--http=]',
                                    'defaults' => array(
                                        'controller' => 'webapi-target-controller',
                                        'action' => 'update',
                                    ),
                                    'arrays' => array(
                                        'http'
                                    ),
                                    'info' => array(
                                        'Updates the settings of existing target.',
                                        array('--target', 'The unique name of the target'),
                                        array('--zsurl','The Zend Server URL. If not specified then it will be http://localhost:10081'),
                                        array('--zskey', 'The name of the API key'),
                                        array('--zssecret', 'The hash of the API key'),
                                        array('--http', 'Optional array with additional HTTP client parameters. Example: --http="timeout=60&sslverifypeer=0" ')
                                    ),
                                    'group'=>'target',
                                    'no-target' => true,
                                )
                            ),
                            'removeTarget' => array(
                                'options' => array(
                                    'route' => 'removeTarget --target=',
                                    'defaults' => array(
                                        'controller' => 'webapi-target-controller',
                                        'action' => 'remove',
                                    ),
                                    'info' => array(
                                        'Removes a target from the list. If the target does not exist the shell exit code is 1.',
                                        array('--target', 'The unique name of the target'),
                                    ),
                                    'group'=>'target',
                                    'no-target' => true,
                                )
                            ),
                            'removeAllTargets' => array(
                                'options' => array(
                                    'route' => 'removeAllTargets',
                                    'defaults' => array(
                                        'controller' => 'webapi-target-controller',
                                        'action' => 'removeAll',
                                    ),
                                    'info' => array(
                                        'Removes all defined targets.',
                                    ),
                                    'group'=>'target',
                                    'no-target' => true,
                                )
                            ),
                            'listTargets' => array(
                                'options' => array(
                                    'route' => 'listTargets',
                                    'defaults' => array(
                                        'controller' => 'webapi-target-controller',
                                        'action' => 'list',
                                    ),
                                    'info' => array(
                                        'List all defined targets',
                                    ),
                                    'group'=>'target',
                                    'no-target' => true,
                                )
                            ),
                            'targetFileLocation' => array(
                                'options' => array(
                                    'route' => 'targetFileLocation',
                                    'defaults' => array(
                                        'controller' => 'webapi-target-controller',
                                        'action' => 'location',
                                    ),
                                    'info' => array(
                                        'Shows the location of the file used to save the target information.',
                                    ),
                                    'group'=>'target',
                                    'no-target' => true,
                                )
                            ),
                            'installApp' => array(
                                'options' => array(
                                    'route' => 'installApp --zpk= --baseUri= [--userParams=] [--userAppName=] [--createVhost=] [--defaultServer=] [--ignoreFailures=] [--target=] [--zsurl=] [--zskey=] [--zssecret=] [--zsversion=] [--http=] [--wait]',
                                    'defaults' => array(
                                        'controller' => 'webapi-app-controller',
                                        'action' => 'install'
                                    ),
                                    'group'=>'high-level',
                                    'async' => true,
                                    'info' => array(
                                        'This command installs or updates an application',
                                        array('--zpk', 'The zpk package file'),
                                        array('--baseUri','The baseUri of where the application will be installed'),
                                        array('--userParams', 'User parameters that have to formated as a query string'),
                                        array('--userAppName', 'Name of the application'),
                                        array('--createVhost', 'Create a virtual host based on the base URL (if the virtual host wasn\'t already created by Zend Server). The default value is FALSE.'),
                                        array('--defaultServer', "Deploy the application on the default server. The provided base URL will be ignored and replaced with '<default-server>'. ".
                                                                "If this parameter and createVhost are both used, createVhost will be ignored. ".
                                                                "The default value is FALSE."),
                                        array('--ignoreFailures', 'Ignore failures during staging if only some servers report failures. If all servers report failures the operation will fail in any case. '.
                                                                  'The default value is FALSE, meaning any failure will return an error.'),
                                        array('--target', 'The unique name of the target'),
                                        array('--zsurl','The Zend Server URL. If not specified then it will be http://localhost:10081'),
                                        array('--zskey', 'The name of the API key'),
                                        array('--zssecret', 'The hash of the API key'),
                                        array('--zsversion', 'The major Zend Server version. Ex: 6.1, 6.0 or 5.6'),
                                        array('--http', 'Allows you to set http connection parameters'),
                                        array('--wait', 'If this option is present then the client will wait until the operation finishes successfully on all servers.'.
                                                        'By default this option is not present which means that the client will return results and exit as soon as the server has reported that it started to handle the task.'),
                                    ),
                                    'arrays' => array(
                                        'userParams',
                                        'http'
                                    ),
                                    'files' => array(
                                        'zpk',
                                    )
                                )
                            ),
                            'createZpk' => array(
                                'options' => array(
                                    'route' => 'createZpk [--folder=]',
                                    'defaults' => array(
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'create',
                                        'folder' => '.',
                                    ),
                                    'info' => array(
                                        'DEPRECATED: Use initZpk instead!',
                                        'Adds ZPK support to existing PHP project',
                                        array('--folder','Folder where the source code is located')
                                    ),
                                    'files' => array(
                                        'folder'
                                    ),
                                    'no-target' => true,
                                    'group' => 'deprecated'
                                ),
                            ),
                            'initZpk' => array(
                                'options' => array(
                                    'route' => 'initZpk [--folder=]',
                                    'defaults' => array(
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'init',
                                        'folder' => '.',
                                    ),
                                    'info' => array(
                                        'Adds ZPK support to existing PHP project',
                                        array('--folder','Folder where the source code is located')
                                    ),
                                    'files' => array(
                                        'folder'
                                    ),
                                    'no-target' => true,
                                    'group' => 'packaging'
                                ),
                            ),
                            'verifyZpk'   => array(
                                'options' => array(
                                    'route' => 'verifyZpk --from=',
                                    'defaults' => array(
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'verify',
                                    ),
                                    'info' => array(
                                        'Verifies the deployment.xml and the existence of the files that have to be packed as described in the deployment.properties file.',
                                        array('--from','Folder where the source code and the deployment.xml is located OR existing ZPK file.'),
                                    ),
                                    'files' => array(
                                        'from'
                                    ),
                                    'no-target' => true,
                                    'group' => 'packaging'
                                ),
                            ),
                            'fixZpk'   => array(
                                    'options' => array(
                                        'route' => 'fixZpk --from=',
                                        'defaults' => array(
                                            'controller' => 'webapi-zpk-controller',
                                            'action' => 'fix',
                                        ),
                                        'files' => array(
                                            'from',
                                        ),
                                        'info' => array(
                                            'Fixes the deployment.xml.',
                                            array('--from','Folder where the source code and the deployment.xml is located OR existing ZPK file.'),
                                        ),
                                        'no-target' => true,
                                        'group' => 'packaging'
                                    ),
                            ),
                            'packZpk'   => array(
                                'options' => array(
                                    'route' => 'packZpk [--folder=] [--destination=] [--name=]  [--version=] [--composer] [--composer-options=] [--composer-dist-files=]',
                                    'defaults' => array(
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'pack',
                                        'folder' => '.',
                                        'destination' => '.',
                                    ),
                                    'info' => array(
                                          'Creates a ZPK package from PHP project with ZPK support',
                                          array('--folder','Folder where the source code is located'),
                                          array('--destination','Folder in which to save the created ZPK file'),
                                          array('--name','The name of the package. If not provided the name will be constructed from the name of the application and its version.'),
                                          array('--version','The version release of the package. The version release will be replace in deployment.xml before creating ZPK package.'),
                                          array('--composer','Enables experimental composer support.'),
                                          array('--composer-options','Adds composer options when running composer'),
                                          array('--composer-dist-files', 'Comma separated list of YAML .dist files containing user parameters.'),
                                    ),
                                    'arrays' => array(
                                                'composer-dist-files',
                                    ),
                                    'files' => array(
                                        'folder', 'destination','composer-dist-files',
                                    ),
                                    'no-target' => true,
                                    'group' => 'packaging'
                                ),
                            ),
                            'installLib' => array(
                                'options' => array(
                                    'route' => 'installLib --zpk= [--target=] [--zsurl=] [--zskey=] [--zssecret=] [--zsversion=] [--http=]',
                                    'defaults' => array(
                                        'controller' => 'webapi-lib-controller',
                                        'action' => 'install'
                                    ),
                                    'files' => array(
                                        'zpk',
                                    ),
                                    'group'=>'high-level',
                                    'info' => array(
                                        'This command installs a library. If the library is already installed then it does not do anything.',
                                        array('--zpk', 'The zpk package file'),
                                        array('--target', 'The unique name of the target'),
                                        array('--zsurl','The Zend Server URL. If not specified then it will be http://localhost:10081'),
                                        array('--zskey', 'The name of the API key'),
                                        array('--zssecret', 'The hash of the API key'),
                                        array('--http', 'Allows you to set http connection parameters'),
                                    ),
                                    'arrays' => array(
                                        'http'
                                    )
                                )
                            ),

                            'pharUpdate' => array(
                                    'options' => array(
                                            'route' => 'pharUpdate',
                                            'defaults' => array(
                                                    'controller' => 'client-update-controller',
                                                    'action' => 'phar'
                                            ),
                                            'group'=>'client',
                                            'no-target' => true,
                                            'info' => array(
                                                    'This command updates the phar file to the latest version.',
                                            ),
                                    ),
                            ),

                            'gettingStartedManual' => array(
                                'options' => array(
                                        'route' => 'getting-started',
                                        'defaults' => array(
                                                'controller' => 'client-manual-controller',
                                                'action' => 'gettingStarted'
                                        ),
                                        'group'=>'manual',
                                        'no-target' => true,
                                        'info' => array(
                                                'Run this command to get started with the tool',
                                        ),
                                ),
                            ),
                            'autocomplete' => array(
                                'options' => array(
                                        'route' => 'auto-complete',
                                        'defaults' => array(
                                                'controller' => 'client-manual-controller',
                                                'action' => 'autocomplete'
                                        ),
                                        'group'=>'manual',
                                        'no-target' => true,
                                        'info' => array(
                                                'Run this command to get the bash file needed for auto-completion',
                                        ),
                                ),
                            ),

                            'initPlugin' => array(
                                'options' => array(
                                    'route' => 'initPlugin [--folder=]',
                                    'defaults' => array(
                                        'controller' => 'webapi-plugin-controller',
                                        'action' => 'init',
                                        'folder' => '.',
                                    ),
                                    'info' => array(
                                        'Creates new plugin skeleton',
                                        array('--folder','Folder where the source code will be located')
                                    ),
                                    'files' => array(
                                        'folder'
                                    ),
                                    'no-target' => true,
                                    'group' => 'plugins'
                                ),
                            ),
                            'packPlugin'   => array(
                                'options' => array(
                                    'route' => 'packPlugin [--folder=] [--destination=] [--name=]',
                                    'defaults' => array(
                                        'controller' => 'webapi-plugin-controller',
                                        'action' => 'pack',
                                        'folder' => '.',
                                        'destination' => '.',
                                    ),
                                    'info' => array(
                                        'Creates a ZIP plugin package from the code in `folder`',
                                        array('--folder','Folder where the source code is located'),
                                        array('--destination','Folder in which to save the created ZIP file'),
                                        array('--name','The name of the plugin. If not provided the name will be constructed from the name of the application and its version.'),
                                    ),
                                    'files' => array(
                                        'folder', 'destination'
                                    ),
                                    'no-target' => true,
                                    'group' => 'plugins'
                                ),
                            ),
                            'installPlugin' => array(
                                'options' => array(
                                    'route' => 'installPlugin --pluginPackage= [--target=] [--zsurl=] [--zskey=] [--zssecret=] [--zsversion=] [--http=] [--wait]',
                                    'defaults' => array(
                                        'controller' => 'webapi-plugin-controller',
                                        'action' => 'install'
                                    ),
                                    'group'=>'high-level',
                                    'async' => true,
                                    'info' => array(
                                        'This command installs or updates a plugin',
                                        array('--pluginPackage', 'The package file'),
                                        array('--http', 'Allows you to set http connection parameters'),
                                        array('--wait', 'If this option is present then the client will wait until the operation finishes successfully on all servers.'.
                                            'By default this option is not present which means that the client will return results and exit as soon as the server has reported that it started to handle the task.'),
                                    ),
                                    'arrays' => array(
                                        'http'
                                    ),
                                    'files' => array(
                                        'pluginPackage',
                                    )
                                )
                            ),
                        ),
                )
        ),

        'service_manager' => array(
            'invokables' => array(
                'zpk'  => 'Client\Service\ZpkInvokable',
                'path' => 'Client\Service\PathInvokable',
                'composer' => 'Client\Service\ComposerInvokable',
                'Composer\File' => 'Client\Service\Composer\File',
             ),
             'factories' => array(
                 'target' => 'Client\Service\TargetFactory',
             )
        ),

        'zsapi' => array(
            //Target definition file
            'file' => (isset($_SERVER['HOME'])? $_SERVER['HOME']:
                        $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'] // Available on Windows
                       ).DIRECTORY_SEPARATOR.'.zsapi.ini',
        ),

        'controller_plugins' => array(
            'invokables' => array(
               'repeater' => 'Client\Controller\Plugin\Repeater'
            )
        ),

        'view_manager' => array(
           'template_path_stack' => array(
               __DIR__ .'/../views/',
            )
        ),
        'view_helpers' => array(
            'invokables' => array(
                'figlet' => 'Slk\View\Helper\Figlet',
             )
        )
);

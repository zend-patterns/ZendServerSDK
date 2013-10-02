<?php
return array (
        'controllers' => array (
                'invokables' => array (
                        'webapi-target-controller' => 'Client\Controller\TargetController',
                        'webapi-zpk-controller' => 'Client\Controller\ZpkController',
                        'webapi-app-controller' => 'Client\Controller\AppController',
                        'webapi-api-controller' => 'Client\Controller\ApiController',
                        'webapi-lib-controller' => 'Client\Controller\LibController',
                )
        ),
        'console' => array (
                'router' => array (
                        'routes' => array (
                            'addTarget' => array (
                                'options' => array (
                                        'route' => 'addTarget --target= [--zsurl=] --zskey= --zssecret= [--zsversion=]',
                                        'defaults' => array (
                                        'controller' => 'webapi-target-controller',
                                        'action' => 'add',
                                        'zsurl'     => "http://localhost:10081",
                                        'zsversion' => '6.1',
                                    ),
                                    'info' => array (
                                        'This command has to be executed first if you do not want to pass always the zskey zssecret and zsurl.',
                                        array('--target', 'The unique name of the target'),
                                        array('--zsurl','The Zend Server URL. If not specified then it will be http://localhost:10081'),
                                        array('--zskey', 'The name of the API key'),
                                        array('--zssecret', 'The hash of the API key'),
                                    ),
                                    'group'=>'target',
                                    'no-target' => true,
                                )
                            ),
                            'installApp' => array (
                                'options' => array (
                                    'route' => 'installApp --zpk= --baseUri= [--userParams=] [--userAppName=] [--createVhost=] [--defaultServer=] [--ignoreFailures=] [--target=] [--zsurl=] [--zskey=] [--zssecret=] [--zsversion=]',
                                    'defaults' => array (
                                        'controller' => 'webapi-app-controller',
                                        'action' => 'install'
                                    ),
                                    'group'=>'high-level',
                                    'info' => array (
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
                                    ),
                                    'arrays' => array (
                                        'userParams',
                                    ),
                                    'files' => array (
                                        'zpk',
                                    )
                                )
                            ),
                            'createZpk' => array(
                                'options' => array (
                                    'route' => 'createZpk [--folder=]',
                                    'defaults' => array (
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'create',
                                        'folder' => '.',
                                    ),
                                    'info' => array(
                                        'Adds ZPK support to existing PHP project',
                                        array('folder','Folder where the source code is located')
                                    ),
                                    'files' => array(
                                        'folder'
                                    ),
                                    'no-target' => true,
                                    'group' => 'packaging'
                                ),
                            ),
                            'packZpk'   => array(
                                'options' => array (
                                    'route' => 'packZpk [--folder=] [--destination=] [--name=]  [--composer] [--composer-options=]',
                                    'defaults' => array (
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'pack',
                                        'folder' => '.',
                                        'destination' => '.',
                                    ),
                                    'info' => array(
                                          'Creates a ZPK package from PHP project with ZPK support',
                                          array('folder','Folder where the source code is located'),
                                          array('destination','Folder in which to save the created ZPK file'),
                                          array('name','The name of the package. If not provided the name will be constructed from the name of the application and its version.'),
                                          array('composer','Enables rudimentary composer support.'),
                                          array('composer-options','Adds composer flags when running composer')
                                    ),
                                    'files' => array(
                                        'folder', 'destination'
                                    ),
                                    'no-target' => true,
                                    'group' => 'packaging'
                                ),
                            ),
                            'installLib' => array (
                                'options' => array (
                                    'route' => 'installLib --zpk= [--target=] [--zsurl=] [--zskey=] [--zssecret=] [--zsversion=]',
                                    'defaults' => array (
                                        'controller' => 'webapi-lib-controller',
                                        'action' => 'install'
                                    ),
                                    'files' => array (
                                        'zpk',
                                    ),
                                    'group'=>'high-level',
                                    'info' => array (
                                        'This command installs a library. If the library is already installed then it does not do anything.',
                                        array('--zpk', 'The zpk package file'),
                                        array('--target', 'The unique name of the target'),
                                        array('--zsurl','The Zend Server URL. If not specified then it will be http://localhost:10081'),
                                        array('--zskey', 'The name of the API key'),
                                        array('--zssecret', 'The hash of the API key'),
                                    ),
                                )
                            ),
                        ),
                )
        ),

        'service_manager' => array (
            'invokables' => array (
                'zpk'  => 'Client\Service\ZpkInvokable',
                'path' => 'Client\Service\PathInvokable',
                'composer' => 'Client\Service\ComposerInvokable',
             )
        ),

        'zsapi' => array(
            //Target definition file
            'file' => (isset($_SERVER['HOME'])? $_SERVER['HOME']:
                        $_SERVER['HOMEDRIVE'].$_SERVER['HOMEPATH'] // Available on Windows
                       ).DIRECTORY_SEPARATOR.'.zsapi.ini',
        )
);

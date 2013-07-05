<?php
return array (
        'controllers' => array (
                'invokables' => array (
                        'webapi-zpk-controller' => 'Client\Controller\ZpkController'
                )
        ),
        'console' => array (
                'router' => array (
                        'routes' => array (
                                'createZpk' => array(
                                'options' => array (
                                    'route' => 'createZpk [--folder=]',
                                    'defaults' => array (
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'create',
                                        'no-target' => true,
                                        'folder' => '.',
                                    ),
                                    'info' => array(
                                        'Adds ZPK support to existing PHP project',
                                        array('folder','Folder where the source code is located')
                                    ),
                                    'files' => array(
                                        'folder'
                                    )
                                ),
                            ),
                            'packZpk'   => array(
                                'options' => array (
                                    'route' => 'packZpk [--folder=] [--destination=] [--name=]',
                                    'defaults' => array (
                                        'controller' => 'webapi-zpk-controller',
                                        'action' => 'pack',
                                        'no-target' => true,
                                        'folder' => '.',
                                        'destination' => '.',
                                    ),
                                    'info' => array(
                                          'Creates a ZPK package from PHP project with ZPK support',
                                          array('folder','Folder where the source code is located'),
                                          array('destination','Folder in which to save the created ZPK file'),
                                          array('name','The name of the package. If not provided the name will be constructed from the name of the application and its version.')
                                    ),
                                    'files' => array(
                                        'folder', 'destination'
                                    )
                                ),
                            )
                        ),
                )

        ),

        'service_manager' => array (
            'invokables' => array (
                'zpk'  => 'Client\Service\ZpkInvokable',
                'path' => 'Client\Service\PathInvokable',
             )
        ),
);

#!/usr/bin/env php
<?php
/**
 * ZF2 command line tool
 *
 * @link      http://github.com/zendframework/ZFTool for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
error_reporting(E_ALL);
ini_set("display_errors", 1);
ini_set('user_agent', 'Zend Server WebAPI Client');

$basePath = dirname(__DIR__);
if(!defined('PHAR')) {
    define('CWD',getcwd());
    chdir($basePath);
}

require_once "vendor/autoload.php";

if (file_exists("config/application.config.php")) {
    $appConfig = require "config/application.config.php";
    if (!in_array('ZendServerWebApi', $appConfig['modules'])) {
        $appConfig['modules'][] = 'ZendServerWebApi';
        $appConfig['modules'][] = 'Client';
    }
} else {
    $appConfig = array(
        'modules' => array(
            'ZendServerWebApi',
            'Client',
        ),
        'module_listener_options' => array(
            'config_glob_paths'    => array(
                'config/autoload/{,*.}{global,local}.php',
            ),
            'module_paths' => array(
                './module',
                './vendor',
            ),
        ),
    );
}

Zend\Mvc\Application::init($appConfig)->run();

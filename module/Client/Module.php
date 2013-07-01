<?php
namespace Client;

use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;

class Module implements ConsoleBannerProviderInterface
{
    /**
     * (non-PHPdoc)
     *
     * @see \Zend\ModuleManager\Feature\ConfigProviderInterface::getConfig()
     */
    public function getConfig ()
    {
        $config = include __DIR__ . '/config/module.config.php';
        if(!getenv('DEBUG')) {
            $config['view_manager']['exception_message'] = <<<EOT
======================================================================
   The application has thrown an exception!
======================================================================
 :className
 :message

EOT;
        }
        return $config;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Zend\ModuleManager\Feature\AutoloaderProviderInterface::getAutoloaderConfig()
     */
    public function getAutoloaderConfig ()
    {
        return array(
                'Zend\Loader\ClassMapAutoloader' => array(
                    __DIR__ . '/autoload_classmap.php',
                ),
                'Zend\Loader\StandardAutoloader' => array(
                    'namespaces' => array(
                          __NAMESPACE__ => __DIR__ . '/src/'.__NAMESPACE__
                    )
                )
        );
    }

    /**
     *
     * @param MvcEvent $e
     */
    public function onBootstrap ($event)
    {

    }

    /**
     * (non-PHPdoc)
     *
     * @see \Zend\ModuleManager\Feature\ConsoleBannerProviderInterface::getConsoleBanner()
     */
    public function getConsoleBanner (Console $console)
    {
        return 'Zend Server Client v1.0';
    }
}

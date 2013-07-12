<?php
namespace Client;

use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Mvc\MvcEvent;

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
         $eventManager = $event->getApplication()->getEventManager();
         $eventManager->attach(MvcEvent::EVENT_ROUTE,
                array(
                        $this,
                        'postRoute'
                ), - 2);
    }

    public function postRoute($event)
    {
        $match = $event->getRouteMatch();
        if(!$match) {
            return;
        }

        $services = $event->getApplication()->getServiceManager();
        $config = $services->get('config');
        $path   = $services->get('path');

        // Translate all paths to real absolute paths
        $routeName = $match->getMatchedRouteName();
        if (isset(
                $config['console']['router']['routes'][$routeName]['options']['files'])
        ) {
            foreach ($config['console']['router']['routes'][$routeName]['options']['files'] as $param) {
                if ($value = $match->getParam($param)) {
                    $match->setParam($param, $path->getAbsolute($value));
                }
            }
        }
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

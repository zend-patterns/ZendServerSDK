<?php
namespace Client;

use Zend\ModuleManager\Feature\ConsoleBannerProviderInterface;
use Zend\Console\Adapter\AdapterInterface as Console;
use Zend\Mvc\MvcEvent;
use Zend\Config\Reader\Ini as ConfigReader;
use Zend\Http\Response as HttpResponse;

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
        if (!getenv('DEBUG')) {
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

         $eventManager->attach(MvcEvent::EVENT_FINISH,
                 array(
                         $this,
                         'preFinish'
                 ), 100);
    }

    /**
     * Manage special input parameters (arrays and files) and target
     *
     * @param MvcEvent $event
     */
    public function postRoute (MvcEvent $event)
    {
        $match = $event->getRouteMatch();
        if (! $match) {
            return;
        }
        $services = $event->getApplication()->getServiceManager();

        // [Normalize the arguments]
        $config = $services->get('config');
        $routeName = $match->getMatchedRouteName();
        if (isset(
                $config['console']['router']['routes'][$routeName]['options']['arrays'])) {
            foreach ($config['console']['router']['routes'][$routeName]['options']['arrays'] as $arrayParam) {
                if ($value = $match->getParam($arrayParam)) {
                    $data = array();
                    if (strpos($value,'&')===false & strpos($value,'=')===false) {
                        // the value is comma separated values
                        $data = explode(',',$value);
                        foreach ($data as $i=>$v) {
                            $data[$i] = trim($v);
                        }
                    } else {
                        Utils::parseString($value, $data);
                    }

                    $match->setParam($arrayParam, $data);
                }
            }
        }

        // [Translate all paths to real absolute paths]
        if (isset(
                $config['console']['router']['routes'][$routeName]['options']['files'])
        ) {
            $path = $services->get('path');
            foreach ($config['console']['router']['routes'][$routeName]['options']['files'] as $param) {
                if ($value = $match->getParam($param)) {
                    if (!is_array($value)) {
                        $match->setParam($param, $path->getAbsolute($value));
                    } else {
                        $newValue = array_map(function($v) use ($path) {
                            return $path->getAbsolute($v);
                        }, $value);
                        $match->setParam($param,$newValue);
                    }
                }
            }
        }

        // [Figure out the target]
        if (isset($config['console']['router']['routes'][$routeName]['options']['no-target'])) {
            return;
        }
        // Read the default target
        $targetConfig = $services->get('targetConfig');

        // Add manage named target (defined in zsapi.ini)
        $target = $match->getParam('target');
        if ($target) {
            try {
                $reader = new ConfigReader();
                $data = $reader->fromFile($config['zsapi']['file']);
                if (empty($data[$target])) {
                    throw new \Zend\Console\Exception\RuntimeException('Invalid target specified.');
                }
                foreach ($data[$target] as $k=>$v) {
                    $targetConfig[$k] = $v;
                }
            } catch (\Zend\Config\Exception\RuntimeException $ex) {
                throw new \Zend\Console\Exception\RuntimeException(
                        'Make sure that you have set your target first. \n
                                                                This can be done with ' .
                        __FILE__ .
                        ' add-target --target=<UniqueName> --zsurl=http://localhost:10081/ZendServer --zskey= --zssecret=');
            }
        }

        if (empty($targetConfig) &&
            ! ($match->getParam('zskey') || $match->getParam('zssecret') || $match->getParam('zsurl'))
        ) {
            throw new \Zend\Console\Exception\RuntimeException(
                    'Specify either a --target= parameter or --zsurl=http://localhost:10081/ZendServer --zskey= --zssecret=');
        }

        // optional: override the target parameters from the command line
        foreach (array(
                    'zsurl',
                    'zskey',
                    'zssecret',
                    'zsversion',
                    'http'
        ) as $key) {
                if ( ! $match->getParam($key)) {
                    continue;
                }
                $targetConfig[$key] = $match->getParam($key);
        }
    }

    /**
     *
     * @param MvcEvent $event
     */
    public function preFinish (MvcEvent $event)
    {
        $response = $event->getResponse();
        if ($response instanceof HttpResponse) {
            $response->setContent($response->getBody());
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

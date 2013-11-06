<?php
namespace Client\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Config\Writer\Ini as ConfigWriter;
use Zend\Config\Reader\Ini as ConfigReader;
use Zend\Config\Exception as ConfigException;

/**
 * Main Console Controller
 *
 * Controller that manage all CLI commands
 */
class TargetController extends AbstractActionController
{
    /**
     * Adding a API Key
     */
    public function addAction()
    {
        $appConfig  = $this->serviceLocator->get('config');
        $target = $this->getRequest()->getParam('target');

        // Read the current configuration
        $data = array();
        try {
            $reader = new ConfigReader();
            $data = $reader->fromFile($appConfig['zsapi']['file']);
        } catch(ConfigException\RuntimeException $ex) {}

        $data[$target] = array();
        foreach (array('zsurl','zskey','zssecret', 'zsversion') as $key) {
            $value = $this->getRequest()->getParam($key);
            if($value) {
               $data[$target][$key] = $value;
            }
        }

        $httpOptions = $this->getRequest()->getParam('http');
        foreach($httpOptions as $key=>$name) {
            $data[$target]['http'][$key] = $name;
        }

        $config = new ConfigWriter();
        $config->toFile($appConfig['zsapi']['file'], $data);
    }
}

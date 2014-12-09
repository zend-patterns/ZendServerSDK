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

        $apiManager = $this->getServiceLocator()->get('zend_server_api');
        $versions = $apiManager->getSupportedVersions();
        $detectedVersion = $versions[0];

        if(empty($data[$target]['zsversion'])) {
            $data[$target]['zsversion'] = $detectedVersion;
        } elseif ($data[$target]['zsversion'] < $detectedVersion) {
            error_log(sprintf("WARNING: The best version for this server is: %s. ".
                              "You are using: %s. Update your target zsversion to get best results.",
                              $detectedVersion, $data[$target]['zsversion']
                      ));
        }

        $httpOptions = $this->getRequest()->getParam('http');
        if(is_array($httpOptions)) {
            foreach($httpOptions as $key=>$name) {
                $data[$target]['http'][$key] = $name;
            }
        }

        $config = new ConfigWriter();
        $config->toFile($appConfig['zsapi']['file'], $data);
    }
}

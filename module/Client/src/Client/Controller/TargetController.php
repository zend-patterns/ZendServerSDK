<?php
namespace Client\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Config\Exception as ConfigException;
use Client\Service\TargetInvokable;

/**
 * Main Console Controller
 *
 * Controller that manage all CLI commands
 */
class TargetController extends AbstractActionController
{
    /**
     * @var TargetInvokable
     */
    protected $targetService;

    /**
     * Adding a API Key
     */
    public function addAction()
    {
        $target = $this->getRequest()->getParam('target');
        // Read the current configuration
        $data = array();
        try {
            $data = $this->getTargetService()->load();
        } catch (ConfigException $ex) {
        }

        $data[$target] = array();
        foreach (array('zsurl', 'zskey', 'zssecret', 'zsversion') as $key) {
            $value = $this->getRequest()->getParam($key);
            if ($value) {
                $data[$target][$key] = $value;
            }
        }

        $apiManager = $this->getServiceLocator()->get('zend_server_api');
        $versions = $apiManager->getSupportedVersions();
        $detectedVersion = $versions[0];

        if (empty($data[$target]['zsversion'])) {
            $data[$target]['zsversion'] = $detectedVersion;
        } elseif ($data[$target]['zsversion'] < $detectedVersion) {
            error_log(sprintf("WARNING: The best version for this server is: %s. ".
                              "You are using: %s. Update your target zsversion to get best results.",
                              $detectedVersion, $data[$target]['zsversion']
                      ));
        }

        $httpOptions = $this->getRequest()->getParam('http');
        if (is_array($httpOptions)) {
            foreach ($httpOptions as $key => $value) {
                $data[$target]['http'][$key] = $value;
            }
        }

        $this->getTargetService()->save($data);
    }

    public function updateAction()
    {
        $target = $this->getRequest()->getParam('target');
        $data = $this->getTargetService()->load();

        if (!isset($data[$target])) {
            throw new \Zend\Console\Exception\RuntimeException("Target '$target' does not exist!");
        }

        foreach (array('zsurl', 'zskey', 'zssecret', 'zsversion') as $key) {
            $value = $this->getRequest()->getParam($key);
            if ($value) {
                $data[$target][$key] = $value;
            }
        }

        $httpOptions = $this->getRequest()->getParam('http');
        if (is_array($httpOptions)) {
            foreach ($httpOptions as $key=> $value) {
                $data[$target]['http'][$key] = $value;
            }
        }

        $this->getTargetService()->save($data);
    }

    public function removeAction()
    {
        $target = $this->getRequest()->getParam('target');
        $list = $this->getTargetService()->load();
        if (isset($list[$target])) {
            unset($list[$target]);
            $this->getTargetService()->save($list);
            return;
        }

        return $this->getResponse()->setErrorLevel(1);
    }

    public function removeAllAction()
    {
        $this->getTargetService()->save(array());
    }

    public function listAction()
    {
        $list = $this->getTargetService()->load();
        $content = "Name           |            URL \n";
        $content.= "--------------------------------\n";
        foreach ($list as $name => $data) {
            $content .= "$name => {$data['zsurl']}\n";
        }

        $this->getResponse()->setContent($content);
        return $this->getResponse();
    }

    public function locationAction()
    {
        $configFile = $this->getTargetService()->getConfigFile();

        return $this->getResponse()->setContent($configFile."\n");
    }

    public function getTargetService()
    {
        if (!$this->targetService) {
            $this->targetService = $this->serviceLocator->get('target');
        }
        return $this->targetService;
    }

    public function setTargetService($targetService)
    {
        $this->targetService = $targetService;
    }
}

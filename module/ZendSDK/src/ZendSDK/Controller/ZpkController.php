<?php
namespace ZendSDK\Controller;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * Main Console Controller
 *
 * Controller that manage all CLI commands
 */
class ZpkController extends AbstractActionController
{
	/**
	 * Adds deployment support to existing PHP code
	 * @param string folder
	 */
    public function createAction()
    {
        $folder = $this->getRequest()->getParam('folder');
        $zpk = $this->serviceLocator->get('zpk');
        $zpk->create($folder);
        
        return $this->getResponse();
    }
    
    /**
     * Creates a package from existing PHP code
	 * @param string source - the source folder
	 * @param string destination - the destination folder
     */
    public function packAction()
    {
    	$folder = $this->getRequest()->getParam('folder');
    	$destination = $this->getRequest()->getParam('destination');
    	$zpk = $this->serviceLocator->get('zpk');
    	$zpkFile = $zpk->pack($folder, $destination);
    	$this->getResponse()->setContent($zpkFile);
    	
    	return $this->getResponse();
    }
}

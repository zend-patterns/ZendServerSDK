<?php
namespace Client\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class TargetFactory implements FactoryInterface
{
    /* (non-PHPdoc)
     * @see \Zend\ServiceManager\FactoryInterface::createService()
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        $config  = $serviceLocator->get('config');
        $target = new TargetInvokable();
        $target->setConfigFile($config['zsapi']['file']);
        return $target;
    }
}

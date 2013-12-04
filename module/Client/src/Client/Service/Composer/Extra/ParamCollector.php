<?php
namespace Client\Service\Composer\Extra;

use Client\Service\Composer\Extra\ParamsInterface;

class ParamCollector implements ParamsInterface
{
    private $libs;
    private $userParams;
    private $paramFactory;
    
    public function setLibs(array $libs) {
        $this->libs = $libs;    
    }
    
    public function setUserParams(array $userParams) {
        $this->userParams = $userParams;
    }
    
    public function setParamFactory(ParamFactory $paramFactory) {
        $this->paramFactory = $paramFactory;
    }
    
    public function getParams() {
        $paramFactory = $this->paramFactory;
        
        $params = array();
        foreach ($this->libs as $lib) {
            if (!$instance = $paramFactory::factory($lib, $this->userParams)) continue;
            
            $params = array_merge($params, $instance->getParams());
        }        
        
        return $params;
    }
}


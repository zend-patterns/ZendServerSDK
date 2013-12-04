<?php
namespace Client\Service\Composer\Extra\Incenteev;

use Client\Service\Composer\Extra\ParamsInterface;

class ComposerParameterHandler implements ParamsInterface
{
    private $userParams;
    
    public function setUserParams($userParams) {
        $this->userParams = $userParams;    
    }
    
    public function getParams() {
        $envMap = $this->userParams;
        array_walk(
            $envMap, 
            function(&$item, $key, $prefix) {
                $item = $prefix . strtoupper($key);
            },
            'ZS_COMPOSER_'
        );
        
        return array('incenteev-parameters' => array('env-map' => $envMap));
    }
}
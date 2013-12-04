<?php
namespace Client\Service\Composer\Extra;

class ParamFactory
{
    public static function factory($lib, $userParams = null) {
        $parts = explode('/', $lib);
        
        foreach ($parts as &$part) {
            $tmp = array();
            foreach (explode('-', $part) as $subpart) {
                $tmp[] = ucfirst($subpart);
            } 
            $part = join('', $tmp);
        }
        
        $classname = 'Client\Service\Composer\Extra' . "\\" . join('\\', $parts);
        
        if (!class_exists($classname)) {
            error_log("Cannot find Extra Data Handler for lib [$lib]");
            return;
        }
        
        $instance = new $classname();
        $instance->setUserParams($userParams);
        
        return $instance;
    }
}

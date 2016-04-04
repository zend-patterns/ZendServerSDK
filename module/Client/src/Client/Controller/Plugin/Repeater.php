<?php
namespace Client\Controller\Plugin;

use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class Repeater extends AbstractPlugin
{
    public function doUntil($callback, array $params=array(), $maxWait=1800, $sleep=2)
    {
        $start = time();
        while (true) {
            $response = call_user_func($callback, $this->controller, $params);
            if ($response) {
                return $response;
            }

            if ((time()-$start) > $maxWait) {
                throw new \Exception('The operation timed out!');
            }
            sleep($sleep);
        }
    }
}

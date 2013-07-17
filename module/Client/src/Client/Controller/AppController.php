<?php
namespace Client\Controller;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * App Console Controller
 *
 * High-Level Application Deployment CLI commands
 */
class AppController extends AbstractActionController
{
    public function installAction()
    {
        $requestParameters = array();
        $zpk     = $this->params('zpk');
        $baseUri = $this->params('baseUri');
        $userParams = $this->params('userParams', array());
        $appName    = $this->params('userAppName');
        $appId      = 0;

        $apiManager = $this->serviceLocator->get('zend_server_api');
        $zpkService = $this->serviceLocator->get('zpk');

        // validate the package
        $zpkService->validateMeta($zpk);

        if(!$appName) {
            // get the name of the application from the package
            try {
                $xml = $zpkService->getMeta($zpk);
            } catch (\ErrorException $ex) {
                throw new \Zend\Mvc\Exception\RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
            }
            $appName = sprintf("%s", $xml->name);

            // or the baseUri
            if(!$appName) {
                $appName = str_replace($baseUri, '/', '');
            }
        }

        // check what applications are deployed
        $response = $apiManager->applicationGetStatus();
        foreach ($response->responseData->applicationsList->applicationInfo as $appElement) {
            if($appElement->appName == $appName) {
                $appId = $appElement->id;
                break;
            }
        }

        if(!$appId) {
            $response = $this->forward()->dispatch('webapi-api-controller',array(
                'action'      => 'applicationDeploy',
                'appPackage'  => $zpk,
                'baseUrl'     => $baseUri,
                'userAppName' => $appName,
                'userParams'  => $userParams,
            ));

        } else {
            // otherwise update the application
            $response = $this->forward()->dispatch('webapi-api-controller',array(
                'action'     => 'applicationUpdate',
                'appId'      => $appId,
                'appPackage' => $zpk,
                'userParams' => $userParams,
            ));
        }

        return $response;
    }
}

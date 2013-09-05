<?php
namespace Client\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Client\Service\ZpkInvokable;

/**
 * Library Console Controller
 *
 * High-Level Library Deployment CLI commands
 */
class LibController extends AbstractActionController
{
    public function installAction()
    {
        $requestParameters = array();
        $zpk     = $this->params('zpk');
        $baseUri = $this->params('baseUri');
        $userParams = $this->params('userParams', array());
        $appName    = $this->params('userAppName');
        $appId      = 0;

        $zpkService = $this->serviceLocator->get('zpk');
        try {
            $xml = $zpkService->getMeta($zpk);
        } catch (\ErrorException $ex) {
            throw new \Zend\Mvc\Exception\RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
        }

        if (!(isset($xml->type) && $xml->type == ZpkInvokable::TYPE_LIBRARY)) {
            return $this->forward()->dispatch('webapi-app-controller');
        }

        $apiManager = $this->serviceLocator->get('zend_server_api');

        // validate the package
        $zpkService->validateMeta($zpk);

        if (!$appName) {
            // get the name of the application from the package
            $appName = sprintf("%s", $xml->name);
            // or the baseUri
            if (!$appName) {
                $appName = str_replace($baseUri, '/', '');
            }
        }
        $version = sprintf("%s", $xml->version->release);

        // check what libraries are deployed
        $response = $apiManager->libraryGetStatus();
        foreach ($response->responseData->libraryList->libraryInfo as $libElement) {
            if ($libElement->libraryName == $appName) {
                foreach ($libElement->libraryVersions->libraryVersion as $versionElement) {
                    if ($versionElement->version == $version) {
                        $appId = $versionElement->libraryVersionId;
                        break;
                    }

                }
            }
        }

        // if this one is not deployed, then try to deploy it.
        if ($appId) {
            return $response->getHttpResponse();
        }

        $response = $this->forward()->dispatch('webapi-api-controller',array(
                        'action'      => 'libraryVersionDeploy',
                        'libPackage'  => $zpk,
                    ));

        return $response;
    }
}

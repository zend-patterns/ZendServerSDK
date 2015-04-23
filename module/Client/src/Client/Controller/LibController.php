<?php
namespace Client\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Client\Service\ZpkInvokable;
use Zend\Mvc\Exception\RuntimeException;

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
        $libId   = 0;

        $zpkService = $this->serviceLocator->get('zpk');
        try {
            $xml = $zpkService->getMeta($zpk);
        } catch (\ErrorException $ex) {
            throw new \Zend\Mvc\Exception\RuntimeException($ex->getMessage(), $ex->getCode(), $ex);
        }

        if (!(isset($xml->type) && $xml->type == ZpkInvokable::TYPE_LIBRARY)) {
            throw new RuntimeException('The package is not a library. Use "installApp" to install an application.');
        }

        $apiManager = $this->serviceLocator->get('zend_server_api');

        // validate the package
        $zpkService->validateMeta($zpk);

        // get  name and version from the package information
        $appName = sprintf("%s", $xml->name);
        $version = sprintf("%s", $xml->version->release);

        // check what libraries are deployed
        $response = $apiManager->libraryGetStatus();
        foreach ($response->responseData->libraryList->libraryInfo as $libElement) {
            if ($libElement->libraryName == $appName) {
                foreach ($libElement->libraryVersions->libraryVersion as $versionElement) {
                    if ($versionElement->version == $version) {
                        $libId = $versionElement->libraryVersionId;
                        break;
                    }
                }
            }
        }

        // just exit if this version of the library is already deployed
        if ($libId) {
            return $response->getHttpResponse();
        }

        // otherwise try to deploy it
        $response = $this->forward()->dispatch('webapi-api-controller', array(
                        'action'      => 'libraryVersionDeploy',
                        'libPackage'  => $zpk,
                    ));

        return $response;
    }
}

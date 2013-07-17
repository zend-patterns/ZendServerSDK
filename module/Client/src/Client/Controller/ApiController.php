<?php
namespace Client\Controller;
use ZendServerWebApi\Controller\ApiController as DefaultApiController;

/**
 * Extended Main Console Controller
 *
 * Controller that manages all CLI commands
 *
 */
class ApiController extends DefaultApiController
{
    protected $apiManager;

    /**
     * User-friendly verions of the applicationDeploy command.
     *
     * @param array $args
     * @return \ZendServerWebApi\Controller\Response
     */
    public function applicationDeployAction($args)
    {
        if(!isset($args['userAppName'])) {
            // get the application name from the zpk file
            $xml = $this->serviceLocator->get('zpk')->getMeta($args['appPackage']);
            $args['userAppName'] = sprintf("%s",$xml->name);
        }
        if(!preg_match("/^(\w+):\/\//", $args['baseUrl'])) {
            $args['baseUrl']     = 'http://default-vhost/'. ltrim($args['baseUrl'],'/');
            $args['createVhost'] = 'TRUE';
        }
        return $this->sendApiRequest($args);
    }

    public function bootstrapSingleServerAction($args)
    {
        $keyService = $this->getServiceLocator()->get('defaultApiKey');
        $keyService->setName('');
        $keyService->setKey('');

        $response = $this->sendApiRequest($args);
        if(isset($args['simple-output'])) {
            $data = $response->responseData->bootstrap;
            $content = '';
            if(sprintf('%s', $data->success) == "true") {
                $content = sprintf("%s\n", $data->apiKey->name);
                $content.= sprintf("%s\n", $data->apiKey->hash);
            }

            $response->getHttpResponse()->setContent($content);
        }

        return $response;
    }
}

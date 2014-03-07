<?php
namespace Client\Controller;
use ZendServerWebApi\Controller\ApiController as DefaultApiController;
use Zend\Console\Response;

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
        $zendServer = $this->serviceLocator->get('targetZendServer');
        $zendServer->setUri(new \Zend\Uri\Http($this->params('zsurl')));

        $zendServerClient = $this->serviceLocator->get('zendServerClient');
        // set the explicit timeout to 3 minutes
        $zendServerClient->setOptions(array('timeout'=> 180)); //

        $response = $this->sendApiRequest($args);
        $data = $response->responseData->bootstrap;
        $content = '';
        if(sprintf('%s', $data->success) == "true") {
            $name = sprintf("%s", $data->apiKey->name);
            $key = sprintf("%s", $data->apiKey->hash);
        }

        if(isset($args['simple-output'])) {
            $response = new Response();
            $response->setContent("$name\n$key\n");
        }

        $wait = $this->params('wait');
        if($wait) {
            $keyService = $this->getServiceLocator()->get('defaultApiKey');
            $keyService->setName($name);
            $keyService->setKey($key);
            $this->repeater()->doUntil(array($this,'onWaitBootstrapSingleServer'));
        }

        return $response;
    }


    public function serverAddToClusterAction($args)
    {
        $zendServerClient = $this->serviceLocator->get('zendServerClient');
        // set the explicit timeout to 3 minutes
        $zendServerClient->setOptions(array('timeout'=> 180));

        $response = $this->sendApiRequest($args);

        $wait = $this->params('wait');
        if($wait) {
            $serverId = sprintf("%d", $response->responseData->serverInfo->id);
            $this->repeater()->doUntil(array($this,'onWaitServerAddToCluster'), array('serverId'=>$serverId));
        }

        return $response;
    }

    public function clusterRemoveServerAction($args)
    {
        $wait = $this->params('wait');
        $serverCount = 0;
        if($wait) {
            // Count the number of servers at the current moment
            $response = $this->sendApiRequest(array(),'clusterGetServersCount');
            $serverCount = sprintf("%d", $response->responseData->serversCount);
        }

        $response = $this->sendApiRequest($args);

        if($wait) {
            $this->repeater()->doUntil(array($this,'onWaitClusterRemoveServer'),
                                                   array(
                                                        'serverCount'=> $serverCount
                                                    ));
        }

        return $response;
    }

    public function onWaitBootstrapSingleServer($controller, $params)
    {
        $response = $this->sendApiRequest(array(), 'tasksComplete');
        if(sprintf("%s", $response->responseData->tasksComplete) == "true") {
            return $response;
        }
    }

    public function onWaitServerAddToCluster($controller, $params)
    {
        /*
         * needs to poll on clusterGetServerStatus and make sure it is 'OK' for 5 seconds before completing the action with success.
        Or Status 'Error' is returned for 5 seconds before releasing with error.
        */
        $response = $this->sendApiRequest(array('serverId'=> $params['serverId']), 'clusterGetServerStatus');
        $status = sprintf("%s", $response->responseData->serversList->serverInfo->status);
        if($status == 'OK') {
            return $response;
        }
    }

    public function onWaitClusterRemoveServer($controller, $params)
    {
        // needs to poll on clusterGetServersCount until it is '0'
        $response = $this->sendApiRequest(array(), 'clusterGetServersCount');
        $count = sprintf("%d", $response->responseData->serversCount);
        if($count < $params['serverCount']) {
            return $response;
        }
    }
}

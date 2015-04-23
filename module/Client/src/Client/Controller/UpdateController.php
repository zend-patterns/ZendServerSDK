<?php
namespace Client\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\Http\Client;
use Zend\Http\Request;
use Zend\Stdlib\ErrorHandler;
use Zend\Http\Headers;
use Zend\Http\Client\Adapter\Curl;

/**
 * Update Controller
 *
 * Controller that manages all commands relate to update, etc.
 */
class UpdateController extends AbstractActionController
{

    public function pharAction()
    {
        $client = $this->serviceLocator->get('zendServerClient');
        $client = new Client();

        if (defined('PHAR')) {
            // the file from which the application was started is the phar file to replace
            $file = $_SERVER['SCRIPT_FILENAME'];
        } else {
            $file = dirname($_SERVER['SCRIPT_FILENAME']).'/zs-client.phar';
        }

        $request = new Request();
        $request->setMethod(Request::METHOD_GET);
        $request->setHeaders(
            Headers::fromString('If-Modified-Since: ' . gmdate('D, d M Y H:i:s T', filemtime($file)))
        );
        $request->setUri('https://github.com/zendtech/ZendServerSDK/raw/master/bin/zs-client.phar');

        //$client->getAdapter()->setOptions(array('sslcapath' => __DIR__.'/../../../certs/'));
        $client->setAdapter(new Curl());
        $response = $client->send($request);
        if ($response->getStatusCode() == 304) {
            return 'Already up-to-date.';
        } else {
            ErrorHandler::start();
            rename($file, $file . '.' . date('YmdHi') . '.backup');
            $handler = fopen($file, 'w');
            fwrite($handler, $response->getBody());
            fclose($handler);
            ErrorHandler::stop(true);

            return 'The phar file was updated successfully.';
        }
    }
}

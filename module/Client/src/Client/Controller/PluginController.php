<?php
namespace Client\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Client\Service\ZpkInvokable;

/**
 * Plugin Console Controller
 *
 * High-Level Plugin Deployment CLI commands
 */
class PluginController extends AbstractActionController
{
    public function initAction()
    {
        $folder = $this->params('folder');

        if (!file_exists($folder)) {
            if (!mkdir($folder)) {
                throw new \Zend\Mvc\Exception\RuntimeException('Unable to create empty folder.');
            }
        }

        // check if the folder is empty
        $iterator = new \FilesystemIterator($folder);
        if ($iterator->valid()) {
            throw new \Zend\Mvc\Exception\RuntimeException('The folder must be empty!');
        }

        $remoteZip = fopen('https://github.com/zend-server-plugins/Skeleton/archive/master.zip', 'r');
        if (!$remoteZip) {
            throw new \Zend\Mvc\Exception\RuntimeException('Unable to download plugin skeleton');
        }

        // download the zip file remotely
        $zipName = tempnam(sys_get_temp_dir(), 'zsc');
        $localZip = fopen($zipName, "w");
        stream_copy_to_stream($remoteZip, $localZip);
        fclose($remoteZip);
        fclose($localZip);

        // Unpack it in the folder
        $zip = new \ZipArchive();
        if (!$zip->open($zipName)) {
            throw new \Zend\Mvc\Exception\RuntimeException('Unable to unzip file');
        }
        mkdir($zipName.'.folder');
        $zip->extractTo($zipName.'.folder');
        $zip->close();
        rename($zipName.'.folder/Skeleton-master', $folder);

        unlink($zipName);

        $content = "Next steps:\n".
                   "\tMake changes to the deployment.json file. Learn more from here: https://github.com/zend-server-plugins/Documentation/blob/master/DeploymentJson.md\n".
                   "\tLearn more about Z-Ray plugin development from here: https://github.com/zend-server-plugins/Documentation\n";
        $this->getResponse()->setContent($content);

        return $this->getResponse();
    }

    public function packAction()
    {
        $folder = $this->params('folder');
        $destination = $this->params('destination');
        $name = $this->params('name');

        if (empty($name)) {
            $text = file_get_contents($folder.'/deployment.json');
            $data = json_decode($text, true);
            $name = preg_replace('/^[^\d\w-\.]$/', '', $data['name'].'-'.$data['version']);
            if (empty($name)) {
                $name = random(1, 30).'.zip';
            }
            $name .= '.zip';
        }

        $zipFileName = $destination.'/'.$name;

        ignore_user_abort(true);
        $root = realpath($folder);

        $zip = new \ZipArchive();
        if (!$zip->open($zipFileName, \ZipArchive::CREATE | \ZipArchive::OVERWRITE)) {
            throw new \Zend\Mvc\Exception\RuntimeException('Unable to zip folder.Check folder permissions.');
        }

        // Notice: Empty directories are omitted
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root),
            \RecursiveIteratorIterator::LEAVES_ONLY
            );

        foreach ($files as $name => $entry) {
            if (!$entry->isDir()) {
                $filePath = $entry->getRealPath();
                $relativePath = substr($filePath, strlen($root) + 1);

                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->close();
        ignore_user_abort(false);

        $this->getResponse()->setContent($zipFileName."\n");

        return $this->getResponse();
    }

    public function installAction()
    {
        $zipFile     = $this->params('pluginPackage');
        $wait       = $this->params('wait');

        $doUpdate = false;
        try {
            $response = $this->forward()->dispatch('webapi-api-controller',
                                                array(
                                                    'action' => 'pluginDeploy',
                                                    'pluginPackage' => $zipFile
                                                ));
        } catch (\ZendServerWebApi\Model\Exception\ApiException $ex) {
            if (strpos($ex->getMessage(), 'already exists') === false) {
                throw $ex;
            }
            $doUpdate = true;
        }

        if ($doUpdate) {
            $response = $this->forward()->dispatch('webapi-api-controller',
                                                    array(
                                                        'action' => 'pluginUpdate',
                                                        'pluginPackage' => $zipFile
                                                    ));
        }

        if ($wait) {
            $xml = new \SimpleXMLElement($response->getBody());
            $this->repeater()->doUntil(array($this,'onWaitInstall'),
                                                   array('pluginId'=> (string)$xml->responseData->plugin->id)
                                                  );
        }

        return $response;
    }

    /**
     * Returns response if the action finished as expected
     * @param AbstractActionController $controller
     * @param array $params
     */
    public function onWaitInstall($controller, $params)
    {
        $pluginId = $params['pluginId'];
        $apiManager = $this->serviceLocator->get('zend_server_api');
        $xml = $apiManager->pluginGetList();
        foreach ($xml->responseData->plugins as $plugin) {
            if ((int)$plugin->plugin->id == $pluginId) {
                $status = (string)$plugin->plugin->status;
                if (stripos($status, 'ERROR')!==false) {
                    throw new \Exception(sprintf("Got error '%s' during deployment.\nThe following error message is reported from the server:\n%s", $status, $plugin->plugin->message));
                }

                if ($status !='STAGED') {
                    return;
                }
                break;
            }
        }

        return $xml->getHttpResponse()->getBody();
    }
}

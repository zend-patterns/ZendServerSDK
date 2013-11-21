<?php
namespace Client\Controller;
use Zend\Mvc\Controller\AbstractActionController;

/**
 * Main Console Controller
 *
 * Controller that manage all CLI commands
 */
class ZpkController extends AbstractActionController
{
    /**
     * Adds deployment support to existing PHP code
     * @param string folder
     */
    public function initAction()
    {
        $folder = $this->getRequest()->getParam('folder');
        $zpk = $this->serviceLocator->get('zpk');
        $zpk->create($folder);

        return $this->getResponse();
    }

    /**
     * Adds deployment support to existing PHP code
     * @param string folder
     */
    public function createAction()
    {
        error_log("WARNING: This method is deprecated. Please, use initZpk instead.");
        return $this->initAction();
    }

    /**
     * Verifies the deployment.xml and the existance of the files that have to be packed as described in the deployment.properties file.
     * @param string folder
     * @param string zpk
     */
    public function verifyAction()
    {
        $zpk = $this->serviceLocator->get('zpk');
        $from = $this->params('from');
        if(is_dir($from)) {
            // check the deployment.xml in the folder
            $content = file_get_contents($from.'/deployment.xml');

        $content = "";
        if ($this->getRequest()->getParam('composer') && file_exists($folder.'/composer.json')) {
            // Enable rudimentary composer support
            $composer = $this->serviceLocator->get('composer');
            $requirements = $composer->getMeta($folder, "require");
            if (count($requirements)) {
                $dependancies = array();
                foreach ($requirements as $name=>$version) {
                    if ($name == "php") {
                        // add in the deployment.xml dependancy on this PHP version
                        $dependancies['php'] = self::convertVersion($version);
                    } elseif (strpos($name,'ext-')===0) {
                        // add in the deployment.xml dependancy on this PHP extension
                        $name = substr($name, 4);
                        $dependancies['extension'][$name] = self::convertVersion($version);
                    } elseif (strpos($name, 'lib-')===0) {
                        // @todo: skip for now
                    } else {
                        $dependandPackages[$name] = $version;
                        $dependancies['library'][] = array_merge(
                                                        array('name' => $name),
                                                        self::convertVersion($version)
                                                     );
                    }
                }

                if (count($dependancies['library'])) {
		    $composerOptions =  $this->getRequest()->getParam('composer-options') ? : null;
                    $data = $composer->install($folder, $composerOptions);

                    foreach ($data['packages'] as $library=>$version) {
                        $libraryFolder = $data['folder'].'/vendor/'.$library;
                        $zpk->create($libraryFolder, array(
                                                            'type'=>'library',
                                                            'name'=>$library,
                                                            'version'=> array('release'=>$version),
                                                            'appdir' => ''
                                                     ));
                        $zpkFile = $zpk->pack($libraryFolder, $destination,"$library-$version.zpk");
                        $content.= $zpkFile."\n";
                    }
                }

                if (!empty($dependancies)) {
                    $zpk->updateMeta($folder, array('dependencies'=> array('required'=> $dependancies)));
                }
            }
        }

        // Check XML
        $zpk->validateXml($content);
    }

    /**
     * Tries to fix the deployment xml if it does not match the schema.xsd.
     * @param string folder
     */
    public function fixAction()
    {
        $zpk = $this->serviceLocator->get('zpk');
        $from = $this->params('from');
        if(is_dir($from)) {
            // check the deployment.xml in the folder
            $content = file_get_contents($from.'/deployment.xml');
            $result = $zpk->fixXml($content);
            file_put_contents($from.'/deployment.xml',$result);

            // @todo: for a folder fix also the properties
        } else {
            $content = $zpk->getFileContent($from, 'deployment.xml');
            $result = $zpk->fixXml($content);
            $zpk->setFileContent($from, 'deployment.xml',$result);
        }
    }

    /**
     * Creates a package from existing PHP code
     * @param string source - the source folder
     * @param string destination - the destination folder
     */
    public function packAction()
    {
        $folder = $this->getRequest()->getParam('folder');
        $destination = $this->getRequest()->getParam('destination');
        $zpk = $this->serviceLocator->get('zpk');
        $zpkFile = $zpk->pack($folder, $destination, $this->getRequest()->getParam('name'));
        $this->getResponse()->setContent($zpkFile."\n");

        return $this->getResponse();
    }
}

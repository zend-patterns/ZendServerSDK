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
    public function createAction()
    {
        $folder = $this->getRequest()->getParam('folder');
        $zpk = $this->serviceLocator->get('zpk');
        $zpk->create($folder);

        return $this->getResponse();
    }

    /**
     * Creates a package from existing PHP code
     * @param string folder - the source folder
     * @param string destination - the destination folder
     */
    public function packAction()
    {
        $folder = $this->getRequest()->getParam('folder');
        $destination = $this->getRequest()->getParam('destination');
        $zpk = $this->serviceLocator->get('zpk');

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
                    $data = $composer->install($folder);
                    foreach ($data['packages'] as $library=>$version) {
                        $libraryFolder = $data['folder'].'/vendor/'.$library;
                        $zpk->create($libraryFolder, array(
                                                            'type'=>'library',
                                                            'name'=>$library,
                                                            'version'=> array('release'=>$version),
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

        $zpkFile = $zpk->pack($folder, $destination, $this->getRequest()->getParam('name'));
        $content.= $zpkFile."\n";

        $this->getResponse()->setContent($content);

        return $this->getResponse();
    }

    protected static function convertVersion($version)
    {
        $version = trim($version);
        if (strpos($version,'>=')===0) {
            return array ('min' => substr($version,2));
        }

        if (strpos($version,'<=')===0) {
            return array ('max' => substr($version,2));
        }

        return array('equals' => $version);
    }
}

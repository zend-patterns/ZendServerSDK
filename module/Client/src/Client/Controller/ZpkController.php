<?php
namespace Client\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Config\Reader\Yaml as YamlReader;
use Zend\Config\Reader\Ini as IniReader;
use Zend\Config\Writer\Ini as IniWriter;

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

            // for a folder we check also the properties
            $zpk->validateProperties($from);
        } else {
            $content = $zpk->getFileContent($from, 'deployment.xml');
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
                    $composerOptions =  $this->getRequest()->getParam('composer-options') ? : null;
                    $packages = $composer->install($folder, $composerOptions);

                    foreach ($packages as $library=>$version) {
                        if (!array_key_exists($library, $requirements)) {
                            $dependancies['library'][] = array_merge(
                                array('name' => $library),
                                self::convertVersion($version)
                            );
                        }
                        $libraryFolder = $folder.'/vendor/'.$library;
                        // @todo: how to handle dev-master versioning correctly?
                        if ($version == 'dev-master') $version = '999.dev-master';
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

                if (!empty($packages)) {
                    $zpk->updateMeta($folder, array('dependencies'=> array('required'=> $dependancies)));
                }

                $composerFile = $this->serviceLocator->get('Composer\File');
                $composerFile->writeAutoloadZendserver($folder, $packages, $dependandPackages);
                
                $scripts = $composer->getMeta($folder, "scripts");
                if(!empty($scripts)) {
                    $distFiles = $this->getRequest()->getParam('composer-dist-files');
                    $userParams = array();
                    if(!count($distFiles)) {
                        error_log('WARNING: If you have user parameters then you have to use --composer-dist-files to point to the YAML dist files.');
                    } else {
                        // Read the parameters from composer-dist-files are specified it gets the parameters from them and puts them as zpk parameters (with default values)
                        $yaml = new YamlReader(array('Spyc','YAMLLoadString'));
                        foreach ($distFiles as $file) {
                            $data = $yaml->fromFile($file);
                            $userParams = array_merge($userParams, $data['parameters']);
                        }
                    }

                    if(!empty($userParams)) {
                        // convert the parameters to deployment.xml ZPK parameters
                        $zpk->updateParameters($folder, $userParams);
                    }
                    
                    $paramCollector = $this->serviceLocator->get('Composer\Extra\ParamCollector');
                    $paramFactory = $this->serviceLocator->get('Composer\Extra\ParamFactory');
                    
                    $paramCollector->setParamFactory($paramFactory);
                    $paramCollector->setLibs(array_keys($dependandPackages));
                    $paramCollector->setUserParams($userParams);
                    $composerFile->writeComposerJson($folder, $composer, $paramCollector->getParams());
                    
                    // Find the scripts directory and copy in it the composer.phar, composer.lock and composer.json files
                    $xml = new \SimpleXMLElement(file_get_contents($folder."/deployment.xml"));
                    $scriptsDir = "$folder/scripts";
                    if($xml->scriptsdir) {
                        $scriptsDir=$folder."/".$xml->scriptsdir;
                    }
                    if(!file_exists($scriptsDir)) {
                        mkdir($scriptsDir);
                    }
                    
                    $composerFile->copyComposerFiles($folder, $scriptsDir);
                    $composerFile->writeDeploymentProperties($folder);
                    $composerFile->writePostStage($folder);
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
        // @todo: how to handle dev-master versioning correctly?
        if ($version == 'dev-master') {
            return array('equals' => '999.dev-master');
        }
        
        if (strpos($version,'>=')===0) {
            return array ('min' => substr($version,2));
        }

        if (strpos($version,'<=')===0) {
            return array ('max' => substr($version,2));
        }
        
        //@todo: specify max value also
        if (strpos($version,'~')===0) {
            return array (
                'min' => substr($version,1)
            );
        }

        return array('equals' => $version);
    }
}

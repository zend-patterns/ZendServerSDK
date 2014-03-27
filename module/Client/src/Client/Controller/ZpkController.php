<?php
namespace Client\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Config\Reader\Yaml as YamlReader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

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
        if (is_dir($from)) {
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
        if (is_dir($from)) {
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
     * @param string name - The name of the package
     * @param string version - The version release of the package
     */
    public function packAction()
    {
        $folder = $this->getRequest()->getParam('folder');
        $destination = $this->getRequest()->getParam('destination');
        $zpk = $this->serviceLocator->get('zpk');

        $content = "";
        $adjustedVendorDir = "";
        $adjustedProperties = array();
        $hasComposerScripts = false;

        ignore_user_abort(true);
        if (file_exists($folder.'/vendor.original')) {
            // The directory structure was not restored to its previous state. Try to fix this.
            foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($folder.'/vendor', RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
                unlink($item);
            }
            rename($folder.'/vendor.original', $folder.'/vendor');
        }
        ignore_user_abort(false);
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
                                                        array('name' => self::normalizeLibName($name)),
                                                        self::convertVersion($version)
                                                     );
                    }
                }

                if (count($dependancies['library'])) {
                    $composerOptions =  $this->getRequest()->getParam('composer-options') ? : null;
                    $packages = $composer->install($folder, $composerOptions);

                    foreach ($packages as $library=>$version) {
                        $version = self::normalizeVersion($version);
                        $libraryFolder = $folder.'/vendor/'.$library;
                        $library = self::normalizeLibName($library);
                        $zpk->create($libraryFolder, array(
                                                            'type'=>'library',
                                                            'name'=>$library,
                                                            'version'=> array('release'=> $version),
                                                            'appdir' => ''
                                                     ));
                        $zpkFile = $zpk->pack($libraryFolder, $destination,"$library-$version.zpk");
                        $content.= $zpkFile."\n";
                    }
                }

                if (!empty($dependancies)) {
                    $zpk->updateMeta($folder, array('dependencies'=> array('required'=> $dependancies)));
                }

                $composerFile = $this->serviceLocator->get('Composer\File');
                $adjustedVendorDir = $composerFile->adjustAutoloader($folder);

                $scripts = $composer->getMeta($folder, "scripts");
                if (!empty($scripts)) {
                    $hasComposerScripts = true;
                    $distFiles = $this->getRequest()->getParam('composer-dist-files');
                    $userParams = array();
                    if (!count($distFiles)) {
                        error_log('WARNING: If you have user parameters then you have to use --composer-dist-files to point to the YAML dist files.');
                    } else {
                        // Read the parameters from composer-dist-files are specified it gets the parameters from them and puts them as zpk parameters (with default values)
                        $yaml = new YamlReader(array('Spyc','YAMLLoadString'));
                        foreach ($distFiles as $file) {
                            $data = $yaml->fromFile($file);
                            $userParams = array_merge($userParams, $data['parameters']);
                        }
                    }

                    if (!empty($userParams)) {
                        // convert the parameters to deployment.xml ZPK parameters
                        $zpk->updateParameters($folder, $userParams);
                    }

                    $composerFile->copyComposerFiles($folder);
                    $adjustedProperties = $composerFile->adjustDeploymentProperties($folder);
                    $composerFile->writePostStage($folder);
                }
            }
        }

        ignore_user_abort(true);
        if ($hasComposerScripts) {
            $xml = new \SimpleXMLElement(file_get_contents($folder.'/deployment.xml'));
            if (!isset($xml->scriptsdir)) {
                $zpk->updateMeta($folder, array('scriptsdir'=> 'scripts'));
            }
        }
        if ($adjustedVendorDir) {
            rename($folder.'/vendor', $folder.'/vendor.original');
            rename($adjustedVendorDir, $folder.'/vendor');
        }
        $zpkFile = $zpk->pack($folder, $destination, $this->getRequest()->getParam('name'), $adjustedProperties, $this->getRequest()->getParam('version'));
        if ($adjustedVendorDir) {
            rename($folder.'/vendor', $adjustedVendorDir);
            rename($folder.'/vendor.original', $folder.'/vendor');
        }
        ignore_user_abort(false);

        $content.= $zpkFile."\n";
        $this->getResponse()->setContent($content);

        return $this->getResponse();
    }

    protected static function convertVersion($version)
    {
        $version = self::normalizeVersion($version);
        if (count($versions = explode(',', $version)) > 1) {
            $v1 = self::convertVersion(array_pop($versions));
            $v2 = self::convertVersion(join(',', $versions));
            $min = @self::compareMinVersion($v1['min'], $v2['min']);
            $max = @self::compareMaxVersion($v1['max'], $v2['max']);

            return array(
                'min' => $min,
                'max' => $max
            );
        }
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
            $version = substr($version,1);
            $versionParts = explode('.', $version);
            array_pop($versionParts);
            array_push($versionParts, 999);
            $maxVersion = join('.', $versionParts);

            return array (
                'min' => $version,
                'max' => $maxVersion
            );
        }

        return array('equals' => $version);
    }

    protected static function compareMinVersion($a, $b)
    {
        if (!isset($a) && !isset($b)) return false;
        elseif (isset($a) && !isset($b)) return $a;
        elseif (!isset($a) && isset($b)) return $b;
        return (version_compare($a, $b)) ? $a : $b;
    }

    protected static function compareMaxVersion($a, $b)
    {
        if (!isset($a) && !isset($b)) return false;
        elseif (isset($a) && !isset($b)) return $a;
        elseif (!isset($a) && isset($b)) return $b;
        return (version_compare($a, $b)) ? $b : $a;
    }

    protected static function normalizeVersion($version)
    {
        return trim($version, ' v');
    }

    protected static function normalizeLibName($library)
    {
        return str_replace('/', '.', $library);
    }
}

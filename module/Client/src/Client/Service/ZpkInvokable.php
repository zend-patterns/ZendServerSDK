<?php
namespace Client\Service;

use Zend\Stdlib\ErrorHandler;

/**
 * ZPK Service
 */
class ZpkInvokable
{
    const TYPE_LIBRARY='library';

    protected static $keyOrder;

    protected static $checkZipVersion=true;

    /**
     *
     * @param string $filename
     * @return \SimpleXMLElement
     */
    public function getMeta($filename)
    {
        $zip = new \ZipArchive;

        ErrorHandler::start();
        $zip->open($filename);
        $content = $zip->getFromName('deployment.xml');
        $zip->close();
        ErrorHandler::stop(true);

        if(!$content) {
            throw new \Zend\Mvc\Exception\RuntimeException('Missing deployment.xml in the zpk file.');
        }

        $xml = new \SimpleXMLElement($content);

        return $xml;
    }

    public function updateMeta($folder, array $updates)
    {
        $file = $folder.'/deployment.xml';
        $content = $this->updateXML($file, $updates);
        if($content) {
            file_put_contents($file, $content);
        }
    }

    /**
     * Simple XML update
     *
     * @param string $file
     * @param array $updates
     * @return string
     * @throws \Zend\Mvc\Exception\RuntimeException
     */
    protected function updateXML($file, array $updates)
    {
        // Load the current data
        $content = file_get_contents($file);
        if(!$content) {
            throw new \Zend\Mvc\Exception\RuntimeException('Missing deployment.xml in the zpk file.');
        }
        $doc = new \DOMDocument();
        $doc->loadXML($content);
        $data = \LSS\XML2Array::createArray($doc);

        $root = $doc->documentElement;
        $rootName = $root->tagName;
        $data[$rootName]['@attributes']['xmlns'] = $root->getAttribute('xmlns');
        // Update it

        $data[$rootName] = array_merge($data[$rootName], $updates);
        // fix the order of the elements
        $data[$rootName] = self::fixMetaKeyOrder($data[$rootName]);

        $xml = \LSS\Array2XML::createXML($rootName, $data[$rootName]);
        return $xml->saveXML();
    }

    public function validateMeta($filename)
    {
        $zip = new \ZipArchive;

        ErrorHandler::start();
        $zip->open($filename);
        $content = $zip->getFromName('deployment.xml');
        $zip->close();
        ErrorHandler::stop(true);

        $dom = new \DOMDocument();
        $dom->loadXML($content);

        libxml_use_internal_errors(true);
        if(!$dom->schemaValidate(__DIR__.'/../../../config/zpk/schema.xsd')){
            $message = "";
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $message.= $error->message;
            }
            libxml_clear_errors();
            throw new \DOMException($message);
        }
    }


    /**
     * Adds deployment support to an existing PHP application
     * @param string $sourceFolder
     */
    public function create($sourceFolder, array $updates=null, array $properties=null)
    {
        if(file_exists($sourceFolder."/deployment.xml")) {
            error_log('WARNING: The specified directory already has deployment.xml.');
            return false;
        }

        if(!is_dir($sourceFolder)) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('The source folder parameter must be real directory.');
        }

        ErrorHandler::start();
        copy(__DIR__.'/../../../config/zpk/deployment.xml', $sourceFolder."/deployment.xml");
        copy(__DIR__.'/../../../config/zpk/deployment.properties', $sourceFolder."/deployment.properties");
        if($updates!==null) {
            $file = $sourceFolder."/deployment.xml";
            $content = $this->updateXML($file, $updates);
            file_put_contents($file, $content);
        }
        if($properties!==null) {
            // @TODO
        }

        ErrorHandler::stop(true);

        return true;
    }

    /**
     * Creates a package from the data in the source folder
     * @param string $sourceFolder
     * @param string $destinationFolder
     */
    public function pack($sourceFolder, $destinationFolder=".", $fileName=null)
    {
        if(!file_exists($sourceFolder."/deployment.xml")) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('The specified directory does not have deployment.xml.');
        }

        // get the current meta information
        $xml = new \SimpleXMLElement(file_get_contents($sourceFolder."/deployment.xml"));
        $name 	 	= sprintf("%s", $xml->name);
        $version 	= sprintf("%s", $xml->version->release);
        $appDir  	= sprintf("%s", $xml->appdir);
        $scriptsDir = sprintf("%s", $xml->scriptsdir);
        $type       = sprintf("%s", $xml->type);

        $properties = $this->getProperties($sourceFolder."/deployment.properties");

        if(!$fileName) {
            $fileName = "$name-$version.zpk";
        }
        $fileName = str_replace(array('/'),array('.'), $fileName);

        $outZipPath = $destinationFolder.'/'.$fileName;

        if(self::$checkZipVersion) {
            $ext = new \ReflectionExtension('zip');
            $zipVersion = $ext->getVersion();
            if(!version_compare($zipVersion,'1.11.0','>=')) {
                error_log("WARNING: Non-Ascii file/folder names are supported only with PHP zip extension >=1.11.0 (your version is: $zipVersion)\n\t(http://pecl.php.net/package-changelog.php?package=zip&release=1.11.0)");
            }
            self::$checkZipVersion = false;
        }


        $zpk = new \ZipArchive();
        $zpk->open($outZipPath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
        $zpk->addFile($sourceFolder."/deployment.xml", 'deployment.xml');

        $folderMap = array();
        if($type == self::TYPE_LIBRARY) {
            // Include all files and folders for the library
            $properties['appdir.includes'] = array_diff(scandir($sourceFolder), array('.','..','deployment.properties'));
            $folderMap['appdir.includes'] = '';
        } else {
            $folderMap['appdir.includes'] = $appDir;
            if(isset($xml->scriptsdir)) {
                $folderMap['scriptsdir.includes'] = $scriptsDir;
            }
        }

        ErrorHandler::start();
        foreach($folderMap as $key => $baseDir) {
            if($baseDir) {
                $baseDir .= '/';
            }
            foreach($properties[$key] as $path) {
                $path = trim($path);
                $fullPath = $sourceFolder.'/'.$path;
                if(is_file($fullPath)) {
                    // Fix the script properties to match the behaviour of ZendStudio
                    if($key=='scriptsdir.includes') {
                        $hardCodedPrefix = 'scripts/';
                        if(strpos($path, $hardCodedPrefix)===0) {
                            $path = substr($path, strlen($hardCodedPrefix));
                        }
                    }

                    $zpk->addFile($fullPath, $this->fixZipPath($baseDir.$path));
                } else if(is_dir($fullPath)) {
                    $countFiles = scandir($fullPath);
                    if(count($countFiles) <= 2) {
                        $zpk->addEmptyDir($this->fixZipPath($baseDir.$path));
                    } else {
                        $this->addDir($zpk, $fullPath, $baseDir);
                    }
                } else {
                    throw new \Zend\ServiceManager\Exception\RuntimeException("Path '$fullPath' is not existing. Verify your deployment.properties!");
                }
            }
        }
        if(!$zpk->close()) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('Failed creating zpk file: '.$outZipPath.". ".
                                                                       $zpk->getStatusString());
        }
        ErrorHandler::stop(true);

        return $outZipPath;
    }

    protected function fixZipPath($path)
    {
        $path = preg_replace('/(\/{2,})/', '/', $path);
        return $path;
    }

    /**
     * Add a directory in zip
     *
     * @param ZipArchive $zpk
     * @param string $directory
     * @param string $baseDir
     */
    protected function addDir($zpk, $directory, $baseDir = null)
    {
        $dir = dir($directory);
        if($dir) {
            if($baseDir) {
                $currentZipFolder = $baseDir.'/'.basename($directory);
            } else {
                $currentZipFolder = basename($directory);
            }

            while($path = $dir->read()) {
                if(in_array($path, array('.','..'))) {
                    continue;
                }

                $path = $directory."/".$path;
                if(is_dir($path)) {
                    $this->addDir($zpk, $path, $currentZipFolder);
                } else if(file_exists($path)){
                    $success = $zpk->addFile($path, $this->fixZipPath($currentZipFolder.'/'.basename($path)));
                    if(!$success) {
                        throw new \Zend\ServiceManager\Exception\RuntimeException("Path '$path' cannot be added zpk");
                    }
                } else {
                    throw new \Zend\ServiceManager\Exception\RuntimeException("Path '$path' is not existing. Verify your deployment.properties!");
                }
            }
            $dir->close();
        }
    }

    /**
     * Gets properties from file.
     *
     * @param string $file
     * @return array
     *
     * @see http://blog.rafaelsanches.com/2009/08/05/reading-java-style-properties-file-in-php/
     * 		Adapted the solution from the URL above.
     */
    public function getProperties($file)
    {
        $lines = file($file);
        $properties = array ();

        $key = "";
        $isWaitingOtherLine = false;
        foreach($lines as $i=>$line) {
            $line = trim($line);

            if(empty($line) || (!$isWaitingOtherLine && strpos($line,"#") === 0)) {
                continue;
            }

            if(!$isWaitingOtherLine) {
                $key = trim(substr($line,0,strpos($line,'=')));
                $value = substr($line,strpos($line,'=') + 1, strlen($line));
            } else {
                $value .= trim($line);
            }

            /* Check if ends with single '\' */
            if(strrpos($value,"\\") === strlen($value)-strlen("\\")) {
                $value = substr($value, 0, strlen($value)-1)."\n";
                $isWaitingOtherLine = true;
            } else {
                $isWaitingOtherLine = false;
            }

            $properties[$key] = $value;
        }

        foreach ($properties as &$data) {
            $data = explode(',',trim($data));
        }

        return $properties;
    }

    /**
     * Fixes the order of the keys in the meta data
     * @param array $xsd
     * @return array
     */
    protected static function fixMetaKeyOrder(array $data)
    {
        if(!isset(self::$keyOrder)) {
            // read the key order
            $doc = new \DOMDocument();
            $doc->load(__DIR__.'/../../../config/zpk/schema.xsd');
            $xsd = \LSS\XML2Array::createArray($doc);

            self::$keyOrder = array(
                '@attributes'
            );

            foreach ($xsd["xs:schema"]["xs:element"][0]["xs:complexType"]["xs:sequence"]["xs:element"] as $element) {
                if(isset($element['@attributes']['name'])) {
                    $name = $element['@attributes']['name'];
                } else if($element['@attributes']['ref']) {
                    $name = $element['@attributes']['ref'];
                }

                if(!$name) {
                    continue;
                }
                self::$keyOrder[] = $name;
            }
        }

        $meta = array();
        foreach(self::$keyOrder as $key) {
            if(isset($data[$key])) {
                $meta[$key] = $data[$key];
            }
        }

        return $meta;

    }
}

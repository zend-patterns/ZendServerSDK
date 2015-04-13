<?php
namespace Client\Service;

use Zend\Stdlib\ErrorHandler;
use Zend\ServiceManager\Exception\RuntimeException;

/**
 * ZPK Service
 */
class ZpkInvokable
{
    const TYPE_LIBRARY='library';

    protected static $keyOrder;

    /**
     *
     * @param  string            $filename
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

        if (!$content) {
            throw new \Zend\Mvc\Exception\RuntimeException('Missing deployment.xml in the zpk file.');
        }

        $xml = new \SimpleXMLElement($content);

        return $xml;
    }

    public function updateMeta($folder, array $updates)
    {
        $file = $folder.'/deployment.xml';
        $content = $this->updateXML($file, $updates);
        if ($content) {
            file_put_contents($file, $content);
        }
    }

    /**
     * Converts associative array with key and velue into ZPK parameters
     * @param string $folder     location of the deployment.xml file
     * @param array  $userParams
     */
    public function updateParameters($folder, array $userParams)
    {
        // <parameter display="test" id="test" readonly="false" required="false" type="string">
        //		<defaultvalue>test</defaultvalue>
        //</parameter>

        $parameterUpdates = array ();
        $i=0;
        foreach ($userParams as $key => $value) {
            $parameterUpdates[$i] = array(
                    '@attributes' => array(
                            'display'=> $key,
                            'id'     => 'COMPOSER_'.$key,
                            'required' => $value ? 'true':'false',
                            'type'   => 'string',
                    )
            );

            if ($value) {
                $parameterUpdates[$i]['defaultvalue'] = $value;
            }
            $i++;
        }

        return $this->updateMeta($folder, array('parameters' => array('parameter' => $parameterUpdates)));
    }

    /**
     * Simple XML update
     *
     * @param  string                               $file
     * @param  array                                $updates
     * @return string
     * @throws \Zend\Mvc\Exception\RuntimeException
     */
    protected function updateXML($file, array $updates)
    {
        // Load the current data
        $content = file_get_contents($file);
        if (!$content) {
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
        $content = $this->getFileContent($filename, 'deployment.xml');
        $this->validateXml($content);
    }

    /**
     * Extracts the content from a packed file in the zpk.
     * @param  string $zpkFile
     * @param  string $internalPath
     * @return string
     */
    public function getFileContent($zpkFile, $internalPath)
    {
        $zip = new \ZipArchive;

        ErrorHandler::start();
        $zip->open($zpkFile);
        $content = $zip->getFromName($internalPath);
        $zip->close();
        ErrorHandler::stop(true);

        return $content;
    }

    /**
     * Extracts the content from a packed file in the zpk.
     * @param  string $zpkFile
     * @param  string $internalPath
     * @return string
     */
    public function setFileContent($zpkFile, $internalPath, $content)
    {
        $internalPath = $this->fixZipPath($internalPath);
        $zip = new \ZipArchive;

        ErrorHandler::start();
        $zip->open($zpkFile);
        $content = $zip->addFromString($internalPath, $content);
        $zip->close();
        ErrorHandler::stop(true);

        return $content;
    }

    /**
     * Validates the deployment.xml against the specified schema.xsd
     * @param  string        $content
     * @throws \DOMException
     */
    public function validateXml($content)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($content);

        libxml_use_internal_errors(true);
        if (!$dom->schemaValidate(__DIR__.'/../../../config/zpk/schema.xsd')) {
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
     * Fixes deployment.xml file
     * @param  string $content
     * @return string
     */
    public function fixXml($content)
    {
        $doc = new \DOMDocument();
        ErrorHandler::start();
        $doc->loadXML($content);
        ErrorHandler::stop(true);
        $data = \LSS\XML2Array::createArray($doc);

        $root = $doc->documentElement;
        $rootName = $root->tagName;
        $data[$rootName]['@attributes']['xmlns'] = $root->getAttribute('xmlns');

        // fix the order of the elements
        $data[$rootName] = self::fixMetaKeyOrder($data[$rootName]);

        $xml = \LSS\Array2XML::createXML($rootName, $data[$rootName]);

        return $xml->saveXML();
    }

    /**
     * Adds deployment support to an existing PHP application
     * @param string $sourceFolder
     */
    public function create($sourceFolder, array $updates=null, array $properties=null)
    {
        if (file_exists($sourceFolder."/deployment.xml")) {
            error_log('WARNING: The specified directory already has deployment.xml.');

            return false;
        }

        if (!is_dir($sourceFolder)) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('The source folder parameter must be real directory.');
        }

        ErrorHandler::start();
        copy(__DIR__.'/../../../config/zpk/deployment.xml', $sourceFolder."/deployment.xml");
        copy(__DIR__.'/../../../config/zpk/deployment.properties', $sourceFolder."/deployment.properties");
        if ($updates!==null) {
            $file = $sourceFolder."/deployment.xml";
            $content = $this->updateXML($file, $updates);
            file_put_contents($file, $content);
        }
        if ($properties!==null) {
            // @TODO
        }

        ErrorHandler::stop(true);

        return true;
    }

    /**
     * Creates a package from the data in the source folder
     * @param string $sourceFolder
     * @param string $destinationFolder
     * @param string $fileName
     * @param array  $extraProperties
     * @param string $customVersion
     */
    public function pack($sourceFolder, $destinationFolder=".", $fileName=null, array $extraProperties=null, $customVersion="")
    {
        if (!file_exists($sourceFolder."/deployment.xml")) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('The specified directory does not have deployment.xml.');
        }

        // get the current meta information
        $xml = new \SimpleXMLElement(file_get_contents($sourceFolder."/deployment.xml"));
        $name 	 	= sprintf("%s", $xml->name);
        $version 	= sprintf("%s", $xml->version->release);
        $appDir  	= sprintf("%s", $xml->appdir);
        $scriptsDir = sprintf("%s", $xml->scriptsdir);
        $type       = sprintf("%s", $xml->type);
        $icon       = sprintf("%s", $xml->icon);

        if (!empty($customVersion)) {
            $version = $customVersion;
            $xml->version->release = $version;
            $xml->asXML($sourceFolder."/deployment.xml");
            $fixedContent = $this->updateXML($sourceFolder."/deployment.xml", array());
            if($fixedContent) {
                file_put_contents($sourceFolder."/deployment.xml", $fixedContent);
            }
        }
        $properties = $this->getProperties($sourceFolder."/deployment.properties");
        if ($extraProperties !== null) {
            $properties = array_merge_recursive($properties, $extraProperties);
            foreach ($properties as $key=> $value) {
                $properties[$key] = array_unique($value);
            }
        }

        if (!$fileName) {
            $fileName = "$name-$version.zpk";
        }
        $fileName = str_replace(array('/'),array('.'), $fileName);

        $outZipPath = $destinationFolder.'/'.$fileName;

        $ext = new \ReflectionExtension('zip');
        $zipVersion = $ext->getVersion();
        if (!version_compare($zipVersion,'1.11.0','>=')) {
            error_log("WARNING: Non-Ascii file/folder names are supported only with PHP zip extension >=1.11.0 (your version is: $zipVersion)\n\t(http://pecl.php.net/package-changelog.php?package=zip&release=1.11.0)");
        }

        $zpk = new \ZipArchive();
        $zpk->open($outZipPath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
        $zpk->addFile($sourceFolder."/deployment.xml", 'deployment.xml');
        // Add the icon file that was specified!
        if (!empty($icon))
            $zpk->addFile($sourceFolder."/" . $icon, $icon);
            
        $folderMap = array();
        if ($type == self::TYPE_LIBRARY) {
            // Include all files and folders for the library
            $properties['appdir.includes'] = array_diff(scandir($sourceFolder), array('.','..','deployment.properties'));
            $folderMap['appdir.includes'] = '';
        } else {
            $folderMap['appdir.includes'] = $appDir;
            if ($scriptsDir) {
                $folderMap['scriptsdir.includes'] = $scriptsDir;
            }
        }

        if(isset($folderMap['scriptsdir.includes']) && !isset($properties['scriptsdir.includes'])) {
            $properties['scriptsdir.includes'] = array ($scriptsDir);
        }

        ErrorHandler::start();
        foreach ($folderMap as $key => $baseDir) {
            $excludes = array();
            if (array_key_exists('appdir.excludes', $properties)) {
                $excludes = $properties['appdir.excludes'];
                array_walk($excludes, function (&$item, $key, $prefix) {$item = $prefix . '/' . $item;}, $baseDir);
            }
            foreach ($properties[$key] as $path) {
                $path = trim($path);
                $fullPath = $sourceFolder.'/'.$path;
                if (is_file($fullPath)) {
                    // Fix the script properties to match the behaviour of ZendStudio
                    if ($key=='scriptsdir.includes') {
                        $prefix = $scriptsDir ? $scriptsDir : 'scripts/';
                        if (strpos($path, $prefix)===0) {
                            $path = substr($path, strlen($prefix));
                        }
                    }
                    if (in_array($baseDir . '/' . $path, $excludes)) continue;
                    $zpk->addFile($fullPath, $this->fixZipPath($baseDir . '/' . $path));
                } elseif (is_dir($fullPath)) {
                    $this->addDir($zpk, $fullPath, $baseDir, $excludes);
                } else {
                    throw new \Zend\ServiceManager\Exception\RuntimeException("Path '$fullPath' is not existing. Verify your deployment.properties!");
                }
            }
        }

        if (!$zpk->close()) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('Failed creating zpk file: '.$outZipPath.". ".
                                                                       $zpk->getStatusString());
        }
        ErrorHandler::stop(true);

        return $outZipPath;
    }

    protected function fixZipPath($path)
    {
        $path = preg_replace('/(\/{2,})/', '/', $path);
        $path = trim($path, '/');

        return $path;
    }

    /**
     * Add a directory in zip
     *
     * @param ZipArchive $zpk
     * @param string     $directory
     * @param string     $baseDir
     */
    protected function addDir($zpk, $directory, $baseDir = null, $excludes = array())
    {
        if ($baseDir) {
            $currentZipFolder = $baseDir.'/'.basename($directory);
        } else {
            $currentZipFolder = basename($directory);
        }

        if (in_array($currentZipFolder, $excludes)) return;

        $countFiles = scandir($directory);
        if (count($countFiles) <= 2) {
            $zpk->addEmptyDir($currentZipFolder);
        } else {
            $dir = dir($directory);
            if ($dir) {
                while ($path = $dir->read()) {
                    if (in_array($path, array('.','..'))) {
                        continue;
                    }

                    $path = $directory."/".$path;
                    if (is_dir($path)) {
                        $this->addDir($zpk, $path, $currentZipFolder, $excludes);
                    } elseif (file_exists($path)) {
                        $success = $zpk->addFile($path, $this->fixZipPath($currentZipFolder.'/'.basename($path)));
                        if (!$success) {
                            throw new \Zend\ServiceManager\Exception\RuntimeException("Path '$path' cannot be added zpk");
                        }
                    } else {
                        throw new \Zend\ServiceManager\Exception\RuntimeException("Path '$path' is not existing. Verify your deployment.properties!");
                    }
                }
                $dir->close();
            }
        }
    }

    /**
     * Gets properties from file.
     *
     * @param  string $file
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
        foreach ($lines as $i=>$line) {
            $line = trim($line);

            if (empty($line) || (!$isWaitingOtherLine && strpos($line,"#") === 0)) {
                continue;
            }

            if (!$isWaitingOtherLine) {
                $key = trim(substr($line,0,strpos($line,'=')));
                $value = substr($line,strpos($line,'=') + 1, strlen($line));
            } else {
                $value .= trim($line);
            }

            /* Check if ends with single '\' */
            if (strrpos($value,"\\") === strlen($value)-strlen("\\")) {
                $value = substr($value, 0, strlen($value)-1)."\n";
                $isWaitingOtherLine = true;
            } else {
                $isWaitingOtherLine = false;
            }

            $properties[$key] = $value;
        }

        foreach ($properties as &$data) {
            $data = explode(',',trim($data));
            array_walk($data, function (&$item, $key) { $item = trim($item); });
        }

        return $properties;
    }

    /**
     * Validates the existence of the files in the deployment.properties
     * @param  array            $properties
     * @throws RuntimeException
     */
    public function validateProperties($folder)
    {
        $properties = $this->getProperties($folder.'/deployment.properties');

        $map = array(
            'appdir.includes',
            'scriptsdir.includes'
        );

        foreach ($map as $key) {
            if (!isset($properties[$key])) {
                continue;
            }

            $error = "";
            $files = $properties[$key];
            foreach ($files as $file) {
                $path = $folder.'/'.trim($file);
                if (!file_exists($path)) {
                    $error.="File/folder does not exist: ".$path."\n";
                }
            }
            if ($error) {
                throw new RuntimeException($error);
            }
        }
    }

    /**
     * Fixes the order of the keys in the meta data
     * @param  array $xsd
     * @return array
     */
    protected static function fixMetaKeyOrder(array $data)
    {
        if (!isset(self::$keyOrder)) {
            // read the key order
            $doc = new \DOMDocument();
            $doc->load(__DIR__.'/../../../config/zpk/schema.xsd');
            $xsd = \LSS\XML2Array::createArray($doc);

            self::$keyOrder = array(
                '@attributes'
            );

            foreach ($xsd["xs:schema"]["xs:element"][0]["xs:complexType"]["xs:sequence"]["xs:element"] as $element) {
                if (isset($element['@attributes']['name'])) {
                    $name = $element['@attributes']['name'];
                } elseif ($element['@attributes']['ref']) {
                    $name = $element['@attributes']['ref'];
                }

                if (!$name) {
                    continue;
                }
                self::$keyOrder[] = $name;
            }
        }

        $meta = array();
        foreach (self::$keyOrder as $key) {
            if (isset($data[$key])) {
                $meta[$key] = $data[$key];
            }
        }

        return $meta;

    }
}

<?php
namespace ZendSDK\Service;

use Zend\Stdlib\ErrorHandler;
use ZendServerWebApi\Service\ZpkInvokable as ZpkWebAPI;

/**
 * ZPK Service
 */
class ZpkInvokable extends ZpkWebAPI
{
    /**
     * Adds deployment support to an existing PHP application
     * @param string $sourceFolder
     */
    public function create($sourceFolder)
    {
        $sourceFolder = realpath($sourceFolder);
        if(file_exists($sourceFolder."/deployment.xml")) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('The specified directory already has deployment.xml.');
        }

        if(!is_dir($sourceFolder)) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('The source folder parameter must be real directory.');
        }

        ErrorHandler::start();
        copy(__DIR__.'/../../../config/zpk/deployment.xml', $sourceFolder."/deployment.xml");
        copy(__DIR__.'/../../../config/zpk/deployment.properties', $sourceFolder."/deployment.properties");
        ErrorHandler::stop(true);
    }

    /**
     * Creates a package from the data in the source folder
     * @param string $sourceFolder
     * @param string $destinationFolder
     */
    public function pack($sourceFolder, $destinationFolder=".")
    {
        $sourceFolder = realpath($sourceFolder);
        if(!file_exists($sourceFolder."/deployment.xml")) {
            throw new \Zend\ServiceManager\Exception\RuntimeException('The specified directory does not have deployment.xml.');
        }

        // get the current meta information
        $xml = new \SimpleXMLElement(file_get_contents($sourceFolder."/deployment.xml"));
        $name 	 	= sprintf("%s", $xml->name);
        $version 	= sprintf("%s", $xml->version->release);
        $appDir  	= sprintf("%s", $xml->appdir);
        $scriptsDir = sprintf("%s", $xml->scriptsdir);

        $properties = $this->getProperties($sourceFolder."/deployment.properties");

        $outZipPath = $destinationFolder."/$name-$version.zpk";

        $ext = new \ReflectionExtension('zip');
        $zipVersion = $ext->getVersion();
        if(!version_compare($zipVersion,'1.11.0','>=')) {
            error_log("WARNING: Non-Ascii file/folder names are supported only with PHP zip extension >=1.11.0 (your version is: $zipVersion)\n\t(http://pecl.php.net/package-changelog.php?package=zip&release=1.11.0)");
        }

        $zpk = new \ZipArchive();
        $zpk->open($outZipPath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
        $zpk->addFile($sourceFolder."/deployment.xml", 'deployment.xml');
        $folderMap = array(
            'appdir.includes' 	  => $appDir,
            'scriptsdir.includes' => '',
        );
        ErrorHandler::start();
        foreach($folderMap as $key => $baseDir) {
            if($baseDir) {
                $baseDir .= '/';
            }
            foreach($properties[$key] as $path) {
                $path = trim($path);
                $fullPath = $sourceFolder.'/'.$path;
                if(is_file($fullPath)) {
                    $zpk->addFile($fullPath, $this->fixZipPath($baseDir.$path));
                } else if(is_dir($fullPath)) {
                    $this->addDir($zpk, $fullPath, $baseDir);
                } else {
                    throw new \Zend\ServiceManager\Exception\RuntimeException("Path '$fullPath' is not existing. Verify your deployment.properties!");
                }
            }
        }
        ErrorHandler::stop(true);
        $zpk->close();

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
}

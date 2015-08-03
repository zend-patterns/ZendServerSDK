<?php
namespace Client\Service;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Zend\Stdlib\ErrorHandler;
use Zend\Console\Exception\RuntimeException;

/**
 * ZPK Service
 */
class ZpkInvokable
{

    const TYPE_LIBRARY = 'library';

    protected static $keyOrder;

    /**
     *
     * @param string $filename
     * @return \SimpleXMLElement
     */
    public function getMeta($filename)
    {
        $zip = new \ZipArchive();

        ErrorHandler::start();
        $zip->open($filename);
        $content = $zip->getFromName('deployment.xml');
        $zip->close();
        ErrorHandler::stop(true);

        if (! $content) {
            throw new RuntimeException('Missing deployment.xml in the zpk file.');
        }

        $xml = new \SimpleXMLElement($content);

        return $xml;
    }

    public function updateMeta($folder, array $updates)
    {
        $file = $folder . '/deployment.xml';
        $content = $this->updateXML($file, $updates);
        if ($content) {
            file_put_contents($file, $content);
        }
    }

    /**
     * Converts associative array with key and velue into ZPK parameters
     *
     * @param string $folder
     *            location of the deployment.xml file
     * @param array $userParams
     */
    public function updateParameters($folder, array $userParams)
    {
        // <parameter display="test" id="test" readonly="false" required="false" type="string">
        // <defaultvalue>test</defaultvalue>
        // </parameter>
        $parameterUpdates = array();
        $i = 0;
        foreach ($userParams as $key => $value) {
            $parameterUpdates[$i] = array(
                '@attributes' => array(
                    'display' => $key,
                    'id' => 'COMPOSER_' . $key,
                    'required' => $value ? 'true' : 'false',
                    'type' => 'string'
                )
            );

            if ($value) {
                $parameterUpdates[$i]['defaultvalue'] = $value;
            }
            $i ++;
        }

        return $this->updateMeta($folder, array(
            'parameters' => array(
                'parameter' => $parameterUpdates
            )
        ));
    }

    /**
     * Simple XML update
     *
     * @param string $file
     * @param array $updates
     * @return string
     * @throws RuntimeException
     */
    protected function updateXML($file, array $updates)
    {
        // Load the current data
        $content = file_get_contents($file);
        if (! $content) {
            throw new RuntimeException('Missing deployment.xml in the zpk file.');
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
     *
     * @param string $zpkFile
     * @param string $internalPath
     * @return string
     */
    public function getFileContent($zpkFile, $internalPath)
    {
        $zip = new \ZipArchive();

        ErrorHandler::start();
        $zip->open($zpkFile);
        $content = $zip->getFromName($internalPath);
        $zip->close();
        ErrorHandler::stop(true);

        return $content;
    }

    /**
     * Extracts the content from a packed file in the zpk.
     *
     * @param string $zpkFile
     * @param string $internalPath
     * @return string
     */
    public function setFileContent($zpkFile, $internalPath, $content)
    {
        $internalPath = $this->fixZipPath($internalPath);
        $zip = new \ZipArchive();

        ErrorHandler::start();
        $zip->open($zpkFile);
        $content = $zip->addFromString($internalPath, $content);
        $zip->close();
        ErrorHandler::stop(true);

        return $content;
    }

    /**
     * Validates the deployment.xml against the specified schema.xsd
     *
     * @param string $content
     * @throws \DOMException
     */
    public function validateXml($content)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($content);

        libxml_use_internal_errors(true);
        if (! $dom->schemaValidate(__DIR__ . '/../../../config/zpk/schema.xsd')) {
            $message = "";
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $message .= $error->message;
            }
            libxml_clear_errors();
            throw new \DOMException($message);
        }
    }

    /**
     * Fixes deployment.xml file
     *
     * @param string $content
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
     *
     * @param string $sourceFolder
     */
    public function create($sourceFolder, array $updates = null,
        array $properties = null)
    {
        if (file_exists($sourceFolder . "/deployment.xml")) {
            error_log('WARNING: The specified directory already has
                deployment.xml.');

            return false;
        }

        if (! is_dir($sourceFolder)) {
            throw new RuntimeException('The source folder parameter must be a
                real directory.');
        }

        ErrorHandler::start();
        copy(__DIR__ . '/../../../config/zpk/deployment.xml',
            $sourceFolder . "/deployment.xml");
        copy(__DIR__ . '/../../../config/zpk/deployment.properties',
            $sourceFolder . "/deployment.properties");
        if ($updates !== null) {
            $file = $sourceFolder . "/deployment.xml";
            $content = $this->updateXML($file, $updates);
            file_put_contents($file, $content);
        }
        if ($properties !== null) {
            // @TODO
        }

        ErrorHandler::stop(true);

        return true;
    }

    /**
     * Creates a package from the data in the source folder
     *
     * @param string $sourceFolder
     * @param string $destinationFolder
     * @param string $fileName
     * @param array $extraProperties
     * @param string $customVersion
     *
     * @return string path to the created zpk
     */
    public function pack($sourceFolder, $destinationFolder = ".",
        $fileName = null, array $extraProperties = null, $customVersion = "")
    {
        if (! file_exists($sourceFolder . "/deployment.xml")) {
            throw new RuntimeException('The specified directory does not have
                deployment.xml.');
        }

        // get the current meta information
        $xml = new \SimpleXMLElement(file_get_contents($sourceFolder .
            "/deployment.xml"));
        $name = sprintf("%s", $xml->name);
        $version = sprintf("%s", $xml->version->release);
        $appDir = trim(sprintf("%s", $xml->appdir));
        $scriptsDir = trim(sprintf("%s", $xml->scriptsdir));
        $type = sprintf("%s", $xml->type);
        $icon = sprintf("%s", $xml->icon);

        if (! empty($customVersion)) {
            $version = $customVersion;
            $xml->version->release = $version;
            $xml->asXML($sourceFolder . "/deployment.xml");
            $fixedContent = $this->updateXML($sourceFolder . "/deployment.xml",
                array());
            if ($fixedContent) {
                file_put_contents($sourceFolder . "/deployment.xml",
                    $fixedContent);
            }
        }
        $properties = $this->getProperties($sourceFolder .
            "/deployment.properties");
        if ($extraProperties !== null) {
            $properties = array_merge_recursive($properties, $extraProperties);
            foreach ($properties as $key => $value) {
                $properties[$key] = array_unique($value);
            }
        }

        if (! $fileName) {
            $fileName = "$name-$version.zpk";
        }
        $fileName = str_replace(array(
            '/'
        ), array(
            '.'
        ), $fileName);

        $outZipPath = $destinationFolder . '/' . $fileName;

        $ext = new \ReflectionExtension('zip');
        $zipVersion = $ext->getVersion();
        if (! version_compare($zipVersion, '1.11.0', '>=')) {
            error_log("WARNING: Non-Ascii file/folder names are supported only
                with PHP zip extension >=1.11.0 (your version is: $zipVersion)
                \n\t(http://pecl.php.net/package-changelog.php?package=zip&release=1.11.0)");
        }

        $zpk = new \ZipArchive();
        $zpk->open($outZipPath, \ZIPARCHIVE::CREATE | \ZIPARCHIVE::OVERWRITE);
        $zpk->addFile($sourceFolder . "/deployment.xml", 'deployment.xml');
        // Add the icon file that was specified!
        if (! empty($icon)) {
            $zpk->addFile($sourceFolder . "/" . $icon, $icon);
        }

        // Get the include map
        $includeMap = array();
        if ($type == self::TYPE_LIBRARY) {
            $appDir = '';
        }
        $includeMap['appdir'] = $this->getAppPaths($appDir,
            $properties['appdir.includes']);

        // get script paths
        if (! empty($scriptsDir) && isset($properties['scriptsdir.includes'])) {
            $includeMap['scriptsdir'] = $this->getScriptPaths($scriptsDir,
                $properties['scriptsdir.includes'], $sourceFolder);
        }

        ErrorHandler::start();
        foreach ($includeMap as $type => $paths) {
            $excludes = array();
            if (isset($properties[$type . '.excludes'])) {
                $excludes = $properties[$type . '.excludes'];
            }

            $excludedPatterns = array();
            $normalizedExclude = array();
            foreach ($excludes as $index => $exclude) {
                $exclude = trim($exclude);
                $exclude = rtrim($exclude, '/'); // no trailing slashes
                if (strlen($exclude) == 0) {} else
                    if (preg_match("/^\*\*\/(.*?)$/", $exclude, $matches)) {
                        $excludedPatterns[$exclude] = $matches[1];
                        unset($excludes[$index]);
                    } else {
                        $normalizedExclude[$index] =
                        $this->normalizePath($exclude);
                    }
            }
            $excludes = $normalizedExclude;
            $excludedExpression =
                $this->createRegexExpression($excludedPatterns);
            foreach ($paths as $localPath => $zpkPath) {
                $this->addPathToZpk($zpk, $sourceFolder, $localPath, $zpkPath,
                    $excludes, $excludedExpression);
            }
        }

        if (! $zpk->close()) {
            throw new RuntimeException('Failed creating zpk file: '
                . $outZipPath . ". " . $zpk->getStatusString());
        }
        ErrorHandler::stop(true);

        return $outZipPath;
    }

    public function getAppPaths($appDir, array $includes)
    {
        $zpkPaths = array();
        if (empty($includes)) {
            return array(
                '.' => $appDir . '/'
            );
        }

        foreach ($includes as $path) {
            $zpkPaths[$path] = $appDir . '/' . $path;
        }

        return $zpkPaths;
    }

    /**
     * Gets list of script files and directories
     *
     * @param string $scriptsDir
     *            the ZPK directory file name where the scripts will be stored
     * @param array $includes
     *            files to be included
     * @param string $sourceFolder
     *            is used as a base directory for the included paths
     * @return array key is the local path, without the sourceFolder
     *         value is the desired path in the ZPK file.
     */
    public function getScriptPaths($scriptsDir, array $includes, $sourceFolder)
    {
        $zpkPaths = array();

        if (count($includes) == 1) {
            $path = $includes[0];
            $localFiles = $this->getFilesOnly($path, $sourceFolder);

            foreach ($localFiles as $file) {
                $zpkPaths[$file] = $scriptsDir . '/' . basename($file);
            }
        } else {
            foreach ($includes as $path) {
                if (is_file($sourceFolder . '/' . $path)) {
                    $zpkPaths[$path] = $scriptsDir . '/' . basename($path);
                    continue;
                }

                $zpkPaths[$path] = $scriptsDir . '/' . $path;
            }
        }

        return $zpkPaths;
    }

    /**
     * Returns list of all files in the current path and sub-paths
     *
     * @param string $path
     *            path to start looking for files
     * @param string $baseDir
     *            if this is set then it will be prepended to the path during
     *            search, but it will not be included in the returned list
     *            of files
     * @return array list of files
     */
    private function getFilesOnly($path, $baseDir = '')
    {
        $startPos = 0;
        if (! empty($baseDir)) {
            $path = $baseDir . '/' . $path;
            $startPos = strlen($baseDir) + 1;
        }

        if (is_file($path)) {
            return array(
                substr($path, $startPos)
            );
        }

        $files = array();
        $paths = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
                RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($paths as $pathInfo) {
            if (! $pathInfo->isFile()) {
                continue;
            }
            $files[] = substr($pathInfo->getPathname(), $startPos);
        }

        return $files;
    }

    protected function normalizePath($path)
    {
        return preg_replace('/((\/{2,})|(\\\\{1,}))/', '/', $path);
    }

    protected function fixZipPath($path)
    {
        $path = $this->normalizePath($path);
        $path = trim($path, '/');

        return $path;
    }

    /**
     * Add a directory in zip
     *
     * @param ZipArchive $zpk
     * @param string $directory
     * @param string $baseDir
     * @param string $excludedExpression
     */
    protected function addPathToZpk($zpk, $sourceFolder, $localPath, $zpkPath,
        $excludes = array(), $excludedExpression = null)
    {
        $localPath = $this->normalizePath($localPath);
        if (in_array($localPath, $excludes)) {
            return;
        }
        if ($excludedExpression) {
            if (preg_match($excludedExpression, $localPath)) {
                return;
            }
        }

        $fullPath = $sourceFolder . '/' . $localPath;
        if (is_file($fullPath)) {
            $success = $zpk->addFile($fullPath, $this->fixZipPath($zpkPath));
            if (! $success) {
                throw new RuntimeException("Path '$fullPath'
                    cannot be added to zpk");
            }
            return;
        }

        if (! is_dir($fullPath)) {
            throw new RuntimeException("Path '$fullPath' does not exist.
                Verify your deployment.properties!");
        }

        // we are dealing with directories
        $entries = scandir($fullPath);
        // filter entries
        foreach ($entries as $idx => $name) {
            if (in_array($name, array(
                '.',
                '..'
            ))) {
                unset($entries[$idx]);
                continue;
            }

            foreach ($excludes as $exclude => $length) {
                if ($name === $exclude) {
                    unset($entries[$idx]);
                }
            }
        }
        if (count($entries) == 0) {
            $zpk->addEmptyDir($zpkPath);
            return;
        }

        foreach ($entries as $name) {
            $this->addPathToZpk($zpk, $sourceFolder, $localPath . '/' . $name,
                $zpkPath . '/' . $name, $excludes, $excludedExpression);
        }
    }

    /**
     * Gets properties from file.
     *
     * @param string $file
     * @return array
     *
     * @see http://blog.rafaelsanches.com/2009/08/05/reading-java-style-properties-file-in-php/ Adapted the solution from the URL above.
     */
    public function getProperties($file)
    {
        $lines = file($file);
        $properties = array();
        $key = "";
        $isWaitingOtherLine = false;
        foreach ($lines as $i => $line) {
            $line = trim($line);

            if (empty($line) || (! $isWaitingOtherLine && strpos($line, "#") === 0)) {
                continue;
            }

            if (! $isWaitingOtherLine) {
                $key = trim(substr($line, 0, strpos($line, '=')));
                $value = substr($line, strpos($line, '=') + 1, strlen($line));
            } else {
                $value .= trim($line);
            }

            /* Check if ends with single '\' */
            if (strrpos($value, "\\") === strlen($value) - strlen("\\")) {
                $value = substr($value, 0, strlen($value) - 1) . "\n";
                $isWaitingOtherLine = true;
            } else {
                $isWaitingOtherLine = false;
            }

            $properties[$key] = $value;
        }

        foreach ($properties as &$data) {
            $data = explode(',', trim($data));
            array_walk($data, function (&$item, $key) {
                $item = trim($item);
            });
        }

        return $properties;
    }

    /**
     * Validates the existence of the files in the deployment.properties
     *
     * @param array $properties
     * @throws RuntimeException
     */
    public function validateProperties($folder)
    {
        $properties = $this->getProperties($folder . '/deployment.properties');

        $map = array(
            'appdir.includes',
            'scriptsdir.includes'
        );

        foreach ($map as $key) {
            if (! isset($properties[$key])) {
                continue;
            }

            $error = "";
            $files = $properties[$key];
            foreach ($files as $file) {
                $path = $folder . '/' . trim($file);
                if (! file_exists($path)) {
                    $error .= "File/folder does not exist: " . $path . "\n";
                }
            }
            if ($error) {
                throw new RuntimeException($error);
            }
        }
    }

    /**
     * Fixes the order of the keys in the meta data
     *
     * @param array $xsd
     * @return array
     */
    protected static function fixMetaKeyOrder(array $data)
    {
        if (! isset(self::$keyOrder)) {
            // read the key order
            $doc = new \DOMDocument();
            $doc->load(__DIR__ . '/../../../config/zpk/schema.xsd');
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

                if (! $name) {
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

    /**
     * Creates a regular expression to be matched if \*\*\/<something> pattern
     * was defined on apddirs.exclude or scriptsdir.exclude.
     *
     * @param array $excludedPatterns
     *            The array with patterns
     */
    private function createRegexExpression($excludedPatterns = array())
    {
        if (! empty($excludedPatterns)) {
            $expression = "(";
            $index = 0;
            foreach ($excludedPatterns as $exclude => $pattern) {
                if (substr_compare($pattern, ".", 0, 1) >= 0) {
                    $pattern = "\\" . $pattern;
                }
                $expression = $expression . ($index > 0 ? "|" : "") . $pattern;
                $index ++;
            }
            $expression = $expression . ")";
            return "/^(.*)(\\/|\\\\)" . $expression . "(.*)$/";
        }
        return "";
    }
}

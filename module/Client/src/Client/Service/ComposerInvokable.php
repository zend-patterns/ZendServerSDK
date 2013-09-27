<?php
namespace Client\Service;

use Zend\Console\Exception\RuntimeException;

/**
 * Composer Service
 */
class ComposerInvokable
{
    protected static $tempFolders = array();

    /**
     * Runs the composer install command in the specified directory
     * @param string $folder
     */
    public function install($folder)
    {
        $location = $this->getComposer($folder);

        /**
         * Poor-man's package dependancy information and installation.
         * 1. Create a temp directory
         * 2. Copy the composer.json and composer.phar files to the temp directory
         * 3. Run composer in the temp directory.
         * 4. Get installed packages
         */

        $tempFolder = tempnam(sys_get_temp_dir(),'zsc');
        if (file_exists($tempFolder)) {
            unlink($tempFolder);
        }
        mkdir($tempFolder);

        copy($location, $tempFolder."/composer.phar");
        copy($folder.'/composer.json', $tempFolder.'/composer.json');

        $cwd = getcwd();
        chdir($tempFolder);

        $output = array();
        $retVal = 0;
        exec("php $location install --no-dev", $output, $retVal);

        $installedPackages = array();
        foreach ($output as $line) {
            // strip bash colors
            $line = preg_replace("/\x1B\[([0-9]{1,2}(;[0-9]{1,2})?)?[m|K]/","",$line);
            if (preg_match("/^  - Installing (.*?) \((.*?)\)$/", $line, $matches)) {
                $name = $matches[1];
                $version = $matches[2];
                $pos =strpos($version, ' ');
                if ($pos !== false) {
                    $version = substr($version, 0, $pos);
                }
                $installedPackages[$name] = $version;
            }
        }
        chdir($cwd);
        self::$tempFolders[] = $tempFolder;

        return array(
            'folder' => $tempFolder,
            'packages' => $installedPackages,
        );
    }

    /**
     * Gets the location of the composer.phar
     * @param  string           $folder
     * @throws RuntimeException
     */
    public function getComposer($folder)
    {
        $location = $folder.'/composer.phar';
        if (!file_exists($location)) {
            error_log("Downloading composer.phar...");
            $fp = fopen($location,'w+');
            if (!$fp) {
                throw new RuntimeException('Unable to write file '.$folder.'/composer.phar');
            }

            fwrite($fp, file_get_contents('http://getcomposer.org/composer.phar'));
            fclose($fp);
        }

        return $location;
    }

    /**
     * Gets meta data about a certain parameter
     * @param string $folder
     * @param string $parameter
     */
    public function getMeta($folder, $parameter)
    {
        $location = $folder.'/composer.json';
        $data = json_decode(file_get_contents($location), true);
        if ($data === null) {
            throw new RuntimeException('Unable to read meta data from '.$location);
        }

        return $data[$parameter];
    }

    // Move this later to an Util class
    public static function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? self::delTree("$dir/$file") : unlink("$dir/$file");
        }

        return rmdir($dir);
    }

    public function __destruct()
    {
        foreach(self::$tempFolders as $folder) {
            self::delTree($folder);
        }
    }
}

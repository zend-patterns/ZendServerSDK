<?php
namespace Client\Service;

use Zend\Console\Exception\RuntimeException;

/**
 * Composer Service
 */
class ComposerInvokable
{
    /**
     * Runs the composer install command in the specified directory
     * @param string $folder
     */
    public function install($folder)
    {
        $location = $this->getComposer($folder);
        /**
        $realServerArgs = $_SERVER['argv'];
        $_SERVER['argv'] = array(
            1 => array('install'),
        );

        require $location;
        $_SERVER['argv'] = $realServerArgs;
        */
        $output = array();
        $retVal = 0;
        $cwd = getcwd();
        chdir($folder);
        @rename("composer.lock","composer.lock.old");
        @rename("vendor","vendor.old");
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

        unlink("composer.lock");
        rename("composer.lock.old","composer.lock");

        self::delTree("vendor");
        rename("vendor.old","vendor");

        chdir($cwd);

        return $installedPackages;
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
}

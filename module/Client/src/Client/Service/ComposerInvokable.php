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
     * @return array list of installed packages and their versions
     */
    public function install($folder, $options = null)
    {
        error_log("Fetching composer packages...");
        $location = $this->getComposer($folder);

        /**
         * Poor-man's package dependancy information and installation.
         * If there is composer.lock file
         * 		use it to get the list of installed packages
         * Else
         *  	Run composer install
         *  	Get installed packages
         */

        $installedPackages = array();
        $lockFile = $folder.'/composer.lock';
        if(file_exists($lockFile)) {
            $data = json_decode(file_get_contents($lockFile), true);
            if ($data === null) {
                throw new RuntimeException('Unable to read meta data from '.$location);
            }

            foreach ($data['packages'] as $package) {
                $installedPackages[$package['name']] = $package['version'];
            }

            return $installedPackages;
        }

        $cwd = getcwd();
        chdir($folder);

        $command = "php $location install --no-dev --no-scripts";
        if (!is_null($options)) {
            $command = "php $location install $options";
        }

        $output = "";
        if($handle = popen($command, 'r')) {
            while(!feof($handle)) {
                $buffer = fread($handle, 1024);
                error_log($buffer);
                $output.= $buffer;
            }
            pclose($handle);
        }

        $lines = explode("\n",$output);
        foreach ($lines as $line) {
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
}

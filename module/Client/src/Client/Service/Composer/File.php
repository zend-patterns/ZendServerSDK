<?php
namespace Client\Service\Composer;

use Zend\Console\Exception\RuntimeException;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Zend\File\ClassFileLocator;

class File
{
    protected $scripts = array();

    protected function getScriptsDir($baseDir)
    {
        if (isset($this->scripts[$baseDir])) {
            return $this->scripts[$baseDir];
        }

        // Find the scripts directory and copy in it the composer.phar, composer.lock and composer.json files
        $xml = new \SimpleXMLElement(file_get_contents($baseDir . "/deployment.xml"));
        $scriptsDir = "$baseDir/scripts";
        if ($xml->scriptsdir) {
            $scriptsDir = $baseDir . "/" . $xml->scriptsdir;
        }
        if (! file_exists($scriptsDir)) {
            mkdir($scriptsDir);
        }

        $this->scripts[$baseDir] = $scriptsDir;

        return $scriptsDir;
    }

    public function copyComposerFiles($baseDir)
    {
        $scriptsDir = $this->getScriptsDir($baseDir);
        copy("$baseDir/composer.phar", "$scriptsDir/composer.phar");
        copy("$baseDir/composer.lock", "$scriptsDir/composer.lock");
        copy("$baseDir/composer.json", "$scriptsDir/composer.json");
    }

    /**
     * Adjusts the deployment.properties to
     * - exclude vendor/* directories
     * - include vendor/composer/*
     * - add the composer.* files to the script path
     *
     * @param string $baseDir
     * @param array  $properties
     */
    public function adjustDeploymentProperties($baseDir)
    {
        $properties['appdir.includes'][] = 'vendor';

        $properties['scriptsdir.includes'][] = "scripts/composer.json";
        $properties['scriptsdir.includes'][] = "scripts/composer.lock";
        $properties['scriptsdir.includes'][] = "scripts/composer.phar";
        $properties['scriptsdir.includes'][] = "scripts/post_stage.php";

        return $properties;
    }

    public function writePostStage($baseDir)
    {
        $scriptsDir = $this->getScriptsDir($baseDir);
        $data = file(__DIR__ . '/../../../../config/composer/post_stage.php');

        $postStageScript = "$scriptsDir/post_stage.php";
        if (file_exists($postStageScript)) {
            array_shift($data);
            $oldContent = file_get_contents($postStageScript);
            if (strpos($oldContent, $data['0'])!==false) {
                return;
            }
        }
        $fh = fopen($postStageScript, 'a+');

        fwrite($fh, "\n");

        $json = file_get_contents($baseDir . '/vendor/composer/installed.json');
        $jsonData = json_decode($json, true);

        $libLines = array();
        foreach ($jsonData as $package) {
            $nameParts = explode('/', $package['name']);
            $name = join('.', $nameParts);
            $libLines[] = <<<LINE
\$dir =  getenv('ZS_APPLICATION_BASE_DIR') . '/vendor/{$nameParts[0]}';
@mkdir(\$dir);
chdir(\$dir);
\$libDir = zend_deployment_library_path('$name');
shell_exec("ln -s \$libDir {$nameParts[1]}");


LINE;
        }

        fwrite($fh, "\n");
        foreach ($data as $line) {
            $line = str_replace('###libLines###', join("\n", $libLines), $line);
            fwrite($fh, $line);
        }

        fclose($fh);
    }

    /*
     * public function writeComposerJson($baseDir, ComposerInvokable $composer, $extraParams = array()) { // @TODO: needs some cosmetic surgery... $composer->setMeta($baseDir, 'autoload', array('files' => array('./autoload_zendserver.php'))); $composer->setMeta($baseDir, 'extra', $extraParams); }
     */

    /**
     * Adjusts the composer autoloader to use zend_library_path
     *
     * @param  string $folder
     * @return string - the directory where the temporary vendor/composer folder is located
     */
    public function adjustAutoloader($folder)
    {
        $lockFile = $folder . '/composer.lock';
        if (! file_exists($lockFile)) {
            return false;
        }

        $data = @json_decode(file_get_contents($lockFile), true);
        if ($data === null) {
            throw new RuntimeException('Unable to read meta data from ' . $lockFile);
        }

        $source = $folder . '/vendor/composer';
        $dest   = $folder . '/.vendor/composer';
        @mkdir($dest, 0775, true);

        // copy the vendor/composer directory to the  temporary directory
        foreach ($iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
            if ($item->isDir()) {
                mkdir($dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            } else {
                copy($item, $dest . DIRECTORY_SEPARATOR . $iterator->getSubPathName());
            }
        }
        copy($folder . '/vendor/autoload.php', $folder . '/.vendor/autoload.php');

        $classmapAutoloader = array();
        $namespaceAutoloader = array();
        $filesAutoloader = array();
        $includePaths = array();
        foreach ($data['packages'] as $package) {
            $normalizedVersion =  trim($package['version'], ' v');
            $normalizedName = str_replace('/', '.', $package['name']);
            $packageLocation = "zend_deployment_library_path('$normalizedName','$normalizedVersion').'/'";

            if (isset($package['autoload'])) {
                if (isset($package['autoload']['files'])) {
                    foreach ($package['autoload']['files'] as $file) {
                        $filesAutoloader[$file] = $packageLocation . '."' . $file . '"';
                    }
                }

                if (isset($package['autoload']['psr-0'])) {
                    /*
                     * From: 'Zend\\View\\' => array($vendorDir . '/zendframework/zend-view'), To: 'Zend\\View\\' => array(zend_deployment_library_path('zendframework/zend-view','version').''/zendframework/zend-view'),
                     */
                    foreach ($package['autoload']['psr-0'] as $namespace => $path) {
                        $namespaceAutoloader[$namespace] = 'array(' . $packageLocation . '."' . $path . '")';
                    }
                }

                if (isset($package['autoload']['classmap'])) {
                    /*
                     * From: 'ZendServerWebApi\\Module' => $vendorDir . '/zenddevops/webapi/Module.php', To 'ZendServerWebApi\\Module' => zend_deployment_library_path('zenddevops/webapi','version') "./Module.php',
                     */
                    foreach ($package['autoload']['classmap'] as $classMap) {
                        $libraryPath = $folder.'/vendor/'.$package['name'].'/'.$classMap;
                        if (is_dir($libraryPath)) {
                            $l = new ClassFileLocator($libraryPath);
                            foreach ($l as $file) {
                                $filename  = str_replace($libraryPath . '/', '', str_replace(DIRECTORY_SEPARATOR, '/', $file->getPath()) . '/' . $file->getFilename());

                                // Add in relative path to library
                                $filename  = $packageLocation . '."'. $filename.'"';

                                foreach ($file->getClasses() as $class) {
                                    $classmapAutoloader[$class] = $filename;
                                }
                            }
                        } else {
                            $dirName = dirname($libraryPath);
                            $baseName = basename($libraryPath);
                            $l = new ClassFileLocator($dirName);
                            foreach ($l as $file) {
                                if ($file->getFilename()!= $baseName) {
                                    continue;
                                }

                                $filename  = $packageLocation . '."'. $classMap.'"';
                                foreach ($file->getClasses() as $class) {
                                    $classmapAutoloader[$class] = $filename;
                                }
                                break;
                            }
                        }
                    }
                }

                if (isset($package['include-path'])) {
                    $paths = array();
                    foreach ($package['include-path'] as $path) {
                        $paths[] = $packageLocation.'."'. $path.'"';
                    }

                    $includePaths = array_unique(($includePaths + $paths));
                }
            }
        }

        // Overwrite the files autoloader
        if (file_exists("$dest/autoload_files.php")) {
            $filesOriginalAutoloader = include "$dest/autoload_files.php";
            if (is_array($filesOriginalAutoloader)) {
                $content = sprintf('<?php
$vendorDir = dirname(dirname(__FILE__));
$baseDir = dirname($vendorDir);

return array(
             %s
        );
', implode(",\n", $filesAutoloader));
                file_put_contents("$dest/autoload_files.php", $content);
            }
        }

        // Overwrite the classmap autoloader
        if (file_exists("$dest/autoload_classmap.php")) {
            $classMapOriginalAutoloader = include "$dest/autoload_classmap.php";
            if (is_array($classMapOriginalAutoloader)) {
                $data = '';
                foreach ($classmapAutoloader as $class => $path) {
                    $data .= "'" . addslashes($class) . "'=> $path,\n";
                }

                $content = sprintf('<?php
    $vendorDir = dirname(dirname(__FILE__));
    $baseDir = dirname($vendorDir);

    return array(
                 %s
            );
    ', $data);
                file_put_contents("$dest/autoload_classmap.php", $content);
            }
        }

        // Overwrite the namespace autoloader
        if (file_exists("$dest/autoload_namespaces.php")) {
            $namespacesOriginalAutoloader = include "$dest/autoload_namespaces.php";
            if (is_array($namespacesOriginalAutoloader)) {
                $autoloaderArray = "";
                foreach ($namespacesOriginalAutoloader as $key => $value) {
                    if (isset($namespaceAutoloader[$key])) {
                        $value = $namespaceAutoloader[$key];
                    } else {
                        unset($namespacesOriginalAutoloader[$key]);
                        continue;
                    }

                    $autoloaderArray .= "'" . addslashes($key) . "'=> ";
                    if (is_array($value)) {
                        $autoloaderArray .= var_export($value, true);
                    } else {
                        $autoloaderArray .= $value;
                    }
                    $autoloaderArray .= ",\n";
                }

                $autoloaderArray = str_replace("'$baseDir", '$baseDir.\'', $autoloaderArray);
                $content = sprintf('<?php
    $vendorDir = dirname(dirname(__FILE__));
    $baseDir = dirname($vendorDir);

    return array(%s);
    ', $autoloaderArray);

                file_put_contents("$dest/autoload_namespaces.php", $content);
            }
        }

        // Overwrite the include_files autoloader
        if (file_exists("$dest/include_paths.php")) {
            $content = sprintf('<?php
    $vendorDir = dirname(dirname(__FILE__));
    $baseDir = dirname($vendorDir);

    return array(%s);
    ', implode(",\n", $includePaths));

            file_put_contents("$dest/include_paths.php", $content);
        }

        return $folder . '/.vendor/';
    }
}

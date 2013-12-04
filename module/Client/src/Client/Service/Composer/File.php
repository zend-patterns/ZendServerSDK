<?php
namespace Client\Service\Composer;

use Zend\Console\Exception\RuntimeException;
use Client\Service\ComposerInvokable;

class File {
    public function copyComposerFiles($baseDir, $scriptsDir) {
        copy("$baseDir/composer.phar", "$scriptsDir/composer.phar");
        copy("$baseDir/composer.lock", "$scriptsDir/composer.lock");
        copy("$baseDir/composer.json", "$scriptsDir/composer.json");
    }
    
    public function writeDeploymentProperties($baseDir) {
        // @TODO: we need a more dynamic approach!
        $deploymentProperties = <<< DEPLOYMENT_PROPERTIES
appdir.includes = \
    app,\
    vendor,\
    autoload_zendserver.php
appdir.excludes = \
    vendor/incenteev,\
    vendor/psr
scriptsdir.includes = \
    scripts/composer.json,\
    scripts/composer.lock,\
    scripts/composer.phar,\
    scripts/post_stage.php
DEPLOYMENT_PROPERTIES;
        
        file_put_contents("$baseDir/deployment.properties", $deploymentProperties);
    }
    
    public function writePostStage($baseDir) {
        // @TODO: creates post_stage.php script that adds at the end of an existing one the code needed to run composer.phar run-script [all] -n on the server.
        // @todo: decide whether it's an update or fresh install
        $postStage = <<<POST_STAGE
<?php
require_once zend_deployment_library_path('ZendServerDeploymentHelper') . '/deph.php';
\$deph = new DepH();
        
\$log = \$deph->startGuiLog();
        
\$shell = \$deph->getShell();
\$shell->exec('chmod 744 ' . __DIR__ . '/composer.phar');
\$shell->exec('mv ' . __DIR__ . '/composer.json ' . getenv('ZS_APPLICATION_BASE_DIR'));
#\$shell->exec('cd ' . __DIR__ . '; /usr/local/zend/bin/php ./composer.phar run-script post-install-cmd -n -d ' . getenv('ZS_APPLICATION_BASE_DIR'));
\$shell->exec('COMPOSER_HOME="/mnt/hgfs/sandboxx/composer-phar/bin" /usr/local/zend/bin/php /mnt/hgfs/sandboxx/composer-phar/bin/composer.php run-script post-install-cmd -n -d ' . getenv('ZS_APPLICATION_BASE_DIR'));
\$shell->exec('rm -f ' . getenv('ZS_APPLICATION_BASE_DIR') . '/composer.json ');
?>
POST_STAGE;
        
        file_put_contents("$baseDir/scripts/post_stage.php", $postStage);
    }
    
    public function writeComposerJson($baseDir, ComposerInvokable $composer, $extraParams = array()) {
        // @TODO: needs some cosmetic surgery...
        $composer->setMeta($baseDir, 'autoload', array('files' => array('./autoload_zendserver.php')));
        
        $composer->setMeta($baseDir, 'extra', $extraParams);
    }
    
    public function writeAutoloadZendserver($baseDir, $packages, $dependandPackages) {
        $installedJson = json_decode(file_get_contents($baseDir."/vendor/composer/installed.json"), true);
        if ($installedJson === null) {
            #throw new RuntimeException('Unable to read meta data from '.$baseDir."/vendor/composer/installed.json");
        }
        
        $libsArr = array();
        foreach ($installedJson as $package) {
            if (!array_key_exists($package['name'], $dependandPackages)) continue;
        
            // @todo: how to get the namespace correctly?
            if (isset($package['target-dir'])) {
                $namespace = str_replace('/', '\\', $package['target-dir']);
            }
            else {
                $parts = explode('/', $package['name']);
                $parts = array_map(function ($a) {return ucfirst($a);}, $parts);
                $namespace = join('\\', $parts);
            }
            //@todo: where to take the version number? from $packages[$package['name']] (CL output) or json definition?
            $version = $packages[$package['name']];
            if ($version == 'dev-master') $version = '999.dev-master';
            $libsArr[] = "'$namespace' => array(zend_deployment_library_path('{$package['name']}', '$version'))";
        }
        $libsArr = join(",\n", $libsArr);
        $autoloadZS = <<<AUTOLOAD
<?php
\$libs = array(
    $libsArr
);
        
foreach (\$libs as \$namespace => \$path) {
    \$loader->set(\$namespace, \$path);
}
?>
AUTOLOAD;
        
                file_put_contents("$baseDir/autoload_zendserver.php", $autoloadZS);
    }
}
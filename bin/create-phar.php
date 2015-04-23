#!/usr/bin/env php
<?php
/**
 * Create zs-client.phar
 *
 * @link      http://github.com/zendframework/ZFTool for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

$srcRoot   = dirname(__DIR__);
$filename  = 'zs-client.phar';
$pharPath = __DIR__ . "/$filename";

if (file_exists($pharPath)) {
    unlink($pharPath);
}

$phar = new \Phar($pharPath, 0, $filename);
$phar->startBuffering();

// remove the first line in the index file
$phar->addFromString("index.php", substr(php_strip_whitespace("$srcRoot/bin/zs-client.php"), 19));

addDir($phar, "$srcRoot/vendor", $srcRoot);
if (is_dir("$srcRoot/config")) {
    addDir($phar, "$srcRoot/config", $srcRoot);
}
addDir($phar, "$srcRoot/module", $srcRoot);

$stub = <<<EOF
#!/usr/bin/env php
<?php
/*
 * This file is part of Zend Server WebAPI command line tool
 *
 * @link      http://github.com/zendframework/ZFTool for the canonical source repository
 * @copyright Copyright (c) 2005-2013 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */
define('PHAR', true);
define('CWD', getcwd());
chdir(dirname(__DIR__));
Phar::mapPhar('$filename');
require 'phar://$filename/index.php';
__HALT_COMPILER();

EOF;

$phar->setStub($stub);
$phar->stopBuffering();

if (file_exists($pharPath)) {
    echo "Phar created successfully in $pharPath\n";
    chmod($pharPath, 0755);
} else {
    echo "Error during the compile of the Phar file $pharPath\n";
    exit(2);
}

/**
 * Add a directory in phar removing whitespaces from PHP source code
 *
 * @param Phar $phar
 * @param string $sDir
 */
function addDir($phar, $sDir, $baseDir = null)
{
    $oDir = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($sDir),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $allowedExtensions = array(
        'php', 'phtml', 'xsd', 'xml', 'properties'
    );

    foreach ($oDir as $sFile) {
        if (in_array(pathinfo($sFile, PATHINFO_EXTENSION), $allowedExtensions)) {
            addFile($phar, $sFile, $baseDir);
        }
    }
}

/**
 * Add a file in phar removing whitespaces from the file
 *
 * @param Phar $phar
 * @param string $sFile
 */
function addFile($phar, $sFile, $baseDir = null)
{
    if (null !== $baseDir) {
        $phar->addFromString(substr($sFile, strlen($baseDir) + 1), php_strip_whitespace($sFile));
    } else {
        $phar->addFromString($sFile, php_strip_whitespace($sFile));
    }
}

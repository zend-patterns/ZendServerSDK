<?php

$baseDir = dirname(__DIR__);
chdir($baseDir);

$text = file_get_contents('composer.json');
$json = json_decode($text, true);

$version = $json['version'];

$deploymentSample  = file_get_contents('build/deployment.xml');
$deploymentSample = str_replace('#VERSION#', $version, $deploymentSample);

# Prepare the build directory
$tempName = tempnam(sys_get_temp_dir(), 'zsclient_');
unlink($tempName);
mkdir($tempName);
file_put_contents($tempName.'/deployment.xml', $deploymentSample);
mkdir($tempName."/library");
copy("bin/zs-client.phar", $tempName."/library/zs-client.phar");
copy("LICENSE.txt", $tempName."/LICENSE.txt");
copy("build/deployment.properties", $tempName."/deployment.properties");


$output = exec("php bin/zs-client.phar packZpk --folder='$tempName' --destination='$baseDir/build' --name='ZendServerSDK.zpk' ");
echo "$output\n";

function removeFolderRecustively($folder)
{
    $directoryIterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($folder, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
        );
    foreach ($directoryIterator as $entry) {
        if ($entry->isDir() && !$entry->isLink()) {
            rmdir($entry->getPathname());
        } else {
            unlink($entry->getPathname());
        }
    }
}

removeFolderRecustively($tempName);

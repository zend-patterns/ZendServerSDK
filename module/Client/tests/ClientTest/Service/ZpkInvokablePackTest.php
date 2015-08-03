<?php
namespace ClientTest\Service;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ClientTest\Framework\ZpkTestCase;

class ZpkInvokablePackTest extends ZpkTestCase
{
    protected $projectFiles = array();

    public function setUp()
    {
        parent::setUp();

        $projectZip = dirname(dirname(__DIR__)).'/samples/project.zip';

        $zip = new \ZipArchive();
        if ($zip->open($projectZip) !== true) {
            throw new \Exception("Unable to unpack sample project!");
        }

        $zip->extractTo($this->tempDir);
        $zip->close();

        $this->projectFiles = $this->getEntriesRecursive('', $this->tempDir);
    }

    public function testProjectPack()
    {
        $zpkPath = $this->zpkService->pack($this->tempDir, $this->tempDir);
        $zpkFiles = $this->getZpkEntriesRecursively($zpkPath);

        $actual = array_keys($zpkFiles);
        $expected   = array(
            'deployment.xml',
            'data/composer.json',
            'data/SubDir/SubSubDir/Test.php',
            'data/module/FolderEndingWith.svn/content.txt',
            'data/EmptyDir/',
            'data/public/.htaccess',
            'data/public/index.php',
            'data/module/fileEndingWith.svn',
            'data/module/.svntobeincluded/content.txt',
            'data/module/Client/autoload_classmap.php',
            'scriptsdir/zend/scripts/pre_activate.php',
            'scriptsdir/zend/scripts/post_stage.php',
            'scriptsdir/zend/pre_stage.php',
        );

        $this->assertEquals(array_diff($actual, $expected), array());
        $this->assertEquals(array_diff($expected, $actual), array());

        $this->assertContains('data/EmptyDir/', $actual, 'Unable to find EmptyDir.');
    }

    public function testProjectPackNoSvnExclusions()
    {
        //remove the **/.svn from the excluded properties
        $propContent = file_get_contents($this->tempDir.'/deployment.properties');
        $propContent = str_replace('**/.svn,', '', $propContent);
        file_put_contents($this->tempDir.'/deployment.properties', $propContent);

        $zpkPath = $this->zpkService->pack($this->tempDir, $this->tempDir);
        $zpkFiles = $this->getZpkEntriesRecursively($zpkPath);

        $actual = array_keys($zpkFiles);
        $expected   = array(
            'deployment.xml',
            'data/composer.json',
            'data/SubDir/.svn/NOT_IN_THE_PACKAGE',
            'data/SubDir/SubSubDir/.svn/NOT_IN_THE_PACKAGE',
            'data/SubDir/SubSubDir/Test.php',
            'data/module/FolderEndingWith.svn/content.txt',
            'data/module/FolderEndingWith.svn/.svn/NOT_IN_THE_PACKAGE',
            'data/module/.svn/NOT_IN_THE_PACKAGE',
            'data/EmptyDir/',
            'data/public/.htaccess',
            'data/public/index.php',
            'data/public/.svn/NOT_IN_THE_PACKAGE',
            'data/module/fileEndingWith.svn',
            'data/module/.svntobeincluded/content.txt',
            'data/module/Client/autoload_classmap.php',
            'data/module/Client/.svn/NOT_IN_THE_PACKAGE',
            'scriptsdir/zend/scripts/pre_activate.php',
            'scriptsdir/zend/scripts/post_stage.php',
            'scriptsdir/zend/scripts/.svn/NOT_IN_THE_PACKAGE',
            'scriptsdir/zend/pre_stage.php',
        );

        $this->assertEquals(array_diff($actual, $expected), array());
        $this->assertEquals(array_diff($expected, $actual), array());

        $this->assertContains('data/EmptyDir/', $actual, 'Unable to find EmptyDir.');
    }

    public function testLibraryPack()
    {
        $this->zpkService->updateMeta($this->tempDir, array('type'=>'library'));

        // Remove SubDir from the included directories
        $propContent = file_get_contents($this->tempDir.'/deployment.properties');
        $propContent = str_replace('SubDir,\\', 'public,\\', $propContent);
        file_put_contents($this->tempDir.'/deployment.properties', $propContent);

        $zpkPath = $this->zpkService->pack($this->tempDir, $this->tempDir);
        $zpkFiles = $this->getZpkEntriesRecursively($zpkPath);

        $actual = array_keys($zpkFiles);
        $expected   = array(
            'deployment.xml',
            '/EmptyDir/',
            'public/.htaccess',
            'composer.json',
            'module/FolderEndingWith.svn/content.txt',
            'module/fileEndingWith.svn',
            'module/.svntobeincluded/content.txt',
            'module/Client/autoload_classmap.php',
            'public/index.php',
            'scriptsdir/zend/scripts/pre_activate.php',
            'scriptsdir/zend/scripts/post_stage.php',
            'scriptsdir/zend/pre_stage.php',
        );

        $this->assertEquals(array_diff($actual, $expected), array());
        $this->assertEquals(array_diff($expected, $actual), array());
        $this->assertContains('/EmptyDir/', $actual, 'Unable to find EmptyDir.');
    }

    /**
     * Get all files and folder in the given path
     *
     * @param string $path
     * @return array
     */
    private function getEntriesRecursive($path, $baseDir='')
    {
        $paths = array();

        $startPos = 0;
        if (!empty($baseDir)) {
            $startPos = strlen($baseDir)+1;
            $path = $baseDir.'/'.$path;
        }

        if (!file_exists($path)) {
            $paths[] = substr($path, $startPos);
        } else {
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($files as $fileinfo) {
                $paths[] = substr($fileinfo->getPathname(), $startPos);
            }
        }

        return $paths;
    }

    /**
     * Get all files and folder in the ZPK
     *
     * @param string $zpkPath
     * @throws \Exception
     * @return array
     *              key - name of the file
     *              value - stat data (see: http://php.net/manual/en/ziparchive.statindex.php)
     */
    private function getZpkEntriesRecursively($zpkPath)
    {
        $paths = array();
        $zpk = new \ZipArchive();
        if ($zpk->open($zpkPath) !== true) {
            throw new \Exception("Unable to open ZPK file");
        }

        for ($i = 0; $i < $zpk->numFiles; $i++) {
            $stat = $zpk->statIndex($i);
            $paths[$stat['name']] = $stat;
        }

        return $paths;
    }
}

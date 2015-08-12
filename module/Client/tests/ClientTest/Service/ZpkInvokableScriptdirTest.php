<?php
namespace ClientTest\Service;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use ClientTest\Framework\ZpkTestCase;

class ZpkInvokableScriptdirTest extends ZpkTestCase
{
    protected $includes;

    public function setUp()
    {
        parent::setUp();

        $this->includes = array(
            'dir1' . DIRECTORY_SEPARATOR .  'test1.php',
            'dir2',
            'dir2' . DIRECTORY_SEPARATOR .  'test1.php',
            'dir2' . DIRECTORY_SEPARATOR .  'test2.php',
        );

        foreach ($this->includes as $include) {
            $fullPath = $this->tempDir.'/'.$include;
            if (preg_match("/\.php$/", $include)) {
                $dir = dirname($include);
                if ($dir && !file_exists($this->tempDir.'/'.$dir)) {
                    mkdir($this->tempDir.'/'.$dir);
                }
                touch($fullPath);
                continue;
            }

            mkdir($fullPath);
        }
    }

    /**
     * Tests to see if the script resolution matches the one in Zend Studio
     */
    public function testOneFilePath()
    {
        $scriptsDir = 'scripts';
        $includes = array($this->includes[0]);
        $expected   = array(
            $this->includes[0] => $scriptsDir.'/'.basename($this->includes[0])
        );

        $actual = $this->zpkService->getScriptPaths($scriptsDir,
                                                  $includes,
                                                  $this->tempDir);

        $this->assertEquals($expected, $actual);
    }

    public function testOneDirPath()
    {
        $scriptsDir = 'scripts';
        $includes = array(
            $this->includes[1]
        );
        $expected   = array(
            $this->includes[2] => $scriptsDir.'/'.basename($this->includes[2]),
            $this->includes[3] => $scriptsDir.'/'.basename($this->includes[3]),
        );

        $actual = $this->zpkService->getScriptPaths($scriptsDir,
            $includes,
            $this->tempDir);

        $this->assertEquals($expected, $actual);
    }

    public function testTwoDirsPath()
    {
        $scriptsDir = 'scripts';
        $includes = array(
            'dir1', 'dir2'
        );
        $expected   = array(
            'dir1' => $scriptsDir.'/dir1',
            'dir2' => $scriptsDir.'/dir2',
        );

        $actual = $this->zpkService->getScriptPaths($scriptsDir,
            $includes,
            $this->tempDir);

        $this->assertEquals($expected, $actual);
    }

    public function testFileDirPath()
    {
        $scriptsDir = 'scripts/zend';
        $includes = array(
            $this->includes[0],
            $this->includes[1]
        );
        $expected   = array(
            $this->includes[0] => $scriptsDir.'/'.basename($this->includes[0]),
            $this->includes[1] => $scriptsDir.'/'.$this->includes[1],
        );

        $actual = $this->zpkService->getScriptPaths($scriptsDir,
            $includes,
            $this->tempDir);

        $this->assertEquals($expected, $actual);
    }
}

<?php
namespace ClientTest\Framework;

use PHPUnit_Framework_TestCase;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use Client\Service\ZpkInvokable;

class ZpkTestCase extends PHPUnit_Framework_TestCase
{
    /**
     * @var ZpkInvokable
     */
    protected $zpkService;
    protected $tempDir;

    public function setUp()
    {
        $this->zpkService = new ZpkInvokable();

        $this->tempDir = tempnam(sys_get_temp_dir(),'phpunit');
        unlink($this->tempDir);
        mkdir($this->tempDir);
    }

    public function tearDow()
    {
        $this->removeDirectory($this->tempDir);
    }

    protected function removeDirectory($path)
    {
        if(!file_exists($path)) {
            return false;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            $cmd = ($fileinfo->isDir() ? 'rmdir' : 'unlink');
            $cmd($fileinfo->getFilename());
        }

        rmdir($path);

        return true;
    }
}

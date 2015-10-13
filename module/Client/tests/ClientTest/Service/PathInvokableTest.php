<?php
namespace ClientTest\Service;

use PHPUnit_Framework_TestCase;
use Client\Service\PathInvokable;

class PathInvokableTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var PathInvokable
     */
    protected $pathService;
    protected $isWindows = false;

    public function setUp()
    {
        $this->pathService = new PathInvokable();
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->isWindows = true;
        }
    }

    /**
     * Tests to see if the absolute path resolver functions correctly on most systems
     */
    public function testGetAbsolute()
    {
        if ($this->isWindows) {
            $this->pathService->setWindows(true);
            $this->assertEquals($this->pathService->getAbsolute('E:\\tmp'), 'E:\\tmp');
            $this->assertEquals($this->pathService->getAbsolute('tmp\\'), getcwd().'\\tmp\\');
        } else {
            $this->assertEquals($this->pathService->getAbsolute('/tmp'), '/tmp');
            $this->assertEquals($this->pathService->getAbsolute('tmp/a/b'), getcwd().'/tmp/a/b');
        }
    }
}

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

    public function setUp()
    {
        $this->pathService = new PathInvokable();
    }

    /**
     * Tests to see if the absolute path resolver functions correctly on most systems
     */
    public function testGetAbsolute()
    {
        $this->assertEquals($this->pathService->getAbsolute('/tmp'), '/tmp');
        $this->assertEquals($this->pathService->getAbsolute('tmp/a/b'), getcwd().'/tmp/a/b');

        $this->pathService->setWindows(true);
        $this->assertEquals($this->pathService->getAbsolute('E:\\tmp'), 'E:\\tmp');
        $this->assertEquals($this->pathService->getAbsolute('tmp\\'), getcwd().DIRECTORY_SEPARATOR.'tmp\\');
    }
}

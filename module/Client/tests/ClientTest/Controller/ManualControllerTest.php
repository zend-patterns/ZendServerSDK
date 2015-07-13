<?php
namespace ClientTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;
use Client\Controller\TargetController;
use Client\Service\TargetInvokable;

class ManualControllerTest extends AbstractConsoleControllerTestCase
{
    protected $tempFile;
    private $targetService;

    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__.'/../../config/application.config.php'
        );
        parent::setUp();
    }

    public function tearDown()
    {
    }

    public function testAutoCompleteAction()
    {
        ob_start();
        $result = $this->dispatch("auto-complete ");
        $content = ob_get_clean();
        $this->assertContains("autocompletion for bash", $content);
        $this->assertControllerName('client-manual-controller');
        $this->assertActionName('autocomplete');
        $this->assertResponseStatusCode(0);
    }
}

<?php
namespace ClientTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;
use Client\Controller\TargetController;
use Client\Service\TargetInvokable;

class TargetControllerTest extends AbstractConsoleControllerTestCase
{
    protected $tempFile;
    private $targetService;

    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__.'/../../config/application.config.php'
        );
        parent::setUp();

        $this->tempFile = tempnam(sys_get_temp_dir(), 'phpunit');

        $targetService = new TargetInvokable();
        $targetService->setConfigFile($this->tempFile);

        $testData = array (
            'delme' => array (
                'zsurl' => 'http://del.me',
                'zssecret' => 'Secret',
                'zskey'    => 'Name',
                'zsversion' => '1.1'
            ),
            'listme' => array (
                'zsurl' => 'http://list.me',
                'zssecret' => 'Secret',
                'zskey'    => 'Name',
                'zsversion' => '1.1'
            ),
            'updateme' => array (
                'zsurl' => 'http://update.me',
                'zssecret' => 'Secret',
                'zskey'    => 'Name',
                'zsversion' => '1.1',
                'http'  => array(
                    'sslverify' => '1',
                    'timeout'   => '20',
                )
            ),
        );
        $targetService->save($testData);

        $this->targetService = $targetService;

        $serviceManager = $this->getApplication()->getServiceManager();
        $targetController = $serviceManager->get('controllerloader')->get('webapi-target-controller');
        $targetController->setTargetService($targetService);
    }

    public function tearDown()
    {
      @unlink($this->tempFile);
    }

    public function testUpdateRemoveAction()
    {
        $targetName = "updateme";

        $data = $this->targetService->load();
        $this->assertArrayHasKey($targetName, $data);

        $oldUrl = $data[$targetName]['zsurl'];
        $oldVersion = $data[$targetName]['zsversion'];
        $oldHttpTimeout = $data[$targetName]['http']['timeout'];
        $oldHttpSSL = $data[$targetName]['http']['sslverify'];

        $result = $this->dispatch("updateTarget --target=$targetName --zsurl=http://new-url.to.use --http=timeout=30 ");
        $this->assertControllerName('webapi-target-controller');
        $this->assertActionName('update');
        $this->assertResponseStatusCode(0);

        $data = $this->targetService->load();
        $this->assertNotEquals($oldUrl, $data[$targetName]['zsurl']);
        $this->assertEquals($oldVersion, $data[$targetName]['zsversion']);
        $this->assertNotEquals($oldHttpTimeout, $data[$targetName]['http']['timeout']);
        $this->assertEquals($oldHttpSSL, $data[$targetName]['http']['sslverify']);
    }

    public function testRemoveAction()
    {
        $targetName = "delme";

        $data = $this->targetService->load();
        $this->assertArrayHasKey($targetName, $data);

        $result = $this->dispatch("removeTarget --target=$targetName");
        $this->assertControllerName('webapi-target-controller');
        $this->assertActionName('remove');
        $this->assertResponseStatusCode(0);

        $data = $this->targetService->load();
        $this->assertArrayNotHasKey($targetName, $data);
    }

    public function testRemoveAllAction()
    {
        $data = $this->targetService->load();
        $this->assertNotCount(0,$data);

        $result = $this->dispatch("removeAllTargets");
        $this->assertControllerName('webapi-target-controller');
        $this->assertActionName('removeAll');
        $this->assertResponseStatusCode(0);

        $data = $this->targetService->load();
        $this->assertCount(0,$data);
    }

    public function testLocationAction()
    {
        $location = $this->targetService->getConfigFile();

        $result = $this->dispatch("targetFileLocation");
        $this->assertControllerName('webapi-target-controller');
        $this->assertActionName('location');
        $this->assertResponseStatusCode(0);

        $content = $this->getResponse()->getContent();
        $content = trim($content);

        $this->assertEquals($content,$location);
    }
}

<?php
namespace ClientTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;

class ConsoleControllerTest extends AbstractConsoleControllerTestCase
{
    protected $traceError = true;

    public function setUp()
    {
        $this->setApplicationConfig(
                include __DIR__.'/../../config/application.config.php'
        );
        parent::setUp();

        // if no target is provided then use "test" as default target
        $eventManager = $this->getApplication()->getEventManager();
        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_ROUTE, function($event) {
            $match = $event->getRouteMatch();
            if (!$match) {
                return;
            }

            if(!$match->getParam('target')) {
                $match->setParam('target','test');
            }
        },-1);

        $eventManager->attach(\Zend\Mvc\MvcEvent::EVENT_DISPATCH, function($event) {
           $result = $event->getResult();
           $response = $event->getResponse();
        }, 2);
    }

    public function testApplicationGetStatusAction()
    {
        $application = $this->getApplication();
        $serviceManager = $application->getServiceManager();
        $eventManager   = $application->getEventManager();

        // This is how the request object can be accessed and modified.
        $request = $this->getRequest();

        // The dispatch method returns the result.
        $result = $this->dispatch('applicationGetStatus');
        $this->assertControllerName('webapi-api-controller');
        $this->assertResponseStatusCode(0);

        $response = $this->getResponse();
    }

    public function testMissingAction()
    {
        $application = $this->getApplication();
        $serviceManager = $application->getServiceManager();
        $eventManager   = $application->getEventManager();

        // This is how the request object can be accessed and modified.
        $request = $this->getRequest();

        // The dispatch method returns the result.
        $result = $this->dispatch('non-existent');
        $this->assertResponseStatusCode(1);
    }

    // @todo: Add tests for package creation
    // @todo: Add tests for package upload
}

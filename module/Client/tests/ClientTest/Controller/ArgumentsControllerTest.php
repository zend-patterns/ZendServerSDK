<?php
namespace ClientTest\Controller;

use Zend\Test\PHPUnit\Controller\AbstractConsoleControllerTestCase;
use Zend\Mvc\MvcEvent;
use Zend\Mvc\Router\Console\Simple as SimpleRouter;

class ArgumentsControllerTest extends AbstractConsoleControllerTestCase
{
    protected $tempFile;
    private $targetService;

    public function setUp()
    {
        $this->setApplicationConfig(
            include __DIR__.'/../../config/application.config.php'
        );
        parent::setUp();

        // Fake the config and the routing
        $serviceManager = $this->getApplication()->getServiceManager();
        $data = array(
            'name' => 'testArgs',
            'options' => array(
                'route' => "testArgs [--singleParam=] [--arrayParam=] ",
                'defaults' => array(
                    'controller' => 'test',
                    'action'     => 'Args',
                ),
                'arrays' => array(
                      'arrayParam'
                ),
            ),
        );
        $config = $serviceManager->get('config');
        $config['console']['router']['routes'][$data['name']] = $data;
        $serviceManager->setAllowOverride(true);
        $serviceManager->setService('config', $config);
        $serviceManager->setAllowOverride(false);

        $testRoute = SimpleRouter::factory($data['options']);
        $router = $serviceManager->get('router');
        $router->addRoute($data['name'], $testRoute, 99999);

        $eventManager = $this->getApplication()->getEventManager();
        $eventManager->attach(MvcEvent::EVENT_ROUTE,
                              array(
                                    $this,
                                    'postRoute'
                              ), - 3);
    }

    public function postRoute(MvcEvent $event)
    {
        // we want to test only parsing of arguments and routing.
        // No real dispatching is needed here
        $event->stopPropagation(true);
        return $event->getResponse()->setContent("stopped");
    }

    public function testSingleParamAction()
    {
        $this->dispatch("testArgs --singleParam=Test");
        $this->assertMatchedRouteName('testArgs');
        $this->assertResponseStatusCode(0);

        $routeMatch = $this->getApplication()->getMvcEvent()->getRouteMatch();
        $singleParam = $routeMatch->getParam('singleParam');
        $this->assertEquals($singleParam, 'Test');
    }

    public function testArrayParamAction()
    {
        $this->dispatch("testArgs --arrayParam=x,y,z");
        $this->assertMatchedRouteName('testArgs');
        $this->assertResponseStatusCode(0);

        $routeMatch = $this->getApplication()->getMvcEvent()->getRouteMatch();
        $arrayParam = $routeMatch->getParam('arrayParam');

        $this->assertEquals($arrayParam, array('x', 'y', 'z'));
    }

    public function testArrayParamCustomDelimiterAction()
    {
        $this->dispatch("testArgs --arrayParam=x#y#z<#>"); // # is the delimiter here
        $this->assertMatchedRouteName('testArgs');
        $this->assertResponseStatusCode(0);

        $routeMatch = $this->getApplication()->getMvcEvent()->getRouteMatch();
        $arrayParam = $routeMatch->getParam('arrayParam');

        $this->assertEquals($arrayParam, array('x', 'y', 'z'));
    }

    public function testArrayAssocParamAction()
    {
        $this->dispatch("testArgs --arrayParam=x[a]=0&x[b]=1&y=2");
        $this->assertMatchedRouteName('testArgs');
        $this->assertResponseStatusCode(0);

        $routeMatch = $this->getApplication()->getMvcEvent()->getRouteMatch();
        $arrayParam = $routeMatch->getParam('arrayParam');

        $this->assertEquals($arrayParam, array('x' => array('a'=>0, 'b'=>1 ), 'y'=> 2));
    }

    public function testArrayAssocParamCustomDelimiterAction()
    {
        $this->dispatch("testArgs --arrayParam=x[a]=0,x[b]=1,y=2<,>"); // , is the delimiter here
        $this->assertMatchedRouteName('testArgs');
        $this->assertResponseStatusCode(0);

        $routeMatch = $this->getApplication()->getMvcEvent()->getRouteMatch();
        $arrayParam = $routeMatch->getParam('arrayParam');

        $this->assertEquals($arrayParam, array('x' => array('a'=>0, 'b'=>1 ), 'y'=> 2));
    }
}

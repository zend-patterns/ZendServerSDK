<?php
namespace Client\Controller;

use Zend\View\Model\ViewModel;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Stdlib\RequestInterface as Request;
use Zend\Stdlib\DispatchableInterface;
use Zend\Mvc\InjectApplicationEventInterface;
use Zend\EventManager\EventInterface as Event;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

use Slk\View\ICMRenderer;
use Zend\View\Renderer\PhpRenderer;

/**
 * Manual Pages Controller
 */
class ManualController implements DispatchableInterface,
                                  ServiceLocatorAwareInterface,
                                  InjectApplicationEventInterface
{
    protected $event;
    protected $serviceLocator;

    /*
     */
    public function autocompleteAction(Request $request, Response $response = null)
    {
        $serviceManager = $this->getServiceLocator();
        $console = 	$serviceManager->get('console');
        $resolver = $serviceManager->get('ViewResolver');
        $render = new PhpRenderer();
        $render->setResolver($resolver);
        $console->write($render->render('client/manual/autocomplete'));
    }


    public function dispatch(Request $request, Response $response = null)
    {
        $serviceManager = $this->getServiceLocator();
        $routeMatch = $this->getEvent()->getRouteMatch();
        $action = $routeMatch->getParam('action');
        $callback = array($this, $action."Action");
        if(is_callable($callback)) {

            return call_user_func_array($callback, array($request, $response));
        }

        $viewModel = new ViewModel();
        $viewModel->setTemplate('client/manual/'.strtolower($action));
        $renderer = new ICMRenderer();
        $renderer->setConsoleAdapter($serviceManager->get('console'));
        $renderer->setResolver($serviceManager->get('ViewResolver'));
        $renderer->setHelperPluginManager($serviceManager->get('ViewHelperManager'));

        $renderer->render($viewModel);
    }
    /* (non-PHPdoc)
     * @see \Zend\Mvc\InjectApplicationEventInterface::setEvent()
     */
    public function setEvent(Event $event)
    {
        $this->event = $event;
    }

    /* (non-PHPdoc)
     * @see \Zend\Mvc\InjectApplicationEventInterface::getEvent()
     */
    public function getEvent()
    {
        return $this->event;
    }

    /* (non-PHPdoc)
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::setServiceLocator()
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
    }

    /* (non-PHPdoc)
     * @see \Zend\ServiceManager\ServiceLocatorAwareInterface::getServiceLocator()
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }

}

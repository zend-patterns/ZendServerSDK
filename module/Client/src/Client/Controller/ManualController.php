<?php
namespace Client\Controller;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Stdlib\ResponseInterface as Response;
use Zend\Stdlib\RequestInterface as Request;
use Zend\View\Renderer\PhpRenderer;

/**
 * Manual Pages Controller
 */
class ManualController extends AbstractActionController
{
    public function dispatch(Request $request, Response $response = null)
    {
        $serviceManager = $this->getServiceLocator();
        $routeMatch = $this->getEvent()->getRouteMatch();
        $viewModel = new ViewModel();
        $viewModel->setTemplate('client/manual/'.strtolower($routeMatch->getParam('action')));
        if($serviceManager->has('viewrenderer')) {
          $renderer = $serviceManager->get('viewrenderer');
        } else {
            $renderer = new PhpRenderer();
            $renderer->setResolver($serviceManager->get('ViewResolver'));
        }

        return $response->setContent($renderer->render($viewModel));
    }
}

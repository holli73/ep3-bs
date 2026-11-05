<?php

namespace Tournament;

use Zend\EventManager\EventInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\BootstrapListenerInterface;
use Zend\ModuleManager\Feature\ConfigProviderInterface;
use Zend\Mvc\MvcEvent;

class Module implements AutoloaderProviderInterface, BootstrapListenerInterface, ConfigProviderInterface
{

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__,
                ),
            ),
        );
    }

    public function onBootstrap(EventInterface $e)
    {
        $events = $e->getApplication()->getEventManager();
        $events->attach(MvcEvent::EVENT_ROUTE, array($this, 'onDispatch'));
    }

    /**
     * Blocks access to the public tournament pages while the feature is disabled
     * through Verwaltung/Einstellungen/Verhalten. The admin backend (Backend\Controller\Tournament*)
     * is intentionally not affected, so admins can still set things up while it's off.
     */
    public function onDispatch(MvcEvent $e)
    {
        $routeMatch = $e->getRouteMatch();
        $controller = $routeMatch->getParam('controller');

        if (strpos((string) $controller, 'Tournament\Controller\\') !== 0) {
            return;
        }

        $serviceManager = $e->getApplication()->getServiceManager();
        $optionManager = $serviceManager->get('Base\Manager\OptionManager');

        if ($optionManager->get('service.tournament', 'true') == 'false') {
            $routeMatch->setParam('controller', 'Frontend\Controller\Index');
            $routeMatch->setParam('action', 'index');
        }
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

}

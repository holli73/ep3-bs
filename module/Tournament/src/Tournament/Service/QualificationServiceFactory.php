<?php

namespace Tournament\Service;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class QualificationServiceFactory implements FactoryInterface
{

    public function createService(ServiceLocatorInterface $sm)
    {
        return new QualificationService($sm->get('Tournament\Service\StandingsService'));
    }

}

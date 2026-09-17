<?php

return array(
    'router' => array(
        'routes' => array(
            'tournament' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/tournament',
                    'defaults' => array(
                        'controller' => 'Tournament\Controller\Tournament',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'view' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/:tid',
                            'defaults' => array(
                                'action' => 'view',
                            ),
                            'constraints' => array(
                                'tid' => '[0-9]+',
                            ),
                        ),
                    ),
                    'register' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/register/:tcid',
                            'defaults' => array(
                                'controller' => 'Tournament\Controller\Registration',
                                'action' => 'register',
                            ),
                            'constraints' => array(
                                'tcid' => '[0-9]+',
                            ),
                        ),
                    ),
                    'withdraw' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/withdraw/:tpid',
                            'defaults' => array(
                                'controller' => 'Tournament\Controller\Registration',
                                'action' => 'withdraw',
                            ),
                            'constraints' => array(
                                'tpid' => '[0-9]+',
                            ),
                        ),
                    ),
                    'standings' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/standings/:tcid',
                            'defaults' => array(
                                'action' => 'standings',
                            ),
                            'constraints' => array(
                                'tcid' => '[0-9]+',
                            ),
                        ),
                    ),
                    'bracket' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/bracket/:tcid',
                            'defaults' => array(
                                'action' => 'bracket',
                            ),
                            'constraints' => array(
                                'tcid' => '[0-9]+',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),

    'controllers' => array(
        'invokables' => array(
            'Tournament\Controller\Tournament' => 'Tournament\Controller\TournamentController',
            'Tournament\Controller\Registration' => 'Tournament\Controller\RegistrationController',
        ),
    ),

    'service_manager' => array(
        'factories' => array(
            'Tournament\Manager\TournamentManager' => 'Tournament\Manager\TournamentManagerFactory',
            'Tournament\Manager\TournamentCategoryManager' => 'Tournament\Manager\TournamentCategoryManagerFactory',
            'Tournament\Manager\TournamentParticipantManager' => 'Tournament\Manager\TournamentParticipantManagerFactory',
            'Tournament\Manager\TournamentGroupManager' => 'Tournament\Manager\TournamentGroupManagerFactory',
            'Tournament\Manager\TournamentGroupParticipantManager' => 'Tournament\Manager\TournamentGroupParticipantManagerFactory',
            'Tournament\Manager\TournamentMatchManager' => 'Tournament\Manager\TournamentMatchManagerFactory',
            'Tournament\Manager\TournamentMatchSetManager' => 'Tournament\Manager\TournamentMatchSetManagerFactory',

            'Tournament\Service\RegistrationService' => 'Tournament\Service\RegistrationServiceFactory',
            'Tournament\Service\DrawService' => 'Tournament\Service\DrawServiceFactory',
            'Tournament\Service\BracketService' => 'Tournament\Service\BracketServiceFactory',
            'Tournament\Service\StandingsService' => 'Tournament\Service\StandingsServiceFactory',
            'Tournament\Service\QualificationService' => 'Tournament\Service\QualificationServiceFactory',

            'Tournament\Table\TournamentTable' => 'Tournament\Table\TournamentTableFactory',
            'Tournament\Table\TournamentMetaTable' => 'Tournament\Table\TournamentMetaTableFactory',
            'Tournament\Table\TournamentCategoryTable' => 'Tournament\Table\TournamentCategoryTableFactory',
            'Tournament\Table\TournamentCategoryMetaTable' => 'Tournament\Table\TournamentCategoryMetaTableFactory',
            'Tournament\Table\TournamentParticipantTable' => 'Tournament\Table\TournamentParticipantTableFactory',
            'Tournament\Table\TournamentParticipantMetaTable' => 'Tournament\Table\TournamentParticipantMetaTableFactory',
            'Tournament\Table\TournamentGroupTable' => 'Tournament\Table\TournamentGroupTableFactory',
            'Tournament\Table\TournamentGroupParticipantTable' => 'Tournament\Table\TournamentGroupParticipantTableFactory',
            'Tournament\Table\TournamentMatchTable' => 'Tournament\Table\TournamentMatchTableFactory',
            'Tournament\Table\TournamentMatchMetaTable' => 'Tournament\Table\TournamentMatchMetaTableFactory',
            'Tournament\Table\TournamentMatchSetTable' => 'Tournament\Table\TournamentMatchSetTableFactory',
        ),
    ),

    'view_manager' => array(
        'template_path_stack' => array(
            __DIR__ . '/../view',
        ),
    ),
);

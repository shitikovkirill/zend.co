<?php
return array(
    'doctrine' => array(
        'driver' => array(
            'my_economic' => array(
                'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'paths' => array(__DIR__ . '/../src/MyEconomic/Entity')
            ),

            'orm_default' => array(
                'drivers' => array(
                    'MyEconomic\Entity' => 'my_economic',
                )
            )
        )
    ),
    'controllers' => array(
        'invokables' => array(
            'MyEconomic\Controller\Index' => 'MyEconomic\Controller\IndexController',
        ),
    ),
    'router' => array(
        'routes' => array(
            'my_economic' => array(
                'type' => 'Literal',
                'priority' => 1000,
                'options' => array(
                    'route' => '/economic',
                    'defaults' => array(
                        '__NAMESPACE__' => 'MyEconomic\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
                'child_routes' => array(
                    'add' => array(
                        'type' => 'Literal',
                        'options' => array(
                            'route' => '/add',
                            'defaults' => array(
                                '__NAMESPACE__' => 'MyEconomic\Controller',
                                'controller' => 'Index',
                                'action' => 'add',
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            //'my_economic' => __DIR__ . '/../view',
        ),
    ),
);

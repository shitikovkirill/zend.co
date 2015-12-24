<?php
return array(
    'url_api' => 'https://api.e-conomic.com/secure/api1/EconomicWebservice.asmx?WSDL',
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
            'myeconomic' => array(
                'type' => 'Literal',
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
                    'default' => array(
                        'type' => 'Segment',
                        'options' => array(
                            'route' => '/[:controller[/:action]]',
                            'constraints' => array(
                                'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ),
                            'defaults' => array(
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'my_economic' => __DIR__ . '/../view',
        ),
    ),
);

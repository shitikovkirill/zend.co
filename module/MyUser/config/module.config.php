<?php
return array(
    'doctrine' => array(
        'driver' => array(
            'zfcuser_entity' => array(
                'class' =>'Doctrine\ORM\Mapping\Driver\AnnotationDriver',
                'paths' => array(__DIR__ . '/../src/MyUser/Entity')
            ),

            'orm_default' => array(
                'drivers' => array(
                    'MyUser\Entity' => 'zfcuser_entity',
                )
            )
        )
    ),

    'zfcuser' => array(
        // telling ZfcUser to use our own class
        'user_entity_class'       => 'MyUser\Entity\User',
        // telling ZfcUserDoctrineORM to skip the entities it defines
        'enable_default_entities' => false,
    ),

    'bjyauthorize' => array(
        // Using the authentication identity provider, which basically reads the roles from the auth service's identity
        'identity_provider' => 'BjyAuthorize\Provider\Identity\AuthenticationIdentityProvider',

        'role_providers'        => array(
            // using an object repository (entity repository) to load all roles into our ACL
            'BjyAuthorize\Provider\Role\ObjectRepositoryProvider' => array(
                'object_manager'    => 'doctrine.entity_manager.orm_default',
                'role_entity_class' => 'MyUser\Entity\Role',
            ),
        ),
    ),
    'controllers' => array(
        'invokables' => array(
            'MyUser\Controller\Index' => 'MyUser\Controller\IndexController',
        ),
    ),
    /*'router' => array(
        'routes' => array(
            'myuser' => array(
                'type' => 'Literal',
                'options' => array(
                    // Change this to something specific to your module
                    'route' => '/test',
                    'defaults' => array(
                        // Change this value to reflect the namespace in which
                        // the controllers for your module are found
                        '__NAMESPACE__' => 'MyUser\Controller',
                        'controller' => 'Index',
                        'action' => 'index',
                    ),
                ),
            ),
            'myuserx' => array(
                'type' => 'Literal',
                'options' => array(
                    // Change this to something specific to your module
                    'route' => '/xxx',
                    'defaults' => array(
                        // Change this value to reflect the namespace in which
                        // the controllers for your module are found
                        '__NAMESPACE__' => 'MyUser\Controller',
                        'controller' => 'Index',
                        'action' => 'xxx',
                    ),
                ),
            ),
            'turnover' => array(
                'type' => 'Literal',
                'options' => array(
                    // Change this to something specific to your module
                    'route' => '/turn',
                    'defaults' => array(
                        // Change this value to reflect the namespace in which
                        // the controllers for your module are found
                        '__NAMESPACE__' => 'MyUser\Controller',
                        'controller' => 'Index',
                        'action' => 'turnover',
                    ),
                ),
            ),
        ),
    ),*/
    'router' => array(
        'routes' => array(
            'myuser' => array(
                'type' => 'Literal',
                'options' => array(
                    'route' => '/myuser',
                    'defaults' => array(
                        '__NAMESPACE__' => 'MyUser\Controller',
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
            'MyUser' => __DIR__ . '/../view',
        ),
    ),
);

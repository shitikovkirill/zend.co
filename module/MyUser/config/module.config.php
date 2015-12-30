<?php
return array(

    'usercrud' => array(
        'userEntity' => 'MyUser\Entity\User',
        'roleEntity' => 'MyUser\Entity\Role'
    ),

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
            'MyUser\Controller\User' => 'MyUser\Controller\UserController',
            'MyUser\Controller\Role' => 'MyUser\Controller\RoleController'
        ),
    ),

    'router' => array(
        'routes' => array(
            'zfcadmin' => array(
                'child_routes' => array(
                    'user-crud' => array(
                        'type' => 'segment',
                        'options' => array(
                            'route' => '/users[/:action][/:id]',
                            'defaults' => array(
                                'controller' => 'MyUser\Controller\User',
                                'action' => 'index',
                            ),
                        ),
                    ),
                ),
            ),
            'user-crud' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/admin/users[/:action][/:id]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'MyUser\Controller',
                        'controller' => 'User',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
            'user-crud-password' => array(
                'type' => 'literal',
                'options' => array(
                    'route' => '/admin/password',
                    'defaults' => array(
                        '__NAMESPACE__' => 'MyUser\Controller',
                        'controller' => 'User',
                        'action' => 'password',
                    ),
                ),
                'may_terminate' => true,
            ),
            'user-crud-role' => array(
                'type' => 'Segment',
                'options' => array(
                    'route' => '/admin/roles[/:action][/:id]',
                    'defaults' => array(
                        '__NAMESPACE__' => 'MyUser\Controller',
                        'controller' => 'Role',
                        'action' => 'index',
                    ),
                ),
                'may_terminate' => true,
            ),
        ),
    ),
    'view_manager' => array(
        'template_path_stack' => array(
            'zfc-user' => __DIR__ . '/../view',
        ),
        'template_map' => array(
            'zfc-user/user/login' => __DIR__ . '/../view/zfc-user/user/login.phtml',
        ),
    ),
);

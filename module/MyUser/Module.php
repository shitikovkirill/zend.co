<?php
namespace MyUser;

class Module
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

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }

    public function onBootstrap($mvcEvent)
    {
        $zfcServiceEvents = $mvcEvent->getApplication()->getServiceManager()->get('zfcuser_user_service')->getEventManager();
        $zfcServiceEvents->attach('register', function($e) use($mvcEvent) {
            $user = $e->getParam('user');
            $em = $mvcEvent->getApplication()->getServiceManager()->get('doctrine.entitymanager.orm_default');
            $config = $mvcEvent->getApplication()->getServiceManager()->get('config');
            $defaultUserRole = $em->getRepository('MyUser\Entity\Role')->findOneBy(array('roleId' => $config['bjyauthorize']['authenticated_role']));
            $user->addRole($defaultUserRole);
        });
    }
}

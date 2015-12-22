<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Application\Entity\User;

class IndexController extends AbstractActionController
{
    public function indexAction() {
   /* 
   $objectManager = $this
        ->getServiceLocator()
        ->get('Doctrine\ORM\EntityManager');

    $user = new User();
    $user->setName('Marco Pivetta');

    $objectManager->persist($user);
    $objectManager->flush();

    //die(var_dump($user->getId())); // yes, I'm lazy*/
}
}

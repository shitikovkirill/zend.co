<?php

namespace MyUser\Controller;

use MyUser\Forms\RoleFilter;
use MyUser\Forms\RoleForm;
use Zend\Mvc\Controller\AbstractActionController;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject as DoctrineHydrator;
use ZfcUser\Options\UserServiceOptionsInterface;

class RoleController extends AbstractActionController {

    /**
     * ORM object manager
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getOM() {
        return $this
                        ->getServiceLocator()
                        ->get('Doctrine\ORM\EntityManager');
    }

    public function indexAction() {
        $config = $this->getServiceLocator()->get('usercrud_options');
        $query = $this
                ->getOM()
                ->getRepository($config['roleEntity'])
                ->createQueryBuilder('q');
        $searchTerm = '';
        if ($this->getRequest()->isPost()) {
            $searchTerm = $this->params()->fromPost('searchTerm');
            $query
                    ->where('q.roleId LIKE :search1')
                    ->setParameter('search1', "%{$searchTerm}%")
            ;
        }
        $paginator = new Paginator(
                new DoctrinePaginator(new ORMPaginator($query))
        );
        $paginator
                ->setCurrentPageNumber($this->params()->fromQuery('page', 1))
                ->setItemCountPerPage(20);
        return array(
            'roles' => $paginator,
            'searchTerm' => $searchTerm
        );
    }

    public function newAction() {
        $translator = $this->getServiceLocator()->get('translator');
        $form = new RoleForm($this->getOM());
        $filter = new RoleFilter();
        $form->setInputFilter($filter);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $role = $form->getData();
                $this->getOM()->persist($role);
                $this->getOM()->flush();
                $this->flashMessenger()->addSuccessMessage($translator->translate('Role saved'));
                $this->redirect()->toRoute('user-crud-role');
            }
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    public function editAction() {
        $translator = $this->getServiceLocator()->get('translator');
        $config = $this->getServiceLocator()->get('usercrud_options');

        $role = $this
                ->getOM()
                ->getRepository($config['roleEntity'])
                ->find($this->params()->fromRoute('id'));

        $form = new RoleForm($this->getOM(), $role);
        $filter = new RoleFilter();
        $form->setInputFilter($filter);
        $form->bind($role);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $role = $form->getData();
                $this->getOM()->persist($role);
                $this->getOM()->flush();
                $this->flashMessenger()->addSuccessMessage($translator->translate('Role updated'));
                $this->redirect()->toRoute('user-crud-role');
            }
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    public function removeAction() {
        $translator = $this->getServiceLocator()->get('translator');
        $config = $this->getServiceLocator()->get('usercrud_options');
        $id = $this->params()->fromRoute('id');
        $entity = $this
                ->getOM()
                ->getRepository($config['roleEntity'])
                ->find($id);
        $this->getOM()->remove($entity);
        $this->getOM()->flush();
        $this->flashMessenger()->addSuccessMessage($translator->translate('Role removed'));
        $this->redirect()->toRoute('user-crud-role');
    }
}

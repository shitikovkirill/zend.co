<?php
namespace MyUser\Controller;

use MyUser\Forms\NameElement;
use MyUser\Forms\UserFilter;
use MyUser\Forms\UserForm;
use MyUser\Model\UserModel;
use MyUser\Forms\NameValidator;
use Zend\Mvc\Controller\AbstractActionController;
use DoctrineORMModule\Paginator\Adapter\DoctrinePaginator;
use Doctrine\ORM\Tools\Pagination\Paginator as ORMPaginator;
use Zend\Paginator\Paginator;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;
use Zend\Crypt\Password\Bcrypt;
use ZfcUser\Options\UserServiceOptionsInterface;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;

class UserController extends AbstractActionController
{
    /**
     * @var UserServiceOptionsInterface
     */
    protected $options;

    /**
     * ORM object manager
     * @return \Doctrine\ORM\EntityManager
     */
    protected function getOM()
    {
        return $this
            ->getServiceLocator()
            ->get('Doctrine\ORM\EntityManager');
    }

    public function indexAction()
    {
        $config = $this->getServiceLocator()->get('usercrud_options');
        $query = $this
            ->getOM()
            ->getRepository($config['userEntity'])
            ->createQueryBuilder('q');
        $searchTerm = '';
        if ($this->getRequest()->isPost()) {
            $searchTerm = $this->params()->fromPost('searchTerm');
            $query
                ->where('q.username LIKE :search1')
                ->orWhere('q.email LIKE :search2')
                ->orWhere('q.displayName LIKE :search3')
                ->setParameter('search1', "%{$searchTerm}%")
                ->setParameter('search2', "%{$searchTerm}%")
                ->setParameter('search3', "%{$searchTerm}%");
        }
        $paginator = new Paginator(
            new DoctrinePaginator(new ORMPaginator($query))
        );
        $paginator
            ->setCurrentPageNumber($this->params()->fromQuery('page', 1))
            ->setItemCountPerPage(20);
        return array(
            'users' => $paginator,
            'searchTerm' => $searchTerm
        );
    }

    public function newAction()
    {
        $form = new UserForm($this->getOM());
        $filter = new UserFilter();
        $filter->add(array(
            'name' => 'password',
            'required' => true
        ))
            ->add(array(
                'name' => 'password_confirm',
                'required' => true
            ));

        $form->setInputFilter($filter);
        if ($this->getRequest()->isPost()) {

            $form->setData($this->getRequest()->getPost());
            $role = $form->get('roles');
            $submit = $form->get('save');
            $form->remove('roles');

            if ($form->isValid()) {
                $user = $form->getData();
                $userModel = new UserModel($this->getOM());
                $userNamesVal = $userModel->checkUsersName($user->getUsername());
                $userEmailVal = $userModel->checkUsersEmail($user->getEmail());

                if ($userNamesVal) {
                    $form->setMessages(array('username' => array('Such login already exists.')));
                } else if ($userEmailVal) {
                    $form->setMessages(array('email' => array('Such email already exists.')));
                } else {
                    $myrole = $userModel->getRoles($role->getValue());
                    $user->addRoles($myrole);
                    $user->setPassword($this->encriptPassword($user->getPassword()));
                    $userModel->addNewUser($user);
                    $this->redirect()->toRoute('user-crud');
                }
            }

            $form->add($role);
            $form->add($submit);
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    public function editAction()
    {
        $userModel = new UserModel($this->getOM());
        $user = $userModel->getUserById($this->params()->fromRoute('id'));
        $form = new UserForm($this->getOM(), $user);
        $filter = new UserFilter();
        $form->setInputFilter($filter);
        $currentPassword = $user->getPassword();
        $form->bind($user);

        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            $role = $form->get('roles');
            $submit = $form->get('save');
            $form->remove('roles');

            if ($form->isValid()) {
                $user = $form->getData();
                /* @var $user User */
                if ($user->getPassword() != '') {
                    $user->setPassword($this->encriptPassword($user->getPassword()));
                } else {
                    $user->setPassword($currentPassword);
                }

                $userNamesVal = $userModel->checkUsersName($user->getUsername(), $user->getId());
                $userEmailVal = $userModel->checkUsersEmail($user->getEmail(), $user->getId());

                if ($userNamesVal) {
                    $form->setMessages(array('username' => array('Such login already exists.')));
                } else if ($userEmailVal) {
                    $form->setMessages(array('email' => array('Such email already exists.')));
                } else {
                    $roles = $userModel->getRoles($role->getValue());
                    $user->addRoles($roles);

                    $userModel->addNewUser($user);
                    $this->redirect()->toRoute('user-crud');
                }
            }

            $form->add($role);
            $form->add($submit);
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    public function removeAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $config = $this->getServiceLocator()->get('usercrud_options');
        $id = $this->params()->fromRoute('id');
        $entity = $this
            ->getOM()
            ->getRepository($config['userEntity'])
            ->find($id);
        $this->getOM()->remove($entity);
        $this->getOM()->flush();
        $this->flashMessenger()->addSuccessMessage($translator->translate('User removed'));
        $this->redirect()->toRoute('user-crud');
    }

    public function encriptPassword($newPass)
    {
        $bcrypt = new Bcrypt;
        $bcrypt->setCost($this->getOptions()->getPasswordCost());
        $pass = $bcrypt->create($newPass);
        return $pass;
    }

    public function passwordAction()
    {
        $translator = $this->getServiceLocator()->get('translator');
        if (!$this->zfcUserAuthentication()->hasIdentity()) {
            $this->flashMessenger()->addWarningMessage($translator->translate('User not logged in'));
            $this->redirect()->toRoute('home');
            return true;
        }
        $form = $this->getPasswordForm();
        if ($this->getRequest()->isPost()) {
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $data = $form->getData();
                $user = $this->zfcUserAuthentication()->getIdentity();
                $bcrypt = new Bcrypt;
                $bcrypt->setCost($this->getOptions()->getPasswordCost());
                if (!$bcrypt->verify($data['oldPassword'], $user->getPassword())) {
                    $this->flashMessenger()->addErrorMessage($translator->translate('Old password don\'t match'));
                    $this->redirect()->toRoute('user-crud-password');
                    return false;
                }
                $user->setPassword($this->encriptPassword($data['password']));
                $this->getOM()->persist($user);
                $this->getOM()->flush();
                $this->flashMessenger()->addSuccessMessage($translator->translate('Password changed'));
                $this->redirect()->toRoute('user-crud-password');
            }
        }
        $form->prepare();
        return array(
            'form' => $form
        );
    }

    /**
     * get service options
     *
     * @return UserServiceOptionsInterface
     */
    public function getOptions()
    {
        if (!$this->options instanceof UserServiceOptionsInterface) {
            $this->setOptions($this->getServiceLocator()->get('zfcuser_module_options'));
        }
        return $this->options;
    }

    /**
     * set service options
     *
     * @param UserServiceOptionsInterface $options
     */
    public function setOptions(UserServiceOptionsInterface $options)
    {
        $this->options = $options;
    }

    protected function getPasswordForm()
    {
        $translator = $this->getServiceLocator()->get('translator');
        $form = new Form('password');
        $form
            ->setAttribute('class', 'form-horizontal')
            ->add(array(
                'name' => 'oldPassword',
                'type' => 'password',
                'options' => array(
                    'label' => $translator->translate('Old password')
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ))
            ->add(array(
                'name' => 'password',
                'type' => 'password',
                'options' => array(
                    'label' => $translator->translate('New password')
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ))
            ->add(array(
                'name' => 'password_confirm',
                'type' => 'password',
                'options' => array(
                    'label' => $translator->translate('Confirm password')
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ))
            ->add(array(
                'name' => 'save',
                'type' => 'submit',
                'attributes' => array(
                    'value' => 'Save',
                    'class' => 'btn btn-sm btn-success'
                )
            ));

        $filter = new InputFilter();
        $filter
            ->add(array(
                'name' => 'password',
                'required' => true
            ))
            ->add(array(
                'name' => 'oldPassword',
                'required' => true
            ))
            ->add(array(
                    'name' => 'password_confirm',
                    'required' => false,
                    'validators' => array(
                        array(
                            'name' => 'Identical',
                            'options' => array(
                                'token' => 'password',
                            )
                        )
                    )
                )
            );
        $form->setInputFilter($filter);

        return $form;
    }
}

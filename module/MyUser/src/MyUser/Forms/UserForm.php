<?php
/**
 * Created by PhpStorm.
 * User: kirill
 * Date: 27.12.15
 * Time: 10:35
 */

namespace MyUser\Forms;


use MyUser\Entity\User;
use Zend\Form\Form;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;

class UserForm extends Form
{

    public function __construct($entityManager, $user=null){
        parent::__construct('user');
        $this ->setAttribute('class', 'form-horizontal');
            $this->setHydrator(new DoctrineObject($entityManager,'MyUser\Entity\User'));

        if(empty($user)){
            $user = new User();
        }
            $this ->setObject($user);
            $this->add(array(
                'name' => 'displayName',
                'options' => array(
                    'label' => 'Name'
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ));
            $this ->add(array(
                'name' => 'username',
                'options' => array(
                    'label' => 'Login'
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ));
            $this->add(array(
                'name' => 'email',
                'type' => 'email',
                'options' => array(
                    'label' =>'Email'
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ));
            $this->add(array(
                'name' => 'password',
                'type' => 'password',
                'options' => array(
                    'label' => 'Password'
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ));
            $this->add(array(
                'name' => 'password_confirm',
                'type' => 'password',
                'options' => array(
                    'label' =>'Password Confirm'
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ));
            $this->add(array(
                'name' => 'state',
                'type' => 'checkbox',
                'options' => array(
                    'label' => 'Enabled'
                )
            ));
            $this->add(array(
                'name' => 'roles',
                'type' => 'DoctrineModule\Form\Element\ObjectSelect',
                'attributes' => array(
                    'multiple' => true,
                ),
                'options' => array(
                    'label' => 'Roles',
                    'object_manager' => $entityManager,
                    'target_class' => 'MyUser\Entity\Role',
                    'property' => 'roleId',
                ),
            ));
            $this->add(array(
                'name' => 'save',
                'type' => 'submit',
                'attributes' => array(
                    'value' => 'Save',
                    'class' => 'btn btn-sm btn-success'
                )
            ));
    }
}
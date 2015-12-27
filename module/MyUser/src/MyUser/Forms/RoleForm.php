<?php
/**
 * Created by PhpStorm.
 * User: kirill
 * Date: 27.12.15
 * Time: 20:46
 */

namespace MyUser\Forms;

use BjyAuthorize\Acl\Role;
use Zend\Form\Form;
use DoctrineModule\Stdlib\Hydrator\DoctrineObject;

class RoleForm extends Form
{
    public function __construct($entityManager, $role=null){
        parent::__construct('user');
        $this ->setAttribute('class', 'form-horizontal');
        $this->setHydrator(new DoctrineObject($entityManager,'MyUser\Entity\Role'));

        if(empty($role)){
            $role = new Role();
        }
        $this->setObject($role)
            ->add(array(
                'name' => 'roleId',
                'options' => array(
                    'label' => 'Role'
                ),
                'attributes' => array(
                    'class' => 'form-control input-sm',
                )
            ))
            ->add(array(
                'name' => 'parent',
                'type' => 'DoctrineModule\Form\Element\ObjectSelect',
                'options' => array(
                    'label' => 'Parent Role',
                    'object_manager' => $entityManager,
                    'target_class' => 'MyUser\Entity\Role',
                    'property' => 'roleId',
                    'empty_option' => 'None'
                ),
            ))
            ->add(array(
                'name' => 'save',
                'type' => 'submit',
                'attributes' => array(
                    'value' => 'Save',
                    'class' => 'btn btn-sm btn-success'
                )
            ));
    }
}
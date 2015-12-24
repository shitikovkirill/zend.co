<?php
/**
 * Created by PhpStorm.
 * User: kirill
 * Date: 19.12.15
 * Time: 21:08
 */

namespace MyUser\Form;

use Zend\Form\Form;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\Form\Element;
use MyUser\Entity\EconomicUser;

class EUserForm extends Form
{
    public function __construct($eUser)
    {
        // we want to ignore the name passed
        parent::__construct('euser');
        $this->setAttribute('class', 'form-horizontal');
        $this->setHydrator(new ClassMethodsHydrator(false));
        $this->setObject($eUser);
        $this->add(array(
            'name' => 'agreementNumber',
            'type' => 'Text',
            'options' => array(
                'label' => 'agreementNumber',
            ),
            'attributes' => array(
                'class' => 'form-control input-sm',
            )
        ));

        $this->add(array(
            'name' => 'username',
            'type' => 'Text',
            'options' => array(
                'label' => '$username',
            ),
            'attributes' => array(
                'class' => 'form-control input-sm',
            )
        ));


        $this->add(array(
            'name' => 'password',
            'type' => 'Text',
            'options' => array(
                'label' => '$password',
            ),
            'attributes' => array(
                'class' => 'form-control input-sm',
            )
        ));

        $this ->add(array(
            'name' => 'save',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Добавить',
                'class' => 'btn btn-sm btn-success'
            ),
        ));
    }
}
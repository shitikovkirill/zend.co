<?php
/**
 * Created by PhpStorm.
 * User: kirill
 * Date: 19.12.15
 * Time: 21:08
 */

namespace MyEconomic\Form;

use Zend\Form\Form;
use Zend\Stdlib\Hydrator\ClassMethods as ClassMethodsHydrator;
use Zend\Form\Element;

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
                'label' => 'Your E-conomic Agreement Number',
            ),
            'attributes' => array(
                'class' => 'form-control input-sm',
            )
        ));

        $this->add(array(
            'name' => 'username',
            'type' => 'Text',
            'options' => array(
                'label' => 'Your E-conomic Username',
            ),
            'attributes' => array(
                'class' => 'form-control input-sm',
            )
        ));


        $this->add(array(
            'name' => 'password',
            'type' => 'Text',
            'options' => array(
                'label' => 'Your E-conomic Password',
            ),
            'attributes' => array(
                'class' => 'form-control input-sm',
            )
        ));
        /*
        $this ->add(array(
            'name' => 'save',
            'type' => 'submit',
            'attributes' => array(
                'value' => 'Update Credentials',
                'class' => 'btn btn-sm btn-success'
            ),
        ));
        */
        if(!empty($eUser->getId())){
            $this->bind($eUser);
        }
    }
}
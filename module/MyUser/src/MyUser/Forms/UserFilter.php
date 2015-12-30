<?php
/**
 * Created by PhpStorm.
 * User: kirill
 * Date: 27.12.15
 * Time: 10:43
 */

namespace MyUser\Forms;


use Zend\InputFilter\InputFilter;

class UserFilter extends InputFilter
{
public function __construct(){
    $this
        ->add(array(
            'name' => 'username',
            'required' => true
        ))
        ->add(array(
            'name' => 'displayName',
            'required' => true
        ))
        ->add(array(
            'name' => 'email',
            'required' => true,
            'validators' => array(
                array(
                    'name' => 'EmailAddress'
                )
            )
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
        )
    ;
}

}
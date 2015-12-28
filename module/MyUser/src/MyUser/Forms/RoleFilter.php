<?php
/**
 * Created by PhpStorm.
 * User: kirill
 * Date: 27.12.15
 * Time: 20:54
 */

namespace MyUser\Forms;

use Zend\InputFilter\InputFilter;

class RoleFilter extends InputFilter
{
    public function __construct()
    {
        $this
        ->add(array(
            'name' => 'roleId',
            'required' => true
        ))
        ->add(array(
            'name' => 'parent',
            'required' => false
        ))
    ;
    }
}
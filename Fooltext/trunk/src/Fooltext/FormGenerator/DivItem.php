<?php
namespace Fooltext\FormGenerator;

class DivItem extends Item
{
    protected static $properties = array
    (
        'type' => 'div',
        'label'=>'',
        'description'=>'',
        '+items' => array(),
        'class' => '',
        'style' => '',
        'id' => ''
    );

    protected static $validItems = array('html', 'fieldset', 'div', 'field', 'submit');
}
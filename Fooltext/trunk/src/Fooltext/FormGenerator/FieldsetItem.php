<?php
namespace Fooltext\FormGenerator;

class FieldsetItem extends Item
{
    protected static $properties = array
    (
        'type' => 'fieldset',
        'label'=>'',
        'description'=>'',
        '+items' => array(),
        'class' => '',
        'style' => '',
        'id' => ''
    );
    protected static $validItems = array('html', 'fieldset', 'div', 'field', 'submit');
}
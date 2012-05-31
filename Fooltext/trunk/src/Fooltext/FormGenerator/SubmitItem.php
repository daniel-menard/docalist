<?php
namespace Fooltext\FormGenerator;

class SubmitItem extends Item
{
    protected static $properties = array
    (
        'type' => 'submit',
        'label'=>'',
        'description'=>'',
        'items' => array(),
        'class' => '',
        'style' => '',
        'id' => ''
    );
    protected static $validItems = array('html');
}
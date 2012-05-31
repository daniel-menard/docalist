<?php
namespace Fooltext\FormGenerator;

use Fab\Schema\Schema;

class FormItem extends Item
{
    protected static $properties = array
    (
        'type' => 'form',
        '+schema'=>null,
        '+items'=>array(),
        'action'=>'',
        'class' => '', 'style' => '', 'id' => ''
    );

    protected static $validItems = array('html', 'fieldset', 'div', 'field', 'submit');

}
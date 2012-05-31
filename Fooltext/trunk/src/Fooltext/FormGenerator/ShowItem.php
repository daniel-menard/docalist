<?php
namespace Fooltext\FormGenerator;

use Fab\Schema\Schema;

class ShowItem extends Item
{
    protected static $properties = array
    (
        'type' => 'form',
        '+schema'=>null,
        '+items'=>array(),
        'class' => '', 'style' => '', 'id' => ''
    );

    protected static $validItems = array('html', 'div', 'field');

}
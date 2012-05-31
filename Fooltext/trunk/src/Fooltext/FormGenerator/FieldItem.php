<?php
namespace Fooltext\FormGenerator;

use Fab\Schema\BaseNode;

use Fab\Schema\Schema;
use Fab\Schema\Field;

class FieldItem extends WidgetItem
{
    protected static $properties = array
    (
        'type' => 'div',
        '+field' => null,
        'label'=>'',
        'description'=>'',
        'class' => '',
        'style' => '',
        'id' => '',
        'items' => null,
        'widget'=>'',
        'datasource'=>'',
        'widgetclass'=>'',
        'widgetstyle'=>'',
        'separator'=>'',
        'before'=>'',
        'after'=>'',
    );

    protected static $validItems = array('html', 'zone');

    public function __construct(array $item, BaseNode $schema = null)
    {
        if (! isset($item['field']))
            throw new \Exception("La propriété field est obligatoire pour un noeud de type " . $item['type']);

        $field = $item['field'];

        if (! $schema->fields->has($field))
            throw new \Exception("Le champ $field n'existe pas dans le schéma.");

        $field = $schema->fields->get($field);

        foreach (array('label', 'description', 'widget', 'datasource') as $name)
        {
            if (! isset($item[$name])) $item[$name] = $field->get($name);
        }

        if ($field->fields && empty($item['items']))
        {
            $item['items'] = array();
            foreach($field->fields as $zone)
            {
                $item['items'][]=array('zone'=>$zone->name);
            }
        }

        parent::__construct($item, $schema);

        if (isset($this->items))
        {
            foreach($this->items as & $item)
            {
                $item->parent = $field;
            }
        }

        // Propriétés supplémentaires : elles ne sont pas autorisés dans le fichier xml
        // mais on en a besoin pour la génération
        foreach (array('name', 'repeatable') as $name)
        {
            $this->$name = $field->get($name);
        }
/*
        if (! $this->widget)
        {
            if ($field->fields)
            {
                $this->widget = $field->repeatable ? 'table' : 'list';
            }
            else
            {
                $this->widget = 'textbox';
            }
        }
*/
    }

    protected function createItem(array $item, BaseNode $schema)
    {
        $field = $schema->fields->get($this->field);

        if (! $field->fields)
        {
            throw new \Exception("Le champ $field->name est un champ simple, il ne peut pas contenir d'items.");
        }

        return parent::createItem($item, $field->fields);
    }
}
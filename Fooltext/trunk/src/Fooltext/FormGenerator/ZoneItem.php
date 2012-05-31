<?php
namespace Fooltext\FormGenerator;

use Fab\Schema\Fields;

class ZoneItem extends WidgetItem
{
    protected static $properties = array
    (
        'type' => 'div',
        '+zone' => null,
        'label'=>'',
        'description'=>'',
        'class' => '',
        'style' => '',
        'id' => '',
        'widget'=>'',
        'datasource'=>'',
        'widgetclass'=>'',
        'widgetstyle'=>'',

    );

    protected static $validItems = array();

    public function __construct(array $item, Fields $fields = null)
    {
        if (! isset($item['zone']))
            throw new \Exception("La propriété zone est obligatoire pour un noeud de type " . $item['type']);

        $zone = $item['zone'];
        if (! $fields->has($zone))
            throw new \Exception("La zone $zone n'existe pas dans le champ $fields->parent->name.");

        $zone = $fields->get($zone);

        foreach (array('label', 'description', 'widget', 'datasource') as $name)
        {
            if (! isset($item[$name])) $item[$name] = $zone->get($name);
        }

        parent::__construct($item, $fields);

        // Propriétés supplémentaires : elles ne sont pas autorisés dans le fichier xml
        // mais on en a besoin pour la génération
        foreach (array('name', 'repeatable') as $name)
        {
            $this->$name = $zone->get($name);
        }
    }
}
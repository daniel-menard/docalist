<?php
namespace Fooltext\FormGenerator;

use Fab\Schema\BaseNode;

abstract class Item
{
    /**
     * seules les propriétés définies dans $properties sont autorisées.
     * la valeur associé à chaque clé constitue la valeur par défaut.
     * si le nom de la propriété commence par un '+', alors la propriété est obligatoire.
     *
     * @var array
     */
    protected static $properties = array('type' => '');

    /**
     * Types des items autorisés dans la propriété items de ce noeud
     *
     * @var array
     */
    protected static $validItems = array();

    public $parent;

    public function __construct(array $item, BaseNode $node = null)
    {
        // Vérifie que l'item ne contient que des propriétés autorisées
        foreach($item as $name => $value)
        {
            if (! array_key_exists($name, static::$properties) && ! array_key_exists("+$name", static::$properties))
            {
                throw new\Exception("La propriété '$name' n'est pas autorisée dans un noeud de type " . $item['type']);
            }
        }

        // Stocke les propriétés
        foreach(static::$properties as $name => $value)
        {
            $required = false;
            if (substr($name, 0, 1) === '+')
            {
                $name = substr($name, 1);
                $required = true;
            }

            if (isset($item[$name]))
            {
                $this->$name = $item[$name];
            }
            elseif ($required)
            {
                throw new \Exception("La propriété $name est obligatoire pour un noeud de type " . $item['type']);
            }
            else
            {
                $this->$name = $value;
            }
        }

        // Crée les items fils
        if (isset($this->items))
        {
            foreach($this->items as & $item)
            {
                $item = $this->createItem($item, $node);
            }
        }
    }

    protected function createItem(array $item, BaseNode $node)
    {
        self::setDefaultType($item);
        if (! in_array($item['type'], static::$validItems))
        {
            throw new \Exception("Un item de type $this->type ne peut pas contenir des items de type $item[type].");
        }

        return self::create($item, $node);
    }

    protected static function setDefaultType(array & $item)
    {
        if (isset($item['type'])) return $item['type'];

        foreach (array('field','zone', 'html') as $type)
        {
            if (isset($item[$type])) return $item['type'] = $type;
        }

        throw new \Exception("La propriété type est requise pour l'item " . var_export($item,true));
    }

    public static function create(array $item, BaseNode $node = null)
    {
/*
        echo "CREATE. Item=<pre>", var_export($item,true), '</pre>';
        echo 'BaseNode=<pre>';
        $data = self::dumpNode($node);
        var_export($data);
        echo '</pre>';
*/
        $type = self::setDefaultType($item);
        $class = __NAMESPACE__ . '\\' . ucFirst($type) . 'Item';
        return new $class($item, $node);
/*
        switch (self::setDefaultType($item))
        {
            case 'form':
                return new FormItem($item, $node);

            case 'field':
                return new FieldItem($item, $node);

            case 'zone':
                return new ZoneItem($item, $node);

            case 'html':
                return new HtmlItem($item, $node);

            case 'fieldset':
                return new FieldsetItem($item, $node);

            case 'div':
                return new DivItem($item, $node);

            case 'submit':
                return new SubmitItem($item, $node);

            default:
                throw new \Exception("Type d'item non géré : '" . $item['type'] . "'");
        }
*/
    }

    public function hasItems()
    {
        if (! isset($this->items)) return false;
        return ! empty($this->items);
    }
}
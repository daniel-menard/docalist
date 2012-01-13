<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Schema
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Schema;
use Fooltext\Schema\Exception\NotFound;

/**
 * Classe abstraite représentant une collection de noeuds.
 *
 * Seul des objets Node peuvent être stockés dans cette collection.
 * Chaque objet est indexé à la fois par son nom et par son ID.
 *
 * La collection se charge d'attribuer un ID aux objets qui son ajoutés et
 * stocke le dernier ID utiisé.
 *
 * @package     Fooltext
 * @subpackage  Schema
 */
abstract class Nodes extends BaseNode
{
    /**
     * Type des noeuds que contient cette collection.
     *
     * Tous les noeuds ajoutés à la collection doivent descendre de la classe indiquée.
     *
     * @var string
     */
    protected static $class = null;

    /**
     * ID qui sera atribué au prochain noeud ajouté dans la collection.
     *
     * Par défaut, les ID sont numériques et commencent à 1. Les classes descendantes
     * peuvent changer ça en surchargeant la valeur par défaut de la propriété.
     * Par exemple, la classe collections gère des ID composés de lettres
     * (a, b, .., z, aa, ab, .., az, etc.) en initialisant nextid à 'a'.
     *
     * @var int|string
     */
    protected $nextid = 1;

    /**
     * Liste des noms des noeuds présents dans la collection, indexés par ID.
     *
     * @var array Name => ID
     */
    protected $id = array();

    public function __construct(array $data = array())
    {
        foreach ($data as $name => $child)
        {
            $this->add($child);
        }
    }

    /**
     * Ajoute un noeud dans la collection.
     *
     * @param Node|array $child le noeud fils à ajouter
     *
     * @return \Fooltext\Schema\Nodes $this
     *
     * @throws Exception Si le noeud n'a pas de nom ou si un noeud portant ce nom figure
     * déjà dans la collection.
     */
    public function add($child)
    {
        // Valide
        if (is_array($child))
        {
            $child = new static::$class($child);
        }
        elseif (! $child instanceof static::$class)
        {
            throw new \InvalidArgumentException("Type incorrect : $name");
        }

        // Vérifie que le noeud a un nom
        $name = $child->name;
        if (empty($name))
        {
            throw new \Exception('Le noeud à ajouter doit avoir un nom');
        }

        // Attribue un id au noeud
        if (is_null($child->_id) || $child->_id === '')
        {
            $child->_id = $this->nextid++;
        }

        if (isset($this->data[$name]))
        {
            throw new \Exception("Il existe déjà un noeud avec le nom $name");
        }

        // Ajoute le noeud
        $child->parent = $this;
        $this->data[$name] = $child;
        $this->id[$child->_id] = $name;

        return $this;
    }

    /**
     * Indique si la collection contient un noeud ayant le nom ou l'id indiqué.
     *
     * @param string $name
     * @return boolean
     */
    public function has($name)
    {
        return isset($this->data[$name]) || isset($this->id[$name]);
    }

    /**
     * Retourne le noeud de la collection ayant le nom ou l'id indiqué.
     *
     * Génère une exception si le noeud indiqué n'existe pas.
     *
     * @param string|int $name
     * @throws NotFound
     */
    public function get($name)
    {
        if (isset($this->data[$name])) return $this->data[$name];
        if (isset($this->id[$name])) return $this->data[$this->id[$name]];
        throw new NotFound("Le noeud $name n'existe pas.");
    }

    /**
     * Retourne un tableau contenant tous les noeuds présents dans la collection.
     *
     * @return array
     */
    public function getAll()
    {
        return $this->data;
    }

    /**
     * Supprime le noeud ayant le nom ou l'id indiqué.
     *
     * Génère une exception si le noeud indiqué n'existe pas.
     *
     * @param string[int $name
     * @throws NotFound
     * @return \Fooltext\Schema\Nodes $this
     */
    public function delete($name)
    {
        if (isset($this->data['fields'][$name]))
        {
            unset($this->id[$this->data['fields'][$name]->_id]);
            unset($this->data['fields'][$name]);
            return $this;
        }

        if (isset($this->id[$name]))
        {
            unset($this->data[$this->id[$name]]);
            unset($this->id[$name]);
            return $this;
        }

        throw new NotFound("Le noeud $name n'existe pas.");
    }



    /**
     * Convertit la collection de noeuds en tableau.
     *
     * @return array
     */
//     public function toArray()
//     {
//         $array = parent::toArray();
//         if (isset($this->children))
//         {
//             $children = array();
//             foreach($this->children as $child)
//             {
//                 $children[] = $child->toArray();
//             }
//             $array['children'] = $children;
//         }

//         return $array;
//     }


    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema}. Ajoute les propriétés
     * et les fils du noeud dans le XMLWriter passé en paramètre.
     *
     * Le tag englobant doit avoir été généré par l'appellant.
     *
     * @param \XMLWriter $xml
     */
    protected function _toXml(\XMLWriter $xml)
    {
        foreach($this->data as $child)
        {
            $xml->startElement('item');
            $child->_toXml($xml);
            $xml->endElement();
        }
    }

    protected function _toJson($indent = false, $currentIndent = '', $colon = ':')
    {
        $h = '';
        foreach($this->data as $name => $child)
        {
//            $h .= $currentIndent . json_encode($name) . $colon;
            $h .= $currentIndent . '{';
            $h .= $child->_toJson($indent, $currentIndent . str_repeat(' ', $indent), $colon);
            $h .= $currentIndent. '},';
        }

        return rtrim($h, ',');
    }

    public function getNextId()
    {
        return $this->nextid;
    }

    public function isEmpty()
    {
        return empty($this->data);
    }
}
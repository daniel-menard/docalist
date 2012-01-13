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

/**
 * Classe de base parent des classe Node et Nodes.
 *
 *
 * @package     Fooltext
 * @subpackage  Schema
 */
abstract class BaseNode implements \IteratorAggregate
{
    /**
     * Les données du noeud.
     *
     * @var array
     */
    protected $data = array();


    /**
     * Noeud parent de ce noeud.
     *
     * Cette propriété est initialisée automatiquement lorsqu'un noeud
     * est ajouté dans une {@link NodesCollection collection}.
     *
     * @var Nodes
     */
    protected $parent = null;


    /**
     * Retourne le noeud parent de ce noeud ou <code>null</code> si le noeud
     * n'a pas encore été ajouté comme fils d'un noeud existant.
     *
     * @return \Fooltext\Schema\Node
     */
    protected function getParent()
    {
        return $this->parent;
    }

    /**
     * Modifie le parent de ce noeud.
     *
     * @param Nodes $parent
     * @return $this
     */
    protected function setParent(Nodes $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Retourne le schéma dont fait partie ce noeud ou <code>null</code> si
     * le noeud n'a pas encore été ajouté à un schéma.
     *
     * @return \Fooltext\Schema
     */
    public function getSchema()
    {
        return is_null($this->parent) ? null : $this->parent->getSchema();
    }


    /**
     * Retourne un tableau contenant toutes les données du noeud.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    public function getProperties()
    {
        $data = $this->data;
        foreach($data as $name=>$value)
        {
            if ($value instanceof Nodes) unset($data[$name]);
        }
        return $data;
    }

    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema::fromXml()} pour
     * charger un chéma Xml.
     *
     * @param \DOMNode $node
     * @param string $path
     * @param string $nodetype
     * @throws \Exception
     */
    protected static function _fromXml(\DOMNode $node, $path='', $nodetype=null)
    {
        /**
         * Tableau utilisé pour convertir les schémas xml de la version 1 à la version 2.
         *
         * Ce tableau contient toutes les "collections" qui existaient dans l'ancien format.
         * Pour chaque collection (on indique le path depuis la racine), on peut indiquer :
         * - true : cette collection existe toujours dans le nouveau format.
         *   Il faut donc la créer et ajouter dedans les noeuds fils.
         * - une chaine contenant un type de noeud symbolique : cela signifie que cette
         *   collection n'existe plus dans le nouveau format. Les noeuds fils doivent être
         *   ajoutés directement dans la clé children du noeud parent et doivent être créé
         *   en utilisant le type indiqué.
         */
        static $oldnodes = array
        (
        	'/schema/fields' => true,
        	'/schema/indices' => true,
        	'/schema/indices/index/fields' => 'indexfield',
        	'/schema/lookuptables' => true,
        	'/schema/lookuptables/lookuptable/fields' => 'lookuptablefield',
        	'/schema/aliases' => true,
        	'/schema/aliases/alias/indices' => 'aliasindex',
        	'/schema/sortkeys' => true,
        	'/schema/sortkeys/sortkey/fields' => 'sortkeyfield',
        );

        // Stocke le type de noeud
        $result = self::create(is_null($nodetype) ? $node->tagName : $nodetype);
        $path .= "/$node->tagName";

        // Les attributs du tag sont des propriétés de l'objet
        if ($node->hasAttributes())
            foreach ($node->attributes as $attribute)
                $result->set($attribute->nodeName, self::_xmlToValue($attribute->nodeValue));

        // Les noeuds fils du tag sont soit des propriétés, soit des objets enfants
        foreach ($node->childNodes as $child)
        {
            $childpath = "$path/$child->tagName";

            switch ($child->nodeType)
            {
                case XML_ELEMENT_NODE:
                    // Le nom de l'élément va devenir le nom de la propriété
                    $name = $child->tagName;

                    // Collection (children ou, pour les anciens formats, fields, indices, etc.)
                    if ($name === 'children')
                    {
                        foreach($child->childNodes as $child)
                        $result->addChild(self::_fromXml($child, $path));
                    }

                    elseif (isset($oldnodes[$childpath]))
                    {
                        if ($oldnodes[$childpath] === true)
                        {
                            $collection = Node::create($name);
                            foreach($child->childNodes as $child)
                            $collection->addChild(self::_fromXml($child, $childpath));

                            $result->addChild($collection);
                        }
                        else
                        {
                            foreach($child->childNodes as $child)
                            $result->addChild(self::_fromXml($child, $childpath, $oldnodes[$childpath]));
                        }
                    }

                    // Propriété
                    else
                    {
                        // Vérifie qu'on n'a pas à la fois un attribut et un élément de même nom (<database label="xxx"><label>yyy...)
                        if ($node->attributes->getNamedItem($name))
                        throw new \Exception("Erreur dans le source xml : la propriété '$name' apparaît à la fois comme attribut et comme élément");

                        // Stocke la propriété
                        $result->set($name, self::_xmlToValue($child->nodeValue)); // si plusieurs fois le même tag, c'est le dernier qui gagne
                    }
                    break;

                    // Types de noeud autorisés mais ignorés
                case XML_COMMENT_NODE:
                    break;

                    // Types de noeud interdits
                default:
                    throw new \Exception("les noeuds de type '".$child->nodeName . "' ne sont pas autorisés");
            }
        }

        return $result;
    }


    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema}. Ajoute les propriétés du
     * noeud dans le XMLWriter passé en paramètre.
     *
     * La méthode ne génère que les propriétés du noeud. Le tag englobant doit
     * avoir été généré par l'appellant.
     *
     * @param \XMLWriter $xml
     */
    protected abstract function _toXml(\XMLWriter $xml);


    /**
    * Fonction utilitaire utilisée par {@link xmlToObject()} pour convertir la
    * valeur d'un attribut ou le contenu d'un tag.
    *
    * Pour les booléens, la fonction reconnait les valeurs 'true' ou 'false'.
    * Pour les autres types scalaires, la fonction encode les caractères '<',
    * '>', '&' et '"' par l'entité xml correspondante.
    *
    * @param scalar $value
    * @return string
    */
    protected static function _xmlToValue($value)
    {
        if ($value === 'true') return true;
        if ($value === 'false') return false;
        if (is_int($value) || ctype_digit($value)) return (int) $value;
        if (is_numeric($value)) return (float)$value;
        return $value;
    }



    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema}. Sérialise le noeud
     * au format JSON.
     *
     * La méthode ne générère que les propriétés du noeud. La méthode appelante doit
     * générer les accolades ouvrantes et fermantes.
     *
     * @param \XMLWriter $xml
     */
    protected abstract function _toJson($indent = false, $currentIndent = '', $colon = ':');

    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }
}
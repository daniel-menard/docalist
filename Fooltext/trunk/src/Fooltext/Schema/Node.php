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
 * Classe de base (abstraite) représentant un noeud dans un schéma.
 *
 * Un noeud est un objet qui peut contenir des propriétés (cf. {@link __get()},
 * {@link __set()}, {@link __isset()}, {@link __unset()} et {@link getData()}).
 *
 * Un noeud dispose de propriétés par défaut (cf. {@link getDefaults()})
 * qui sont créées automatiquement et sont toujours disponibles.
 *
 * Pour chaque propriété, il est possible de définir un getter et/ou un setter
 * pour contrôler le type des des données associées (cf. {@link __get()} et
 * {@link __set()}).
 *
 * @package     Fooltext
 * @subpackage  Schema
 */
abstract class Node extends BaseNode
{
    /**
     * Propriétés prédéfinies et valeurs par défaut de ces propriétés.
     *
     * Cette propriété est destinée à être surchargée par les classes descendantes.
     *
     * @var array
     */
    protected static $defaults = array();

    /**
     * Liste des collections de noeuds dont dispose ce noeud.
     *
     * Cette propriété est destinée à être surchargée par les classes descendantes.
     * Par exemple, la classe Collection contient les collections fields et aliases.
     *
     * @var array un tableau de la forme "nom de la propriété" => "classe utilisée".
     */
    protected static $nodes = array();

    /**
     * Liste des propriétés à ignorer lorsque ce type de noeud est sérialisé en
     * xml ou en json.
     *
     * Cette propriété est destinée à être surchargée par les classes descendantes.
     * Par exemple, la classe Schema ignore la propriété _stopwords qui est calculée
     * dynamiquement à partir de la propriété stopwords.
     *
     * @var array un tableau de la forme "nom de la propriété" => true|false.
     */
    protected static $ignore = array();


    /**
     * Crée un nouveau noeud.
     *
     * Un noeud contient automatiquement toutes les propriétés par défaut définies
     * pour ce type de noeud et celles-ci apparaissent en premier.
     *
     * @param array $data les propriétés du noeud.
     */
    public function __construct(array $data = array())
    {
        // On commence par stocker les propriétés par défaut de ce type de noeud
        // pour qu'elles apparaissent en premier et dans l'ordre indiqué dans la classe.
        $this->data = static::$defaults;

        // On ajoute ensuite toutes les collections définies pour ce type de noeud
        foreach (static::$nodes as $name => $class)
        {
            if (isset($data[$name]))
            {
                $nodes = $data[$name];
                unset($data[$name]);
                if (is_array($nodes))
                {
                    $nodes = new $class($nodes);
                }
                elseif (! $nodes instanceof $class)
                {
                    throw new \InvalidArgumentException("type incorrect : $name");
                }
                $this->data[$name] = $nodes;
            }
            else
            {
                $this->data[$name] = new $class();
            }
        }

        // Et enfin les données fournies en paramètre
        foreach($data as $name => $value)
        {
            $this->__set($name, $value);
        }
    }

    /**
     * Retourne la propriété ayant le nom est indiqué ou null si la propriété
     * demandée n'existe pas.
     *
     * Si la classe contient un getter pour cette propriété (i.e. une méthode nommée
     * get + nom de la propriété), celui-ci est appellé.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        $getter = 'get' . $name;

        // Remarque : il n'y a pas besoin d'appeller ucfirst() pour construire le nom
        // exact de la méthode à appeller car "php methods are case insensitive"
        // (documenté explictement dans la page php.net/functions.user-defined)

        if (method_exists($this, $getter))
        {
            return $this->$getter($name);
        }

        if (array_key_exists($name, $this->data))
        {
            return $this->data[$name];
        }

        return null;
    }

    /**
     * Ajoute ou modifie une propriété.
     *
     * Si la valeur indiquée est null, la propriété est supprimée de l'objet
     * ou revient à sa valeur par défaut si c'est une propriété prédéfinie.
     *
     * Si la classe contient un setter pour cette propriété (i.e. une méthode nommée
     * set + nom de la propriété), celui-ci est appellé pour modifier la propriété.
     *
     * @param string $name le nom de la propriété à modifier.
     * @param mixed $value la nouvelle valeur de la propriété.
     */
    public function __set($name, $value = null)
    {
        $setter = 'set' . $name;

        if (method_exists($this, $setter))
        {
            return $this->$setter($value);
        }

        if (is_null($value))
        {
            $this->__unset($name);
        }
        else
        {
            $this->data[$name] = $value;
        }
    }

    /**
     * Indique si une propriété existe.
     *
     * @param string $name
     *
     * @return bool
     */
    public function __isset($name)
    {
        return array_key_exists($name, $this->data);
    }

    /**
     * Supprime la propriété indiquée ou la réinitialise à sa valeur par défaut
     * s'il s'agit d'une propriété prédéfinie.
     *
     * Sans effet si la propriété n'existe pas.
     *
     * @param string $name
     */
    public function __unset($name)
    {
        if (isset(static::$defaults[$name]))
        {
            $this->data[$name] = static::$defaults[$name];
        }
        else
        {
            unset($this->data[$name]);
        }
    }

    /**
     * Retourne les propriétés par défaut définies pour ce type de noeud.
     *
     * @return array()
     */
    public static function getDefaults()
    {
        return static::$defaults;
    }

    protected function _toXml(\XMLWriter $xml)
    {
        foreach($this->data as $name=>$value)
        {
            if (isset(static::$ignore[$name]) && static::$ignore[$name]) continue;

//            if (array_key_exists($name, static::$defaults) && static::$defaults[$name] === $value) continue;

            if (is_bool($value))
            {
                $value = $value ? 'true' : 'false';
            }

            if (is_null($value))
            {
                $xml->writeElement($name); // empty node
            }
            elseif (is_scalar($value))
            {
                $this->writeXmlString($xml, $name, $value);
            }
            elseif (is_array($value))
            {
                $xml->startElement($name);
                foreach($value as $item)
                {
                    $this->writeXmlString($xml, 'item', $item);
                }
                $xml->endElement();
            }
            elseif ($value instanceof Nodes)
            {
                $xml->startElement($name);
//                 $xml->writeAttribute('nextid', $value->getNextId());

                $value->_toXml($xml);
                $xml->endElement();
            }
            else
            {
                var_export($value);
                throw new \Exception('non géré');
            }
        }
    }

    /**
     * Méthode utilisée par {@link _toXml()} pour écrire la valeur d'une propriété.
     *
     * La méthode examine le contenu de la propriété et génère une section cdata
     * si la valeur contient plus d'une entité xml. Dans le cas contraire, elle
     * écrit directement la valeur.
     *
     * @param \XMLWriter $xml le writer à utiliser.
     * @param string $name le nom de la propriété à générer.
     * @param string $value sa valeur.
     */
    protected function writeXmlString(\XMLWriter $xml, $name, $value)
    {
        if (preg_match_all('~[&<>"]~', $value, $matches) > 1)
        {
            $xml->startElement($name);
            $xml->writeCdata($value);
            $xml->endElement();
        }
        else
        {
            $xml->writeElement($name, $value);
        }
    }

    protected function _toJson($indent = false, $currentIndent = '', $colon = ':')
    {
        $h ='';
        foreach($this->data as $name=>$value)
        {
            if (isset(static::$ignore[$name]) && static::$ignore[$name]) continue;

//             if (isset(static::$defaults[$name]) && static::$defaults[$name] === $value) continue;

            $h .= $currentIndent . json_encode($name) . $colon;
            if ($value instanceof Nodes)
            {
                $h .= $currentIndent . '[';
                $h .= $value->_toJson($indent, $currentIndent . str_repeat(' ', $indent), $colon);
                $h .= $currentIndent. '],';
            }
            else
            {
                $h .= json_encode($value) . ',';
            }
        }

        return rtrim($h, ',');
    }
}
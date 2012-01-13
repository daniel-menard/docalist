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

use DOMDocument;
use SimpleXmlElement;
use DOMElement;
use XMLWriter;

/**
 * Représente un schéma.
 *
 *
 */
class Schema extends Node
{
    /**
     * Propriétés par défaut du schéma.
     *
     * @var array
     */
    protected static $defaults = array
    (
        // Version du format. Initialisé dans le constructeur pour qu'on la voit dans le xml
        'version' => "2",

        // Un libellé court décrivant la base
    	'label' => '',

        // Description, notes, historique des modifs...
        'description' => '',

        // Liste par défaut des mots-vides à ignorer lors de l'indexation
        'stopwords' => '',

        // Version indexée de stopwords
        '_stopwords' => '',

        // Faut-il indexer les mots vides ?
        'indexstopwords' => true,

        // Date de création du schéma
        'creation' => "",

        // Date de dernière modification du schéma
        'lastupdate' => "",
    );

    /**
     * Liste des collections de noeuds dont dispose un schéma.
     *
     * @var array un tableau de la forme "nom de la propriété" => "classe utilisée".
     */
    protected static $nodes = array
    (
    	'collections' => 'Fooltext\\Schema\\Collections',
    );

    /**
     * Liste des propriétés à ignorer lorsqu'un schéma est sérialisé en xml ou en json.
     *
     * @var array un tableau de la forme "nom de la propriété" => true|false.
     */
    protected static $ignore = array('_stopwords' => true);

    /**
     * Crée un schéma depuis un source xml.
     *
     * @param string|DOMDocument|SimpleXmlElement $xml la méthode peut prendre en entrée :
     * - une chaine de caractères contenant le code source xml du schéma
     * - un objet DOMDocument
     * - un objet SimpleXmlElement
     *
     * @return Schema
     *
     * @throws \Exception si le code source contient des erreurs.
     */
    public static function fromXml($xml)
    {
        // Source XML
        if (is_string($xml))
        {
            // Crée un document XML
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;

            libxml_use_internal_errors(true);

            // Charge le document
            if (! $dom->loadXML($xml, defined('LIBXML_COMPACT') ? LIBXML_COMPACT : 0))
            {
                $message = "Schéma incorrect :\n";
                foreach (libxml_get_errors() as $error)
                {
                    $message .= "- ligne $error->line : $error->message<br />\n";
                }
                libxml_clear_errors();
                throw new \Exception($message);
            }
        }

        // Un objet DOMDocument existant
        elseif ($xml instanceof DOMDocument)
        {
            $dom = $xml;
        }

        // Un objet SimpleXmlElement existant
        elseif ($xml instanceof SimpleXmlElement)
        {
            $dom = dom_import_simplexml($xml)->ownerDocument;
        }
        else
        {
            throw new \Exception('Paramètre incorrect');
        }

        // Crée le schéma
        return new self(self::domToArray($dom->documentElement));
    }

    /**
     * Méthode récursive utilisée par {@link fromXml()} pour charger un schéma
     * au format XML.
     *
     * @param DOMElement $node
     * @throws \Exception
     */
    protected static function domToArray(DOMElement $node)
    {
        // Les attributs ne sont pas autorisés dans les noeuds
        if ($node->hasAttributes())
        {
            throw new \Exception(sprintf(
                'Erreur ligne %d, tag %s : attribut interdit',
                $node->getLineNo(), $node->tagName
            ));
        }

        // Détermine la valeur du noeud en parcourant les noeuds fils
        $value = null;
        $hasTags = $hasItems = false;
        foreach($node->childNodes as $child)
        {
            switch ($child->nodeType)
            {
                // Texte ou section cdata : value sera une chaine
                case XML_TEXT_NODE:
                case XML_CDATA_SECTION_NODE:
                    // Vérifie que la config ne mélange pas à la fois des noeuds et du texte
                    if (is_array($value))
                    {
                        throw new \Exception(sprintf(
                            'Erreur ligne %d : le noeud %s contient à la fois des tags et du texte',
                            $child->getLineNo(), $node->tagName
                        ));
                    }

                    // Stocke la valeur
                    $value = is_null($value) ? $child->data : ($value . $child->data);
                    break;

                // Un tag : value sera un tableau
                case XML_ELEMENT_NODE:
                    // Vérifie que la config ne mélange pas à la fois des noeuds et du texte
                    if (is_string($value))
                    {
                        throw new \Exception(sprintf(
                            'Erreur ligne %d : le noeud %s contient à la fois des tags et du texte',
                            $child->getLineNo(), $node->tagName
                        ));
                    }

                    if (is_null($value)) $value = array();

                    // Récupère le nom du noeud
                    $name = $child->tagName;

                    // Récupère le contenu du noeud
                    $item = self::domToArray($child);

                    // Cas particulier : la valeur de la clé est un tableau d'items
                    if ($child->tagName === 'item')
                    {
                        $value[] = $item;
                        $hasItems = true;
                    }
                    else
                    {
                        if (isset($value[$name]))
                        {
                            throw new \Exception(sprintf(
                                'Erreur ligne %d, clé répétée : %s',
                                $child->getLineNo(), $node->tagName
                            ));
                        }
                        $value[$name] = $item;
                        $hasTags = true;
                    }

                    // Vérifie qu'on ne mélange pas des tags et des items
                    if ($hasTags && $hasItems)
                    {
                        throw new \Exception(sprintf(
                            'Erreur ligne %d : le noeud %s contient à la fois des tags et des items',
                            $child->getLineNo(), $node->tagName
                        ));
                    }

                    break;

                // Les commentaires sont autorisés mais sont ignorés
                case XML_COMMENT_NODE:
                    break;

                // Les autres types de noeuds interdits (PI, etc.)
                default:
                    throw new \Exception(sprintf(
                        'Erreur ligne %d : type de noeud interdit.',
                        $child->getLineNo()
                    ));
            }
        }

        // Convertit les chaines en entiers, booléens
        if (is_string($value))
        {
            $h = trim($value);
            if (is_numeric($h))
            {
                $value = ctype_digit($h) ? (int)$h : (float)$h;
            }
            elseif ($h === 'true')
            {
                $value = true;
            }
            elseif($h ==='false')
            {
                $value = false;
            }
        }

        // Retourne le résultat
        return $value;
    }

    /**
     * Sérialise le schéma au format xml.
     *
     * @param true|false|int $indent
     * - false : aucune indentation, le xml généré est compact.
     * - true : le xml est généré de façon lisible, avec une indentation de 4 espaces.
     * - int : xml lisible, avec une indentation de int espaces.
     *
     * @return string
     */
    public function toXml($indent = false)
    {
        $xml = new XMLWriter();
        $xml->openMemory();

        if ($indent === true) $indent = 4; else $indent=(int) $indent;
        if ($indent > 0)
        {
            $xml->setIndent(true);
            $xml->setIndentString(str_repeat(' ', $indent));
        }
        $xml->startDocument('1.0', 'utf-8', 'yes');

        $xml->startElement('schema');
        $this->_toXml($xml);
        $xml->endElement();

        $xml->endDocument();

        return $xml->outputMemory(true);
    }

    /**
     * Crée un schéma à partir d'une chaine au format JSON.
     *
     * @param string $json
     * @return Schema
     */
    public static function fromJson($json)
    {
        $array = json_decode($json, true);

        if (is_null($array))
            throw new \Exception('JSON invalide');

        return new self($array);
    }

    /**
     * Sérialise le schéma au format Json.
     *
     * @param true|false|int $indent
     * - false : aucune indentation, le json généré est compact
     * - true : le json est généré de façon lisible, avec une indentation de 4 espaces.
     * - x (int) : json lisible, avec une indentation de x espaces.
     *
     * @return string
     */
    public function toJson($indent = false)
    {
        if (! $indent) return '{' . $this->_toJson() . '}';

        if ($indent === true) $indent = 4; else $indent=(int) $indent;
        $indentString = "\n" . str_repeat(' ', $indent);

        $h = "{";
        $h .= $this->_toJson($indent, $indentString, ': ');
        if ($indent) $h .= "\n";
        $h .= '}';

        return $h;
    }

    /**
     * Retourne le schéma en cours.
     *
     * Pour un Schéma, getSchema() n'a pas trop d'utilité, mais ça permet
     * d'interrompre la chaine getSchema() des noeuds qui font tous
     * return parent::getSchema().
     *
     * @return $this
     */
    public function getSchema()
    {
        return $this;
    }

    /**
     * Setter pour la propriété 'stopwords'.
     *
     * A chaque fois qu'on modifie la propriété 'stopwords', cela modifie également
     * la propriété cachée 'stopwords' qui est une version tableau (les mots-vides sont
     * tokenisés et sont indexés dans les clés du tableau) de la chaine 'stopwords'.
     *
     * @param string $stopwords
     */
    protected function setStopwords($stopwords)
    {
        $this->data['stopwords'] = $stopwords;

        if (is_string($stopwords))
        {
            $stopwords = str_word_count($stopwords, 1, '0123456789@_');
            // @todo : utiliser l'analyseur lowerCase.
        }
        elseif (is_array($stopwords))
        {
            $stopwords = array_values($stopwords);
        }
        $stopwords = array_fill_keys($stopwords, true);

        $this->data['_stopwords'] = $stopwords;
    }

    public function validate()
    {
        return true;
    }

    public function compile()
    {
    }

    public function setLastUpdate()
    {
        return true;
    }
}
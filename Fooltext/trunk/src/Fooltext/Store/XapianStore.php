<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Store
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Store;

use Fooltext\Indexing\AnalyzerData;

use Fooltext\Document\Document;
use Fooltext\Schema\Schema;
use Fooltext\Schema\FieldNames;
use Fooltext\Schema\Field;
use Fooltext\Schema\Group;
use Fooltext\Schema\Index;
use Fooltext\Schema\Exception\NotFound;

use Fooltext\QueryParser\Parser;

/**
 * Une base de données Xapian.
 */
class XapianStore implements StoreInterface
{
    /**
     * Longueur maximale d'un terme dans une base xapian.
     *
     * @var int
     */
    const MAX_TERM = 236;

    /**
     * La base de données xapian en cours.
     *
     * @var \XapianDatabase
     */
    protected $db;

    /**
     * Le schéma de la base en cours.
     *
     * @var Fooltext\Schema\Schema
     */
    protected $schema;

    /**
     * QueryParser
     *
     * @var Parser
     */
    protected $queryParser;

    /**
     * Cache des analyseurs déjà créés pour l'indexation.
     *
     * @var array className => Analyzer
     */
    static protected $analyzerCache = array();

    /**
     * Ouvre ou crée une base de données.
     *
     * @param array $options les options suivantes sont reconnues :
     * - path : string, le path complet de la base de données. Obligatoire.
     * - readonly : boolean, indique si la base doit être ouverte en mode
     *   "lecture seule" ou en mode "lecture/écriture".
     *   Optionnel, valeur par défaut : true.
     * - create : boolean, indique que la base de données doit être créée si
     *   elle n'existe pas déjà. Dans ce cas, la base est obligatoirement
     *   ouverte en mode "lecture/écriture". Optionnel. Valeur par défaut : false.
     * - overwrite : indique que la base de données doit être écrasée si elle
     *   existe déjà. Optionnel, valeur par défaut : false.
     * - schéma : le schema à utiliser pour créer la base. Obligatoire si
     *   create ou overwrite sont à true. Optionnel sinon.
     *
     * Exemples :
     * - Ouverture en lecture seule d'une base existante :
     * array('path'=>'...')
     *
     * - Ouverture en lecture/écriture d'une base existante :
     * array('path'=>'...', 'readonly'=>false)
     *
     * - Créer une nouvelle base de données (exception si elle existe déjà)
     * array('path'=>'...', 'create'=>true, 'schema'=>$schema)
     *
     * - Créer une base et écraser la base existante :
     * array('path'=>'...', 'overwrite'=>true, 'schema'=>$schema)
     */
    public function __construct(array $options = array())
    {
        // Options d'ouverture par défaut
        $defaultOptions = array
        (
            'path' => null,
            'readonly' => true,
            'create' => false,
            'overwrite' => false,
            'schema' => null,
        );

        // Détermine les options de la base
        $options = (object) ($options + $defaultOptions);

        // Le path de la base doit toujours être indiqué
        if (empty($options->path)) throw new \BadMethodCallException('option path manquante');

        // Création d'une nouvelle base
        if ($options->create || $options->overwrite)
        {
            if (empty($options->schema)) throw new \BadMethodCallException('option schema manquante');
            if (! $options->schema instanceof Schema) throw new \BadMethodCallException('schéma invalide');

            $mode = $options->overwrite ? \Xapian::DB_CREATE_OR_OVERWRITE : \Xapian::DB_CREATE;
            $this->db = new \XapianWritableDatabase($options->path, $mode);
            $this->setSchema($options->schema);
        }

        // Ouverture d'une base existante en readonly
        elseif ($options->readonly)
        {
            $this->db = new \XapianDatabase($options->path);
            $this->loadSchema();
        }

        // Ouverture d'une base existante en read/write
        else
        {
            $this->db = new \XapianWritableDatabase($options->path, \Xapian::DB_OPEN);
            $this->loadSchema();
        }
    }

    public function isReadonly()
    {
        return ! ($this->db instanceof \XapianWritableDatabase);
    }

    public function setSchema(Schema $schema)
    {
        $errors =array();
        if (! $schema->validate())
        {
            throw new \InvalidArgumentException("Schéma incorrect : " . implode("\n", $errors));
        }

        $this->schema = $schema;
        $this->db->set_metadata('schema_object', serialize($schema));
        return $this;
    }

    protected function loadSchema()
    {
        $this->schema = unserialize($this->db->get_metadata('schema_object'));
        return $this;
    }

    public function getSchema()
    {
        return $this->schema;
    }

    protected function handleException(\Exception $e)
    {
        $message = $e->getMessage();
        if (false !== $pt = strpos($message, ':'))
        {
            $code = rtrim(substr($message, 0, $pt));
            $message = ltrim(substr($message, $pt+1));
            switch ($code)
            {
                case 'DocNotFoundError':
                    throw new Exception\DocumentNotFound($message, $e->getCode());
            }
        }

        throw $e;
    }

    // Convertit l'id au sein de la collection en id global pour la base
    protected function userIdToXapianId($id)
    {
        $term = $this->id . $id;

        $postlist = $this->db->postlist_begin($term);
        if ($postlist->equals($this->db->postlist_end($term)))
        {
            throw new Exception\DocumentNotFound("Le document $id n'existe pas dans cette collection");
        }

        return $postlist->get_docid();
    }

    /*

    ID global attribué par xapian (int)
    ID attribué par fooltext (lettre + int)
    ID popre à une collection (int)

    */
    public function get($id)
    {
        // version actuelle : $id doit être l'ID global attribué par xapian
        //$id = $this->userIdToXapianId($id);

        // Charge le document Xapian
        try
        {
            $data = json_decode($this->db->get_document($id)->get_data(), true);
        }
        catch (\Exception $e)
        {
            $this->handleException($e);
        }

        // Crée un objet Document du type indiqué dans la collection
        $class = $this->schema->document;
        return new $class($this->schema, $data);
    }

    /**
     * Retourne une instance de l'analyseur dont le nom de classe
     * est passé en paramètre.
     *
     * @param string $class
     * @return Fooltext\Indexing\AnalyzerInterface
     */
    protected function getAnalyzer($class)
    {
        if (! isset(self::$analyzerCache[$class]))
        {
            self::$analyzerCache[$class] = new $class();
        }
        return self::$analyzerCache[$class];
    }

    protected static $data = null;

    public function put($document)
    {
        $dump = false;

        if ($dump) echo "<pre>";

        // Si on nous a passé un tableau, crée un objet Document pour valider les données
        if (! $document instanceof Document)
        {
            $class = $this->schema->get('document');
            $document = new $class($this->schema, $document);
        }

        // Convertit le document en tableau tel qu'il sera sérialisé dans la base
        $document = $document->getData(false); // todo à changer + dans schéma : _docid contient l'id du doc, index._fields contient les id des champs, index._field aussi
        if ($dump) echo "Document : <pre>", var_export($document, true), "</pre>";

        // Crée le document xapian qu'on va stocker dans la base
        $doc = new \XapianDocument();

//         $termGenerator = new \XapianTermGenerator();
//         $termGenerator->set_document($doc);
//        $termGenerator->set_database($this->db);

        // Enregistre les données
        $doc->set_data(json_encode($document));

        $docId = (int) $document['REF']; // @todo allouer un id au doc + utiliser la propriété docid du schéma
        $doc->add_boolean_term($docId);

        foreach($this->schema->get('indices') as $index)
        {
            // Récupère la liste des analyseurs
            // remarque : Schema::validate impose qu'il y ait au moins 1 analyseur/index
            $data = $index->getData();
            $classes = $data['analyzer'];
            $prefix = $data['_id'];
            $weight = $data['weight'];
            $field = $data['_field'];
            $fields = $data['fields']->getData();

            $data = null;

            // Cas 1. L'index porte sur les zones d'un groupe de champs
            if ($field)
            {
                $start = strlen($field) + 1;
                $data = array();
                if (isset($document[$field]))
                {
                    foreach((array) $document[$field] as $item)
                    {
                        $value = null;
                        foreach($fields as $i=>$zone)
                        {
                            $zone = substr($zone, $start);
                            if (isset($item[$zone]))
                            {
                                if (is_null($value)) $value = $item[$zone]; else $value .= '|' . $item[$zone];
                            }
                        }
                        $data[] = $value;
                    }
                }
            }

            // Cas2. L'index porte sur des champs simples
            else
            {
//                 if (count($fields) === 1)
//                 {
//                     $data = isset($document[$fields[0]]) ? $document[$field[0]] : null;
//                 }
//                 else
                {
                    $data = array();
                    foreach($fields as $field)
                    {
                        if (isset($document[$field])) $data = array_merge($data, (array)$document[$field]);
                    }
                }
            }

            // todo : value = startEnd(value). Créer un "StartEndAnalyzer" ?
            $data = new AnalyzerData($index, $data);

            foreach((array)$classes as $class)
            {
//                 self::getAnalyzer($class)->analyze($data);
                if (! isset(self::$analyzerCache[$class]))
                {
                    self::$analyzerCache[$class] = new $class();
                }

                self::$analyzerCache[$class]->analyze($data);
            }

            if ($dump) $data->dump("Index $index->name");

            foreach($data->terms as $term)
            {
                foreach((array)$term as $term)
                {
                    if (strlen($term) > self::MAX_TERM) continue;
                    $doc->add_term($prefix . $term, $weight);
                }
//                 $termGenerator->index_text_without_positions
//                 (
//                     implode(' ', (array)$term),
//                     $weight,
//                     $prefix
//                 );
            }

            $start = 0;
            foreach($data->postings as $position => $term)
            {
                foreach((array)$term as $position => $term)
                {
                    if (strlen($term) > self::MAX_TERM) continue;
                    $doc->add_posting($prefix . $term, $start + $position, $weight);
                }

                $start += 100;
                $start -= $start % 100;

//                 $termGenerator->index_text
//                 (
//                     implode(' ', (array)$term),
//                     $weight,
//                     $prefix
//                 );
            }

            foreach($data->keywords as $term)
            {
                foreach((array)$term as $term)
                {
                    if (strlen($term) > self::MAX_TERM) continue;
                    $doc->add_boolean_term($prefix . $term);
                }
//                 $termGenerator->index_text_without_positions
//                 (
//                     implode(' ', (array)$term),
//                     0,
//                     $prefix
//                 );
            }

            foreach($data->spellings as $term)
            {
                foreach((array)$term as $term)
                {
                    if (strlen($term) > self::MAX_TERM) continue;
                    $this->db->add_spelling($term, 1);
                }
            }

            foreach($data->lookups as $term)
            {
                $p = $prefix . ':';
                foreach((array)$term as $term)
                {
                    if (strlen($term) > self::MAX_TERM) continue;
                    $doc->add_boolean_term($p. $term);
                }
            }

            foreach($data->sortkeys as $term)
            {
                $slot = $index->_slot;
                foreach((array)$term as $term)
                {
                    $doc->add_value($slot, $term);
                }
            }

        }

        if ($dump) echo "Appelle replace_document($docId)\n";
        $docId = $this->db->replace_document($docId, $doc);
        if ($dump) echo "ID ATTRIBUE PAR XAPIAN : ", var_export($docId, true), "\n";
        if ($dump) echo "</pre>";
        return $this;
    }

    public function delete($id)
    {
        $id = $this->id . $id;
        try
        {
            $this->db->delete_document($id);
        }
        catch (\Exception $e)
        {
            $this->handleException($e);
        }
        return $this;
    }

    public function getSearchOptions()
    {
        return array
        (
        //  'equation'			=> '',
            'start'             => 1,
            'max'               => 10,
            'sort'              => '-',
            'filter'            => null,
            'minscore'          => 0,
        //  'rset'              => null,
        //  'defaultop'         => 'OR',
        //  'opanycase'         => true,
        //  'defaultindex'      => null,
            'checkatleast'      => 100,
        //  'facets'            => null,
        //  'boost'             => null,
        //  'auto'              => null,
        //  'defaultequation'   => null,
        //  'defaultfilter'     => null,
        //  'autosort'          => '-', // tri auto : ordre pour une requête booléenne, cf setSortOrder.
        //  'docset'            => null, // un tableau de termes : array('REF'=>array(1,2,3,4...));
        );
    }

    public function find(Query $query, array $options = array())
    {
        return new XapianSearch($this, $query, $options);
    }
}
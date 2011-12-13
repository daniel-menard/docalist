<?php
namespace Fooltext\Store;

use Fooltext\Schema\Schema;
use Fooltext\Document\Document;
use Fooltext\Indexing\AnalyzerData;

class XapianStore implements StoreInterface
{
    /**
     * Options par défaut utilisées par {@link __construct()}.
     *
     * @var array
     */
    protected static $defaultOptions = array
    (
        'path' => null,
        'readonly' => true,
        'create' => false,
        'overwrite' => false,
        'schema' => null,
    );

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

    protected $collections = array();

    /**
     * Ouverture en lecture seule d'une base existante :
     * array('path'=>'...')
     *
     * Ouverture en lecture/écriture d'une base existante :
     * array('path'=>'...', 'readonly'=>false)
     *
     * Créer une nouvelle base de données (exception si elle existe déjà)
     * array('path'=>'...', 'create'=>true, 'schema'=>$schema)
     *
     * Créer une base et écraser la base existante :
     * array('path'=>'...', 'overwrite'=>true, 'schema'=>$schema)
     *
     * Enter description here ...
     * @param array $options
     */
    public function __construct(array $options = array())
    {
        // Détermine les options de la base
        $options = (object) ($options + self::$defaultOptions);

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

        // Charge la liste des collections
        $this->collections = array_fill_keys(array_keys($this->schema->getCollections()), null);

    }

    public function isReadonly()
    {
        if ($this->db instanceof \XapianWritableDatabase) return false;
        return true;
    }

    public function setSchema(Schema $schema)
    {
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

    public function getCollectionNames()
    {
        return array_keys($this->collections);
    }

    public function __get($name)
    {
        if (! array_key_exists($name, $this->collections))
        {
            throw new \Exception("La collection $name n'existe pas");
        }

        return new XapianCollection($this, $this->db, $name);
    }

    public function __isset($name)
    {
        return array_key_exists($name, $this->collections);
    }

/*
    public function reopen()
    {
        $this->db->reopen();
        return $this;
    }
*/
}
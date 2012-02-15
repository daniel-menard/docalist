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

use \Iterator;
use Fooltext\Query\Query;
use \XapianQuery;
use \XapianEnquire;
use \XapianMSet;
use \XapianMSetIterator;
use Fooltext\Schema\Fields;
use Fooltext\Document\Document;

/**
 * Représente le résultat d'une recherche dans une base Xapian.
 *
 * La liste des hits est itérable avec une boucle foreach
 *
 * Au sein de la boucle, les champs du document en cours sont accessibles comme des propriétés
 *
 * Exemple :
 * echo 'Votre recherche : ', $search->getQuery(), "\n"
 * if ($search->isEmpty())
 * {
 *     echo "Aucune réponse\n";
 * }
 * else
 * {
 *     echo $search->count(), " réponses :\n"
 *     foreach ($search as $rank=>$document)
 *     {
 *         echo $document->REF, $document->titre;
 *     }
 * }
 */
class XapianSearch implements Iterator // implements SearchInterface
{
    /**
     * La collection sur laquelle porte la recherche.
     *
     * @var XapianCollection
     */
    protected $collection;

    /**
     * La requête en cours
     *
     * @var Query
     */
    protected $query;

    /**
     * La version Xapian de la requête en cours.
     *
     * @var XapianQuery
     */
    protected $xapianQuery;

    /**
     * @var XapianEnquire
     */
    protected $xapianEnquire;

    /**
     * @var XapianMSet
     */
    protected $xapianMSet;

    /**
     * L'objet XapianMSetIterator permettant de parcourir les réponses obtenues
     *
     * @var \XapianMSetIterator
     */
    protected $xapianMSetIterator;

    /**
     * @var Fields;
     */
    protected $fields;

    /**
     * @var Document
     */
    protected $document;

    public function __construct(XapianCollection $collection, Query $query, array $options = array())
    {
        $this->collection = $collection;
        $this->query = $query;

        $converter = new XapianQueryMaker($collection);
        $this->xapianQuery = $converter->convert($query);
        echo "Xapian Query: <code>", $this->xapianQuery->get_description(), "</code><br />";

        // Initialise l'environnement de recherche
        $this->xapianEnquire = new XapianEnquire($collection->getXapianDatabase());

        // Définit la requête à exécuter
        $this->xapianEnquire->set_query($this->xapianQuery);

        // Lance la recherche
        $this->xapianMSet = $this->xapianEnquire->get_MSet(0, 10, 100);
        echo "MSet description: <code>", $this->xapianMSet->get_description(), "</code><br />";

        // Initialise l'itérateur
        echo "matches estimated: <code>", $this->xapianMSet->get_matches_estimated(), "</code><br />";

        $this->fields = $this->collection->getSchema()->fields;
    }

    public function getCollection()
    {
        return $this->collection;
    }

    public function getQuery()
    {
        return $this->query;
    }

    public function getXapianQuery()
    {
        return $this->xapianQuery;
    }

    public function isEmpty()
    {
        return $this->xapianMSet->is_empty();
    }


    /* <Iterator> */

    public function rewind()
    {
        $this->xapianMSetIterator = $this->xapianMSet->begin();
    }

    public function key()
    {
        return $this->xapianMSetIterator->get_rank();
    }

    public function current()
    {
        $docid = $this->xapianMSetIterator->get_docid();
        return $this->document = $this->collection->get($docid);
    }

    public function next()
    {
        $this->xapianMSetIterator->next();
    }

    public function valid()
    {
        return ! $this->xapianMSetIterator->equals($this->xapianMSet->end());
    }

    /* </Iterator> */

    public function __isset($name)
    {
        return $this->fields->has($name);
    }

    public function __get($name)
    {
        if (! $this->fields->has($name)) return "fields ! has $name"; //return null;
        return $this->document[$name];
    }
}
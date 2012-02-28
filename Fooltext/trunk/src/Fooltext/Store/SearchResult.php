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
use Fooltext\Document\Document;
use Fooltext\Schema\Fields;

/**
 * Représente le résultat d'une recherche c'est-à-dire la liste des réponses obtenues.
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
abstract class SearchResult implements Iterator
{
    /**
     * La base de données sur laquelle porte la recherche en cours.
     *
     * @var StoreInterface
     */
    protected $store;

    /**
     * La requête en cours.
     *
     * @var SearchRequest
     */
    protected $searchRequest;

    /**
     * Le document en cours.
     *
     * @var Document
     */
    protected $document;

    /**
     * Les champs du schéma.
     *
     * @var Fields;
     */
    protected $fields;


    public function __construct(StoreInterface $store, SearchRequest $searchRequest)
    {
        $this->store = $store;
        $this->searchRequest = $searchRequest;
        $this->fields = $this->store->getSchema()->fields;
    }

    /**
     * Retourne la base de données sur laquelle porte la recherche.
     *
     * @return StoreInterface
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Retourne la requête exécutée.
     *
     * @return SearchRequest
     */
    public function getSearchRequest()
    {
        return $this->searchRequest;
    }

    /**
     * Indique si la liste des réponses obtenues est vide.
     *
     * @return bool
     */
    abstract public function isEmpty();

    /**
     * Retourne une estimation du nombre de réponses obtenues.
     *
     * @param int|string $countType le type d'estimation à fournir ou le
     * libellé à utiliser

     * @return int|string
     */
    abstract public function count();

    /* Interface Iterator */

    /**
     * Interface Iterator : va sur la première réponse obtenue.
     */
    public function rewind()
    {
    }


    /**
     * Interface Iterator : retourne le rang de la réponse en cours.
     *
     * @return int
     */
    public function key()
    {
    }

    /**
     * Interface Iterator : retourne le document en cours.
     *
     * @return Document
     */
    public function current()
    {

    }

    /**
     * Interface Iterator : va sur la réponse suivante.
     */
    public function next()
    {

    }

    /**
     * Interface Iterator : indique s'il y a un document en cours.
     *
     * @return bool
     */
    public function valid()
    {

    }


    // Accès aux champs du document en cours comme s'il s'agissait de propriétés de l'objet SearchResult

    /**
     * Indique si le champ indiqué existe.
     *
     * Lé méthode retourne vrai pour tous les champs qui existent dans le schéma,
     * que ceux-ci soient présents ou non dans le document en cours.
     *
     * @param string $name
     * @return bool
     */
    public function __isset($name)
    {
        return $this->fields->has($name);
    }

    /**
     * Retourne le contenu du champ indiqué.
     *
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (! $this->fields->has($name)) return "fields ! has $name"; //return null;
        return $this->document[$name];
    }

// -------------------------------

    /**
     * Retourne la liste des mots-vides qui ont été ignorés lors de la recherche.
     *
     * getStopWords retourne la liste des mots de la requête qui ont été ignorés
     * parce qu'ils figuraient dans la liste des mots-vides déinis dans la base.
     *
     * Par exemple, pour la recherche <code>outil pour le web, pour internet</code>
     * la méthode pourrait retourner <code>array('pour', 'le')</code>.
     *
     * Par défaut, les mots-vides retournés sont dédoublonnés, mais vous pouvez
     * passer <code>false</code> en paramètre pour obtenir la liste brute (dans
     * l'exemple ci-dessus, on obtiendrait <code>array('pour', 'le', 'pour')</code>
     *
     * @param bool $removeDuplicates flag indiquant s'il faut dédoublonner ou non la
     * liste des mots-vides (true par défaut).
     *
     * @return array un tableau contenant la liste des termes obtenus.
     */
    abstract public function getStopwords($removeDuplicates = true);

    /**
     * Retourne la liste des termes de recherche générés par la requête.
     *
     * getQueryTerms construit la liste des termes qui ont été pris en compte
     * lors de la recherche.
     *
     * La liste comprend tous les termes présents dans la requête (mais pas les
     * mots vides) et tous les termes générés par les troncatures.
     *
     * Par exemple, la requête <code>éduc* pour la santé</code> pourrait
     * retourner <code>array('educateur', 'education', 'sante')</code>.
     *
     * Par défaut, les termes retournés sont filtrés de manière à pouvoir être
     * présentés à l'utilisateur (dédoublonnage des termes, suppression des
     * préfixes internes, etc.), mais vous pouvez passer <code>false</code>
     * en paramètre pour obtenir la liste brute.
     *
     * @param bool $internal flag indiquant s'il faut filtrer ou non la liste
     * des termes.
     *
     * @return array un tableau contenant la liste des termes obtenus.
     */
    abstract public function getQueryTerms($internal = false);

    /**
     * Retourne la liste des termes de la requête qui figurent dans le document
     * en cours.
     *
     * getMatchingTerms construit l'intersection entre la liste des termes
     * générés par la requête et la liste de termes du document en cours.
     *
     * Cela permet, entre autres, de comprendre pourquoi un document apparaît
     * dans la liste des réponses.
     *
     * Par défaut, les termes retournés sont filtrés de manière à pouvoir être
     * présentés à l'utilisateur (dédoublonnage des termes, suppression des
     * préfixes internes utilisés dans les index, etc.), mais vous pouvez
     * passer <code>false</code> en paramètre pour obtenir la liste brute.
     *
     * @param bool $internal flag indiquant s'il faut filtrer ou non la liste
     * des termes.
     *
     * @return array un tableau contenant la liste des termes obtenus.
     */
    abstract public function getMatchingTerms($internal = false);

    abstract public function getCorrectedQuery();
}
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
use \Exception;

use \XapianQueryParser;

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
class XapianSearchResult extends SearchResult
{
    /**
     * La requête xapian générée à partir de searchRequest.
     *
     * @var XapianQuery
     */
    protected $xapianQuery;

    /**
     * @var XapianEnquire
     */
    protected $xapianEnquire;

    /**
     * @var XapianQueryParser
     */
    protected $xapianQueryParser;

    /**
     * @var XapianMSet
     */
    protected $xapianMSet;

    /**
     * Une estimation du nombre de réponses obtenues.
     *
     * (XapianMSet::get_matches_estimated())
     *
     * @var int
     */
    protected $count;

    /**
     * L'objet XapianMSetIterator permettant de parcourir les réponses obtenues
     *
     * @var \XapianMSetIterator
     */
    protected $xapianMSetIterator;

    public function __construct(StoreInterface $store, SearchRequest $searchRequest)
    {
        if (! $store instanceof XapianStore) throw new \InvalidArgumentException();

        parent::__construct($store, $searchRequest);

//        echo "Paramètres de la recherche :";
//        $searchRequest->dump();

        $this->xapianQueryParser = $store->getQueryParser();

        $this->xapianQuery = $this->createXapianQuery($searchRequest);
//        echo "Xapian Query: <code>", $this->xapianQuery->get_description(), "</code><br />";

        // Initialise l'environnement de recherche
        $this->xapianEnquire = new XapianEnquire($store->getXapianDatabase());

        // Définit la requête à exécuter
        $this->xapianEnquire->set_query($this->xapianQuery);

        // Lance la recherche
        $this->xapianMSet = $this->xapianEnquire->get_MSet($searchRequest->start(), $searchRequest->max(), $searchRequest->checkatleast());

        // Détermine le nombre de réponses obtenues
        $this->count = $this->xapianMSet->get_matches_estimated();

        // Si on n'a aucune réponse parce que start était "trop grand", ré-essaie en ajustant start
        if ($this->xapianMSet->is_empty() && $this->count > 1 && $searchRequest->start() > $this->count)
        {
            // le mset est vide, mais on a des réponses (count > 0) et le start demandé était
            // supérieur au count obtenu. Fait pointer start sur la 1ère réponse de la dernière page
            $searchRequest->start($this->count-(($this->count-1) % $searchRequest->max()));

            // Relance la recherche
            $this->xapianMSet = $this->xapianEnquire->get_MSet($searchRequest->start(), $searchRequest->max(), $searchRequest->checkatleast());
        }

        // Si on n'a aucune réponse, retourne false
        if ($this->xapianMSet->is_empty())
        {
            $this->count = 0;
        }
    }

    public function isEmpty()
    {
        return $this->xapianMSet->is_empty();
    }

    /**
     * Retourne une estimation du nombre de réponses obtenues lors de la
     * dernière recherche exécutée.
     *
     * @param null|string $format Par défaut, (format = null), la méthode retourne un entier
     * contenant une estimation du nombre de réponses. Vous pouvez formatter le résultat en
     * passant en paramètre un format tel que "Environ %s réponses". Dans ce cas, la méthode
     * retournera un entier si elle connait le nombre exact de réponses et une chaine formattée
     * sinon.
     *
     * @return int|string
     */
    public function count($format = null)
    {
        if (is_null($format) || $this->count === 0) return $this->count;

        $min = $this->xapianMSet->get_matches_lower_bound();
        $max = $this->xapianMSet->get_matches_upper_bound();

        // Si min==max, c'est qu'on a le nombre exact de réponses, pas d'évaluation
        if ($min === $max) return $min;

        $unit = pow(10, floor(log10($max-$min))-1);
        $round = max(1, round($this->count / $unit)) * $unit;


        // Dans certains cas, on peut se retrouver avec une évaluation inférieure à start, ce
        // qui génère un pager de la forme "Réponses 2461 à 2470 sur environ 2000".
        // Quand on détecte ce cas, passe à l'unité supérieure.
        // Cas trouvé avec la requête "prise en +charge du +patient diabétique"
        // dans la base documentaire bdsp.
        if ($round < $this->searchRequest->start())
        {
            $round = max(1, round($count / $unit)+1) * $unit;
        }

        $round = number_format($round, 0, '.', ' ');

        if ($unit === 0.1)
        {
            return '~&#160;' . $round; //  ou '±&#160;'
        }

        return sprintf($format, $round);
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
        return $this->document = $this->store->get($docid);
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


// -------------------------------

    /**
     * Crée la requête xapian à partir de la requête passée en paramètre.
     *
     * @param SearchRequest $request
     * @return XapianQuery
     */
    protected function createXapianQuery(SearchRequest $request)
    {
        $defaultop = $request->defaultop() === 'and' ? XapianQuery::OP_AND : XapianQuery::OP_OR;

        $query = $request->equation();
        if ($query)
        {
            $query = $this->parseQuery($query, $defaultop, XapianQuery::OP_AND);
        }

        /*
            Combinatoire utilisée pour construire l'équation de recherche :
            +----------+-----------------+---------------------+-------------------+
            | Type de  |    Opérateur    | Opérateur entre les |  Opérateur entre  |
            | requête  | entre les mots  |  valeurs d'un champ | champs différents |
            +----------+-----------------+---------------------+-------------------+
            |   PROB   |    default op   |        AND          |        AND        |
            +----------+-----------------+---------------------+-------------------+
            |   BOOL   |    default op   |        OR           |        AND        |
            +----------+-----------------+---------------------+-------------------+
            |   LOVE   |    default op   |        AND          |        AND        |
            +----------+-----------------+---------------------+-------------------+
            |   HATE   |    default op   |        OR           |        OR         |
            +----------+-----------------+---------------------+-------------------+
         */
        $types = array
        (
            'prob'  => array(XapianQuery::OP_AND, XapianQuery::OP_AND), //
            'bool'  => array(XapianQuery::OP_OR , XapianQuery::OP_AND), //
            'love'  => array(XapianQuery::OP_AND, XapianQuery::OP_AND), // Tous les mots sont requis, donc on parse en "ET"
            'hate'  => array(XapianQuery::OP_OR , XapianQuery::OP_OR) , // Le résultat sera combiné en "AND_NOT hate", donc on parse en "OU"
        );

        foreach ($types as $type => $op)
        {
//             echo "Compilation des équations de type $type<br />";
            $queries = array();
            foreach((array) $request->$type() as $index => $equation)
            {
//                 echo "- Equation = $index:$equation<br />";
                $queries[] = $this->parseQuery($equation, $defaultop, $op[0], $index);
            }

            switch (count($queries))
            {
                case 0: // Aucune requête de type $type, rien à faire
                    $$type = null;
                    break;

                case 1: // Une seule requête de type $type, on l'utilise telle quelle
                    $$type = $queries[0];
                    break;

                default: // Plusieurs requêtes de type $type : on les combine ensemble
                    $$type = new XapianQuery($op[1], $queries);
                    break;
            }
        }

        // Crée la partie principale de la requête sous la forme :
        // ((query AND love AND_MAYBE prob) AND_NOT hate) FILTER bool
        // Si defaultop=AND, le AND_MAYBE devient OP_AND
        if ($love || $query)
        {
            if ($love)
            {
                $query = (is_null($query) || $this->isMatchAll($query))
                ? $love
                : new XapianQuery(XapianQuery::OP_AND, $query, $love);
            }

            if ($prob)
            {
                if (is_null($query) || $this->isMatchAll($query))
                {
                    $query = $prob;
                }
                elseif ($defaultop === XapianQuery::OP_OR)
                {
                    $query=new XapianQuery(XapianQuery::OP_AND_MAYBE, $query, $prob);
                }
                else
                {
                    // todo : AND ou AND_MAYBE quand defaultop = AND ???
                    //$query=new XapianQuery(XapianQuery::OP_AND_MAYBE, $query, $prob);
                    $query=new XapianQuery(XapianQuery::OP_AND, $query, $prob);
                }
            }
        }
        else
        {
            $query=$prob;
        }

        if ($hate)
        {
            // on ne peut pas faire null AND_NOT xxx. Si query est null, crée une query '*'
            if (is_null($query)) $query = new XapianQuery('');
            $query = new XapianQuery(XapianQuery::OP_AND_NOT, $query, $hate);
        }

        if ($bool)
        {
            if (is_null($query) || $this->isMatchAll($query))
                $query = $bool;
            else
                $query = new XapianQuery(XapianQuery::OP_FILTER, $query, $bool);
        }


// XapianDatabase:1997
// filter
// docset
// default equation
// default filter
// boost
// création de la "version affichée à l'utilisateur" de la requête
        return $query;
    }

    /**
     * Indique si la requête xapian passée en paramètre est Xapian::MatchAll.
     *
     * @param XapianQuery $query
     * @return bool
     */
    private function isMatchAll(XapianQuery $query)
    {
        return $query->get_description() === 'Xapian::Query(<alldocuments>)';
    }

    private function parseQuery($equations, $intraOpCode=XapianQuery::OP_OR, $interOpCode=XapianQuery::OP_OR, $index=null)
    {
        // Paramètre l'opérateur par défaut du Query Parser
        $this->xapianQueryParser->set_default_op($intraOpCode);

        // Détermine les flags du Query Parser
        $flags = XapianQueryParser::FLAG_BOOLEAN
               | XapianQueryParser::FLAG_PHRASE
               | XapianQueryParser::FLAG_LOVEHATE
               | XapianQueryParser::FLAG_WILDCARD
               | XapianQueryParser::FLAG_PURE_NOT;

//         if ($this->searchRequest->opanycase())
//         {
//             $flags |= XapianQueryParser::FLAG_BOOLEAN_ANY_CASE;
//         }

        // Pour tokenizer l'équation, on utilise la même que l'analyseur Lowercase
        $map = \Fooltext\Indexing\Lowercase::$map;

        // Sauf qu'on veut conserver le tiret pour pouvoir gérer la syntaxe -hate
        unset($map['-']);

        $query = array();
        foreach ((array)$equations as $equation)
        {
            $equation = trim($equation);
            if ($equation === '') continue;

            if ($equation === '*')
            {
                $query[] = new XapianQuery('');
                continue;
            }

            // Pré-traitement de l'équation pour que xapian l'interprête comme on souhaite

            // Transforme l'équation en minus non accentuées en conservant les autres caractères
            $equation= strtr($equation, $map); // on ne gère plus opanycase : toujours à true

            // Transforme les sigles de 2 à 9 lettres en mots
            $equation = preg_replace_callback
            (
                '~(?:[a-z0-9]\.){2,9}~i',
                function ($matches) { return str_replace('.', '', $matches[0]); },
                $equation
            );

            // Convertit les recherches à l'article en termes Xapian : [doe john] -> _doe_john_
            $equation = preg_replace_callback
            (
                '~\[\s*(.*?)\s*\]~',
                function($matches)
                {
                    $term = $matches[1];
                    return '_' . implode('_', str_word_count($term, 1, '0123456789')) . (substr($term, -1) === '*' ? '*' : '_');
                },
                $equation
            );

            // Elimine les espaces éventuels après les noms d'index (ticket bdsp 125. Index= test non géré)
            $equation = preg_replace('~\b(\w+)\s*[:]\s*~', '$1:', $equation);

            // Convertit les opérateurs booléens français en anglais, sauf dans les phrases
            $t = explode('"', $equation);  //   a ET b ET "c ET d"
            foreach($t as $i => & $h)
            {
                if ($i % 2 === 1) continue;
                $h = preg_replace
                (
                    array('~\bET\b~','~\bOU\b~','~\b(?:SAUF|BUT)\b~'),
                    array('and', 'or', 'not'),
                    $h
                );
            }
            $equation = implode('"', $t);

            // Ajoute le nom de l'index sur lequel porte la recherche
            if (! is_null($index))
            {
                $equation = strtolower($index) . ':(' . $equation . ')';
            }

            // Construit la requête
            $query[] = $this->xapianQueryParser->parse_Query($equation, $flags);
        }

        switch(count($query))
        {
            case 0: return null;
            case 1: return $query[0];
            default: new XapianQuery($interOpCode, $query);
        }
    }

    public function getStopwords($removeDuplicates = true)
    {
        if (is_null($this->xapianQueryParser)) return array();

        $stopwords = array();
        $begin = $this->xapianQueryParser->stoplist_begin();
        $end = $this->xapianQueryParser->stoplist_end();

        if ($removeDuplicates)
        {
            while(! $begin->equals($end))
            {
                $stopwords[$begin->get_term()] = true;
                $begin->next();
            }

            return array_keys($stopwords);
        }

        while(! $begin->equals($end))
        {
            $stopwords[] = $begin->get_term(); // pas de dédoublonnage
            $begin->next();
        }

        return $stopwords;
    }

    public function getQueryTerms($internal = false)
    {
        // Si aucune requête n'a été exécutée, retourne un tableau vide
        if (is_null($this->xapianQuery)) return array();

        $terms = array();
        $begin = $this->xapianQuery->get_terms_begin();
        $end = $this->xapianQuery->get_terms_end();
        if ($internal)
        {
            while (! $begin->equals($end))
            {
                $terms[] = $begin->get_term();
                $begin->next();
            }

            return $terms;
        }

        while (! $begin->equals($end))
        {
            $term = $begin->get_term();

            // Supprime le préfixe de l'index
            if (false !== $pt=strpos($term, ':')) $term = substr($term,$pt+1);

            // Pour les articles, supprime les underscores
            $term = strtr(trim($term, '_'), '_', ' ');

            // Dédoublonnage
            $terms[$term]=true;

            $begin->next();
        }
        return array_keys($terms);

    }

    public function getMatchingTerms($internal = false)
    {
        if (is_null($this->xapianMSetIterator)) return array();

        $terms = array();
        $begin = $this->xapianEnquire->get_matching_terms_begin($this->xapianMSetIterator);
        $end = $this->xapianEnquire->get_matching_terms_end($this->xapianMSetIterator);
        if ($internal)
        {
            while(! $begin->equals($end))
            {
                $terms[] = $begin->get_term();
                $begin->next();
            }
            return $terms;

        }

        while(! $begin->equals($end))
        {
            $term = $begin->get_term();

            // Supprime le préfixe de l'index
            if (false !== $pt=strpos($term, ':')) $term = substr($term,$pt+1);

            // Pour les articles, supprime les underscores
            $term = strtr(trim($term, '_'), '_', ' ');

            // Dédoublonnage
            $terms[$term] = true;

            $begin->next();
        }

        return array_keys($terms);
    }

    public function getCorrectedQuery()
    {

    }

}
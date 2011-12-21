<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  QueryParser
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id: Words.php 10 2011-12-13 15:45:47Z daniel.menard.35@gmail.com $
 */
namespace Fooltext\QueryParser;

use Fooltext\Query\OrQuery;
use Fooltext\Query\AndQuery;
use Fooltext\Query\NotQuery;
use Fooltext\Query\AndMaybeQuery;
use Fooltext\Query\PhraseQuery;
use Fooltext\Query\NearQuery;
use Fooltext\Query\WildcardQuery;
use Fooltext\Query\TermQuery;
use Fooltext\Query\MatchAllQuery;
use Fooltext\Query\MatchNothingQuery;

/**
 * Hand written recursive descent parser LL(1)
 * Enter description here ...
 * @author dmenard
 *
 */
class Parser
{
    /**
     * Analyseur lexical
     *
     * @var Lexer
     */
    protected $lexer;

    /**
     * Le champ sur lequel porte la recherche.
     *
     * @var string
     */
    protected $field;

    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    protected function read($equation = null)
    {
        $this->token = $this->lexer->read($equation);
    }

    public function parseQuery($equation, $field = null)
    {
        // Initialise le lexer
        $this->read($equation);

        // Stocke le champ en cours
        $this->prefix = $field;

        // Analyse l'équation
        $query = $this->parseExpression();

        // Vérifie qu'on a tout lu
        if ($this->token !== Lexer::TOK_END)
            echo "L'EQUATION N'A PAS ETE ANALYSEE COMPLETEMENT <br />";
        // Retourne la requête
        return $query;
    }

    private function parseExpression()
    {
        $query = $loveQuery = $hateQuery = array();

        for(;;)
        {
            switch($this->token)
            {
                case Lexer::TOK_BLANK:
                    $this->read();
                    break;

                case Lexer::TOK_TERM:
                case Lexer::TOK_WILD_TERM:
                case Lexer::TOK_PHRASE_TERM:
                case Lexer::TOK_PHRASE_WILD_TERM:
                case Lexer::TOK_INDEX_NAME:
                case Lexer::TOK_AND:  // explication : la requête commence par un mot-clé.
                case Lexer::TOK_OR:   // On le traite comme un terme car ça peut être le début
                case Lexer::TOK_NEAR: // d'une phrase (exemple : near death experience)
                case Lexer::TOK_ADJ:
                    $query[] = $this->parseOr();
                    break;

                case Lexer::TOK_LOVE:
                    $this->read();
                    $loveQuery[] = $this->parseCompound();
                    break;

                case Lexer::TOK_HATE:
                    $this->read();
                    if ($this->token !== Lexer::TOK_END)
                    {
                        $hateQuery[] = $this->parseCompound();
                    }
                    // sinon : on a juste "-", ignore silencieusement
                    break;

                case Lexer::TOK_AND_NOT:
                    $sav = $this->lexer->getTokenText();
                    $this->read();
                    if ($this->token === Lexer::TOK_END)
                    {
                        $query[] = $sav; // la requête contient seulement "not". Traite comme un terme
                    }
                    else
                    {
                        $hateQuery[] = $this->parseCompound();
                    }
                    break;

                case Lexer::TOK_START_PARENTHESE:
                    $query[] = $this->parseCompound();
                    break;

                case Lexer::TOK_END_PARENTHESE:
                    $this->read();
                    break; // une parenthèse fermante superflue. Ignore silencieusement l'erreur

                case Lexer::TOK_END:
                    break 2;

                case Lexer::TOK_MATCH_ALL:
                    $query[] = $this->parseCompound();
                    break;

//                 case Lexer::TOK_RANGE_START:
//                 case Lexer::TOK_RANGE_END:
//                     // pour le moment, on ignore
//                     $this->read();
//                     break;
            }
        }

        $query     = (count($query)     > 1) ? new OrQuery ($query    , $this->field) : reset($query);
        $loveQuery = (count($loveQuery) > 1) ? new AndQuery($loveQuery, $this->field) : reset($loveQuery);
        $hateQuery = (count($hateQuery) > 1) ? new AndQuery($hateQuery, $this->field) : reset($hateQuery);

        if (! $query)
        {
            $query = $loveQuery;
        }
        elseif ($loveQuery)
        {
            $query = new AndMaybeQuery(array($loveQuery, $query), $this->field);
        }

        if ($hateQuery)
        {
            $query = new NotQuery(array($query ? $query : new MatchAllQuery(), $hateQuery), $this->field);
        }

        if (is_string($query)) $query = new TermQuery($query, $this->field);
        if ($query === false) $query = new MatchNothingQuery($this->field);

        return $query;
    }

    private function parseCompound()
    {
        switch($this->token)
        {
            case Lexer::TOK_TERM:
            case Lexer::TOK_AND:  // explication : la requête commence par un mot-clé.
            case Lexer::TOK_OR:   // On le traite comme un terme car ça peut être le début
            case Lexer::TOK_NEAR: // d'une phrase (exemple : near death experience)
            case Lexer::TOK_ADJ:
                $term = $this->lexer->getTokenText();
                $this->read();
                return new TermQuery($term, $this->field);

            case Lexer::TOK_WILD_TERM:
                $query = new WildcardQuery($this->lexer->getTokenText(), $this->field);
                $this->read();
                return $query;

            case Lexer::TOK_INDEX_NAME:

                // Sauvegarde le préfixe actuel
                $previousField = $this->field;

                // Vérifie que ce nom d'index existe et récupère le(s) préfixe(s) associé(s)
                $this->field = $this->lexer->getTokenText();
//                 if (! isset($this->structure['index'][$index]))
//                 {
//                     throw new \Exception("Impossible d'interroger sur le champ '$index' : index inconnu");
//                 }

//                 $this->prefix=$this->structure['index'][$index];

                // Analyse l'expression qui suit
                $this->read();
                $query = $this->parseCompound();

                // Restaure le préfixe précédent
                $this->field = $previousField;
                return $query;

            case Lexer::TOK_START_PARENTHESE:
                $this->read();
                $query = $this->parseExpression();
//                 echo "token = ", $this->token, $this->lexer->getTokenText(), "<br />";
//                 if ($this->token === Lexer::TOK_END_PARENTHESE)
//                 {
//                     $this->read();
//                     die('here');
//                     //throw new \Exception($this->token.'Parenthèse fermante attendue');
//                 }
//                 // Sinon : Il manque une parenthèse fermante, ignore l'erreur

                return $query;


            case Lexer::TOK_PHRASE_TERM:
            case Lexer::TOK_PHRASE_WILD_TERM:
                $terms=array();
                $type=array();
                do
                {
                    if ($this->token===Lexer::TOK_PHRASE_WILD_TERM)
                    {
                        $terms[] = new WildcardQuery($this->lexer->getTokenText(), $this->field);
                    }
                    else
                    {
                        $terms[] = $this->lexer->getTokenText();
                    }
                    $this->read();
                }
                while ($this->token===Lexer::TOK_PHRASE_TERM || $this->token===Lexer::TOK_PHRASE_WILD_TERM);

                if (count($terms) == 1)
                {
                    $term = reset($terms);
                    if (is_string($term)) return new TermQuery($term, $this->field);
                    return $term; // WildcardQuery
                }

                return new PhraseQuery($terms, $this->field);

            case Lexer::TOK_MATCH_ALL:
                $this->read();
                return new MatchAllQuery($this->field);

            case Lexer::TOK_LOVE:
            case Lexer::TOK_HATE:
                // la requête contient juste "+" ou "-"
                // on ignore silencieusement
                die('here');
                $this->read();
                break;

            case Lexer::TOK_END:
                return new MatchNothingQuery($this->field);
//         TOK_END = -1, TOK_BLANK = 1,
//         TOK_AND_NOT = 12,
//         TOK_RANGE_START = 60, TOK_RANGE_END = 61;
//         TOK_END_PARENTHESE = 51,

        }
    }


    private function parseOr()
    {
        return $this->parse('parseAnd', Lexer::TOK_OR, 'OrQuery');

        $query = $this->parseAnd();
        while ($this->token === Lexer::TOK_OR)
        {
            $this->read();
            $query = new OrQuery($query, $this->parseExpression()); //parseAnd
            echo "parseOr $query<br />";
        }
        return $query;
    }

    private function parseAnd()
    {
        return $this->parse('parseAndNot', Lexer::TOK_AND, 'AndQuery');

        $query = $this->parseAndNot();
        while ($this->token === Lexer::TOK_AND)
        {
            $this->read();
            $query = new AndQuery($query, $this->parseAndNot());//parseAndNot
            echo "parseAnd $query<br />";
        }
        return $query;
    }

    private function parseAndNot()
    {
        return $this->parse('parseNear', Lexer::TOK_AND_NOT, 'NotQuery');

        $query = $this->parseNear();
        while ($this->token === Lexer::TOK_AND_NOT)
        {
            $this->read();
            $query = new NotQuery($query, $this->parseNear());
            echo "parseAndNot $query<br />";
        }
        return $query;
    }

    private function parseNear()
    {
        return $this->parse('parseAdj', Lexer::TOK_NEAR, 'NearQuery', 5); // TODO: 1=window size du NEAR, à mettre en config

        $query = $this->parseAdj();
        while ($this->token === Lexer::TOK_NEAR)
        {
            $this->read();
            //$query = new Query(Query::QUERY_NEAR, array($query, $this->parseAdj()), 5); // TODO: 5=window size du near, à mettre en config
            $query = new NearQuery(array($query, $this->parseAdj()), 5); // TODO: 5=window size du near, à mettre en config
            echo "parseNear $query<br />";
        }
        return $query;
    }


    private function parseAdj()
    {
        return $this->parse('parseCompound', Lexer::TOK_ADJ, 'NearQuery', 1); // TODO: 1=window size du ADJ, à mettre en config

        $args = array();
        for(;;)
        {
            $args[] = $this->parseCompound();
            if ($this->token !== Lexer::TOK_ADJ) break;
            $this->read();
        }
        if (count($args) === 1 ) return reset($args);
        echo "ADJ(", implode(', ', $args), ")<br />";
        return new NearQuery($args, 1); // TODO: 1=window size du ADJ, à mettre en config
    }
    private function parse($method, $token, $class, $option = null)
    {
        $args = array();
        for(;;)
        {
            $args[] = $this->$method();
            if ($this->token !== $token) break;
            $this->read();
        }
        if (count($args) === 1 ) return reset($args);

        //echo $this->lexer->getTokenName($token), "(", implode(', ', $args), ")<br />";

        $class = 'Fooltext\\Query\\' . $class;
        return new $class($args, $this->field, $option); // TODO: 1=window size du ADJ, à mettre en config
    }
}
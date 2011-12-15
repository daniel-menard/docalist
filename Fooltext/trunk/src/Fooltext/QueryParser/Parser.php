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

use Fooltext\Query\Query;

class Parser
{
    /**
     * Analyseur lexical
     *
     * @var Lexer
     */
    protected $lexer;

    private $prefix;

    public function __construct()
    {
        $this->lexer = new Lexer();
    }

    protected function read($equation = null)
    {
        $this->token = $this->lexer->read($equation);
    }
    public function parseQuery($equation)
    {
        // Initialise le lexer
        $this->read($equation);

        // Préfixe par défaut
        $this->prefix='';

        // Analyse l'équation
        $query=$this->parseExpression();

        // Vérifie qu'on a tout lu
        if ($this->token !== Lexer::TOK_END)
            echo "L'EQUATION N'A PAS ETE ANALYSEE COMPLETEMENT <br />";
        // Retourne la requête
        return $query;
    }

    private function parseExpression()
    {
        $query=null;
        $loveQuery=null;
        $hateQuery=null;
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
                    if (is_null($query))
                        $query = $this->parseOr();
                    else
                        $query = new Query(Query::QUERY_OR, $query, $this->parseOr()); // defaultOp
                    break;

                case Lexer::TOK_LOVE:
                    $this->read();
                    if (is_null($loveQuery))
                        $loveQuery = $this->parseCompound();
                    else
                        $loveQuery = new Query(Query::QUERY_AND, $loveQuery, $this->parseCompound());
                    echo "Love query : $loveQuery<br />";
                    break;

                case Lexer::TOK_HATE:
                    $this->read();
                    if (is_null($hateQuery))
                        $hateQuery = $this->parseCompound();
                    else
                        $hateQuery = new Query(Query::QUERY_OR, $hateQuery, $this->parseCompound());
                    echo "Hate query : $hateQuery<br />";
                    break;

                case Lexer::TOK_START_PARENTHESE:
                    if (is_null($query))
                        $query = $this->parseCompound();
                    else
                        $query = new Query(Query::QUERY_OR, $query, $this->parseCompound());
                    break;

                case Lexer::TOK_END:
                case Lexer::TOK_END_PARENTHESE:
                    break 2;

                case Lexer::TOK_MATCH_ALL:
                    $query=$this->parseCompound();
                    break;

                default:
                    echo 'inconnu2 : ', 'token=', $this->token, ', text=', $this->lexer->getTokenText(), "<br />";
                    return;
            }
        }
        if (is_null($query))
        {
            $query = $loveQuery;
        }
        elseif (! is_null($loveQuery))
        {
            $query = new Query(Query::QUERY_AND_MAYBE, $loveQuery, $query);
        }

        if (!is_null($hateQuery)) $query=new Query(Query::QUERY_NOT, $query, $hateQuery);
        return $query;
    }

    private function parseCompound()
    {
        switch($this->token)
        {
            case Lexer::TOK_WILD_TERM:
            case Lexer::TOK_TERM:
                $term=$this->lexer->getTokenText();

                $terms=array();
                if($this->token===Lexer::TOK_WILD_TERM)
                {
                    $terms=array_merge($terms, $this->expandTerm($term, $this->prefix));
                }
                else
                {
                  if (false)
                    {
                        if
                        (
                                isset($this->structure['stopwords'][$term])
                            ||
                                strlen($term)<3 && !ctype_digit($term)
                        )
                        {
                            $query=new \XapianQuery('terme vide'); // TODO: à revoir. retourner une MATCH_ALL ou une MATCH_NOTHING ?
                            $this->read();
                            break;
                        }
                    }
                    foreach((array)$this->prefix as $prefix)
                        $terms[]=$prefix.$term;

                }
                $this->read();

//                $query=new \XapianQuery(\XapianQuery::OP_OR, $terms); // TODO: OP_OR=default operator, à mettre en config
                $query = new Query(Query::QUERY_OR, $terms);// TODO: OP_OR=default operator, à mettre en config
                echo "parseCompound (termes) : $query<br />";
                return $query;

            case Lexer::TOK_INDEX_NAME:

                // Sauvegarde le préfixe actuel
                $save=$this->prefix;

                // Vérifie que ce nom d'index existe et récupère le(s) préfixe(s) associé(s)
                $index=$this->lexer->getTokenText();
//                 if (! isset($this->structure['index'][$index]))
//                 {
//                     throw new \Exception("Impossible d'interroger sur le champ '$index' : index inconnu");
//                 }

//                 $this->prefix=$this->structure['index'][$index];
                $this->prefix="&lt;$index&gt;";

                // Analyse l'expression qui suit
                $this->read();
                $query=$this->parseCompound();

                // Restaure le préfixe précédent
                $this->prefix=$save;
                return $query;

            case Lexer::TOK_START_PARENTHESE:
                $this->read();
                $query=$this->parseExpression();
                if ($this->token !== Lexer::TOK_END_PARENTHESE)
                {
                    throw new \Exception($this->token.'Parenthèse fermante attendue');
                }
                $this->read();
                return $query;


            case Lexer::TOK_PHRASE_WILD_TERM:
                $nbWild=1;
            case Lexer::TOK_PHRASE_TERM:
                $nbWild=0;

                $terms=array();
                $type=array();
                do
                {
                    $terms[]=$this->lexer->getTokenText();
                    $type[]=$this->token;
                    $this->read();
                }
                while($this->token===Lexer::TOK_PHRASE_TERM || ($this->token===Lexer::TOK_PHRASE_WILD_TERM && (++$nbWild)));
                if ($this->token===Lexer::TOK_BLANK) $this->read();

                // Limitation actuelle de xapian : on ne peut avoir qu'une seule troncature dans une expression
//                if($nbWild>1)
//                    throw new exception("$nbWild xxxLa troncature ne peut être utilisé qu'une seule fois dans une expression entre guillemets");
                if($nbWild>1)                   // TODO: mettre en option ?
                    $op=Query::QUERY_AND;    // plusieurs troncatures : la phrase devient un "et"
                else
                    $op=Query::QUERY_PHRASE;


                // on a des préfixes en cours : p1,p2,p3
                //    -> requête de la forme "term1 term2 term3"
                // on aura autant de phrases qu'on a de préfixes :
                //    -> phrase1 OU phrase 2 OU phrase3
                $phrases=array();
                // (sauf si la requête contient un terme avec troncature et que expand ne retourne rien pour ce préfixe)

                // chaque phrase contient tous les termes
                //    -> (p1:term1 PHRASE p1:term2) OU (p2:term1 PHRASE p2:term2) OU (p3:term1 PHRASE p3:term2)
                $phrase=array();
                foreach((array)$this->prefix as $prefix)
                {
                    foreach($terms as $i=>$term)
                    {
                        if ($type[$i]===Lexer::TOK_PHRASE_TERM)
                        {
                            $phrase[$i]=$prefix.$term;
                        }
                        else
                        {
                            // Génère toutes les possibilités
                            $t=$this->expandTerm($term, $prefix);

                            // Aucun résultat : la phrase ne peut pas aboutir
                            if (count($t)===0) continue 2; // 2=continuer avec le prochain préfixe

                            $phrase[$i]=new Query(Query::QUERY_OR, $t);
                        }
                    }

                    // Fait une phrase avec le tableau phrase obtenu
                    $phrases[]=new Query($op, $phrase); // TODO: 3=window size du PHRASE, à mettre en config
                }

                // COmbine toutes les phrases en ou
                return new Query(Query::QUERY_OR, $phrases);

            case Lexer::TOK_MATCH_ALL:
                $this->read();
                if ($this->prefix==='')
                {
                    return new \XapianQuery('');// la syntaxe spéciale de xapian pour désigner [match anything]
                }

                $t=$this->expandTerm('@has', $this->prefix);
                if (count($t)===0) // aucun des chaps interrogés n'est "comptable", transforme en requete 'toutes les notices'
                {
                    return new \XapianQuery(''); // syntaxe spéciale de xapian : match all
                }

                return new \XapianQuery(\XapianQuery::OP_OR, $t);

            default:
                die('truc inattendu : '.$this->token);
        }
        throw new\Exception('Should not be here, missing return in the switch');
    }


    private function parseOr()
    {
        $query=$this->parseAnd();
        while ($this->token===Lexer::TOK_OR)
        {
            $this->read();
            $query = new Query(Query::QUERY_OR, $query, $this->parseExpression()); //parseAnd
            echo "parseOr $query<br />";
        }
        return $query;
    }

    private function parseAnd()
    {
        $query=$this->parseAndNot();
        while ($this->token===Lexer::TOK_AND)
        {
            $this->read();
            $query = new Query(Query::QUERY_AND, $query, $this->parseAndNot());//parseAndNot
            echo "parseAnd $query<br />";
        }
        return $query;
    }

    private function parseAndNot()
    {
        $query=$this->parseNear();
        while ($this->token===Lexer::TOK_AND_NOT)
        {
            $this->read();
            $query = new Query(Query::QUERY_NOT, $query, $this->parseNear());
            echo "parseAndNot $query<br />";
        }
        return $query;
    }

    private function parseNear()
    {
        $query=$this->parseAdj();
        while ($this->token===Lexer::TOK_NEAR)
        {
            $this->read();
            //$query = new Query(Query::QUERY_NEAR, array($query, $this->parseAdj()), 5); // TODO: 5=window size du near, à mettre en config
            $query = new Query(Query::QUERY_NEAR, $query, $this->parseAdj(), 5); // TODO: 5=window size du near, à mettre en config
            echo "parseNear $query<br />";
        }
        return $query;
    }

    private function parseAdj()
    {
        $query=$this->parseCompound();
        while ($this->token===Lexer::TOK_ADJ)
        {
            $this->read();
            $query=new Query(Query::PHRASE, array($query, $this->parseCompound()), 1); // TODO: 1=window size du ADJ, à mettre en config
            echo "parseAdj $query<br />";
        }
        return $query;
    }

    /**
     * @param mixed $prefix préfixe ou tableau de préfixes
     */
    private function expandTerm($term, $prefix='')
    {
        $max=100; // TODO: option de XapianDatabase ou bien dans la config

        $begin=$this->xapianDatabase->allterms_begin();
        $end=$this->xapianDatabase->allterms_end();

        $terms=array();
        $nb=0;
        foreach((array)$prefix as $prefix)
        {
            $prefixTerm=$prefix.$term;
            $begin->skip_to($prefixTerm);
            while (!$begin->equals($end))
            {
                $h=$begin->get_term();
                if (substr($h, 0, strlen($prefixTerm))!==$prefixTerm)
                    break;

                $terms[]=$h;
                if (++$nb>$max)
                {
                    throw new \Exception("Le terme '$term*' génère trop de possibilités, augmentez la longueur du préfixe");
                }
                $begin->next();
            }
        }
        return $terms;
    }
}

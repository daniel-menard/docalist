<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Query
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Query;

/**
 * Indexe un ISBN. Gère les isbn à 10 et 13 chiffres
 * - un isb13 est indexé tel quel :
 *   "978-2-1234-5680-3" -> keywords='9872123456803'
 * - un isbn10 et indexé comme isbn10 ET comme isbn13 :
 *  "2-1234-5680-2" -> keywords='2123456802', '9872123456803'
 *
 */
class Query implements QueryInterface
{
    const
        QUERY_AND = 1,
        QUERY_OR = 2,
        QUERY_NOT = 3,
        QUERY_AND_MAYBE = 4,
        QUERY_NEAR = 5,
        QUERY_PHRASE = 6;

    protected static $types = array
    (
        self::QUERY_AND => 'AND',
        self::QUERY_OR => 'OR',
        self::QUERY_NOT => 'NOT',
        self::QUERY_AND_MAYBE => 'AND_MAYBE',
        self::QUERY_NEAR => 'NEAR',
        self::QUERY_PHRASE => 'PHRASE',
    );

    protected $type;
    protected $args;

    public function __construct($type, $left, $right = null)
    {
        $this->type = $type;

        if (is_null($left))
        {
            $left = $right;
            $right = null;
        }

        $args = is_array($left) ? $left : array($left);

        if (! is_null($right))
        {
            if (is_array($right)) $args = array_merge($args, $right);
            else $args[] = $right;
        }
        $this->args = $args;
        return;

/*
                                  RIGHT (r)
                     1          2            3             4
                  +------+------------+--------------+------------+
                  | null |   scalar   |    array     |   query    |
           +------+------+------------+--------------+------------+
           |      |      |            |              |            |
         A |  null|Xnull |X    r      |X     r       |X    r      |
           |      |      |            |              |            |
           +------+------+------------+--------------+------------+
           |      |      |            |              |type=r.type?|
         B |scalar|X l   |Xarray(l,r) |Xunshift(r,l) |  l+args(r) |
           |      |      |            |              |: array(l,r)|
LEFT (l)   +------+------+------------+--------------+------------+
           |      |      |            |              |            |
         C | array|X l   |X  l[]=r    |X merge(l,r)  |X   l[]=r   |
           |      |      |            |              |            |
           +------+------+------------+--------------+------------+
           |      |      |type=l.type?|              |            |
         D | query|X l   | args(l)+r  |Xunshift(r,l) | array(l,r) |
           |      |      |:array(l,r) |              |+ same type?|
           +------+------+------------+--------------+------------+
*/
        if (is_null($left) && is_null($right)) $args = null;    // A1
        elseif (is_null($left)) $args = $right;                 // A2, A3, A4
        elseif (is_null($right)) $args = $left;                 // B1, C1, D1
        elseif (is_array($left))                                // C
        {
            if (is_array($right))                               // C3
            {
                $args = array_merge($left, $right);
            }
            else                                                // C2, C4
            {
                $args = $left;
                $args[] = $right;
            }
        }
        elseif (is_array($right))                               // B3, D3
        {
            $args = $right;
            array_unshift($args, $left);
        }
        elseif (is_scalar($left))                               // B
        {
            if (is_scalar($right))                              // B2
            {
                $args = array($left, $right);
            }
            elseif ($type = $right->getType())                  // B4, $right est une query
            {
                $right->getArgs();
                array_unshift($args, $left);
            }
            else
            {
                $args = array($left, $right);                   // B4 également
            }
        }
        elseif(is_scalar($right))                               // D2, D4 $left est une query
        {
            if ($type === $left->getType())                     // D2
            {
                $args = $left->getArgs();
                $args[] = $right;
            }
            else                                                // D2 également
            {
                $args = array($left, $right);
            }
        }
        else                                                    // D4 right et left sont des query
        {
            if ($type === $left->getType())
            {
                $args = array_merge($left->getArgs(), $right->getArgs());
            }
            elseif($type === $right->getType())
            {
                $args = $right->getArgs();
                array_unshift($args, $left);
            }
            else
            {
                $args=array($left,$right);
            }
        }

        $this->args = $args;
    }

    public function getType($asString = false)
    {
        return $asString ? self::$types[$this->type] : $this->type;
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function dump($title='')
    {
        if ($title) echo "<strong>$title</strong> :<br />";
        echo $this->getType(true);
        if ($this->args)
        {
            echo '<ul>';
            foreach($this->args as $arg)
            {
                if ($arg instanceof QueryInterface)
                {
                    echo '<li>';
                    $arg->dump();
                    echo '</li>';
                }
                else
                {
                    echo "<li>$arg</li>";
                }
            }
            echo '</ul>';
        }
    }

    public function __toString()
    {
        if (empty($this->args)) return '{empty-query}';
        if (! is_array($this->args)) return (string) $this->args;
        if (count($this->args) === 1) return (string) reset($this->args);

        $h = '(';
        foreach($this->args as $i=>$arg)
        {
            if ($i) $h .= ' ' . $this->getType(true) . ' ';
            $h .= (string) $arg;
        }
        $h .= ')';
        return $h;
    }
}

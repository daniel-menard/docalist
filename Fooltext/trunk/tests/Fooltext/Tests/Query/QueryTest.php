<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Tests
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Tests;

use Fooltext\Query\Query;
use Fooltext\Query\QueryOr;
use Fooltext\Query\QueryAnd;

class QueryTest extends \PHPUnit_Framework_TestCase
{
    public function test1()
    {
        $this->assertSame('a', (string)new QueryOr('a'));
        $or = new QueryOr(null, 'a');
        var_dump($or);
        $this->assertSame('a', (string) $or);
        $this->assertSame(array('a'), $or->getArgs());

        $or1 = new QueryOr('a', 'b');
        $or2 = new QueryOr('c', 'd');
        $or3 = new QueryOr('e', 'f');
        $this->assertSame('(a OR b)', (string)$or1);
        $this->assertSame(array('a','b'), $or1->getArgs());

        $and1 = new QueryAnd('A', 'B');
        $and2 = new QueryAnd('C', 'D');
        $and3 = new QueryAnd('E', 'F');
        $this->assertSame('(A AND B)', (string)$and1);
        $this->assertSame(array('A','B'), $and1->getArgs());

        $this->assertSame('((a OR b) OR (c OR d))', (string)new Query(Query::QUERY_OR, $or1, $or2));
        $this->assertSame('((a OR b) OR (c OR d))', (string)new Query(Query::QUERY_OR, array($or1, $or2)));
        $this->assertSame('((a OR b) OR (c OR d) OR (e OR f))', (string)new Query(Query::QUERY_OR, array($or1, $or2, $or3)));

//         $this->assertSame('(a OR b OR c OR d)', (string)new Query(Query::QUERY_OR, $or1, $or2));
    }
}
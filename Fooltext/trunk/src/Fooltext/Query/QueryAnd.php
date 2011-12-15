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

class QueryAnd extends Query
{
    public function __construct($left = null, $right = null)
    {
        parent::__construct(Query::QUERY_AND, $left, $right);
    }
}
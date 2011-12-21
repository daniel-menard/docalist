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
 * Requête qui retourne les documents qui contiennent les arguments indiqués
 * dans l'ordre indiqués à une certaine distance maximale les uns des autres.
 */
class PhraseQuery extends PositionalQuery
{
    protected static $type = self::QUERY_PHRASE;

    public function __toString()
    {
        return '"' . implode(' ', $this->args) . '"';
    }
}
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
 * Classe de base (abstraite) pour les requêtes booléennes (AND, OR, NOT, AND_MAYBE).
 */
abstract class BooleanQuery extends Query
{
    public function optimize()
    {
        parent::optimize();

        // Si les sous-requêtes sont du même type que la requête en cours,
        // et qu'elles portent sur le même champ
        // on les fusionne dans la requête en cours. Autrement dit, on supprime
        // les parenthèses inutiles (associativité).
        // Exemples :
        // (a or b) OR (c or d) -> (a or b OR c or d)
        // (a and b) AND (c and d) -> (a and b AND c and d)
        for ($offset = count($this->args) - 1 ; $offset >= 0 ; $offset--)
        {
            $arg = $this->args[$offset];

            $arg->optimize();

            if ($arg->getType() === $this->getType() && $this->field === $arg->getField())
            {
                array_splice($this->args, $offset, 1, $arg->args);
            }

            if (static::$type === self::QUERY_OR && $arg instanceof MatchNothingQuery) unset($this->args[$offset]);
        }
//        var_export($this->args);
        return $this;
    }
}
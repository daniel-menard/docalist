<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Schema
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Schema;

/**
 * Liste de champs définies dans une collection.
 *
 * C'est une collection d'objets {@link Field}.
 */
class Fields extends Nodes
{
    protected static $class = 'Fooltext\\Schema\\Field';
}
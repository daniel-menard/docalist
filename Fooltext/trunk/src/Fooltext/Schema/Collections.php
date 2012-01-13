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
 * Liste de collections. Collection d'objets {@link Collection}.
 */
class Collections extends Nodes
{
    protected static $class = 'Fooltext\\Schema\\Collection';

    protected $nextid = 'a';
/*
    protected static $knownProperties = array
    (
        '_lastid' => null,
    );

    protected static $labels = array
    (
        'main' => 'Liste des collections',
    );

    protected static $icons = array
    (
        'image' => 'zone--arrow.png',
    );
*/
}
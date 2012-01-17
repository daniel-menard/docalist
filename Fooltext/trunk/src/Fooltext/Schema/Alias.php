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
 * Un alias au sein d'une collection.
 */
class Alias extends Node
{
    /**
     * Propriétés par défaut d'un alias.
     *
     * @var array
     */
    protected static $defaults = array
    (
        // Identifiant (numéro unique) de l'alias (non utilisé)
    	'_id' => null,

        // Nom de l'alias
        'name' => '',

        // Libellé de l'alias
        'label' => '',

        // Description de l'alias
        'description' => '',

        // Liste des champs présents dans l'alias
        'fields' => array(),
    );
}
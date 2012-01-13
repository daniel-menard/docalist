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
 * Un alias. Collection d'objets {@link AliasIndex}.
 */
class Alias extends Node
{
    protected static $defaults = array
    (
        // Identifiant (numéro unique) de l'alias (non utilisé)
    	'_id' => null,

        // Nom de l'alias
        'name' => '',

        // Libellé de l'index
        'label' => '',

        // Description de l'index
        'description' => '',

    	'widget' => 'textbox', //array('textbox', 'textarea', 'checklist', 'radiolist', 'select'),
    	'datasource' => '', // array('pays','langues','typdocs'),
    );
}
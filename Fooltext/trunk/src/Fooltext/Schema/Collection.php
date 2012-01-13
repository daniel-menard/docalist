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
 * Collection de documents.
 */
class Collection extends Node
{
    /**
     * Propriétés par défaut d'une collection.
     *
     * @var array
     */
    protected static $defaults = array
    (
        // Identifiant (préfixe) de la collection (code unique : a, b, aa, aaa)
		'_id' => null,

        // Nom de la collection
        'name' => '',

        // Un libellé court décrivant la collection
    	'label' => '',

        // Description, notes, historique des modifs...
        'description' => '',

        // Nom de la classe utilisée pour représenter les documents
        // présents dans cette collection. Doit hériter de Fooltext\Document\Document
        'document' => '\\Fooltext\\Document\\Document',

        // Nom du champ utilisé comme numéro unique des documents
        'docid' => '',
    );

    /**
     * Liste des collections de noeuds dont dispose une collection.
     *
     * @var array un tableau de la forme "nom de la propriété" => "classe utilisée".
     */
    protected static $nodes = array
    (
    	'fields' => 'Fooltext\\Schema\\Fields',
    	'aliases' => 'Fooltext\\Schema\\Aliases',
    );
}
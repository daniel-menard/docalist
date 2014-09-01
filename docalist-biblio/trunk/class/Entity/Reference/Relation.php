<?php
/**
 * This file is part of the 'Docalist Biblio' plugin.
 *
 * Copyright (C) 2012-2014 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Biblio
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     $Id$
 */
namespace Docalist\Biblio\Entity\Reference;

use Docalist\Biblio\Type\Object;

/**
 * Relation
 *
 * @property String $type
 * @property Integer[] $ref
 */
class Relation extends Object {
    static protected function loadSchema() {
        // @formatter:off
        return [
            'fields' => [
                'type' => [
                    'label' => __('Type', 'docalist-biblio'),
                    'description' => __('Type de relation', 'docalist-biblio'),
                ],
                'ref' => [
                    'type' => 'int*',
                    'label' => __('Notices liées', 'docalist-biblio'),
                    'description' => __('Numéro de référence des notices (Ref)', 'docalist-biblio'),
                ]
            ]
        ];
        // @formatter:on
    }
}
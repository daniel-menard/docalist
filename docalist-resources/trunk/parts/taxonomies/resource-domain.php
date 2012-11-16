<?php
/**
 * This file is part of a "Docalist Resources" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Resources
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Resources;

return array(
    'name' => 'resource_domain',
    'post_type' => array('resource'),
    'configuration' => array(
        'label' => __('Resource domains', 'docapress'),
        'hierarchical' => true,
        'show_ui' => true,
        'query_var' => 'domaine', // addoption
        'rewrite' => true,
        /*
         array
         (
         'slug' => 'ressources/domain',
         'with_front' => false,
         'hierarchical' => true,
         ),
         */
    )
);

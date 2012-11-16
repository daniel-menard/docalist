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
    'name' => 'resource',
    'labels' => array(
        'name' => __('Resources', 'docapress'), //addoption
        'singular_name' => __('New resource', 'docapress'), // dans le menu
        // nouveau de la admin bar
        'add_new' => __('New resource', 'docapress'),
        'all_items' => __('All resources', 'docapress'),
        'add_new_item' => __('New resource', 'docapress'),
        'edit_item' => __('Edit resource', 'docapress'),
        'new_item' => __('Add new resource', 'docapress'),
        'view_item' => __('View resource', 'docapress'), // dans admin bar
        'search_items' => __('Search resource', 'docapress'),
        'not_found' => __('No resources found.', 'docapress'),
        'not_found_in_trash' => __('No resources found in trash.', 'docapress'),
    ),
    'public' => true,
    'rewrite' => array(
        'slug' => 'ressources', // addoption
        'with_front' => false,
    ),
    'capability_type' => 'post',
    'supports' => array(
        'title',
        'editor',
        'thumbnail'
    ),
    //    'taxonomies' => array('post_tag'),
    'has_archive' => true,
    'edit_columns' => array(
        'title' => __('Order Number'),
        'author' => __('customer'),
        'type' => 'Type',
    )
);

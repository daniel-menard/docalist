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
        'name' => $this->option('Resources.menu'),
        'singular_name' => $this->option('Resources.name'),
        'add_new' => $this->option('Resources.new'),
        'add_new_item' => $this->option('Resources.new'),
        'edit_item' => $this->option('Resources.edit'),
        'new_item' => $this->option('Resources.new'),
        'view_item' => $this->option('Resources.view'),
        'search_items' => $this->option('Resources.search'),
        'not_found' => $this->option('Resources.notfound'),
        'not_found_in_trash' => $this->option('Resources.notfound'),
        'all_items' => $this->option('Resources.all'),
        'menu_name' => $this->option('Resources.menu'),
        'name_admin_bar' => $this->option('Resources.name'),
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
/*    
    'edit_columns' => array(
        'title' => __('Order Number'),
        'author' => __('customer'),
        'type' => 'Type',
    )
*/ 
);

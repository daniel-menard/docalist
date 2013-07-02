<?php
/**
 * This file is part of a "Docalist Biblio" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Biblio
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Biblio\Taxonomy;
use Docalist\AbstractTaxonomy;

/**
 * Taxonomie "Supports de documents".
 */
class RefMedia extends AbstractTaxonomy {
    /**
     * @inheritdoc
     */
    protected $id = 'dclrefmedia';

    /**
     * @inheritdoc
     */
    protected $postTypes = array('dclref');

    /**
     * @inheritdoc
     */
    protected function options() {
        return array(
            'label' => __('Supports', 'docalist-biblio'),
            'hierarchical' => false,
            'show_ui' => false,
            'query_var' => true,
            'rewrite' => false,
        );
    }

}

<?php
/**
 * This file is part of a "Docalist Resources" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Setting:     docalist-options
 * Tab:         Resources
 * Title:       Labels
 *
 * @package     Docalist
 * @subpackage  Resources
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Resources;
use Docalist;

$plugin = Docalist::plugin('Resources');

_e('<p>Utilisez les options ci-dessous pour modifier les libellés utilisés pour désigner la base et son contenu.</p>', 'docalist-resources');


$labels = array(
    'menu' => array(
        __('Nom de la base', 'docalist-resources'),
        __('Libellé du menu principal.', 'docalist-resources')
    ),
    'name' => array(
        __('Nom au singulier', 'docalist-resources'),
        __('Option "nouveau" dans l\'admin bar de Wordpress.', 'docalist-resources')
    ),
    'all' => array(
        __('Tous les enregistrements', 'docalist-resources'),
        __('Première option du menu.', 'docalist-resources')
    ),
    'new' => array(
        __('Créer un enregistrement', 'docalist-resources'),
        __('Seconde option du menu.', 'docalist-resources')
    ),
    'edit' => array(
        __('Modifier', 'docalist-resources'),
        __('Utilisé à divers endroits.', 'docalist-resources')
    ),
    'view' => array(
        __('Afficher', 'docalist-resources'),
        __('Utilisé à divers endroits.', 'docalist-resources')
    ),
    'search' => array(
        __('Rechercher', 'docalist-resources'),
        __('Libellé du bouton rechercher.', 'docalist-resources')
    ),
    'notfound' => array(
        __('Aucune réponse trouvée', 'docalist-resources'),
        __('En cas de recherche infructueuse.', 'docalist-resources')
    ),
);

foreach ($labels as $name => $desc) {
    $fullName = 'Resources.' . $name;
    piklist('field', array(
        'type' => 'text',
        'field' => $fullName,
        'label' => isset($desc[0]) ? $desc[0] : ucfirst($name),
        'description' => isset($desc[1]) ? $desc[1] : '',
        'value' => $plugin->defaultOption($fullName),
        'attributes' => array('class' => 'regular-text'),
        'required' => true, // @todo : ne fonctionne pas avec le piklist actuel
    ));
}

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
use Docalist\Core\AbstractPlugin;

/**
 * Plugin de gestion d'un annuaire de ressources.
 */
class Plugin extends AbstractPlugin {
    /**
     * @inheritdoc
     */
    protected function defaultOptions() {
        return array(
            'Resources.menu' => __('Ressources', 'docalist-resources'),
            'Resources.name' => __('Ressource', 'docalist-resources'),
            'Resources.all' => __('Liste des ressources', 'docalist-resources'),
            'Resources.new' => __('Créer une ressource', 'docalist-resources'),
            'Resources.edit' => __('Modifier', 'docalist-resources'),
            'Resources.view' => __('Afficher', 'docalist-resources'),
            'Resources.search' => __('Rechercher', 'docalist-resources'),
            'Resources.notfound' => __('Aucune réponse trouvée.', 'docalist-resources'),
        );
    }


}

<?php
/**
 * This file is part of a "Docalist Resources" plugin.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 * 
 * Plugin Name: Docalist Resources
 * Plugin URI:  http://docalist.org
 * Plugin Type: Piklist
 * Description: Docalist : gestion d'un annuaire de ressources.
 * Version:     0.1
 * Author:      Docalist
 * Author URI:  http://docalist.org
 * 
 * @package     Docalist
 * @subpackage  Resources
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Resources;
use Docalist\PluginManager; 

if (class_exists('Docalist\\PluginManager')) {
    PluginManager::load('Docalist\Resources\Plugin', __DIR__);
}
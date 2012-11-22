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
 * Description: Docalist: resources directory management.
 * Version:     0.1
 * Author:      Daniel Ménard
 * Author URI:  http://docalist.org/
 * Text Domain: docalist-resources
 * Domain Path: /languages
 *  
 * @package     Docalist
 * @subpackage  Resources
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Resources;
use Docalist;

if (class_exists('Docalist')) {
    // Enregistre notre espace de noms
    Docalist::registerNamespace(__NAMESPACE__, __DIR__ . '/class');
    
    // Charge le plugin
    Docalist::load('Docalist\\Resources\\Plugin', __FILE__);
}

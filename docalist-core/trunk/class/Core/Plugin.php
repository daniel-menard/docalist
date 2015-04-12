<?php
/**
 * This file is part of the "Docalist Core" plugin.
 *
 * Copyright (C) 2012-2015 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Core
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id$
 */

namespace Docalist\Core;

use Docalist\LogManager;
use Docalist\Views;
use Docalist\Cache\FileCache;
use Docalist\Table\TableManager;
use Docalist\Table\TableInfo;
use Docalist\Sequences;
use Docalist\Repository\SettingsRepository;
use InvalidArgumentException;
use Docalist\AdminNotices;

/**
 * Plugin Docalist-Core.
 */
class Plugin {
    /**
     * Initialise le plugin.
     */
    public function __construct() {
        // Charge les fichiers de traduction du plugin
        load_plugin_textdomain('docalist-core', false, 'docalist-core/languages');

        // Définit le path des répertoires docalist (tables, logs, etc.)
        $this->setupPaths();

        // Enregistre les services docalist de base
        $this->setupServices();

        // Charge la configuration de docalist-core
        docalist('services')->add('docalist-core-settings', function() {
            return new Settings(docalist('settings-repository'));
            // TODO : à supprimer, n'est utilisé que pour les tables personnalisées
        });

        // Définit les actions et les filtres par défaut
        $this->setupHooks();

        // Gestion des admin notices - à revoir, pas içi
//         add_action('admin_notices', function(){
//             $this->showAdminNotices();
//         });
    }

    /**
     * Définit les path docalist par défaut (racine du site, répertoire des
     * données, des logs, des tables, etc.)
     *
     * @return self
     */
    protected function setupPaths() {
        docalist('services')->add([
            // Répertoire racine du site (/)
            'root-dir' => function() {
                return $this->rootDirectory();
            },

            // Répertoire de base (WP_CONTENT_DIR/data)
            'data-dir' => function() {
                return $this->dataDirectory();
            },

            // Répertoire de config (WP_CONTENT_DIR/data/config)
            'config-dir' => function() {
                return $this->dataDirectory('config');
            },

            // Répertoire de config (WP_CONTENT_DIR/data/config)
            'cache-dir' => function() {
                return $this->cacheDirectory();
            },

            // Répertoire des logs (WP_CONTENT_DIR/data/log)
            'log-dir' => function() {
                return $this->dataDirectory('log');
            },

            // Répertoire des tables (WP_CONTENT_DIR/data/tables)
            'tables-dir' => function() {
                return $this->dataDirectory('tables');
            },
        ]);

        return $this;
    }

    /**
     * Enregistre les services docalist de base (gestionnaire de vues,
     * gestionnaire de cache, gestionnaire de tables, etc.)
     *
     * @return self
     */
    protected function setupServices() {
        // Enregistre les services docalist par défaut
        docalist('services')->add([

            // Gestion des Settings
            'settings-repository' => function() {
                return new SettingsRepository();
            },

            // Gestion des logs
            'logs' => function() {
                return new LogManager();
            },

            // Gestion des vues
            'views' => function() {
                return new Views();
            },

            // Gestion du cache
            'file-cache' => function() {
                return new FileCache(docalist('root-dir'), docalist('cache-dir'));
            },

            // Gestion des tables
            'table-manager' => function() {
                return new TableManager(docalist('docalist-core-settings')); // TODO: BAD
            },

            // Gestion des admin-notices
            'admin-notices' => function() {
                return new AdminNotices();
            },

            // Gestion des séquences
            'sequences' => function() {
                return new Sequences();
            },

            // Gestion des lookups
            'lookup' => function() {
                return new Lookup();
            },
        ]);

        return $this;
    }

    /**
     * Définit les actions et les filtres par défaut de docalist.
     *
     * @return self
     */
    protected function setupHooks() {
        // Définit les lookups de type "table"
        add_filter('docalist_table_lookup', function($value, $source, $search) {
            return docalist('table-manager')->lookup($source, $search, false);
        }, 10, 3);

        // Définit les lookups de type "thesaurus"
        add_filter('docalist_thesaurus_lookup', function($value, $source, $search) {
            return docalist('table-manager')->lookup($source, $search, true);
        }, 10, 3);

        // Crée l'action ajax "docalist-lookup"
        add_action('wp_ajax_docalist-lookup', $ajaxLookup = function() {
            docalist('lookup')->ajaxLookup();
        });
        add_action('wp_ajax_nopriv_docalist-lookup', $ajaxLookup);

        // Déclare les tables docalist prédéfinies
        add_action('docalist_register_tables', array($this, 'registerTables'));

        // Déclare les JS et les CSS inclus dans docalist-core
        add_action('init', function() {
            $this->registerAssets();
        });

        // Crée la page "Gestion des tables d'autorité" dans le back-office
        add_action('admin_menu', function () {
            new AdminTables();
        });

        return $this;
    }

    /**
     * Retourne le path de la racine du site : soit le répertoire de WordPress
     * (si WordPress est installé à la racine de façon classique), soit le
     * répertoire au-dessus (si WordPress est installé dans un sous-répertoire).
     *
     * Remarque : WordPress n'offre aucun moyen simple d'obtenir la racine du
     * site :
     * - ABSPATH ne fonctionne pas si WordPress est dans un sous-répertoire.
     * - get_home_path() ne fonctionne que dans le back-office et n'est pas
     *   disponible en mode cli car SCRIPT_FILENAME n'est pas utilisable.
     *
     * @throws InvalidArgumentException
     *
     * @return string
     */
    protected function rootDirectory() {
        // Version 1, basée sur SCRIPT_FILENAME : pas dispo en mode cli
        // if (PHP_SAPI === 'cli' || PHP_SAPI === 'cli-server') {
        //     throw new \Exception('root-dir is not available with CLI SAPI');
        // }
        // $root = substr($_SERVER['SCRIPT_FILENAME'], 0, -strlen($_SERVER['PHP_SELF']));

        // $root = strtr($root, '/\\', DIRECTORY_SEPARATOR);
        // $root = rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        // Version 2, basée sur l'emplacement de wp-config.php
        // Adapté de wordpress/wp-load.php
        $root = rtrim(ABSPATH, '/\\'); // ABSPATH contient un slash final
        if (!file_exists($root . '/wp-config.php') ) {
            $root = dirname($root);
            if (! file_exists($root . '/wp-config.php' ) || file_exists($root . '/wp-settings.php')) {
                throw new InvalidArgumentException('Unable to find root dir');
            }
        }

        return $root;
    }

    /**
     * Retourne le path du répertoire "data" de docalist, c'est-à-dire le
     * répertoire qui contient toutes les données docalist (tables, config,
     * logs, user-data, etc.)
     *
     * Par défaut, il s'agit du répertoire "docalist-data" situé dans le
     * répertoire uploads de WordPress.
     *
     * Si un sous-répertoire est fourni en paramètre, la fonction crée le
     * répertoire s'il n'existe pas déjà et retourne son path absolu.
     *
     * Les répertoires créés par cette fonction sont protégés avec un fichier
     * index.php et un fichier .htaccess.
     *
     * @param string $subdir Optionnel, sous-répertoire.
     *
     * @return string Le path absolu du répertoire demandé.
     */
    public function dataDirectory($subdir = null) {
     // $directory = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'docalist-data';
        $directory = wp_upload_dir();
        $directory = $directory['basedir'];
        $directory = strtr($directory, '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
        $directory .= DIRECTORY_SEPARATOR . 'docalist-data';

        ! is_dir($directory) && $this->createProtectedDirectory($directory);

        if ($subdir) {
            if (!ctype_alpha($subdir)) {
                throw new InvalidArgumentException("Bad data directory name: '$subdir'");
            }
            $directory .= DIRECTORY_SEPARATOR . $subdir;
            ! is_dir($directory) && $this->createProtectedDirectory($directory);
        }

        return $directory;
    }

    /**
     * Retourne le path du répertoire "cache" de docalist.
     *
     * Par défaut, il s'agit du répertoire "docalist-data" situé dans le
     * répertoire temporaire de WordPress.
     *
     * Le répertoires cache créé est protégé avec un fichier index.php et un
     * fichier .htaccess (au cas où celui-ci se trouve dasn l'arborescence
     * publique du site).
     *
     * @return string
     */
    protected function cacheDirectory() {
        $directory = get_temp_dir() . 'docalist-cache'; // get_temp_dir : slash à la fin
        $directory = strtr($directory, '/\\', DIRECTORY_SEPARATOR . DIRECTORY_SEPARATOR);
        ! is_dir($directory) && $this->createProtectedDirectory($directory);

        return $directory;
    }

    /**
     * Crée un répertoire "protégé".
     *
     * Crée le répertoire demandé puis appelle protectDirectory().
     *
     * Important : vous devez vous assurer que le répertoire n'exite pas avant
     * d'appeler cette fonction.
     *
     * @param string $directory Le path absolu du répertoire à créer.
     *
     * @throws InvalidArgumentException Si le répertoire ne peut pas être créé.
     *
     * @return self
     */
    public function createProtectedDirectory($directory) {
        if (! @mkdir($directory, 0700, true)) {
            throw new InvalidArgumentException('Unable to create ' . basename($directory) . ' directory');
        }

        $path = $directory . '/index.php';
        file_put_contents($path, '<?php // Silence is golden.');

        $path = $directory . '/.htaccess';
        file_put_contents($path, 'Deny from all');

        return $this;
    }

    /**
     * Protège un répertoire en créant un fichier index.php ("Silence is
     * golden") et un fichier .htaccess ("Deny From All").
     *
     * Important :
     * - vous devez vous assurer que le répertoire à protéger existe avant
     *   d'appeler cette fonction.
     * - si les fichiers index.php et .htaccess existent déjà, ils sont écrasés.
     *
     * @param string $directory Le path absolu du répertoire à protéger.
     *
     * @return self
     */
    public function protectDirectory($directory) {
        $path = $directory . '/index.php';
        file_put_contents($path, '<?php // Silence is golden.');

        $path = $directory . '/.htaccess';
        file_put_contents($path, 'Deny from all');

        return $this;
    }

    /**
     * Déclare les scripts et styles standard de docalist-core.
     *
     * @return self
     */
    protected function registerAssets() {
        $js = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? 'js' : 'min.js';

        $url = plugins_url('docalist-core');

        // Bootstrap
        wp_register_style(
            'bootstrap',
            '//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.0/css/bootstrap-combined.min.css',
            [],
            '2.3.0'
        );

        // Selectize
        wp_register_script(
            'selectize',
            "$url/lib/selectize/js/standalone/selectize.$js",
            ['jquery'],
            '0.8.5',
            false // TODO: Passer à true (position top)
        );

        wp_register_style(
            'selectize',
            "$url/lib/selectize/css/selectize.default.css",
            [],
            '0.8.5'
        );

        // Todo : handsontable

        // docalist-forms
        wp_register_script(
            'docalist-forms',
            "$url/views/forms/docalist-forms.js", // TODO: version min.js
            ['jquery','jquery-ui-sortable','selectize'],
            '140927',
            false // TODO: Passer à true (position top)
        );

        // Thème par défaut des formulaires
        wp_register_style(
            'docalist-forms-default',
            "$url/views/forms/default/default.css",
            ['wp-admin'],
            '140318'
        );

        // Thème bootstrap des formulaires
        wp_register_style(
            'docalist-forms-bootstrap',
            "$url/views/forms/bootstrap/bootstrap-theme.css",
            ['bootstrap'],
            '140318'
        );

        // Thème wordpress des formulaires
        wp_register_style(
            'docalist-forms-wordpress',
            "$url/views/forms/wordpress/wordpress-theme.css",
            ['wp-admin'],
            '140927'
        );

        return $this;
    }

    /**
     * Affiche les admin-notices qui ont été enregistrés
     * (cf AbstractPlugin::adminNotice).
     */
/*
    protected function showAdminNotices() {
        // Adapté de : http://www.dimgoto.com/non-classe/wordpress-admin_notice/
        if (false === $notices = get_transient(self::ADMIN_NOTICE_TRANSIENT)) {
            return;
        }

        foreach($notices as $notice) {
            list($message, $isError) = $notice;
            printf(
                '<div class="%s"><p>%s</p></div>',
                $isError ? 'error' : 'updated',
                $message
            );
        }

        delete_transient(self::ADMIN_NOTICE_TRANSIENT);
    }
*/
    /**
     * Enregistre les tables prédéfinies.
     *
     * @param TableManager $tableManager
     *
     * @return self
     */
    public function registerTables(TableManager $tableManager) {
        $dir = dirname(dirname(__DIR__)) . DIRECTORY_SEPARATOR . 'tables'  . DIRECTORY_SEPARATOR;

        // Tables des langues complète
        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_fr',
            'path' => $dir . 'languages/ISO-639-2_alpha3_fr.txt',
            'label' => __('Liste complète des codes langues 3 lettres en français (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_en',
            'path' => $dir . 'languages/ISO-639-2_alpha3_en.txt',
            'label' => __('Liste complète des codes langues 3 lettres en anglais (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
        ]));

        // Tables des langues simplifiées (langues officielles de l'union européenne)
        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_EU_fr',
            'path' => $dir . 'languages/ISO-639-2_alpha3_EU_fr.txt',
            'label' => __('Codes 3 lettres en français des langues officielles de l\'Union Européenne (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha3_EU_en',
            'path' => $dir . 'languages/ISO-639-2_alpha3_EU_en.txt',
            'label' => __('Codes 3 lettres en anglais des langues officielles de l\'Union Européenne (ISO-639-2)', 'docalist-core'),
            'format' => 'table',
            'type' => 'languages',
        ]));

        // Tables de conversion des codes langues
        $tableManager->register(new TableInfo([
            'name' => 'ISO-639-2_alpha2-to-alpha3',
            'path' => $dir . 'languages/ISO-639-2_alpha2-to-alpha3.txt',
            'label' => __('Table de conversion "alpha2 -> alpha3" pour les codes langues (ISO-639-2)', 'docalist-core'),
            'format' => 'conversion',
            'type' => 'languages',
        ]));

        // Tables des pays
        $tableManager->register(new TableInfo([
            'name' => 'ISO-3166-1_alpha2_fr',
            'path' => $dir . 'countries/ISO-3166-1_alpha2_fr.txt',
            'label' => __('Codes pays 2 lettres en français (ISO-3166-1)', 'docalist-core'),
            'format' => 'table',
            'type' => 'countries',
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-3166-1_alpha2_EN',
            'path' => $dir . 'countries/ISO-3166-1_alpha2_en.txt',
            'label' => __('Codes pays 2 lettres en anglais (ISO-3166-1)', 'docalist-core'),
            'format' => 'table',
            'type' => 'countries',
        ]));

        $tableManager->register(new TableInfo([
            'name' => 'ISO-3166-1_alpha3-to-alpha2',
            'path' => $dir . 'countries/ISO-3166-1_alpha3-to-alpha2.txt',
            'label' => __('Table de conversion "alpha3 -> alpha2" pour les codes pays (ISO-3166-1)', 'docalist-core'),
            'format' => 'conversion',
            'type' => 'countries',
        ]));

        return $this;
    }
}
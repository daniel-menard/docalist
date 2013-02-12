<?php
/**
 * This file is part of the "Docalist Forms" package.
 *
 * Copyright (C) 2012,2013 Daniel Ménard
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Docalist
 * @subpackage  Forms
 * @author      Daniel Ménard <daniel.menard@laposte.net>
 * @version     SVN: $Id: Field.php 397 2013-02-11 15:30:06Z
 * daniel.menard.35@gmail.com $
 */

namespace Docalist\Forms;

use ArrayAccess, Exception, XmlWriter;

/**
 * Un champ de formulaire.
 *
 * Un champ est un élément de formulaire. Il dispose d'attributs et il a un nom
 * et une valeur. Il peut également avoir un libellé et une description.
 */
abstract class Field {
    /**
     * @var XMLWriter Le générateur XML utilisé pour générer le code html
     * du formulaire.
     */
    protected static $writer;

    /**
     * @var array Liste des ID déjà utilisés pour le rendu en cours.
     */
    protected static $usedId;

    /**
     * @var Fields Le bloc parent de cet élément.
     */
    protected $parent;

    /**
     * @var array Attributs de l'élément.
     */
    protected $attributes = array();

    /**
     * @var string Nom de l'élément.
     */
    protected $name;

    /**
     * @var string Libellé associé à l'élément.
     */
    protected $label;

    /**
     * @var string Description de l'élément.
     */
    protected $description;

    /**
     * @var Position de la description.
     *
     * Par défaut, le bloc description est affiché avant le champ.
     * Lorsque cette propriété est à false, ell est affichée après.
     */
    protected $descriptionAfter = false;

    /**
     * @var mixed Les données du champ.
     */
    protected $data;

    /**
     * @var bool Indique si le champ est répétable.
     */
    protected $repeatable = false;

    /**
     * Crée un nouveau champ.
     *
     * @param string $name Le nom du champ.
     */
    public function __construct($name) {
        $this->name = $name;
    }

    /**
     * Retourne le type du champ.
     *
     * Par convention, le type du champ correspond à la version en minuscules
     * du dernier élément du nom de classe.
     *
     * Par exemple, le type des éléments {@link Input} est "input".
     *
     * @return string
     */
    public final function type() {
        return strtolower(substr(strrchr(get_class($this), '\\'), 1));
    }

    /**
     * Retourne le parent de ce champ ou null s'il n'a pas encore été ajouté
     * dans une {@link Fields liste de champs}.
     *
     * @return Fields
     */
    public function parent() {
        return $this->parent;
    }

    /**
     * Retourne l'élément racine de la hiérarchie, c'est-à-dire l'élément de
     * plus haut niveau qui contient ce champ ou null s'il n'a pas encore été
     * ajouté dans une {@link Fields liste de champs}.
     *
     * @return Fields
     */
    public function root() {
        return $this->parent ? $this->parent->root() : $this;
    }

    /**
     * Retourne la profondeur du champ, c'est-à-dire le niveau auquel il
     * se trouve dans la hiérarchie.
     *
     * L'élément de plus haut niveau à une profondeur de 0, ses enfants une
     * profondeur de 1 et ainsi de suite.
     *
     * @return int
     */
    public function depth() {
        return $this->parent ? 1 + $this->parent->depth() : 0;
    }

    /**
     * Retourne ou modifie les attributs du champ.
     *
     * @param array $attributes Un tableau de la forme nom de l'attribut =>
     * contenu de l'attribut.
     *
     * @return array|$this
     */
    public function attributes($attributes = null) {
        if (is_null($attributes))
            return $this->attributes;

        // @todo : tester si on a des attributs booléen dans la liste
        $this->attributes = $attributes;

        return $this;
    }

    /**
     * Retourne ou modifie la valeur d'un attribut.
     *
     * @param string $name le nom de l'attribut.
     * @param string $value la valeur de l'attribut
     *
     * @return string|$this
     */
    public function attribute($name, $value = null) {
        if (is_null($value)) {
            if (isset($this->attributes[$name])) {
                return $this->attributes[$name];
            } else {
                return null;
            }
        }

        // @todo : tester si c'est un attribut booléen
        $this->attributes[$name] = $value;

        return $this;
    }

    /**
     * Ajoute une ou plusieurs classes à l'attribut class du champ.
     *
     * Chacune des classes indiquées n'est ajoutée à l'attribut que si elle
     * n'y figure pas déjà. Les noms de classes sont sensibles à la casse.
     *
     * @param string $class La classe à ajouter. Vous pouvez également ajouter
     * plusieurs classes en séparant leurs noms par un espace.
     *
     * Exemple $input->addClass('text small');
     *
     * @return $this
     */
    public function addClass($class) {
        if (!isset($this->attributes['class']) || empty($this->attributes['class'])) {
            $this->attributes['class'] = $class;
        } else {
            foreach (explode(' ', $class) as $class) {
                $pos = strpos(' ' . $this->attributes['class'] . ' ', " $class ");
                if ($pos === false) {
                    $this->attributes['class'] .= " $class";
                }
            }
        }

        return $this;
    }

    /**
     * Supprime une ou plusieurs classes de l'attribut class du champ.
     *
     * @param string $class La classe à supprimer. Vous pouvez également enlever
     * plusieurs classes en séparant leurs noms par un espace.
     *
     * Exemple $input->removeClass('text small');
     *
     * @return $this
     */
    public function removeClass($class) {
        if (isset($this->attributes['class'])) {
            foreach (explode(' ', $class) as $class) {
                $pos = strpos(' ' . $this->attributes['class'] . ' ', " $class ");
                if ($pos !== false) {
                    $len = strlen($class);
                    if ($pos > 0 && ' ' === $this->attributes['class'][$pos - 1]) {
                        --$pos;
                        ++$len;
                    }
                    $this->attributes['class'] = trim(substr_replace($this->attributes['class'], '', $pos, $len));
                    if (empty($this->attributes['class'])) {
                        unset($this->attributes['class']);
                        break;
                    }
                }
            }
        }

        return $this;
    }

    /**
     * Indique si l'attribut class du champ contient l'une des classes indiquées.
     *
     * @param string $class La classe à tester. Vous pouvez également tester
     * plusieurs classes en séparant leurs noms par un espace.
     *
     * Exemple $input->removeClass('text small');
     *
     * Retournera true si l'atttribut class contient la classe 'text' OU la
     * classe 'small'.
     *
     * @return $this
     */
    public function hasClass($class) {
        if (isset($this->attributes['class'])) {
            foreach (explode(' ', $class) as $class) {
                if (false !== strpos(' ' . $this->attributes['class'] . ' ', " $class ")) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Retourne ou modifie le nom du champ.
     *
     * @param string $name
     *
     * @return string|$this
     */
    public function name($name = null) {
        if (is_null($name))
            return $this->name;

        $this->name = $name;

        return $this;
    }

    /**
     * @var int
     */
    protected $occurence = 0;

    protected function occurence($occurence = null) {
        if (is_null($occurence))
            return $this->occurence;

        $this->occurence = $occurence;

        return $this;
    }

    protected function controlName() {
        // Autre chose qu'un champ (i.e. pas de nom)
        if (!$this->name)
            return '';

        // Champ de base
        if (is_null($this->parent) || !$this->parent->name) {
            $name = $this->name;
        }

        // Sous champ
        else {
            $name = $this->parent->controlName() . '[' . $this->name . ']';
        }

        if ($this->repeatable)
            $name .= '[' . $this->occurence . ']';

        return $name;
    }

    /**
     * Retourne ou modifie le libellé du champ.
     *
     * @param string $label
     *
     * @return string|$this
     */
    public function label($label = null) {
        if (is_null($label))
            return $this->label;

        $this->label = $label;

        return $this;
    }

    /**
     * Retourne ou modifie la description du champ.
     *
     * @param string $description
     *
     * @param null|bool Emplacement de la description par rapport au champ :
     * - false : en haut (valeur par défaut)
     * - true : en bas
     *
     * @return string|$this
     */
    public function description($description = null, $after = null) {
        if (is_null($description))
            return $this->description;

        $this->description = $description;
        if (! is_null($after)) {
            $this->descriptionAfter = $after;
        }

        return $this;
    }

    /**
     * Retourne ou modifie les données du champ.
     *
     * @param mixed $data
     *
     * @return mixed|$this
     */
    public function data($data = null) {
        if (is_null($data))
            return $this->data;

        $this->data = $data;

        return $this;
    }

    /**
     * Retourne ou modifie l'attribut repeatable du champ.
     *
     * @param bool $repeatable
     *
     * @return bool|$this
     */
    public function repeatable($repeatable = null) {
        if (is_null($repeatable))
            return $this->repeatable;

        $this->repeatable = $repeatable;

        return $this;
    }

    /**
     * Initialise les données du champ à partir du tableau (ou de l'objet passé
     * en paramètre).
     *
     * @param array|ArrayAccess|Object|Scalar $data
     */
    public function bind($data) {
        if (is_array($data) || $data instanceof ArrayAccess) {
            if (isset($data[$this->name])) {
                $this->data = $data[$this->name];
            }

            return;
        }

        if (is_object($data)) {
            $name = $this->name;
            // le formatter d'aptana buggue si on utilise $data{$this->name}
            if (isset($data->$name)) {
                $this->data = $data->$name;
            }

            return;
        }

        if (is_scalar($data)) {
            $this->data = $data;
        }

        // else : ben c'est quoi ?

        return $this;
    }

    protected function bindOccurence($data) {
        return $this->bind($data);
    }

    /**
     * Génère le rendu du champ en utilisant le thème indiqué.
     *
     * @param string $theme Le nom du thème à utiliser pour faire le rendu
     * de l'élément.
     *
     * @param string $template Optionnel, le nom du template à appeller.
     *
     * @param array|null $args Paramètres optionnels utilisés pour le rendu du
     * formulaire ou bien transmis aux templates utilisés pour la génération.
     *
     * Actuellement, la seule option reconnue est la clé 'option'.
     *
     * Les options disponibles sont :
     *
     * - indent : chaine utilisée pour l'indentation du code html généré.
     * Par défaut, le code html n'est pas indenté. Vous pouvez indiquer
     * soit true (indenter de quatre espaces), soit un entier (indenter de x
     * espaces) soit une chaine d'indentation personnalisée ("\t", "  ", etc.)
     *
     * - charset : jeu de caractères à utiliser pour la génération du code html.
     * Par défaut, le formulaire est généré en UTF-8. Vous pouvez indiquer un
     * charset différent dans cette option. Remarque : le charset indiqué doit
     * être supporté par XMLWriter, consultez la doc.
     *
     * - comment : ajoute le nom des templates utilisés pour le rendu sous
     * forme de commentaires insérés dans le code html généré.
     *
     * @param bool $inherit Optionnel True pour exécuter le template de la
     * classe parent au lieu du template de la classe en cours.
     */
    public function render($theme = 'default', $template = 'container', $args = null, $inherit = false) {
        // Premier appel : crée l'objet xmlwriter
        if (is_null(self::$writer)) {

            // Crée le XMLWriter
            self::$writer = new XMLWriter();
            self::$writer->openURI('php://output');
            // Faire une option ?
            $createdWriter = true;

            // Gère les options indiquées
            if (isset($args['options'])) {
                $options = $args['options'];

                // Option indent : indentation du code
                if (isset($options['indent']) && $options['indent']) {
                    //Le test ci-dessus ignore false, 0, '' (pas d'indentation)
                    $indent = $options['indent'];

                    // true = 4 espaces par défaut
                    if ($indent === true) {
                        $indent = '    ';

                        // entier : n espaces
                    } elseif (is_int($indent)) {
                        $indent = str_repeat(' ', $indent);
                    }

                    // sinon : chaine litérale (tabulation, deux espaces, etc.)

                    // Demande au xmlwriter de nous indenter le code
                    self::$writer->setIndent(true);
                    self::$writer->setIndentString($indent);

                }

                // Option 'charset' : jeu de caractère utilisé
                $charset = isset($options['charset']) ? $options['charset'] : 'UTF-8';

                // Pour que XMLWriter nous génère de l'utf-8, il faut
                // obligatoirement appeller startDocument() et indiquer
                // l'encoding. Dans le cas contraire, xmlwriter génère des
                // entités numériques (par exemple "M&#xE9;nard").
                // Par contre, on ne veut pas que le prologue xml (<?xml ..>)
                // apparaisse dans la sortie générée. Donc on bufferise
                // l'écriture du prologue et on l'ignore.
                ob_start();
                self::$writer->startDocument('1.0', $charset);
                self::$writer->flush();
                ob_end_clean();

                // Supprime les variables dont on n'a plus besoin pour qu'on
                // n'ait dans les templates que les variables documentées.
                // Vide également $args si on n'avait que options car on n'en
                // aura plus besoin pour le rendu et ça évite de faire un
                // extract à chaque exécution de template.
                unset($indent, $charset);
                unset($options['indent'], $options['charset']);
                if (empty($options)) {
                    unset($args['options']);
                    if (empty($args)) {
                        $args = null;
                    }
                }
                unset($options);
            }

			// Débogage : vérifie que les templates transmettent bien $args aux sous-templates
			$args['dmdm'] = 1;
        }

        // Débogage - Vérifie que $args est correctement transmis
        if (! isset($args['dmdm'])) {
            $t = debug_backtrace();
            $file = $t[0]['file'];
            $file = basename(dirname($file)) . '/' . basename($file);
            $line = $t[0]['line'];
            echo "\nARGUMENTS NON TRANSMIS : $file ligne $line\n";
        }

        // On commence soit avec la classe de l'objet, soit celle de son parent
        $class = $inherit ? get_parent_class($this) : get_class($this);

        // On remonte la hiérarchie des classes jusqu'à ce qu'on trouve $template
        do {
            // Détermine le nom du template recherché
            $file = strtolower(substr(strrchr($class, '\\'), 1)) . ".$template.php";

            // Détermine son path dans le thème (ou dans les thèmes parents)
            $path = Themes::search($theme, $file);

            // On a trouvé, on exécute le template
            if ($path !== false) {

                // Les variables accessibles depuis un template sont :
                // - $this : l'objet Field en cours de rendu
                // - $theme : le nom du thème en cours
                // - $template : le nom du template en cours
                // - $path : son path complet
                // - $args : les paramètres du template
                // - $writer : l'ojet XMLWriter utilisé pour générer le html
                // - les varaibles définies dans le tableau $args.
                //
                // On a aussi $createdWriter, mais on est obligé de la garder.
                unset($inherit, $class, $file);
                $writer = self::$writer;
                $args && extract($args, EXTR_SKIP);

                // Exécute le template et ajoute en commentaire le nom des templates
                if (isset($args['options']['comment']) && $args['options']['comment'] && $template !== 'attributes') {
                    $templateFriendlyName = basename(dirname($path)) . '-' . basename($path);
                    self::$writer->writeComment(' start ' . $templateFriendlyName);

                    include $path;
                    self::$writer->writeComment(' end  ' . $templateFriendlyName);
                }

                // Exécute le template normallement
                else {
                    include $path;
                }

                // Si c'est nous qui avons créé le writer, on le ferme
                if (isset($createdWriter)) {
                    self::$writer->endDocument();
                    self::$writer->flush();
                    self::$writer = null;
                }

                // Terminé
                return;
            }

            // Continue à remonter la hiérarchie
            $class = get_parent_class($class);
        } while($class);

        // Le template demandé n'existe pas dans ce thème
        $class = $inherit ? get_parent_class($this) : get_class($this);
        $file = strtolower(substr(strrchr($class, '\\'), 1)) . ".$template.php";
        $msg = 'Unable to render template %s with theme %s';
        throw new Exception(sprintf($msg, $file, $theme));
    }

    public function generateId() {
        if (!isset($this->attributes['id'])) {
            $id = $this->controlName() ? : $this->type();
            if (!isset(self::$usedId[$id])) {
                self::$usedId[$id] = 1;
            } else {
                $id .= ++self::$usedId[$id];
            }

            $this->attributes['id'] = $id;
        }
        return $this->attributes['id'];
    }

    /**
     * Affiche le code html d'un attribut html.
     *
     * La méthode se charge de filtrer (escape) correctement le nom et la
     * valeur de l'attribut. Le nom de l'attribut est toujours généré en
     * minuscules.
     *
     * Elle prend également en charge les
     * {@link http://www.w3.org/TR/html5/infrastructure.html#boolean-attribute
     * attributs booléens} tels que selected="selected", checked="checked", etc.
     *
     * La chaine générée est de la forme ' name="valeur"' (avec un espace
     * initial). Si l'attribut n'a pas besoin d'être généré (cas d'un attribut
     * booléen à faux), la méthode n'affiche rien.
     *
     * @param string $name Le nom de l'attribut.
     * @param string $value La valeur de l'attribut.
     */
    public function htmlAttribute($name, $value) {
        // Liste des attributs booléen existants en html 5.
        //
        // cf http://www.w3.org/TR/html5/infrastructure.html#boolean-attribute
        // Cette liste a été constituée "à la main" en recherchant la chaine
        // "boolean attribute" dans la page :
        // http://www.w3.org/TR/html5/index.html.
        static $booleanAttributes = array(
            'async' => true,
            'autofocus' => true,
            'autoplay' => true,
            'checked' => true,
            'controls' => true,
            'default' => true,
            'defer' => true,
            'disabled' => true,
            'formnovalidate' => true,
            'hidden' => true,
            'ismap' => true,
            'loop' => true,
            'multiple' => true,
            'muted' => true,
            'novalidate' => true,
            'open' => true,
            'readonly' => true,
            'required' => true,
            'reversed' => true,
            'scoped' => true,
            'seamless' => true,
            'selected' => true,
            'typemustmatch' => true,
        );

        // Minusculise le nom de l'attribut
        $name = strtolower($name);
        $name = htmlspecialchars($name, ENT_COMPAT, 'UTF-8', true);

        // Attributs booléens
        if (isset($booleanAttributes[$name])) {
            // On ne génère quelque chose que si l'attribut est à vrai
            if ($value === true || 0 === strcasecmp($value, $name) || $value === 'true') {
                echo ' ', $name, '="', $name, '"';
            }

            // l'attribut booléen est à faux, on ne génére rien. On aurait pu
            // aussi choisir de générer name="", mais ce n'est pas très utile.

            // Autres attributs
        } else {
            $value = htmlspecialchars($value, ENT_COMPAT, 'UTF-8', true);

            echo ' ', $name, '="', $value, '"';
        }
    }

    public function toArray($withData = false) {
        $t = array('type' => $this->type());
        foreach ($this as $name => $value) {
            if ($name === 'parent' || $name === 'fields' || $name === 'occurence')
                continue;

            if (is_null($value) || $value === array())
                continue;

            if ($name === 'repeatable' && $value === false)
                continue;

            if ($name === 'data' && !$withData)
                continue;

            $t[$name] = $value;

        }

        if (isset($t['data'])) {
            $data = $t['data'];
            unset($t['data']);
            $t['data'] = $data;
        }

        return $t;
    }

    /**
     * Prépare le formulaire à l'affichage.
     *
     * Cette méthode peut être utilisée pour :
     * - définir les assets dont on va avoir besoin (css, js)
     * - vérifier que le formulaire est valide (pas de boucle, etc.)
     * - déplacer des éléments (par exemple les inputs)
     */
    public function prepare($theme = 'default') {
    }

    /**
     * Retourne les fichiers javascript et css qui sont nécessaires pour
     * afficher et faire fonctionner le formulaire.
     *
     * @return array La méthode retourne un tableau (éventuellement vide)
     * contenant des assets, c'est-à-dire un tableau de tableaux.
     *
     * Chaque asset contient (toujours) les clés suivantes :
     * - type : type de l'asset, soit 'css', doit 'js'.
     * - name : le nom de l'asset (jquery, bootstrap-css, etc.) lorsqu'il
     *   s'agit d'une librairie connue. Dans ce cas, l'appellant doit gérer
     *   lui-même la traduction de ce nom en url.
     * - src  : l'url de l'asset lorsqu'il s'agit d'un asset spécifique.
     * - version : le numéro de version de l'asset
     * - position : soit 'top' (pour les css) soit 'bottom' (pours les js).
     * - media : pour les css, le media auquel celle-ci s'applique (all,
     *   screen etc.)
     */
    public final function assets($theme = 'default') {
        // On va faire un parcourt non récursif de l'arbre en utilisant une
        // pile et en élagant lorsqu'on rencontre un type de champ déjà vu.

        // La liste des assets qu'on va retourner
        $assets = array();

        // Pile contenant les noeuds à visiter
        $stack = array($this);

        // Liste des types de noeud qu'on a déjà vu, pour l'élagage
        $seen = array();

        // Propriétés par défaut
        $defaults = array(
            'css' => array(
                'name' => null,
                'src' => null,
                'version' => null,
                'position' => 'top',
                'media' => 'all',
                'condition' => null,
            ),
            'js' => array(
                'name' => null,
                'src' => null,
                'version' => null,
                'position' => 'bottom',
                'condition' => null,
            )
        );

        // Commence avec les assets définis par le thème
        if ($theme) {
            foreach ((array) Themes::assets($theme) as $asset) {
                $asset += $defaults[$asset['type']];
                $key = $asset['name'] ? : $asset['src'];

                $assets[$key] = $asset;
            }
        }

        // Tant qu'y'a de la pile ;-)
        while ($stack) {
            // Récupère le prochain champ à aller voir
            $field = array_shift($stack);

            // Liste de champs, il faudra visiter les enfants, ajoute à la pile
            if ($field instanceof Fields) {
                $stack = array_merge($stack, $field->fields);
            }

            // Type de champ qu'on n'a pas encore vu, on lui demande ses assets
            $type = get_class($field);
            if (!isset($seen[$type])) {
                $seen[$type] = true;
                foreach ((array) $field::getAssets() as $asset) {
                    $asset += $defaults[$asset['type']];
                    $key = $asset['src'] ? : $asset['name'];

                    $assets[$key] = $asset;
                }
            }
        }

        return $assets;
    }

    /**
     * Retourne les fichiers javascript et css qui sont nécessaires pour
     * ce type de champ.
     *
     * Remarque : ne pas confondre cette méthode (getAssets) avec la méthode
     * assets() :
     * - assets() se charge de créer la liste de tous les assets requis pour
     *   faire le rendu de l'ensemble du formulaire. Elle parcourt tous les
     *   champs, appelle getAssets(), dédoublonne les assets, etc. De ce fait,
     *   elle est marquée "final" car elle ne doit pas être surchargée par les
     *   classes filles et elle est publique car elle fait partie de l'API.
     * - getAssets() ne s'occuppe que des assets requis pour un type de champ
     *   donné. Elle est statique, car les assets ne dépendent que du type de
     *   champ, pas de ses paramètres et elle est protected car c'est notre
     *   cuisine interne. Dans les classes filles, c'est cette méthode qu'il
     *   faut surcharger pour déclarer des assets.
     *
     * @return null|array La méthode doit retourner soit null (si ce type de
     * champs n'a besoin de rien), soit un tableau d'assets, c'est-à-dire un
     * tableau de tableaux.
     *
     * Chaque asset peut contenir les éléments suivants :
     * - type : obligatoire, soit css, doit js.
     * - name : optionnel, le nom de l'asset (jquery, bootstrap-css, etc.)
     * - src  : optionnel, l'url de l'asset
     * - version : optionnel, le numéro de version de l'asset
     * - position : optionnel, soit top soit bottom
     * - media : optionnel, uniquement pour les css, le media (all, screen etc.)
     *
     * Remarque : name et src sont optionnels mais l'un des deux doit être
     * fourni.
     *
     * Important : dans la méthode assets(), aucun contrôle n'est fait pour
     * vérifier la validité de l'asset. Vous devez donc vous assurer que le
     * tableau que vous retournez respecte bien les spécifications ci-dessus.
     */
    protected static function getAssets() {
    }

}

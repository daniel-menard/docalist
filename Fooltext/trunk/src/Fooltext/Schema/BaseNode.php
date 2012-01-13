<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Schema
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id$
 */
namespace Fooltext\Schema;

use IteratorAggregate;
use ArrayIterator;
use XMLWriter;

/**
 * Classe de base parente des classes Node et Nodes.
 *
 *
 * @package     Fooltext
 * @subpackage  Schema
 */
abstract class BaseNode implements IteratorAggregate
{
    /**
     * Les données du noeud.
     *
     * @var array
     */
    protected $data = array();


    /**
     * Noeud parent de ce noeud.
     *
     * Cette propriété est initialisée automatiquement lorsqu'un noeud
     * est ajouté dans une {@link Nodes collection de noeuds}.
     *
     * @var Nodes
     */
    protected $parent = null;


    /**
     * Retourne le noeud parent de ce noeud ou null si le noeud
     * n'a pas encore été ajouté comme fils d'un noeud existant.
     *
     * @return Nodes
     */
    protected function getParent()
    {
        return $this->parent;
    }

    /**
     * Modifie le parent de ce noeud.
     *
     * @param Nodes $parent
     * @return BaseNode $this
     */
    protected function setParent(Nodes $parent)
    {
        $this->parent = $parent;
    }

    /**
     * Retourne le schéma dont fait partie ce noeud ou null si
     * le noeud n'a pas encore été ajouté à un schéma.
     *
     * @return Schema
     */
    public function getSchema()
    {
        return is_null($this->parent) ? null : $this->parent->getSchema();
    }

    /**
     * Retourne un tableau contenant toutes les données du noeud.
     *
     * Pour un objet {@link Node}, la méthode retourne les propriétés du noeud.
     * Pour un objet {@link Nodes} elle retourne la liste des noeuds fils.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Implémente l'interface {@link IteratorAggregate}.
     *
     * Permet d'itérer sur les propriétés d'un noeud avec une boucle foreach.
     *
     * Pour un objet {@link Node}, la boucle itère sur les propriétés du noeud.
     * Pour un objet {@link Nodes} la boucle permet de parcourir tous les noeuds fils.
     *
     * @return ArrayIterator
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    /**
     * Méthode utilisée par {@link Schema::toXml()} pour sérialiser un schéma en XML.
     *
     * Ajoute les propriétés du noeud dans l'objet {@link XMLWriter} passé en paramètre.
     *
     * @param XMLWriter $xml
     */
    protected abstract function _toXml(XMLWriter $xml);

    /**
     * Méthode utilisée par {@link Schema::toJson()} pour sérialiser un schéma en JSON.
     *
     * Sérialise le noeud au format JSON.
     *
     * La méthode ne générère que les propriétés du noeud. La méthode appelante doit
     * générer les accolades ouvrantes et fermantes.
     *
     * @param int $indent indentation à générer.
     * @param string $currentIndent indentation en cours.
     * @param string $colon chaine à utiliser pour générer le signe ":".
     */
    protected abstract function _toJson($indent = 0, $currentIndent = '', $colon = ':');
}
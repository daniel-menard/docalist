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

/**
 * Classe de base parente des classes Node et Nodes.
 *
 *
 * @package     Fooltext
 * @subpackage  Schema
 */
abstract class BaseNode implements \IteratorAggregate
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
     * @var \Fooltext\Schema\Nodes
     */
    protected $parent = null;


    /**
     * Retourne le noeud parent de ce noeud ou null si le noeud
     * n'a pas encore été ajouté comme fils d'un noeud existant.
     *
     * @return \Fooltext\Schema\Nodes
     */
    protected function getParent()
    {
        return $this->parent;
    }

    /**
     * Modifie le parent de ce noeud.
     *
     * @param \Fooltext\Schema\Nodes $parent
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
     * @return \Fooltext\Schema
     */
    public function getSchema()
    {
        return is_null($this->parent) ? null : $this->parent->getSchema();
    }

    /**
     * Retourne un tableau contenant toutes les données du noeud.
     *
     * Pour un objet {\Fooltext\Schema\Node}, la méthode retourne les propriétés
     * du noeud. Pour un objet {\Fooltext\Schema\Nodes} elle retourne la liste
     * des noeuds fils.
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Implémente l'interface {@link \IteratorAggregate}.
     *
     * Permet d'itérer sur les propriétés d'un noeud avec une boucle foreach.
     *
     * Pour un objet {\Fooltext\Schema\Node}, la boucle itère sur les propriétés
     * du noeud. Pour un objet {\Fooltext\Schema\Nodes} la boucle permet de parcourir
     * tous les noeuds fils.
     *
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema\Schema::toXml()}.
     *
     * Ajoute les propriétés du noeud dans le XMLWriter passé en paramètre.
     *
     * @param \XMLWriter $xml
     */
    protected abstract function _toXml(\XMLWriter $xml);

    /**
     * Méthode utilitaire utilisée par {@link \Fooltext\Schema\Schema::toJson()}.
     *
     * Sérialise le noeud au format JSON.
     *
     * La méthode ne générère que les propriétés du noeud. La méthode appelante doit
     * générer les accolades ouvrantes et fermantes.
     *
     * @param \XMLWriter $xml
     */
    protected abstract function _toJson($indent = false, $currentIndent = '', $colon = ':');
}
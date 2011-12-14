<?php
/**
 * This file is part of the Fooltext package.
 *
 * For copyright and license information, please view the
 * LICENSE.txt file that was distributed with this source code.
 *
 * @package     Fooltext
 * @subpackage  Document
 * @author      Daniel Ménard <Daniel.Menard@laposte.net>
 * @version     SVN: $Id: AnalyzerInterface.php 10 2011-12-13 15:45:47Z daniel.menard.35@gmail.com $
 */
namespace Fooltext\Document;

/**
 * Représente un document de la base.
 *
 */
class Document implements DocumentInterface
{
    /**
     * Les données du document.
     *
     * @var array
     */
    protected $data;

    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    public function __get($field)
    {
        return isset($this->data[$field]) ? $this->data[$field] : null;
    }

    public function __set($field, $value)
    {
        $this->data[$field] = $value;
    }

    public function __isset($field)
    {
        return isset($this->data[$field]);
    }

    public function __unset($field)
    {
        unset($this->data[$field]);
    }

    /**
     * Retourne un itérateur sur la liste des champs présents
     * dans le document.
     *
     * Permet d'utiliser un objet Document dans une boucle foreach.
     *
     * @return \ArrayAccess
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    /**
     * Retourne le nombre de champs présents dans le document.
     *
     * @return int
     */
    public function count ()
    {
        return count($this->data);
    }

    public function toArray()
    {
        return $this->data;
    }
}
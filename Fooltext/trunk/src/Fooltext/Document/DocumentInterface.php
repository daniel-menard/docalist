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
 * Interface de base pour représenter un document.
 *
 */
interface DocumentInterface extends \IteratorAggregate, \Countable
{
    /**
     * Construit un nouveau document contenant les données
     * passées en paramètre.
     *
     * @param array $data
     */
    public function __construct(array $data = array());

    /**
     * Retourne le contenu du champ dont le nom est indiqué.
     *
     * @param string $field
     * @return mixed retourne null si le champ n'existe pas.
     */
    public function __get($field);

    /**
     * Modifie le contenu du champ dont le nom est indiqué.
     *
     * @param string $field
     * @param mixed $value
     */
    public function __set($field, $value);

    /**
     * Indique si le document contient le champ dont le nom est indiqué.
     *
     * @param string $field
     * @return bool
     */
    public function __isset($field);

    /**
     * Supprime le champ dont le nom est indiqué.
     *
     * @param string $field
     */
    public function __unset($field);

    /**
     * Convertit le document en tableau.
     *
     * @return array
     */
    public function toArray();
}
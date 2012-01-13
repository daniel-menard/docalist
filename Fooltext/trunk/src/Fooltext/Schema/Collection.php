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
 * Collection de documents.
 */
class Collection extends Node
{
    protected static $defaults = array
    (
        // Identifiant (préfixe) de la collection (code unique : a, b, aa, aaa)
		'_id' => null,

        // Nom de la collection
        'name' => '',

        // Un libellé court décrivant la collection
    	'label' => '',

        // Description, notes, historique des modifs...
        'description' => '',

        // Nom de la classe utilisée pour représenter les documents
        // présents dans cette collection. Doit hériter de Fooltext\Document\Document
        'document' => '\\Fooltext\\Document\\Document',

        // Nom du champ utilisé comme numéro unique des documents
        'docid' => '',
    );

    protected static $nodes = array
    (
    	'fields' => 'Fooltext\\Schema\\Fields',
    	'aliases' => 'Fooltext\\Schema\\Aliases',
    );

//     public function __construct(array $data = array())
//     {
//         parent::__construct($data);

//         if (isset($data['fields']))
//         {
//             $fields = $data['fields'];
//             unset($data['fields']);
//             if (! $fields instanceof Fields) $fields = new Fields($fields);
//         }
//         else
//         {
//             $fields = new Fields();
//         }

//         if (isset($data['aliases']))
//         {
//             $aliases = $data['aliases'];
//             unset($data['aliases']);
//             if (! $aliases instanceof Aliases) $aliases = new Aliases($aliases);
//         }
//         else
//         {
//             $aliases = new Aliases();
//         }

//         $this->data['fields'] = $fields;
//         $this->data['aliases'] = $aliases;
//     }

    /**
     * Ajoute un champ dans la collection.
     *
     * @param Field $field le champ à ajouter.
     *
     * @return \Fooltext\Schema\Collection $this
     *
     * @throws \Exception si le champ a déjà un id.
     */
//     public function addField($field)
//     {
//         // Vérifie que $field est un champ
//         if(! $field instanceof Field) $field = new Field($field);

//         // Attribue un id au champ
//         if (! is_null($field->_id)) throw new \Exception('Le champ a déjà un _id');
//         $field->_id = ++$this->_lastid;

//         // Stocke le champ
//         $this->data['fields']->add($field);
//         $this->id[$field->_id] = $field->name;

//         return $this;
//     }

    /**
     * Indique si la collection contient des champs.
     *
     * @return boolean
     */
//     public function hasFields()
//     {
//         return ! empty($this->data['fields']);
//     }

    /**
     * Indique si la collection contient un champ ayant le nom ou l'id indiqué.
     *
     * @param string $name
     * @return boolean
     */
//     public function hasField($name)
//     {
//         return (isset($this->data['fields'][$name])) || (isset($this->id[$name]));
//     }

    /**
     * Retourne le champ de la collection ayant le nom ou l'id indiqué.
     *
     * Génère une
     * @param string|int $name
     * @throws \Exception
     */
//     public function getField($name)
//     {
//         if (isset($this->data['fields'][$name])) return $this->data['fields'][$name];
//         if (isset($this->id[$name])) return $this->fields[$this->id[$name]];
//         throw new \NotFound("Le champ $name n'existe pas.");
//     }

    /**
     * Retourne la liste des champs de la collection.
     *
     * @return Collections
     */
//     public function getFields()
//     {
//         return $this->data['fields'];
//     }

    /**
     * Supprime le champ ayant le nom ou l'id indiqué.
     *
     * Génère une exception si le champ indiqué n'existe pas.
     *
     * @param string $name
     * @throws NotFound
     * @return \Fooltext\Schema\Collection $this
     */
//     public function deleteField($name)
//     {
//         if (isset($this->data['fields'][$name]))
//         {
//             unset($this->id[$this->data['fields'][$name]->_id]);
//             unset($this->data['fields'][$name]);
//             return $this;
//         }

//         throw new \NotFound("Le champ $name n'existe pas.");
//     }

//     public function getFieldName($id)
//     {
//         if (isset($this->id[$id])) return $this->id[$id];
//         return null;
//     }
}
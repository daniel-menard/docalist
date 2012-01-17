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

use DOMElement;

/**
 * Classe statique contenant des méthodes permettant de convertir des
 * schémas XML d'ancienne génération vers le format actuel.
 */
class SchemaConverter
{
    /**
     * Convertit un schéma en version 1 vers la version actuelle.
     *
     * Important : la conversion n'est pas parfaite car la logique est trop
     * différente entre les anciens schémas et les nouveaux. Le convertisseur
     * permet de récupérer la majorité des informations, mais il faut ensuite
     * vérifier pour chaque champ la chaine d'analyse et les propriétés.
     *
     * @param DOMDocument $xml
     * @return Schema
     */
    public static function fromVersion1(DOMElement $node)
    {
        $data = self::_fromVersion1($node);

        if (isset($data['aliases']))
        {
            foreach ($data['aliases'] as $i => & $alias)
            {
                $fields = array();
                foreach($alias['indices'] as $index)
                {
                    $fields[] = $index['name'];
                }
                unset($alias['indices']);
                $alias['fields'] = $fields;
            }
            unset($alias);
        }

        $collection = array('name' => 'CONVERTED');

        foreach(array('fields', 'aliases') as $prop)
        {
            if (isset($data[$prop]))
            {
                $t = array();
                foreach($data[$prop] as $item)
                {
                    $t[strtolower($item['name'])] = $item;
                }
                $collection[$prop] = $t;
                unset($data[$prop]);
            }
        }

        $props = array
        (
            'words' => 'Fooltext\\Indexing\\Words',
            'phrases' => 'Fooltext\\Indexing\\Phrases',
            'values' => 'Fooltext\\Indexing\\Keywords',
            'count' => 'Fooltext\\Indexing\\Countable',
        );

        if (isset($data['indices']))
        {
            foreach ($data['indices'] as $i => $index)
            {
                $fields = $index['fields'];

                $all = array();
                foreach ($fields as $field)
                {
                    $fieldName = strtolower($field['name']);

                    if (isset($collection['fields'][$fieldName]['type'])
                        && $collection['fields'][$fieldName]['type'] === 'autonumber')
                    {
                        $collection['fields'][$fieldName]['analyzer'] = 'Fooltext\\Indexing\\Integer';
                        unset($collection['fields'][$fieldName]['defaultstopwords']);
                    }
                    else
                    {

                        if (isset($field['phrases']) && isset($field['words'])) unset($field['words']);

                        $analyzer = array();
                        foreach($props as $prop=>$class)
                        {
                            if (isset($field[$prop]))
                            {
                                if (empty($analyzer)) $analyzer[] = 'Fooltext\\Indexing\\Lowercase';
                                $analyzer[] = $class;
                            }
                        }

                        if (isset($collection['fields'][$fieldName]['defaultstopwords'])
                            && $collection['fields'][$fieldName]['defaultstopwords'] === 'false')
                        {
                            $analyzer[] = 'Fooltext\\Indexing\\RemoveStopwords';
                        }
                        unset($collection['fields'][$fieldName]['defaultstopwords']);

                        if (isset($index['spelling'])) $analyzer[] = 'Fooltext\\Indexing\\Spellings';

                        $collection['fields'][$fieldName]['analyzer'] = $analyzer;

                        foreach(array('weight', 'start', 'end') as $prop)
                        {
                            if (isset($field[$prop]))
                            {
                                $collection['fields'][$fieldName][$prop] = $field[$prop];
                            }
                        }

                        $all[] = $field['name'];
                    }
                }

                if (count($all) > 1)
                {
                   $index['fields'] = $all;
                   if (isset($collection['aliases'][$index['name']]))
                   {
                       $index['name'] .= '2';
                   }
                   $collection['aliases'][$index['name']] = $index;
                }
            }
        }
        unset($data['indices']);

        if (isset($data['sortkeys']))
        {
            foreach ($data['sortkeys'] as $i => $key)
            {
                $fields = array();
                foreach($key['fields'] as $field)
                {
                    $field = & $collection['fields'][strtolower($field['name'])];
                    $field['analyzer'][] = 'Fooltext\\Indexing\\Attribute';
                    unset($field);
                }
            }
        }
        unset($data['sortkeys']);

        if (isset($data['lookuptables']))
        {
            foreach ($data['lookuptables'] as $i => $table)
            {
                $fields = array();
                foreach($table['fields'] as $field)
                {
                    $field = & $collection['fields'][strtolower($field['name'])];
                    array_unshift($field['analyzer'], 'Fooltext\\Indexing\\Lookup');
                    unset($field);
                }
            }
        }

        unset($data['lookuptables']);

        $data['collections'] = array($collection);

        return $data;
    }

    public static function _fromVersion1(DOMElement $node)
    {
        $data = array();

        foreach ($node->attributes as $attribute)
        {
            $data[$attribute->nodeName] = $attribute->nodeValue;
        }

        foreach($node->childNodes as $child)
        {
            switch ($child->nodeName)
            {
                case 'fields':
                case 'indices':
                case 'lookuptables':
                case 'aliases':
                case 'indices':
                case 'sortkeys':
                    foreach($child->childNodes as $item)
                    {
                        $data[$child->nodeName][] = self::_fromVersion1($item);
                    }
                    break;
                default:
                    $data[$child->nodeName] = $child->nodeValue;
            }
        }
        return $data;
    }
}
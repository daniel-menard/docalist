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
 * Un champ au sein d'une collection.
 */
class Field extends Node
{
    /**
     * Propriétés par défaut d'un champ.
     *
     * @var array
     */
    protected static $defaults = array
    (
        // Identifiant (numéro unique) du champ
		'_id' => null,

        // Nom du champ, d'autres noms peuvent être définis via des alias
        'name' => '',

        // Type du champ
        'type' => 'text',

        // Traduction de la propriété type en entier
        '_type' => null,

        // Libellé du champ
        'label' => '',

        // Description
        'description' => '',

    	'widget' => 'textbox',
    	'datasource' => '',

    	'analyzer' => null,

    	'weight' => 1,
    );

    /**
     * Setter pour la propriété 'analyzer' du champ.
     *
     * Vérifie que les analyseurs indiqués existent et stocke le résultat sous
     * forme de tableau. Le nom de classe d'un analyseur peut être indiqué avec ou
     * sans namespace. Si aucune namespace ne figure dans le nom de la classe, la
     * méthode ajoute le namespace Fooltext\Indexing\.
     *
     * @param string|array $value le nom de l'analyseur (ou un tableau d'analyseurs)
     * à utiliser pour ce champ.
     *
     * @throws \Exception Si l'analyseur indiqué n'existe pas ou s'il n'implémente
     * pas l'interface {@link Fooltext\Indexing\AnalyzerInterface}.
     */
    public function setAnalyzer($value)
    {
        if (is_scalar($value)) $value = array($value);

        foreach($value as & $analyzer)
        {
            if (false === strpos($analyzer, '\\'))
            {
                $analyzer = 'Fooltext\\Indexing\\' . $analyzer;
            }

            if (! class_exists($analyzer))
            {
                throw new \Exception("Classe $analyzer non trouvée");
            }

            $interfaces = class_implements($analyzer);
            if (! isset($interfaces['Fooltext\Indexing\AnalyzerInterface']))
            {
                throw new \Exception("La classe $analyzer n'est pas un analyseur");
            }
        }
        $this->data['analyzer'] = $value;
    }
}
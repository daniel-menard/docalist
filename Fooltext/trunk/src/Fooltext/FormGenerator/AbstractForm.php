<?php
namespace Fooltext\FormGenerator;

abstract class AbstractForm
{
    protected $module;

    public function __construct(\Module $module)
    {
        $this->module = $module;
    }

    abstract public function render($document);

    /**
     * @param string $datasource nom de la source de données à utiliser.
     *
     * @param string $format format (sprintf) utilisé pour lister chacune des entrées de la table.
     * Premier %s (%1$s) : code de l'entrée (typiquement : attribut value)
     * Second  %s (%2$s) : attribut de sélection (selected="selected" ou checked="checked")
     * Troisème %s (%3$s) : libellé de l'entrée (typiquement, le label)
     *
     * Exemples :
     * - pour une option de select : <option value="%s" %s>%s</option>
     * - pour une checkbox : <label><input type="checkbox" value="%s" %s />%s</label>
     *
     * @param string $select valeur et attribut utilisé pour marquer une entrée comme sélectionnée.
     * pour un option de select : "selected", pour une checkbox ou un radio : "checked".
     */
    public function listDataSource($datasource, $values, $format = '<option value="%s"%s>%s</option>', $select='selected')
    {
        $values = array_flip((array) $values);

        foreach ($this->module->getDataSource($datasource) as $entry)
        {
            printf
            (
                $format,
                $entry[0],
                isset($values[$entry[0]]) ? " $select=\"$select\"" : '',
                $entry[1]
            );
        }
    }
}
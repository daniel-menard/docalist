<?php
namespace Fooltext\FormGenerator;

use Fab\Schema\Node;

use Fab\Schema\Schema;
use Fab\Schema\Nodes;


class FormGenerator extends FormWriter
{
    /**
     *
     * @var array
     */
    protected $form;

    public function __construct()
    {
    }

    public function compile($path, $class, $to)
    {
        // Charge le formulaire xml
        $form = \Config::loadXml(file_get_contents($path));

        // Vérifie qu'un schéma a été indiqué
        if (! isset($form['schema']))
        {
            throw new \Exception("Le formulaire doit contenir la clé schema.");
        }

        // Charge le schéma
        $path = \Utils::makePath(\Runtime::$root, 'data/schemas', $form['schema']);
        if (! file_exists($path))
        {
            throw new \Exception("Le schéma indiqué dans le formulaire n'existe pas ($form[schema])");
        }
        $schema = Schema::fromXml(file_get_contents($path));

        // Crée la hiérarchie d'items
        $this->form = Item::create($form, $schema);
        //echo '<pre>', htmlentities(print_r($this->form,true)),'</pre>';

        // Initialise le writer
        parent::__construct($class);

        // Génère le formulaire
        $this->item($this->form);

        // Stocke le résultat
        $this->output($to);
    }

    protected function item(Item $item)
    {
        $this->{$item->type . 'Item'}($item, $this);
    }

    protected function items(array $items)
    {
        foreach($items as $item)
        {
            $this->item($item, $this);
        }
    }

    protected function formItem(FormItem $item)
    {
        $this->start('form', array('class'=>$item->class, 'style'=>$item->style, 'action'=>$item->action));
        $this->items($item->items, $this);
        $this->end();
    }

    protected function fieldsetItem(FieldsetItem $item)
    {
        $this->start('fieldset', array('class' => $item->class, 'style' => $item->style));

        if ($item->label) $this->tag('legend', $item->label);
        if ($item->description) $this->helpBlock($item->description, $this);

        $this->items($item->items, $this);

        $this->end();
    }

    protected function divItem(DivItem $item)
    {
        $this->start('div', array('class' => $item->class, 'style' => $item->style));

        if ($item->label) $this->tag('h2', $item->label);
        if ($item->description) $this->helpBlock($item->description, $this);

        $this->items($item->items, $this);

        $this->end();
    }

    protected function htmlItem(HtmlItem $item)
    {
        $this->raw(trim($item->html) . "\n");
    }

    protected function submitItem(SubmitItem $item)
    {
        $this->start('button', array('type'=>'submit', 'class' => $item->class, 'style' => $item->style));
        {
            $this->items($item->items, $this);
            $this->text($item->label);
        }
        $this->end();
    }

    /**
     * Génère le container d'un champ (div.control-group), son label et son widget
     *
     * @param FieldItem $item
     */
    protected function fieldItem(FieldItem $item)
    {
        $this->start('div', array('class'=>"control-group $item->class", 'style'=>$item->style));
        {
            $this->fieldLabel($item, 'control-label', 0);
            $this->start('div', array('class' => 'controls'));
            $this->widget($item, "\$document['$item->name']");
            $this->end();
        }
        $this->end();
    }

    protected function fieldLabel(Item $item, $class = '', $var = '$row')
    {
        $for = $this->getItemName($item, $var);
        $this->tag('label', $item->label, array('class'=>$class, 'for'=>$for, 'title'=>$item->description));
    }

    protected function oldfieldItem(FieldItem $item)
    {
        $this->start('div', array('class'=>"control-group $item->mainclass"));
        {
            $this->start('label', array('class'=>'control-label', 'for'=>$item->name));
            {
                $this->tag('abbr', $item->label, array('title'=>$item->description), $item->description);
            }
            $this->end('label');

            // Champ simple
            if (! isset($item->items))
            {
                $this->start('div', array('class'=>'controls'));
                {
                    if ($item->repeatable)
                    {
                        $this->php("\$value=\$document->get('$item->name');");
                        $this->widget($item, 'is_null($value) ? array(\'\') : $value');
                    }
                    else
                    {
                        $this->widget($item, "\$document->get('$item->name')");
                    }
                }
                $this->end('div.controls');
            }

            // Champ structuré non répétable
            elseif (! $item->repeatable)
            {
                $this->start('div', array('class'=>'controls'));
                {
                    $code = "\$value=\$document->get('$item->name');";
                    $code .= "if (is_null(\$value)) \$value=array(";
                    foreach ($item->items as $zone)
                    {
                        if ($zone->repeatable)
                            $code .= "'$zone->name'=>array(''),";
                        else
                            $code .= "'$zone->name'=>'',";
                    }
                    $code .= ");";

                    $this->php($code);

                    foreach ($item->items as $zone)
                    {
                        $this->start('div', array('class'=>'input-prepend'));
                        {
                            $this->tag('abbr', $zone->label, array('title'=>$zone->description, 'class'=>'add-on'), $zone->description);
                            $this->widget($zone, "\$value['$zone->name']");
                        }
                        $this->end('div');
                        $this->text(' ');
                    }
                }
                $this->end('div.controls');
            }

            // Champ structuré répétable
            else
            {
                $this->start('table', array('class'=>'table table-condensed'));
                {
                    $this->start('thead');
                    {
                        $this->start('tr');
                        {
                            $this->tag('td');
                            foreach ($item->items as $zone)
                            {
                                $this->start('th');
                                $this->tag('abbr', $zone->label, array('title'=>$zone->description), $zone->description);
                                $this->end('th');
                            }
                        }
                        $this->end('tr');
                    }
                    $this->end('thead');
                    $this->start('tbody');
                    {
                        $code = "\n\$value=\$document->get('$item->name');\n";
                        $code .= "if (is_null(\$value))\n    \$value=array(null);\n";
                        $code .= "foreach(\$value as \$row=>\$value)\n{\n";
                        $this->php($code);

                        $this->start('tr');
                        {
                            $this->start('td');
                            $this->php('echo $row+1');
                            $this->end('td');
                            foreach ($item->items as $zone)
                            {
                                $this->start('td');
                                $code = "isset(\$value['$zone->name']) ? \$value['$zone->name'] : " . ($zone->repeatable ? "array('')" : "''");
                                $this->widget($zone, $code);
                                $this->end('td');
                            }
                        }
                        $this->end('tr');
                        $this->php('}'); // foreach
                    }
                    $this->end('tbody');
                }
                $this->end('table');
/*
                $this->start('ol', array('class'=>'controls'));
                {
                    $code = "\n\$value=\$document->get('$item->name');\n";
                    $code .= "if (is_null(\$value))\n    \$value=array(null);\n";

                    $code .= "foreach(\$value as \$value)\n{\n";
                    $this->php($code);

                    $this->start('li');
                    {
                        foreach ($item->items as $zone)
                        {
                            $this->start('div', array('class'=>"input-prepend $zone->mainclass"));
                            {
                                $this->tag('abbr', $zone->label, array('title'=>$zone->description, 'class'=>'add-on'), $zone->description);
                                $code = "isset(\$value['$zone->name']) ? \$value['$zone->name'] : " . ($zone->repeatable ? "array('')" : "''");
                                $this->widget($zone, $code);
                            }
                            $this->end('div');
                            $this->text(' ');
                        }
                    }
                    $this->end('li');

                    $this->php('}');

                    $this->start('li', array('style'=>'list-style-type: none;'));
                    {
                        $this->repeatButton($item);
                    }
                    $this->end('li');
                }
                $this->end('ol.controls');
*/
            }
        }
        $this->end('div.control-group');
    }

/*
Code généré par tous les widgets :

    div.control-group           class, style
        label.control-label
        div.controls
            code du widget      widget-class, widget-style

 */
    protected function getDefaultWidget(WidgetItem $item)
    {
        if (! $item->hasItems()) return 'textbox';
        return $item->repeatable ? 'table' : 'list';
    }

    protected function widget($item, $values)
    {
        if (! $item->widget) $item->widget = $this->getDefaultWidget($item);

        $item->name = $this->getItemName($item);
//        $this->php("echo '<small>name=$item->name</small>'");
        $this->{$item->widget . 'Widget'}($item, $values);
    }

    protected function getItemName(WidgetItem $item, $var='$row')
    {
        // champ simple non répétable : $name
        // champ simple répétable : $name[]   (ou $name si repairGetPost)

        // zone non répétable. parent non répétable : $parent[$name]
        // zone répétable. parent non répétable : $parent[$name][]

        // zone non répétable. parent répétable : $parent[$row][$name]
        // zone répétable. parent répétable : $parent[$row][$name][]

        $name = $item->name;
        if ($item instanceof FieldItem)
        {
            if ($item->repeatable)
            {
//                 if (is_int($var))
//                     $name .= '[0]';
//                 else
//                     $name = '<?php echo "' . $name . '[' . $var . ']' . '"? >';
                $name .= '[]';
            }
        }
        elseif ($item instanceof ZoneItem)
        {
            $name = "[$name]";
            if ($item->repeatable) $name .= '[]';

            if ($item->parent->repeatable)
            {
                if (is_int($var))
                    $name = $item->parent->name . '[0]' . $name;
                else
                    $name = '<?php echo "' . $item->parent->name . '[' . $var . ']' . $name . '"?>';
            }
            else
            {
                $name = $item->parent->name . $name;
            }
        }
        else
        {
            throw new \Exception('non géré');
        }

        return $name;
    }

    protected function textboxWidget(WidgetItem $item, $values)
    {
        if ($item->hasItems())
            throw new \Exception("$item->name : le widget textbox n'est pas utilisable pour un champ structuré");

        $class='';
        if ($item->repeatable)
        {
            $this->php("foreach($values as \$row=>\$entry){");
            $values = '$entry';
            $class='repeatable';
        }

        $this->tag('input', null, array(
            'type'=>'text',
            'name'=>$item->name,
            'id'=>$item->name,
            'class'=>"$class $item->widgetclass",
            'style'=>$item->widgetstyle,
            'value'=>"<?php echo htmlspecialchars($values)?>"
        ));

        if ($item->repeatable)
        {
            $this->php('}'); // foreach
            $this->repeatButton($item);
        }
    }

    protected function textareaWidget(WidgetItem $item, $value)
    {
        if ($item->hasItems())
            throw new \Exception("$item->name : le widget textarea n'est pas utilisable pour un champ structuré");

        $class='';
        if ($item->repeatable)
        {
            $this->php("foreach($value as \$entry){");
            $value = '$entry';
            $class='repeatable';
        }

        $this->tag('textarea', "<?php echo htmlspecialchars($value)?>", array(
            'name'=>$item->name,
            'id'=>$item->name,
            'class'=>"$class $item->widgetclass",
            'style'=>$item->widgetstyle,
            'cols'=>80,
            'rows'=>1,
        ));

        if ($item->repeatable)
        {
            $this->php('}'); // foreach
            $this->repeatButton($item);
        }
    }

    protected function selectWidget(WidgetItem $item, $value)
    {
        if ($item->hasItems())
            throw new \Exception("$item->name : le widget select n'est pas utilisable pour un champ structuré");

        $this->start('select', array(
            'name'=>$item->name,
            'id'=>$item->name,
            'class'=>$item->widgetclass,
            'style'=>$item->widgetstyle,
        ));

        if ($item->repeatable)
        {
            $this->attr('multiple', 'multiple');
        }

        $this->php
        (
            sprintf
            (
                '$this->listDataSource(%s, %s, %s, %s);',
                var_export($item->datasource,true),
                $value,
                var_export('<option value="%s"%s>%s</option>', true),
                var_export('selected',true)
            )
        );

        $this->end('select');
    }

    protected function inputlist(WidgetItem $item, $values, $type='radio') // checklist ou radiolist
    {
        $class = $item->class ? "$type $item->widgetclass" : $type;
        $style = $item->style ? " style=\"$item->widgetstyle\"" : '';

        $format = "<label class='$class'$style><input type='$type' name='$item->name' value='%s'%s/> %s</label>";

        $this->php
        (
            sprintf
            (
                '$this->listDataSource(%s, %s, %s, %s);',
                var_export($item->datasource,true),
                $values,
                var_export($format, true),
                var_export('checked',true)
            )
        );
        if ($type==='radio' && $item->repeatable) $this->repeatButton($item);
    }

    protected function checklistWidget(WidgetItem $item, $values)
    {
        if ($item->hasItems())
            throw new \Exception("$item->name : le widget checklist n'est pas utilisable pour un champ structuré");

        $this->inputlist($item, $values, 'checkbox');
    }

    protected function radiolistWidget(WidgetItem $item, $values)
    {
        if ($item->hasItems())
            throw new \Exception("$item->name : le widget radiolist n'est pas utilisable pour un champ structuré");

        $this->inputlist($item, $values, 'radio');
    }

    protected function tableWidget(WidgetItem $item, $values)
    {
        if (! $item->hasItems())
            throw new \Exception("$item->name : le widget table n'est utilisable que pour un champ structuré");

        $code = "\n\$value=$values;\n";
        $code .= "if (is_null(\$value)) \$value=array(null);\n";
        if (! $item->repeatable) $code .= "\$value = array(\$value);\n";
        $this->php($code);

        $this->start('table', array('class'=>"table table-condensed $item->widgetclass", 'style'=>$item->widgetstyle));
        {
            $this->start('thead');
            {
                $this->start('tr');
                {
//                     if ($item->repeatable) $this->tag('th');
                    foreach ($item->items as $zone)
                    {
                        $this->start('th', array('title'=>$zone->description));
                        if ($zone instanceof ZoneItem)
                        {
//                            $this->fieldLabel($zone, '', 0);
//                            $this->tag('span', $zone->label, array('title'=>$item->description));
                            $this->text($zone->label);
                        }
                        $this->end('th');
                    }
                }
                $this->end();
            }
            $this->end('thead');
            $this->start('tbody');
            {
                $this->php("foreach(\$value as \$row=>\$value) {");
                $this->start('tr');
                {
//                     if ($item->repeatable) $this->tag('th', "<?php echo \$row+1,'.'; ? >");
                    foreach ($item->items as $zone)
                    {
                        $this->start('td', array('class'=>$zone->class, 'style'=>$zone->style));
                        if ($zone instanceof ZoneItem)
                        {
                            $code = "isset(\$value['$zone->name']) ? \$value['$zone->name'] : " . ($zone->repeatable ? "array('')" : "''");
                            $this->widget($zone, $code);
                        }
                        else
                        {
                            $this->item($zone);
                        }
                        $this->end('td');
                    }
                }
                $this->end('tr');
                $this->php('}'); // foreach
            }
            $this->end('tbody');
            if ($item->repeatable)
            {
                $this->start('tfoot');
                {
                    $this->start('tr');
                    {
                        //$this->tag('td');
                        $this->start('td', array('colspan'=>count($item->items)));
                        {
                            $this->repeatButton($item);
                        }
                        $this->end();
                    }
                    $this->end();
                }
                $this->end();
            }
        }
        $this->end('table');
    }

    protected function listWidget(WidgetItem $item, $values)
    {
        if (! $item->hasItems())
            throw new \Exception("$item->name : le widget list n'est utilisable que pour un champ structuré");

        $code = "\n\$value=$values;\n";
        $code .= "if (is_null(\$value)) \$value=array(null);\n";
        $this->php($code);

        if ($item->repeatable)
        {
            $this->start('ol', array('class'=>''));
            $this->php("foreach(\$value as \$row=>\$value) {");
            $this->start('li');
        }
        foreach ($item->items as $zone)
        {
            if ($zone instanceof ZoneItem)
            {
                $this->start('div', array('class'=>"input-prepend $zone->class", 'style'=>$zone->style));
                {
                    $this->fieldLabel($zone, 'add-on');
                    $code = "isset(\$value['$zone->name']) ? \$value['$zone->name'] : " . ($zone->repeatable ? "array('')" : "''");
                    $this->widget($zone, $code);
                }
                $this->end('div');
                $this->text(' ');
            }
            else
            {
                $this->item($zone);
            }
        }
        if ($item->repeatable)
        {
            $this->end('li');
            $this->php('}'); // foreach

            $this->start('li', array('style'=>'list-style-type: none;'));
            {
                $this->repeatButton($item);
            }
            $this->end('li');
            $this->end('ol.controls');
        }
    }

    protected function showItem(ShowItem $item)
    {
        $this->start('div', array('class'=>$item->class, 'style'=>$item->style));
        $this->items($item->items, $this);
        $this->end();
    }

    protected function spanWidget(WidgetItem $item, $values)
    {
        if ($item->hasItems())
            throw new \Exception("$item->name : le widget span n'est pas utilisable pour un champ structuré");

        $class='';
        if ($item->repeatable)
        {
            $this->php("foreach($values as \$entry){");
            $values = '$entry';
            $class='repeatable';
        }

        $this->tag('span', "<?php echo htmlspecialchars($values)?>", array(
            'class'=>"$class $item->widgetclass",
            'style'=>$item->widgetstyle,
        ));

        if ($item->repeatable)
        {
            $this->php('}'); // foreach
        }
    }

    protected function divWidget(WidgetItem $item, $values)
    {
        if ($item->hasItems())
            throw new \Exception("$item->name : le widget div n'est pas utilisable pour un champ structuré");

        $class='';
        if ($item->repeatable)
        {
            $this->php("foreach($values as \$entry){");
            $values = '$entry';
            $class='repeatable';
        }

        $this->tag('div', "<?php echo htmlspecialchars($values)?>", array(
            'class'=>"$class $item->widgetclass",
            'style'=>$item->widgetstyle,
        ));

        if ($item->repeatable)
        {
            $this->php('}'); // foreach
        }
    }


    protected function helpBlock($content)
    {
        $this->tag('p', $content, array('class'=>'help-block'));
    }

    protected function repeatButton(Item $item)
    {
        $this->start('button', array('type'=>'button', 'class'=>"btn btn-mini btn-add"));
        $this->tag('i', '', array('class'=>'icon-plus-sign'));
        if (isset($item->items)) $this->text(' Ajouter une ligne');
        $this->end('button');
    }





    protected function formButtons()
    {
        $this->start('div', array('class'=>'form-actions'));
        $this->start('button', array('type'=>'submit', 'class'=>"btn btn-primary"));
        $this->tag('i', '', array('class'=>'icon-ok icon-white'));
        $this->text('Enregistrer');
        $this->end('button');
        $this->text(' ');
        $this->start('button', array('type'=>'', 'class'=>"btn"));
        $this->tag('i', '', array('class'=>'icon-remove'));
        $this->text('Annuler');
        $this->end('button');
        $this->end('form');
    }


}
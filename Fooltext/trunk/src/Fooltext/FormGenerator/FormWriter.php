<?php
namespace Fooltext\FormGenerator;

use \XMLWriter;

abstract class FormWriter
{
    /**
     * @var XMLWriter
     */
    protected $xml;

    public function __construct($class)
    {
        $this->xml = new XMLWriter();
        $this->xml->openMemory();

        if (debug)
        {
            $this->xml->setIndent(true);
            $this->xml->setIndentString(str_repeat(' ', 4));
        }

        // Début de la génération
        $this->xml->startDocument('1.1', 'UTF-8', 'true');

        $this->php("\nuse \\Fooltext\\FormGenerator\\AbstractForm;\n\nclass $class extends AbstractForm\n{\n    public function render(\$document)\n    {\n");
    }

    protected function output($to)
    {
        $this->php("\n    } // function render\n} // class\n");
        $this->xml->endDocument();

        $result = $this->xml->outputMemory(true);
        $result = substr($result, strpos($result, '<?php'));
        unset($this->xml);
        file_put_contents($to, $result);
    }

    public function start($tag, $attr = null)
    {
        $this->xml->startElement($tag);

        if (is_null($attr)) return;

        foreach($attr as $name => $value)
        {
            $this->attr($name, $value);
        }
    }

    public function end($forceEndTag = false)
    {
        $forceEndTag ? $this->xml->fullEndElement() : $this->xml->endElement();
    }

    /**
     * content : indiquer null pour un tag vide (exemple : <br />
     * indiquer '' pour une tag vide avec balise fermante : (exemple : <span></span>)
     * @param unknown_type $tag
     * @param unknown_type $content
     * @param unknown_type $attr
     * @param unknown_type $if
     */
    public function tag($tag, $content = null, $attr = null, $if = true)
    {
        if ($if) $this->start($tag, $attr);
        if (! is_null($content))
        {
            if (substr($content, 0, 5) === '<?php')
            {
                $this->xml->writeRaw($content);
            }
            else
            {
                $this->xml->text($content);
            }
        }
        if ($if) $this->end($content === '');
    }

    public function attr($name, $value)
    {
        $value = trim($value);
        if ($value === '') return;

        if (substr($value, 0, 5) === '<?php')
        {
            $this->xml->startAttribute($name);
            $this->xml->writeRaw($value);
            $this->xml->endAttribute();
        }
        else
        {
            $this->xml->writeAttribute($name, $value);
        }
    }

    public function text($text)
    {
        $this->xml->text($text);
    }

    public function raw($content)
    {
        $this->xml->writeRaw($content);
    }

    public function php($content)
    {
        $this->xml->writePi('php', $content);
    }
}
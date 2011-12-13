<?php
namespace Fooltext\Document;
use Fooltext\Schema\Schema;

// Un enregistrement de la base
class Document// extends \Multimap
{

    public function __construct(array $data = array())
    {
        $this->data = $data;
        return;
        $this->compareMode(self::CMP_EQUAL);
        foreach($data as $key=>$value)
        {
            $this->add($key, $value);
        }
    }

    public function toArray()
    {
        return $this->data;
    }
}

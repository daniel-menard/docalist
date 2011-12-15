<?php
header('content-type: text/html; charset=utf-8');

echo '<pre>';
require_once(__DIR__ . '/autoload.php');

// test query
use Fooltext\Query\Query;
use Fooltext\Query\QueryOr;
use Fooltext\Query\QueryAnd;
if (false)
{
    $q1 = new QueryOr('a','b');
    $q2 = new QueryOr('c','d');
    $q = new QueryOr($q1, $q2);
    echo "q1 : $q1<br />q2: $q2<br />result: $q<br /><br />";
    $q1->dump('q1');
    $q2->dump('q1');
    $q->dump('result');

    die();
}
// ********** test parser
use Fooltext\QueryParser\Lexer;
use Fooltext\QueryParser\Parser;

$equation = "typdoc=article date:2011 titre:\"l'hôpital dans tous ses états\" motscles=[humour]";
$equation = 'a OR b +(c NOT z) d OR e AND D near E';
$equation = 'a "b c" "d e"';
//$equation = 'a b titre:+';

$lexer = new Lexer();
$lexer->dumpTokens($equation);

$parser = new Parser();
$query = $parser->parseQuery($equation);
echo "<hr />", $query, "<hr />";
var_export($query);

die();

// ********** test lexer
$lexer = new Lexer();
$lexer->dumpTokens('MotsCles="a b c"');
die();


// *****************
$schema = new Fooltext\Schema\Schema();
$schema->stopwords = 'le la les de du des a c en';
$catalog = new Fooltext\Schema\Collection(array('name'=>'catalog', 'documentClass'=>'Notice'));
$catalog
    ->addField(array('name'=>'REF'    , 'analyzer'=>array('Fooltext\Indexing\Integer', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'Type'   , 'analyzer'=>array('Fooltext\Indexing\StandardValuesAnalyzer', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'Titre'  , 'analyzer'=>array('Fooltext\Indexing\StandardTextAnalyzer','Fooltext\Indexing\RemoveStopwords')))
    ->addField(array('name'=>'Aut'    , 'analyzer'=>array('Fooltext\Indexing\StandardValuesAnalyzer', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'ISBN'   , 'analyzer'=>array('Fooltext\Indexing\Isbn', 'Fooltext\Indexing\Attribute')))
    ->addField(array('name'=>'Visible', 'analyzer'=>array('Fooltext\Indexing\BooleanExtended', 'Fooltext\Indexing\Attribute')));

$schema->addCollection($catalog);

$db = new Fooltext\Store\XapianStore(array('path'=>'f:/temp/test', 'overwrite'=>true, 'schema'=>$schema));


class Notice extends \Fooltext\Document\Document
{
}

echo "Ajout d'un enreg\n";
for ($ref=123; $ref<=124; $ref++)
{
    $db->catalog->put(array
    (
        'REF'=>$ref,
        'Type'=>array('Article','Document électronique'),
        'Titre'=>'Premier essai <i>(sous-titre en italique)</i>',
        'Aut'=>'Ménard (D.)',
        'ISBN'=>array("978-2-1234-5680-3", "2-1234-5680-2"),
        'Visible'=>true,
    ));
}

for ($ref=123; $ref<=124; $ref++)
{
    echo "Appelle get($ref)\n";
    $doc2 = $db->catalog->get($ref);
    echo $doc2, "\n";
//     var_export((array)$doc2);
//     echo serialize($doc2), "\n";
    echo "\n\n";
}
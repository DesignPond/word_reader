<?php

require_once( 'bootstrap/autoload.php' );

//include('vendor/simple_html_dom.php');
//include('vendor/html_dom_parser.php');

use Sunra\PhpSimple\HtmlDomParser;
use Pond\Clean;

$cleaner = new Clean();

?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="description" content="$1">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">
    <title></title>
</head>
<body>

<div class="container">
    <div class="col-md-8 col-md-offset-2">
        <?php


        $dom = HtmlDomParser::file_get_html( 'http://pond.local/index.php' );

        $elems = $dom->find('div.start');

        $data = [];

        function globChildren($elems){

            $result   = array();

            $children = $elems->children;

            if($children)
            {
                foreach($children as $child)
                {
                    $result[] = [ 'text' => $child->innertext, 'tag' => $child->tag ];
                }
                $result = array_merge($result,globChildren($child));
            }
            else
            {
                $result[] = [ 'text' => $elems->innertext, 'tag' => $elems->tag ];
            }

            return $result;
        }


        foreach($elems as $elem){

            $text     = $elem->innertext;
            $children = globChildren($elem);

            $data[] = [
                'parent' => $children
            ];

        }

        echo '<pre>';
        print_r($data);
        echo '</pre>';


        ?>
    </div>
</div>


</body>
</html>
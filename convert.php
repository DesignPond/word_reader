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
    <style type="text/css">
        .text-justify{
            margin-bottom: 15px;
        }
    </style>
    <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
    <script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>

    <script>
        $( function() {

            $('.text-indent').each(function(index, value) {
                var pixels = $(this).attr('rel');
                $(this).css({ 'padding-left' : pixels+'px' });
            });

        });
    </script>
</head>
<body>

<div class="container">
    <div class="col-md-8 col-md-offset-2">
        <?php


        $dom = HtmlDomParser::file_get_html( 'http://pond.local/index.php' );

        // Write a function with parameter "$element"
        function my_callback($element) {
            // Hide all <b> tags
            $align = $element->find('span[class=align]',0);

            if ($align->rel == 'justify'){
                $element->innertext = '<div class="justify">' . $element->innertext . '<div>';
            }

            if ($align->rel == 'left'){
                $element->innertext = '<div class="left">' . $element->innertext . '<div>';
            }

            if ($align->rel == 'justify'){
                $element->innertext = '<div class="justify">' . $element->innertext . '<div>';
            }
        }

        // Register the callback function with it's function name
        //$dom->set_callback('my_callback');

        $elems = $dom->find('div.start');

        $string = '';

        foreach($elems as $elem){

            $children = $elem->children;

            foreach($children as $el)
            {

                $text    = $el->innertext;
                $pattern = "/<[^\/>]*>([\s]?)*<\/[^>]*>/"; // pattern for removing all empty tags
                $text    = preg_replace($pattern, '', $text);

                $string .= $text;

            }
        }

        // Print it!
        echo $string;

        function globChildren($elems){
            $result   = array();
            $children = $elems->children;

            if($children)
            {
                foreach($children as $child)
                {  $result[$child->tag] =  $child->innertext;}
                $result = array_merge($result,globChildren($child));
            }
            else {  $result[] = [ 'text' => $elems->innertext, 'tag' => $elems->tag ];}
            return $result;
        }


        ?>
    </div>
</div>


</body>
</html>
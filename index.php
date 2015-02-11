<?php

require_once( 'bootstrap/autoload.php' );

use Pond\Parserxml;

$reader = new Parserxml();

$xml  = getcwd().'/files/doc/word/document.xml';

$array = $reader->Init();


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
        .bullet{
            display: inline-block;
            margin-right: 5px;
            height: 5px;
            width: 5px;
            background: #000;
        }
        div.well{
            padding: 20px;
            background: #ccc;
        }
    </style>
</head>
<body>

<div class="container">
	<div class="col-md-10 col-sm-offset-1">
        <?php
        echo($reader->error);
        //echo '<pre>';
        echo '<p>';
        echo($reader->output);
        echo '</p>';
        //echo '</pre>';

        echo '<pre>';
        $reader->extractXMLShow();
        echo '</pre>';
        ?>
	</div>
</div>


</body>
</html>
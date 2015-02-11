<?php

require_once( 'bootstrap/autoload.php' );

use Pond\Parserxml;

$reader = new Parserxml();

$xml  = getcwd().'/files/doc/word/document.xml';

$array = $reader->extractXML($xml);

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
	<div class="col-md-12">
        <?php

        //echo '<pre>';
        echo '<p>';
        echo($reader->output);
        echo '</p>';
        //echo '</pre>';

        ?>
	</div>
</div>


</body>
</html>
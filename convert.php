<?php

require_once( 'bootstrap/autoload.php' );
include('vendor/simple_html_dom.php');
include('vendor/ganon.php');

use PHPHtmlParser\Dom;
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

        // test it!
        $str = file_get_html('http://pond.local/index.php');

        echo $str; // 10


        echo '<pre>';
        //print_r($result);
        echo '</pre>';


        ?>
    </div>
</div>


</body>
</html>
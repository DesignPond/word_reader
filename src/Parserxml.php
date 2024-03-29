<?php namespace Pond;

use Pond\File;
use Chumper\Zipper\Zipper;

class Parserxml{

    /**
     * @var String This is the path to the file that should be read
     * @since 1.0
     */

    var $docxPath = "question.docx";
    /**
     * @var String This is where the ziped contents will be extracted to to process
     * @since 1.0
     */
    var $tempDir = "files/doc";
    /**
     *
     * @var String This is the html data that is returned from this class
     * @since 1.0
     */
    var $output = "";
    /**
     * @var Int This is the maximum width of an image after the process
     * @since 1.0
     * @update 1.2
     */
    var $image_max_width = 0;
    /**
     * @var String The path to where the content is extracted
     * @since 1.0
     */
    var $content_folder = "files/doc";
    /**
     * @var String The current Status of the class
     * @since 1.0
     */
    var $status = "";
    /**
     * @var String The path to where the media files of the document should be extracted
     * @since 1.0
     */
    var $mediaDir = "";
    /**
     * @var String The value of this variable will be prefixed to the path of the image. This class will create a folder 2 levels up, inside an 'upload' folder and this value should go to there.
     * @since 1.0
     */
    var $imagePathPrefix = "";
    /**
     * @var Float The time the scipt took to complete the file parsing
     * @since 1.0
     */
    var $time = 0;
    /**
     * @var Array This contains the relationships of different elements inside the word document and is used to link to the correct image.
     * @since 1.1
     */
    var $rels = array();
    /**
     * @val String The error number generated and the meaning of the error
     * @since 1.0
     */
    var $error = NULL;
    /**
     * @val String This will contain the closing tag of a paragraph level opened tag that can't be specified explicitly
     * @since 1.1
     */
    var $tagclosep = "";
    /**
     * @val String This will contain the closing tag of a text opened tag that can't be specified explicitly
     * @since 1.1
     */
    var $tagcloset = "";
    /**
     * @val Bool SWhould a thumbnail be created as well as to keep the original image in the folder
     * @since 1.3
     */
    var $keepOriginalImage = false;

    protected $zipper;
    /**
     * This function start the timer of this script
     *
     * @global Float $timestart The time the function started, used to calculate script proccessing time
     * @return Bool True when the timer have started
     * @since 1.0
     */
    function timer_start() {
        global $timestart;
        $mtime = explode(' ', microtime() );
        $mtime = $mtime[1] + $mtime[0];
        $timestart = $mtime;
        return true;
    }
    /**
     * This function calculates the difference between when the timer_start was called and the current time
     *
     * @global Float $timestart The time the timer have started
     * @global Float $timeend The time the timer is stopped
     * @param Int $display Should the result be displayed or returned
     * @param Int $precision The amount of decimals to return (after the comma)
     * @return Float Containing the Time since the timer start was called
     * @since 1.0
     */
    function timer_stop($display = 0, $precision = 3) { //if called like timer_stop(1), will echo $timetotal
        global $timestart, $timeend;
        $mtime = microtime();
        $mtime = explode(' ',$mtime);
        $mtime = $mtime[1] + $mtime[0];
        $timeend = $mtime;
        $timetotal = $timeend-$timestart;
        $r = number_format($timetotal, $precision);
        if ( $display )
            echo $r;
        return $r;
    }
    /**
     * This function will set the status to Ready when the class is called. The Constructor Method.
     * @return Bool True when ready
     * @since 1.0
     */
    function __construct(){
        $this->timer_start();
        $this->status = "Ready";

        $this->zipper = new File(new Zipper());

        return true;
    }
    /**
     * This function call the Constructor Method
     * @return Bool True when ready
     * @since 1.0
     */
    function DOCXtoHTML(){
        return __construct();
    }

    /**
     * This function will initialize the process as well as handle the process automatically.
     * This requires that the vars be set to start
     * @return Bool True when successfully completed
     * @since 1.0
     * @modified 1.2.3
     */
    function Init(){

        if($this->testInput()==false){
            $this->time = $this->timer_stop(0);
            $this->error = "11. Not enough information provided to parse the file.";
            return false;
        }
        if($this->UnZipDocx()==false){
            $this->time = $this->timer_stop(0);
            $this->error = "12. The file's contents could not be extracted to use.";
            return false;
        }
        if($this->extractRelXML()==false){
            $this->DeleteTemps();
            $this->time = $this->timer_stop(0);
            $this->error = "13. The file data could not be found or read.";
            return false;
        }
        if($this->extractMedia()==false){
            $this->DeleteTemps();
            $this->time = $this->timer_stop(0);
            $this->error = "14. The Media could not be found.";
            return false;
        }
        if($this->extractXML()==false){
            $this->DeleteTemps();
            $this->time = $this->timer_stop(0);
            $this->error = "15. The file data could not be found or read.";
            return false;
        }
        if($this->DeleteTemps()==false){
            $this->time = $this->timer_stop(0);
            $this->error = "16. The temporary files created during the process could not be deleted. The contents, however, might still have been extracted.";
            return false;
        }

        $this->time = $this->timer_stop(0);
        return true;
    }
    /**
     * This function make sure that the script have everything it needs to continue
     * @return Bool True if everything is ready for script execution
     * @since 1.0
     */
    function testInput(){
        if(!empty($this->docxPath) && $this->status == "Ready" ){
            $this->status = "Starting...";
            return true;
        } else {
            return false;
        }
    }

    public function UnZipDocx(){

        return $this->zipper->extract($this->docxPath);
    }
    /**
     * This function will get a unique directory for the temporary files
     * @return string The first unique directory the function have found
     * @since 1.0
     */
    function getUniqueDir(){
        $targetDir = "./doccontents";
        if(!is_dir($targetDir)){
            return $targetDir;
        }
        $i = 1;
        while(is_dir($targetDir.$i)){
            $i++;
        }
        $i-1;
        return $targetDir.$i;
    }
    /**
     * This function handles the extraction of the XML building the Rels array
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    /**
     * This function handles the extraction of the XML building the Rels array
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    function extractRelXML(){
        $xmlFile = $this->tempDir."/word/_rels/document.xml.rels";

        $xml = file_get_contents($xmlFile);
        if($xml == false){
            return false;
        }
        $xml    = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data   = array();
        xml_parse_into_struct($parser, $xml, $data);
        foreach($data as $value){
            if($value['tag']=="RELATIONSHIP"){
                //it is an relationship tag, get the ID attr as well as the TARGET and (if set, the targetmode)set into var.
                if(isset($value['attributes']['TARGETMODE'])){
                    $this->rels[$value['attributes']['ID']] = array(0 => $value['attributes']['TARGET'], 3=> $value['attributes']['TARGETMODE']);
                } else {
                    $this->rels[$value['attributes']['ID']] = array(0 => $value['attributes']['TARGET']);
                }
            }
        }
        return true;
    }
    /**
     * This function handles the extraction of the Media
     * @return Bool True on success
     * @since 1.1
     * @modified 1.2.3
     */
    function extractMedia(){
        $wordFolder = $this->tempDir."/word/";
        if(!is_dir($wordFolder."media")){
            return true;
            //there are no images to extract
        }
        $this->getMediaFolder();
        $i = false;
        foreach($this->rels as $key => $value){
            if(strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="png" || strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="gif" || strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="jpg"
                || strtolower(pathinfo($value[0],PATHINFO_EXTENSION))=="jpeg"){
                //this really is an image that we are working with
                $fileType = strtolower(pathinfo($value[0],PATHINFO_EXTENSION));
                //set the file type so that the correct image creation function can be called
                if(is_file($wordFolder.$value[0])){
                    if($this->keepOriginalImage == true){
                        $image = $this->processImage($wordFolder.$value[0], $this->image_max_width);
                        $imageorr = $this->processImage($wordFolder.$value[0]);
                    } else {
                        $image = $this->processImage($wordFolder.$value[0], $this->image_max_width);
                        $imageorr = false;
                    }
                    if($image){
                        $i = true;//this have been resourceful, do not return false
                        //the image was successfully created, now write to file
                        $filename = pathinfo($value[0],PATHINFO_BASENAME);
                        if($fileType=="png"){
                            if(imagePng($image,$this->mediaDir."/".$filename,0,PNG_NO_FILTER)){
                                imagedestroy($image);
                                $this->rels[$key][1] = $this->mediaDir."/".$filename;
                            }
                        } elseif($fileType=="gif"){
                            if(imageGif($image,$this->mediaDir."/".$filename,0)){
                                imagedestroy($image);
                                $this->rels[$key][1] = $this->mediaDir."/".$filename;
                            }
                        } else {
                            if(imageJpeg($image,$this->mediaDir."/".$filename,100)){
                                imagedestroy($image);
                                $this->rels[$key][1] = $this->mediaDir."/".$filename;
                            }
                        }
                    }
                    if($imageorr){
                        $i = true;//this have been resourceful, do not return false
                        //the image was successfully created, now write to file
                        $pathinfo = pathinfo($value[0]);
                        $filename = $pathinfo['filename']."_big.".$pathinfo['extension'];
                        if($fileType=="png"){
                            if(imagePng($imageorr,$this->mediaDir."/".$filename,0,PNG_NO_FILTER)){
                                imagedestroy($imageorr);
                                $this->rels[$key][2] = $this->mediaDir."/".$filename;
                            }
                        } elseif($fileType=="gif"){
                            if(imageGif($imageorr,$this->mediaDir."/".$filename,0)){
                                imagedestroy($imageorr);
                                $this->rels[$key][2] = $this->mediaDir."/".$filename;
                            }
                        } else {
                            if(imageJpeg($imageorr,$this->mediaDir."/".$filename,100)){
                                imagedestroy($imageorr);
                                $this->rels[$key][2] = $this->mediaDir."/".$filename;
                            }
                        }
                    }
                }
            }
        }
        return $i;
    }
    /**
     * This function creates the folder that will contain the media after the move
     * @return Bool True on success
     * @since 1.0
     */
    function getMediaFolder(){
        if(empty($this->content_folder)){
            $mediaFolder = pathinfo($this->docxPath,PATHINFO_BASENAME);
            $ext = pathinfo($this->docxPath,PATHINFO_EXTENSION);
            $MediaFolder = strtolower(str_replace(".".$ext,"",str_replace(" ","-",$mediaFolder)));
            $this->mediaDir = "../../uploads/media/".$MediaFolder;
        } else {
            $this->mediaDir = "../../uploads/media/".$this->content_folder;
        }
        if($this->mkdir_p($this->mediaDir)){
            return true;
        } else {
            return false;
        }
    }
    /**
     * This function handles the image proccessing
     * @param String $url Path to the file to proccess
     * @param Int $thumb The maximum width of an proccessed image
     * @return String The binary of the image that was created
     * @since 1.0
     */
    function processImage($url, $thumb=0) {
        $tmp0 = imageCreateFromString(fread(fopen($url, "rb"), filesize( $url )));
        if ($tmp0) {
            if($thumb == 0) {
                $dim = Array ('w' => imageSx($tmp0), 'h' => imageSy($tmp0));
            } else {
                if(imagesx($tmp0)<=$thumb){
                    if (imageSy($tmp0) > imageSx($tmp0)){
                        $dim = Array ('w' => imageSx($tmp0), 'h' => imageSy($tmp0));
                    } else {
                        $dim = Array ('w' => imageSx($tmp0), 'h' => imageSy($tmp0));
                    }
                } else {
                    $dim = Array ('w' => $thumb, 'h' => round(imageSy($tmp0)*$thumb/imageSx($tmp0)));
                }
            }
            $tmp1 = imageCreateTrueColor ( $dim [ 'w' ], $dim [ 'h' ] );
            if ( imagecopyresized  ( $tmp1 , $tmp0, 0, 0, 0, 0, $dim [ 'w' ], $dim [ 'h' ], imageSx ( $tmp0 ), imageSy ( $tmp0 ) ) ) {
                imageDestroy ( $tmp0 );
                return $tmp1;
            } else {
                imageDestroy ( $tmp0 );
                imageDestroy ( $tmp1 );
                return $this -> null;
            }
        } else {
            return $this -> null;
        }
    }
    /**
     * This function handles the extraction of the XML file data used to construct the HTML
     * @return Bool True on success
     * @since 1.0
     * @modified 1.2.3
     */
    function extractXML(){
        $xmlFile = $this->tempDir."/word/document.xml";
        $xml = file_get_contents($xmlFile);
        if($xml == false){
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
        xml_parse_into_struct($parser, $xml, $data);
        //echo "<pre>";
        //print_r($data);
        //echo "</pre>";
        $html4output = "";
        $i = 0;
        while(isset($data[$i])){
            $html4output .= $this->buildhtml($data[$i]);
            $i++;
        }
        $this->output = $html4output;
        $this->status = "Contents Extracted...";
        if(empty($html4output)){
            return false;
        }
        return true;
    }
    /**
     * This function do the actual building of the HTML data string
     * @param Array $data An array containing the data of the XML tag currently proccessed
     * @return string The corresponding HTML for the tag that was proccessed
     * @since 1.0 Modified: 1.2.3
     */
    function buildhtml($data){
        $return = "";
        if(!is_array($data)){
            return $return;
            //the value should be an array otherwise break;
        }
        if($data['type']=="open"){
            //if it is an open tag see if it should be parsed
            switch ($data['tag']) {
                case "W:P"://the paragrah begins
                    $return = "<div class='start'><p>";
                    break;
                case "W:TBL"://the table is initiated
                    $return = "<table border='1'>";
                    break;
                case "W:TR"://the table row is initiated
                    $return = "<tr>";
                    break;
                case "w:numPr"://the table row is initiated
                    $return = "<ul>";
                    break;
                case "W:TC"://the table cell is initiated
                    $return = "<td>";
                    break;
                case "W:HYPERLINK"://the hyperlink is initiated
                    $rid = $data['attributes']['R:ID'];
                    $path = $this->rels[$rid][0];
                    $target = $this->rels[$rid][3];
                    //now determine which type of link it is
                    if(strtolower($target) == "external"){
                        //this is an external link to a website
                        $return = "<a target='_blank' href='".$path."'>";
                    } elseif(isset($data['attributes']['W:ANCHOR'])){
                        $return = "<a class='anchor' href='#".$data['attributes']['W:ANCHOR']."'>";
                    }
                    break;
                default:
                    break;
            }
        }elseif($data['type']=="complete"){
            //if it is an complete tag see if it should be parsed
            switch ($data['tag']) {
                case "W:T":
                    $return = $data['value'].$this->tagcloset;//return the text (add spaces after)
                    $this->tagcloset = "";
                    break;
                case "V:TEXTPATH":
                    $return = $data['attributes']['STRING'];//add word art text (this is also important)
                    break;
                case "A:BLIP"://the image data
                    $rid = $data['attributes']['R:EMBED'];
                    $imagepath    = $this->rels[$rid][1];
                    $imagebigpath = $this->rels[$rid][2];
                    if($this->keepOriginalImage == true){
                        $return = "<a href='".$this->imagePathPrefix.$imagebigpath."' target='_blank' >
                            <img src='".$this->imagePathPrefix.$imagepath."' alt='' />
                            </a>";
                    } else {
                        $return = "<img src='".$this->imagePathPrefix.$imagepath."' alt='' />";
                    }
                    break;
                case "W:PSTYLE"://word styles used for headings etc.
                    if($data['attributes']['W:VAL'] == "Heading1" || $data['attributes']['W:VAL'] == "Titre1"){
                        $return = "<h1>";
                        $this->tagclosep = "</h1>";
                    }elseif($data['attributes']['W:VAL'] == "Heading2" || $data['attributes']['W:VAL'] == "Titre2"){
                        $return = "<h2>";
                        $this->tagclosep = "</h2>";
                    }
                    elseif($data['attributes']['W:VAL'] == "Heading3" || $data['attributes']['W:VAL'] == "Titre3"){
                        $return = "<h3>";
                        $this->tagclosep = "</h3>";
                    }
                    break;
                case "W:B"://word style for bold
                    if($this->tagcloset == "</strong>"){
                        break;
                    }
                    $return = "<strong>";//return the text (add spaces after)
                    $this->tagcloset = "</strong>";
                    break;
                case "W:NUMID"://word style for list
                    if($this->tagcloset == "</span>"){
                        break;
                    }
                    if($data['attributes']['W:VAL'] > 0){
                        $return = "<span rel='list'></span>";//return the text (add spaces after)
                    }
                    break;
                case "W:I"://word style for italics
                    if($this->tagcloset == "</em>"){
                        break;
                    }
                    $return = "<em>";//return the text (add spaces after)
                    $this->tagcloset = "</em>";
                    break;
                case "W:JC"://word style for italics
                    if($data['attributes']['W:VAL'] == "both"){
                        $return = "<div class='text-justify'>";
                        $this->tagclosep = "</div>";
                    }elseif($data['attributes']['W:VAL'] == "left"){
                        $return = "<div class='text-left'>";
                        $this->tagclosep = "</div>";
                    }elseif($data['attributes']['W:VAL'] == "right"){
                        $return = "<div class='text-right'>";
                        $this->tagclosep = "</div>";
                    }
                    break;
                case "W:U"://word style for underline
                    if($this->tagcloset == "</div>"){
                        break;
                    }
                    $return = "<u>";//return the text (add spaces after)
                    $this->tagcloset = "</u>";
                    break;
                case "W:STRIKE"://word style for strike-throughs
                    if($this->tagcloset == "</div>"){
                        break;
                    }
                    $return = "<s>";
                    $this->tagcloset = "</s>";
                    break;
                case "W:IND"://word style for strike-throughs
                    if($this->tagcloset == "</div>"){
                        break;
                    }
                    if(isset($data['attributes']['W:LEFT'])){
                        $return = "<div class='text-indent' rel='".($data['attributes']['W:LEFT']/20)."'>";
                    }
                    else{
                        $return = "<div>";
                    }
                    $this->tagcloset = "</div>";
                    break;
                case "W:VERTALIGN"://word style for super- and subscripts
                    if($data['attributes']['W:VAL'] == "subscript"){
                        $return = "<sub>";
                        $this->tagcloset = "</sub>";
                    }elseif($data['attributes']['W:VAL'] == "superscript"){
                        $return = "<sup>";
                        $this->tagcloset = "</sup>";
                    }
                    break;
                case "W:BOOKMARKSTART"://word style for bookmarks/internal links
                    if($data['attributes']['W:NAME'] != '_GoBack'){
                        $return = "<a id='".$data['attributes']['W:NAME']."'></a>";
                    }
                    break;
                case "MA14:WRAPPINGTEXTBOXFLAG"://word style for bookmarks/internal links
                    if($this->tagcloset == "</div>"){
                        break;
                    }
                    $return = "<div class='well'>";//return the text (add spaces after)
                    $this->tagcloset = "</div>";
                    break;
                default:
                    break;
            }
        }elseif($data['type']=="close"){
            //if it is an close tag see if it should be parsed
            switch ($data['tag']) {
                case "W:P"://the paragraph ends
                    $return = $this->tagclosep."</p></div>";
                    $this->tagclosep = "";
                    break;
                case "W:TC"://the table cell ends
                    $return = "</td>";
                    break;
                case "W:TR"://the table row ends
                    $return = "</tr>";
                    break;
                case "w:numPr"://the table row is initiated
                    $return = "</ul>";
                    break;
                case "W:TBL"://the table ends
                    $return = "</table>";
                    break;
                case "W:HYPERLINK"://the hyperlink ends
                    $return = "</a>";
                    break;
                default:
                    break;
            }
        }
        return $return;
    }
    /**
     * Recursive directory creation based on full path.
     * Will attempt to set permissions on folders.
     * @param string $target Full path to attempt to create.
     * @return bool Whether the path was created or not. True if path already exists.
     * @since 1.0
     */
    function mkdir_p( $target ) {
        // from php.net/mkdir user contributed notes
        $target = str_replace( '//', '/', $target );
        if ( file_exists( $target ) ){
            return @is_dir( $target );
        }
        // Attempting to create the directory may clutter up our display.
        if ( @mkdir( $target ) ) {
            $stat = @stat( dirname( $target ) );
            $dir_perms = $stat['mode'] & 0007777;  // Get the permission bits.
            @chmod( $target, $dir_perms );
            return true;
        } elseif ( is_dir( dirname( $target ) ) ) {
            return false;
        }
        // If the above failed, attempt to create the parent node, then try again.
        if ( ( $target != '/' ) && ( $this->mkdir_p( dirname( $target ) ) ) ){
            return $this->mkdir_p( $target );
        }
        return false;
    }
    /**
     * This function concludes the class by removing all te temporary files and folders as well as unsetting all variables not required
     * @return Bool True on success
     * @since 1.0
     */
    function DeleteTemps(){
        //this function will delete all the temp files except the word document
        //(.docx) itself. If this was uploaded it will be removed when the
        //script terminates
        if(is_dir($this->tempDir)){
            //the temp directory still exist
            //$this->rrmdir($this->tempDir);
            unset($this->content_folder);
            unset($this->docxPath);
            unset($this->imagePathPrefix);
            unset($this->image_max_width);
            unset($this->tempDir);
            unset($this->rels);
            unset($this->tagclosep);
            unset($this->tagcloset);
            return true;
        }
        return false;
    }
    /**
     * This function will remove files and directories recursivly
     * @param String $dir The path to the folder to be removed
     */
    function rrmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (filetype($dir."/".$object) == "dir"){
                        $this->rrmdir($dir."/".$object);
                    } else {
                        unlink($dir."/".$object);
                    }
                }
            }
            reset($objects);
            rmdir($dir);
        }
    }

    public function reading($xmlFile){

        // set location of docx text content file
        $reader = new \XMLReader;
        $reader->open($xmlFile);
        // set up variables for formatting
        $text = ''; $formatting['bold'] = 'closed'; $formatting['italic'] = 'closed'; $formatting['underline'] = 'closed'; $formatting['header'] = 0;
        // loop through docx xml dom
        while ($reader->read()){
            // look for new paragraphs
            if ($reader->nodeType == \XMLREADER::ELEMENT && $reader->name === 'w:p'){
                // set up new instance of XMLReader for parsing paragraph independantly
                $paragraph = new \XMLReader;
                $p = $reader->readOuterXML();
                $paragraph->xml($p);
                // search for heading
                preg_match('/<w:pStyle w:val="(Heading.*?[1-6])"/',$p,$matches);
                switch($matches[1]){
                    case 'Heading1': $formatting['header'] = 1; break;
                    case 'Heading2': $formatting['header'] = 2; break;
                    case 'Heading3': $formatting['header'] = 3; break;
                    case 'Heading4': $formatting['header'] = 4; break;
                    case 'Heading5': $formatting['header'] = 5; break;
                    case 'Heading6': $formatting['header'] = 6; break;
                    default: $formatting['header'] = 0; break;
                }
                // open h-tag or paragraph
                $text .= ($formatting['header'] > 0) ? '<h'.$formatting['header'].'>' : '<p>';
                // loop through paragraph dom
                while ($paragraph->read()){
                    // look for elements
                    if ($paragraph->nodeType == \XMLREADER::ELEMENT && $paragraph->name === 'w:r'){
                        $node = trim($paragraph->readInnerXML());

                        // add <br> tags
                        if (strstr($node,'<w:br ')) $text .= '<br>';

                        // look for formatting tags
                        $formatting['bold'] = (strstr($node,'<w:b/>')) ? (($formatting['bold'] == 'closed') ? 'open' : $formatting['bold']) : (($formatting['bold'] == 'opened') ? 'close' : $formatting['bold']);
                        $formatting['italic'] = (strstr($node,'<w:i/>')) ? (($formatting['italic'] == 'closed') ? 'open' : $formatting['italic']) : (($formatting['italic'] == 'opened') ? 'close' : $formatting['italic']);
                        $formatting['underline'] = (strstr($node,'<w:u ')) ? (($formatting['underline'] == 'closed') ? 'open' : $formatting['underline']) : (($formatting['underline'] == 'opened') ? 'close' : $formatting['underline']);
                        // build text string of doc
                        $text .= (($formatting['bold'] == 'open') ? '<strong>' : '').
                            (($formatting['italic'] == 'open') ? '<em>' : '').
                            (($formatting['underline'] == 'open') ? '<u>' : '').
                            htmlentities(iconv('UTF-8', 'ASCII//TRANSLIT',$paragraph->expand()->textContent)).
                            (($formatting['underline'] == 'close') ? '</u>' : '').
                            (($formatting['italic'] == 'close') ? '</em>' : '').
                            (($formatting['bold'] == 'close') ? '</strong>' : '');
                        // reset formatting variables
                        foreach ($formatting as $key=>$format){
                            if ($format == 'open') $formatting[$key] = 'opened';
                            if ($format == 'close') $formatting[$key] = 'closed';
                        }
                    }
                }
                $text .= ($formatting['header'] > 0) ? '</h'.$formatting['header'].'>' : '</p>';
            }
        }
        $reader->close();

        // suppress warnings. loadHTML does not require valid HTML but still warns against it...
        // fix invalid html
        $doc = new \DOMDocument();
        $doc->encoding = 'UTF-8';
        @$doc->loadHTML($text);
        $goodHTML = simplexml_import_dom($doc)->asXML();

        return $goodHTML;

    }

    function extractXMLShow(){
        $xmlFile = getcwd()."/files/doc/word/document.xml";
        $xml     = file_get_contents($xmlFile);

        if($xml == false){
            return false;
        }

        $xml    = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data   = array();

        xml_parse_into_struct($parser, $xml, $data);

        echo "<pre>";
        print_r($data);
        echo "</pre>";

    }

    function extractRelXMLShow(){

        $xmlFile = getcwd()."/files/unzip/test/word/_rels/document.xml.rels";

        $xml = file_get_contents($xmlFile);
        if($xml == false){
            return false;
        }

        $xml    = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data   = array();
        xml_parse_into_struct($parser, $xml, $data);

        foreach($data as $value){
            if($value['tag']=="RELATIONSHIP"){
                //it is an relationship tag, get the ID attr as well as the TARGET and (if set, the targetmode)set into var.
                if(isset($value['attributes']['TARGETMODE'])){
                    $this->rels[$value['attributes']['ID']] = array(0 => $value['attributes']['TARGET'], 3=> $value['attributes']['TARGETMODE']);
                } else {
                    $this->rels[$value['attributes']['ID']] = array(0 => $value['attributes']['TARGET']);
                }
            }
        }

        echo "<pre>";
        print_r($this->rels);
        echo "</pre>";
    }


}
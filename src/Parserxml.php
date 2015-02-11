<?php namespace Pond;


class Parserxml{

    /**
     * @var String This is the path to the file that should be read
     * @since 1.0
     */
    var $docxPath = "";
    /**
     * @var String This is where the ziped contents will be extracted to to process
     * @since 1.0
     */
    var $tempDir = "";
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
    var $content_folder = "";
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

    public function __construct()
    {
        //$this->reader = $reader;
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

    public function extractXML($xmlFile){

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

    public function buildhtml($data){
        $return = "";

        if(!is_array($data)){
            return $return;
            //the value should be an array otherwise break;
        }

        if($data['type']=="open"){
            //if it is an open tag see if it should be parsed
            switch ($data['tag']) {
                case "W:P"://the paragrah begins
                    $return = "<p>";
                    break;
                case "W:TBL"://the table is initiated
                    $return = "<table border='1'>";
                    break;
                case "W:TR"://the table row is initiated
                    $return = "<tr>";
                    break;
                case "W:TC"://the table cell is initiated
                    $return = "<td>";
                    break;
                case "W:HYPERLINK"://the hyperlink is initiated
                    $rid = $data['attributes']['R:ID'];

                    if(isset($this->rels[$rid])){
                        $path   = $this->rels[$rid][0];
                        $target = $this->rels[$rid][3];
                    }

                    //now determine which type of link it is
                    if(isset($target) && strtolower($target) == "external"){
                        //this is an external link to a website
                        $return = "<a href='".$path."'>";
                    } elseif(isset($data['attributes']['W:ANCHOR'])){
                        $return = "<a href='#".$data['attributes']['W:ANCHOR']."'>";
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
                    $imagepath = $this->rels[$rid][1];
                    $imagebigpath = $this->rels[$rid][2];
                    if($this->keepOriginalImage == true){
                        $return = "<a href='".$this->imagePathPrefix.$imagebigpath."' target='_blank' >
                            <img style='display:inline;' src='".$this->imagePathPrefix.$imagepath."' alt='' />
                            </a>";
                    } else {
                        $return = "<img style='display:inline;' src='".$this->imagePathPrefix.$imagepath."' alt='' />";
                    }
                    break;
                case "W:PSTYLE"://word styles used for headings etc.
                    if($data['attributes']['W:VAL'] == "Heading1"){
                        $return = "<h1>";
                        $this->tagclosep = "</h1>";
                    }elseif($data['attributes']['W:VAL'] == "Heading2"){
                        $return = "<h2>";
                        $this->tagclosep = "</h2>";
                    }elseif($data['attributes']['W:VAL'] == "Heading3"){
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
                case "W:I"://word style for italics
                    if($this->tagcloset == "</em>"){
                        break;
                    }
                    $return = "<em>";//return the text (add spaces after)
                    $this->tagcloset = "</em>";
                    break;
                case "W:U"://word style for underline
                    if($this->tagcloset == "</span>"){
                        break;
                    }
                    $return = "<span style='text-decoration:underline;'>";//return the text (add spaces after)
                    $this->tagcloset = "</span>";
                    break;
                case "W:STRIKE"://word style for strike-throughs
                    if($this->tagcloset == "</span>"){
                        break;
                    }
                    $return = "<span style='text-decoration:line-through;'>";//return the text (add spaces after)
                    $this->tagcloset = "</span>";
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
                    $return = "<a id='".$data['attributes']['W:NAME']."'></a>";
                    break;
                default:
                    break;
            }
        }elseif($data['type']=="close"){
            //if it is an close tag see if it should be parsed
            switch ($data['tag']) {
                case "W:P"://the paragraph ends
                    $return = $this->tagclosep."</p>";
                    $this->tagclosep = "";
                    break;
                case "W:TC"://the table cell ends
                    $return = "</td>";
                    break;
                case "W:TR"://the table row ends
                    $return = "</tr>";
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

    public function extractRelXML(){
        $xmlFile = $this->tempDir."/word/_rels/document.xml.rels";
        $xml = file_get_contents($xmlFile);
        if($xml == false){
            return false;
        }
        $xml = mb_convert_encoding($xml, 'UTF-8', mb_detect_encoding($xml));
        $parser = xml_parser_create('UTF-8');
        $data = array();
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

}
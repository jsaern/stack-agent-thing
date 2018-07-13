<?php
namespace Nrwtaylor\StackAgentThing;

use QR_Code\QR_Code;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

use setasign\Fpdi;

ini_set("allow_url_fopen", 1);

class Lattice
{
    public $var = 'hello';

    public function __construct(Thing $thing, $agent_input = null)
    {
        $this->agent_input = $agent_input;

        $this->agent_name = "lattice";
        $this->agent_prefix = 'Agent "' . ucwords($this->agent_name) . '" ';
        $this->test= "Development code";

        $this->thing = $thing;

        $this->thing_report  = array("thing"=>$this->thing->thing);

        $this->start_time = $this->thing->elapsed_runtime();
        $this->resource_path = $GLOBALS['stack_path'] . 'resources/';

        $command_line = null;

        $this->uuid = $thing->uuid;
        $this->to = $thing->to;
        $this->from = $thing->from;
        $this->subject = $thing->subject;

        $this->node_list = array("snowflake"=>array("snowflake", "uuid"));


        $this->haystack = $thing->uuid .
                $thing->to .
                $thing->subject .
                $command_line .
                        $this->agent_input;

        $this->thing->log($this->agent_prefix . 'running on Thing '. $this->thing->nuuid . '.', "INFORMATION");
        $this->thing->log($this->agent_prefix . 'received this Thing "'.  $this->subject . '".', "DEBUG");

        $this->current_time = $this->thing->json->time();

        // Get some stuff from the stack which will be helpful.
        $this->web_prefix = $thing->container['stack']['web_prefix'];
        $this->mail_postfix = $thing->container['stack']['mail_postfix'];
        $this->word = $thing->container['stack']['word'];
        $this->email = $thing->container['stack']['email'];

        $this->thing->log($this->agent_prefix .'completed init. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->thing->json->setField("variables");
        $time_string = $this->thing->json->readVariable(array("snowflake", "refreshed_at"));

        if ($time_string == false) {
            $this->thing->json->setField("variables");
            $time_string = $this->thing->json->time();
            $this->thing->json->writeVariable(array("snowflake", "refreshed_at"), $time_string);
        }

        $split_time = $this->thing->elapsed_runtime();

        $agent = new Retention($this->thing, "retention");
        $this->retain_to = $agent->retain_to;

        $agent = new Persistence($this->thing, "persistence");
        $this->time_remaining = $agent->time_remaining;
        $this->persist_to = $agent->persist_to;

        $this->thing->log($this->agent_prefix .'got retention. ' . number_format($this->thing->elapsed_runtime() - $split_time) .  'ms.', "OPTIMIZE");

        $this->readSubject();

        $this->init();
        //$this->initSnowflake();
        $this->initLattice();

        //$this->updateSnowflake();

        $this->canvas_size_x = 1640;
        $this->canvas_size_y = 1640;

        $this->draw_center = false;
        $this->draw_outline = false; //Draw hexagon line


        $this->thing->log($this->agent_prefix .'completed getLattice. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->setLattice();
        if ($this->agent_input == null) {
            $this->setSignals();
        }

        $this->thing->log($this->agent_prefix .'completed setSignals.', "OPTIMIZE");

        $this->thing->log($this->agent_prefix .'completed setSnowflake.', "OPTIMIZE");
        $this->thing->log($this->agent_prefix .'ran for ' . number_format($this->thing->elapsed_runtime() - $this->start_time) . 'ms.', "OPTIMIZE");

        $this->thing_report['log'] = $this->thing->log;

        return;
    }

    // https://www.math.ucdavis.edu/~gravner/RFG/hsud.pdf

    // -----------------------


    public function getNuuid()
    {
        $agent = new Nuuid($this->thing, "nuuid");
        $this->nuuid_png = $agent->PNG_embed;
    }

    public function getUuid()
    {
        $agent = new Uuid($this->thing, "uuid");
        $this->uuid_png = $agent->PNG_embed;
    }

    public function timestampLattice($t = null)
    {
        $s = $this->thing->thing->created_at;

        if (!isset($this->retain_to)) {
            $text = "X";
        } else {
            $t  = $this->retain_to;
            $text = "GOOD UNTIL " .  strtoupper(date('Y M d D H:i', $t));
        }
        $this->timestamp = $text;
        return $this->timestamp;
    }

    public function init()
    {
        if (!isset($this->max)) {
            $this->max = 12;
        }
        if (!isset($this->size)) {
            $this->size = 3.7;
        }
        if (!isset($this->lattice_size)) {
            $this->lattice_size = 15;
        }

        $this->initLattice($this->max);
        $this->initSegment();

        $this->setProbability();
        $this->setRules();
    }

    private function setSignals()
    {
        $this->thing->flagGreen();

        $to = $this->thing->from;
        $from = "lattice";
        $this->makePNG();

        $this->thing->log($this->agent_prefix .'completed makePNG. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->makeSMS();

        $this->makeMessage();
        //$this->makeTXT();
        $this->thing->log($this->agent_prefix .'completed makeTXT. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->makeChoices();
        $this->thing->log($this->agent_prefix .'completed makeChoices. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->thing->log($this->agent_prefix .'completed makeWeb. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->thing_report["info"] = "This creates a Lattice.";
        $this->thing_report["help"] = 'Try "UUID SNOWFLAKE"';

        $this->thing->log($this->agent_prefix .'started message. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $message_thing = new Message($this->thing, $this->thing_report);
        $this->thing_report['info'] = $message_thing->thing_report['info'] ;

        $this->makeWeb();
        $this->makeTXT();
        $this->makePDF();

        $this->thing->log($this->agent_prefix .'completed message. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");
        return $this->thing_report;
    }

    public function makeChoices()
    {
        $this->thing->log($this->agent_prefix .'started makeChoices. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->thing->choice->Create($this->agent_name, $this->node_list, "lattice");
        $this->thing->log($this->agent_prefix .'completed create choice. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");

        $this->choices = $this->thing->choice->makeLinks('lattice');
        $this->thing->log($this->agent_prefix .'completed makeLinks. Timestamp = ' . number_format($this->thing->elapsed_runtime()) .  'ms.', "OPTIMIZE");


        $this->thing_report['choices'] = $this->choices;

        //  $this->thing_report['choices'] = false;
    }

    public function makeSMS()
    {
        $cell = $this->lattice[0][0][0];
        //$sms = "SNOWFLAKE | cell (0,0,0) state ". strtoupper($cell['state']);
        $sms = "LATTICE | ";
        $sms .= $this->web_prefix . "thing/".$this->uuid."/lattice";
        $this->sms_message = $sms;
        $this->thing_report['sms'] = $sms;
    }

    public function makeMessage()
    {
        $message = "Stackr made a lattice for you.<br>";

        $uuid = $this->uuid;

        $message .= "Keep on stacking.\n\n<p>" . $this->web_prefix . "thing/$uuid/snowflake.png\n \n\n<br> ";
        $message .= '<img src="' . $this->web_prefix . 'thing/'. $uuid.'/lattice.png" alt="lattice" height="92" width="92">';

        $this->thing_report['message'] = $message;

        return;
    }

    public function setLattice()
    {
//        $this->thing->json->setField("message7");
//        $this->thing->json->writeVariable( array("snowflake", "lattice"), $this->lattice );
        //echo "setSnowflake";
        //var_dump($this->decimal_snowflake);
        $this->thing->json->setField("variables");
        $this->thing->json->writeVariable(array("lattice", "decimal"), $this->decimal_lattice);

        $this->thing->log($this->agent_prefix . ' saved decimal lattice ' . $this->decimal_lattice . '.', "INFORMATION") ;
    }

    public function getLattice()
    {
        $this->thing->json->setField("variables");
        $this->decimal_lattice = $this->thing->json->readVariable(array("lattice", "decimal"));

        if ($this->decimal_lattice == false) {
            $this->thing->log($this->agent_prefix . ' did not find a decimal lattice.', "INFORMATION") ;
            // No snowflake saved.  Return.
            return true;
        }

//        $this->max = 12;
//        $this->size = 3.7;
//        $this->lattice_size = 15;

//        $this->binarySnowflake($this->decimal_snowflake);

        $this->thing->log($this->agent_prefix . ' loaded decimal lattice ' . $this->decimal_lattice . '.', "INFORMATION") ;
        return;
    }

    public function decimalUuid()
    {
        $hex = str_replace("-", "", $this->uuid);

        $dec = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }

        $this->decimal_lattice = $dec;

        return;
    }

    public function binaryUuid()
    {
        $hex = str_replace("-", "", $this->uuid);

        $bin = 0;
        $len = strlen($hex);
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hex2bin($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }

        $this->thing->log($this->agent_prefix . ' loaded decimal lattice ' . $this->decimal_lattice . '.', "INFORMATION") ;
        return;
    }

    public function makeWeb()
    {
        $link = $this->web_prefix . 'thing/' . $this->uuid . '/lattice.pdf';
        $this->node_list = array("web"=>array("snowflake","uuid snowflake"));

        $web = '<a href="' . $link . '">';
        //$web .= '<img src= "' . $this->web_prefix . 'thing/' . $this->uuid . '/snowflake.png">';
        $web .= $this->html_image;
        $web .= "</a>";
        $web .= "<br>";

        $this->timestampLattice($this->retain_to);
        $web .= ucwords($this->timestamp). "<br>";

        $web .= "<br>";
        $web .= $this->decimal_lattice. "<br>";

        $web .= "<br><br>";
        $this->thing_report['web'] = $web;
    }

    public function makeTXT()
    {
        $txt = 'This is a LATTICE';
        $txt .= "\n";
        $txt .= count($this->lattice). ' cells retrieved.';

        $txt .= "\n";
        $txt .= str_pad("COORD (Q,R,S)", 15, ' ', STR_PAD_LEFT);
        $txt .= " " . str_pad("NAME", 10, " ", STR_PAD_LEFT);
        $txt .= " " . str_pad("STATE", 10, " ", STR_PAD_RIGHT);
        $txt .= " " . str_pad("VALUE", 10, " ", STR_PAD_LEFT);

        $txt .= " " . str_pad("COORD (X,Y)", 6, " ", STR_PAD_LEFT);

        $txt .= "\n";
        $txt .= "\n";


        // Centre framed on 0,0,0
        $q_array= array(-2,-1,0,1,2);
        $r_array= array(-2,-1,0,1,2);
        $s_array= array(-2,-1,0,1,2);


        // Run the lattice update/display loops
        foreach ($this->point_list as $point) {
            //    foreach($r_array as $r){
            //        foreach($s_array as $s){
            list($q, $r, $s) = $point;
              
            //$cell = $this->lattice[$q][$r][$s];
            $cell = $this->getCell($q, $r, $s);


            $txt .= " " . str_pad("(".$q.",".$r.",".$s.")", 15, " ", STR_PAD_LEFT);

            $txt .= " " . str_pad($cell['name'], 10, ' ', STR_PAD_LEFT);
            $txt .= " " . str_pad($cell['state'], 10, " ", STR_PAD_LEFT);
            $txt .= " " . str_pad($cell['value'], 10, " ", STR_PAD_RIGHT);

            //$txt .= " " . str_pad($cell['neighbours'], 10, ' ', STR_PAD_LEFT);
            //$txt .= " " . str_pad($cell['p_melt'], 10, " ", STR_PAD_LEFT);
            //$txt .= " " . str_pad($cell['p_freeze'], 10, " " , STR_PAD_RIGHT);



            $txt .= "\n";


            //}
           // }
        }



        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;
    }

    function rgbcolor($r, $g, $b) {

        $this->rgb = imagecolorallocate($this->image, $r, $g, $b);

    }

    public function makePNG()
    {
//$canvas_size = 164;
//$this->canvas_size =164;
        $this->image = imagecreatetruecolor($this->canvas_size_x, $this->canvas_size_y);

        $this->white = imagecolorallocate($this->image, 255, 255, 255);
        $this->black = imagecolorallocate($this->image, 0, 0, 0);
        $this->red = imagecolorallocate($this->image, 255, 0, 0);
        $this->green = imagecolorallocate($this->image, 0, 255, 0);
        $this->grey = imagecolorallocate($this->image, 128, 128, 128);


        imagefilledrectangle($this->image, 0, 0, $this->canvas_size_x, $this->canvas_size_y, $this->white);

        $textcolor = imagecolorallocate($this->image, 0, 0, 0);
        $this->drawLattice($this->canvas_size_x/2, $this->canvas_size_y/2);

        // Write the string at the top left
        $border = 30;
        $r = 1.165;

        $radius = $r * ($this->canvas_size_x - 2 * $border) / 3;


        
        // devstack add path
        //$font = $this->resource_path . '/var/www/html/stackr.test/resources/roll/KeepCalm-Medium.ttf';
        $font = $this->resource_path . 'roll/KeepCalm-Medium.ttf';
        $text = "test";
        // Add some shadow to the text
        //imagettftext($image, 40, 0, 0, 75, $grey, $font, $number);

        $size = $this->canvas_size_x - 90;
        $angle = 0;


        $bbox = imagettfbbox($size, $angle, $font, $text);
        $bbox["left"] = 0- min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["top"] = 0- min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $bbox["width"] = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]) - min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["height"] = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]) - min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        extract($bbox, EXTR_PREFIX_ALL, 'bb');
        //check width of the image
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $pad = 0;

$size = 10;

        imagettftext($this->image, $size, $angle, $width/2-$bb_width/2, $height/2+ $bb_height/2,$this->grey, $font, $text);

        imagestring($this->image, 2, 140, 0, $text, $textcolor);




        // Save the image
        //header('Content-Type: image/png');
        //imagepng($im);
        //xob_clean();

        // https://stackoverflow.com/questions/14549110/failed-to-delete-buffer-no-buffer-to-delete

        if (ob_get_contents()) {
            ob_clean();
        }

        ob_start();
        imagepng($this->image);
        $imagedata = ob_get_contents();
  
        ob_end_clean();

        $this->thing_report['png'] = $imagedata;

        //echo '<img src="data:image/png;base64,'.base64_encode($imagedata).'"/>';
        $response = '<img src="data:image/png;base64,'.base64_encode($imagedata).'"alt="snowflake"/>';

        $this->html_image = '<img src="data:image/png;base64,'.base64_encode($imagedata).'"alt="snowflake"/>';

        $this->PNG_embed = "data:image/png;base64,".base64_encode($imagedata);

//        $this->thing_report['png'] = $image;

//        $this->PNG = $this->image;
        $this->PNG = $imagedata;
        //imagedestroy($this->image);
//        $this->thing_report['png'] = $imagedata;



//        $this->PNG_data = "data:image/png;base64,'.base64_encode($imagedata).'";


        return $response;



        $this->PNG = $image;
        $this->thing_report['png'] = $image;
 
        return;
    }



    public function drawWord($q,$r,$s, $center_x, $center_y,$text, $angle = null, $size = null, $color =null)
    {

        if ($size == null) {$size = 10;}

        list($x_pt, $y_pt) = $this->hextopixel($q, $r, $s, $size);

        // devstack add path
        //$font = $this->resource_path . '/var/www/html/stackr.test/resources/roll/KeepCalm-Medium.ttf';
        $font = $this->resource_path . 'roll/KeepCalm-Medium.ttf';
//        $text = "test";
        // Add some shadow to the text
        //imagettftext($image, 40, 0, 0, 75, $grey, $font, $number);

        $size = $this->canvas_size_x - 90;
        $angle = 0;
$font_size = 6;

        $bbox = imagettfbbox($font_size, $angle, $font, $text);
        $bbox["left"] = 0- min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["top"] = 0- min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $bbox["width"] = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]) - min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["height"] = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]) - min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        extract($bbox, EXTR_PREFIX_ALL, 'bb');
        //check width of the image
        $width = imagesx($this->image);
        $height = imagesy($this->image);
        $pad = 0;

        //$size = 10;

//$font_size = 10;
//var_dump($bbox["height"]);
//exit();
        imagettftext($this->image, $font_size, $angle, $x_pt + $center_x - $bbox["width"] / 2, $y_pt + $center_y+$bbox["height"]/2, $color, $font, $text);

//$font_size = 2;
//        imagestring($this->image, $font_size, $x_pt+$center_x, $y_pt+$center_y, $text, $this->grey);


    }

    public function drawTriangle()
    {
        $pta = array(0,0);
        $ptb = array(sqrt(20),1);
        $ptc = array(20,0);

        imageline($image, 20, 20, 280, 280, $black);
        imageline($image, 20, 20, 20, 280, $black);
        imageline($image, 20, 280, 280, 280, $black);
    }

    public function hex_corner($center_x, $center_y, $x, $y, $i)
    {
        // So this takes a centre co-ordinate
        // and projects a point $size away from it at angle $i.

        $PI=3.14159;
        $angle_deg = 60 * $i   + 30;
        $angle_rad = $PI / 180 * $angle_deg;

        return array($center_x + $x * cos($angle_rad) - sin($angle_rad) * $y,
                 $center_y + $x * sin($angle_rad) + cos($angle_rad) * $y);
    }

    public function hextopixel($r, $g, $b, $s)
    {
        if ($r + $g + $b != 0) {
            return;
        }

        $y = 3/2 * $s * $b;
        // $b = 2/3 * $y / $s
        $x = sqrt(3) * $s * ($b/2 + $r);
        //$x = - sqrt(3) * $s * ( $b/2 + $g )
        //$r = (sqrt(3)/3 * $x - $y/3 ) / $s
        //$g = -(sqrt(3)/3 * $x + $y/3 ) / $s

        return  array($x,$y);
    }

    public function drawHexagon($q, $r, $s, $center_x, $center_y, $angle, $size, $color = null, $label = null)
    {
        if ($color == null) {
            $color = $this->white;
            $color = $this->red; // override colo
        }

        list($x_pt, $y_pt) = $this->hextopixel($q, $r, $s, $size);

        if ($this->draw_center == true) {
            // Draw centre points of hexagons
            imageline($this->image, $center_x+$x_pt, $center_y+$y_pt, $center_x+$x_pt, $center_y+$y_pt, $this->black);
        }


        $arr = array(0, 1, 2, 3, 4, 5);
        list($x_old, $y_old) = $this->hex_corner($x_pt, $y_pt, $size, 0, count($arr)-1);
        $point_array = array();
        foreach ($arr as &$value) {
            list($x, $y) = $this->hex_corner($x_pt, $y_pt, $size, 0, $value);

            $point_array[] = $x+$center_x;
            $point_array[] = $y+$center_y;
            //imageline($this->image, $x+60, $y+60, $x_old+60, $y_old+60, $this->black);

            $x_old = $x;
            $y_old = $y;
        }

        if ($this->draw_outline == true) {
            imagepolygon($this->image, $point_array, count($point_array)/2, $this->black);
        }
        if ($color != $this->white) {

//        $cell = $this->lattice[$q][$r][$s];
        //$value = $cell['value'];
            $red = 155;
            $green = 183;
            $blue = 217;
            $this->rgbcolor(rand($red-20,$red+10),rand($green-10,$green+10),rand($blue-40, $blue-+20));
            // Need consistency from image to image
            $this->rgbcolor(155,183,217);

            $this->rgbcolor(255,255,255);

            //imagefilledpolygon($this->image, $point_array, count($point_array)/2, $color);
            imagefilledpolygon($this->image, $point_array, count($point_array)/2, $this->rgb);

            $this->rgbcolor(20,20,20);
            imagepolygon($this->image, $point_array, count($point_array)/2, $this->rgb);
        }
/*
        $label = "(" . $q . ", " . $r . ", " . $s . ")";;
        if ($label != null) {

            imagestring($this->image, 2, $x_pt+$center_x, $y_pt+$center_y, $label, $textcolor);
            $this->drawWord($label, $x_pt+ $center_x, $y_pt + $center_y);

        }
*/

    }

    public function setProbability()
    {
        $type = 'preset';

        $this->thing->log($this->agent_prefix . 'using probability set "'.  strtoupper($type) . '".', "DEBUG");


        switch ($type) {
        case 'preset':
            $this->p_freeze = array(1, 0.2, 0.1, 0, 0.2, 0.1, 0.1, 0, 0.1, 0.1, 1, 1, 0);
            $this->p_melt = array(0, 0.7, 0.5, 0.5, 0, 0, 0, 0.3, 0.5, 0, 0.2, 0.1, 0);
            break;
        case 'random':
            $this->p_melt = array();
            $this->p_freeze = array();
            foreach (range(1, 13) as $t) {
                $this->p_melt[$t] = rand(0, 1000)/1000;
                $this->p_freeze[$t] = rand(0, 1000)/1000;
            }
            break;
        case 'uuid':
            $s = $this->uuid;
            $s = strtolower(str_replace("-", "", $s));

            foreach (range(0, strlen($s), 2) as $i) {
                $melt = $this->hextodec($s[$i]);
                $freeze = $this->hextodec($s[$i+1]);
                $this->p_melt[$i/2] = $melt/15;
                $this->p_freeze[$i/2] = $freeze/15;
            }
            break;
        }
    }

    public function hextodec($value)
    {
        $n = $value;

        if ($value == 'a') {
            $n = 10;
        }
        if ($value == 'b') {
            $n = 11;
        }
        if ($value == 'c') {
            $n = 12;
        }
        if ($value == 'd') {
            $n = 13;
        }
        if ($value == 'e') {
            $n = 14;
        }
        if ($value == 'f') {
            $n = 15;
        }

        return $n;
    }

    public function setRules()
    {
        $this->rules = array();
        $this->rules[0][0][0][0][0][1] = 1;
        $this->rules[0][0][0][0][1][1] = 2;
        $this->rules[0][0][0][1][0][1] = 3;
        $this->rules[0][0][0][1][1][1] = 4;
        $this->rules[0][0][1][0][0][1] = 5;
        $this->rules[0][0][1][0][1][1] = 6;
        $this->rules[0][0][1][1][0][1] = 7;
        $this->rules[0][0][1][1][1][1] = 8;
        $this->rules[0][1][0][1][0][1] = 9;
        $this->rules[0][1][0][1][1][1] = 10;
        $this->rules[0][1][1][0][1][1] = 11;
        $this->rules[0][1][1][1][1][1] = 12;
        $this->rules[1][1][1][1][1][1] = 13;
    }

    public function getProb($s)
    {
        foreach (range(0, 5) as $i) {
            $a = $i % 6;
            $b = ($i + 1) % 6;
            $c = ($i + 2) % 6;
            $d = ($i + 3) % 6;
            $e = ($i + 4) % 6;
            $f = ($i + 5) % 6;

            if ((isset($this->rules[$s[$a]][$s[$b]][$s[$c]][$s[$d]][$s[$e]][$s[$f]])) or
                (isset($this->rules[$s[$f]][$s[$e]][$s[$d]][$s[$c]][$s[$b]][$s[$a]]))) {
                $n = $this->rules[$s[$a]][$s[$b]][$s[$c]][$s[$d]][$s[$e]][$s[$f]];
                break;
            } else {
                $n = rand(3, 8);
//                $n = 13;
            }
        }
        //echo " p = " .$n

        // So we are supposed to use rule N for
        // finding the probability of melting
        // and freezing to the cell.

        $p_melt = $this->p_melt[$n-1];
        $p_freeze = $this->p_freeze[$n-1];

        return array($n, $p_melt,$p_freeze);
    }

    public function initLattice()
    {
        $this->thing->log($this->agent_prefix . 'initialized the lattice.', "INFORMATION");

//        $this->lattice_size = $n;
        $n = $this->lattice_size;
        ;
        //$this->lattice_size = $n;

        $this->lattice = array();

        $value= array("name"=>null, "state"=>null, "value"=>0);

        foreach (range(-$n, $n) as $q) {
            foreach (range(-$n, $n) as $r) {
                foreach (range(-$n, $n) as $s) {
                    //foreach($point_list as $point) {
                    //    list($q,$r,$s) = $point;
                    $this->lattice[$q][$r][$s] = $value;
                    //array($q=>array($r=>array($s=>$value)));
                }
            }
        }
    

        //$this->lattice[-1][0][0] = array("name"=>"seed", "state"=>"on", "value"=>.5);
        $this->lattice[0][0][0] = array("name"=>"seed", "state"=>"on", "value"=>.5);
        //$this->lattice[1][-2][1] = array("name"=>"seed", "state"=>"on", "value"=>.5);
    }

    public function getCell($q, $r, $s)
    {

  // $cell = true;

        if (($q > $this->lattice_size) or
            ($q < -$this->lattice_size) or
            ($r > $this->lattice_size) or
            ($r < -$this->lattice_size) or
            ($s > $this->lattice_size) or
            ($s < -$this->lattice_size)) {
            $cell = array('name'=>'boundary', 'state'=>'off', 'value'=>0); // red?
        } else {
            if (isset($this->lattice[$q][$r][$s])) {
                $cell = $this->lattice[$q][$r][$s];
            } else {
                // Flag an error;
                $cell = array('name'=>"bork", 'state'=>'off', 'value'=>true);
            }
        }

        return $cell;
    }

    public function updateCell($q, $r, $s)
    {
        // Process the cell;
        // Because CA is 3D spreadsheets.
        //$q_array= array(-1,1);
        //$r_array= array(-1,1);
        //$s_array= array(-1,1);

        //$cell_value = 0;

        // Build a list of the state of the surrounding cells.

        $cell = $this->getCell($q, $r, $s);



        $states = array();
        $i = 0;
        foreach (range(-1, 1, 2) as $q_offset) {
            foreach (range(-1, 1, 2) as $r_offset) {
                foreach (range(-1, 1, 2) as $s_offset) {
                    $neighbour_cell = $this->getCell($q+$q_offset, $r+$r_offset, $s+$s_offset);

                    if ($neighbour_cell['state'] == 'on') {
                        $states[$i] = 1;
                    } else {
                        $states[$i] = 0;
                    }
                    $i += 1;
                }
            }
        }

        // Perform some calculation here on $states,
        // to determine what state the current cell should be in.

        list($n, $p_melt, $p_freeze)  = $this->getProb($states);


        $cell['neighbours'] = $states[0] . ' ' . $states[1] .' ' . $states[2] .' ' .$states[3] . $states[4] .' ' .$states[5];

        $cell['p_melt'] = $p_melt;
        $cell['p_frozen'] = $p_freeze;

        if ($p_melt < $p_freeze) {
            $cell['state'] = 'on';
        } else {
            $cell['state'] = 'off';
        }


        //if (rand(0,10)/10 > .3) {
        //    $cell['state'] ='off';
        //} else {
        //    $cell['state'] = 'on';
        //}


        // Then set lattice value
        $this->lattice[$q][$r][$s] = $cell;
    }

    public function wordLattice()
    {
        $this->decimal_lattice = null;
/*
        $s ="";

        foreach ($this->lattice_points as $point) {
            $s .= $point;
        }
*/
//        $this->word_lattice= bindec($s);
        return $this->decimal_lattice;
    }



    public function decimalLattice()
    {
        $s ="";
        foreach ($this->lattice_points as $point) {
            $s .= $point;
        }
        $this->decimal_lattice= bindec($s);
        return $this->decimal_lattice;

    }

/*
    public function binarySnowflake($dec)
    {
        if ($dec == null) {
            $dec = $this->decimal_snowflake;
        }

        $Input = $dec;
        $Output='';
        if (preg_match("/^\d+$/", $Input)) {
            while ($Input!='0') {
                $Output.=chr(48+($Input{strlen($Input)-1}%2));
                $Input=BCDiv($Input, '2');
            }
            $Output=strrev($Output);
        }

        //var_dump($Output);
        //exit();
        $this->binary_snowflake = $Output;
        //      $this->binary_snowflake= decbin($dec);
        return $this->binary_snowflake;
    }
*/

    public function initSegment()
    {
        $this->thing->log($this->agent_prefix . 'initialized the segment.', "INFORMATION");

        $this->point_list = array();

        foreach (range(0, $this->max) as $a) {
            foreach (range(0, $this->max) as $b) {

//            foreach (range(0, $a-3) as $b) {
                if (!(($a-$b) > $a)) {
                    //echo $a-$b . " " .-$a . " " .$b . "---" . ( ($a-$b) > $a) . "<br>";
//                    $this->point_list[] = array($a-$b, -$a, $b);
                    $this->point_list[] = array($a-$b, -$a, $b);

                }
            }
        }
    }

    public function updateLattice()
    {
        $this->thing->log($this->agent_prefix . 'updated the snowflake.', "INFORMATION");


        foreach ($this->point_list as $point) {
            list($q, $r, $s) = $point;
            $this->updateCell($q, $r, $s);
        }
    }

    public function drawLattice($q = null, $r = null, $s = null, $size = null, $index = 0)
    {

        if (!isset($this->lattice)) {$this->getLattice();}

        $this->split_time = $this->thing->elapsed_runtime();

        $index += 1; // Track for recursion
        if ($index >= 2) {
            return;
        }

        if ($q == null) {
            $q=0;
        }
        if ($r == null) {
            $r=0;
        }
        if ($s == null) {
            $s=0;
        }

        if ($size == null) {
            $size=$this->size;
        }

        $this->lattice_points = array();
        foreach ($this->point_list as $point) {
            list($q, $r, $s) = $point;

            //   $this->updateCell($q,$r,$s);

            // Gives any cell value
            $cell = $this->lattice[$q][$r][$s];

            $color = $this->black;
            if ($cell['state'] == 'on') {
                $color = $this->grey;
                $this->lattice_points[] = 1;
            } else {
                $color = $this->green;
                $this->lattice_points[] = 0;
            }

            //if ($cell['name'] == 'boundary') {$color = $this->black;}

            if ($index == 2) {
                $color=$this->green;
            }
            // Draw out the state

            $center_x = 164/2;
            $center_y = 164/2;
            $angle = 0;

//                    foreach(range(0,5) as $i) {
//                        $x = $size * 6;
//                        $y = 0;

//                    list($x_next, $y_next) = $this->hex_corner($center_x, $center_y, $x, $y, $i);
//                    $angle = $i/5 * 3.14159;

            // Draw an individual hexagon (q,r,s) centred at at an angle and distance from (x,y)

            $this->drawHexagon($q, $r, $s, $center_x, $center_y, $angle, $size, $color);

        $label = "(" . $q . ", " . $r . ", " . $s . ")";;
        //if ($label != null) {

            //imagestring($this->image, 2, $x_pt+$center_x, $y_pt+$center_y, $label, $textcolor);
            $this->drawWord($q, $r, $s, $center_x, $center_y, $label, $angle, $size, $this->grey);

        //}


            //$this->drawHexagon(-1*$r, -1*$s, -1*$q, $center_x, $center_y, $angle, $size, $color);

            //$this->drawHexagon($r, $q, $s, $center_x, $center_y, $angle, $size, $color);
            //$this->drawHexagon(-1*$s, -1*$r, -1*$q, $center_x, $center_y, $angle, $size, $color);

            //$this->drawHexagon($q, $s, $r, $center_x, $center_y, $angle, $size, $color);
            //$this->drawHexagon(-$q, -$r, -$s, $center_x, $center_y, $angle, $size, $color);

            //$this->drawHexagon(-1*$s, -1*$q, -1*$r, $center_x, $center_y, $angle, $size, $color);
            //$this->drawHexagon(-1*$q, -1*$s, -1*$r, $center_x, $center_y, $angle, $size, $color);

            //$this->drawHexagon($s, $r, $q, $center_x, $center_y, $angle, $size, $color);
            //$this->drawHexagon($r, $s, $q, $center_x, $center_y, $angle, $size, $color);

            //$this->drawHexagon(-$r, -$q, -$s, $center_x, $center_y, $angle, $size, $color);
            //$this->drawHexagon($s, $q, $r, $center_x, $center_y, $angle, $size, $color);



//                    $this->drawSnowflake($q,$r,$s,$index);
//                    // Which eventually becomes recursively $this->drawSnowflake(...)
        }

//var_dump($this->decimal_lattice);
//echo "drawLattice";
//exit();
        $this->wordLattice();

        $this->thing->log($this->agent_prefix .'drew a lattice in ' . number_format($this->thing->elapsed_runtime() - $this->split_time) . 'ms.', "OPTIMIZE");
        $this->thing->log($this->agent_prefix . 'drew an lattice.', "INFORMATION");


        return;
    }


    public function read()
    {
        //$this->thing->log("read");

//        $this->get();
        return $this->state;
    }



    /*
        function getRoll($input)
        {
            if (!isset($this->rolls)) {
                $this->rolls = $this->extractRolls($input);
            }
    //var_dump($this->rolls);
    
            if (count($this->rolls) == 1) {
                $this->roll = $this->rolls[0];
                return $this->roll;
          }
    
            if (count($this->rolls) == 0) {
                $this->roll = "d6";
                return $this->roll;
          }
    
    
            $this->roll = false;
    
            //array_pop($arr);
    //exit();
            return false;
        }
    
    
        function extractRolls($input)
        {
            if (!isset($this->rolls)) {
                $this->rolls = array();
            }
    
    //Why not combine them into one character class? /^[0-9+#-]*$/ (for matching) and /([0-9+#-]+)/ for capturing ?
            $pattern = "|^(\\d)?d(\\d)(\\+\\d)?$|";
            //$pattern = "|[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}|";
            $pattern = '/([0-9d+]+)/';
            preg_match_all($pattern, $input, $m);
    
            $arr = $m[0];
            //array_pop($arr);
            $this->rolls = $arr;
    
    //$var_dump($this->rolls);
    //exit();
    
            return $this->rolls;
    
    
        }
    
    
        function dieRoll($die_N = 6, $modifier = 0) {
    
            $d = rand(1, $die_N);
            $roll = $d + $modifier;
    
            return $roll;
        }
    
    */

    public function makePDF()
    {
        try {
            // initiate FPDI
            $pdf = new Fpdi\Fpdi();

            $pdf->setSourceFile($this->resource_path . 'snowflake/bubble.pdf');
            $pdf->SetFont('Helvetica', '', 10);

            $tplidx1 = $pdf->importPage(1, '/MediaBox');
            $pdf->addPage();
            $pdf->useTemplate($tplidx1, 0, 0, 215);
            $this->getNuuid();
            $pdf->Image($this->nuuid_png, 5, 18, 20, 20, 'PNG');

            $pdf->Image($this->PNG_embed, 5, 5, 400, 400, 'PNG');
            //$pdf->Image($this->PNG_embed, 5, 5, 20, 20, 'PNG');

            //$pdf->Image($this->PNG_embed, 5, 5, 5+$this->canvas_size_x, 5 + $this->canvas_size_y, 'PNG');

            // $pdf->SetTextColor(0,0,0);
            // $pdf->SetXY(50, 50);
            // $t = $this->thing_report['sms'];
            // $pdf->Write(0, $t);

            // Page 2
            $tplidx2 = $pdf->importPage(2);

            $pdf->addPage();
            $pdf->useTemplate($tplidx2, 0, 0);
            // Generate some content for page 2

            $pdf->SetFont('Helvetica', '', 10);
            $this->txt = "".$this->uuid.""; // Pure uuid.
//            $this->getUuid();
//            $pdf->Image($this->uuid_png, 175, 5, 30, 30, 'PNG');

            $pdf->SetTextColor(0, 0, 0);
//        $pdf->SetXY(15, 10);
//        $t = $this->web_prefix . "thing/".$this->uuid;
//        $t = $this->uuid;

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(15, 10);
            $t = $this->thing_report['sms'];


            $pdf->Write(0, $t);

            $pdf->SetXY(15, 15);
            $text = $this->timestampLattice();
            $pdf->Write(0, $text);

            $text = "Pre-printed text and graphics (c) 2018 Stackr Interactive Ltd";
            $pdf->SetXY(15, 20);
            $pdf->Write(0, $text);

            if (ob_get_contents()) {
                ob_clean();
            }

            ob_start();
            $image = $pdf->Output('', 'I');
            $image = ob_get_contents();
            ob_clean();

            $this->thing_report['pdf'] = $image;

        } catch (Exception $e) {
            echo 'Caught exception: ',  $e->getMessage(), "\n";
        }


        return $this->thing_report['pdf'];
    }

    public function readSubject()
    {
        $input = strtolower($this->subject);

        $pieces = explode(" ", strtolower($input));

        if (count($pieces) == 1) {
            if ($input == 'lattice') {
                $this->getLattice();

                if ((!isset($this->decimal_lattice)) or
                    ($this->decimal_lattice == null)) {
                    $this->decimal_lattice = rand(1, rand(1, 10)*1e11);
                }

                $this->wordLattice();
                $p = strlen($this->decimal_lattice);

                $this->max = 13;
                $this->size = 4;
                $this->lattice_size = 40;
                return;
            }
        }

        $keywords = array("uuid","iterate");
        foreach ($pieces as $key=>$piece) {
            foreach ($keywords as $command) {
                if (strpos(strtolower($piece), $command) !== false) {
                    switch ($piece) {

                        case 'word':
                              $this->max = sqrt(128) + 6;
                              //$this->max = 24;

                              $this->size = 2.5;
                              $this->lattice_size = 40;
                            $this->wordLattice();

                            return;


                        case 'uuid':
                              $this->max = sqrt(128) + 6;
                              //$this->max = 24;

                              $this->size = 2.5;
                              $this->lattice_size = 40;
                            $this->uuidLattice();

                            return;

                        case 'iterate':
                            $this->thing->log($this->agent_prefix . 'received a command to update the snowflake.', "INFORMATION");
                            $this->updateLattice();
                            return;

                        case 'on':
                            //$this->setFlag('green');
                            //break;


                        default:
                     }
                }
            }
        }



        $this->getLattice();

        if ((!isset($this->decimal_lattice)) or
            ($this->decimal_lattice == null)) {
            $this->decimal_lattice = rand(1, rand(1, 10)*1e11);
        }

        $this->wordLattice();
//        $p = strlen($this->binary_snowflake);

        $this->max = 13;
        $this->size = 30;
        $this->lattice_size = 40;
        return;

        if (strpos($input, 'uuid') !== false) {
            //    $this->uuidSnowflake();
        }

        if ($this->agent_input == "lattice iterate") {
            $this->thing->log($this->agent_prefix . 'received a command to update the snowflake.', "INFORMATION");
            $this->updateLattice();
            return;
        }

        return;
    }
}

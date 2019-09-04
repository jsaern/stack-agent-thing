<?php
/**
 * Chatbot.php
 *
 * @package default
 */


namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

use setasign\Fpdi;

ini_set("allow_url_fopen", 1);

class Chatbot extends Agent
{
    public $var = 'hello';

    /**
     *
     */
    public function run() {
        $this->getCard();
    }


    /**
     *
     */
    public function get() {

        // Borrow this from iching
        $this->thing->json->setField("variables");
        $time_string = $this->thing->json->readVariable( array("chatbot", "refreshed_at") );

        if ($time_string == false) {
            $this->thing->json->setField("variables");
            $time_string = $this->thing->json->time();
            $this->thing->json->writeVariable( array("chatbot", "refreshed_at"), $time_string );
        }

        $this->refreshed_at = strtotime($time_string);


        $this->thing->json->setField("variables");
        $this->nom = strtolower($this->thing->json->readVariable( array("chatbot", "nom") ));
        $this->number = $this->thing->json->readVariable( array("chatbot", "number") );
        $this->suit = $this->thing->json->readVariable( array("chatbot", "suit") );

        if ( ($this->nom == false) or ($this->number == false) or ($this->suit == false) ) {

            $this->readSubject();

            $this->thing->json->writeVariable( array("chatbot", "nom"), $this->nom );
            $this->thing->json->writeVariable( array("chatbot", "number"), $this->number );
            $this->thing->json->writeVariable( array("chatbot", "suit"), $this->suit );


            $this->thing->log($this->agent_prefix . ' completed read.', "OPTIMIZE") ;
        }

    }


    /**
     *
     */
    public function set() {
$this->respond();
    }


    /**
     *
     * @param unknown $input (optional)
     * @return unknown
     */
    public function extractChatbots($input = null) {

        if (!isset($this->chatbot_names)) {
            $this->chatbot_names = array();
        }

        if (!isset($this->cast)) {$this->getCast();}



        foreach ($this->cast as $index=>$chatbot) {
            //            $place_name = strtolower($place['name']);
            //            $place_code = strtolower($place['code']);
            $chatbot_name = strtolower($chatbot['name']);

            if (empty($chatbot_name)) {continue;}
            //            if (empty($place_code)) {continue;}

            // Thx. https://stackoverflow.com/questions/4366730/how-do-i-check-if-a-string-contains-a-specific-$
            if (strpos($input, $chatbot_name) !== false) {
                $this->chatbot_names[] = $chatbot_name;
            }


        }
        $this->chatbot_names = array_unique($this->chatbot_names);

        return array($this->chatbot_names);
    }


    /**
     *
     * @param unknown $input (optional)
     * @return unknown
     */
    public function filterChatbots($input = null) {

        if (!isset($this->chatbot_names)) {
            return;
        }

        if (!isset($this->cast)) {$this->getCast();}

        $this->chatbot_aliases = $this->chatbot_names;

        foreach ($this->chatbot_aliases as $chatbot_alias) {

            $this->chatbot_aliases[] = '@'.$chatbot_alias;
        }

        usort($this->chatbot_aliases, function($a, $b) {
                return strlen($b) <=> strlen($a);
            });


        $this->filtered_input = trim(str_replace($this->chatbot_aliases, '' , $input));

        return $this->filtered_input;
    }


    /**
     *
     * @param unknown $selector (optional)
     * @return unknown
     */
    function getChatbot($selector = null) {
        foreach ($this->cast as $index=>$chatbot) {
            // Match the first matching place

            if (($selector == null) or ($selector == "")) {
                $this->refreshed_at = $this->last_refreshed_at; // This is resetting the count.
                $this->chatbot_name = $this->last_chatbot_name;

                $this->chatbot_variables = new Variables($this->thing, "variables " . $this->chatbot_name . " " . $this->from);
                return array($this->chatbot_name);
            }

            if ($chatbot['name'] == $selector) {
                $this->refreshed_at = $chatbot['refreshed_at'];
                $this->place_name = $chatbot['name'];
                $this->chatbot_name = new Variables($this->thing, "variables " . $this->chatbot_name . " " . $this->from);
                return array($this->chatbot_name);
            }
        }
        return true;
    }



    /**
     *
     */
    function getNuuid() {
        $agent = new Nuuid($this->thing, "nuuid");
        $this->nuuid_png = $agent->PNG_embed;
    }


    /**
     *
     */
    function getUuid() {
        $agent = new Uuid($this->thing, "uuid");
        $this->uuid_png = $agent->PNG_embed;
    }


    /**
     *
     * @param unknown $txt (optional)
     */
    function getQuickresponse($txt = "qr") {
        $agent = new Qr($this->thing, $txt);
        $this->quick_response_png = $agent->PNG_embed;
    }


    /**
     *
     */
    function init() {
        // Charley variables

        $this->unit = "";
        $this->node_list = array();

        if (!isset($this->channel_count)) {$this->channel_count = 2;}
        if (!isset($this->volunteer_count)) {$this->volunteer_count = 3;}
        if (!isset($this->food)) {$this->food = "X";}

        // $this->setProbability();
        // $this->setRules();
    }


    /**
     *
     * @return unknown
     */
    public function respond() {

        //        $this->getResponse($this->nom, $this->suit);

        $this->thing->flagGreen();

        $to = $this->thing->from;
        $from = "chatbot";

        $this->makePNG();

        $this->makeSMS();

        $this->makeMessage();
        // $this->makeTXT();
        $this->makeChoices();

        $this->thing_report["info"] = "This creates an exercise message.";
        $this->thing_report["help"] = 'Try NONSENSE.';

        $message_thing = new Message($this->thing, $this->thing_report);
        $this->thing_report['info'] = $message_thing->thing_report['info'] ;
        $this->makeWeb();

        $this->makeTXT();
        $this->makePDF();

        return $this->thing_report;
    }


    /**
     *
     */
    function makeChoices() {
        $this->thing->choice->Create($this->agent_name, $this->node_list, "chatbot");
        $this->choices = $this->thing->choice->makeLinks('chatbot');

        $this->thing_report['choices'] = $this->choices;
    }


    /**
     *
     */
    function makeSMS() {
        $sms = "CHATBOT\n";

        $sms .= $this->response;

        //$sms .= "\n";
        //$sms .= "TEXT WEB";

        $this->sms_message = $sms;
        $this->thing_report['sms'] = $sms;
    }


    /**
     *
     */
    function getCast() {
        // Load in the cast. And roles.
        $file = $this->resource_path .'/chatbot/chatbot.txt';
        $contents = file_get_contents($file);

        $handle = fopen($file, "r");

        if ($handle) {
            while (($line = fgets($handle)) !== false) {

                $person_name = $line;
                $arr = explode(",", $line);
                $name= trim($arr[0]);
                if (isset($arr[1])) {$role = trim($arr[1]);} else {$role = "X";}

                // Unique name <> Role mappings. Check?
                $this->name_list[$role] = $name;
                $this->role_list[$name] = $role;

                //$this->placename_list[] = $place_name;
                $this->cast[] = array("name"=>$name, "role"=>$role);
            }

            fclose($handle);
        } else {
            // error opening the file.
        }
    }


    /**
     *
     * @param unknown $text (optional)
     * @return unknown
     */
    function isChatbot($text = null) {

        $selector = $text;
        foreach ($this->cast as $index=>$chatbot) {
            // Match the first matching place

            if ($chatbot['name'] == $selector) {
                return true;
            }
            if ("@".$chatbot['name'] == $selector) {
                return true;
            }

        }
        return false;
    }


    /**
     *
     * @param unknown $role (optional)
     * @return unknown
     */
    function getName($role = null) {
        if (!isset($this->name_list)) {$this->getCast();}

        if ($role == "X") {$this->chatbot = "Rocky"; return;}

        $this->chatbot = array_rand(array("Chatbot"));
        $input = array("Charlie", "Charley", "Charles", "Charlene", "Charlize", "Carl", "Karl", "Carlos", "Caroline", "Charlotte");

        // Pick a random Charles.

        $chatbot_index = $this->refreshed_at % count($input);
        $this->chatbot = $input[$chatbot_index];

        if (isset($this->name_list[$role])) {$this->chatbot = $this->name_list[$role];}

        return $this->chatbot;
    }


    /**
     *
     * @param unknown $nom
     * @param unknown $suit
     */
    function getResponse($nom, $suit) {

        $this->response = "Looked for an agent.";

        if (isset($this->response)) {return;}
        $this->getCards();


        $this->getCard();


        $this->response = $this->text;

    }


    /**
     *
     */
    function getCards() {
        if (isset($this->cards)) {return;}

        // Load in the cast. And roles.
        $file = $this->resource_path .'/chatbot/messages.txt';
        $contents = file_get_contents($file);

        $handle = fopen($file, "r");

        $this->cards = array();

        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                $arr = explode(",", $line);

                $nom = $arr[0]; // Describer of the card
                $suit = trim($arr[1]);
                $number = trim($arr[2]);
                $text = trim($arr[3]);

                $from = "X";
                if (isset($arr[4])) {$from = trim($arr[4]);}

                $to = "X";
                if (isset($arr[5])) {$to = trim($arr[5]);}

                $this->texts[$nom][$suit] = $text;
                $this->numbers[$nom][$suit] = $number;


                $this->card_list[] = array("nom"=>$nom, "suit"=>$suit, "number"=>$number, "text"=>$text, "from"=>$from, "to"=>$to );

                $this->cards[$nom][$suit] = array("nom"=>$nom, "suit"=>$suit, "number"=>$number, "text"=>$text, "from"=>$from, "to"=>$to );


            }

            fclose($handle);
        } else {
            // error opening the file.
        }
    }


    /**
     *
     */
    function getCard() {
        $this->getCards();

        if (($this->nom == false) or ($this->suit == false)) {
            $this->card = $this->card_list[array_rand($this->card_list)];

            $this->nom = $this->card['nom'];
            $this->suit = $this->card['suit'];
            $this->number = $this->card['number'];

        }

        $this->card = $this->cards[$this->nom][$this->suit];

        $this->text = $this->card['text'];

        if ($this->text == "ROCKY") {$this->text = "Send a ROCKY inject to the last station.";}

        $this->role_from = $this->card['from'];
        $this->role_to = $this->card['to'];

        if ($this->number == "X") {$this->number = "REPORT";}
        if ($this->number == 0.5) {$this->number = "HALVE";}

        if (is_numeric($this->number)) {
            if ($this->number < 0) {$this->number = "SUBTRACT " . abs($this->number);}
            if ($this->number > 0) {$this->number = "ADD " . $this->number;}
            //if ($this->number == 0) {$this->number = "BINGO";}
        }

        $this->fictional_to = $this->getName($this->role_to);
        $this->fictional_from = $this->getName($this->role_from);

        //        $this->response = "to: " . $this->fictional_to . ", " . $this->role_to . " from: " . $this->fictional_from . ", " . $this->role_from . " / " . $this->text . " / " . $this->number . " " . $this->unit . ".";
        //        $this->response = "TO " . $this->fictional_to .
        //             ", " . $this->role_to . "\nFROM " .
        //            $this->fictional_from . ", " . $this->role_from . "\n" .
        //             "INJECT " . $this->text . "\n" . $this->number . " " . $this->unit . ".";


        if (($this->role_to == "X") and ($this->role_from == "X") and ($this->text == "X")) {
            //            $this->response = $this->number . " " . $this->unit . ".";
        }

        if (($this->role_to == "X") and ($this->role_from == "X") and ($this->text != "X")) {
            //            $this->response = $this->text . "\n" . $this->number. " " . $this->unit . ".";
        }

        if (($this->role_to == "X") and ($this->role_from != "X") and ($this->text != "X")) {
            //            $this->response = "to: < ? >" . " from: " . $this->fictional_from .  ", " . $this->role_from . " / " . $this->text . "\n" . $this->number. " " . $this->unit . ".";
        }

    }


    /**
     *
     */
    function makeMessage() {
        $message = $this->response . "<br>";

        $uuid = $this->uuid;

        $message .= "<p>" . $this->web_prefix . "thing/$uuid/chatbot\n \n\n<br> ";

        $this->thing_report['message'] = $message;

        return;

    }


    /**
     *
     */
    function getBar() {
        $this->bar = new Bar($this->thing, "display");
    }


    /**
     *
     */
    function setChatbot() {
    }


    //    function getChatbot()
    //    {
    //    }


    /**
     *
     */
    function makeWeb() {

        $link = $this->web_prefix . 'thing/' . $this->uuid . '/chatbot';

        $this->node_list = array("chatbot"=>array("chatbot"));
        // $this->node_list = array("charley"=>array("rocky", "bullwinkle","charley"));
        // Make buttons
        //$this->thing->choice->Create($this->agent_name, $this->node_list, "charley");
        //$choices = $this->thing->choice->makeLinks('charley');

        if (!isset($this->html_image)) {$this->makePNG();}

        $web = "<b>Chatbot Agent</b>";
        $web .= "<p>";
        $web .= "<p>";

        $web .= '<a href="' . $link . '">'. $this->html_image . "</a>";
        $web .= "<br>";

        $web .= "<p>";

        //$web .= $this->nom;

        switch (trim($this->suit)) {
        case "diamonds":
            $web .= "OPERATIONS inject received.";
            break;
        case "hearts":
            $web .= "PLANNING inject received.";
            break;
        case "clubs":
            $web .= "LOGISTICS inject received.";
            break;
        case "spades":
            $web .= "FINANCE inject received.";
            break;
        default:
            $web .= "UNRECOGNIZED inject received.";
        }

        $web .= "<p>";

        $ago = $this->thing->human_time ( time() - $this->refreshed_at );
        $web .= "This inject was created about ". $ago . " ago. ";

        $link = $this->web_prefix . "privacy";
        $privacy_link = '<a href="' . $link . '">'. $link . "</a>";


        $web .= "This proof-of-concept inject is hosted by the " . ucwords($this->word) . " service.  Read the privacy policy at " . $privacy_link . ".";


        $web .= "<br>";

        $this->thing_report['web'] = $web;


    }


    /**
     *
     */
    function makeTXT() {
        $txt = "Traffic for CHATBOT.\n";
        $txt .= 'Duplicate messages may exist. Can you de-duplicate?';
        $txt .= "\n";

        $txt .= $this->response;

        $txt .= "\n";


        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;
    }


    /**
     *
     * @return unknown
     */
    public function makePNG() {

        $this->image = imagecreatetruecolor(164, 164);

        $width = imagesx($this->image);
        $height = imagesy($this->image);

        $this->white = imagecolorallocate($this->image, 255, 255, 255);
        $this->black = imagecolorallocate($this->image, 0, 0, 0);
        $this->red = imagecolorallocate($this->image, 255, 0, 0);
        $this->green = imagecolorallocate($this->image, 0, 255, 0);
        $this->grey = imagecolorallocate($this->image, 128, 128, 128);
        $this->blue = imagecolorallocate($this->image, 0, 68, 255);

        $this->flag_yellow = imagecolorallocate($this->image, 255, 239, 0);

        switch (trim($this->suit)) {
        case "diamonds":
            imagefilledrectangle($this->image, 0, 0, $width, $height, $this->red);
            $textcolor = imagecolorallocate($this->image, 255, 255, 255);
            break;
        case "hearts":
            imagefilledrectangle($this->image, 0, 0, $width, $height, $this->blue);
            $textcolor = imagecolorallocate($this->image, 255, 255, 255);
            break;
        case "clubs":
            imagefilledrectangle($this->image, 0, 0, $width, $height, $this->flag_yellow);
            $textcolor = imagecolorallocate($this->image, 0, 0, 0);
            break;
        case "spades":
            imagefilledrectangle($this->image, 0, 0, $width, $height, $this->grey);
            $textcolor = imagecolorallocate($this->image, 255, 255, 255);
            break;
        default:
            imagefilledrectangle($this->image, 0, 0, $width, $height, $this->white);
            $textcolor = imagecolorallocate($this->image, 0, 0, 0);
        }

        // Write the string at the top left
        $border = 30;
        $radius = 1.165 * ($width - 2 * $border) / 3;

        // devstack add path
        //$font = $this->resource_path . '/var/www/html/stackr.test/resources/roll/KeepCalm-Medium.ttf';
        $font = $this->resource_path . 'roll/KeepCalm-Medium.ttf';
        $text = "EXERCISE EXERCISE EXERCISE WELFARE TEST ROCKY 5";
        $text = "ROCKY";

        $text = strtoupper($this->nom);

        if (!isset($this->bar)) {$this->getBar();}

        $bar_count = $this->bar->bar_count;

        // Add some shadow to the text
        //imagettftext($this->image, 40 , 0, 0 - $this->bar->bar_count*5, 75, $this->grey, $font, $text);

        $size = 72;
        $angle = 0;
        $bbox = imagettfbbox($size, $angle, $font, $text);
        $bbox["left"] = 0- min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["top"] = 0- min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $bbox["width"] = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]) - min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["height"] = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]) - min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        extract($bbox, EXTR_PREFIX_ALL, 'bb');
        $pad = 0;

        imagettftext($this->image, $size, $angle, $width/2-$bb_width/2, $height/2+ $bb_height/2, $textcolor, $font, $text);

        $text= $this->number . " " . strtoupper($this->unit);

        $size = 9.5;
        $angle = 0;
        $bbox = imagettfbbox($size, $angle, $font, $text);
        $bbox["left"] = 0- min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["top"] = 0- min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        $bbox["width"] = max($bbox[0], $bbox[2], $bbox[4], $bbox[6]) - min($bbox[0], $bbox[2], $bbox[4], $bbox[6]);
        $bbox["height"] = max($bbox[1], $bbox[3], $bbox[5], $bbox[7]) - min($bbox[1], $bbox[3], $bbox[5], $bbox[7]);
        extract($bbox, EXTR_PREFIX_ALL, 'bb');

        imagettftext($this->image, $size, $angle, $width/2-$bb_width/2, $height*11/12, $textcolor, $font, $text);


        // Small nuuid text for back-checking.
        imagestring($this->image, 2, 140, 0, $this->thing->nuuid, $textcolor);


        // Save the image
        //header('Content-Type: image/png');
        //imagepng($im);
        //xob_clean();

        // https://stackoverflow.com/questions/14549110/failed-to-delete-buffer-no-buffer-to-delete
        if (ob_get_contents()) ob_clean();

        ob_start();
        imagepng($this->image);
        $imagedata = ob_get_contents();

        ob_end_clean();

        $this->thing_report['png'] = $imagedata;

        //echo '<img src="data:image/png;base64,'.base64_encode($imagedata).'"/>';
        $response = '<img src="data:image/png;base64,'.base64_encode($imagedata).'"alt="snowflake"/>';

        $this->PNG_embed = "data:image/png;base64,".base64_encode($imagedata);

        $this->PNG = $imagedata;

        $this->html_image = $response;

        return $response;
    }


    /**
     *
     */
    function setRules() {
        $this->rules = array();
        /*
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
*/
    }


    /**
     *
     * @param unknown $array
     * @return unknown
     */
    function computeTranspositions($array) {
        if (count($array) == 1) {return false;}
        $result = [];
        foreach (range(0, count($array)-2) as $i) {
            $tmp_array = $array;
            $tmp = $tmp_array[$i];
            $tmp_array[$i] = $tmp_array[$i+1];
            $tmp_array[$i+1] = $tmp;
            $result[] = $tmp_array;
        }

        return $result;
    }


    /**
     *
     * @return unknown
     */
//    function read() {
//        return $this->state;
//    }


    /**
     *
     * @param unknown $input
     * @return unknown
     */
    function extractNuuid($input) {
        if (!isset($this->duplicables)) {
            $this->duplicables = array();
        }

        return $this->duplicables;
    }


    /**
     *
     * @return unknown
     */
    public function makePDF() {
        $txt = $this->thing_report['txt'];
        //$txt = explode($txt , "\n");
        // initiate FPDI
        $pdf = new Fpdi\Fpdi();

        $pdf->setSourceFile($this->resource_path . 'snowflake/bubble.pdf');
        $pdf->SetFont('Helvetica', '', 10);

        $tplidx1 = $pdf->importPage(3, '/MediaBox');

        $s = $pdf->getTemplatesize($tplidx1);

        $pdf->addPage($s['orientation'], $s);
        //        $pdf->useTemplate($tplidx1,0,0,215);
        $pdf->useTemplate($tplidx1);

        $pdf->SetTextColor(0, 0, 0);

        $text = "Pre-printed text and graphics (c) 2018 Stackr Interactive Ltd";
        $pdf->SetXY(15, 20);
        $pdf->Write(0, $text);

        /*
        ob_start();
        $image = $pdf->Output('', 'I');
        $image = ob_get_contents();
        ob_clean();
*/
        $image = $pdf->Output('', 'S');


        $this->thing_report['pdf'] = $image;

        return $this->thing_report['pdf'];
    }


    /**
     *
     */
    public function readSubject() {

        $input = strtolower($this->subject);

        $this->extractChatbots($input);
        $this->filterChatbots($input);


        if ($this->agent_input == "extract") {return;}

        $pieces = explode(" ", strtolower($input));


        if (count($pieces) == 1) {

            if ($input == 'chatbot') {
                $this->getCard();
                $this->response = "OK.";
                //                if ((!isset($this->index)) or
                //                    ($this->index == null)) {
                //                    $this->index = 1;
                //                }
                return;
            }
        }

        $keywords = array("chatbot");
        foreach ($pieces as $key=>$piece) {
            foreach ($keywords as $command) {
                if (strpos(strtolower($piece), $command) !== false) {
                    switch ($piece) {

                    case 'chatbot':
                        $this->response = "OK.";
                        $this->getCard();

                        return;

                    case 'on':
                        //$this->setFlag('green');
                        //break;


                    default:
                    }
                }
            }
        }

        $this->getCard();
        $this->response = "OK.";
        //        if ((!isset($this->index)) or
        //            ($this->index == null)) {
        //            $this->index = 1;
        //        }

        //    return;
    }


}
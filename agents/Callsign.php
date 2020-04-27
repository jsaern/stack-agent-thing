<?php
/**
 * Callsign.php
 *
 * @package default
 */

namespace Nrwtaylor\StackAgentThing;

class Callsign extends Agent
{
    // devstack

    // https://www.ic.gc.ca/eic/site/025.nsf/eng/h_00004.html
    // Download regularly

    /**
     *
     * @param Thing   $thing
     * @param unknown $agent_input (optional)
     */
    public function init()
    {
        $this->version_date = "2020-04-27";
        $this->start_time = microtime(true);

        $this->assert_callsign = false;
        $this->resource_path = $GLOBALS['stack_path'] . 'resources/callsign/';

        //        $this->keywords = array();
        //
        //        $this->thing_report['help'] = "Looks up callsigns.";
        $this->thing_report['info'] = "Possibly helpful to station operators.";

        $this->net_horizon = 30*60; // 30 minutes. Then assume station has gone.

        $this->keywords = [
            'is',
            'heard',
            'check in',
            'check-in',
            'checkin',
            'add',
            'drop',
            'check out',
            'checkout',
            'check-out',
            '73',
            'callsign',
            "active",
            "net",
            "callsign",
            "call sign"
        ];

        $this->thing_report['help'] = 'Recognises the following modifiers. ' . ucwords(
            trim(implode(" ", $this->keywords)). "."
        );
    }

    function getTimezone()
    {
        // Eventually call the timezone agent.
        $this->time_zone = "America/Vancouver";
    }

    function t($timestamp)
    {
        $i = 1;
        $this->getTimezone();

        $dt = new \DateTime($timestamp, new \DateTimeZone("UTC"));

        $dt->setTimezone(new \DateTimeZone($this->time_zone));

        //                $d = date('H:i', strtotime($prediction["date"]));
        $d = $dt->format('H:i');

        return $d;
        $date = $dt->format('Y/m/d');

        //                $date = date('Y/m/d H:i', strtotime($prediction["date"]));

        if ($i == 0) {
            //   $d = date('Y/m/d H:i',strtotime($prediction["date"]));
            $d = $dt->format('Y/m/d H:i');

            $old_date = $date;
        }

        //echo $date . " " . $old_date ."\n";

        if ($old_date != $date) {
            //   $d = date('m/d H:i',strtotime($prediction["date"]));
            $d = $dt->format('m/d H:i');
        }

        //                   $d = $dt->format('H:i');

        //return $d;
        //                $i+=1;
    }

    function netCallsign()
    {
        $text = "";

        // Build a list of the latest heard.
        $callsigns_heard = [];

        foreach ($this->callsigns as $i => $callsign) {
            $call = $callsign['callsign'];

$last_refreshed_at = $callsign['refreshed_at'];
$ago = strtotime($this->current_time) - strtotime($last_refreshed_at);
if ($ago > $this->net_horizon) {continue;}

            if (!isset($callsigns_heard[$call])) {
                $callsigns_heard[$call] = $callsign;
            }
        }

        foreach ($callsigns_heard as $i => $callsign) {
            $action = "";
            if (isset($callsign['action'])) {
                $action = $callsign['action'];
            }

            $text .=
                $callsign['callsign'] .
                " " .
                $action .
                " " .
                $this->t($callsign['refreshed_at']) .
                " / ";
            //$text .= $callsign['callsign'] ." ". $callsign['refreshed_at'] . " / ";
        }

        $this->callsigns_heard = $callsigns_heard;
        $this->response = $text;
    }

    /**
     *
     */
    function get()
    {
        $this->thing->json->setField("variables");
        $time_string = $this->thing->json->readVariable([
            "callsign",
            "refreshed_at",
        ]);

        if ($time_string == false) {
            $time_string = $this->thing->json->time();
            $this->thing->json->writeVariable(
                ["callsign", "refreshed_at"],
                $time_string
            );
        }

        $this->reading = $this->thing->json->readVariable([
            "callsign",
            "reading",
        ]);

        $this->variables = new Variables(
            $this->thing,
            "variables callsign " . $this->from
        );

        $this->callsign_action = $this->variables->getVariable("action");
        $this->callsign_text = $this->variables->getVariable("callsign");
        $this->refreshed_at = $this->variables->getVariable("refreshed_at");
    }

    /**
     *
     */
    function run()
    {
    }

    /**
     *
     */
    function set()
    {
//        $this->makeSMS();

        //  $this->thing_report['sms'] = strtoupper($this->agent_name) . " | " . $this->response;

        //        $this->reading = "X";
        //        if (isset($this->callsigns)) {
        //            $this->reading = count($this->callsigns);
        //        }
        //        $this->thing->json->writeVariable(array("callsign", "reading"), $this->reading);

        $this->thing->json->writeVariable(
            ["callsign", "reading"],
            $this->reading
        );

        //$this->variables = new Variables(
        //    $this->thing,
        //    "variables callsign " . $this->from
        //);

        if ($this->assert_callsign) {
            $this->variables->setVariable("callsign", $this->callsign["callsign"]);
            $time_string = $this->thing->json->time();
            $this->variables->setVariable("refreshed_at", $time_string);
            $this->variables->setVariable("action", $this->callsign["action"]);
        }
    }

    /**
     *
     * @param unknown $input
     */
    function assertCallsign($input)
    {
        $whatIWant = $input;
        if (($pos = strpos(strtolower($input), "callsign is")) !== false) {
            $whatIWant = substr(
                strtolower($input),
                $pos + strlen("callsign is")
            );
        } elseif (($pos = strpos(strtolower($input), "callsign")) !== false) {
            $whatIWant = substr(strtolower($input), $pos + strlen("callsign"));
        }
        $filtered_input = ltrim(strtolower($whatIWant), " ");
        $callsigns = $this->extractCallsigns($filtered_input);

        $this->callsign = reset($callsigns);
        $this->assert_callsign = true;
    }

    public function checkinCallsign()
    {
        $this->callsign['action'] = "checkin";
        $this->response .= "Checked in. ";
    }

    public function checkoutCallsign()
    {
        $this->callsign['action'] = "checkout";
        $this->response .= "Checked out. ";
    }

    function getCallsigns()
    {
        $this->callsign_list = [];
        $this->callsigns = [];

        // See if a headcode record exists.
        $findagent_thing = new Findagent($this->thing, 'callsign');
        $count = count($findagent_thing->thing_report['things']);
        $this->thing->log(
            'Agent "Callsign" found ' .
                count($findagent_thing->thing_report['things']) .
                " Callsign Things."
        );

        if ($count > 0) {
            foreach (
                array_reverse($findagent_thing->thing_report['things'])
                as $thing_object
            ) {
                $uuid = $thing_object['uuid'];
                $variables_json = $thing_object['variables'];
                $variables = $this->thing->json->jsontoArray($variables_json);

                if (isset($variables['callsign'])) {
                    $callsign = "X";
                    $refreshed_at = "X";
                    $action = "X";

                    if (isset($variables['callsign']['action'])) {
                        $action = $variables['callsign']['action'];
                    }

                    if (isset($variables['callsign']['callsign'])) {
                        $callsign = $variables['callsign']['callsign'];
                    }


                    if (isset($variables['callsign']['refreshed_at'])) {
                        $refreshed_at = $variables['callsign']['refreshed_at'];
                    }

// Check for junk entries. And discard.
if ($callsign == "X") {continue;}
if ($action == "X") {continue;}
if ($refreshed_at == "X") {continue;}


                    $this->callsigns[] = [
                        "callsign" => $callsign,
                        "action" => $action,
                        "refreshed_at" => $refreshed_at,
                    ];
                    $this->callsign_list[] = $callsign;
                }
            }
        }

        $refreshed_at = [];
        foreach ($this->callsigns as $key => $row) {
            $refreshed_at[$key] = $row['refreshed_at'];
        }
        array_multisort($refreshed_at, SORT_DESC, $this->callsigns);

        return [$this->callsign_list, $this->callsigns];
    }

    /**
     *
     * @param unknown $input
     * @param unknown $replace_with (optional)
     * @return unknown
     */
    public function stripPunctuation($input, $replace_with = " ")
    {
        $unpunctuated = preg_replace(
            '/[\:\;\/\!\?\#\.\,\'\"\{\}\[\]\<\>\(\)]/i',
            $replace_with,
            $input
        );
        return $unpunctuated;
    }

    /**
     *
     * @param unknown $x
     * @return unknown
     */
    function isDate($x)
    {
        $date_array = date_parse($x);

        if (
            $date_array['day'] != false and
            $date_array['month'] != false and
            $date_array['year'] != false
        ) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param unknown $string
     * @return unknown
     */
    function extractCallsigns($string)
    {
        $pattern = '/\b\w*?\p{N}\w*\b/u';
        preg_match_all($pattern, $string, $callsigns);

        $w = $callsigns[0];

        $w = [$string];

        $this->callsigns = [];

        foreach ($w as $key => $value) {
            // Return dictionary entry.
            $value = $this->stripPunctuation($value);
            $text = $this->findCallsign('list', $value);

            if ($text === true) {
                return true;
            }

            foreach ($text as $x) {
                $line = $x['line'];
                $line = utf8_encode($line);
                $a = explode(";", $line);
                $t = $a[1];
                if ($this->isDate($t)) {
                    $callsign = [
                        "callsign" => $a[0],
                        "first_name" => $a[3],
                        "second_name" => $a[4],
                    ];
                } else {
                    $callsign = [
                        "callsign" => $a[0],
                        "first_name" => $a[1],
                        "second_name" => $a[2],
                    ];
                }

                if ($text != false) {
                    //   echo "callsign is " . $text . "\n";
                    $this->callsigns[$a[0]] = $callsign;
                } else {
                    //   echo "callsign is not " . $value . "\n";
                }
            }
        }

        if (count($this->callsigns) != 0) {
            $this->callsign = reset($this->callsigns);
        } else {
            $this->callsign = null;
        }
        return $this->callsigns;
    }

    /**
     *
     * @param unknown $text (optional)
     * @return unknown
     */
    function getCallsign($text = null)
    {
        if (!isset($this->callsigns)) {
            $this->extractCallsigns($text);
        }

        if (count($this->callsigns) == 0) {
            $this->callsign = false;
            return $this->callsign;
        }

        $this->callsign = reset($this->callsigns);
        return $this->callsign;
    }

    /**
     *
     * @param unknown $librex
     * @param unknown $searchfor
     * @return unknown
     */
    function findCallsign($librex, $searchfor)
    {
        if ($librex == "" or $librex == " " or $librex == null) {
            return false;
        }

        switch ($librex) {
            case null:
            // Drop through
            case 'list':
                if (isset($this->callsigns_list)) {
                    $contents = $this->callsigns_list;
                    break;
                }
                $file = $this->resource_path . 'amateur_delim.txt';
                $pre_contents = file_get_contents($file);

                if ($pre_contents == false) {
                    return true;
                }

                // Remove address info from search space.
                $arr = explode("\n", $pre_contents);
                $contents = "";
                foreach ($arr as $key => $line) {
                    $fields = explode(";", $line);
                    if (!isset($fields[1])) {
                        continue;
                    }

                    $contents .=
                        $fields[0] . ";" . $fields[1] . ";" . $fields[2] . "\n";
                }

                $file = $this->resource_path . 'special_callsign.txt';
                $contents .= file_get_contents($file);

                $this->callsigns_list = $contents;

                break;
            default:
                $file = $this->resource_path . 'amateur_delim.txt';
        }

        $line_matches = [];

        foreach (explode(" ", $searchfor) as $word) {
            $regex_pieces = "(?=.*" . $word . ")";
            $pattern = "/^" . $regex_pieces . ".*$/mi";

            // search, and store all matching occurences in $matches
            $m = false;

            preg_match_all($pattern, $contents, $matches);
            $line_matches = array_merge($line_matches, $matches[0]);
        }
        $best_score = 0;

        $sorted_matches = [];
        $test_array = [];
        foreach ($line_matches as $line) {
            $score = $this->getCloseness($line, $searchfor);

            if ($score != 0) {
                // Add to the bottom.
                $test_array[] = ["line" => $line, "score" => $score];
                //            $sorted_matches[] = $line;
            }
        }

        $score = [];
        foreach ($test_array as $key => $row) {
            $score[$key] = $row['score'];
        }
        array_multisort($score, SORT_DESC, $test_array);

        $i = 0;
        foreach ($test_array as $key => $value) {
            //echo $value['score'] . " ". $value['line'] . "\n";
            $i += 1;
            if ($i > 10) {
                break;
            }
        }
        return $test_array;
    }

    /**
     *
     * @param unknown $line
     * @param unknown $text
     * @return unknown
     */
    function getCloseness($line, $text)
    {
        $words = preg_split('/[^a-z0-9.\']+/i', $line);
        $score = 0;
        foreach (explode(" ", $text) as $text_word) {
            foreach ($words as $word) {
                if ($word == "" or $text_word == "") {
                    continue;
                }

                if (strtolower($word) == strtolower($text_word)) {
                    $score += mb_strlen($text_word) * 10;
                    break;
                }

                if (
                    strpos(strtolower($word), strtolower($text_word)) !== false
                ) {
                    $score += 2;
                }

                $lev = levenshtein(strtolower($text_word), strtolower($word));
                if (mb_strlen($text_word) != $lev) {
                    $score += 1;
                }

                if (
                    mb_substr(strtolower($text_word), 0, 3) ==
                    mb_substr(strtolower($word), 0, 3)
                ) {
                    $score += 2;
                }

                if (
                    mb_substr(strtolower($text_word), 0, 1) ==
                    mb_substr(strtolower($word), 0, 1)
                ) {
                    //echo $text ." " . $word . "\n";
                    $score += 1;
                }
            }
        }
        return $score;
    }

    /**
     *
     * @return unknown
     */
    public function respondResponse()
    {
        //  public function respond() {

        $this->cost = 100;

        // Thing stuff
        $this->thing->flagGreen();

        $message_thing = new Message($this->thing, $this->thing_report);
        $this->thing_report['info'] = $message_thing->thing_report['info'];

        return $this->thing_report;
    }

    /**
     *
     */
    function makeSMS()
    {
        //$callsign_text = (implode(" ",$this->callsign));
        $r = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $this->response);

        //            $this->sms_message .= $callsign_text;

        $sms = "CALLSIGN | " . $r;
        $this->sms_message = $sms;
        $this->thing_report['sms'] = $sms;
        return;
    }

    /**
     *
     */
    function makeWeb()
    {
        $web = "";

        //        $link = $this->web_prefix . 'thing/' . $this->uuid . '/agent';

        //        $web = '<a href="' . $link . '">';
        //        $web .= '<img src= "' . $this->web_prefix . 'thing/' . $this->uuid . '/flag.png">';
        //        $web .= $this->html_image;

        $web .= "</a>";
        $web .= "<br>";
        $web .= '<b>Callsign Agent</b><br>';
        $web .= "<p>";
        $web .= "No web response available.";
/*
        $web .= 'sms message ' . $this->sms_message;
        $web .= "<p>";
        $web .= 'input ' . $this->input;
        $web .= "<p>";
        $web .= 'uuid ' . $this->uuid;
        $web .= "<p>";
        $web .= 'subject ' . $this->subject;
        $web .= "<p>";

        $web .= 'subject '. $this->thing->subject;
        $web .= "<p>";

        $web .= 'agent_input ' . $this->agent_input;
        $web .= "<p>";



        if (isset($this->callsigns)) {
            foreach ($this->callsigns as $id => $callsign) {
                $first_name = "X";
                if (isset($callsign['first_name'])) {
                    $first_name = $callsign['first_name'];
                }

                $callsign_text = $callsign["callsign"] . " " . $first_name;
                $web .= "<br>" . $callsign_text;
            }
        }
*/
        $this->web_message = $web;
        $this->thing_report['web'] = $web;
    }

    /**
     *
     */
    function makeEmail()
    {
        $this->email_message = "CALLSIGN | " . $this->response;
    }

    /**
     *
     * @return unknown
     */
    public function readSubject()
    {
        $prefix = 'callsign';
        $callsigns = preg_replace(
            '/^' . preg_quote($prefix, '/') . '/',
            '',
            $this->input
        );
        $callsigns = ltrim($callsigns);

        $this->search_callsigns = $callsigns;
        $this->extractCallsigns($callsigns);

        //       $keywords = array('is', 'heard', 'callsign', "active", "net");
        //  $pieces = explode(" ", strtolower($this->input));

        $ngram_agent = new Ngram($this->thing, "ngram");
        $pieces = [];
        $arr = $ngram_agent->getNgrams(strtolower($this->input), 3);
        $pieces = array_merge($pieces, $arr);
        $arr = $ngram_agent->getNgrams(strtolower($this->input), 2);
        $pieces = array_merge($pieces, $arr);
        $arr = $ngram_agent->getNgrams(strtolower($this->input), 1);
        $pieces = array_merge($pieces, $arr);

        $pieces = array_reverse($pieces);

        //private function getNgrams($input, $n = 3) {

        if (count($pieces) == 1) {
            if ($this->input == 'callsign') {
                //                $this->getCallsigns($this->callsign_text);
                $this->extractCallsigns($this->callsign_text);
                if (isset($this->callsign)) {
                    $this->response =
                        $this->callsign["callsign"] .
                        " " .
                        $this->callsign["first_name"] .
                        ". " .
                        "Last asserted callsign retrieved.";
                    return;
                }

                $this->response = "No match found.";
                return;
            }
        }

        foreach ($pieces as $key => $piece) {
            foreach ($this->keywords as $command) {
                if (strpos(strtolower($piece), $command) !== false) {
                    switch ($piece) {
                        case 'net':
                        case 'active':
                            $this->getCallsigns();
                            $this->netCallsign();
                            $count = 0;
                            if (is_array($this->callsigns_heard)) {$count = count($this->callsigns_heard);}
                            $this->response .= "Retrieved " . $count . " active callsigns. ";
                            return;

                        case 'check in':
                        case 'check-in':
                        case 'checkin':
                        case 'add':
                        case 'heard':
                        case 'is':
                            $this->assertCallsign(strtolower($this->input));

                            if (empty($this->callsign)) {
                                $this->response = "Did not find a callsign.";
                            } else {
                                $this->response =
                                    'Callsign asserted to be ' .
                                    strtoupper($this->callsign["callsign"]) .
                                    ". ";
                                $this->checkinCallsign();
                            }

                            return;

                        case 'check out':
                        case 'check-out':
                        case 'checkout':
                        case 'drop':
                        case '73':
                            $this->assertCallsign(strtolower($this->input));

                            if (empty($this->callsign)) {
                                $this->response = "Did not find a callsign. ";
                            } else {
                                $this->response =
                                    'Callsign asserted to be ' .
                                    strtoupper($this->callsign["callsign"]) .
                                    ". ";
                                $this->checkoutCallsign();
                            }

                            return;



                        default:
                        //echo 'default';
                    }
                }
            }
        }
        $first_name = $this->callsign["first_name"];

        // If more than one first name is returned.
        $arr = explode(" ", $first_name);
        if (count($arr) >= 2) {
            if (strlen($arr[1]) != 1) {
                $first_name = $arr[0];
            }
        }

        if (!isset($this->callsigns)) {
            $this->response = "No match found. ";
            return;
        }

        if (count($this->callsigns) > 1) {
            $this->response =
                "Found " .
                count($this->callsigns) .
                " callsigns. Best " .
                $this->callsign["callsign"] .
                " " .
                $first_name .
                ".";
            return;
        }

        if (count($this->callsigns) == 1) {
            $this->response =
                "Found " .
                $this->callsign["callsign"] .
                " " .
                $first_name .
                ". ";
            return;
        }

        $this->response = "No match found. ";
    }
}

<?php
namespace Nrwtaylor\StackAgentThing;

class Dateline extends Agent
{
    public $var = 'hello';

    function init()
    {
        $this->at_agent = new At($this->thing, "at");

        $this->test_url = null;
        if (isset($this->thing->container['api']['dateline']['test_url'])) {
            $this->test_url =
                $this->thing->container['api']['dateline']['test_url'];
        }
        $this->url_agent = new Url($this->thing, "url");
    }

    function run()
    {
        $this->doDateline();
    }

    public function get()
    {
        //$this->test();
        //$this->extractDateline();
    }

    public function test()
    {
        if (!is_string($this->test_url)) {
            return false;
        }

        $url = $this->test_url;
        $read_agent = new Read($this->thing, $url);

        $paragraph_agent = new Paragraph($this->thing, $read_agent->contents);

        $paragraphs = $paragraph_agent->paragraphs;

        $arr = ['year', 'month', 'day', 'day_number', 'hour', 'minute'];

        foreach ($paragraphs as $i => $paragraph) {
            $dateline = $this->extractDateline($paragraph);
            if ($dateline == false) {
                continue;
            }
            $this->thing->log($dateline['dateline'] . "\n" . $dateline['line']);
        }
    }

    public function getDateline($text = null)
    {
        //        if (!is_string($this->test_url)) {
        //            return false;
        //        }

        // Ignore text for now.
        // Read the specificed url. And get the first dateline.
        // Dateline being  timestamp + text.
        // Time this part/
        $start_time = time();
        $url = $this->test_url;
        $read_agent = new Read($this->thing, $url);

        $run_time = time() - $start_time;
        $this->response .= "Dateline source took " . $run_time . " seconds to get. ";

        $start_time = time();

        $paragraph_agent = new Paragraph($this->thing, $read_agent->contents);

        $paragraphs = $paragraph_agent->paragraphs;

        $arr = ['year', 'month', 'day', 'day_number', 'hour', 'minute'];

        foreach ($paragraphs as $i => $paragraph) {
            $dateline = $this->extractDateline($paragraph);

            if ($dateline == false) {
                continue;
            }
            if ($dateline['line'] == " ") {
                continue;
            }
            $this->thing->log(
                $dateline['dateline'] . "\n" . $dateline['line'] . "\n"
            );
            break;
        }
        $run_time = time() - $start_time;
        $this->thing->log(" getDateline " . $run_time);
        $this->response .= "Took another " . $run_time . " seconds to find a dateline. ";
        return $dateline;
    }

    public function extractDateline($text = null)
    {
        if ($text === false) {
            return false;
        }
        if ($text === "") {
            return false;
        }
        if ($text === " ") {
            return false;
        }
        if ($text === true) {
            return false;
        }
        if ($text === null) {
            return false;
        }

        //$url_agent = new Url($this->thing,"url");
        $text = $this->url_agent->stripUrls($text);
        $paragraph = $text;

        // Todo extract calendar.

        $arr = [
            'year',
            'month',
            'day',
            'day_number',
            'hour',
            'minute',
            'timezone',
        ];

        if ($paragraph == "") {
            return false;
        }
        $t = $this->at_agent->extractAt($paragraph);

        $flag = false;
        $date = [];

        foreach ($arr as $component) {
            $this->{$component} = $this->at_agent->{$component};

            if ($this->{$component} !== false) {
                $flag = true;
            }
            $date[$component] = $this->{$component};
        }

        if ($flag === false) {
            // No components seen
            return false;
        }

        $dateline = $this->textDateline($date);
        $date['line'] = $paragraph;
        $date['dateline'] = $dateline;

        return $date;
    }

    public function timestampDateline($dateline)
    {
        $arr = [
            'year' => 'XXXX',
            'month' => 'XX',
            'day_number' => 'XX',
            'hour' => 'XX',
            'minute' => 'XX',
            'second' => 'XX',
        ];
        foreach ($arr as $component => $default_text) {
            ${$component} = $default_text;

            if ($dateline[$component] === null) {
                continue;
            }
            if ($dateline[$component] === false) {
                continue;
            }
            if ($dateline[$component] === true) {
                continue;
            }
            if (strtolower($dateline[$component]) === 'x') {
                continue;
            }
            if (strtolower($dateline[$component]) === 'z') {
                continue;
            }
            if ($dateline[$component] === '?') {
                continue;
            }

            // is_int does not recognizing '2020' as an integer.
            // So use this.
            // https://www.php.net/manual/en/function.is-int.php
            if (ctype_digit(strval($dateline[$component]))) {
                ${$component} = str_pad(
                    $dateline[$component],
                    mb_strlen($default_text),
                    "0",
                    STR_PAD_LEFT
                );
            }
        }

        $timezone = "X";
        if (strtolower($dateline['timezone']) == 'utc') {
            $timezone = "Z";
        }

        //        $text = $year ."-" . $dateline['month'] . $dateline['day_number'] . 'T'.
        //        $dateline['hour'] .":".$dateline['minute'].":". $second . $timezone;

        $text =
            $year .
            "-" .
            $month .
            "-" .
            $day_number .
            'T' .
            $hour .
            ":" .
            $minute .
            ":" .
            $second .
            $timezone;

        return $text;
    }

    public function textDateline($dateline)
    {
        $text = "";
        foreach ($dateline as $key => $value) {
            if ($value === false) {
                continue;
            }
            $text .= $key . " " . $value . " ";
        }

        return $text;
    }

    public function doDateline()
    {
        if ($this->agent_input == null) {
            $array = ['where are you?'];
            $k = array_rand($array);
            $v = $array[$k];

            if (isset($this->dateline['dateline'])) {
                $v = $this->dateline['dateline'];
            }

            //$response = "DATELINE | " . strtolower($v) . ".";

            $this->dateline_message = $v; // mewsage?
        } else {
            $this->dateline_message = $this->agent_input;
        }
    }

    function getNegativetime()
    {
        $agent = new Negativetime($this->thing, "dateline");
        $this->negative_time = $agent->negative_time; //negative time is asking
    }

    public function respondResponse()
    {
        $this->thing->flagGreen();

        $this->thing_report["info"] =
            "This is a dateline keeping an eye on how late this Thing is.";
        $this->thing_report["help"] = "This is about being inscrutable.";

        //$this->thing_report['sms'] = $this->sms_message;
        $this->thing_report['message'] = $this->sms_message;
        $this->thing_report['txt'] = $this->sms_message;

        $message_thing = new Message($this->thing, $this->thing_report);
        $thing_report['info'] = $message_thing->thing_report['info'];

        return $this->thing_report;
    }

    function makeSMS()
    {
        $this->node_list = ["dateline" => ["dateline", "dog"]];

        $sms = "DATELINE ";
        // . $this->dateline_message;

        $dateline_timestamp = $this->timestampDateline($this->dateline);

        $timestamp_text = "undated";
        if (is_string($dateline_timestamp)) {
            $timestamp_text = $dateline_timestamp;
        }

        $sms .= $timestamp_text . " ";

        // See if there is a dateline with a UTC timestamp.
        if (stripos($this->dateline['line'], " utc ") !== false) {
            $tokens = explode(" UTC ", $this->dateline['line']);

            $text_token = $tokens[1];
            $time_tokens = explode(" ", $tokens[0]);

            if (strtolower($time_tokens[0]) === 'timestamp') {
                $sms .= "| " . $text_token . " ";
            }
        }

        $sms .= $this->response;

        $this->sms_message = "" . $sms;
        $this->thing_report['sms'] = $this->sms_message;
    }

    function makeChoices()
    {
        $this->thing->choice->Create('channel', $this->node_list, "dateline");
        $choices = $this->thing->choice->makeLinks('dateline');
        $this->thing_report['choices'] = $choices;
    }

    public function questionDateline()
    {
        $agent_class_name = "Dateline";

        // Start of ? code
        // This is a generalised piece of code.
        // It creates a unique key from the hashed from address and the agent name.
        // Then it gets either a cached version of the agent variable.
        // Or if 'too' old, calls for a more recent agent variable.

        // TODO Refactor to Agent.

        $agent_name = strtolower($agent_class_name);

        $slug_agent = new Slug($this->thing, "slug");
        $slug = $slug_agent->getSlug($agent_name . "-" . $this->from);

        $memory = $this->getMemory($slug);

        if ($memory == false) {
            $memory = $this->memoryAgent('Dateline');
            $this->response .= "No memory found. Got first memory. ";
        }

        $age =
            strtotime($this->current_time) - strtotime($memory['retrieved_at']);

        // How old can the dateline be before needing another check?
        $dateline_horizon = 60;
        if ($age <= $dateline_horizon) {
            // If younger than 60 seconds use response in memory.
            $this->response .=
                "Saw an " .
                $agent_name .
                " channel memory from " .
                $this->thing->human_time($age) .
                " ago. ";
        }
        if ($age > $dateline_horizon) {
            // TODO: Schedule offline request for dateline update.
            // Work to worker, Thing  and Database?
            /*

//$from_hash = hash('sha256', $this->from);
//var_dump($this->from);


                $datagram = [
                    "to" => $this->from,
                    "from" => "calendar",
                    "subject" => "s/ " . "dateline",
                    "agent_input" => "dateline"
                ];
                $this->thing->spawn($datagram);
                $this->response .= "Requested a dateline update. ";
*/
            //                $memory = $this->getMemory($slug);
            $memory = $this->getDateline($slug);
            //                $memory = $this->memoryAgent('Dateline');

            $this->response .= "Got a dateline update. ";
            $age = 0;
        }

        $dateline = $memory;

        $this->dateline = $dateline;
    }

    public function readSubject()
    {
        $input = strtolower($this->subject);
        if ($this->agent_input != null) {
            $input = $this->agent_input;
        }

        if ($input == "dateline") {
            //$dateline = $this->memoryAgent('Dateline');

            $this->questionDateline();

            return;
        }
        if ($input == "dateline test") {
            $this->test();
        }

        $this->dateline = $this->extractDateline($input);
    }
}

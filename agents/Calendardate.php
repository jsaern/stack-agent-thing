<?php
namespace Nrwtaylor\StackAgentThing;


ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

class Calendardate 
{

    public $var = 'hello';

    function __construct(Thing $thing, $agent_input = null)
    {
        $this->start_time = microtime(true);

        //if ($agent_input == null) {$agent_input = "";}

        $this->agent_input = $agent_input;

        $this->thing = $thing;
        $this->start_time = $this->thing->elapsed_runtime();
        $this->thing_report['thing'] = $this->thing->thing;

        $this->agent_name = "calendardate";
        $this->agent_prefix = 'Agent "Calendardate" ';

        $this->thing->log($this->agent_prefix . 'running on Thing '. $this->thing->nuuid . '.',"INFORMATION");

        // I'm not sure quite what the node_list means yet
        // in the context of headcodes.
        // At the moment it seems to be the headcode routing.
        // Which is leading to me to question whether "is"
        // or "Place" is the next Agent to code up.  I think
        // it will be "Is" because you have to define what 
        // a "Place [is]".
 //       $this->node_list = array("start"=>array("stop 1"=>array("stop 2","stop 1"),"stop 3"),"stop 3");
 //       $this->thing->choice->load('headcode');

        $this->keywords = array('now','next', 'accept', 'clear', 'drop','add','new');

        // You will probably see these a lot.
        // Unless you learn headcodes after typing SYNTAX.

        $this->current_time = $this->thing->json->time();

		$this->test= "Development code"; // Always iterative.

        // Non-nominal
        $this->uuid = $thing->uuid;
        $this->to = $thing->to;
        // Potentially nominal
        $this->subject = $thing->subject;
        // Treat as nominal
        $this->from = $thing->from;

        // Agent variables
        $this->sqlresponse = null; // True - error. (Null or False) - no response. Text - response

        $this->state = null; // to avoid error messages

        $this->calendardate = new Variables($this->thing, "variables calendardate " . $this->from);

        //$this->subject = "Let's meet at 10:00";

        // Read the subject to determine intent.
		$this->readSubject();

        // Generate a response based on that intent.
        // I think properly capitalized.
        //$this->set();

        if ($this->agent_input == null) {
		    $this->Respond();
        }

        $this->set();

        $this->thing->log( $this->agent_prefix .' ran for ' . number_format($this->thing->elapsed_runtime() - $this->start_time) . 'ms.' );

        $this->thing_report['log'] = $this->thing->log;

		return;
    }

    function makeCalendardate($input = null)
    {

        if ($input == null) {
            $input_time = $this->current_time;
        } else {
            $input_time = $input;
        }


        if (strtoupper($input) == "X") {
            $this->calendar_date  = "X";
            return $this->calendar_date;
        }

        $t = strtotime($input_time);

        //echo $t->format("Y-m-d H:i:s");
        $this->day = date("d",$t);
        $this->month =  date("m",$t);
        $this->year = date("Y",$t);


        $this->calendar_date = $this->year."-" . $this->month ."-" .$this->day;

        //if ($input == null) {$this->clocktime = $train_time;}
        return $this->calendar_date;
    }


    function test()
    {
        $test_corpus = file_get_contents("/var/www/html/stackr.ca/resources/clocktime/test.txt");
        $test_corpus = explode("\n", $test_corpus);

        $this->response = "";
        foreach ($test_corpus as $key=>$line) {

            if ($line == "-") {break;}
            $this->extractCalendardate($line);

            $line."<br>".
            "year " . $this->year . " month " . $this->month . " day " . $this->day . "<br>".
            "<br>";
        }
    }

    function set()
    {
        //$this->head_code = "0Z15";
        //$headcode = new Variables($this->thing, "variables headcode " . $this->from);

        //$this->refreshed_at = $this->current_time;

        if (!isset($this->refreshed_at)) {$this->refreshed_at = $this->thing->time();}
        //$string_coordinate = $this->stringCoordinate($this->coordinate);
//        $quantity = $this->quantity;
//        if (($this->quantity == true) and (!is_numeric($this->quantity))) {return;}

        //$this->refreshed_at = $this->current_time;
//        $quantity_variable = new Variables($this->thing, "variables quantity " . $this->from);


        $this->calendardate->setVariable("refreshed_at", $this->refreshed_at);
        $this->calendardate->setVariable("year", $this->year);
        $this->calendardate->setVariable("month", $this->month);
        $this->calendardate->setVariable("day", $this->day);


        $this->thing->log( 'saved '  . $this->year . " " . $this->month  . " " . $this->day . ".", "DEBUG" );

        return;
    }

/*
    function getVariable($variable_name = null, $variable = null) {

        // This function does a minor kind of magic
        // to resolve between $variable, $this->variable,
        // and $this->default_variable.

        if ($variable != null) {
            // Local variable found.
            // Local variable takes precedence.
            return $variable;
        }

        if (isset($this->$variable_name)) {
            // Class variable found.
            // Class variable follows in precedence.
            return $this->$variable_name;
        }

        // Neither a local or class variable was found.
        // So see if the default variable is set.
        if (isset( $this->{"default_" . $variable_name} )) {

            // Default variable was found.
            // Default variable follows in precedence.
            return $this->{"default_" . $variable_name};
        }

        // Return false ie (false/null) when variable
        // setting is found.
        return false;
    }
*/

    function getRunat()
    {

        if (!isset($this->calendardate)) {
            if (isset($calendardate)) {
               $this->calendardate = $calendardate;
            } else {
                $this->calendardate = "Meep";
            }
        }
        return $this->calendardate;

    }

    function get($run_at = null)
    {

        $this->last_refreshed_at = $this->calendardate->getVariable('refreshed_at');


        $this->day = $this->calendardate->getVariable("day");
        $this->month = $this->calendardate->getVariable("month");
        $this->year = $this->calendardate->getVariable("year");


        return;
    }

    function extractCalendardate($input = null) 
    {

        if (is_numeric($input)) {
            // See if we received a unix timestamp number
            $input = date('Y-m-d H:i:s', $input);
        }

        $this->parsed_date = date_parse($input);

        $this->year = $this->parsed_date['year']; 
        $this->month = $this->parsed_date['month']; 
        $this->day = $this->parsed_date['day']; 



        if (($this->year == false) and ($this->month == false) and ($this->day == false)) {

            // Start here
            $this->year = "X";
            $this->month = "X";
            $this->day = "X";

/*
            // Test for non-recognized edge case
            if (preg_match("(o'clock|oclock)", $input) === 1) {
//                require_once '/var/www/html/stackr.ca/agents/number.php';
                $number_agent = new Number($this->thing, "number " . $input);
                if (count($number_agent->numbers) == 1) {
                    $this->hour = $number_agent->numbers[0];
                    if ($this->hour > 12) {$this->hour = "X";}
              }
            }
*/
/*
            // Test for non-recognized edge case
            if (strpos($input, '0000') !== false) {
                $this->minute = 0;
                $this->hour = 0;
            }
*/
            if (($this->year == "X") and ($this->month == "X") and ($this->day == "X")) {return null;}
        }

        return array($this->year, $this->month, $this->day);
    }

    function read()
    {
//        $this->thing->log("read");
        return;
    }

    function makeTXT() {
        $txt = $this->sms_message;

        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;





    }

    public function makeWeb() {

        if (!isset($this->response)) {$this->response = "meep";}

        $m = '<b>' . ucwords($this->agent_name) . ' Agent</b><br>';

        //$m .= "CLOCKTIME<br>";
        $m .= "year " . $this->year . "month " . $this->month . " day " . $this->day . "<br>";

        //$sms_message .= " | " . $this->headcodeTime($this->start_at);
        $m .= $this->response;

        $this->web_message = $m;
        $this->thing_report['web'] = $m;
    }



    public function makeSMS()
    {
        $sms_message = "CALENDARDATE";
        //$sms_message .= " | " . $this->headcodeTime($this->start_at);
        $sms_message .= " | year " . $this->year. " month " . $this->month . " day " . $this->day;

        if (isset($this->response)) {$sms_message .= " | " . $this->response;}

        $this->sms_message = $sms_message;
        $this->thing_report['sms'] = $sms_message;
    }

	private function Respond()
    {
		// Thing actions

		$this->thing->flagGreen();

		// Generate email response.

		$to = $this->thing->from;
		$from = "calendardate";


		//$choices = $this->thing->choice->makeLinks($this->state);
        $choices = false;
		$this->thing_report['choices'] = $choices;

        //$this->makeTXT();

        $this->makeSMS();

	    $this->thing_report['email'] = $this->sms_message;
		$this->thing_report['message'] = $this->sms_message; // NRWTaylor 4 Oct - slack can't take html in $test_message;

        if (!$this->thing->isData($this->agent_input)) {
            $message_thing = new Message($this->thing, $this->thing_report);
            $this->thing_report['info'] = $message_thing->thing_report['info'] ;
        } else {
            $this->thing_report['info'] = 'Agent input was "' . $this->agent_input . '".' ;
        }

        $this->makeTXT();
        $this->makeweb();

        $this->thing_report['help'] = 'This is a calendardate.  Extracting clock times from strings.';

		return;

	}

    function isData($variable)
    {
        if (
            ($variable !== false) and
            ($variable !== true) and
            ($variable != null) ) {
            return true;
        } else {
            return false;
        }
    }

    public function readSubject()
    {
        if ($this->agent_input == "test") {$this->test(); return;}

        $this->num_hits = 0;

        $keywords = $this->keywords;

        if ($this->agent_input != null) {
            // If agent input has been provided then
            // ignore the subject.
            // Might need to review this.

//$assume_time = date('Y-m-d H:i:s', $this->agent_input);
//$assume_string = date('Y-m-d H:i:s', str_to_time($this->agent_input));

//echo $this->agent_input ." > " . $assume_time . " " . $assume_string . "\n";

            $input = strtolower($this->agent_input);
        } else {
            $input = strtolower($this->subject);
        }

        $prior_uuid = null;

        // Is there a clocktime in the provided datagram
        $this->extractCalendardate($input);
        if ($this->agent_input == "extract") {$this->response = "Extracted a calendardate.";return;}

        $pieces = explode(" ", strtolower($input));


     if (count($pieces) == 1) {
            if ($input == 'calendardate') {
                $this->get();

                $this->refreshed_at = $this->last_refreshed_at;

                $this->response = "Last 'calendardate' retrieved.";
                return;
            }

        }

        foreach ($pieces as $key=>$piece) {
            foreach ($this->keywords as $command) {
                if (strpos(strtolower($piece),$command) !== false) {
                    switch($piece) {
                        case 'now':
                            $this->thing->log("read subject nextheadcode");
                            $t = $this->thing->time();
                            $this->extractCalendardate($t);
                            $this->response = "Got server date.";

                            return;

                    }
                }
            }
        }

//        if (($this->minute == "X") and ($this->hour == "X")) {
        if (($this->year == "X") and ($this->month == "X") and ($this->day == "X")) {

            $this->get();
            $this->response = "Last calendardate retrieved.";
        }
/*
        // Added in test 2018 Jul 26
        if (($this->minute == false) and ($this->hour == false)) {

            $t = $this->thing->time();
            $this->extractClocktime($t);
            $this->response = "Got server time.";
        }
*/
        return "Message not understood";

	}
}

?>


<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

class Fuel
{

    // This is a place.

    //

    // This is an agent of a place.  They can probaby do a lot for somebody.
    // With the right questions.

    public $var = 'hello';

    function __construct(Thing $thing, $agent_input = null)
    {
//echo "meep";
//exit();
//        $this->start_time = microtime(true);

        //if ($agent_input == null) {$agent_input = "";}
        $this->agent_input = $agent_input;

        $this->thing = $thing;
        $this->start_time = $this->thing->elapsed_runtime();
        $this->thing_report['thing'] = $this->thing;

        $this->agent_prefix = 'Agent "Fuel" ';

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

        $this->keywords = array('fuel','next', 'last', 'nearest', 'accept', 'clear', 'drop','add','new','here','there');

//        $this->default_quantity = $this->thing->container['api']['quantity']['default_quantity'];
        $this->default_fuel_quantity = 0;
        $this->default_fuel_name = "NAME";
        $this->default_fuel_units = "L";

        $this->resource_path = $GLOBALS['stack_path'] . 'resources/';

        $this->default_alias = "Thing";
        $this->current_time = $this->thing->time();

		$this->test= "Development code"; // Always iterative.

        // Non-nominal
        $this->uuid = $thing->uuid;
        $this->to = $thing->to;

        $this->getFuel();

        // Potentially nominal
        $this->subject = $thing->subject;

        // Treat as nominal
        $this->from = $thing->from;

        // Agent variables
        $this->sqlresponse = null; // True - error. (Null or False) - no response. Text - response

        $this->state = null; // to avoid error messages
        $this->getFuel();
        // Read the subject to determine intent.

		$this->readSubject();

        // Generate a response based on that intent.
        // I think properly capitalized.

        $this->Respond();

        if ($this->agent_input != "extract") {
            $this->set();
        }

        $this->thing->log( $this->agent_prefix .' loaded fuel ' . $this->fuel . "." );

        $this->thing->log( $this->agent_prefix .' ran for ' . number_format($this->thing->elapsed_runtime() - $this->start_time) . 'ms.' );
        $this->thing_report['log'] = $this->thing->log;

        $this->thing_report['response'] = $this->response;

		return;
    }

    function set()
    {

        $this->quantity_agent->quantity = 999;

        if (!isset($this->refreshed_at)) {$this->refreshed_at = $this->thing->time();}

//        $fuel = $this->fuel;
        if (($this->fuel == true) and (!is_numeric($this->fuel))) {return;}

        //$this->refreshed_at = $this->current_time;
        $fuel_variable = new Variables($this->thing, "variables fuel " . $this->from);

        $this->quantity_agent->quantity = $this->fuel_quantity;
        $this->name = "FUEL";

        $fuel_variable->setVariable("name", $this->fuel_name);
        $fuel_variable->setVariable("quantity", $this->fuel_quantity);
        $fuel_variable->setVariable("units", $this->fuel_units);

        $fuel_variable->setVariable("refreshed_at", $this->refreshed_at);

        $this->thing->log( $this->agent_prefix .' set ' . $this->fuel . ".", "INFORMATION" );

        //$coordinate_variable = new Variables($this->thing, "variables " . $string_coordinate . " " . $this->from);
        //$coordinate_variable->setVariable("refreshed_at", $this->refreshed_at);

        return;
    }
/*
    function isCoordinate()
    {
        $place_zone = "05";
        //$place_code = $place_zone  . str_pad(rand(0,999) + 1,6,  '0', STR_PAD_LEFT);

        foreach (range(1,9999) as $n) {
            foreach($this->coordinates as $coordinate) {

                $place_code = $place_zone . str_pad($n, 4, "0", STR_PAD_LEFT);
                if ($this->getCoordinate($coordinate)) {
                    // Code doesn't exist
                    break;
                }
            }
            if ($n >= 9999) {
                $this->thing->log("No Place code available in zone " . $place_zone .".", "WARNING");
                return;
            }
        }
    }
*/
/*
    function nextCode()
    {
        $place_code_candidate = null;

        foreach ($this->coordinates as $place) {
            $place_code = strtolower($place['code']);
            if (($place_code == $place_code_candidate) or ($place_code_candidate == null)) {
                $place_code_candidate = str_pad(rand(100,9999) , 8, " ", STR_PAD_LEFT);
            }
        }

//        $place_code = str_pad(RAND(1,99999), 8, " ", STR_PAD_LEFT);
        return $place_code;
    }
*/
/*
    function nextCoordinate() {

        $this->thing->log("next coordinate");
        // Pull up the current headcode
        $this->get();

        // Find the end time of the headcode
        // which is $this->end_at

        // One minute into next headcode
        $quantity = 1;
        $next_time = $this->thing->json->time(strtotime($this->end_at . " " . $quantity . " minutes"));

        $this->get($next_time);

        // So this should create a headcode in the next quantity unit.

        return $this->available;

    }
*/
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

    function getFuel($selector = null)
    {

        $this->is_agent = new Is($this->thing, "fuel");
        $this->last_fuel_name = "FUEL"; //$is_agent->name; 

//        $this->quantity_agent = new Quantity($this->thing, "fuel" . $this->last_fuel_name);
        $this->quantity_agent = new Quantity($this->thing, "fuel" . $this->last_fuel_name);

        $this->last_fuel_quantity =  $this->quantity_agent->quantity;

        //$this->units_agent($this->thing, "fuel" . $this->fuel_name);
        $this->last_fuel_units = "L"; //$units_agent->units.
    

        $this->fuel_name = $this->last_fuel_name;
        $this->fuel_quantity = $this->last_fuel_quantity;

        $this->fuel_units = $this->last_fuel_units;
    }

    function is_positive_integer($str)
    {
        return (is_numeric($str) && $str > 0 && $str == round($str));
    }

    private function get($fuel = null)
    {
        // This is a request to get the Place from the Thing
        // and if that doesn't work then from the Stack.
//        if ($place_code == null) {$place_code = $this->place_code;}

        //$this->quantity_variable = new Variables($this->thing, "variables " . $quantity . " " . $this->from);
        $this->fuel_variable = new Variables($this->thing, "variables " . "fuel" . " " . $this->from);


        $fuel = $this->variables_fuel->getVariable("fuel");

//        $this->quantity = $this->arrayQuantity($quantity);
        $this->fuel = $fuel;

        $this->refreshed_at = $this->variables_fuel->getVariable("refreshed_at");

//var_dump($this->fuel);

        return $this->fuel;
    }

    function dropFuel() {
        $this->thing->log($this->agent_prefix . "was asked to drop a Fuel.");


        // If it comes back false we will pick that up with an unset headcode thing.

        if (isset($this->fuel)) {
            $this->fuel->Forget();
            $this->fuel = null;
        }

        $this->get();
 
    }


    function makeFuel($fuel = null)
    {
        if ($fuel == null) {return true;}
//var_dump($coordinate);


/*
        if (!isset($this->coordinates)) {$this->getCoordinates();}

        // See if the coordination is already tagged
        foreach ($this->coordinates as $coordinate_object) {
            if ($coordinate == $coordinate_object['coordinate']) {return true;}
        }
*/
        //if ($coordinate == null) {$coordinate = $this->nextCode();}
        $this->thing->log('Agent "Fuel" will make a Fuel for ' . $this->stringFuel($fuel) . ".");

        $this->current_fuel = $fuel;
        $this->fuel = $fuel;
        $this->refreshed_at = $this->current_time;
  
        // This will write the refreshed at.
        $this->set();

      //      $this->getCoordinate();
      //      $this->getPlace($this->place_code);

      //      $this->place_thing = $this->thing;

        $this->thing->log('Agent "Fuel" found a Fuel and pointed to it.');
    }

    function fuelTime($input = null)
    {
        if ($input == null) {
            $input_time = $this->current_time;
        } else {
            $input_time = $input;
        }

        if ($input == "x") {
            $fuel_time = "x";
            return $fuel_time;
        }

        $t = strtotime($input_time);

        $this->hour = date("H",$t);
        $this->minute =  date("i",$t);

        $fuel_time = $this->hour . $this->minute;

        if ($input == null) {$this->fuel_time = $fuel_time;}

        return $fuel_time;
    }

    // Currently just tuple extraction.
    // With negative numbers.

    public function extractQuantities($input = null)
    {

        $number = new Number($this->thing, "number");
//var_dump ($number->numbers);
//exit();

        $numbers = $number->numbers;

        //$this->coordinates = array_unique($this->coordinates);
        return $numbers;
    }

    public function extractFuel($input)
    {
        $this->fuel_name = null;

        if (is_array($input)) {$this->fuel_name = true; return;}

        $quantities = $this->extractQuantities($input);


        if ( (is_array($quantities)) and (count($quantities) == 1)) {
        //if ( ( count($coordinates) ) == 1) {
            if (isset($quantities[0])) {$this->fuel_name = $quantities[0];}

            $this->thing->log( $this->agent_prefix  . 'found a fuel ' . $this->fuel_name . ' in the text.');
            return $this->fuel_name;
        }


        //if (count($place_codes == 0)) {return false;}
        //if (count($place_codes > 1)) {return true;}

        // And then extract place names.
        // Take out word 'place' at the start.
//        $filtered_input = ltrim(strtolower($input), "place");

        if ( (is_array($quantities)) and (count($quantities) == 1)) {
        //if (count($coordinates) == 1) {
            $this->fuel_quantity = $this->quantities[0];
        }
        return $this->fuel_quantity;
    }

    // Assert that the string has a coordinate.
    function assertFuel($input)
    {

        if (($pos = strpos(strtolower($input), "fuel is")) !== FALSE) { 
            $whatIWant = substr(strtolower($input), $pos+strlen("fuel is")); 
        } elseif (($pos = strpos(strtolower($input), "fuel")) !== FALSE) { 
            $whatIWant = substr(strtolower($input), $pos+strlen("fuel")); 
        }

        $filtered_input = ltrim(strtolower($whatIWant), " ");

        $is_fuel = $this->extractFuel($filtered_input);

        if ($is_fuel) {
            //true so make a place
            $this->makeFuel($this->fuel_name);
        }

    }

    function read()
    {
        $this->thing->log("read");

//        $this->get();
        //return $this->available;
    }

    function addFuel($number)
    {
        //$this->makeHeadcode();
        $this->get();
        var_dump($this->fuel_quantity);
        $this->fuel_quantity += $number;
        var_dump($this->fuel_quantity);

        return;
    }

    public function makeWeb()
    {
        $test_message = "<b>FUEL " . $this->fuel_name . "</b>" . '<br>';

        if (!isset($this->refreshed_at)) {
            $test_message .= "<br>Thing just happened.";
        } else {
            $refreshed_at = $this->refreshed_at;

            $test_message .= "<p>";
            $ago = $this->thing->human_time ( strtotime($this->thing->time()) - strtotime($refreshed_at) );
            $test_message .= "<br>Thing happened about ". $ago . " ago.";
        }
        //$test_message .= '<br>' .$this->whatisthis[$this->state] . '<br>';

        //$this->thing_report['sms'] = $this->message['sms'];
        $this->thing_report['web'] = $test_message;


    }

    function makeTXT()
    {
        if (!isset($this->fuel_name)) {$txt = "Not here";} else {

//            $txt = 'These are FUELs for RAILWAY ' . $this->last_fuel_variable->nuuid . '. ';
        }
        $txt .= "\n";
        $txt .= "\n";


        $txt .= " " . str_pad("FUEL", 19, " ", STR_PAD_RIGHT);
        $txt .= " " . str_pad("REFRESHED AT", 25, " ", STR_PAD_RIGHT);

        $txt .= "\n";
        $txt .= "\n";

        // Places must have both a name and a code.  Otherwise it's not a place.

        $txt .= "\n";
        $txt .= "Last fuel " . $this->last_fuel_quantity . "\n";
        $txt .= "Now at " . $this->fuel_quantity;

        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;
    }

    // String to array
    function arrayFuel($input) 
    {

        //if (is_array($input)) {echo "meep";exit();}

        $quantities = $this->extractQuantities($input);

        $fuel_array = true;

//prod
//var_dump($coordinates);
//        if (is_array($coordinates)) {

//        if (count($coordinates) == 1) {$coordinate_array = $coordinates[0];}
//        }

        if ( (is_array($quantities)) and (count($quantities) == 1)) {
            $fuel_array = $quantities[0];
        }


//exit();

        return $fuel_array;

    }

    function stringFuel($fuel_string = null)
    {

       if ($fuel_string == null) {$fuel_string = $this->fuel_quantity . " " . $this->fuel_units . " " .$this->fuel_name;}

//        if (!is_array($quantity)) {$this->quantity_string = true; return $this->quantity_string;}
       if (is_array($fuel_string)) {$this->fuel_string = true; return $this->fuel_string;}

       $this->fuel_string = "" . $this->fuel_quantity . " " .$this->fuel_units . " " . $this->fuel_name;
       return $this->fuel_string;
    }

    public function makeSMS()
    {
        $this->inject = null;
        $s = $this->inject;
//echo "makesms";
//echo implode(" ", $this->coordinate);
//echo "\n";
$string_fuel = $this->stringFuel();
//echo $string_coordinate;


        $sms = "FUEL " . $string_fuel;

        if ((!empty($this->inject))) {
            $sms .= " | " . $s;
        } 

        $this->sms_message = $sms;
        $this->thing_report['sms'] = $sms;

    }

	private function Respond()
    {
		// Thing actions

		$this->thing->flagGreen();

		// Generate email response.

		$to = $this->thing->from;
		$from = "fuel";


		//$choices = $this->thing->choice->makeLinks($this->state);
        $choices = false;
		$this->thing_report['choices'] = $choices;

// Get available for place.  This would be an available agent.
//$available = $this->thing->human_time($this->available);

        // Allow for indexing.
        if (!isset($this->index)) {
            $index = "0";
        } else {
            $index = $this->index;//
        }

        $this->makeSMS();
        $this->makeWeb();

		$test_message = 'Last thing heard: "' . $this->subject . '".  Your next choices are [ ' . $choices['link'] . '].';
		$test_message .= '<br>headcode state: ' . $this->state . '<br>';

		$test_message .= '<br>' . $this->sms_message;

    	$this->thing_report['email'] = $this->sms_message;
		$this->thing_report['message'] = $this->sms_message; // NRWTaylor 4 Oct - slack can't take html in $test_message;

        if (!$this->thing->isData($this->agent_input)) {
            $message_thing = new Message($this->thing, $this->thing_report);
            $this->thing_report['info'] = $message_thing->thing_report['info'] ;
        } else {
            $this->thing_report['info'] = 'Agent input was "' . $this->agent_input . '".' ;
        }

        $this->makeTXT();

        $this->thing_report['help'] = 'This is a Fuel.';

		return;
	}

    function isData($variable) {
        if (
            ($variable !== false) and
            ($variable !== true) and
            ($variable != null) ) {
            return true;

        } else {
            return false;
        }
    }

/*
    function lastFuel()
    {
        $this->last_fuel_variable = new Variables($this->thing, "variables fuel " . $this->from);
       // $this->last_coordinate = $this->last_coordinate_variable->getVariable('coordinate');

        // Get a textual representation of a coordinate
        $fuel = $this->last_fuel_variable->getVariable("fuel");

        // Turn the text into an array
//        $this->last_quantity = $this->arrayQuantity($quantity);
        $this->last_fuel = $fuel;  
        // This doesn't work
        $this->last_refreshed_at = $this->last_fuel_variable->getVariable('refreshed_at');

        return;

        // So do it the hard way

        if (!isset($this->quantities)) {$this->getQuantities();}
$last_fuel = $this->quantities[0];

$this->last_fuel = $last_fuel['fuel'];
$this->last_refreshed_at = $last_coordinate['refreshed_at'];
var_dump($last_fuel['fuel']);
//        foreach(array_reverse($this->coordinates) as $key=>$coordinate) {

//            if ($coordinate['coordinate'] == $this->last_coordinate) {
//                $this->last_refreshed_at = $coordinate['refreshed_at'];
//                break;
//            }


//        }
//exit();


    }
*/
    public function readSubject() 
    {
        $this->response = null;
        $this->num_hits = 0;

        switch (true) {
            case ($this->agent_input == "extract"):
                //$input = strtolower($this->from . " " . $this->subject);
                $input = strtolower($this->subject);

                break;
            case ($this->agent_input != null):
                $input = strtolower($this->agent_input);
                break;
            case (true):
                // What is going to be found in from?
                // Allows place name extraction from email address.
                // Allows place code extraction from "from"
                // Internet of Things.  Sometimes all you have is the Thing's address.
                // And that's a Place.
                //$input = strtolower($this->from . " " . $this->subject);
                $input = strtolower($this->subject);

        }

        // Would normally just use a haystack.
        // Haystack doesn't work well here because we want to run the extraction on the cleanest signal.
        // Think about this.
		// $haystack = $this->agent_input . " " . $this->from . " " . $this->subject;

        $prior_uuid = null;

        // Is there a place in the provided datagram
//        $this->extractFuel($input);
        $number_agent = new Number($this->thing, "number");
        $number_agent->extractNumber($input);
//var_dump($number_agent->number);
//exit();

        if ($this->agent_input == "extract") {$this->response = "Extracted fuel(s).";return;}
        $pieces = explode(" ", strtolower($input));

		// So this is really the 'sms' section
		// Keyword

$this->getFuel();



        if (count($pieces) == 1) {
            if ($input == 'fuel') {

                //$this->lastFuel();

                $this->getFuel();

                $this->fuel_quantity = $this->last_fuel_quantity;
                $this->fuel_units= $this->last_fuel_units;
                $this->fuel_name = $this->last_fuel_name;

                $this->refreshed_at = $this->last_refreshed_at;

                $this->response = "Last fuel retrieved.";
                return;
            }
        }

        foreach ($pieces as $key=>$piece) {
            foreach ($this->keywords as $command) {
                if (strpos(strtolower($piece),$command) !== false) {

                    switch($piece) {

                        case 'next':
                            $this->thing->log("read subject next fuel");
                            $this->nextFuel();
                            break;

                        case 'drop':
                            //$this->thing->log("read subject nextheadcode");
                            $this->dropFuel();
                            break;

                        case 'add':
                            $this->addFuel($number_agent->number);
                            return;

                        case 'subtract':
                            $this->fuel_quantity= $this->fuel_quantity - $number_agent->number;
                            return;

                        case 'make':
                        case 'new':
                        case 'fuel':
                        case 'create':
//                        case 'add':

                            if (is_numeric($this->fuel_quantity)) {
                                $this->response = 'Asserted fuel and found ' . $this->stringFuel($this->fuel) .".";
                                return;
                            }

                            if ($this->fuel) { // Coordinate not provided in string
                                $this->getFuel();
                                $this->fuel = $this->last_fuel;
                                $this->refreshed_at = $this->last_refreshed_at;
                                $this->response = 'Asserted fuel and found last ' . $this->stringFuel($this->fuel) .".";
                                return;
                            }

                            $this->assertFuel(strtolower($input));

                            if (empty($this->fuel)) {$this->fuel = "X";}

                            $this->response = 'Asserted Fuel and found ' . $this->stringFuel($this->fuel) .".";

                            return;
                            break;

                        default:

                    }
                }
            }

        }


        // If at this point we get false/false, then the default Place has not been created.
//        if ( ($this->place_code == false) and ($this->place_name == false) ) {
//            $this->makePlace($this->default_place_code, $this->default_place_name);
//        }


        if ($this->fuel != null) {
            $this->getFuel($this->fuel);
            $this->thing->log($this->agent_prefix . 'using extracted fuel ' . $this->stringFuel() . ".","INFORMATION");
            $this->response = $this->stringFuel() . " used to retrieve a Fuel.";

            return;
        }

        if ($this->last_fuel != null) {
            $this->getFuel($this->last_fuel);
            $this->thing->log($this->agent_prefix . 'using extracted last_fuel ' . $this->last_fuel . ".","INFORMATION");
            $this->response = "Last fuel " . $this->last_fuel . " used to retrieve a Fuel.";

            return;
        }

        // so we get here and this is null placename, null place_id.
        // so perhaps try just loading the place by name

        $fuel = strtolower($this->subject);

        if ( !$this->getFuel(strtolower($fuel)) ){
            // Place was found
            // And loaded
            $this->response = $fuel . " used to retrieve a Fuel.";

            return;
        }

        //    function makePlace($place_code = null, $place_name = null) {
        $this->makeFuel($fuel);

        $this->thing->log($this->agent_prefix . 'using default_fuel ' . implode(" ",$this->default_fuel) . ".","INFORMATION");

        $this->response = "Made a Fuel called " . $fuel . ".";
        return;


        if (($this->isData($this->fuel)) or ($this->isData($this->fuel)) ) {
            $this->set();
            return;
        }

		return false;

	}
/*
	function kill()
    {
		// No messing about.
		return $this->thing->Forget();
	}
*/
}

/* More on places

Lots of different ways to number places.

*/
?>

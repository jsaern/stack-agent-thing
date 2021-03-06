<?php
/**
 * Consist.php
 *
 * @package default
 */


namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

//require '../vendor/autoload.php';
//require '/var/www/html/stackr.ca/vendor/autoload.php';
//require_once '/var/www/html/stackr.ca/agents/message.php';
//require '/var/www/html/stackr.ca/public/agenthandler.php'; // until the callAgent call can be
// factored to
// call agent 'Agent'

ini_set("allow_url_fopen", 1);

class Consist extends Agent {

    // This is a headcode.  You will probably want to read up about
    // the locomotive headcodes used by British Rail.
    //
    // A headcode takes the form (or did in the 1960s),
    // of NANN.  Where N is a digit from 0-9, and A is an uppercase character from A-Z.
    //
    // This implementation is recognizes lowercase and uppercase characters as the same.

    // The headcode is used by the Train agent to create the proto-train.

    // A headcode must have a route. Route is a text string.  Examples of route are:
    //  Gilmore > Hastings > Place
    //  >> Gilmore >>
    //  > Hastings

    // A headcode may have a consist. (Z - indicates train may fill consist.
    // X - indicates train should specify the consist. (devstack: "Input" agent)
    // NnXZ is therefore a valid consist. As is "X" or "Z".
    // A consist must always resolve to a locomotive.  Specified as uppercase letter.
    // The locomotive closest to the first character is the engine.  And gives
    // commands to following locomotives to follow.

    // This is the headcode manager.  This person is pretty special.

    public $var = 'hello';


    /**
     *
     * @param Thing   $thing
     * @param unknown $agent_input (optional)
     */
//    function __construct(Thing $thing, $agent_input = null) {
function init() {
//        if ($agent_input == null) {$agent_input = "";}

//        $this->agent_input = $agent_input;

//        $this->thing = $thing;
//        $this->thing_report['thing'] = $this->thing->thing;

//        $this->agent_prefix = 'Agent "Consist" ';

//        $this->thing->log('<pre> Agent "Consist" running on Thing '. $this->thing->nuuid . '.</pre>');


        // I'm not sure quite what the node_list means yet
        // in the context of Consists.
        // At the moment it seems to be the Consist routing.
        // Which is leading to me to question whether "is"
        // or "Place" is the next Agent to code up.  I think
        // it will be "Is" because you have to define what
        // a "Place [is]".
        $this->node_list = array("start"=>array("stop 1"=>array("stop 2", "stop 1"), "stop 3"), "stop 3");
        $this->thing->choice->load('Consist');

        $this->keywords = array('consist', 'clear', 'drop', 'add', 'load');

        $this->web_prefix = $this->thing->container['stack']['web_prefix'];


        // So around this point I'd be expecting to define the variables.
        // But I can do that in each agent.  Though there will be some
        // common variables?

        // So here is building block of putting a Consist in each Thing.
        // And a little bit of work on a common variable framework.

        // Factor in the following code.

        //                'Consist' => array('default run_time'=>'105',
        //                                'negative_time'=>'yes'),

        //$this->default_run_time = $this->thing->container['api']['Consist']['default run_time'];
        //$this->negative_time = $this->thing->container['api']['Consist']['negative_time'];

        // But for now use this below.

        // You will probably see these a lot.
        // Unless you learn Consists after typing SYNTAX.

        $this->default_variable = "0Z10";
        $this->default_alias = "Thing";

        $this->current_time = $this->thing->json->time();

        $this->resource_path = $GLOBALS['stack_path'] . 'resources/';
        $this->agents_path = $GLOBALS['stack_path'] . 'agents/';
        $this->agents_path = $GLOBALS['stack_path'] . 'vendor/nrwtaylor/stack-agent-thing/agents/';


        // Loads in Consist variables.
        // This will attempt to find the latest variable
//        $this->get(); // Updates $this->elapsed_time as well as pulling in the current Consist

        // Now at this point a  "$this->Consist_thing" will be loaded.
        // Which will be re-factored eventaully as $this->variables_thing.

        // This looks like a reminder below that the json time generator might be creating a token.

        // So created a token_generated_time field.
        //        $this->thing->json->setField("variables");
        //        $this->thing->json->writeVariable( array("stopwatch", "request_at"), $this->thing->json->time() );

        //$this->thing->json->time()


        $this->test= "Development code"; // Always iterative.

        // Non-nominal
//        $this->uuid = $thing->uuid;
//        $this->to = $thing->to;

        // Potentially nominal
//        $this->subject = $thing->subject;

        $this->link = $this->web_prefix . 'thing/' . $this->uuid . '/consist';


//        $this->thing->log('<pre> Agent "Consist" received this Thing "'.  $this->subject . '".</pre>');


        // Treat as nominal
//        $this->from = $thing->from;


        // Agent variables
//        $this->sqlresponse = null; // True - error. (Null or False) - no response. Text - response



        // Read the subject to determine intent.
//        $this->readSubject();

        // Generate a response based on that intent.
        // I think properly capitalized.
//        $this->respond();



//        $this->thing->log('<pre> Agent "Consist" completed</pre>');

//        $this->thing_report['log'] = $this->thing->log;

    }





    /**
     *
     */
    function set() {

        // A Consist has some remaining amount of resource and
        // an indication where to start.

        // This makes sure that
        if (!isset($this->consist_thing)) {
            $this->consist_thing = $this->thing;
        }

        $this->consist_thing->json->setField("variables");
        $this->consist_thing->json->writeVariable( array("consist", "variable"), $this->variable );
        $this->consist_thing->json->writeVariable( array("consist", "refreshed_at"), $this->current_time );
    }


    /**
     *
     * @param unknown $variable_name (optional)
     * @param unknown $variable      (optional)
     * @return unknown
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


    /**
     *
     * @param unknown $variable (optional)
     * @return unknown
     */
    function get($variable = null) {

        // Loads current Consist into $this->Consist_thing

        $match = false;

        $variable = $this->getVariable('consist', $variable);


        $consist_things = array();
        // See if a Consist record exists.
        $findagent_thing = new \Nrwtaylor\StackAgentThing\Findagent($this->thing, 'consist');

        // This pulls up a list of other Consist Things.
        // We need the newest Consist as that is most likely to be relevant to
        // what we are doing.

        $this->thing->log('Agent "Consist" found ' . count($findagent_thing->thing_report['things']) ." Consist Things." );

        $this->current_variable = null;

        $findagent_thing = new \Nrwtaylor\StackAgentThing\Findagent($this->thing, 'consist');

        foreach (array_reverse($findagent_thing->thing_report['things']) as $thing_obj) {

            $thing = new Thing ($thing_obj['uuid']);

            $thing->json->setField("variables");
            $thing->variable = $thing->json->readVariable( array('consist', "variable"))  ;
            $thing->refreshed_at = $thing->json->readVariable( array('consist', "refreshed_at"))  ;

            if ($thing->refreshed_at == false) {
                // Things is list sorted by date.  So this is the oldest Thing.
                // with a 'keyword' record.
                continue;
            } else {
                $this->useConsist($thing);
                return false;

            }

        }


        $this->makeConsist();


        return false;
    }


    /**
     *
     */
    function dropConsist() {
        $this->thing->log($this->agent_prefix . "was asked to drop a Consist.");


        // If it comes back false we will pick that up with an unset Consist thing.

        if (isset($this->consist_thing)) {
            $this->consist_thing->Forget();
            $this->consist_thing = null;
        }

        $this->get();
    }


    /**
     *
     * @param unknown $thing
     * @return unknown
     */
    function useConsist($thing) {

        $this->consist_thing = $thing;

        // Core elements of a Consist
        $this->variable = $thing->variable;

        return false;

    }


    /**
     *
     * @param unknown $variable (optional)
     */
    function makeConsist($variable = null) {


        $variable = $this->getVariable('consist', $variable);

        $this->thing->log('Agent "Consist" will make a Consist for ' . $variable . ".");

        // Check that the shift is okay for making Consists.


        // Otherwise we needs to make trains to run in the Consist.

        $this->thing->log($this->agent_prefix . "is going to run this for the default engine.");

        $this->current_variable = $variable;
        $this->variable = $variable;
        $this->consist = $variable;

        //            $this->consist_thing = $this->thing;



        // Write the variables to the db.
        $this->set();

        //$this->Consist_thing = $this->thing;

        $this->thing->log('Agent "Consist" found Consist and pointed to it.');

    }


    /**
     *
     * @param unknown $input
     * @return unknown
     */
    function extractAlpha($input) {

        $words = explode(" ", $input);

        $arr = array();

        foreach ($words as $word) {
            if (ctype_alpha($word)) {
                $arr[] = $word;
            }
        }


        return $arr;
    }


    /**
     *
     * @param unknown $input
     * @return unknown
     */
    function extractConsists($input) {

        //        $input = "Train is NbbbX";

        if (!isset($this->consists)) {
            $this->consists = array();
        }

        //        $pattern = "|^[A-Z0-9]{3}(?:List)?$|";

        $pattern = "|^[A-Z]*[A-Z]$|";
        $pattern = "|\w*[A-Z]\w*|";
        //Explanation:
        //^        : Start anchor
        //[A-Z0-9] : Char class to match any one of the uppercase letter or digit
        //{3}      : Quantifier for previous sub-regex
        //(?:List) : A literal 'List' enclosed in non-capturing parenthesis
        //?        : To make the 'List' optional
        //$        : End anchor
        //echo $input;

        preg_match_all($pattern, $input, $m);

        $possible_consists = $m[0];
        //array_pop($arr);
        //var_dump($possible_consists);


        if ((count($possible_consists) >= 1)  and ($possible_consists[0] == "Consist")) {
            array_shift($possible_consists);
        }

        $consists = array();
        // Then tweak selection?
        foreach ($possible_consists as $possible_consist) {

            //echo $possible_consist;
            $consists[] = $possible_consist;
            //       $requested_locomotives =
            //       $requested_rollingstock =


        }


        //   if ($this->isData($this->variable)) {
        //       $this->makeConsist($this->variable);
        //        return;
        //   }
        $this->variables = $consists;
        //exit();

        //        $this->variables = $this->extractAlpha($input);

        return $this->variables;


        //        return $arr;

    }


    /**
     *
     * @param unknown $input
     * @return unknown
     */
    function getConsist($input) {

        $this->headcode_agent = new Headcode($this->thing, $input);
        $headcodes = $this->headcode_agent->getHeadcodes();

        $consist = array();
        foreach ($headcodes as $i=>$headcode) {
            if ($headcode['head_code'] == $this->headcode_agent->head_code) {$consist[] = $headcode;}

        }
// http://www.greatwestern.org.uk/stockcode.htm
        $this->consist_stock = array("engine", "carriage", "wagon", "caboose", "break van", "brake van", "coal wagon","toad","dogfish");

        $this->consist_string = "";
        $this->consist_array = $consist;
        foreach ($consist as $item) {

            foreach ($this->consist_stock as $j=>$stock_name) {
                //var_dump($item_name);
                if (strpos($item[0], $stock_name) !== false) {

                    $this->consist_string .= " " . $item[0];

                    //    echo 'true';
                }

            }

            //$this->consist_string .= " " . $item[0];
            //}

        }
        return;
        //var_dump($consist);
        //var_dump($headcode_agent->headcodes);
        //exit();
        $variables = $this->extractConsists($input);

        if (count($variables) == 1) {
            $this->variable = $variables[0];
            $this->thing->log('Agent "Consist" found a Consist (' . $this->variable . ') in the text.');
            $this->consist = $this->variable;

            //echo $this->consist;
            //exit();
            return $this->consist;
        }

        if (count($variables) == 0) {return false;}
        if (count($variables) > 1) {return true;}

        return true;
    }




    /**
     *
     * @return unknown
     */
    function read() {
        $this->thing->log("read");

        //        $this->get();
        return $this->variable;
    }



    /**
     *
     */
    function addConsist() {
        $this->makeConsist();
        $this->get();
        return;
    }



    /**
     *
     */
    public function respond() {

        // Thing actions

        $this->thing->flagGreen();

        // Generate email response.

        $to = $this->thing->from;
        $from = "consist";

        //echo "<br>";

        //  $choices = $this->thing->choice->makeLinks($this->state);
        //  $this->thing_report['choices'] = $choices;

        //       $choices = $this->thing->choice->makeLinks($this->state);
        $this->thing_report['choices'] = false;


        //echo "<br>";
        //echo $html_links;


        if (!isset($this->index)) {
            $index = "0";
        } else {
            $index = $this->index;
        }

        //$s = $this->Consist_thing->state;
        $s = "GREEN";

        if ((!isset($this->consist)) or ($this->consist == null)) {
            $this->consist = "Z";
            //$this->getConsist();
        }
        $sms_message = "CONSIST ";
        $sms_message .= strtoupper($this->headcode_agent->head_code);
        $sms_message .= " | " . $this->consist;
        $sms_message .= " " . $this->link;

if (trim($this->consist_string) != "") {
   $sms_message .= " Consists of " . trim($this->consist_string);

}







        $test_message = $sms_message;
        //
        //  $test_message .= '<br>Current node: ' . $this->thing->choice->current_node;



        //  $test_message .= '<br>Requested state: ' . $this->requested_state;

        $this->thing_report['sms'] = $sms_message;
        $this->thing_report['email'] = $sms_message;
        $this->thing_report['message'] = $sms_message; // NRWTaylor 4 Oct - slack can't take html in $test_message;




        if (!$this->thing->isData($this->agent_input)) {
            $message_thing = new Message($this->thing, $this->thing_report);

            $this->thing_report['info'] = $message_thing->thing_report['info'] ;
        } else {
            $this->thing_report['info'] = 'Agent input was "' . $this->agent_input . '".' ;
        }



        //$this->thing->email->sendGeneric($to,$from,$this->subject, $test_message, $choices);

        $this->thing_report['help'] = 'This is a Consist.';


        //echo '<pre> Agent "Account" email NOT sent to '; echo $to; echo ' </pre>';
        //echo $message;

        return;


    }


    /**
     *
     * @param unknown $variable
     * @return unknown
     */
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


    /**
     *
     * @return unknown
     */
    public function readSubject() {
        $this->response = null;
        $this->num_hits = 0;

        $keywords = $this->keywords;

        if ($this->agent_input != null) {

            // If agent input has been provided then
            // ignore the subject.
            // Might need to review this.
            //$input = strtolower($this->agent_input);
            $input = $this->agent_input;
        } else {

            // $input = strtolower($this->subject);
            $input = $this->subject;
        }

        $haystack = $this->agent_input . " " . $this->from . " " . $this->subject;

        //  $this->requested_state = $this->discriminateInput($haystack); // Run the discriminator.

        $prior_uuid = null;

        // Updated $this->variable
        $this->getConsist($this->subject);

        //echo $this->consist;
        //echo $this->variable;
        //exit();


        $pieces = explode(" ", strtolower($input));


        // So this is really the 'sms' section
        // Keyword
        if (count($pieces) == 1) {

            if ($input == 'consist') {

                //echo "readsubject Consist";
                $this->read();
                return;
            }

            // Drop through
        }


        // Extract runat signal
        $matches = 0;




        /*
    if ((isset($this->run_time)) and (isset($this->run_at))) {
        // Good chance with both these set that asking for a new
        // Consist to be created, or to override existing Consist.
        $this->thing->log('Agent "Consist" found a run time.');

        $this->nextConsist();
        return;
    }
*/
        foreach ($pieces as $key=>$piece) {
            foreach ($keywords as $command) {
                if (strpos(strtolower($piece), $command) !== false) {

                    switch ($piece) {
                    case 'accept':
                        $this->acceptThing();
                        break;
                    case 'is':
                    case '=':
                        if ($this->isData($this->variable)) {
                            //echo $this->variable;
                            //exit();
                            $this->makeConsist($this->variable);
                            return;
                        }

                    case 'clear':
                        $this->clearThing();
                        break;

                    case 'drop':
                        //     //$this->thing->log("read subject nextConsist");
                        $this->dropConsist();
                        break;


                    case 'add':
                        //     //$this->thing->log("read subject nextConsist");
                        $this->makeConsist();
                        break;

                    default:
                        //$this->read();                                                    //echo 'default';

                    }

                }
            }

        }



        // Likely matching a variable to a uuid.

        //}


        //if ( (isset($this->run_at)) and (isset($this->quantity)) ) {
        //echo $this->variable;
        //var_dump( ($this->variable !== true) );
        //exit();
        //$this->variable = true;
        if ($this->isData($this->variable)) {
            $this->makeConsist($this->variable);
            return;
        }
        //exit();
        //    if ((isset($this->run_time)) and (isset($this->run_at))) {
        // Good chance with both these set that asking for a new
        // Consist to be created, or to override existing Consist.
        //        $this->thing->log('Agent "Consist" found a run time.');

        //        $this->nextConsist();
        //        return;
        //    }


        // If all else fails try the discriminator.

        $this->requested_state = $this->discriminateInput($haystack); // Run the discriminator.
        switch ($this->requested_state) {
        case 'start':
            $this->start();
            break;
        case 'stop':
            $this->stop();
            break;
        case 'reset':
            $this->reset();
            break;
        case 'split':
            $this->split();
            break;
        }

        $this->read();




        return "Message not understood";




        return false;


    }






    /**
     *
     * @return unknown
     */
    function kill() {
        // No messing about.
        return $this->thing->Forget();
    }


    /**
     *
     * @param unknown $input
     * @param unknown $discriminators (optional)
     * @return unknown
     */
    function discriminateInput($input, $discriminators = null) {


        //$input = "optout opt-out opt-out";

        if ($discriminators == null) {
            $discriminators = array('accept', 'clear');
        }



        $default_discriminator_thresholds = array(2=>0.3, 3=>0.3, 4=>0.3);

        if (count($discriminators) > 4) {
            $minimum_discrimination = $default_discriminator_thresholds[4];
        } else {
            $minimum_discrimination = $default_discriminator_thresholds[count($discriminators)];
        }



        $aliases = array();

        $aliases['accept'] = array('accept', 'add', '+');
        $aliases['clear'] = array('clear', 'drop', 'clr', '-');



        $words = explode(" ", $input);

        $count = array();

        $total_count = 0;
        // Set counts to 1.  Bayes thing...
        foreach ($discriminators as $discriminator) {
            $count[$discriminator] = 1;

            $total_count = $total_count + 1;
        }
        // ...and the total count.



        foreach ($words as $word) {

            foreach ($discriminators as $discriminator) {

                if ($word == $discriminator) {
                    $count[$discriminator] = $count[$discriminator] + 1;
                    $total_count = $total_count + 1;
                    //echo "sum";
                }

                foreach ($aliases[$discriminator] as $alias) {

                    if ($word == $alias) {
                        $count[$discriminator] = $count[$discriminator] + 1;
                        $total_count = $total_count + 1;
                        //echo "sum";
                    }
                }
            }

        }

        //echo "total count"; $total_count;
        // Set total sum of all values to 1.

        $normalized = array();
        foreach ($discriminators as $discriminator) {
            $normalized[$discriminator] = $count[$discriminator] / $total_count;
        }


        // Is there good discrimination
        arsort($normalized);


        // Now see what the delta is between position 0 and 1

        foreach ($normalized as $key=>$value) {
            //echo $key, $value;

            if ( isset($max) ) {$delta = $max-$value; break;}
            if ( !isset($max) ) {$max = $value;$selected_discriminator = $key; }
        }


        //echo '<pre> Agent "Consist" normalized discrimators "';print_r($normalized);echo'"</pre>';


        if ($delta >= $minimum_discrimination) {
            //echo "discriminator" . $discriminator;
            return $selected_discriminator;
        } else {
            return false; // No discriminator found.
        }

        return true;
    }


    /* More on Consists

http://myweb.tiscali.co.uk/gansg/3-sigs/bellhead.htm
1 Express passenger or mail, breakdown train en route to a job or a snow plough going to work.
2 Ordinary passenger train or breakdown train not en route to a job
3 Express parcels permitted to run at 90 mph or more
4 Freightliner, parcels or express freight permitted to run at over 70 mph
5 Empty coaching stock
6 Fully fitted block working, express freight, parcels or milk train with max speed 60 mph
7 Express freight, partially fitted with max speed of 45 mph
8 Freight partially fitted max speed 45 mph
9 Unfitted freight (requires authorisation) engineers train which might be required to stop in section.
0 Light engine(s) with or without brake vans

E     Train going to       Eastern Region
M         "     "     "         London Midland Region
N         "     "     "         North Eastern Region (disused after 1967)
O         "     "     "         Southern Region
S          "     "     "         Scottish Region
V         "     "     "         Western Region

*/
}

<?php
namespace Nrwtaylor\StackAgentThing;
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

//require '../vendor/autoload.php';
//require '/var/www/html/stackr.ca/vendor/autoload.php';
//require_once '/var/www/html/stackr.ca/agents/message.php';
//require_once '/var/www/html/stackr.ca/agents/headcode.php';
//require_once '/var/www/html/stackr.ca/agents/flag.php';
//require_once '/var/www/html/stackr.ca/agents/consist.php';
//require_once '/var/www/html/stackr.ca/agents/variables.php';


//require '/var/www/html/stackr.ca/public/agenthandler.php'; // until the callAgent call can be
								// factored to
								// call agent 'Agent'

ini_set("allow_url_fopen", 1);

class Cue 
{
    // This is a resource block.  It is a train which be run by the block scheduler.
    // It will respond to trains with a signal.
    // Red - Not available
    // Green - Slot allocated
    // Yellow - Next signal Red.
    // Double Yellow - Next signal Yellow

    // The block keeps track of the uuids of associated resources.
    // And checks to see what the block signal should be.  And pass and collect tokens.

    // This is the block manager.  They are an ex-British Rail signalperson.

    public $var = 'hello';

    function __construct(Thing $thing, $agent_input = null) {
        if ($agent_input == null) {$agent_input = "";}

        $this->agent_input = $agent_input;

        $this->keyword = "cue";

        $this->thing = $thing;
        $this->start_time = $this->thing->elapsed_runtime();
        $this->thing_report['thing'] = $this->thing->thing;

        $this->test= "Development code"; // Always

        $this->uuid = $thing->uuid;
        $this->to = $thing->to;
        $this->from = $thing->from;
        $this->subject = $thing->subject;
        $this->sqlresponse = null;

        $this->agent_prefix = 'Agent "Cue" ';

// $this->node_list = array("green"=>array("red"=>array("green","red"),"red"),"green3");
//$this->thing->choice->load('alias');
//        $this->node_list = array("off"=>array("on"=>array("off")));


        $this->current_time = $this->thing->json->time();

    //    $this->variables_agent = new Variables($this->thing, "variables alias " . $this->from);

        $this->keywords = array('cue','is');



        $this->current_time = $this->thing->json->time();

        $default_alias_name = "cue";


		$this->thing->log($this->agent_prefix . 'running on Thing '. $this->thing->nuuid . '.</pre>');
		$this->thing->log($this->agent_prefix . 'received this Thing "'.  $this->subject . '".</pre>');

		$this->readSubject();
        if ($this->agent_input == null) {
		    $this->respond();
        }
        $this->thing->log( $this->agent_prefix .' ran for ' . number_format($this->thing->elapsed_runtime()-$this->start_time) . 'ms.' );

        $this->thing_report['log'] = $this->thing->log;
//exit();

		return;
		}





    function set()
    {

        // A block has some remaining amount of resource and 
        // an indication where to start.


        // This makes sure that
        if (!isset($this->alias_thing)) {
            $this->alias_thing = $this->thing;
        }

  //      if ($requested_state == null) {
  //          $requested_state = $this->requested_state;
  //      }

        // Update calculated variables.
        //$this->alias_id = $this->context_id;

//        $this->variables_agent->setVariable("state", $requested_state);
        $this->variables_agent->setVariable("headcode", $this->head_code);

        $this->variables_agent->setVariable("context", $this->context);
        //$this->variables_agent->setVariable("alias_id", $this->alias_id); // exactly same as context id

        $this->variables_agent->setVariable("refreshed_at", $this->current_time);

//        $this->thing->choice->save('alias', $this->state);


//        $this->thing->json->writeVariable( array("cue", "state"), $requested_state );
        $this->thing->json->writeVariable( array("cue", "headcode"), $this->head_code );

        $this->thing->json->writeVariable( array("alias", "context"), $this->context );
//        $this->thing->json->writeVariable( array("alias", "alias_id"), $this->alias_id ); // exactly same as context_id

        $this->thing->json->writeVariable( array("cue", "refreshed_at"), $this->current_time );






//        $this->state = $requested_state;
        $this->refreshed_at = $this->current_time;

        return;
    }


    function getContext()
    {
//        require_once '/var/www/html/stackr.ca/agents/context.php';
        $this->context_agent = new Context($this->thing, "context");
        $this->context =  $this->context_agent->context;
        $this->context_id = $this->context_agent->context_id;
        return $this->context;
    }


    function getVariable($variable_name = null, $variable = null) {

        // This function does a minor kind of magic
        // to resolve between $variable, $this->variable,
        // and $this->default_variable.

        // Doesn't yet do it's magic with...
        // identity_variable
        // thing_variable
        // stack_variable

        // Prefer closest...
        // Or prefer furthest ...


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



    function getAlias($input = null) 
    {
        // Extract everything to the right
        // of the first is or =
        $pieces = explode(" ", strtolower($input));

        if ($input == null) { 
            $this->alias = "X";
            return $this->alias;
        } else {
            $keywords = $this->keywords;
            foreach ($pieces as $key=>$piece) {

                switch($piece) {
                    case '=':
                    case 'is':
                        $key += 1;
                        $t = "";
                        while ($key  < count($pieces)) {
                            //$key = $key +1;
                            $t .= $pieces[$key] . " ";
                            $key += 1;
                        }
                        $this->alias = $t;
                        return $this->alias;
                }


            }
        }

        $this->alias = "X";
        return $this->alias;
    }

    function getHeadcode()
    {

        if ( (isset($this->head_code)) and (isset($this->headcode_thing)) ) { return $this->head_code;}

        $this->head_code = "0O00";

        return $this->head_code;

        $this->headcode_thing = new Headcode($this->variables_agent->thing, 'headcode '. $this->input);
        $this->head_code = $this->headcode_thing->head_code; 

        //if ($this->head_code == false) { // Didn't return a useable headcode.
        //    // So assign a 'special'.
        //    //$this->head_code = "0Z" . str_pad($this->index + 11,2, '0', STR_PAD_LEFT);
        //    $this->head_code = "2Z99";
        //}

        // Not sure about the direct variable
        // probably okay if the variable is renamed to variable.  Or if $headcode_thing
        // resolves to the variable.

        return $this->head_code;
    }





	private function respond() {
		// Thing actions

		$this->thing->flagGreen();
		// Generate email response.

		$to = $this->thing->from;
		$from = "cue";

		//echo "<br>";

//		$choices = $this->thing->choice->makeLinks($this->state);
//		$this->thing_report['choices'] = $choices;
        $this->thing_report['choices'] = false;



        //$interval = date_diff($datetime1, $datetime2);
        //echo $interval->format('%R%a days');

        //$available = $this->thing->human_time($this->available);

		$sms_message = "CUE ";
        $sms_message .= " | context " . ucwords($this->context);
 //       $sms_message = " | Not recognized";


 //       $sms_message .= " | flag " . $this->flag;
 //       $sms_message .= " | alias " . strtoupper($this->alias);

 //       $sms_message .= " | nuuid " . substr($this->variables_agent->uuid,0,4); 
 //       $sms_message .= " | nuuid " . substr($this->alias_thing->uuid,0,4); 


 //       $sms_message .= " | context " . $this->context;
 //       $sms_message .= " | alias id " . $this->alias_id; 

        $sms_message .= " | rtime " . number_format($this->thing->elapsed_runtime()) . "ms";

        $sms_message .= " | TEXT ?";




		$test_message = 'Last thing heard: "' . $this->subject . '".';
//		$test_message .= '<br>Train state: ' . $this->state . '<br>';

		$test_message .= '<br>' . $sms_message;

//        $test_message .= '<br>run_at: ' . $this->run_at;
 //       $test_message .= '<br>end_at: ' . $this->end_at;


//		$test_message .= '<br>Requested state: ' . $this->requested_state;

			$this->thing_report['sms'] = $sms_message;
			$this->thing_report['email'] = $sms_message;
			$this->thing_report['message'] = $sms_message; // NRWTaylor 4 Oct - slack can't take html in $test_message;


                        $message_thing = new Message($this->thing, $this->thing_report);

                        $this->thing_report['info'] = $message_thing->thing_report['info'] ;



		//$this->thing->email->sendGeneric($to,$from,$this->subject, $test_message, $choices);

            $this->thing_report['help'] = 'This is the Cueing manager.  Normally only gets called when a Cue has not been recognized.';



		return;


	}


    public function readSubject()
    {
        $this->getContext();
		return false;
	}


	function kill() {
		// No messing about.
		return $this->thing->Forget();
	}

}

?>


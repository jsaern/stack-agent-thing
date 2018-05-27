<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

//require '../vendor/autoload.php';
//require '/var/www/html/stackr.ca/vendor/autoload.php';
//require_once '/var/www/html/stackr.ca/agents/message.php';
ini_set("allow_url_fopen", 1);

class FindAgent {

	public $var = 'hello';

 	function __construct(Thing $thing, $agent_input = null)
    {

        //$this->start_time = microtime(true);


        if ($agent_input == null) {$agent_input = null;}
        $this->agent_input = strtolower($agent_input);

        $this->agent_name = 'findagent';
        $this->agent_prefix = 'Agent "Findagent" ';
        $this->thing = $thing;
        $this->start_time = $this->thing->elapsed_runtime();
        $this->thing_report['thing'] = $this->thing->thing;


		// So I could call
		if ($this->thing->container['stack']['state'] == 'dev') {$this->test = true;}
		$this->api_key = $this->thing->container['api']['translink'];
        // Just some examples of pulling in settings from settings.php

		$this->retain_for = 4; // Retain target 4 hours.
		$this->time_units = "hrs";

        $this->verbosity = 1;

	    $this->uuid = $thing->uuid;
        $this->to = $thing->to;
        $this->from = $thing->from;
        $this->subject = $thing->subject;


        // I don't think either of these variables are used.
        $this->sms_message = "";
        $this->response = false;

		$this->sqlresponse = null;



        $this->horizon = 99;
        $this->requested_agent_name = 'thing';
        $this->readInstruction();



		// Allow for a new state tree to be introduced here.

        $ref_time = microtime(true);

        $this->node_list = array( "start"=> array("listen"=> array("say hello"=> array("listen") ),
                                       	        "new group"=>array("say hello") 
                                        ) );

        //$this->thing->choice->Create($this->agent_name, $this->node_list, "start");
        //$this->choices = $this->thing->choice->makeLinks('start');

        $this->choices = false;

//        $run_time = microtime(true) - $ref_time;
//        $milliseconds = round($run_time * 1000);
//        $this->thing->log( $this->agent_prefix .'choice init ran for ' . $milliseconds . 'ms.' );




//		$this->thing->log( '<pre> Agent "Find Agent" running on Thing ' . $this->thing->nuuid . '.</pre>' );
//		$this->thing->log( '<pre> Agent "Find Agent" received this Thing "' . $this->subject . '".</pre>');

        $ref_time = microtime(true);

        $this->findAgent($this->requested_agent_name);

//        $run_time = microtime(true) - $ref_time;
//        $milliseconds = round($run_time * 1000);
//        $this->thing->log( $this->agent_prefix .'findAgent call ran for ' . $milliseconds . 'ms.' );


		$this->readSubject();

		if ($this->response == true) {

                $this->thing_report['info'] = 'No matching agent found.';
                $this->thing_report['help'] = 'This is the "Find Group".';
                $this->thing_report['num_hits'] = $this->num_hits;

                if ($this->verbosity >= 2) {
                    $this->thing->log( $this->agent_prefix . 'returned ' . count($this->thing_report['things']) .' Things.', "DEBUG" );
                }
        $this->thing->log( $this->agent_prefix .'ran for ' . number_format($this->thing->elapsed_runtime()-$this->start_time) . 'ms.', "OPTIMIZE" );


 $this->thing_report['log'] = $this->thing->log;


                return;
		}


        $this->thing_report = $this->respond();

	    $this->thing_report['info'] = '"Find Agent" looks for matching previous Things.';
        $this->thing_report['help'] = 'This is the "Find Agent" manager.';
        $this->thing_report['num_hits'] = $this->num_hits;


if ($this->verbosity >= 2) {
$this->thing->log( $this->agent_prefix . 'returned ' . count($this->thing_report['things']) .' Things.', "DEBUG" );
}


//        $this->thing->log( $this->agent_prefix .'ran for ' . $this->thing->elapsed_runtime() . 'ms.' );
//        $this->thing->log( $this->agent_prefix .'. ' . number_format($this->thing->elapsed_runtime()) . 'ms.' );
        $this->thing->log( $this->agent_prefix .'ran for ' . number_format($this->thing->elapsed_runtime()-$this->start_time) . 'ms.', "OPTIMIZE" );


        $this->thing_report['log'] = $this->thing->log;


		return;

		}



	function findAgent($name = null, $id = null)
    {

        $ref_time = microtime(true);

		$thingreport = $this->thing->db->setUser($this->from);
		$thingreport = $this->thing->db->variableSearch(null, $name, $this->horizon);

        $run_time = microtime(true) - $ref_time;
        $milliseconds = round($run_time * 1000);
        $this->thing->log( $this->agent_prefix .'db call ran for ' . $milliseconds . 'ms.', "OPTIMIZE" );


		$groups = array();
        $agent_things = array();

		foreach ($thingreport['things'] as $thing_obj) {



			//$agent_thing = new Thing( $thing_obj['uuid'] );

         	//$agent_thing->json->setField("variables");
           	//$agent_thing_id = $agent_thing->json->readVariable( array($name, $name."_id") );

            if ($id == null) {
                // No id matching, just grab thing
                $agent_things[] = $thing_obj;

            } else {

                //  id matching
                //$agent_thing = new Thing( $thing_obj['uuid'] );
                //$agent_thing->json->setField("variables");
                //$agent_thing_id = $agent_thing->json->readVariable( array($name, $name."_id") );



            $uuid = $thing_object['uuid'];

            if ($thing_object['nom_to'] != "usermanager") {
                $match += 1;

                $thing= new Thing($uuid);
                $variables = $thing->account['stack']->json->array_data;

                if (isset($variables[$name])) {
                    $agent_thing_id = $variables[$name][$name."_id"];

                } else {
                    // No alias variable set
                    // Try the next one.
                    $agent_thing_id = null;
                    //break;
                }
            }




                if (!($agent_thing_id == false) or ($agent_thing_id == null)) {
                    $agent_things[] = $thing_obj;
                }
            }


		}


		if ( count($agent_things) == 0 ) {
			$this->sms_message .= "";
			$this->sms_message .= " | No agent thing found.";
			$this->thing_report['things'] = true;
		} else {
            $this->agent_thing_id = $agent_things[0];
			$this->sms_message .= ' | This is the "Find Agent" function.  Commands: none.';
			$this->thing_report['things'] = $agent_things; 
		}

		return $this->thing_report['things'];

	}

// -----------------------

	private function respond() {


		// Thing actions
		$this->thing->flagGreen();



		// Generate email response.

		$to = $this->thing->from;

		$from = "group";

		$this->thing_report['choices'] = false;


		$sms_end = strtoupper( strip_tags( $this->choices['link'] ) );

        $x = implode("", explode("FORGET", $sms_end, 2));

		$this->sms_message =  strtoupper($this->agent_name) . " | " . $this->sms_message . " | TEXT" . $x;


		$this->message = $this->sms_message;

        $this->thing_report['sms'] = $this->sms_message;
        $this->thing_report['email'] = $this->message;

        // $message_thing = new Message($this->thing, $this->thing_report);
        // $this->thing_report['info'] = $message_thing->thing_report['info'] ;

		return $this->thing_report;
	}

	private function nextWord($phrase) {


	}


    public function readInstruction() 
    {
        if($this->agent_input == null) {
            //$this->defaultCommand();
            return;
        }

        $pieces = explode(" ", strtolower($this->agent_input));

        if (isset($pieces[0])) {$this->requested_agent_name = $pieces[0];}
        if (isset($pieces[1])) {$this->horizon = $pieces[1];}


//echo $this->requested_thing_name;
//echo $this->horizon;
//exit();
        //$this->identity = $pieces[2];


//        $this->thing->log( 'Agent "Tally" read the instruction and got ' . $this->agen$

        return;

    }

	public function readSubject() {

		$this->response = null;
        $this->num_hits = 0;

		return "Null";
	}



}

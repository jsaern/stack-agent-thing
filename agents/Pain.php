<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

class Pain {

	public $var = 'hello';

	function __construct(Thing $thing)
    {
		$this->thing = $thing;
		$this->agent_name = 'pain';
		$this->thing_report = array('thing' => $this->thing->thing);

		// So I could call
		if ($this->thing->container['stack']['state'] == 'dev') {$this->test = true;}

		$this->api_key = $this->thing->container['api']['translink'];

		$this->retain_for = 4; // Retain for at least 4 hours.

        	$this->uuid = $thing->uuid;
        	$this->to = $thing->to;
        	$this->from = $thing->from;
        	$this->subject = $thing->subject;

		$this->num_hits = 0;
		$this->pain_score = null;
		$this->sqlresponse = null;

		// Allow for a new state tree to be introduced here.
		$this->node_list = array("start"=>array("useful", "useful?"));

		$this->thing->log( '<pre> Agent "Pain" running on Thing ' . $this->uuid . '</pre>' );
		$this->thing->log( '<pre> Agent "Pain" received this Thing "' . $this->subject . '"</pre>');

		// Read the group agent variable
                $this->thing->json->setField("variables");
                $time_string = $this->thing->json->readVariable( array("pain", "refreshed_at") );

                if ($time_string == false) {
			// Then this Thing has no group information
                        //$this->thing->json->setField("variables");
                        //$time_string = $this->thing->json->time();
                        //$this->thing->json->writeVariable( array("group", "refreshed_at"), $time_string );
                }

                $this->thing->json->setField("variables");
                $this->pain_score = $this->thing->json->readVariable( array("pain", "pain_score") );
		
                if ($this->pain_score == false) {
			// No group_id found on this Thing either.
                        //$this->startGroup();
                } 
		//else {$this->group_id = $group_id;}




		$this->readSubject(); // Extract possible responses.
		$this->thing_report = $this->respond();


		//$this->PNG(); // Red dot/green dot.

		$this->thing->log( '<pre> Agent "'.ucfirst($this->agent_name) . '" completed</pre>' );

		return;

	}

	public function notePain($text = null) {




		$t = "Agent '" . ucfirst($this->agent_name) . "' is watching for patterns.  The provided observation will be kept for " . $this->retain_for;

		$this->pain_score = null;
                $this->message = $t;
		$this->sms_message = ucfirst($this->agent_name) . " observation noted.  Pattern watching.";



                return $this->message;
        }

        public function noteScore($value = null) {


                                                                if ( is_numeric($value) ) {

                                                                        $truth1 = $value >= 1 && $value <= 10; // true if 1 <= x <= 10

                                                                        if ($truth1) {
                                                                                $this->num_hits += 1;

                $t = "Agent '" . ucfirst($this->agent_name) . "' is watching for patterns.  This pain score observation will be kept for " . $this->retain_for;


                $this->pain_score = $value;
                $this->message = ucfirst($this->agent_name) . " score " . $this->pain_score . " noted.  Pattern watching." . $t;
                $this->sms_message = ucfirst($this->agent_name) . " score = " . $this->pain_score . ".  Pattern watching.";
							

                                                                        } else {

                                                                                $this->message = "Pain score received but not understood.  It should be a number from 1 to 10";
                                                                                $this->sms_message = "Not understood.  Pain score should be from 1 to 10";
                                                                        }
                                                                }




                return $this->message;
        }

        public function painReport($text = null) {

		$this->sms_message = "s/devstack here will be a pain report";
		$this->message = "s/devstack here will be useful information on your pain";

                                                        //$this->painReport($input);
		$path = null;

		$this->thing->db->setUser($this->from);
		$thing_report = $this->thing->db->variableSearch($path, 'pain', 10);

		$priorDate = null;
		$t = "<br>";
		$t_sms = "";
		foreach ($thing_report['things'] as $thing) {

			$newDate = date("d/m", strtotime($thing['created_at']));



			if ($newDate == $priorDate) {
				// Same date
				// Just display time
				$date_text = date("H:s", strtotime($thing['created_at']));
			} else {
				$date_text = date("d M H:s", strtotime($thing['created_at']));
			}
			//$date_text = $newDate;

                        //$newDate = date("d/m", strtotime($thing['created_at']));

			$pain_thing = new Thing($thing['uuid']);

                $pain_thing->json->setField("variables");
                $pain_score = $pain_thing->json->readVariable( array("pain", "pain_score") );

		if (strtolower($pain_score) == "pain report") {continue;}

		if ($pain_score == null) {
			$pain_text = $thing['task'];
		} else {
			$pain_text = "p-".$pain_score;
		}
			

			//if (isset($thing['number']) ) {
			//	$pain_text = $thing['number'];
			//} else {
			//	$pain_text = $thing['number'];
			//}

                        $t .= date("d/m H:s", strtotime($thing['created_at']))  . " " . $pain_text . "<br>";

			$t_sms .= $date_text . ' ' . $pain_text . " > ";

			$priorDate = $newDate;


		}

                //$t = "Agent '" . ucfirst($this->agent_name) . "' is watching for patterns.  This pain score observation will be kept for " . $this->retain_for;


                $this->pain_score = $text;
                $this->message = ucfirst($this->agent_name) . " report: <br>" . $t;
                $this->sms_message = ucfirst($this->agent_name) . " reports: " . $t_sms;


                                                        //$this->sms_message = "s/devstack here will be a pain report";
                                                        //$this->message = "s/devstack here will be useful information on your pain";



                return $this->message;
        }





// -----------------------

	private function respond()
    {
		// Thing actions
		$this->thing->flagGreen();

		$this->thing_report['num_hits'] = $this->num_hits;

		// Update thing

        if ( $this->pain_score != null ) {
           // Then this Thing has no group information
           $this->thing->json->setField("variables");
           $time_string = $this->thing->json->time();
           $this->thing->json->writeVariable( array("pain", "refreshed_at"), $time_string );
           $this->thing->json->writeVariable( array("pain", "pain_score"), $this->pain_score );
        }

		// Generate email response.
		$to = $this->thing->from;
		$from = "pain";
		$message = $this->readSubject();

		$this->thing->choice->Create($this->agent_name, $this->node_list, "start");
		$choices = $this->thing->choice->makeLinks('start');

        $thing_report['choices'] = $choices;


		$this->sms_message = "PAIN | " . $this->sms_message . " | TEXT ?";
		$this->thing_report['sms_message'] = $this->sms_message;
		$this->thing_report['sms'] = $this->sms_message;

        $message_thing = new Message($this->thing, $this->thing_report);
        $this->thing_report['info'] = $message_thing->thing_report['info'] ;

		$this->thing_report['message'] = $this->message;

    	if ($this->pain_score != null) {
	            $this->thing_report['number'] = $this->pain_score;
	    }

	    $this->thing_report['info'] = 'This is the pain manager responding to a request.';
	    $this->thing_report['help'] = 'This is the pain manager.  PAIN <1 to 10>.  PAIN <text>.';
	    $this->thing_report['log'] = $this->thing->log;

		return $this->thing_report;



	}

	private function nextWord($phrase) {


	}

	public function readSubject() {

		$this->response = null;

		$keywords = array('pain', 's/pain', 'pn', 'paine','p');


		// Make a haystack.  Using just the subject, because ...
		// ... because ... I don't want to repeating an agents request
		// and creating some form of unanticipated loop.  Can 
		// change this when there is some anti-looping in the path
		// following.

		$input = strtolower($this->subject);

		$prior_uuid = null;


		// Split into 1-grams.
		$pieces = explode(" ", strtolower($input));

		// Keywording first
                if (count($pieces) == 1) {

			foreach ($keywords as $keyword) {
				if($keyword == $input) {
					$this->notePain($input);
					$this->num_hits += 1;
				}
				return $this->notePain($input);

    			}


                        if (is_numeric($this->subject) and strlen($input) == 5 ) {
                                //return $this->response;
                        }

                        if (is_numeric($this->subject) and strlen($input) == 4 ) {
                                //return $this->response;
                        }

			$this->notePain($input);

                        return $this->message;

        	}



		foreach ($pieces as $key=>$piece) {
			foreach ($keywords as $command) {				
				if (strpos(strtolower($piece),$command) !== false) {

					switch($piece) {

                                                case 'score':    
                $this->thing->log( '<pre> Agent "Pain" noted the word score </pre>' );

                                                        if ($key + 1 > count($pieces)) {
                                                                //echo "last word is pain";
                                                                $this->pain_score = null;
                                                                $this->notePain($input);
								$this->sms_message = "meep";
                                                                return $this->message;

                                                        } else {
                                                                $value = $pieces[$key + 1];
                                                                if (is_numeric($value)) {
                                                                        $this->noteScore($value);
                                                                //} else {
                                                                //      $this->notePain($input);
                                                                //}
                                                                return $this->message;
                                                                }
                                                        }

                                                        //return "Pain noted.  Request not understood";
                                                        break;


						case 'report':
							//$this->sms_message = "s/devstack here will be a pain report";
							//$this->message = "s/devstack here will be useful information on your pain";
						
							$this->painReport($input);
							
							return $this->message;
							//echo 'bus';
							//break;


                                                case 'pain':    
                $this->thing->log( '<pre> Agent "Pain" noted the word pain </pre>' );

                                                        if ($key + 1 > count($pieces)) {
                                                                //echo "last word is pain";
                                                                break;
								$this->pain_score = null;
                                                                $this->notePain($input);
                                                                return $this->message;

                                                        } else {
                                                                $value = $pieces[$key + 1];
                                                                if ( is_numeric($value)) {
                                                                        $this->noteScore($value);
                                                                //} else {
                                                                //      $this->notePain($input);
                                                                //}
                                                                return $this->message;
								} elseif ( ( $value == 'score') and is_numeric($pieces[$key + 2]) ) {
									$this->noteScore($pieces[$key + 2]);
									return $this->message;
								} else {
									break;
                                                                }
                                                        }

                                                        //return "Pain noted.  Request not understood";
                                                        break;


                                                case 'start':

							// Not responding to start for some reason 29 June 2017
							$this->sms_message = "s/devstack start";
                                                        $this->message = "s/devstack start";
                                                        return $this->message;
                                                        //echo 'bus';
                                                        //break;


						default:
						$this->notePain($input);
						//$this->sms_message = "meep";
					               return $this->message; // Capture

							//echo 'default';

					}

				}
			}

		}

                return $this->notePain($input); // Capture


		//return "Message not understood";
	}



        public function PNG() {
// Thx https://stackoverflow.com/questions/24019077/how-to-define-the-result-of-qrcodepng-as-a-variable

//I just lost about 4 hours on a really stupid problem. My images on the local server were somehow broken and therefore did not display in the browsers. After much looking around and tes$
//No the problem was not a whitespace, but the UTF BOM encoding character at the begining of one of my inluded files...
//So beware of your included files!
//Make sure they are not encoded in UTF or otherwise in UTF without BOM.
//Hope it save someone's time.

//http://php.net/manual/en/function.imagepng.php

//header('Content-Type: text/html');
//echo "Hello World";
//exit();

//header('Content-Type: image/png');
//QRcode::png('PHP QR Code :)');
//exit();
                // here DB request or some processing

		if ($this->group_id == null) {
			$this->startGroup();
		}

                $codeText = "group:".$this->group_id;

                ob_clean();
                ob_start();

// choose a color for the ellipse
//$ellipseColor = imagecolorallocate($image, 0, 0, 255);

// draw the blue ellipse
//imagefilledellipse($image, 100, 100, 10, 10, $ellipseColor);

                QRcode::png($codeText,false,QR_ECLEVEL_Q,4); 
                $image = ob_get_contents();

                ob_clean();
// Can't get this text editor working yet 10 June 2017

//$textcolor = imagecolorallocate($image, 0, 0, 255);
// Write the string at the top left
//imagestring($image, 5, 0, 0, 'Hello world!', $textcolor);

$this->thing_report['png'] = $image;
//echo $this->thing_report['png']; // for testing.  Want function to be silent.

                return $this->thing_report['png'];
                }


}




?>




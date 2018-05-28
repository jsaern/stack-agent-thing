<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

class Thing {

	public $var = 'hello';

	function __construct($uuid, $test_message = null) {

		// At this point, we are presented a UUID.
		// Whether or not the record exists is another question.

		// But we don't need to "find", because the UUID is randomly created.	
		// Chance of collision super-super-small.

		// https://www.quora.com/Has-there-ever-been-a-UUID-collision

		// So just return the contents of thing.  false if it doesn't exist.
		// create container and configure it

		// That UUID collisions can occur is not a concern of the Thing object.
		// It is a significant Stack concern.

        if (!isset($GLOBALS['stack_path'])) {
            // Try this, otherwise fail.
            $GLOBALS['stack_path'] = "/var/www/stackr.test/";
        }

        $url = $GLOBALS['stack_path'] . 'private/settings.php';
        $settings = require $url;

		$this->container = new \Slim\Container($settings);

		//$this->container = $app->getContainer();
		//$this->test = true;

        // A REMINDER THAT IT IS TRIVIAL TO ADD ACCESS TO THE STACK MYSQL
        // DATABASE SETTINGS FROM A THING.
        //		$this->container['db'] = function ($c) {
        //			$db = $c['settings']['db'];
        //			$pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'],
        //				$db['user'], $db['pass']);
        //			//$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //			$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        //			$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        //			return $pdo;
        //			};
        // BUT THINGS DON'T NEED THAT.  THAT LEADS TO A BROKEN STACK.
        // NRWTAYLOR 9:48 17 April 2017

        // Action: Look for this in submitted code.

		$this->container['stack'] = function ($c) {
			$db = $c['settings']['stack'];
			return $db;
			};

		$this->container['api'] = function ($c) {
			$db = $c['settings']['api'];
			return $db;
			};

		$this->char_max = $this->container['stack']['char_max'];

		$this->mail_postfix = $this->container['stack']['mail_postfix'];
		$this->stack_uuid = $this->container['stack']['uuid'];

		$this->associate_prior = $this->container['stack']['associate_prior'];
		$this->associate_posterior = $this->container['stack']['associate_posterior'];



//        if (!isset($_SERVER['HTTP_HOST'])) {
            $this->web_prefix = $this->container['stack']['web_prefix'];
        
//        } else {
//            $this->web_prefix = "http://$_SERVER[HTTP_HOST]/";
//        }


		$this->log = "";

		if (null === $uuid) {

			// ONLY PLACE IN STACK WHERE UUIDs ARE ASSIGNED
			//
			// ---
			// THIS IS CORE TECHNOLOGY.  THE SELECTION OF THE UUID GENERATOR IS
			// CRITICAL.  INTENTIONALLY LEFT OPEN AS CHAR(34) DB FIELD.

			$this->uuid = $this->getUUid();
			$this->nuuid = substr($this->uuid, 0, 4);
			//$this->uuid = (string) Uuid::uuid4();

			// And then we pull out some Thing related variables and settings.

			$this->container['thing'] = function ($c) {
				$db = $c['settings']['thing'];
				return $db;
				};

			$this->stack_account = $this->container['thing']['stack_account'];
			// The settings file can make Thing set up a specific Thing account.
			$this->thing_account = $this->container['thing']['thing_account'];

			// I'm still working on what the difference between the two really
			// is.  Settings determine the functioning of the Thing.
			// Variables are stuff that can be lost when the Thing
			// deinstantiates.

			// Can't call db here, can only call it when $from is known.
			// $this->db = new Database($this->uuid, $this->from);

			$this->json = new Json($this->uuid);
			$this->choice = new Choice($this->uuid);


			// Sigh.  Hold this Thing to account.  Unless it is a forager.
			$this->state = 'foraging'; // Add code link later.

			// Don't create accounts here because that is done on ->Create()
			// The instatiation function needs to return a minimum clean false
			// Thing.
			$this->thing = false;

			// Calling constructor with a uuid that doesn't exist,
			// returns false, and with a Thing instantiated.  For tasking.

		} else {

			// Reinstate existing Thing from Stack

			// EXISTING THING IS CONNECTED TO THE STACK.

			// A specific-Uuid has been called by Uuid reference.
			// This section re-creates a Thing and sets scalar either to 0,
			// or to Stack value.

			$this->uuid = $uuid;
			$this->nuuid = substr($this->uuid, 0, 4);


			// Is link to the ->db broken when the Thing is deinstantiated.
			// Assume yes.
			$this->db = new Database($this->uuid, 'null' . $this->mail_postfix);

			// Provide handler for Json translation from/to MySQL.
			$this->json = new Json($this->uuid);

			// Provide handler to support state maps and navigation.
			// And state persistence through de-instantiation/instantiation.
			$this->choice = new Choice($this->uuid);

			// Cost of connecting to a Thing is 100 <units>.
			// That is set by the stack variable.  No need to do anything here
			// except load the Things internal balances.
			$this->loadAccounts();

			// Examples:

			//$this->account[$account_name] = new Account($this->uuid, $account_name);
			//$this->account[$account_name]->getBalance(); // Yup.  That easy.

			//$this->account['cost']->getBalance(); // Yup.  That easy.

			//$this->account['run_time'] = new Account($this->uuid, $account_name);
			//$this->account['run_time']->Create(0, 'time', 'seconds');
			//$this->account['run_time']->getBalance(); // Yup.  That easy.

			// Pull the Thing's record from the stack.  Providing
			// $to(stack), $from, $task, $message0-7, ,$variables, $settings
			// $message0-7 not implemented except for development testing.

			$this->Get();

			// And fire up the stack balance calculation to make 
			// sure stack balance snapshot is latest.
			//$this->stackBalance();
		}




//	echo '<pre> thing.php $this->thing: '; print_r($this->thing); echo '</pre>';

//		echo "Stack Balance<br>";
//		$this->stackBalance($this->uuid);

		return $this->thing;

    }


	function getUUid()
    {
	    return (string) Uuid::uuid4();
	}

	function Shuffle()
    {

		$this->uuid = $this->getUUid();
		$this->nuuid = substr($this->uuid, 0, 4);

		$this->db->writeField('uuid', $this->uuid);
		$this->Forget();

	}

	function Create($from = null, $to = "", $subject = "") {
		if ($from == null) {$from = 'null' . $this->mail_postfix ;}
		$message0 = array();
		$message0['50 words'] = null;
		$message0['500 words'] = null;

		// Now create the new thing
		// I think it is a valid point to redo the '@' check. 
		// Though hear we should throw some kind of exception to an agent.
		// Which I suppose would be a message to nonnom.  Saying
		// I found and deleted an @ sign.

		if (strpos($to, "@") !== false) {	
			//echo "@ sign found";
			$to = "";
			//echo "email address removed completely<br>";
			$message0['50 words'] .= $this->uuid . " found and removed an @ sign";
		}



		//echo "Problem is here with associatePosterior";
		// I hear ya.
		// This is the most likely candidate for the 1040 25 Apr
		// This function needs ->db.
		$this->db = new Database($this->uuid, $from);



		// All records are associated with a posterior record.  Ideally
		// one of the two latest records matching the newly created
		// records created_at.

		// Unfortunately this requires another database called to find that
		// record and get the uuid, and write it to the database.

		// If it is called before the Create command, maybe we can save
		// some database calls and writes.
//$ref_time = microtime(true);
		$this->associatePosterior($from, $to); // 3s 2s 3s
        // Currently timing (27 Feb) at 268 ms 340ms 261ms
//echo number_format((microtime(true)-$ref_time)*1000) . "s";

		// If stack is set for associating prior records, then associate the new
		// record to the last create record.
//		if ($this->container['settings']['stack']['associate_prior']===true) {
//			$this->pushJson('associations', $prior_uuid);	
//		}





		// First query after instantiating Database. And after the associate
		// Posterior business.

		// Load newly created values into thing memory
		// Simple insert query to MySql.

        // This seems to create the db entry.
        //Commented out 27 Feb 2018.  And it stopped creating mysql records.
		$query = $this->db->Create($subject, $to); // 3s
$query = true;
//echo number_format(microtime(true)-$ref_time) . "s";


		if($query == true) { // will return true if successfull else it will return false

				$this->sqlresponse =  "New record created successfully.";
				$message0['500 words'] .= $this->sqlresponse;

			} else {

				//$error = $query->errorInfo();
				//$this->sqlresponse = "Error: " . $sql . "<br>" . $query->errorInfo();
				$this->sqlresponse = "Error: " .implode(":",$query->errorInfo());
				$message0['50 words'] .= $this->sqlresponse;

			}




		if($query == true) {// will return true if succefull else it will return false

			$this->sqlresponse =  "New record created successfully.";
			$this->to = $to;
			$this->from = $from;
			$this->subject = $subject;
			$message0['500 words'] .= $this->sqlresponse;

		} else {

			//$error = $query->errorInfo();
			//$this->sqlresponse = "Error: " . $sql . "<br>" . $query->errorInfo();
			$this->sqlresponse = "Error: " .implode(":",$query->errorInfo());
			$message0['50 words'] .= $this->sqlresponse;
			return false;

		}

		// Create new accounts.  Still under development as of 25 April.   
		// Credit and debit records testing pass.  

		// Here we create an array to call named accounts on.
		// Each account has a 'nickname'.  A mechanism that prevents
		// similar named accounts
		// or as current behaviour (anticipated) overrides account 
		// information with newly presented information.

		// Which means the stack can reset a Things balance.  Handy.

		$this->account = array();

		// Kind of ugly.  But I guess this isn't Python.  And null
		// accounts can't be allowed.
		
		if ($this->stack_account != null) {
			$this->newAccount($this->stack_uuid,
								$this->stack_account['account_name'],
								$this->stack_account['balance']);
			}

		if ($this->thing_account != null) {
			$this->newAccount($this->uuid,
								$this->thing_account['account_name'],
								$this->thing_account['balance']);
			}

		// No need to save accounts here, as all we have done
		// is load them into this Thing
		// from the settings files and from variables.

		// But we do need to calculate the stack balance.
		// $this->thing_account['account_name']->balance['amount'] will
		// return a scalar amount.  Thing balances.  Amount
		// this Thing owes or is owed by other Things.

		// Calling ->db->UUids() will provide the corresponding UUids.
		// Which can then be polled (later?) to provide the Stack balances.
		// The sum of the balances of all uuids which have records
		// corresponding to $this->uuid.

        // Commented out 27 Feb 2018.  Seems unnecessary.
///		$thingreport = $this->db->UUids(); // Designed to accept null as $this->uuid.

///		$things = $thingreport['things'];

		//$this->stackBalance();


		return $this->Get();
		}



	public function newAccount($account_uuid, $account_name, $balance = null) {
//echo '<pre> thing.php newAccount $balance: '; print_r($balance); echo '</pre>';

//echo $account_uuid;
//echo $account_name;

		// Condition received input.
//		if ( ($account_uuid == null) or ($account_name == null) or ($balance == null)) {
 

		if ( ($account_uuid == null) or ($account_name == null) ) {

			return true;}  // was false if there are problems



		if ($balance == null) {$balance['amount'] = 0;}

//		if (is_numeric($balance)) {$balance = array("amount"=>$balance, "attribute"=>null, "unit"=>null);}



//		if ($balance['amount'] == null) {$balance['amount'] = 1234567890.07;}
//		if ($balance['attribute'] == null) {$balance['attribute'] = 'undefined';}
//		if ($balance['unit'] == null) {$balance['unit'] = 'undefined';}




		if (!isset($this->account)) {$this->account = array();}

//		echo "newAccount uuid:" . $account_uuid ."<br>";
//		echo "account name:".$account_name ."<br>";
//		echo "balance amount". $balance['amount'] . "<br>";
	
		$this->account[$account_name] = new Account($this->uuid, $account_uuid, $account_name);
		$this->account[$account_name]->Create($balance);



	// For debugging
		$thingreport = $this->db->Get();
	//echo '<pre> thingreport[ thing ]: '; print_r($thingreport['thing']); echo '</pre>';
		


	return false;
	}

	public function loadAccounts() {
		//echo "loadAccounts()";
		$this->json->setField("variables");

		$accounts = $this->json->readVariable(array("account"));

		
		// At this point we have a PHP array of all accounts on
		// this Thing.

		// This means that we can generate the thing and stack balance now.
		// And set-up all Thing accounts.


		if ($accounts == null) {return false;}
			foreach ($accounts as $uuid=>$account) {
				foreach($account as $account_name=>$balance) {
					if (($uuid == 'stack') or ($uuid == 'thing' )) {echo "corrupted account list";return true;}
					$this->newAccount($uuid, $account_name,	$balance);
				}
			}

		return;
		}


	function stackBalance() {
		// Query stack for matching uuid and nom_from

		echo "WORK ON STACK BALANCE";

		$thingreport = $this->db->UUids(); // Designed to accept null as $this->uuid.

		$things = $thingreport['things'];

		if ( ($things == null) or $things == array() ) {return false;}
	
		// Should have an array... which could be presumptuous.
		if (!is_array($things)) {return false;}

		if (!isset($this->from)){return false;}

		// Okay pretty sure we can do this now.
		$thingreport = $this->db->UUids($account_uuid);
	
		//$variables = $thingreport['variables'];

		//echo $variables;
		return;
		}


//	public function receiptPNG() {
//		return;
//		$param = $_GET['id'];
//
//		ob_start("callback");
//		// here DB request or some processing
//		$codeText = "thing:".$this->uuid;
//		// end of processing here
//		$debugLog = ob_get_contents();
//		ob_end_clean();
//		QRcode::png($codeText,false,QR_ECLEVEL_Q,4); 
//
//		return;
//		}

//        public function PNG()  {
//		return;
//                $param = $_GET['id'];
//
//                ob_start("callback");
//                // here DB request or some processing
//                $codeText = "thing:".$this->uuid;
//                // end of processing here
//                $debugLog = ob_get_contents();
//                ob_end_clean();
//                QRcode::png($codeText,false,QR_ECLEVEL_Q,4); 
//
//               return;
//                }



        public function PDF($pdf_name = null) {
		return;
                $param = $_GET['id'];

                ob_start("callback");
                // here DB request or some processing
                $codeText = "thing:".$this->uuid;
                // end of processing here
                $debugLog = ob_get_contents();
                ob_end_clean();
                QRcode::png($codeText,false,QR_ECLEVEL_Q,4); 

                return;
                }



	public function test($variable = null, $agent= null, $action = null) {
		if ($agent == null) {$agent = "null";}
		if ($action == null) {$action = "did something with";}
		// Keep it simple for now.

		echo '<pre> Agent "'.$agent.'" ' . $action . ' this Thing at ';print_r($variable);echo'</pre>';
	}		
		

    public function getVariable($variable_set, $variable) {

//                $thing= new Thing($uuid);
        $variables = $this->account['stack']->json->array_data;


        if (isset($variables[$variable_set])) {

            $this->$variable_set = (object) array($variables[$variable_set])[0];

            return $this->$variable_set->$variable;
        }

//        if (isset($this->$variable_set)) {
//            $this->$variable_set = $variables[$variable_set];

//            if (isset($this->$variable_set->$variable)) {
//                return $this->$variable_set->$variable;
//            }
//        }

        
        return false;
        



    }


	public function Forget() {
		// Call to account destruction.  Both for DB and stack account, 
		// and the Thing.

		// To be developed.  Stack account destruction.
		// $this->account['scalar']->Destroy(100, '<not set>', '<not set>');

		// Current behaviour:
		// Stack account value is destroyed on deinstantiation of the Thing.
		// at a net cost to Stack of 0.

		// Planned behaviour:
		// Stack account value is distributed within defined groups.
		$thingreport = $this->db->Uuids();


		// Call Db and forget the record.

		$thingreport = $this->db->Forget($this->uuid);


		// To be developed.  PHP object destruction.

		}

	public function Ignore() {

		$this->json->setField("variables");
		$this->json->writeVariable(array("thing","status"), "green");
		$this->Get();

		}


	public function flagRed() {

		$this->json->setField("variables");
		$this->json->writeVariable(array("thing","status"), "red");
		$this->Get();

		}

        public function flagAmber() {

                $this->json->setField("variables");
                $this->json->writeVariable(array("thing","status"), "amber");
                $this->Get();

                }


	public function flagGreen() {

		$this->json->setField("variables");
		$this->json->writeVariable(array("thing","status"), "green");
		$this->Get();

		}


	public function isRed() {

		$var_path = array("thing", "status");
		if ($this->json->readVariable($var_path) == "red") {
			return true;
			}
		
		return false;
		}


	public function isGreen() {

		$var_path = array("thing", "status");
		if ($this->json->readVariable($var_path) == "green") {
			return true;
			}
		
		return false;
		}

	// Yeah - it's amber.  Cycles red > red + amber > green > amber > red
	public function isAmber() {
		$var_path = array("thing", "status");
		if ($this->json->readVariable($var_path) == "amber") {return true;}
		return false;
		}


    function isData($variable) {
        if ( 
            ($variable !== false) and
            ($variable !== true) and
            ($variable !== null) and
            ($variable !== "")
            ) {
 
            return true;

        } else {
            return false;
        }
    }



	public function flagGet() {

                $var_path = array("thing", "status");
                return $this->json->readVariable($var_path);

                }

        public function flagSet($color = null) {

		if ($color == null) {$color = 'red';}

                $this->json->setField("variables");
                $this->json->writeVariable(array("thing","status"), $color);
                $this->Get();

                }




	public function Get() {

		// Bootstrapping db access.
		// A Thing can call an UUID so called up
		// the requested UUID.  Using the null account.
		$thingreport = $this->db->Get($this->uuid);
		$thing = $thingreport['thing'];



		if ($thing == false) {
			//$this->uuid = $this->thing->uuid;
		        $this->to = null;
		        $this->from = null;
	        	$this->subject = null;
		} else {


			// This just makes sure these four variables
			// are consistently available 
			// as top level Thing objects.
			//$this->uuid = $this->thing->uuid;
        		$this->to = $thing->nom_to;
        		$this->from = $thing->nom_from;
  
// One of these looks promising.  

//$thingreport = $this->db->setUser($this->from);
//$thingreport = $this->db->from = $this->from;

	      		$this->subject = $thing->task;


			// Factor this out as an agent 'RFC822'
			//$this->email = new Email($this->uuid, $this->from, $this->to, $this->subject);
            // NRWTaylor 25 Sep 2017
            // NRWTaylor 4 Oct 2017. Flag Green.
		}

		$this->thing = $thing;

// Once the Thing has been pulled we can update the db connector
// to use the current from.

//		$this->db->setFrom($this->from);

//$this->db = new Database($this->uuid, $this->from);

//$thingreport = $this->db->setUser($this->from);

// Uncommenting any of the above options results in non-working uuids


// Commented out to allow PNG to work




// Keep for debugging.  Working as of 5 April 2017.  And passing all tests
// 25 Apr, except Test 4: Test posterior association.
// echo '<pre>  db.php Get()'; print_r($this->thing); echo '</pre>';

		return $thing;
		}

	public function readSubject() {
		return false;
		}


	function currentState() {

		throw new Exception('devstack deprecate');

// Needed for opt-in state.  Should be replaceable by a custom state
// agent for the Thing.

		$this->db->setUser($this->from);
		$this->current_state = $this->db->userState();
		return $this->current_state;

		}

	function getState($agent = null) {

// This can probably be deprecated after updating usermanager
//echo "agent provided";$agent;

		if ($agent == null) {$agent = 'thing';}
		// Need to find latest record with a usermanager state in it for $from.



		// LET'S START HERE
		// Have we dealt with this nom_from before?
		// Get the latest 3 usermanager interactions.

		$thingreport = $this->db->agentSearch($agent,3); // Get newest

//		echo '<pre> Thing processed subject "';print_r($thingreport);echo'"</pre>';

		$things = $thingreport['things'];

		$states= array();
		foreach($things as $thing) {

			$uuid = $thing['uuid'];

			$thing = new Thing($uuid);
			// append to states

//			$thing->json->setField('variables');
//			$t = $thing->json->readVariable(array($uuid, 'decision'));

			$t = $thing->choice->load($agent);


			if (is_array($t)) {
				// unexpected
				$t = true;
			}

//			echo $uuid . " " .$t . "<br>";
				$states[] =	$t;

		}


	
//			echo '<pre> Thing processed subject "';print_r($states);echo'"</pre>';

		if ($states == array() ) {return $this->current_state = null;}

		if (array_key_exists(1,$states)) { // Then this isn't the only one...
			return $this->current_state = $states[1];
		} else {
			return $this->current_state = $states[0];
		}
	
	return false;

	}



	function setState() {
		throw new Exception('devstack deprecate');
		$this->test("Not implemented");
		return;

	}

	function associatePosterior ($nom_from, $nom_to) {


		// Get the UUID of the last entry in the db with
		// the same planned $to email address.

//echo '<pre> thing.php associatePosterior() $this->uuid call priorGet: '; print_r($this->uuid); //echo '</pre>';

		// This is likely to be a pretty intensive call.
		// It search the db for the most recent last record.

		// Factored out one call (a new Thing instantiation)
		// to the database.  26 Apr.  Got to be worth something.
		// Apparently enough to get rid of Too many connections in test_account.php
		// Passing test_redpanda.php 26 Apr.
//$ref_time = microtime(true);
		$thingreport = $this->db->priorGet(); // 3s

//echo number_format(microtime(true)-$ref_time) . "s";

		$posterior_thing = $thingreport['thing'];

		if ($posterior_thing != false) {

			// Check stack settings and associate previous record with new
			// record if true.  Previous record updated to point to new record.

			if ($this->associate_posterior===true) {

				$posterior_thing->json = new Json($posterior_thing->uuid);
				$posterior_thing->json->setField("associations");
				$posterior_thing->json->pushStream($this->uuid);
				//Tested with unset and commented out 
				//doesn't seem to improve (at least) the 
				//too many connection issue.  Leave it in for
				//the time being.  25 Apr.
				//unset($posterior_thing);
			}

		return 'Posterior uuid ' . $posterior_thing->uuid .
				' associated with Thing uuid ' . $this->uuid;
		}
	}

	function associate ($uuids = null) {
		if ($uuids == null) {return false;}

		if ( is_string($uuids) ) {$uuids = array($uuids);}

		if ( is_array($uuids) ) {
			foreach($uuids as $uuid) {

			    	$this->json->setField("associations");
                                $this->json->pushStream($uuid);

			}
			return false;
		}
		return true;
	}

	function log($text = "|", $logging_level = null) {//echo $notusd;
        // DEBUG, INFORMATION, WARNING, ERROR, FATAL
        // Plus OPTIMIZE

        if ($logging_level == null) {$logging_level = "WARNING";}

        $t = strip_tags($text);


        //if (strtoupper($logging_level) == "INFORMATION") {


		  //  $this->log .= $t . "<br>";

        //}

        switch ($logging_level) {
            case "OPTIMIZE":
//                $this->log .= $t . "<br>";
//                break;
            case "FATAL":
            case "ERROR":
            case "WARNING":
            case "INFORMATION":
                $this->log .= $t . "<br>";
                break;
            case "DEBUG":
            default:
                //echo "i is not equal to 0, 1 or 2";
        }


	}




    function elapsed_runtime()
    {
        if (!isset($this->start_time)) {
            $this->start_time = microtime(true);
        }

        $run_time = microtime(true) - $this->start_time;
        return round($run_time * 1000);
    }



    function human_time ($ptime)
    {
        //$etime = time() - $ptime;
        $etime = $ptime;

        if ($etime < 1)
        {
            return '0 seconds';
        }

        $a = array( 365 * 24 * 60 * 60  =>  'year',
                 30 * 24 * 60 * 60  =>  'month',
                      24 * 60 * 60  =>  'day',
                           60 * 60  =>  'hour',
                                60  =>  'minute',
                                 1  =>  'second'
                );
        $a_plural = array( 'year'   => 'years',
                       'month'  => 'months',
                       'day'    => 'days',
                       'hour'   => 'hours',
                       'minute' => 'minutes',
                       'second' => 'seconds'
                );

        foreach ($a as $secs => $str)
        {
            $d = $etime / $secs;
            if ($d >= 1)
            {
                $r = round($d);
                return $r . ' ' . ($r > 1 ? $a_plural[$str] : $str) . '';
            }
        }
    }
}

?>
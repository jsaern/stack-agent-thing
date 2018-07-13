<?php
namespace Nrwtaylor\StackAgentThing;


ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);


class Start {

	function __construct(Thing $thing) {

        // $timestamp =  new Timestamp($thing, "timestamp");


		$this->thing = $thing;
		$this->agent_name = 'start';
        $this->agent_prefix = 'Agent "' . ucwords($this->agent_name) . '" ';

        $this->thing_report['thing'] = $this->thing->thing;

		// So I could call
		if ($this->thing->container['stack']['state'] == 'dev') {$this->test = true;}
		// I think.
		// Instead.

        $this->uuid = $thing->uuid;
        $this->to = $thing->to;
        $this->from = $thing->from;
        $this->subject = $thing->subject;

        // Get some stuff from the stack which will be helpful.
        $this->web_prefix = $thing->container['stack']['web_prefix'];
        $this->mail_postfix = $thing->container['stack']['mail_postfix'];
        $this->word = $thing->container['stack']['word'];
        $this->email = $thing->container['stack']['email'];


		$this->node_list = array("start"=>array("new user", "opt-in","opt-out"));

        $this->thing->log( $this->agent_prefix . 'running on Thing '. $this->thing->nuuid . '.</pre>', "INFORMATION");

        $this->variables_agent = new Variables($this->thing, "variables start " . $this->from);

        $this->current_time = $this->thing->json->time();

        $this->verbosity = 1;

        $this->thing->log( $this->agent_prefix .'get().', "OPTIMIZE" );


        $this->get();

        $this->thing->log( $this->agent_prefix .'readSubject().', "OPTIMIZE" );

		$this->readSubject();

        $this->set();

        $this->thing->log( $this->agent_prefix .'respond().', "OPTIMIZE" );

 		$this->respond();

		$this->thing->flagGreen();

        $this->thing->log( $this->agent_prefix .'ran for ' . number_format($this->thing->elapsed_runtime()) . 'ms.', "OPTIMIZE" );

        $this->thing_report['etime'] = number_format($this->thing->elapsed_runtime());
        $this->thing_report['log'] = $this->thing->log;

    	return;
	}



    function set()
    {
        $this->variables_agent->setVariable("counter", $this->counter);
        $this->variables_agent->setVariable("refreshed_at", $this->current_time);

//        $this->thing->choice->save('usermanager', $this->state);

        return;
    }


    function get()
    {
        $this->counter = $this->variables_agent->getVariable("counter");
        $this->refreshed_at = $this->variables_agent->getVariable("refreshed_at");

        $this->thing->log( $this->agent_prefix .  'loaded ' . $this->counter . ".", "DEBUG");

        $this->counter = $this->counter + 1;

        return;
    }

    private function makeSMS() {

        switch ($this->counter) {
            case 1:
                $sms = "START | Thank you for starting to use Stackr.  Read our Privacy Policy " . $this->web_prefix . "policy";
                break;
            case 2:
                $sms = "START | Stackr started. Read our Privacy Policy at " . $this->web_prefix . "privacy";
                break;

            case null;

            default:
                $sms = "START | Started. " . $this->web_prefix ."";

        }

        if ($this->verbosity > 5) {
            $sms .= " | counter " . $this->counter;
        }
        $this->sms_message = $sms;
        $this->thing_report['sms'] = $sms;

    }


    private function makeEmail()
    {
        switch ($this->counter) {
            case 1:

                $subject = "Start Stackr?";

                $message = "So an action you took (or someone else took) opted you into 
                    Stackr.
                    <br>
                    There is always that little element of uncertainity.  So we clearly think
                    this is a good thing and are excited to start
                    making associations from your emails that (which?) we know will be
                    helpful or useful to you.
                    <br>
                    So thanks for that and be sure to keep an eye on your stack balance. Which
                    will be maintained at least until you opt-out.  
                    <br>
                    Keep on stacking.

                    ";
                break;
            case 2:
                $subject = "Opt-in request accepted";

                $message = "Thank you for your opt-in request.  'optin' has 
                    added ".$this->from." to the accepted list of Stackr emails.
                    You can now use Stackr.  Keep on stacking.\n\n";

                break;

            case null;

            default:
                $message = "START | Acknowledged. " . $this->web_prefix;

        }

            $this->message = $message;
            $this->thing_report['email'] = $message;

    }


    public function makeChoices()
    {
        // Make buttons
        $this->thing->log( $this->agent_prefix .'Create(...).', "OPTIMIZE" );

        $this->thing->choice->Create($this->agent_name, $this->node_list, "start");
        $this->thing->log( $this->agent_prefix .'makeLinks(...).', "OPTIMIZE" );

        $choices = $this->thing->choice->makeLinks('start');
        // $choices = false;
        $this->thing_report['choices'] = $choices;
        return;
    }



	public function respond() {

		// Thing actions

		// New user is triggered when there is no nom_from in the db.
		// If this is the case, then Stackr should send out a response
		// which explains what stackr is and asks either
		// for a reply to the email, or to send an email to opt-in@<email postfix>.

		$this->thing->flagGreen();

		// Get the current user-state.

        $this->thing->log( $this->agent_prefix .'makeSMS().', "OPTIMIZE" );

        $this->makeSMS();

        $this->thing->log( $this->agent_prefix .'makeEmail().', "OPTIMIZE" );

        $this->makeEmail();

        $this->thing->log( $this->agent_prefix .'makeChoices().', "OPTIMIZE" );
        $this->makeChoices();

        $this->thing_report['message'] = $this->sms_message;
        $this->thing_report['email'] = $this->sms_message;
        $this->thing_report['sms'] = $this->sms_message;

        // While we work on this
        $this->thing->log( $this->agent_prefix .'call Message.', "OPTIMIZE" );
        $message_thing = new Message($this->thing, $this->thing_report);

        $this->thing_report['info'] = $message_thing->thing_report['info'];

        $this->thing_report['help'] = $this->agent_prefix  .'responding to an instruction to start.';


		return;
	}



	public function readSubject()
    {
        $this->start();
		return;
	}


	function start()
    {
        // Call the Usermanager agent and update the state
        $agent = new Usermanager($this->thing, "usermanager start");
        $this->thing->log( $this->agent_prefix .'called the Usermanager and said "usermanager start".' );
        $timestamp =  new Timestamp($this->thing, "timestamp");

		return;
	}

}

?>

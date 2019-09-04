<?php
/**
 * Tick.php
 *
 * @package default
 */


namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

class Tick extends Agent {


    /**
     *
     * @param Thing   $thing
     * @param unknown $agent_input (optional)
     * @param unknown $timer_name
     * @param unknown $start_time
     * @param unknown $max_time    (optional)
     */


    /**
     * function __construct(Thing $thing, $agent_input = null) {
     */
    function init() {

        $this->value_destroyed = 0;
        $this->things_destroyed = 0;

        $this->stack_idle_mode = 'use'; // Prevents stack generated execution when idle.
        $this->cron_period = $this->thing->container['stack']['cron_period'];
        $this->start_time = $this->thing->elapsed_runtime();


        $this->variables = new Variables($this->thing, "variables tick " . $this->from);
        $this->current_time = $this->thing->json->time();

        $this->thing->json->setField("variables");

    }


    /**
     *
     */
    function run() {

        $this->tick_count = $this->tick_count + 1;

        if ($this->tick_count > 4) {
            $this->tick_count = 1;
            $this->doBar();
        }

    }


    /**
     *
     */
    function doBar() {

//$thing = new Thing(null);
//$thing->Create("null@stackr.ca", "bar", "s/ advance bar");
//$bar_agent = new Bar($thing, "bar");
//return;

        $client= new \GearmanClient();
        $client->addServer();
        $arr = json_encode(array("to"=>"null@stackr.ca", "from"=>"bar", "subject"=>"s/ advance bar"));

        $client->doLowBackground("call_agent", $arr);
    }


    /**
     *
     */
    function set() {
        $this->thing->json->setField("variables");

        $this->thing->json->writeVariable(array("tick",
                "refreshed_at"),  $this->thing->json->time()
        );

        $this->thing->json->writeVariable(array("tick",
                "count"),  $this->tick_count
        );

        $this->variables->setVariable("count", $this->tick_count);
        $this->variables->setVariable("refreshed_at", $this->current_time);
    }


    /**
     *
     */
    function get() {
        $this->tick_count = $this->variables->getVariable("count");
        $this->refreshed_at = $this->variables->getVariable("refreshed_at");
        $this->thing->log( $this->agent_prefix .  'loaded ' . $this->tick_count . ".", "DEBUG");
        //        $this->tick_count = $this->tick_count + 1;
    }

    /**
     *
     */
    function respond() {
        $this->makeSMS();
        $this->makeMessage();
    }


    /**
     *
     */
    public function makeMessage() {
        $message = "On this tick, " . number_format($this->value_destroyed) . " units of value, and " . $this->things_destroyed . " Things were destroyed. ";
        $message .= "Counted " . $this->tick_count . " ticks.";

        $this->message = $message;
        $this->thing_report['message'] = $message;
    }


    /**
     *
     */
    function makeSMS() {
        $this->sms_message = "TICK | value destroyed " . number_format($this->value_destroyed) . " things destroyed " . $this->things_destroyed . ".";
        $this->sms_message .= " | " . $this->tick_count;
        $this->thing_report['sms'] = $this->sms_message;
    }


    /**
     *
     */
    function readSubject() {
    }


    /**
     *
     * @param unknown $depth (optional)
     */
    function doTick($depth = null) {



        $this->run_time = $this->cron_period * 0.90; // Leave some slack
        $this->step_time = $this->cron_period / 7;

        $this->timer = new Timer_tick("main", time() , $this->run_time);
        // Clock is ticking

        $this->thing->log( "cronhandler Thing uuid is " . $this->thing->uuid . "");


        // Get the last cron run
        //echo $cronhandler_thing->account['thing']->balance['amount'];


        // Generate a time for the provided $step_time.
        // devstack This can developed to stochastically assign a range of times.

        $this->step_timer = new Timer_tick("sub", time(), $this->step_time);

        $this->thing->log("Tick runtime alloted is " . $this->run_time . "s.");

        // Generate 7 time windows worth of work for the stack to do.
        // Need to make sure we call agenthandler often enough to be responsive.
        // And damagehandler enough to ensure sufficient emphemerality.


        // Roll 7 dice and record the results.

        for ($i = 0; $i < $num_die; $i++) {
            // Generate work schedule of 6 activities
            $arr[] = rand(1, 6);
        }


        $this->periods = array("1", "2", "3", "4", "5", "6", "7");

        $this->node_list = array('start'=>
            array('agent'=>
                array('damage'=>
                    array('retention'=>
                        array('agent', 'idle'=>
                            array('agent')
                        )))));



        $this->thing->choice->Create('cronhandler', $this->node_list, "start");

        $this->state = $this->thing->choice->load('cronhandler');

        echo "initial state is " . $this->state . "<br>";


        $this->budget = 0;
        $this->value_created = 0;
        $this->value_destroyed = 0;

        $this->timeWindow();

        $this->exitCronhandler(100); //with a 100 bonus for completing
    }


    /**
     *
     * @param unknown $depth (optional)
     */
    function timeWindow($depth = null) {

        if ($depth == null) {$depth = 0;}
        $depth += 1;

        if ($depth >=2) {echo "bottomed out";return;}

        foreach ($this->periods as $period) {
            if ($this->timer->timeUp()) {$this->exitCronhandler();}
            $this->step_timer->reset($this->step_time);

            $this->nextState();
            echo $this->state;

            switch ($this->state) {

            case 'dispatch':
                echo "dispatchandler called " ."<br>";
                //    $this->dispatchhandler();
                break;


            case 'agent':
                echo "agenthandler called " .$this->budget ."<br>";
                //           $this->agenthandler();
                break;

            case 'damage':
                echo "damagehandler called";
                //    $this->damagehandler();
                break;

            case 'retention':
                echo "retentionhandler called";
                $this->retentionhandler();
                break;


            case 'idle':
                echo "idle called";
                // Choose one f 5 useful things to do.
                if ($this->stack_idle_mode == 'idle') {echo "Idling";break;} // Test

                //Devstack - recursive
                //     $this->timeWindow( $depth );

                break;
            }





            echo "<br>  processing remainder of cycle";

            $flag = false;
            while ( ($this->step_timer->timeUp() == false) ) {
                if ($this->timer->timeUp()) {$this->exitCronhandler();}

                // Process remaining items

                if ($flag == false) {

                    //                   $this->agenthandler();

                    $flag = true;
                }
            }
        }

        return;
    }


    /**
     *
     */
    function dispatchhandler() {
        $t = new Dispatchhandler();
        $value = $t->Apply();
        $this->value_created += $value;
        $this->budget += $value;
    }


    /**
     *
     */
    function agenthandler() {
        //  $t = new Agenthandler();
        //                $value = $t->Apply();
        //                $this->value_created += $value;
        //                $this->budget += $value;
    }


    /**
     *
     */
    function damagehandler() {
        $t = new Damagehandler();
        if ($this->budget <= 0) {$hits = 100;} else {$hits = $this->budget;}
        $value = $t->Apply( $hits );
        $this->value_destroyed += $value;
        $this->budget -= $value;
    }


    /**
     *
     */
    function retentionhandler() {
        //echo "damagehandler called";
        $t = new Retentionhandler();
        $t->Apply();
        //if ($this->budget <= 0) {$hits = 100;} else {$hits = $this->budget;}
        //$value = $t->Apply( $hits );
        //                $this->value_destroyed += $value;
        //$this->budget -= $value;
    }


    /**
     *
     * @param unknown $default_choice (optional)
     * @return unknown
     */
    function nextState($default_choice = null) {
        if ($default_choice == null) {$default_choice = "idle";}


        $choices =  $this->thing->choice->getChoices($this->state) ;
        //var_dump($choices);
        array_shift($choices);

        if ($choices == null) {
            $choice = $default_choice; // to be explicit
        } else {
            //                                var_dump($choices);
            $choice = $choices[ rand(0, count($choices) - 1) ];

            //echo ">choice" . $choice;
        }
        $this->state = $choice;

        return $choice;
    }


    /**
     *
     * @param unknown $bonus (optional)
     */
    function exitCronhandler($bonus = null) {

        if ($bonus == null) {$bonus = 0;}
        echo "exitCronhandler()";

        // So there will be some damage budget left over.
        // Created value from newly created user interaction with Things.
        // Destroyed value by randomly deleted Things up to the value created.
        // Net creation rate will be marginal but positive.

        // No need to credit the remaining budget
        // stack value was created and destroyed in the process
        // stackbalance is where this accounting is reckoned.

        //var_dump( $this->thing->account['stack']->balance );

        // Credit the Thing

        //$this->thing->account['stack']->Credit( $this->value_destroyed );
        $this->thing->account['thing']->Credit( $bonus + $this->value_destroyed );

        //var_dump( $this->thing->account['thing']->balance );


        // $this->thing->account['thing']->Credit($remaining_budget);

        // Do we pay the Thing though?  Already paid it 100.  And it owes us 100.

        //exit();
    }


}







class Timer_tick {


    /**
     *
     * @param unknown $timer_name
     * @param unknown $start_time
     * @param unknown $max_time   (optional)
     */
    function __construct($timer_name, $start_time, $max_time = 60/7) {
        $this->timer_name = $timer_name;
        $this->max_time = $max_time;
        //echo $max_time;
        $this->start_time = $start_time;
    }


    /**
     *
     * @return unknown
     */
    function timeUp() {

        if (time() - $this->start_time > $this->max_time) {
            echo "Timer expired (Timer '" . $this->timer_name . "') :"  . (time() - $this->start_time) . ' seconds <br>';

            return true;
        }
        return false;
    }


    /**
     *
     * @param unknown $max_time (optional)
     */
    function reset($max_time = 1) {
        $this->max_time = $max_time;
        $this->start_time = time();
    }


    /**
     *
     */
    function elapsed() {
        echo "Time elapsed (Timer '" . $this->timer_name . "') :"  .(time() - $this->start_time) . ' seconds <br>';
    }


}
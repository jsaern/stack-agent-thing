<?php
/**
 * Thankyou.php
 *
 * @package default
 */


namespace Nrwtaylor\StackAgentThing;

class Thankyou extends Agent {

    public $var = 'hello';


    /**
     *
     * @param Thing   $thing
     * @param unknown $text  (optional)
     */
    function init() {
        $this->agent_name = "thankyou";
        $this->test= "Development code";
        $this->thing_report["info"] = "This responds to a thank you.";
        $this->thing_report["help"] = "Why thanks. Glad it was helpful and/or useful.";
    }


    /**
     *
     * @return unknown
     */
/*
    public function respond() {
        $this->thing->flagGreen();


        $this->makeSMS();
        $this->makeChoices();

        //$this->thing_report["info"] = "This is a ntp in a park.";
        //$this->thing_report["help"] = "This is finding picnics. And getting your friends to join you. Text RANGER.";

        $this->thing_report['message'] = $this->sms_message;
        $this->thing_report['txt'] = $this->sms_message;

        $message_thing = new Message($this->thing, $this->thing_report);
        $thing_report['info'] = $message_thing->thing_report['info'] ;

        return $this->thing_report;
    }
*/

    /**
     *
     */
    function makeSMS() {
        $this->node_list = array("thankyou"=>array("thankyou"));
        $m = strtoupper($this->agent_name) . " | " . $this->response;
        $this->sms_message = $m;
        $this->thing_report['sms'] = $m;
    }

    // function query_time_server

    /**
     *
     */
    function makeChoices() {
        $choices = false;
        $this->thing_report['choices'] = $choices;
    }

    function doThankyou($text = null) {

// https://www.google.com/search?responses+to+the+word+thank+you
            $array = array("Why shucks. ",
'Glad to help. ',
'Anytime. ',
'No worries. ',
'Thank you! ',
'Sure! ',"It's no problem at all. ", "You're welcome. ");
            $k = array_rand($array);
            $v = $array[$k];


$this->thankyou_response = $v;

        // If we didn't receive the command NTP ...
if (strtolower($this->input) != "thankyou") {
    $this->thankyou_message = $this->thankyou_response;
$this->response .= $this->thankyou_response;
return;
}

    $this->thankyou_message = $this->response;
$this->response .= $this->thankyou_response;

    }



    /**
     *
     * @return unknown
     */
    public function readSubject() {

        $this->doThankyou($this->input);

        return false;
    }


}

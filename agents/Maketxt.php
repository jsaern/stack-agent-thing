<?php
namespace Nrwtaylor\StackAgentThing;


error_reporting(E_ALL);ini_set('display_errors', 1);

// First off this is called 'make pdf' intentionally to mimic the command structure.
// Touchy much?


// And now the makePng class, exactly like the makePdf
// Let's call give it an N-gram to facilitate command 'make pdf'.
// Also means post-poning tackling what Pdf is actually defined as.
// Which might be exactly how it should be.

// Allowing these core channel processing functions to be handled
// in Composer packages

class makeTxt
{

    public $var = 'hello';

    function __construct(Thing $thing, $input = null)
    {

    	$this->input = $input;
        $this->thing_report = array('thing' => $thing);

    	// routes passes file_name to make txt as $input
	    $image_name = $input;
	    $this->agent_thing = new Agent($thing, $input);

        if (isset($this->agent_thing->thing_report['txt'])) {
        	$this->thing_report['txt'] = $this->agent_thing->thing_report['txt'];
        } else {
            $this->thing_report['txt'] = "MAKE TXT | No text file response found.";
        }
	}

}


?>

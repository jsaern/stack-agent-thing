<?php
namespace Nrwtaylor\StackAgentThing;

/*
tests

agent claws --channel=txt "/var/www/stackr.test/resources/call/call-test-CapiTalized.txt"
agent claws --channel=txt "/var/www/stackr.test/resources/call/call-test.txt"



*/

class Claws extends Agent
{
    public $var = 'hello';

    function init()
    {
    }

    function run()
    {
        $this->doClaws();
    }

    public function doClaws()
    {
        if ($this->agent_input == null) {
            $array = ['miao', 'miaou', 'hiss', 'prrr', 'grrr'];
            $k = array_rand($array);
            $v = $array[$k];

            $response = "CLAWS | " . strtolower($v) . ".";

            $this->claws_message = $response; // mewsage?
        } else {
            $this->claws_message = $this->agent_input;
        }
    }

    function getNegativetime()
    {
        $agent = new Negativetime($this->thing, "claws");
        $this->negative_time = $agent->negative_time; //negative time is asking
    }

    // -----------------------

    public function respondResponse()
    {
        $this->thing->flagGreen();

        $this->thing_report["info"] =
            "This is a claws keeping an eye on how late this Thing is.";
        $this->thing_report["help"] = "This is about being inscrutable.";

        $this->thing_report['message'] = $this->sms_message;

        $message_thing = new Message($this->thing, $this->thing_report);
        $thing_report['info'] = $message_thing->thing_report['info'];
    }

    function makeChoices()
    {
        //        $this->thing->choice->Create('channel', $this->node_list, "claws");
        //        $choices = $this->thing->choice->makeLinks('claws');
        //        $this->thing_report['choices'] = $choices;
    }

    /*
        Load file provided by Claws.
        Assume it is MH.
        dev - test filetype and respond appropriately.
    */
    public function loadClaws($text = null)
    {
        if ($text == null) {
            return true;
        }
        $filename = trim($text, '"');

        if (!file_exists($filename)) {
            return true;
        }

        if (is_string($filename)) {
            $mh_contents = file_get_contents($filename);
            $contents = $this->textMH($mh_contents);

            return $contents;
        }
        return true;
    }

    public function readClaws($text = null)
    {
        var_dump("Claws readClaws");
        var_dump($text);
    }

    public function whenClaws()
    {
        if ($this->claws_when_flag != "on") {
            return;
        }

        // Code to write When calendar line item goes here.

        // Build entry for when calendar
        $line = "test item";

        $this->writeWhen($line);
        $this->response .= "Wrote item to When calendar file. ";
    }

    public function makeSMS()
    {
        $count = count($this->claws_items);

        $sms = "CLAWS | " . "Read " . $count . " items. See TXT response.";

        $this->thing_report['sms'] = $sms;
        $this->sms_message = $sms;
    }

    public function makeTXT()
    {
        $txt = "CLAWS\n";
        foreach ($this->claws_items as $i => $claws_item) {
            $text_claws =
                $claws_item['call']['password'] .
                " " .
                $claws_item['call']['access_code'] .
                " " .
                $claws_item['call']['url'] .
                "\n";
            $text_claws .= $claws_item['subject'] . "\n";
            $text_claws .= $claws_item['dateline']['line'] . "\n";
            $text_claws .=
                $this->timestampDateline($claws_item['dateline']) . "\n";

            $txt .= $text_claws . "\n";
        }
        $txt .= "\n";

        $this->thing_report['txt'] = $txt;
        $this->txt = $txt;
    }

    public function scoreAt($dateline)
    {
        // Multiple dimensions.
        // Here we care about do we have enough to know when a meeting is.

        $context = 'meeting';

        $score = 10 - $this->falsesCount($dateline);

        return $score;
    }

    function datelinesCall($text = null)
    {
        if ($text == null) {
            return;
        }
        $paragraph_agent = new Paragraph($this->thing, $text);
        $paragraphs = $paragraph_agent->paragraphs;

        $datelines = [];
        // Read every line for a date.
        foreach ($paragraphs as $i => $paragraph) {
            $containsDigit = preg_match('/\d/', $paragraph);
            if ($containsDigit == false) {
                continue;
            } // No digit. So no date. Reasonable?

            $dateline = $this->extractDateline($paragraph);
            if ($dateline == false) {
                continue;
            }

            $dateline['score'] = $this->scoreAt($dateline, "meeting");
            $datelines[] = $dateline;
        }

        // Sort by best to worst match.
        // Subject to how defined the date is.
        // Expect it to be missing stuff. Like year.
        // And to perform poorly if all we get is "Details for the call Thursday night".

        usort($datelines, function ($a, $b) {
            return $a['score'] < $b['score'];
        });

        return $datelines;
    }

    public function readSubject()
    {
        $input = $this->input;

        // Note for dev.
        // Try this as $this->assert($input, false).

        //        $filtered_input = $this->assert($input);
        //        $this->filenameClaws($filtered_input);

        // Recognize if the instruction has "when" in it.
        // Set a flag so that we can later create a calendar item if needed.
        $indicators = [
            'when' => ['when'],
        ];
        $this->flagAgent($indicators, strtolower($input));

//        $filtered_input = $this->assert($input, false);
        //var_dump($filtered_input);
        //exit();

        $string = $input;
        $str_pattern = 'claws';
        $str_replacement = '';
        $filtered_input = $input;
        if (strpos($string, $str_pattern) !== false) {
            $occurrence = strpos($string, $str_pattern);
            $filtered_input = substr_replace(
                $string,
                $str_replacement,
                strpos($string, $str_pattern),
                strlen($str_pattern)
            );
        }
        $filtered_input = trim($filtered_input);

        // See note above to re-factor above.

        $this->claws_items = [];
        $tokens = explode(" ", $filtered_input);
        foreach ($tokens as $i => $token) {
            $filename = trim($token);

            // Delegating contents to agents for processing
            $contents = $this->loadClaws($filename);

            // Pass contents through MH routine to remove trailing =

            //$meta = $mh_agent->metaMH($contents);
            $subject = $this->subjectMH($contents);
            //var_dump("Claws metaMH response");
            //var_dump($subject);

            // Pass contents to call to extract conference details.
            // Tested on Webex.
            // Needs further service development.
            // Prioritize Zoom dev test.
            $call = $this->readCall($contents);
            //var_dump("Claws readCall response");
            //var_dump($call);

            $dateline = $this->extractAt($subject);
            var_dump("Claws readSubject");
            //var_dump("TODO - Read at in subject. See Claws");
            //var_dump($at);

            $subject_at_score = 0;
            if ($at != null) {
                $subject_at_score = $this->scoreAt($at, "meeting");
            }

            // TODO - Check if the subject has a well qualified date time.
            // dev start with a simple score of missing information.
            // dev assess whether date time is "adequate"
            if ($subject_at_score <= 4) {
                // Otherwise ... see if there is a better date time in the combined contents.
                $datelines = $this->datelinesCall($subject . "\n" . $contents);
                // Pick best dateline.

                $dateline = $datelines[0];
            }

            $this->claws_items[] = [
                'subject' => $subject,
                'call' => $call,
                'dateline' => $dateline,
            ];
        }

        // get an MH reader to clean up the format
        // See what we get from Call.
        //$call_agent = new Call($this->thing, "call");

        // desired actions - priority and focuses
        // 1. insert with conference link into when calendar
        // 2. take conference link to forward it in an email (?)
        // 3. clickable action to connect to conference link (?)
        // 4. include subject of original email

        return false;
    }
}

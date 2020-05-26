<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

//require '/var/www/html/stackr.ca/public/agenthandler.php'; // until the callAgent call can be
// factored to
// call agent 'Agent'

ini_set("allow_url_fopen", 1);

class Read extends Agent
{
    public $var = 'hello';

    function init()
    {
        $this->test = "Development code"; // Always
        $this->keywords = ['read', 'link', 'date', 'wordlist'];

        $this->variables_agent = new Variables(
            $this->thing,
            "variables " . "read" . " " . $this->from
        );

        $this->link = $this->web_prefix;
        if ($this->link == false) {
            $this->link = "";
        }
    }

    function run()
    {
        // Now have this->link potentially from reading subject

        $this->matched_sentences = [];

        $this->robot_agent = new Robot($this->thing, $this->link);

        if (
            $this->robot_agent->robots_allowed(
                $this->link,
                $this->robot_agent->user_agent_short
            )
        ) {
            $this->response .= "Robot allowed. ";

            if (
                substr($this->link, 0, 4) === "http" or
                substr($this->link, 0, 5) === "https"
            ) {
                // Okay.
            } elseif (isset($this->robot_agent->scheme)) {
                $this->link = $this->robot_agent->scheme . '://' . $this->link;
            } else {
                return true;
            }
            // Populate $this->contents
            $this->getUrl($this->link);

            $this->metaRead($this->contents);
            $this->copyrightRead($this->contents);

            // Get all the URLs in the page.
            $url_agent = new Url($this->thing, "url");
            $this->urls = $url_agent->extractUrls($this->contents);
            $text = strip_tags($this->contents);
            // Remove multiple spaces
            $text = preg_replace('/\s+/', ' ', $text);
            // Remove start and end spaces
            $text = trim($text);

            //https://stackoverflow.com/questions/16377437/split-a-text-into-sentences
            $pattern = '/(?<=[.?!])\s+(?=[a-z])/i';

            //$pattern = '/(?<!\.\.\.)(?<!Dr\.)(?<=[.?!]|\.\.)|\.")\s+(?=[a-zA-Z"\(])/';
            $this->sentences = preg_split($pattern, $text);

            foreach ($this->sentences as $i => $sentence) {
                if (stripos($sentence, $this->search_phrase) !== false) {
                    $this->matched_sentences[] = $sentence;
                }
            }
        } else {
            $this->response .=
                "Robot not allowed. " . $this->robot_agent->response;
        }
    }

    function copyrightRead($html)
    {
        // devstack

        if (stripos($html, 'copywrite') !== false) {
            return true;
        }

        if (stripos($html, 'copyright') !== false) {
            return true;
        }

        if (stripos($html, '©') !== false) {
            return true;
        }

        if (stripos($html, '(c)') !== false) {
            return true;
        }

        if (stripos($html, 'copr') !== false) {
            return true;
        }

        return false;
    }

    function metaRead($html)
    {
        $doc = new \DOMDocument();
        //$doc->loadHTML('<?xml encoding="UTF-8">' . $html);
        @$doc->loadHTML($html);

        $xpath = new \DOMXpath($doc);
        //$elements = $xpath->query("*/div[@class='yourTagIdHere']");
        $elements = $xpath->query(
            "//*[contains(@class, 'class name goes here')]"
        );
    }

    /* A comment to break the confusion that the above string causes. */

    function set()
    {
        $this->variables_agent->setVariable("state", $this->state);

        $this->variables_agent->setVariable("link", $this->link);

        $this->variables_agent->setVariable(
            "refreshed_at",
            $this->current_time
        );

        $this->refreshed_at = $this->current_time;
    }

    function get()
    {
        $this->state = $this->variables_agent->getVariable("state");
        $this->link = $this->variables_agent->getVariable("link");
        $this->refreshed_at = $this->variables_agent->getVariables(
            "refreshed_at"
        );
    }

    function getUrl($url = null)
    {
        $this->contents = false;
        if ($url == null) {
            $this->link = $this->web_prefix;
            $url = $this->link;
        }

        $data_source = $this->link;

        $options = [
            'http' => [
                'method' => "GET",
                'header' =>
                    "User-Agent: " . $this->robot_agent->useragent . "\r\n",
            ],
        ];

        $context = stream_context_create($options);
        $data = file_get_contents($data_source, false, $context);

        if (isset($http_response_header[0])) {
            $response_string = $http_response_header[0];
        } else {
            $this->thing->log('No response code header found.');
            return true;
        }
        $parts = explode(' ', $response_string);
        $response_code = null;
        if (isset($parts[1])) {
            $response_code = $parts[1];
        }
        $this->response_code = $response_code;
        $allowed_response_codes = [301, 302, 200];

        if (
            $data == false or
            !in_array($response_code, $allowed_response_codes)
        ) {
            $this->thing->log('No response or response code not 200.');
            return true;
            // Invalid return from site..
        }

        // Raw file
        $this->contents = $data;
    }

    function match_all($needles, $haystack)
    {
        if (empty($needles)) {
            return false;
        }

        foreach ($needles as $needle) {
            if (strpos($haystack, $needle) == false) {
                return false;
            }
        }
        return true;
    }

    public function makeChoices()
    {
        $choices = false;
        $this->thing_report['choices'] = $choices;
    }

    public function makeTxt()
    {
        //        $this->thing_report['txt'] = implode("/n", $this->yard_sales);
        $this->thing_report['txt'] = "No text retrieved.";
    }

    function makeSMS()
    {
        $sms_message = "READ | ";
        $sms_message .= trim($this->response);

        if ($this->verbosity >= 2) {
        }
        /*
        $a = implode(" | ", $this->addresses);

        $addresses = $a;

        $sms_message .= " | " . $addresses;

        if ($this->verbosity >= 5) {
            $sms_message .= " | wordlist " . $this->wordlist;
        }
*/
        $sms_message .= " | link " . $this->link;

        if ($this->verbosity >= 9) {
            $sms_message .=
                " | nuuid " . substr($this->variables_agent->thing->uuid, 0, 4);
            $sms_message .=
                " | rtime " .
                number_format($this->thing->elapsed_runtime()) .
                'ms';
        }

        $sms_message .= " | TEXT ?";

        $this->thing_report['sms'] = $sms_message;
        $this->sms_message = $sms_message;
    }

    public function makeWeb()
    {
        $web = "<b>READ AGENT</b><p>";

        if ($this->urls == true) {
        } else {
            //var_dump($this->urls);
        }

        $web .= "<p><b>URLs read</b><br>";
        $link_agent = new Link($this->thing, "link");
        $link_agent->extractLinks($this->contents);
        //var_dump($link_agent->links);
        //exit();
        $links = array_unique($link_agent->links);
        foreach ($links as $i => $link) {
            $unsafe_characters = ['{', '}'];

            if (
                preg_match(
                    '/[' . preg_quote(implode(',', $unsafe_characters)) . ']+/',
                    $link
                )
            ) {
                continue;
            }

            $web .= '<a href="' . $link . '">' . $link . '</a>' . '<br>';
        }

        $sentence = $this->sentences[0];

        $word_agent = new Word($this->thing, "word");
        $words = $word_agent->extractWords($this->contents);

        $unique_words = array_unique($words);

        $web .= "<p><b>Words read</b>" . '<br>';

        foreach ($unique_words as $i => $unique_word) {
            $web .= $unique_word . " ";
        }

        $web .= '<p>';
        $web .= $this->sms_message;
        $web .= '<br>';
        $this->thing_report['web'] = $web;
    }

    public function respondResponse()
    {
        // Thing actions

        $this->thing->flagGreen();

        $this->thing_report['email'] = $this->sms_message;
        $this->thing_report['message'] = $this->sms_message; // NRWTaylor 4 Oct - slack can't take html in $test_message;

        $message_thing = new Message($this->thing, $this->thing_report);

        $this->thing_report['info'] = $message_thing->thing_report['info'];
        $this->thing_report['help'] = 'This reads a web resource.';
    }

    public function extractNumber($input = null)
    {
        if ($input == null) {
            $input = $this->subject;
        }

        $pieces = explode(" ", strtolower($input));

        // Extract number
        $matches = 0;
        foreach ($pieces as $key => $piece) {
            if (is_numeric($piece)) {
                $number = $piece;
                $matches += 1;
            }
        }

        if ($matches == 1) {
            if (is_integer($number)) {
                $this->number = intval($number);
            } else {
                $this->number = floatval($number);
            }
        } else {
            $this->number = true;
        }
        return $this->number;
    }

    public function readSubject()
    {
        $this->response = null;
        $this->num_hits = 0;

        $keywords = $this->keywords;

        $input = $this->assert($this->input);

        $url_agent = new Url($this->thing, "url");

        $this->url = $url_agent->extractUrl($input);

        $this->link = $this->url;

        $input = str_replace($this->url, "", $input);
        $this->search_phrase = trim(strtolower($input));

        $pieces = explode(" ", strtolower($input));

        if (count($pieces) == 1) {
            if ($input == 'read') {
                return;
            }
        }

        return "Message not understood";

        return false;
    }
}

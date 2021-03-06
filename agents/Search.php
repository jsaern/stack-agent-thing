<?php
namespace Nrwtaylor\StackAgentThing;

ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

ini_set("allow_url_fopen", 1);

class Search extends Agent
{
    public $var = 'hello';

    // http://www.helios825.org/url-parameters.php

    public $search_settings = [
        "google" => [
            "state" => "off",
            "search_prefix" => 'https://google.com/search?q=',
            "search_space" => "+",
        ],
        "amazon" => [
            "search_prefix" => 'https://amazon.com/?s?k=',
            "search_space" => "+",
        ],
        "craigslist vancouver" => [
            "search_prefix" =>
                'https://vancouver.craigslist.org/search/sss?query=',
            "search_postfix" => "&sort=rel",
            "search_space" => "%20",
        ],
        "etsy" => [
            "search_prefix" => "https://www.etsy.com/search?q=",
            "search_space" => "%20",
            "search_encoding" => "url",
        ],
        "amazon ca" => [
            "search_prefix" => 'https://amazon.ca/?s?k=',
            "search_space" => "+",
        ],
        "ebay" => [
            "search_prefix" => 'http://www.ebay.com/sch/',
            "search_space" => "%20",
            "search_encoding" => "url",
        ],
        "ebay ca" => [
            "search_prefix" => '%uFFFD php web link appearinghttps://ebay.ca/?s=',
            "search_space" => "+",
        ],
    ];

    public function init()
    {

        // https://codereview.stackexchange.com/questions/165263/move-one-element-before-another-in-an-associated-array
        $arr = $this->search_settings;
        while (count($arr) != 0) {
            $ngrams_count_max = 0;
            $longest_token = "";
            foreach ($arr as $search_engine => $search_settings) {
                $tokens = explode(" ", $search_engine);
                $count_tokens = count($tokens);
                if (count($tokens) > $ngrams_count_max) {
                    $longest_token = $search_engine;
                    $ngrams_count_max = $count_tokens;
                    //    $key_order[] = $search_engine;
                    //unset($arr[$search_engine]);
                }

                // Will this work?
            }

            $key_order[] = $longest_token;
            unset($arr[$longest_token]);
        }

        $this->search_engine_order = $key_order;

        $this->search_engines = [];
        foreach ($this->search_engine_order as $i => $search_engine) {
            $this->search_engines[$search_engine] =
                $this->search_settings[$search_engine];
        }
    }

    public function run()
    {
    }

public function linksSearch($text) {
$flag_first = true;
$this->search_links = array();
        foreach ($this->search_engines as $search_engine => $x) {
            if (!$flag_first) {
                $this->response .= ' - ';
            }
            $link = $this->urlSearch($search_engine, $text);
$this->search_links[$search_engine] = $link;

            $flag_first = false;
        }

return $this->search_links;

}

public function extractSearch($text = null) {

if ($text == null) {return true;}

        $parts = explode("search", strtolower($text));

$filtered_parts = array();
foreach($parts as $i=>$part) {

$part = trim($part);
if ($part == "") {continue;}
$filtered_parts[] = $part;

}

$input_search = "search";

if (isset($filtered_parts[0])) {
        $input_search = $filtered_parts[0];
}

if (isset($filtered_parts[1])) {
        $input_search = $filtered_parts[1];
}

$search_text = $this->readSearch($input_search);
//$this->linksSearch($search_text);

return $search_text;
//        $this->url = $url_agent->extractUrl($input);


}

    public function respondResponse()
    {
        $this->thing_report['info'] = 'Creates url search links.';

        if (!$this->thing->isData($this->agent_input)) {
            $message_thing = new Message($this->thing, $this->thing_report);
            $this->thing_report['info'] = $message_thing->thing_report['info'];
        }

        $this->thing_report['help'] =
            'This is an agent which understands what search is. And will help do one.';
    }

    public function urlSearch($search_engine = null, $raw_search_words = null)
    {
        if ($search_engine == null) {
            return;
        }

        if ($raw_search_words == null) {
            $raw_search_words = $this->search_text;
        }

        $links = "";
        $search_settings = $this->search_settings[$search_engine];
        //$search_words = $this->search_words;
        $search_words = $raw_search_words;
        if (
            isset($search_settings['search_encoding']) and
            $search_settings['search_encoding'] == "url"
        ) {
            $search_words = urlencode($search_words);
        }

        $search_postfix = "";
        if (isset($search_settings['search_postfix'])) {
            $search_postfix = $search_settings['search_postfix'];
        }

        $link =
            $search_settings['search_prefix'] .
            str_replace(
                " ",
                $search_settings['search_space'],
                $search_words . $search_postfix
            );

        $html_link =
            '<div><a href="' .
            $link .
            '">' .
            $search_engine .
            ' search</a></div>';

        $this->response .= $link;

        return $link;
    }
    /*
    function assert($search, $input)
    {
        $search = strtolower($search);
        $whatIWant = $input;
        if (($pos = strpos(strtolower($input), $search . " is")) !== FALSE) { 
            $whatIWant = substr(strtolower($input), $pos+strlen($subject . " is")); 
        } elseif (($pos = strpos(strtolower($input), $search)) !== FALSE) { 
            $whatIWant = substr(strtolower($input), $pos+strlen($search)); 
        }

        $filtered_input = ltrim(strtolower($whatIWant), " ");
        return $filtered_input;
    }
*/

    public function readSearch($text = null)
    {
        if ($text == null) {
            return false;
        }

        $text = trim($text);

        $brilltagger = new Brilltagger($this->thing, "brilltagger");

        $arr = $brilltagger->tag($text);

        $search_tokens = [];

        foreach ($arr as $i => $tag_array) {
            $token = $tag_array['token'];
            // devstack - fix brilltagger tag name generator
            $tag = trim($tag_array['tag']);

            $allow_tags = ["JJ", "NNS", "NN"];

            if (in_array($tag, $allow_tags)) {
                $search_tokens[] = $token;
            }
        }

        $search_text = trim(implode(" ", $search_tokens));

        return $search_text;
    }

    public function makeSMS()
    {

$links = $this->linksSearch($this->search_text);


        $sms = "SEARCH | ";
//        $sms .= $this->response;

foreach ($links as $search_engine=>$link) {
$sms .= $search_engine . ' ' . $link . ' ';

}


        $this->sms_message = $sms;
        $this->thing_report['sms'] = $sms;
    }

public function makeWeb()
{

$links = $this->linksSearch($this->search_text);
$web = "<b>Search Agent</b><p>";
$web .= $this->input . "<br>";
//$web .= $this->subject ."<br>";
//$web .= $this->thing->subject . "<br>";
foreach ($this->search_links as $search_engine=>$link) {
//echo $link;
$web .= $search_engine . ' <a href="' . $link .'">' . $this->search_text . '</a>' . "<br>";

}

$this->thing_report['web'] = $web;


}



    public function readSubject()
    {

$input = $this->input;
        $this->search_text = $this->extractSearch($input);

$pieces = explode(" ", $this->search_text);

        if (count($pieces) == 1) {
            if (strtolower($input) == 'search') {
                return;
            }
        }

    }

public function build() {

        $matches = 0;

        foreach ($this->search_engine_order as $i => $search_engine) {
            if (strpos($filtered_input, $search_engine) !== false) {
                $filtered_input = $this->assert(
                    $search_engine,
                    $filtered_input
                );
                $this->search_engines[$search_engine] =
                    $this->search_settings[$search_engine];
            }
        }

        if ($matches == 1) {
            $this->search_engine = $this->search_engines[0];
        }


}


}

<?php
/**
 * Fetcher class
 * 
 * Fetches events from various sources and stores them as Event objects in an array.
 * 
 * @property array $calendars     // Array of source calendars to fetch
 * @property int $timeout         // Fetch timeout in seconds
 * @property array $events        // Array of fetched events
 */

if( ! IS_AGGR ) {
    die('No direct calls, run main script aggregator.php instead.' . PHP_EOL);
}

class Fetcher {
    private $calendars = array();
    private $timeout = 5;
    private $events = array();
    
    public function __construct() {
        $this->read_config_ical();
        $this->fetch();
        // print_r($this->events);
    }
    
    private function read_config_ical( $config = BASE_DIR . '/config/ical.cfg' ) {
        if( ! file_exists($config) ) {
            // throw new Exception('ical.cfg not found');
            echo "Copy config/ical.cfg.example as $config and adjust to your taste before running this script.\n\n";
            Aggregator::admin_notice("ical.cfg not found, aborting.", 1, true);
        }

        $csv = file($config);
        #ignore empty lines, lines containing only spaces and lines starting with # or ;
        $csv = array_filter($csv, function($line) {
            return !empty($line) && $line[0] != '#' && $line[0] != ';' && !ctype_space($line);
        });        
        
        foreach ($csv as $line) {
            #ignore empty lines, lines containing only spaces and lines starting with # or ;
            
            if (empty($line) || $line[0] == '#' || $line[0] == ';' || ctype_space($line) ) {
                continue;
            }
            #ignore lines that don't contain less than two comma
            if (substr_count($line, ',') < 2) {
                continue;
            }
            
            list($slug, $grid_url, $ical_url) = explode(',', $line);
            if (empty($slug) || empty($grid_url) || empty($ical_url)) {
                continue;
            }
            $this->calendars[$slug] = array(
                'slug' => $slug,
                'grid_url' => $grid_url,
                'ical_url' => trim($ical_url),
                'type' => 'ical',
            );
        }
    }

    public function fetch() {
        foreach ($this->calendars as $slug => $calendar) {
            if ($calendar['type'] == 'ical') {
                $this->fetch_ical($slug, $calendar);
            } else {
                Aggregator::admin_notice ( "$slug source type ${calendar['type']} not implemented", 1 );
            }
        }
        
        usort($this->events, function($a, $b) {
            return $a->dateUTC <=> $b->dateUTC;
        });

        Aggregator::notice(count($this->events) . " events fetched");
    }

    private function fetch_ical($slug, $calendar) {
        # get ical url with a timeout of 5 seconds
        $url = $calendar['ical_url'];

        $command = 'php parsers/ical-parser.php ' . escapeshellarg($url);
        try {
            $json = shell_exec($command);
        } catch (Exception $e) {
            Aggregator::admin_notice("$slug $url parse error " . " " . $e->get_code() . ": " . $e->get_message() );
            return;
        }
        $source_events = json_decode($json, true);

        if(empty($source_events)) {
            Aggregator::notice("$slug no events");
            return;
        }
        if(!is_array($source_events)) {
            Aggregator::admin_notice("$slug $url error: wrong answer format", 1);
            return;
        }
        
        $events = array();
        foreach ($source_events as $source) {
            // Aggregator::admin_notice("source " . print_r($source, true) );

            $event = new Event($source, $calendar);
            if($event === false) {
                continue;
            }
            // Don't override if already fetched, it might be a later date for repeating events
            // TODO: check if repeating events share the same uid
            // TODO: generate uid if not present (should not happen wih iCal though)
            if(empty($events[$event->uid]) && empty($this->events[$event->uid])) {
                $events[$event->uid] = $event;
            }
        }
        Aggregator::notice("$slug " . count($events) . " events");
        $this->events = array_merge($this->events, $events);
    }

    public function get_events() {
        return $this->events;
    }
}

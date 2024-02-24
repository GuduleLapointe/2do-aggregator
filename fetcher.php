#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';
require_once 'includes/functions.php';

// require_once 'vendor/magicoli/opensim-helpers/includes/opensim-helpers.php';
use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;

class Fetcher {
    private $calendars = array();
    private $timeout = 5;
    private $events = array();
    
    public function __construct() {
        $this->read_config_ical();
        $this->fetch();
        // print_r($this->events);
    }
    
    private function read_config_ical( $config = 'config/ical.cfg' ) {
        if( ! file_exists($config) ) {
            // throw new Exception('ical.cfg not found');
            echo "Copy config/ical.cfg.example as config/ical.cfg and adjust to your taste before running this script.\n\n";
            osAdminNotice("ical.cfg not found, aborting.", 1, true);
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
                osAdminNotice ( "$slug source type ${calendar['type']} not implemented", 1 );
            }
        }
        osAdminNotice("Fetched " . count($this->events) . " events");
    }

    private function fetch_ical($slug, $calendar) {
        # get ical url with a timeout of 5 seconds
        $url = $calendar['ical_url'];

        $command = 'php parse_icalendar.php ' . escapeshellarg($url);
        try {
            $json = shell_exec($command);
        } catch (Exception $e) {
            osAdminNotice("$slug $url parse error " . " " . $e->get_code() . ": " . $e->get_message() );
            return;
        }
        $source_events = json_decode($json, true);

        if(empty($source_events)) {
            osNotice("$slug no events");
            return;
        }
        if(!is_array($source_events)) {
            osAdminNotice("$slug $url error: wrong answer format", 1);
            return;
        }
        
        $events = array();
        foreach ($source_events as $source) {
            // osAdminNotice("source " . print_r($source, true) );

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
        osNotice("$slug " . count($events) . " events");
        $this->events = array_merge($this->events, $events);
    }
}

class Event {
    public $uid;
    public $name;
    public $description;
    public $simname;
    public $dateUTC;
    public $duration;
    public $category;
    public $owneruuid;
    public $creatoruuid;
    public $covercharge;
    public $coveramount;
    public $parcelUUID;
    public $globalPos;
    public $eventflags;
    public $gatekeeperURL;
    public $hash;

    /**
     * Event constructor.
     * 
     * @param array $data
     */
    public function __construct($data, $calendar = array() ) {
        $original_data = $data;
        // Make sure all required indices are present
        $data = array_merge( EVENT_STRUCTURE, $data);
        $data['category'] = $this->sanitize_category($data['category']);
        $sanitized_url = $this->sanitize_hgurl($data['simname'], $calendar['grid_url'] );
        if($sanitized_url === false) {
            osAdminNotice( sprintf(
                "%s event %s error checking location %s",
                $calendar['slug'],
                $data['uid'],
                empty($data['simname']) ? $calendar['grid_url'] : $data['simname'],
            ) );
            return false;
        }
        if(empty($sanitized_url)) {
            osAdminNotice( sprintf(
                "%s event %s has no location %s",
                $calendar['slug'],
                $data['uid'],
                empty($data['simname']) ? $calendar['grid_url'] : $data['simname'],
            ) );
            return false;
        }
        $data['simname'] = $sanitized_url;

        // TODO: generate uid if not present (for other sources than iCal)
        $this->uid = $data['uid'];
        $this->owneruuid = $data['owneruuid'];
        $this->name = $data['name'];
        $this->creatoruuid = $data['creatoruuid'];
        $this->category = $data['category'];
        $this->description = $data['description'];
        $this->dateUTC = $data['dateUTC'];
        $this->duration = $data['duration'];
        $this->covercharge = $data['covercharge'];
        $this->coveramount = $data['coveramount'];
        $this->simname = $data['simname'];
        $this->parcelUUID = $data['parcelUUID'];
        $this->globalPos = $data['globalPos'];
        $this->eventflags = $data['eventflags'];
        $this->gatekeeperURL = $data['gatekeeperURL'];
        $this->hash = $data['hash'];
    }

    public function sanitize_hgurl($url, $grid_url = null) {
        static $sanitize_hgurl_cache = [];

        if ( empty( $url ) ) {
            $url = $grid_url;
        }

        // Return cached value if available
        if (isset($sanitize_hgurl_cache[$url])) {
            switch ($sanitize_hgurl_cache[$url]) {
                case 'empty':
                    return;
                case 'offline':
                    return false;
                case 'invalid':
                    return false;
            }
            return $sanitize_hgurl_cache[$url];
        }

        $region = opensim_sanitize_uri( $url, $grid_url, true );

        $tmpurl = opensim_sanitize_uri( $url, $grid_url );
        
        $region_data = opensim_get_region( $tmpurl );
        
        if(empty($region_data)) {
            osAdminNotice("region $tmpurl data could not be fetched" );

            $sanitize_hgurl_cache[$url] = "invalid";
            return false;
        }
        $region['region'] = (empty($region['region'])) ? $region_data['region_name'] : $region['region'];
        if(!opensim_region_is_online($region)) {
            osAdminNotice("region $tmpurl is offline" );

            $sanitize_hgurl_cache[$url] = "offline";
            return false;
        }
        $tmpurl = $region['gatekeeper'] . ':' . $region['region'] . ( empty($region['pos']) ? '' : '/' . $region['pos'] );
        $url = opensim_format_tp( $tmpurl, TPLINK_TXT );

        $sanitize_hgurl_cache[$url] = $url;
        return $url;
    }

    public function sanitize_category($values) {
        if ( empty( $values ) ) {
            return 0; // Undefined
        }
        if ( ! is_array( $values ) ) {
            $values = $array( $values );
        }
        foreach ( $values as $value ) {
            if ( is_int( $value ) ) {
                return $value;
            }
            $key = strtolower( $value );
            if ( isset( $this->categories[ $key ] ) ) {
                return $this->categories[ $key ];
            }
        }
        return 29; // Not undefined, but unknown, so we return Miscellaneous
    }
}

// check for command line arguments, if -q is set, don't output anything. -q could be anywhere in the arguments
$quiet = in_array('-q', $argv) ? true : false; //|| in_array('-q', array_slice($argv, 1));
if($quiet) {
    array_splice($argv, array_search('-q', $argv), 1);
    error_reporting(0);
    ini_set('display_errors', '0');
}

if( isset($argv[1]) ) {
    $output_dir = $argv[1];
    // error if output is not a directory
} else {
    // make temp directory for output

    $tempnam = tempnam(sys_get_temp_dir(), basename($argv[0]) . '.');
    if ($tempnam === false) {
        osAdminNotice("Could not create temporary file", 1, true);
    }
    unlink($tempnam);
    mkdir($tempnam);
    $output_dir = $tempnam;

    // trap exit and delete temp directory
    register_shutdown_function(function() use ($tempnam) {
        if (is_dir($tempnam)) {
            $files = scandir($tempnam);
            $files = array_diff($files, array('.', '..'));

            // We don't delete temp directory unless it's empty
            if(empty($files)) {
                osadminNotice("Deleting empty temp directory $tempnam");
                rmdir($tempnam);
            // } else {
            //     osAdminNotice("temp directory $tempnam is not empty, not deleting " . print_r($files, true), 1);
            }
            // foreach ($files as $file) {
            //     if ($file == '.' || $file == '..') {
            //         continue;
            //     }
            //     echo "unlink($tempnam . '/' . $file)";
            // }
            // echo "rmdir($tempnam)";
        }
    });
}
if( !is_dir($output_dir) ) {
    osAdminNotice("Output directory $output_dir does not exist", 1, true);
}

define( 'EVENTS_NULL_KEY', '00000000-0000-0000-0000-000000000001' );
define( 'DEFAULT_POS', array(128, 128, 25) );
define( 'CATEGORIES', array(
    'discussion'              => 18,
    'sports'                  => 19,
    'live music'              => 20,
    'commercial'              => 22,
    'nightlife/entertainment' => 23,
    'games/contests'          => 24,
    'pageants'                => 25,
    'education'               => 26,
    'arts and culture'        => 27,
    'charity/support groups'  => 28,
    'miscellaneous'           => 29,
    
    // Aliases
    'nightlife'               => 23, // Nightlife/Entertainment
    'entertainment'           => 23, // Nightlife/Entertainment
    'games'                   => 24, // Games/Contests
    'contests'                => 24, // Games/Contests
    'charity'                 => 28, // Charity / Support Groups
    'support groups'          => 28, // Charity / Support Groups

    // From HYPEvents code:
    'music'                   => 20, // Live Music
    'fair'                    => 23, // Nightlife/Entertainment
    'roleplay'                => 24, // Games/Contests
    'education'               => 26, // Education
    'art'                     => 27, // Art & Culture
    'lecture'                 => 27, // Art & Culture
    'litterature'             => 27, // Art & Culture
    'social'                  => 28, // Charity / Support Groups
));

define('EVENT_STRUCTURE', array(
    'source'        => NULL,
    'uid'           => NULL,
    'owneruuid'     => EVENTS_NULL_KEY, // Not implemented
    'name'          => NULL,
    'creatoruuid'   => EVENTS_NULL_KEY, // Not implemented
    'category'      => NULL,
    'description'   => NULL,
    'dateUTC'       => NULL,
    'duration'      => NULL,
    'covercharge'   => 0, // Not implemented
    'coveramount'   => 0, // Not implemented
    'simname'       => NULL,
    'parcelUUID'    => EVENTS_NULL_KEY, // Not implemented
    'globalPos'     => NULL,
    'eventflags'    => 0, // Not implemented
    'gatekeeperURL' => NULL,
    'hash'          => NULL,
));

$fetcher = new Fetcher();

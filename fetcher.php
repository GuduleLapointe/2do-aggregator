#!/usr/bin/env php
<?php
require_once 'vendor/autoload.php';
require_once 'helpers/includes/functions.php';

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
    }
    
    private function read_config_ical( $config = 'config/ical.cfg' ) {
        if( ! file_exists($config) ) {
            // throw new Exception('ical.cfg not found');
            error_log("ical.cfg not found, aborting.\nCopy config/ical.cfg.example as config/ical.cfg and adjust to your state.");
            die(1);
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
                error_log ( "$slug source type ${calendar['type']} not implemented" );
            }
        }
    }

    private function fetch_ical($slug, $calendar) {
        error_log("calendar " . print_r($calendar, true));

        # get ical url with a timeout of 5 seconds
        $url = $calendar['ical_url'];

        $command = 'php parse_icalendar.php ' . escapeshellarg($url);
        try {
            $json = shell_exec($command);
        } catch (Exception $e) {
            error_log("$slug parse failed to fetch $url " . $e->get_message() );
            return;
        }
        $source_events = json_decode($json, true);

        if( $source_events === null ) {
            error_log("$slug parse failed $url");
            return;
        }
        if(empty($source_events)) {
            error_log("$slug parse no events found");
            return;
        }
        if(!is_array($source_events)) {
            error_log("$slug parse wrong format");
            return;
        }
        
        $events = array();
        foreach ($source_events as $source) {
            error_log("source " . print_r($source, true) );

            $event = new Event($source, $calendar);
            if($event === false) {
                continue;
            }
            $events[$event->uid] = $event;
            error_log("event " . print_r($event, true) );
            die("DEBUG\n");
        }
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
        // Make sure all required indices are present
        $data = array_merge( EVENT_STRUCTURE, $data);
        $data['category'] = $this->sanitize_category($data['category']);
        $data['simname'] = $this->sanitize_hgurl($data['simname'], $calendar['grid_url'] );
        if(empty($data['simname'])) {
            error_log( sprintf(
                "%s event %s %s has no location, skipping",
                $calendar['slug'],
                $data['uid'],
                $data['name'],
            ) );
            return false;
        }
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
        
        // TODO: Implement
        $url = "speculoos.world:8002:Grand Place/12/13/23";
        $url = "Grand Place/12/13/23";
        
        // Use cached value if available
        if (isset($sanitize_hgurl_cache[$url])) {
            error_log("cache hit for $url " . $sanitize_hgurl_cache[$url]);
            return $sanitize_hgurl_cache[$url];
        }

        $region = opensim_sanitize_uri( $url, '', true );
        error_log("opensim_sanitize_uri " . print_r($region, true) );
        return $url;

        $region['region'] = trim(str_replace("_", " ", $region['region'] ));
        error_log("region before " . print_r($region, true) );
        
        $tmpurl = $region['gatekeeper'] . ':' . $region['region'] . ( empty($region['pos']) ? '' : '/' . $region['pos'] );
        $region_data = opensim_get_region( $tmpurl );
        $region['region'] = (empty($region['region'])) ? $region_data['region_name'] : $region['region'];
        error_log("region_data " . print_r($region_data, true) );
        error_log("region after " . print_r($region, true) );
        if(empty($region_data)) {
            error_log("get_region failed for $url");
            return false;
        }
        
        return $url;

        

        error_log("get_region " . print_r($region_data, true) );

        $pos        = ( empty( $region['pos'] ) ) ? DEFAULT_POS : explode( '/', $region['pos'] );
        error_log("pos " . print_r($pos, true) );

        $region['pos'] = implode( ',', $pos );
        error_log("region " . print_r($region, true) );


        // $slurl       = opensim_format_tp( $url, TPLINK_TXT );
        // $links       = opensim_format_tp( $url, TPLINK_APPTP + TPLINK_HOP );
        $sanitize_hgurl_cache[$url] = $sanitizedUrl;
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

function opensim_region_url($region) {
    return $region['gatekeeper'] . ( empty($region['region']) ? '' : ':' . $region['region'])  . ( empty($region['pos']) ? '' : '/' . $region['pos'] );
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

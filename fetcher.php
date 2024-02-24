<?php
require_once 'vendor/autoload.php';

use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;

define( 'EVENTS_NULL_KEY', '00000000-0000-0000-0000-000000000001' );
define( 'DEFAULT_POS', array(128, 128, 25) );

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
            die();
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
        # get ical url with a timeout of 5 seconds
        $url = $calendar['ical_url'];

        $command = 'php parse_icalendar.php ' . escapeshellarg($url);
        try {
            $json = shell_exec($command);
        } catch (Exception $e) {
            error_log("$slug parse failed to fetch $url " . $e->get_message() );
            return;
        }
        $events = json_decode($json, true);

        if( $events === null ) {
            error_log("$slug parse failed $url");
            return;
        }
        if(empty($events)) {
            error_log("$slug parse no events found");
            return;
        }
        if(!is_array($events)) {
            error_log("$slug parse wrong format");
            return;
        }
        
        foreach ($events as $source) {
            error_log("source " . print_r($source, true) );
            die("DEBUG\n");          

            // $event = new Event($source);
            // error_log("event " . print_r($event, true) );
            // die("DEBUG\n");
            
            // $event = new Event(array(
            //     'source'        => $slug,
            //     'uid'           => $source['uid'],
            //     'owneruuid'     => EVENTS_NULL_KEY, // Not implemented
            //     'name'          => $source['summary'],
            //     'creatoruuid'   => EVENTS_NULL_KEY, // Not implemented
            //     'category'      => $source['categories'],
            //     'description'   => $source['description'],
            //     'dateUTC'       => $source['start'],
            //     'duration'      => $source['duration'],
            //     'covercharge'   => 0, // Not implemented
            //     'coveramount'   => 0, // Not implemented
            //     'simname'       => $source['location'],
            //     'parcelUUID'    => EVENTS_NULL_KEY, // Not implemented
            //     'globalPos'     => empty( $source['pos'] ) ? DEFAULT_POS : $source['pos'],
            //     'eventflags'    => 0, // Not implemented
            //     'gatekeeperURL' => $calendar['grid_url'],
            //     'hash'          => $source['hash'],
            // ));
        }
    }
}

class Event {
    public $uid;
    public $summary;
    public $description;
    public $start;
    public $duration;
    public $location;
    public $pos;
    public $hash;
    public $event = array();
    public $categories = array(
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

        // From HYPEvents code:
        'art'                     => 27, // Art & Culture
        'education'               => 26, // Education
        'fair'                    => 23, // ambiguous, could be 23 Nightlife, or 27 Art, or 28 Charity
        'lecture'                 => 27, // Art & Culture
        'litterature'             => 27, // Art & Culture
        'music'                   => 20, // Live Music
        'roleplay'                => 24, // Games/Contests
        'social'                  => 28, // Charity / Support Groups
    );

    public function __construct($data) {
        $data = array_merge(array(
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
        ), $data);

        $data['category'] = $this->sanitize_category($data['category']);
        $data['simname'] = $this->sanitize_hgurl($data['simname']);
    }

    public function sanitize_hgurl($url) {
        // TODO: Implement
        // $region     = opensim_sanitize_uri( $json_event['hgurl'], '', true );
        // $get_region = opensim_get_region( $json_event['hgurl'] );
        // $pos        = ( empty( $region['pos'] ) ) ? array( 128, 128, 25 ) : explode( '/', $region['pos'] );
        // if ( ! empty( $get_region['x'] ) & ! empty( $get_region['y'] ) ) {
        //     $pos[0] += $get_region['x'];
        //     $pos[1] += $get_region['y'];
        // }
        // $pos = implode( ',', $pos );
    
        // $slurl       = opensim_format_tp( $json_event['hgurl'], TPLINK_TXT );
        // $links       = opensim_format_tp( $json_event['hgurl'], TPLINK_APPTP + TPLINK_HOP );
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

$fetcher = new Fetcher();

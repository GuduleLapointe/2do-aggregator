<?php

use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;

/**
 * Event class
 * 
 * Represents an event of the calendar
 * 
 * @property string $uid            // Unique identifier, collected from source if possible
 * @property string $name           // Event name
 * @property string $description    // Event description
 * @property string $simname        // Region name for standalone grids only
 *                                  // Region Hypergrid URL for hypergrid events
 * @property string $dateUTC        // Event start date and time in UTC
 * @property int $duration          // Event duration in minutes
 * @property int $category          // Event category code number
 * @property string $owneruuid      // Not implemented
 * @property string $creatoruuid    // Not implemented
 * @property int $covercharge       // Not implemented
 * @property int $coveramount       // Not implemented
 * @property string $parcelUUID     // Not implemented
 * @property string $globalPos      // Not implemented
 * @property int $eventflags        
 * @property string $gatekeeperURL  // Region grid target URL
 * @property string $hash           // Not implemented
 */
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
            Aggregator::admin_notice( sprintf(
                "%s event %s error checking location %s",
                $calendar['slug'],
                $data['uid'],
                empty($data['simname']) ? $calendar['grid_url'] : $data['simname'],
            ) );
            return false;
        }
        if(empty($sanitized_url)) {
            Aggregator::admin_notice( sprintf(
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

    /**
     * Sanitize a hypergrid URL
     * 
     * @param string $url           // URL to sanitize
     * @param string $grid_url      // Grid URL to use if $url is empty or missin host
     * @return string|bool          // Sanitized URL or false if the region is offline or invalid
     */
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
            Aggregator::admin_notice("region $tmpurl data could not be fetched" );

            $sanitize_hgurl_cache[$url] = "invalid";
            return false;
        }
        $region['region'] = (empty($region['region']) &! empty($region_data['region_name'])) ? $region_data['region_name'] : $region['region'];
        if(!opensim_region_is_online($region)) {
            Aggregator::admin_notice("region $tmpurl is offline" );

            $sanitize_hgurl_cache[$url] = "offline";
            return false;
        }
        $tmpurl = $region['gatekeeper'] . ':' . $region['region'] . ( empty($region['pos']) ? '' : '/' . $region['pos'] );
        $url = opensim_format_tp( $tmpurl, TPLINK_TXT );

        $sanitize_hgurl_cache[$url] = $url;
        return $url;
    }

    /**
     * Sanitize category code. 
     * 
     * Return category code number if $value is a valid number, otherwise the best guess 
     * based on the given string(s). First match wins.
     * 
     * @param integer|string|array $values  // Category name or array of category names
     * @return int                          // Valid category code number
     */
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
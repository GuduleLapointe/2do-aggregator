<?php
// Independant process to fetch ical data from url given as argument and output it
// to stdout if a format that the parent script can use to fill an array of events

require_once 'vendor/autoload.php';
require_once 'includes/functions.php';

use Kigkonsult\Icalcreator\Vcalendar;
use Kigkonsult\Icalcreator\Vevent;

$timeout = 5;
$process_from = '- 1 day';
$process_to = '+ 3 months';
$scriptName = $argv[0];

if( !isset($argv[1]) ) {
    osAdminNotice("Usage: $scriptName <ical_url>", 1, true);
}

$url = $argv[1];

error_reporting(0);
ini_set('display_errors', '0');

try {
    $ics_data = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'timeout' => 5,
        ),
    )));
} catch (Exception $e) {
    osAdminNotice( $e.get_message(), 1, true );
}
if($ics_data === false) {
    osAdminNotice("$scriptName $url data fetch failed", 2, true);
}

$ics_data = preg_replace('/:MAILTO:(?![^:]*@[^:]*\.[^:]*\b)([^:\n]*)(?=\n|$)/i', "$1", $ics_data);
$ics_data = preg_replace('/:$/m', '', $ics_data);

// Check if $ics_data is a valid ics formatted file or google calendar file
if (strpos($ics_data, 'BEGIN:VCALENDAR') === false && strpos($ics_data, 'BEGIN:VEVENT') === false) {
    osAdminNotice("$scripName $url ERROR, not a valid ics file", 3, true);
}

// Use Kigkonsult\Icalcreator to parse $ics_data and create an array of events
$vcalendar = Vcalendar::factory();

try {
    $vcalendar->parse($ics_data);
} catch (Exception $e) {
    // Log the error
    osAdminNotice("$scriptName $url error " . $e->get_code() . ': ' . $e->get_message(), 4, true);
}
$vcalendar->sort();

$startDate = new DateTime();
$startDate->modify($process_from);
$endDate = new DateTime();
$endDate->modify($process_to);

try {
    $vevents = $vcalendar->selectComponents(
        $startDate->format('Y'), $startDate->format('m'), $startDate->format('d'),
        $endDate->format('Y'), $endDate->format('m'), $endDate->format('d'),
        Vcalendar::VEVENT
    );
} catch (Exception $e) {
    // Log the error
    osAdminNotice("$scriptName $url error " . $e->get_code() . ': ' . $e->get_message(), 5, true);
}
if($vevents === false) {
    // Silently fail if no events are found
    die();
}

$events = array();

foreach ($vevents as $yearlyEvents) {
    foreach ($yearlyEvents as $monthlyEvents) {
        foreach ($monthlyEvents as $dailyEvents) {
            foreach ($dailyEvents as $vevent) {
                $uid = $vevent->getUid();
                $dtstart = $vevent->getDtstart();
                $dtend = $vevent->getDtend();
                $duration = $vevent->getDuration();

                if ($duration) {
                    $interval = new DateInterval($duration);
                    $durationInMinutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                } else {
                    $interval = $dtend->diff($dtstart);
                    $durationInMinutes = $interval->days * 24 * 60 + $interval->h * 60 + $interval->i;
                }
                
                $dateUTC = $dtstart->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:sP');

                $event = array(
                    'source_url' => $url,
                    'uid' => $vevent->getUid(),
                    // 'dtstart' => $vevent->getDtstart(),
                    // 'dtend' => $vevent->getDtend(),
                    'dateUTC' => $dateUTC,
                    'duration' => $durationInMinutes,
                    // 'owneruuid' => null, // Not implemented
                    // 'creatoruuid' => null, // Not implemented
                    'name' => $vevent->getSummary(),
                    'category' => $vevent->getCategories(),
                    'description' => $vevent->getDescription(),
                    // 'covercharge' => 0, // Not implemented
                    // 'coveramount' => 0, // Not implemented
                    'simname' => $vevent->getLocation(),
                    // 'parcelUUID' => null, // Not implemented
                    // 'globalPos' => null, // Will be processed. by the main script
                    // 'eventflags' => 0, // Not implemented
                    // 'gatekeeperURL' => null, // Will be processed. by the main script
                    // 'hash' => null, // Will be processed. by the main script
                );

                $events[$uid] = $event;
            }
        }
    }
}

echo json_encode($events);

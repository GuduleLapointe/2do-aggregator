<?php
// Independant process to fetch ical data from url given as argument and output it
// to stdout if a format that the parent script can use to fill an array of events

if( ! defined('APP_DIR') ) {
    define('APP_DIR', dirname(__DIR__));
}

require_once APP_DIR . '/vendor/autoload.php';
require_once APP_DIR . '/includes/functions.php';

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

$source_url = "../dev/dev/opensimworld-events.php";
// $source_url = "https://opensimworld.com/events/";
$source_tz = 'America/Los_Angeles';

$timeout = 5;
$process_from = '- 1 day';
$process_to = '+ 3 months';

$startDate = new DateTime();
$startDate->modify($process_from);
$endDate = new DateTime();
$endDate->modify($process_to);

$scriptName = $argv[0];

function fetchHGUrl($event_page_url) {
    static $url_cache;
    if(isset($url_cache[$event_page_url])) {
        // error_log("DEBUG: Cache hit for $event_page_url");
        return $url_cache[$event_page_url];
    }

    $client = new Client();
    $crawler = $client->request('GET', $event_page_url);

    $hgurl_input = $crawler->filter('input#hgAddr')->attr('value');
    $hgurl = $hgurl_input ? $hgurl_input : "-";

    $url_cache[$event_page_url] = $hgurl;
    return $hgurl;
}

$client = new Client();
$crawler = $client->request('GET', $source_url);

// original python selector
// event_rows = tree.xpath('//div[@class="container wcont"]//table[@class="table table-striped table-bordered"]//tr')[1:]

$client = new Client();
$crawler = $client->request('GET', 'https://opensimworld.com/events/');

$i=0;
$events = $crawler->filter('div.container.wcont table.table.table-striped.table-bordered tr')->each(function (Crawler $node) {
    global $source_url;
    $event = [];

    $title = $node->filter('h4 b a')->text();
    
    $parentHtml = $node->filter('td > b')->html();
    preg_match('/\b\d{4}-\d{2}-\d{2} \d{2}:\d{2}\b/', $parentHtml, $matches);
    $datetime_slt = $matches[0] ?? '';
    $date = DateTime::createFromFormat('Y-m-d H:i', $datetime_slt, new DateTimeZone('America/Los_Angeles'));
    $date->setTimezone(new DateTimeZone('UTC'));
    $datetime_utc = $date->format('Y-m-d H:i');

    $event_id = $node->filter('td b a[href^="/hop/"]')->attr('href');
    $event_page_url = "https://opensimworld.com" . $event_id;
    $tpurl = fetchHGUrl($event_page_url);
    $description = $node->filter('div small')->text();

    $event['source_url'] = $source_url;
    $event['uid'] = $event_page_url;; // a unique id and the domain name of the source
    $event['dateUTC'] = $datetime_utc; // event start date in UTC
    $event['duration'] = 120; // duration in minutes, not provided, 2 hours by default
    $event['name'] = $title; // title + " @HIE " + year of the event
    $event['category'] = 'Education';
    $event['tags'] = []; // an array of tags, including 'HIE ' . year
    $event['description'] = $description;
    $event['simname'] = $tpurl; // Destination HG teleport url

    return $event;
});

echo json_encode($events);
exit;

print_r($events);
echo "count of events: " . count($events) . "\n";
die("DEBUG END\n");

// Save format
//
// $events = array();
//     $event = array(
//         'source_url' => $url,
//         'uid' => ''; // a unique id and the domain name of the source
//         // 'dtstart' => $vevent->getDtstart(),
//         // 'dtend' => $vevent->getDtend(),
//         'dateUTC' => $dateUTC,              // event start date in UTC
//         'duration' => $durationInMinutes,
//         // 'owneruuid' => null, // Not implemented
//         // 'creatoruuid' => null, // Not implemented
//         'name' => $vevent->getSummary(),    // title + " @HIE " + year of the event
//         'category' => 'Education',
//         'tags' => $tags,                    // an array of tags, including 'HIE ' . year
//         'description' => $vevent->getDescription(),
//         // 'covercharge' => 0, // Not implemented
//         // 'coveramount' => 0, // Not implemented
//         'simname' => $vevent->getLocation(),    // Destination HG teleport url
//         // 'parcelUUID' => null, // Not implemented
//         // 'globalPos' => null, // Will be processed. by the main script
//         // 'eventflags' => 0, // Not implemented
//         // 'gatekeeperURL' => null, // Will be processed. by the main script
//         // 'hash' => null, // Will be processed. by the main script
//     );
//     $events_count++;
//     $events[] = $event;
//
// error_log("\nDEBUG: " . count($events) . "/$events_count events\n");
// echo json_encode($events);
// exit


//
// Below is the original python code to retrieve the parsing logic.
//

// #!/usr/bin/env python
// import requests
// from lib.event import Event
// import datetime
// from dateutil import parser as date_parser
// from lxml import html
// import sys
// import pytz
// from lib.category import Category

// class OpenSimWorldEvent(Event):
//     tz_pacific = pytz.timezone('US/Pacific')

//     def __init__(self, webcache=None, url=None):
//         super(OpenSimWorldEvent, self).__init__()
//         self.id = None
//         self.webcache = webcache
//         self.url = url

//     def fetchHGUrl(self, url):
//         event_page_url = "https://opensimworld.com" + url

//         if self.webcache is None:
//             r = requests.get(event_page_url)
//         else:
//             r = self.webcache.fetch(event_page_url, 24 * 3600, 48 * 3600)

//         if r.status_code == 200:
//             tree = html.fromstring(r.text)
//             hgurl_input = tree.xpath('//input[@id="hgAddr"]/@value')
//             self.hgurl = hgurl_input[0] if hgurl_input else "-"

//     def __str__(self):
//         rv = super(OpenSimWorldEvent, self).__str__()
//         return rv

// class OpenSimWorldFetcher:
//     event_page_url = "https://opensimworld.com/events/"
//     tz_slt = pytz.timezone('Etc/GMT+8')  # SLT is GMT+8
//     tz_pst = pytz.timezone('America/Los_Angeles')  # PST is America/Los_Angeles

//     def __init__(self, eventlist, webcache):
//         self.eventlist = eventlist
//         self.webcache = webcache

//     def fetch(self, limit=0):
//         print("OpenSimWorldFetcher: fetch overview..")

//         r = self.webcache.fetch(self.event_page_url, 900, 1800)

//         if r.status_code == 200:
//             tree = html.fromstring(r.text)

//             event_rows = tree.xpath('//div[@class="container wcont"]//table[@class="table table-striped table-bordered"]//tr')[1:]
//             nevents = len(event_rows)

//             for ievent, event_row in enumerate(event_rows, 1):
//                 sys.stdout.write("\rOpenSimWorldFetcher: [{}/{}]        ".format(ievent, nevents))
//                 sys.stdout.flush()

//                 try:
//                     title = event_row.xpath('.//h4/b/a/text()')
//                     datetime_slt = event_row.xpath('.//i[contains(@class,"glyphicon-time")]/following-sibling::text()')[0].strip()
//                     url = event_row.xpath('.//td/b/a[starts-with(@href, "/hop/")]/@href')
//                     description = event_row.xpath('.//div/small/text()')[0]

//                     event = OpenSimWorldEvent(self.webcache, url[0] if url else None)
//                     event.title = title[0] if title else None
//                     event.description = description

//                     event.fetchHGUrl(event.url)

//                     if datetime_slt == "Upcoming Event":
//                         event.start = datetime.datetime.now(self.tz_pst).replace(minute=0, second=0, microsecond=0)
//                         event.end = event.start + datetime.timedelta(hours=2)
//                     else:
//                         datetime_parts = datetime_slt.split("|")
//                         if len(datetime_parts) >= 2:
//                             date_str = datetime_parts[1].strip().replace("SLT", "")
//                             if date_str:
//                                 event.start = date_parser.parse(date_str).replace(tzinfo=self.tz_slt)
//                                 if event.start is None:
//                                     raise ValueError("Failed to parse event start time.")
//                                 event.start = event.start.astimezone(self.tz_pst).replace(minute=0, second=0, microsecond=0)
//                                 event.end = event.start + datetime.timedelta(hours=2)
//                             else:
//                                 raise ValueError("Invalid event start time.")
//                         else:
//                             raise ValueError("Unknown string format: %s" % datetime_slt)

//                     self.eventlist.add(event)

//                     if limit > 0 and ievent >= limit:
//                         break

//                 except Exception as e:
//                     print("\nError fetching event:", e, event)

//         print("\n")

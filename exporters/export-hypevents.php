<?php
/**
 * HYPEvents Exporter
 * 
 * Export events to HYPEvents legacy format
 */
if( ! IS_AGGR ) {
    die('No direct calls, run main script aggregator.php instead.' . PHP_EOL);
}

class HYPEvents_Exporter {
    private $events = array();
    private $output_dir;

    public function __construct($events, $output_dir) {
        $this->events = $events;
        $this->output_dir = $output_dir;
        $this->export();
    }

    public function export() {
        $output = BOARD_VER . "\n";

        $prev_day = '';
        $today = date('l F j');

        foreach ($this->events as $event) {
            // copy templates/events.lsl to output_dir
            copy(APP_DIR . '/templates/events.lsl', $this->output_dir . '/events.lsl');

            $name = $event->name;
            // Transliterating name to ASCII
            // $name = preg_replace('/[\x{1F600}-\x{1F6FF}]/u', '', $event->name);
            $name = Aggregator::remove_emoji($name);
            $name = iconv('UTF-8', 'ASCII//TRANSLIT', utf8_encode($name));
            // Remove any remaining non-ASCII characters (icons, etc.)
            if( empty($name) ) {
                continue;
            }

            // calculate end datetime
            $end_stamp = strtotime($event->dateUTC) + $event->duration * 60;
            if ( $end_stamp < time() ) {
                continue;
            }

            $begin = new DateTime($event->dateUTC, new DateTimeZone('UTC'));
            $begin->setTimezone(new DateTimeZone('America/Los_Angeles'));
            
            $end = new DateTime();
            $end->setTimestamp($end_stamp);
            $end->setTimezone(new DateTimeZone('America/Los_Angeles'));

            $time_parts = array(
                $begin->format('h:iA'),
                $begin->format('Y-m-d'),
                $begin->getTimestamp(),
                $end->format('h:iA'),
                $end->format('Y-m-d'),
                $end->getTimestamp(),
            );

            $hgurl = $event->simname;
            
            $output .= "$name\n" . implode('~', $time_parts) . "\n$hgurl\n";
        }
        // echo "\n$output\n\n";

        $result = file_put_contents($this->output_dir . '/events.lsl2', $output);
        if( $result != false ) {
            Aggregator::admin_notice("saved " . $this->output_dir . '/events.lsl2');
        } else {
            Aggregator::admin_notice("Error writing " . $this->output_dir . '/events.lsl2', 1, true);
        }
    }
}

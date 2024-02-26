<?php
/**
 * HTML Exporter
 * 
 * Generate mobile-first responsive html page, css and js, with events taken dynamically from events.json
 */
if( ! IS_AGGR ) {
    die('No direct calls, run main script aggregator.php instead.' . PHP_EOL);
}

require_once APP_DIR . '/vendor/autoload.php';
use MatthiasMullie\Minify;

class HTML_Exporter {
    private $events = array();
    private $output_dir;

    public function __construct($events, $output_dir) {
        $this->events = $events;
        $this->output_dir = $output_dir;
        $this->export();
    }

    public function export() {
        // DEBUG copy original styles and js to output
        copy(APP_DIR . '/templates/styles.css', $this->output_dir . '/styles.css'); 
        copy(APP_DIR . '/templates/script.js', $this->output_dir . '/script.js');

        // Minify CSS
        $css = new Minify\CSS(APP_DIR . '/templates/styles.css');
        $css->minify(APP_DIR . '/templates/styles.min.css');

        // Minify JS
        $js = new Minify\JS(APP_DIR . '/templates/script.js');
        $js->minify(APP_DIR . '/templates/script.min.js');

        $result = copy(APP_DIR . '/templates/index.html', $this->output_dir . '/index.html');
        if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/index.html');
        else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/index.html', 1, true);

        // Copy minified files to output directory
        $result = copy(APP_DIR . '/templates/styles.min.css', $this->output_dir . '/styles.min.css');
        if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/styles.min.css');
        else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/styles.min.css', 1, true);

        $result = copy(APP_DIR . '/templates/script.min.js', $this->output_dir . '/script.min.js');
        if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/script.min.js');
        else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/index.html', 1, true);
    }
}

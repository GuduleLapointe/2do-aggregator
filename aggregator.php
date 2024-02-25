#!/usr/bin/env php
<?php
/**
 * 2do Aggregator main file
**/

// Make sure we are called from command line, not from a web server
// TODO: control procedures before allowing web access, to avoid
// - accidental exposure (config file path)
// - overload (maximum requests per day/hour/minute)
// - abuse
// ...
if (php_sapi_name() != 'cli') {
    die('This script can only be run from the command line.' . PHP_EOL);
}

/**
 * Aggregator class
 * 
 * Main class, set session parameters, load needed files and start processing
 */
class Aggregator {
    public $output_dir;
    private static $quiet;
    private static $verbose;
    public static $script;

    public function __construct() {
        global $argv;
        
        session_start();
        self::constants();
        self::includes();
        
        $this->load_args($argv);

        $this->run();
    }

    /**
     * Run
     * 
     * Run aggregator processes
     */
    public function run() {
        $fetcher = new Fetcher();
        // $fetcher->fetch();
        // $fetcher->export();
    }

    /**
     * Includes
     * 
     * Include needed files
     * 
     * @return void
     */
    private static function includes() { 
        // Composer dependencies
        require_once 'vendor/autoload.php';

        // OpenSimulator functions
        require_once 'includes/functions.php';
        // require_once 'vendor/magicoli/opensim-helpers/includes/opensim-helpers.php';
        
        // Classes
        require_once 'includes/class-fetcher.php';
        require_once 'includes/class-event.php';
    }

    /**
     * Define constants
     * 
     * Define constants for the application
     */
    private static function constants() {
        define('BASE_DIR', __DIR__);
        define('AGGREGATOR_VERSION', '0.0.1');
        define('IS_AGGR', true);

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
    }

    /**
     * Load arguments
     * 
     * Load arguments and set session parameters
     */
    private function load_args($args) {
        // $args are command line arguments, check for -q (quiet) -v (verbose) -o (output directory) etc) or -h (help) option, and removed them from the array. The remaining arguments are the output directories.
        self::$script = basename(array_shift($args));
        $left = $args;
        foreach($args as $arg) {
            switch($arg) {
                case '-q':
                    self::$quiet = true;
                    array_splice($left, array_search('$arg', $left), 1);
                    break;
                case '-v':
                    self::$verbose = true;
                    array_splice($left, array_search('$arg', $left), 1);
                    break;
                case '-h':
                case '--help':
                    echo "Usage: php " . self::$script . " [-q] [-v] [output_dir]\n";
                    echo "  -q  quiet mode\n";
                    echo "  -v  verbose mode (overriden if -q is set)\n";
                    echo "If output dir is not set a temporary directory will be created\n";
                    die();
                    break;
                case '--version':
                    echo "Aggregator version " . AGGREGATOR_VERSION . "\n";
                    die();
                    break;
            }
        }
        $args = $left;

        if( isset($args[0]) ) {
            // use output directory from command line
            $output_dir = $args[0];
        } else {
            // make temp directory for output
        
            $tempnam = tempnam(sys_get_temp_dir(), basename(self::$script) . '.');
            if ($tempnam === false) {
                Aggregator::admin_notice("Could not create temporary file", 1, true);
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
                        Aggregator::admin_notice("Deleting empty temp directory $tempnam");
                        rmdir($tempnam);
                    // } else {
                    //     Aggregator::admin_notice("temp directory $tempnam is not empty, not deleting " . print_r($files, true), 1);
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

        // Fail if output directory does not exist
        if( !is_dir($output_dir) ) {
            Aggregator::admin_notice("Output directory $output_dir does not exist", 1, true);
        }

        $this->output_dir = $output_dir;

    }

    public static function quiet() {
        return self::$quiet;
    }
    public static function verbose() {
        if (self::$quiet) {
            return false;
        }
        return self::$verbose;
    }

    function notice( $message ) {
        if ( Aggregator::quiet() ) {
            return;
        }
        echo $message . "\n";
    }
    
    function admin_notice( $message, $error_code = 0, $die = false) {
        if ( ! Aggregator::verbose() ) {
            return;
        }   
        // get calling function and file
        $trace = debug_backtrace();
        
        if(isset($trace[1])) {
            $caller = $trace[1];
        } else {
            $caller = $trace[0];
        }
        $file = empty($caller['file']) ? '' : $caller['file'];
        $function = $caller['function'] . "()" ?? 'main';
        $line = $caller['line'] ?? 0;
        $class = $caller['class'] ?? 'main';
        $type = $caller['type'] ?? '::';
        if($class != 'main') {
            $function = $class . $type . $function;
        }
        $file = $file . ':' . $line;
        $message = sprintf(
            '%s%s: %s in %s',
            $function,
            empty($error_code) ? '' : " Error $error_code",
            $message,
            $file,
        );
        error_log( $message );
        if($die == true) {
            die( $error_code );
        }
    }

    // public function __destruct() {
    //     error_log("Aggregator::__destruct");
    // }
}
        
new Aggregator();

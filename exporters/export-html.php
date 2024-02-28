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
        // copy(APP_DIR . '/templates/styles.css', $this->output_dir . '/styles.css'); 
        // copy(APP_DIR . '/templates/script.js', $this->output_dir . '/script.js');

        // Minify CSS
        $css = new Minify\CSS(APP_DIR . '/templates/styles.css');
        $css->minify(APP_DIR . '/templates/styles.min.css');

        // Minify JS
        $js = new Minify\JS(APP_DIR . '/templates/script.js');
        $js->minify(APP_DIR . '/templates/script.min.js');

        // Fill sections in index.html

        $Parsedown = new Parsedown();

        // Lire et convertir le contenu du README
        $text = file_get_contents( APP_DIR . '/README.md');
        $html = $Parsedown->text($text);

        // Charger le modèle de la page HTML
        $page = file_get_contents(APP_DIR . '/templates/index.html');

        // Remplacer le contenu de la section 'readme' par le contenu du README
        // $page = str_replace('<section id="readme"></section>', '<section id="readme">' . $html . '</section>', $page);
        
        $parsedown = array(
            'README.md' => 'About',
            'CHANGELOG.md' => 'Changelog',
            'FAQ.md' => 'FAQ',
        );
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML(mb_convert_encoding($page, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

        $section = $dom->getElementById('readme');
        if ($section) {
            // Créer un nouveau DOMDocument pour le contenu du README
            $domForContent = new DOMDocument();
            $domForContent->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

            // Obtenir tous les éléments de niveau supérieur du contenu du README
            $body = $domForContent->getElementsByTagName('body')->item(0);

            // Créer un nouvel élément div avec la classe wrapper
            $wrapper = $dom->createElement('div');
            $wrapper->setAttribute('class', 'wrapper');

            // Créer un nouvel élément div avec la classe content
            $content = $dom->createElement('div');
            $content->setAttribute('class', 'content');

            // Si body n'est pas null, importer chaque élément dans le DOM principal et l'ajouter au content
            if ($body !== null) {
                while ($body->hasChildNodes()) {
                    $child = $body->firstChild;
                    $importedNode = $dom->importNode($child, true);
                    $content->appendChild($importedNode);
                    $body->removeChild($child);
                }
            } else {
                // Si body est null, ajouter le contenu du README directement au content
                $fragment = $dom->createDocumentFragment();
                $fragment->appendXML($html);
                $content->appendChild($fragment);
            }

            // Ajouter le content au wrapper
            $wrapper->appendChild($content);

            // Ajouter le wrapper à la section
            $section->appendChild($wrapper);
        }

        $page = $dom->saveHTML();
        
        // Ajouter la classe list-check aux éléments li qui contiennent [x] et remplacer [x] par une case à cocher cochée
        $page = preg_replace('/<li>\s*\[x\]/', '<li class="list-check"><input type="checkbox" checked disabled>', $page);

        // Ajouter la classe list-check aux éléments li qui contiennent [ ] et remplacer [ ] par une case à cocher non cochée
        $page = preg_replace('/<li>\s*\[\s\]/', '<li class="list-check"><input type="checkbox" disabled>', $page);

        $file = 'index.html';
        // Sauvegarder la page dans le répertoire de sortie
        $result = file_put_contents($this->output_dir . '/' . $file, $page);
        if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/' . $file);
        else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/' . $file, 1, true);

        // following is a repeat of the same code, use a for loop from an array with the file names
        $files = array(
            // 'index.html',
            'styles.min.css',
            'script.min.js',
            '2do-logo-trim.png',
        );
        foreach($files as $file) {
            $result = copy(APP_DIR . '/templates/' . $file, $this->output_dir . '/' . $file);
            if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/' . $file);
            else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/' . $file, 1, true);
        }
        // $result = copy(APP_DIR . '/templates/index.html', $this->output_dir . '/index.html');
        // if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/index.html');
        // else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/index.html', 1, true);

        // // Copy minified files to output directory
        // $result = copy(APP_DIR . '/templates/styles.min.css', $this->output_dir . '/styles.min.css');
        // if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/styles.min.css');
        // else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/styles.min.css', 1, true);

        // $result = copy(APP_DIR . '/templates/script.min.js', $this->output_dir . '/script.min.js');
        // if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/script.min.js');
        // else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/script.min.js', 1, true);

        // $result = copy(APP_DIR . '/templates/2do-logo-trim.png', $this->output_dir . '/2do-logo-trim.png');
        // if($result !== false) Aggregator::notice("updated " . $this->output_dir . '/2do-logo-trim.png');
        // else Aggregator::admin_notice("Error $result writing " . $this->output_dir . '/2do-logo-trim.png', 1, true);
    }
}

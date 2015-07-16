<?php

/**
 * (c) 2015 skinod.com | author Sijad aka Mr.Wosi
 */

namespace IPS;

error_reporting(E_ALL);

/* Check this is running at the command line */
if (\php_sapi_name() !== 'cli') {
    echo 'Not at command line' . \PHP_EOL;
    exit;
}
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once 'init.php';
if (!IN_DEV) {
    echo 'IN_DEV must be enabled to use this tool' . \PHP_EOL;
    exit;
}
new DevMaker;

class DevMaker {

    protected $stdin;

    /*
     * class constructor
     */

    public function __construct() {
        $this->stdin = \fopen('php://stdin', 'r');
        $this->_print('--------------------------------------------');
        $this->_print('Welcome to the IPS4 Rebuild App Dev Tool');
        $this->_print('--------------------------------------------');
        $this->run();
        exit;
    }

    /*
     * main menu
     */

    protected function run() {
        $this->writeDevFolder($this->getApp());
        $this->run();
    }

    /*
     * app selection
     */

    protected function getApp() {
        $apps = $this->applications();
        foreach ($apps as $app) {
            $this->_print('[' . $app . '] ' . $app);
        }
        $app = $this->fetchOption();
        if (!in_array($app, $apps)) {
            $this->_print('Invalid Selection!');
            $app = $this->getApp();
        }
        return $app;
    }

    /*
     * write dev folder.
     */

    protected function writeDevFolder($app) {
        $path = ROOT_PATH . '/applications/' . $app;
        if (\is_dir($path . '/dev')) {
            $this->_print('Error: Dev folder already exists!');
            return;
        }
        $this->_print('Writing dev folder for ' . $app . '!');
        $this->writeThemeFolders($path);
        $this->writeLangs($path);
        $this->writeJS($path);
        return;
    }

    /*
     * write css & html & resources
     */

    protected function writeThemeFolders($path) {
        if (!\is_file($path . '/data/theme.xml')) {
            $this->_print('Error: Can\'t find theme.xml!');
            return;
        }
        if (!\is_readable($path . '/data/theme.xml')) {
            $this->_print('Error: Can\'t read theme.xml!');
            return;
        }
        /* Open XML file */
        $xml = new \XMLReader;
        $xml->open($path . '/data/theme.xml');
        $xml->read();

        while ($xml->read()) {
            if ($xml->nodeType != \XMLReader::ELEMENT) {
                continue;
            }

            if ($xml->name == 'template') {
                $template = array(
                    'group' => $xml->getAttribute('template_group'),
                    'name' => $xml->getAttribute('template_name'),
                    'variables' => $xml->getAttribute('template_data'),
                    'content' => $xml->readString(),
                    'location' => $xml->getAttribute('template_location'),
                );

                if (!\is_dir($path . '/dev/html/' . $template['location'] . '/' . $template['group'])) {
                    \mkdir($path . '/dev/html/' . $template['location'] . '/' . $template['group'], IPS_FOLDER_PERMISSION, TRUE);
                }

                $file = $path . '/dev/html/' . $template['location'] . '/' . $template['group'] . '/' . $template['name'] . '.phtml';

                $data = <<<HTML
	<ips:template parameters="{$template['variables']}" />
	{$template['content']}
HTML;
                \file_put_contents($file, $data);
            } else if ($xml->name == 'css') {
                $css = array(
                    'location' => $xml->getAttribute('css_location'),
                    'name' => $xml->getAttribute('css_name'),
                    'content' => $xml->readString(),
                );

                if (!\is_dir($path . '/dev/css/' . $css['location'])) {
                    \mkdir($path . '/dev/css/' . $css['location'], IPS_FOLDER_PERMISSION, TRUE);
                }

                $file = $path . '/dev/css/' . $css['location'] . '/' . $css['name'];

                \file_put_contents($file, $css['content']);
            } else if ($xml->name == 'resource') {
                $resource = array(
                    'location' => $xml->getAttribute('location'),
                    'path' => $xml->getAttribute('path'),
                    'name' => $xml->getAttribute('name'),
                    'content' => \base64_decode($xml->readString()),
                );

                if (!\is_dir($path . '/dev/resources/' . $resource['location'])) {
                    \mkdir($path . '/dev/resources/' . $resource['location'], IPS_FOLDER_PERMISSION, TRUE);
                }

                $file = $path . '/dev/resources/' . $resource['location'] . '/' . $resource['name'];

                \file_put_contents($file, $resource['content']);
            }
        }
    }

    /*
     *  write lang & js lang
     */

    protected function writeLangs($path) {
        if (!\is_file($path . '/data/lang.xml')) {
            $this->_print('Error: Can\'t find lang.xml!');
            return;
        }
        if (!\is_readable($path . '/data/lang.xml')) {
            $this->_print('Error: Can\'t read lang.xml!');
            return;
        }
        /* Open XML file */
        $xml = new \XMLReader;
        $xml->open($path . '/data/lang.xml');
        $xml->read();
        $xml->read();
        $xml->read();

        $langs = array();
        $js_langs = array();

        while ($xml->read()) {
            if ($xml->nodeType != \XMLReader::ELEMENT) {
                continue;
            }
            if ((int) $xml->getAttribute('js') === 1) {
                $js_langs[$xml->getAttribute('key')] = $xml->readString();
            } else {
                $langs[$xml->getAttribute('key')] = $xml->readString();
            }
        }

        if (\count($langs)) {
            $file = $path . '/dev/lang.php';
            $langs = \var_export($langs, true);
            $data = <<<PHP
<?php
	\$lang = $langs;
PHP;
            \file_put_contents($file, trim($data));
        }

        if (\count($js_langs)) {
            $file = $path . '/dev/jslang.php';
            $js_langs = \var_export($js_langs, true);
            $data = <<<PHP
<?php
	\$lang = $js_langs;
PHP;
            \file_put_contents($file, trim($data));
        }
    }

    /*
     * write JS
     */

    protected function writeJS($path) {
        if (!\is_file($path . '/data/javascript.xml')) {
            $this->_print('Error: Can\'t find javascript.xml!');
            return;
        }
        if (!\is_readable($path . '/data/javascript.xml')) {
            $this->_print('Error: Can\'t read javascript.xml!');
            return;
        }
        $xml = new \XMLReader;
        $xml->open($path . "/data/javascript.xml");
        $xml->read();

        while ($xml->read()) {
            if ($xml->nodeType != \XMLReader::ELEMENT) {
                continue;
            }

            $file = $path . '/dev/js/' . $xml->getAttribute('javascript_location') . '/' . $xml->getAttribute('javascript_path') . '/';
            if (!is_dir($file)) {
                \mkdir($file, IPS_FOLDER_PERMISSION, TRUE);
            }
            \file_put_contents($file . $xml->getAttribute('javascript_name'), $xml->readString());
        }
    }

    /**
     * Out to stdout
     */
    protected function _print($message, $newline = \PHP_EOL) {
        $stdout = \fopen('php://stdout', 'w');
        \fwrite($stdout, $message . $newline);
        \fclose($stdout);
    }

    /* Fetch option
     *
     */

    protected function _fetchOption() {
        return \trim(\fgets($this->stdin));
    }

    /* Fetch option wrapper
     *
     */

    protected function fetchOption() {
        $opt = $this->_fetchOption();
        if ($opt === 'x') {
            print 'Goodbye!';
            exit;
        }
        return $opt;
    }

    protected function applications() {
        $apps = array();
        foreach( new \DirectoryIterator( ROOT_PATH . '/applications/' ) as $app )
        {
            if ( $app->isDot() || mb_substr( $app->getFilename(), 0, 1 ) === '.' || $app->getFilename() == 'index.html' )
            {
                continue;
            }
            $apps[] = (string) $app;
        }

        return $apps;
    }

}

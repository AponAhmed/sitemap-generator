<?php

/**
 * @package SitemapGenerator
 * @version 1
 */
/*
  Plugin Name: Sitemap  Genetrator
  Plugin URI: http://siatex.com/plugins/seo-search/
  Description: Generate XML Sitemap with Page, Post and custom Post also MPG
  Author: Apon
  Version: 2.7.8
  Author URI:http://siatex.com/
 */

namespace sitemapGenerator;

use sitemapGenerator\lib\Admin;

define('__SG_ROOT', __FILE__);
define('SITEMAP_GENERATOR_ROOT_DIR', dirname(__SG_ROOT)); //Plugin ABSPATH
//Auto Load
foreach (glob(SITEMAP_GENERATOR_ROOT_DIR . "/lib/*.php") as $filename) {
    require_once $filename;
} //End AutoLoad;

/**
 * Description of sitemap-generator
 *
 * @author apon
 */
class SitemapGenerator
{

    public $admin;

    public function __construct()
    {
        add_filter('wp_sitemaps_enabled', '__return_false');
        if (is_admin()) {
            $this->adminInit();
        } else {
            $this->init();
        }
    }

    /**
     * Admin Section Initialize
     */
    private function adminInit()
    {
        $this->admin = new Admin();
    }

    // public static function allLinks()
    // {
    //     $links = Admin::allLinks();
    //     // Combine Multipost here
    //     //allLinks
    //     if (class_exists('\Aponahmed\StaticPageGenerator\AdminController')) {
    //         $links = \Aponahmed\StaticPageGenerator\AdminController::allLinks();
    //     }
    //     return $links;
    // }

    public static function getRandomLines($filename, $numLines = 100)
    {
        // Check if the file exists
        if (!file_exists($filename)) {
            return [];
        }

        // Get total number of lines in the file
        $totalLines = 0;
        $file = fopen($filename, 'r');
        if ($file) {
            while (fgets($file) !== false) {
                $totalLines++;
            }
            fclose($file);
        }

        // If the file has fewer lines than requested, adjust the number
        $numLines = min($numLines, $totalLines);

        // Generate unique random line numbers
        $lineNumbers = array_rand(range(0, $totalLines - 1), $numLines);
        if (!is_array($lineNumbers)) {
            $lineNumbers = [$lineNumbers];
        }
        sort($lineNumbers); // Sort for sequential reading

        // Extract the required lines
        $lines = [];
        $file = fopen($filename, 'r');
        if ($file) {
            $currentLine = 0;
            foreach ($lineNumbers as $lineNumber) {
                while ($currentLine < $lineNumber && fgets($file) !== false) {
                    $currentLine++;
                }
                if ($currentLine == $lineNumber) {
                    $line = fgets($file);
                    // Decode URL-encoded sequences
                    $line = urldecode($line);
                    // Remove trailing newlines and extra spaces
                    $line = rtrim($line, "\r\n");
                    $lines[] = $line;
                }
            }
            fclose($file);
        }

        return $lines;
    }


    public static function allLinks()
    {
        // Define the path to the cached file
        $cache_dir = ABSPATH . 'wp-content/cache/';
        $cache_file = $cache_dir . 'all_links.txt';
        $cache_time = 3600 * 24; // Cache for 1 day (3600 seconds)

        // Check if the cache directory exists, if not, create it
        if (!file_exists($cache_dir)) {
            mkdir($cache_dir, 0755, true); // 0755 is the permission mode
        }

        // Check if the cache file exists and is still valid
        if (file_exists($cache_file) && (time() - filemtime($cache_file)) < $cache_time) {
            // Read the links from the cache file
            $links = self::getRandomLines($cache_file, 100);
            //$links = file($cache_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        } else {
            // Fetch the links
            $links = Admin::allLinks();

            // Combine additional links if the class exists
            if (class_exists('\Aponahmed\StaticPageGenerator\AdminController')) {
                $additionalLinks = \Aponahmed\StaticPageGenerator\AdminController::allLinks();
                // Check if both $links and $additionalLinks are arrays
                if (is_array($links) && is_array($additionalLinks)) {
                    $links = array_merge($links, $additionalLinks);
                }
            }

            // Save the links to the cache file
            file_put_contents($cache_file, implode(PHP_EOL, $links));
        }

        return $links;
    }




    /**
     * Front-end Initialize
     */
    private function init() {}
}

$sitemapGenerator = new SitemapGenerator();

//echo "<pre>";
//var_dump($sitemapGenerator);

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
  Version: 2.3
  Author URI:http://siatex.com/
 */

namespace sitemapGenerator;

use sitemapGenerator\lib\Admin;

define('__SG_ROOT', __FILE__);
define('SITEMAP_GENERATOR_ROOT_DIR', dirname(__SG_ROOT)); //Plugin ABSPATH
//Auto Load
foreach (glob(SITEMAP_GENERATOR_ROOT_DIR . "/lib/*.php") as $filename) {
    require_once $filename;
}//End AutoLoad;

/**
 * Description of sitemap-generator
 *
 * @author apon
 */
class SitemapGenerator {

    public function __construct() {
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
    private function adminInit() {
        $this->admin = new Admin();
    }

    /**
     * Front-end Initialize
     */
    private function init() {
        
    }

}

$sitemapGenerator = new SitemapGenerator();

//echo "<pre>";
//var_dump($sitemapGenerator);


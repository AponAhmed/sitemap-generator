<?php

/**
 * @package SitemapGenerator
 * @version 1
 */

namespace sitemapGenerator\lib;

use sitemapGenerator\lib\AdminView;
use sitemapGenerator\lib\Generator;
use MPG_Constant;

/**
 * Description of Admin
 *
 * @author apon
 */
class Admin {

    public $options;
    public $adminView;

    //put your code here
    public function __construct() {
        $this->get_option();
        $this->adminHookReg();
    }

    /**
     * Admin Hooks Register
     */
    private function adminHookReg() {
        //Admin menu
        add_action("admin_menu", [$this, "sitemapGeneratorAdminMenu"]);

        //Admin Scripts
        add_action('admin_enqueue_scripts', array($this, 'admin_assets'));
        //Ajax Hooks
        add_action('wp_ajax_update_sitemap_option', [$this, 'update_sitemap_option']);
        add_action('wp_ajax_updateFileName', [$this, 'updateFileName']);
        add_action('wp_ajax_GenerateSitemap', [$this, 'GenerateSitemap']);
        add_action('wp_ajax_update_mpg_sitemap_option', [$this, 'update_mpg_sitemap_option']);
        add_action('wp_ajax_mpgSitemapGenerateSingle', [$this, 'mpgSitemapGenerateSingle']);
    }

    /**
     * Admin Script Register
     */
    function admin_assets() {
        wp_register_style('sitemapGenerator-css', plugin_dir_url(__SG_ROOT) . '/assets/admin-style.css', false, '1.0.0');
        wp_enqueue_style('sitemapGenerator-css');
        wp_enqueue_script('sitemapGenerator-scripts', plugin_dir_url(__SG_ROOT) . '/assets/admin-scripts.js', array('jquery'), '1.0');
        wp_localize_script('sitemapGenerator-scripts', 'ajax_object_sitemap', array('ajax_url' => admin_url('admin-ajax.php')));
    }

    /**
     * admin Menu Init
     */
    public function sitemapGeneratorAdminMenu() {
        add_submenu_page(
                "tools.php", //$parent_slug
                "Sitemap Generator", //$page_title
                "Sitemap Generator", //$menu_title
                "manage_options", //$capability
                "sitemap-generator", //$menu_slug
                [$this, 'sitemapGeneratorControl'] //Calback
        );
    }

    /**
     * Sitemap Generator Control In Admin
     */
    public function sitemapGeneratorControl() {
        $this->adminView = new AdminView($this->options);
        $this->adminView->renderView();
    }

    /**
     * Get Options 
     */
    function get_option() {
        $defaultOption = [
            'sitemap_max_links' => 1000,
            'post_types' => ['page'],
            'taxonomies' => []
        ];
        $otherField = get_option('sitemap_options');
        if ($otherField != "") {
            $this->options = json_decode($otherField, true);
        } else {
            $this->options = $defaultOption;
        }
        $postTypes = get_option('sitemap_post_types');
        if ($postTypes != "") {
            $this->options['post_types'] = json_decode($postTypes, true);
        }
        $taxonomies = get_option('sitemap_taxonomies');
        if ($taxonomies != "") {
            $this->options['taxonomies'] = json_decode($taxonomies, true);
        }
    }

    /**
     * Update Sitemap Option By Ajax
     */
    function update_sitemap_option() {
        $data = array();
        parse_str($_POST['data'], $data);
        //echo "<pre>";
        //var_dump($data);
        //exit;
        $c = count($data);
        if ($c > 0) {
            foreach ($data as $k => $val) {
                if (is_array($val)) {
                    $val = json_encode($val);
                }
                if (update_option($k, $val)) {
                    $c--;
                }
            }
        }
        echo 1;
        wp_die();
    }

    /**
     * Update File name for sitemap with post type
     */
    function updateFileName() {
        if (isset($_POST['Filedata'])) {
            if (!empty($_POST['Filedata']['Fname'])) {
                update_option($_POST['Filedata']['Fname'], strtolower(str_replace(array(" ", "_", ","), "-", $_POST['Filedata']['val'])));
            }
        }
        echo 1;
        wp_die();
    }

    /**
     * Generate Sitemap AJAX callback
     */
    function GenerateSitemap() {
        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        //echo "<pre>";

        $generator = new Generator($this->options);
        $generator->run();
        wp_die();
    }

    /**
     * 
     */
    function mpgSitemapGenerateSingle() {
        //var_dump($_POST);
        if (isset($_POST['projecID'])) {
            $id = $_POST['projecID'];
            $generator = new Generator($this->options);
            $generator->MPGenerate($id);
        }

        wp_die();
    }

    function update_mpg_sitemap_option() {
        global $wpdb;

        $data = [];
        $pID = $_POST['projecID'];
        parse_str($_POST['data'], $data);

        $upData = [
            'sitemap_filename' => $data['sitemap_filename_input'],
            'sitemap_max_url' => $data['sitemap_max_urls_input'],
            'sitemap_update_frequency' => $data['sitemap_frequency_input'],
            'sitemap_add_to_robots' => isset($data['sitemap_robot']) ? 1 : 0,
        ];
        $table_name = $wpdb->prefix . MPG_Constant::MPG_PROJECTS_TABLE;

        $res = $wpdb->update($table_name, $upData, array('id' => $pID));

        echo $res;
        wp_die();
    }

}

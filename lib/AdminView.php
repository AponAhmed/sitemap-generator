<?php

/**
 * @package SitemapGenerator
 * @version 1
 */

namespace sitemapGenerator\lib;

/**
 * Description of AdminView
 *
 * @author apon
 */
class AdminView
{

    //put your code here
    public $viewTitle;
    public $Freq;
    public $maxDpth;
    public $publicPostTypes;
    public $options;

    public function __construct($options)
    {
        $this->options = $options;
        $this->viewTitle = get_admin_page_title();

        //        $arg = ['public' => true, '_builtin' => true];
        //        $CPInfos = get_post_types($arg, 'objects');
        //        unset($CPInfos['attachment']); //Remove Attachment
        //
        //        $this->publicPostTypes = $CPInfos;

        $this->Freq = array(
            "always" => "Always",
            "hourly" => "Hourly",
            "daily" => "Daily",
            "weekly" => "Weekly",
            "monthly" => "Monthly",
            "yearly" => "Yearly",
            "never" => "Never"
        );
        $this->maxDpth = 4;
    }

    function PageTitle()
    {
        $disable = '';
        if (!file_exists(ABSPATH . "/sitemap.xml")) {
            $disable = " disabled ";
        }
        $sitemapFilename = site_url() . "/sitemap.xml";
        $sitemapLink = "<a class='button viewSitemap' $disable href='$sitemapFilename' target='_new'>View Sitemap</a>";

        echo "<h2 class='wp-heading-inline'>$this->viewTitle &nbsp;&nbsp; <a href=\"javascript:void(0)\" onclick=\"GenerateSitemap(this)\" class=\"button\">Generate All</a>&nbsp;&nbsp;$sitemapLink</h2><hr><br>";
    }

    function getOption($key = "")
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        } else {
            return "";
        }
    }

    function optionsControl()
    {
?>
        <form id="sitemapGeneratorForm">
            <div class="customPost-wrap">
                <input type="hidden" name="sitemap_post_types" value="">
                <input type="hidden" name="sitemap_taxonomies" value="">
                <?php
                foreach ($this->publicPostTypes as $type => $info) {
                    $taxonomies = get_object_taxonomies($type);
                    //var_dump($info);
                ?>
                    <div class="post-type">
                        <div class="post-type-title">
                            <label>
                                <input name="sitemap_post_types[]" data-modified="<?php echo get_option('sitemap_file_' . $type) ?>" class="checkField" type="checkbox" <?php echo isset($this->options['post_types']) && in_array($type, $this->options['post_types']) ? "checked" : "" ?> value="<?php echo $type ?>"><?php echo $info->label ?>
                            </label>
                            <span class="editSitemapFileName dashicons dashicons-edit"></span>
                            <span class="toggler"></span>
                        </div>
                        <div class="post-type-body">
                            <div class="taxBlock">
                                <?php
                                foreach ($taxonomies as $key => $value) {
                                    $checked = isset($this->options['taxonomies']) && in_array($value, $this->options['taxonomies']) ? "checked" : "";
                                    $att = "data-modified=\"" . get_option('sitemap_file_' . $value) . "\"";
                                    echo "<div class='taxItem '><label><input $att class=\"checkField\" $checked name=\"sitemap_taxonomies[]\" value='$value' type='checkbox'>&nbsp;&nbsp;$value </label><span class=\"editSitemapFileName dashicons dashicons-edit\"></span></div>";
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                <?php
                }
                ?>
            </div>
            <div class="sitemap-generate-wrap">
                <div class="option-wrap">
                    <label>Directory :</label>
                    <div class="input-wrap">
                        <input type="text" value="<?php echo $this->getOption('sitemap_dir_name') ?>" placeholder="root" name="sitemap_options[sitemap_dir_name]">
                        <p class="description">Directory name,if empty then XML and html files will store in Root directory</p>
                    </div>
                </div>
            </div>
            <div class="sitemap-generate-wrap">
                <div class="option-wrap">
                    <label>Max:</label>
                    <div class="input-wrap">
                        <input type="text" value="<?php echo $this->getOption('sitemap_max_links') ?>" name="sitemap_options[sitemap_max_links]">
                        <p class="description">Maximum number of links in single Xml document</p>
                    </div>
                </div>
            </div>
            <div class="sitemap-generate-wrap">
                <div class="option-wrap">
                    <label>Last Modified:</label>
                    <div class="input-wrap">
                        <input type="date" value="<?php echo $this->getOption('sitemap_last_modified') ?>" name="sitemap_options[sitemap_last_modified]">
                        <p class="description"></p>
                    </div>
                </div>
            </div>
            <hr>
            <label><strong>Change Frequencies & Priority Settings</strong></label>
            <div class="fp-row">
                <div class="freq-wrap">
                    <div class="post-type">
                        <div class="post-type-title">
                            <label>
                                <input type="hidden" value="" name="sitemap_options[enable_change_freq]">
                                <input <?php echo isset($this->options['enable_change_freq']) && $this->options['enable_change_freq'] == "1" ? "checked" : "" ?> name="sitemap_options[enable_change_freq]" value="1" type="checkbox">
                                Change Frequencies
                            </label>
                            <span class="toggler">
                            </span>
                        </div>
                        <div class="post-type-body">
                            <div class="taxBlock">
                                <?php
                                for ($i = 0; $i < $this->maxDpth; $i++) {
                                ?>
                                    <div class="freq-item">
                                        <select name="sitemap_options[sitemapFreqDpth][<?php echo "dpth$i" ?>]" class="custom-select-sm custom-select">
                                            <?php
                                            foreach ($this->Freq as $val => $label) {
                                                $sel = $this->options['sitemapFreqDpth']["dpth$i"] == $val ? "selected" : "";
                                                echo "<option $sel value='$val'>$label</option>";
                                            }
                                            ?>
                                        </select>
                                        <label>&nbsp;Depth:&nbsp;<?php echo $i ?></label>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="prio-wrap">
                    <div class="post-type">
                        <div class="post-type-title">
                            <label>
                                <input type="hidden" value="" name="sitemap_options[enable_priority]">
                                <input <?php echo isset($this->options['enable_priority']) && $this->options['enable_priority'] == "1" ? "checked" : "" ?> name="sitemap_options[enable_priority]" value="1" type="checkbox">
                                Priority
                            </label>
                            <span class="toggler">
                            </span>
                        </div>
                        <div class="post-type-body">
                            <div class="taxBlock">
                                <?php
                                for ($j = 0; $j < $this->maxDpth; $j++) {
                                ?>
                                    <div class="freq-item">
                                        <select name="sitemap_options[sitemapPriorityDpth][<?php echo "dpth$j" ?>]" class="custom-select-sm custom-select w80">
                                            <?php
                                            for ($i = 1.0; $i > 0.0; $i -= 0.1) {
                                                $val = number_format($i, 1);
                                                $sel = $this->options['sitemapPriorityDpth']["dpth$j"] == $val ? "selected" : "";
                                                echo "<option $sel value='$val'>$val</option>";
                                            }
                                            ?>
                                        </select>
                                        <label>Depth:<?php echo $j ?></label>
                                    </div>
                                <?php
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <hr>
            <button class="button button-primary" type="button" onclick="updateSitemapOption(this)">Update</button>
        </form>
<?php
        //echo "<pre>";
        //var_dump($this->options);
        //echo "</pre>";
    }

    public function renderView()
    {
        global $wp_post_types;
        //echo "<pre>";
        $publicPostTyps = array_filter($wp_post_types, function ($v, $k) {
            if ($v->public === true) //&& $k != 'attachment')
                return $v;
        }, ARRAY_FILTER_USE_BOTH);

        $this->publicPostTypes = $publicPostTyps;

        $blogType = new \stdClass();
        $blogType->label = 'Blog';
        $this->publicPostTypes['blog'] = $blogType;

        echo "<div class=\"wrap\">";
        $this->PageTitle();
        $this->optionsControl();
        echo "</div>";
    }
}

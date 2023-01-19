<?php

namespace sitemapGenerator\lib;

use MPG_Constant;

/**
 * Description of Generator
 *
 * @author apon
 */
class Generator {

    /**
     * Current Progress is Post or Taxonomy
     * @var type
     */
    public $type;

    /**
     * Post Type if post in $type
     * @var postType
     */
    public $postType;

    /**
     * Taxonomy Name if taxo in $type
     * @var taxType
     */
    public $taxType;

    /**
     * Directory Name, Where store XML Files
     * @var dirName
     */
    public string $dirName;

    /**
     * File Name of Current Instance 
     * @var type
     */
    public string $fileName;
    public array $options;
    public array $tempData;
    public array $generatedFiles;
    public int $fileCount;

    /**
     * @var int Home Page ID
     */
    public int $homeID;

    /**
     * @var string Site URL
     */
    public string $siteUrl;

    /**
     * @param string freqChange
     */
    public $freqChange;
    public array $dataMap;
    public array $HtmlSidebar;
    public int $n;
    public $tempLinks;

    /**
     * Type Basis Data map
     * @param type $options
     */
    public array $typeBasisData;
    public string $currentType;

    public function __construct($options) {
        $this->options = $options;
        $this->homeID = get_option('page_on_front');
        $this->siteUrl = get_site_url();
        $this->n = 0;
    }

    /**
     * Setup Type information and Reset counter of file
     * @param string $type
     * @param string $typeName
     */
    public function setTypes($type = "post", $typeName = "post") {
        //Assign Post or Taxonomy Information
        //File Counter reset 
        $this->fileCount = 0;
        $this->type = $type;
        if ($this->type == 'post') {
            $this->postType = $typeName;
        } else {
            $this->taxType = $typeName;
        }
    }

    /**
     * File name and folder handler
     */
    function setDirFileName() {
        //sitemap_file_page
        //sitemap_file_test_taxonomy
        $fileName = "demo-name";
        if ($this->type == "post") {
            $fileName = $this->postType;
            $ModifiedfileName = trim(get_option("sitemap_file_" . $this->postType));
            if (!empty($ModifiedfileName)) {
                $fileName = $ModifiedfileName;
            }
        } else {
            $fileName = $this->taxType;
            $ModifiedfileName = trim(get_option("sitemap_file_" . $this->taxType));
            if (!empty($ModifiedfileName)) {
                $fileName = $ModifiedfileName;
            }
        }
        $this->fileName = $fileName;
        //Directory Name
        if (!empty($this->options['sitemap_dir_name'])) {
            $this->dirName = trim($this->options['sitemap_dir_name']);
        } else {
            $this->dirName = "sitemaps";
        }
        //Making of Folder where store xml files
        if (!is_dir(ABSPATH . $this->dirName)) {
            mkdir(ABSPATH . $this->dirName, 0777, true);
            chmod(ABSPATH . $this->dirName, 0777);
            file_put_contents(ABSPATH . $this->dirName . "/index.php", "<?php //Silence is golden");
        }
        //$this->dirName = ABSPATH . $this->dirName;
    }

    /**
     * Main Entry Point before Generate
     */
    public function run() {
        //echo "<pre>";
        //var_dump($this->options);
        //return;
        //Loop Initialize for Post Typ
        if (count($this->options['post_types']) > 0) {
            foreach ($this->options['post_types'] as $postType) {
                $this->typeBasisData = [];
                $this->setTypes('post', $postType);
                $this->distributePost();
                $this->TypeBasisStore();
            }
        }

        //Loop Initialize for Taxonomies
        if (isset($this->options['taxonomies']) && count($this->options['taxonomies']) > 0) {
            foreach ($this->options['taxonomies'] as $taxo) {
                $this->typeBasisData = [];
                $this->setTypes('taxo', $taxo);
                $this->distributeTaxo();
                $this->TypeBasisStore();
            }
        }
        //var_dump($this);
        //MPG Section----
        if (class_exists('MPG_Constant')) {
            $this->MPGenerate();
        }

        //Final Sitemap or Main sitemap
        $this->fileName = "sitemap";

        $this->tempData = $this->generatedFiles;
        $this->generateXml(true);

        //HTML Part
        $this->handleHtmlGenerate();
        $this->allinOneGenerate();
    }

    /**
     * MPG Multi page Generate Sitemap
     */
    function MPGenerate($id = false) {
        global $wpdb;
        $singleQry = "";
        if ($id) {
            $singleQry = "and id=$id";
        }
        $this->type = 'post';
        $this->postType = 'repeatable';
        $this->typeBasisData = [];
        $projects = $wpdb->get_results("SELECT name,headers,source_type,source_path,original_file_url,template_id,urls_array,sitemap_filename,sitemap_max_url,sitemap_update_frequency From {$wpdb->prefix}" . MPG_Constant::MPG_PROJECTS_TABLE . " where $singleQry exclude_in_robots !=0");
        foreach ($projects as $project) {
            $pageTitle = get_the_title($project->template_id);

            $modDate = get_the_date('', $project->template_id);
            //set File Name

            $this->fileName = strtolower($project->sitemap_filename);
            if ($this->fileName == "") {
                $this->fileName = strtolower(str_replace([" ", "_"], "-", $project->name));
            }
            $this->fileCount = 0;
            if ($project->sitemap_update_frequency) {
                $this->freqChange = $project->sitemap_update_frequency;
            }

            //set Limit from Project
            $fileLimit = $project->sitemap_max_url;
            $linksArray = json_decode($project->urls_array);
            $totalLinks = is_array($linksArray) ? count($linksArray) : 0;
            if ($totalLinks > 0) {
                $n = 0;
                foreach ($linksArray as $linkSuffex) {
                    $n++;
                    //$pageTitle = $this->shortcodeFilter($project, $pageTitle, $linkSuffex);//Commented For Execution Time Issue

                    $link = $this->trimSlash($this->siteUrl . "/" . $linkSuffex);
                    if (strpos($link, '.html') !== false) {
                        $link = trim($link, "/");
                    }
                    $this->typeBasisData[] = $link;

                    $this->tempData[] = array($link, $pageTitle, $modDate);

                    $this->tempLinks[] = $link; //All Links
                    if (count($this->tempData) == $fileLimit || $n == $totalLinks) {
                        $file = $this->storeXml(false);
                        if ($file) {
                            $this->generatedFiles[] = array($file);
                            $this->fileCount++;
                        }
                    }
                }
            }
        }
        $this->TypeBasisStore();
    }

    /**
     * Distribute Posts for each sitemap file and file number handle
     */
    public function distributePost() {
        global $wpdb;
        $this->setDirFileName();

        $skipPost = [$this->homeID];
        //Skip MPG POST
        if (class_exists('MPG_Constant')) {
            $projects = $wpdb->get_results("SELECT template_id From {$wpdb->prefix}" . MPG_Constant::MPG_PROJECTS_TABLE . " where exclude_in_robots !=0");
            foreach ($projects as $p) {
                $skipPost[] = $p->template_id;
            }
        }

        if ($this->postType == 'attachment') {
            $MediaCats = get_option('media_category_in_search');
            $arg = array(
                'post_type' => $this->postType,
                'post_status' => 'inherit',
                'posts_per_page' => -1,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'media-category',
                        'field' => 'term_id',
                        'terms' => $MediaCats,
                    )
                ),
            );
        } elseif ($this->postType == 'blog') {
            $ids = get_option('blog_post_category');
            if (!empty($ids)) {
                $ids = explode(",", $ids);
            }
            if (is_array($ids)) {
                $ids = array_unique(array_filter($ids, 'trim'));
            }
            $lmt = -1;
            if (count($ids) == 0) {
                $lmt = 0;
            }
            $arg = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => $lmt,
                'tax_query' => array(
                    array(
                        'taxonomy' => 'category',
                        'field' => 'term_id',
                        'terms' => $ids,
                    )
                ),
            );
        } else {
            if ($this->postType == 'post') {
                $ids = get_option('blog_post_category');
                if (!empty($ids)) {
                    $ids = explode(",", $ids);
                }
                if (is_array($ids)) {
                    $ids = array_unique(array_filter($ids, 'trim'));
                }
                if (count($ids) > 0) {
                    $argBlog = array(
                        'post_type' => 'post',
                        'post_status' => 'publish',
                        'posts_per_page' => -1,
                        'fields' => 'ids',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'category',
                                'field' => 'term_id',
                                'terms' => $ids,
                            )
                        ),
                    );
                    $wpQBlog = new \WP_Query($argBlog);
                    $skipPost = array_merge($wpQBlog->posts, $skipPost);
                }
            }

            $arg = array(
                'post_type' => $this->postType,
                'post_status' => 'publish',
                'posts_per_page' => -1,
                'fields' => array('post_title', 'comment_status'),
                'post__not_in' => $skipPost
            );
        }
        //#get all data 
        $wpQuery = new \WP_Query($arg);
//        if ($this->postType == 'blog') {
//            var_dump(count($wpQuery->posts));
//            exit;
//        }

        $n = 0;
        $exception = false;
        if ($this->postType == "page") {//Exception For Home Page to Top
            $modDate = get_the_date('', $this->homeID);
            $this->tempData[$this->homeID] = array($this->siteUrl, get_the_title($this->homeID), $modDate);
            $this->typeBasisData[] = $this->siteUrl;
            $n = 1;
            $exception = true;
        }



        if ($wpQuery->posts) {
            if ($exception) {
                $wpQuery->post_count++; //Counter Up If page Type: cause Home Page Pushed in scope
            }
            foreach ($wpQuery->posts as $post) {
                $link = get_permalink($post->ID);
                if ($this->postType != 'blog') {//Blog type Post Exclude from All links
                    $this->tempLinks[] = $link; //All Links
                }
                $this->typeBasisData[] = $link;

                $modDate = get_the_date('', $post->ID);
                $this->tempData[$post->ID] = array($link, $post->post_title, $modDate);
                $n++;
                //check count 
                //if count == max or end current post type
                //then generate XML file and store file name into $generatedFiles
                if (count($this->tempData) == $this->options['sitemap_max_links'] || $n == $wpQuery->post_count) {
                    $file = $this->storeXml();
                    if ($file) {
                        $this->generatedFiles[] = array($file);
                        $this->fileCount++;
                    }
                }//Count Logic
            }//Foreach Loop
        } else {
            //If No Page Exist without Home Page
            if ($n > 0) {
                $file = $this->storeXml();
                $this->generatedFiles[] = array($file);
            }
        }
    }

    /**
     * Distribute Taxo Links
     */
    function distributeTaxo() {
        $this->setDirFileName();
        $terms = get_terms(array(
            'taxonomy' => $this->taxType,
            'hide_empty' => false,
        ));

        if (count($terms) > 0) {
            $n = 0;
            //echo "<pre>";
            foreach ($terms as $term) {
                $n++;
                $privateTerm = get_term_meta($term->term_id, 'term_private', true);
                if ($privateTerm == "1") {
                    //var_dump($this->fileName);
                    continue;
                }
                $termLink = get_term_link($term);
                $this->tempLinks[] = $termLink; //All Links
                $this->typeBasisData[] = $termLink;
                $modDate = get_term_meta($term->term_id, 'modified_at', true);
                if (!empty($modDate)) {
                    $modDate = date('Y-m-d H:i:s', $modDate);
                }
                $this->tempData[$term->term_id] = array($termLink, $term->name, $modDate);

                //var_dump($this->typeBasisData);
                if (count($this->tempData) == $this->options['sitemap_max_links'] || $n == count($terms)) {
                    $file = $this->storeXml();
                    if ($file) {
                        $this->generatedFiles[] = array($file);
                        $this->fileCount++;
                    }//if File Created
                }//Logic of max Links in one file
            }//Term Loop
        }
    }

    /**
     * Generate File and Store in Dir
     * @return string Generated File Name
     */
    public function storeXml($html = true) {
        $this->n++;
        //Generate XML data;
        //HTML sitemap data map
        if ($html) {
            $this->dataMap[$this->n] = array(
                'fileName' => $this->fileName,
                'fileIndex' => $this->fileCount,
                'links' => $this->tempData,
            );
            //Sidebar Html Generate for html sitemap
            $indx = $this->fileCount > 0 ? $this->fileCount : "";
            $furl = $this->trimSlash($this->siteUrl . "/" . $this->dirName . "/" . $this->fileName . $indx . ".html");
            if ($this->n == 1) {
                $furl = $this->siteUrl . "/sitemap.html";
            }
            $this->HtmlSidebar[$this->n] = "<li><a href=\"$furl\">{$this->fileName}$indx</a></li>";
        }//Html  

        $fileName = $this->generateXml();
        if ($fileName) {
            $this->tempData = [];
            return $fileName;
        }
    }

    /**
     * Html Sitemap Generate 
     */
    function handleHtmlGenerate() {
        $sidebarHtml = implode("\n", $this->HtmlSidebar); //Html Of Sidebar

        if ($this->dataMap) {
            $n = 0;
            foreach ($this->dataMap as $data) {
                $n++;
                $bodyHtml = "";
                $indx = $data['fileIndex'] > 0 ? $data['fileIndex'] : "";
                $path = $this->dirName . "/" . $data['fileName'] . $indx . ".html";
                if ($n == 1) {
                    $path = "sitemap.html";
                    //Main File 
                }
                //Generate Began
                if (count($data['links']) > 0) {
                    foreach ($data['links'] as $link) {
                        $bodyHtml .= "<li><a href=\"$link[0]\"><span class=\"title\">$link[1]</span></a></li>";
                    }
                }
                //var_dump($bodyHtml);
                $dataHtmlSideBarAct = str_replace($path . "\"", $path . "\" " . " class='active'", $sidebarHtml);
                $this->generateHtmlFile($bodyHtml, $path, $dataHtmlSideBarAct);
                //Body Html
            }
        }
    }

    public function generateXml($main = false) {
        $doc = new \DOMDocument('1.0', "UTF-8");
        $doc->formatOutput = true;

        if ($main) {
            $urlSet = $doc->createElement("sitemapindex");
        } else {
            $urlSet = $doc->createElement("urlset");
        }

        $attArr = [
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
        ];

        //XML Processing
        if ($this->options['enable_priority'] == '1' && $this->options['enable_change_freq'] == '1') {
            $stylePath = $this->siteUrl . "/wp-content/plugins/sitemap-generator/assets/xml-styles/xml-sitemap.xsl";
        } elseif ($this->options['enable_priority'] != '1' && $this->options['enable_change_freq'] != '1') {
            $stylePath = $this->siteUrl . "/wp-content/plugins/sitemap-generator/assets/xml-styles/xml-sitemapno.xsl";
        } elseif ($this->options['enable_priority'] == '1') {
            $stylePath = $this->siteUrl . "/wp-content/plugins/sitemap-generator/assets/xml-styles/xml-sitemapprio.xsl";
        } else {
            $stylePath = $this->siteUrl . "/wp-content/plugins/sitemap-generator/assets/xml-styles/xml-sitemapfreq.xsl";
        }
        if ($main) {
            $stylePath = $this->siteUrl . "/wp-content/plugins/sitemap-generator/assets/xml-styles/xml-sitemapset.xsl";
        }

        $stylePath = preg_replace('/([^:])(\/{2,})/', '$1/', $stylePath);
        $xslt = $doc->createProcessingInstruction('xml-stylesheet', ' type="text/xsl" href="' . $stylePath . '"');
        $doc->appendChild($xslt);

        //Creating Attributes
        foreach ($attArr as $key => $value) {
            $attr = $doc->createAttribute($key);
            $attr->value = $value;
            $urlSet->appendChild($attr);
        }
        //Add Attributes
        $doc->appendChild($urlSet);

        //URL Loop
        foreach ($this->tempData as $id => $info) {
            $link = $info[0];
            //----------------
            if ($main) {
                $url = $doc->createElement("sitemap");
            } else {
                $url = $doc->createElement("url");
            }

            $depth = $this->getDepth($link);
            $depthIndex = "dpth$depth";

            $defaultLastMod = date(DATE_ATOM);
            if (isset($info[2]) && !empty($info[2])) {
                $defaultLastMod = date(DATE_ATOM, strtotime($info[2]));
            }
            $defaultLastMod = empty($this->options['sitemap_last_modified']) ? $defaultLastMod : date(DATE_ATOM, strtotime($this->options['sitemap_last_modified']));
            //Required Elements
            $urleElements = [
                'loc' => $link,
                'lastmod' => $defaultLastMod,
            ];
            if (!$main) { //This Section not Apply for Main Sitemap File
                //ChangeFreq [optional]
                if ($this->options['enable_change_freq'] == "1") {
                    if ($this->freqChange) {
                        $urleElements['changefreq'] = $this->freqChange;
                    } else {
                        $urleElements['changefreq'] = $this->options['sitemapFreqDpth'][$depthIndex];
                    }
                }
                //Priority [optional]
                if ($this->options['enable_priority'] == "1") {
                    $urleElements['priority'] = $this->options['sitemapPriorityDpth'][$depthIndex];
                }
            }

            foreach ($urleElements as $el => $val) {
                $e = $doc->createElement($el);
                $e->appendChild($doc->createTextNode($val));
                $url->appendChild($e);
            }

            //Final Append
            $urlSet->appendChild($url);
        }

        if (!$main) {
            $fileNumber = $this->fileCount ? $this->fileCount : "";
            $fileName = ABSPATH . $this->dirName . "/" . $this->fileName . $fileNumber;
            if ($doc->save($fileName . ".xml")) {
                $fileUri = $this->siteUrl . "/" . $this->dirName . "/" . $this->fileName . $fileNumber . ".xml";
                return $this->trimSlash($fileUri);
            } else {
                return false;
            }
        } else {//This section for Main Sitemap Only
            //$info = $doc->createElement("info");
            //$totalInfo = $doc->createElement("total-urls");
            //$totalInfo->appendChild($doc->createTextNode(count($this->tempLinks)));
            //$info->appendChild($totalInfo);
            //$doc->appendChild($info);
            $fileName = ABSPATH . $this->fileName;
            if ($doc->save($fileName . ".xml")) {
                return true;
            }
        }
        return false;
    }

    function TypeBasisStore() {
        //var_dump($this->postType, $this->typeBasisData);
        //var_dump($this->typeBasisData);
        $doc = new \DOMDocument('1.0', "UTF-8");
        $doc->formatOutput = true;
        $urlSet = $doc->createElement("urlset");
        $attArr = [
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
        ];

        $stylePath = $this->siteUrl . "/wp-content/plugins/sitemap-generator/assets/xml-styles/xml-sitemap-all.xsl";
        $stylePath = preg_replace('/([^:])(\/{2,})/', '$1/', $stylePath);
        $xslt = $doc->createProcessingInstruction('xml-stylesheet', ' type="text/xsl" href="' . $stylePath . '"');
        $doc->appendChild($xslt);

        //Creating Attributes
        foreach ($attArr as $key => $value) {
            $attr = $doc->createAttribute($key);
            $attr->value = $value;
            $urlSet->appendChild($attr);
        }
        //Add Attributes
        $doc->appendChild($urlSet);

        foreach ($this->typeBasisData as $link) {
            $url = $doc->createElement("url");
            $e = $doc->createElement('loc');
            $e->appendChild($doc->createTextNode($link));
            $url->appendChild($e);
            //Final Append
            $urlSet->appendChild($url);
        }
        $typeDir = ABSPATH . $this->dirName . "/type/";
        if (!is_dir($typeDir)) {
            mkdir($typeDir, 0777);
            chmod($typeDir, 0777);
            file_put_contents($typeDir . "index.php", "<?php //Silence is golden");
        }
        if ($this->type == 'post') {
            $FName = $this->postType;
        } else {
            $FName = $this->taxType;
        }
        $doc->save($typeDir . "/$FName.xml");
    }

    function allinOneGenerate() {
        $doc = new \DOMDocument('1.0', "UTF-8");
        $doc->formatOutput = true;
        $urlSet = $doc->createElement("urlset");

        $attArr = [
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xsi:schemaLocation' => 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9'
        ];

        $stylePath = $this->siteUrl . "/wp-content/plugins/sitemap-generator/assets/xml-styles/xml-sitemap-all.xsl";
        $stylePath = preg_replace('/([^:])(\/{2,})/', '$1/', $stylePath);
        $xslt = $doc->createProcessingInstruction('xml-stylesheet', ' type="text/xsl" href="' . $stylePath . '"');
        $doc->appendChild($xslt);

        //Creating Attributes  
        foreach ($attArr as $key => $value) {
            $attr = $doc->createAttribute($key);
            $attr->value = $value;
            $urlSet->appendChild($attr);
        }
        //Add Attributes
        $doc->appendChild($urlSet);

        $html = "<ul>";
        if (is_array($this->tempLinks) && count($this->tempLinks)) {
            foreach ($this->tempLinks as $link) {
                $url = $doc->createElement("url");
                $e = $doc->createElement('loc');
                $e->appendChild($doc->createTextNode($link));
                $url->appendChild($e);
                //Final Append
                $urlSet->appendChild($url);
                $html .= "<li><a href=\"$link\"><span class=\"title\">$link</span></a></li>";
            }
        }
        $html .= "</ul>";
        $doc->save(ABSPATH . $this->dirName . "/all.xml");
        $this->generateHtmlFile($html, "all.html", false);
    }

    function getAllLinks() {
        $xmlFile = ABSPATH . $this->dirName . "/all.xml";
        if (file_exists($xmlFile)) {
            $xmlContent = file_get_contents($xmlFile);
            // Convert xml string into an object
            $data = simplexml_load_string($xmlContent);
            return $data;
        }
        return false;
    }

    function generateHtmlFile($PageItemHtml = "", $fileName = "sitemap", $sidebar = "") {
        //print_r($doc);exit;
        //===============HTML==================
        $siteName = get_bloginfo();
        $aditionalMeta = "";
        //if (!$sidebar) {
        $canonical = $this->trimSlash($this->siteUrl . "/$fileName");
        //File Info
        $pInfo = pathinfo($fileName);
        $namyfy = "";
        if ($pInfo) {
            $namyfy = ucwords(str_replace("-", " ", $pInfo['filename']));
        }
        $aditionalMeta .= "<meta name=\"robots\" content=\"noarchive\">";
        $aditionalMeta .= "<meta name=\"robots\" content=\"noindex\">";
        $aditionalMeta .= "<link rel=\"canonical\" href=\"$canonical\">";
        //}
        $desc = "sitemap - $namyfy";
        $htmlData = "<!DOCTYPE html>
<html>
	<head>
                <meta charset=\"UTF-8\">
		<title>$namyfy - Sitemap</title>
		<meta id=\"MetaDescription\" name=\"description\" content=\"$desc\" />
		<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\" />
		<meta content=\"Xml Sitemap Generator .org\" name=\"Author\" />
                $aditionalMeta
		<style>
*, a {color: #444 !important;}
body, head, #xsg {margin:0px 0px 0px 0px; line-height:22px; color:#666666; width:100%; padding:0px 0px 0px 0px;font-family : Tahoma, Verdana,   Arial, sans-serif; font-size:13px;max-width: 1100px;margin: auto;}

.logo{padding: 15px;max-width: 55px;background: #f6f6f6;border-radius: 50%;}
#xsg ul li a {font-weight:bold; }
#xsg ul ul li a {font-weight:normal; }
#xsg a {text-decoration:none; }
#xsg p {margin:10px 0px 10px 0px;}
#xsg ul {list-style:square; }
#xsg li {margin: 5px 0;}
#xsg th { text-align:left;font-size: 0.9em;padding:2px 10px 2px 2px; border-bottom:1px solid #CCCCCC; border-collapse:collapse;}
#xsg td { text-align:left;font-size: 0.9em; padding:2px 10px 2px 2px; border-bottom:1px solid #CCCCCC; border-collapse:collapse;}
			
#xsg .title {font-size: 0.9em;  color:#132687;  display:inline;}
#xsg .url {font-size: 0.7em; color:#999999;}			
#xsgHeader { width:100%;  margin:0px 0px 5px 0px; border-bottom: 1px solid #f6f6f6;}
#xsgHeader h1 {  padding:0px 0px 0px 20px ; }
#xsgHeader h1 a {color:#132687; font-size:14px; text-decoration:none;cursor:default;}
#xsgBody {padding: 10px 0;display: flex;}
.xsgContent {height: 100%;overflow: hidden;flex-grow: 1;max-width: 75%;}
#xsgFooter { color:#999999; width:100%;  margin:20px 0px 15px 0px; border-top:1px solid #999999;padding: 10px 0px 10px 0px; }
#xsgFooter a {color:#999999; font-size:11px; text-decoration:none;   }    
#xsgFooter span {color:#999999; font-size:11px; text-decoration:none; margin-left:20px; }
.xsgSidebar {flex-basis: 25%;max-width: 25%;}
.xsgSidebar ul {padding: 0;}
.xsgSidebar ul li a {padding: 8px;display: block;border-bottom: 1px solid #eee;font-weight: normal !important; font-size: 16px;text-transform: capitalize;position: relative;padding-left: 12px;}
.xsgSidebar ul li a::before {content: \"\";position: absolute;left: 3px;top: 0; border: 5px solid transparent;border-left-color: #999;top: calc(50% - 5px);}
.xsgSidebar ul li a::after {content: \"\";position: absolute;left: 2px;top: 0;border: 5px solid transparent;border-left-color: transparent;border-left-color: #fff;top: calc(50% - 5px);}
.xsgSidebar ul li {margin: 0 !important;list-style: none;}
.xsgSidebar ul li a.active {background: #f6f6f6;}
#xsgHeader h2 {display: flex;justify-content: space-between;align-items: center;margin: 40px 0;}
#xsgHeader h2 p {font-size: 14px;font-style: italic;font-weight: 300;}
#xsgHeader h2 span {font-size: 28px;padding: 15px 0;position: relative;}
#xsgHeader h2 span::before {content: \"TM\";position: absolute;right: -16px;top: 3px;font-size: 12px;font-weight: 300;line-height: 1;}
		</style>
	</head>
	<body>
            <div id=\"xsg\">
                <div id=\"xsgHeader\">
                    <h1 style='display:none'>H1</h1>
                    <h2>
                        <span>$siteName</span>
                       <p>A Private Label Clothing Manufacturer in Bangladesh Since 1987</p>
                    </h2>
                </div>
                <div id=\"xsgBody\">";
        if ($sidebar) {
            $htmlData .= "<div class=\"xsgSidebar\">
                        <ul>$sidebar</ul>
                    </div>";
        }
        $htmlData .= "<div class=\"xsgContent\">
                        <ul>
                        $PageItemHtml
                        </ul>
                    </div>
                </div>
                <div id=\"xsgFooter\">
                    <span>$siteName HTML Sitemap</span>
                </div>
            </div>
	</body>
</html>";
        $resHtml = file_put_contents(ABSPATH . "/$fileName", $htmlData);
    }

    /**
     * REmove Double Slash from URL
     * @param type $url
     * @return type
     */
    function trimSlash($url) {
        return preg_replace('/([^:])(\/{2,})/', '$1/', $url);
    }

    /**
     * Get Depth Of URL 
     * @param String $url
     * @return int 
     */
    function getDepth($url) {
        $RQURI = str_replace($this->siteUrl, "", $url);
        $parts = explode("/", $RQURI);
        $parts = array_unique(array_filter(array_map('trim', $parts)));
        return count($parts);
    }

    /**
     * CSV File to Shortcode Decode
     * @param Object $wpdb Project
     * @param string $str String, What convert
     * @param string $link Suffix
     */
    function shortcodeFilter($project, $str, $suffix) {
        //var_dump($project, $str);
        $filePath = $project->source_path;
        if ($project->source_type == "direct_link") {
            $filePath = $project->original_file_url;
        }
        $file = fopen($filePath, 'r');
        $data = [];
        $cols = [];
        $n = 0;
        while (($line = fgetcsv($file)) !== FALSE) {
            //$line is an array of the csv elements
            $n++;
            if ($n == 1) {
                $cols = $line;
            } else {
                foreach ($cols as $i => $col) {
                    $data[$col][] = $line[$i];
                }
            }
        }
        fclose($file);

        $headers = json_decode($project->headers, true);
        $lockedIndx = false;
        foreach ($headers as $code) {
            if (strpos($str, '{{mpg_' . $code . '}}') !== false) {
                //Select Column data by shortcode if exist in str
                $colData = $data[$code];
                foreach ($colData as $k => $val) {
                    if (strpos($suffix, $val) !== false) {
                        $str = str_replace('{{mpg_' . $code . '}}', $val, $str);
                        $lockedIndx = $k;
                        break;
                    }
                }
                //var_dump($lockedIndx, $str, '{{mpg_' . $code . '}}');
                if ($lockedIndx !== false) {
                    $str = str_replace('{{mpg_' . $code . '}}', $colData[$lockedIndx], $str);
                }
            }
        }
        //exit;
        //var_dump($headers);
        //var_dump($data, $suffix, $str);
        return $str;
    }

}

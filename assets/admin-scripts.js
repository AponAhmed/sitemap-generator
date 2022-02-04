/**
 * @package SitemapGenerator
 * @version 1
 */
//Ajax object ajax_object_sitemap


(function ($) {
    $(document).ready(function () {
        $('.editSitemapFileName').click(function (e) {
            //closest checkField attr value
            let ClT = $(e.target);
            //ClT.removeClass('dashicons dashicons-edit').addClass('dashicons dashicons-update loading');//
            let ckkBox = ClT.closest('div').find('.checkField');
            let velOf = ckkBox.val();
            let existingVal = ckkBox.attr('data-modified');
            $(".sitemap-name-edit").remove();
            $('body').prepend('<div class="sitemap-name-edit">\n\
                                <div class="smn-edit-title-area"><h3>File Name for "' + velOf + '"</h3></div>\n\
<div class="smn-edit-body">\n\
<input type="text" value="' + existingVal + '" id="fileNameField" name="sitemap_file_' + velOf + '">\n\
<hr><button type="button" onclick="updateFileName(this)" class="button button-primary">Update</button>&nbsp;&nbsp;&nbsp;<button onclick="jQuery(\'.sitemap-name-edit\').remove()" type="button" class="button">Cancel</button></div>\n\
                        </div>');
        });

        //MPG Settings Update
        //body.mpg_page_mpg-project-builder
        //<button type=\"button\" onclick=\"mpgSitemapGenerate(this)\" class=\"button\">&nbsp;Generate</button>
        if ($("body.mpg_page_mpg-project-builder").length > 0) {
            console.log("This is MPG Scope");
            $(".sitemap-page  .save-changes-block .generate-sitemap").before("<div><button type=\"button\" onclick=\"mpgSitemapOptionUpdate(this)\" class=\"button\">&nbsp;Update Option</button>&nbsp;&nbsp;</div>");
            $(".sitemap-page  .save-changes-block .generate-sitemap").remove();
        }
    })
})(jQuery);


function mpgSitemapOptionUpdate(_this) {
    const state = JSON.parse(localStorage.getItem('mpg_state')) || null;
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "update_mpg_sitemap_option", projecID: state.projectId, data: jQuery(_this).closest('form').serialize()};
    jQuery.post(ajax_object_sitemap.ajax_url, data, function (response) {
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        //console.log(response);
    });
}
function mpgSitemapGenerate(_this) {
    const state = JSON.parse(localStorage.getItem('mpg_state')) || null;
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "mpgSitemapGenerateSingle", projecID: state.projectId};
    jQuery.post(ajax_object_sitemap.ajax_url, data, function (response) {
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        //console.log(response);
    });
}

function updateSitemapOption(_this) {
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    var data = {action: "update_sitemap_option", data: jQuery(_this).closest('form').serialize()};
    jQuery.post(ajax_object_sitemap.ajax_url, data, function (response) {
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        console.log(response);
    });
}

function updateFileName(_this) {
    let btn = jQuery(_this);
    btn.find(".dashicons").remove();
    btn.prepend('<span class="dashicons dashicons-update loading"></span>');
    let FnameVal = jQuery('#fileNameField').val();
    let Fname = jQuery('#fileNameField').attr('name');
    var data = {action: "updateFileName", Filedata: {Fname: Fname, val: FnameVal}};
    jQuery.post(ajax_object_sitemap.ajax_url, data, function (response) {
        btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
        //console.log(response);
    });
}

function GenerateSitemap(_this) {
    (function ($) {
        let btn = $(_this);
        console.log(btn);
        btn.find(".dashicons").remove();
        btn.prepend('<span class="dashicons dashicons-update loading"></span>');
        $.post(ajax_object_sitemap.ajax_url, {action: 'GenerateSitemap'}, function (response) {
            btn.find('.loading').removeClass('loading dashicons-update').addClass('dashicons-saved');
            $('.viewSitemap').removeAttr('disabled');
        })
    })(jQuery);
}
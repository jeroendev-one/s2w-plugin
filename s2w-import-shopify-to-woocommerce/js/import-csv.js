'use strict';
jQuery(document).ready(function ($) {
    console.log(s2w_import_shopify_to_woocommerce_import_params);
    $('.vi-ui.dropdown').dropdown({placeholder: 'Do not import'});
    if (s2w_import_shopify_to_woocommerce_import_params.step === 'mapping') {
        let required_fields = s2w_import_shopify_to_woocommerce_import_params.required_fields;
        $('input[name="s2w_import_shopify_to_woocommerce_import"]').on('click', function (e) {
            let empty_required_fields = [];
            for (let field in required_fields) {
                if (required_fields.hasOwnProperty(field) && !$('#s2w-' + field).val()) {
                    empty_required_fields.push(required_fields[field]);
                }
            }
            if (empty_required_fields.length > 0) {
                if (empty_required_fields.length === 1) {
                    alert(empty_required_fields[0] + ' is required to map')
                } else {
                    alert('These fields are required to map: ' + empty_required_fields.join());
                }
                e.preventDefault();
                return false;
            } else if (Boolean($('#s2w-option2_name').val()) !== Boolean($('#s2w-option2_value').val())) {
                alert('Option2 Name & Option2 Value should both be mapped or not mapped');
                e.preventDefault();
                return false;
            } else if (Boolean($('#s2w-option3_name').val()) !== Boolean($('#s2w-option3_value').val())) {
                alert('Option3 Name & Option3 Value should both be mapped or not mapped');
                e.preventDefault();
                return false;
            }
        })
    }

    let $progress = $('.s2w-import-progress');
    let total = 0;
    let ftell = 0;
    let start = parseInt(s2w_import_shopify_to_woocommerce_import_params.custom_start) - 1;
    if (start === 0) {
        start = 1;
    }
    let products_per_request = parseInt(s2w_import_shopify_to_woocommerce_import_params.products_per_request);
    let download_images = s2w_import_shopify_to_woocommerce_import_params.download_images;
    let download_description_images = s2w_import_shopify_to_woocommerce_import_params.download_description_images;
    let download_images_later = s2w_import_shopify_to_woocommerce_import_params.download_images_later;
    let keep_slug = s2w_import_shopify_to_woocommerce_import_params.keep_slug;
    let global_attributes = s2w_import_shopify_to_woocommerce_import_params.global_attributes;
    let product_status = s2w_import_shopify_to_woocommerce_import_params.product_status;
    let product_categories = s2w_import_shopify_to_woocommerce_import_params.product_categories;
    let s2w_index = s2w_import_shopify_to_woocommerce_import_params.s2w_index;

    if (s2w_import_shopify_to_woocommerce_import_params.step === 'import') {
        if (parseInt(s2w_index['image']) < 0 && parseInt(s2w_index['variant_image']) < 0) {
            download_images = '';
        }
        if (parseInt(s2w_index['body_html']) < 0) {
            download_description_images = '';
        }
        $progress.progress('set label', 'Checking file...');
        $.ajax({
            url: s2w_import_shopify_to_woocommerce_import_params.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce_import',
                nonce: s2w_import_shopify_to_woocommerce_import_params.nonce,
                file_url: s2w_import_shopify_to_woocommerce_import_params.file_url,
                s2w_index: s2w_index,
                step: 'check',
            },
            success: function (response) {
                console.log(response);
                if (response.status === 'success') {
                    total = parseInt(response.total);
                    if (total > 0) {
                        if (total >= start) {
                            s2w_import();
                            $progress.progress('set percent', 0);
                            $progress.progress('set label', 'Importing...');
                        } else {
                            $progress.progress('set error');
                            $progress.progress('set label', 'Error: The Start line must be smaller than ' + total + ' for this file');
                        }
                    } else {
                        $progress.progress('set error');
                        $progress.progress('set label', 'Error: No data');
                    }
                } else {
                    $progress.progress('set error');
                    if (response.hasOwnProperty('message')) {
                        $progress.progress('set label', 'Error: ' + response.message);
                    }
                }
            },
            error: function (err) {
                $progress.progress('set error');
                $progress.progress('set label', err.statusText);
            },
        });
    }

    function s2w_import() {
        $.ajax({
            url: s2w_import_shopify_to_woocommerce_import_params.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce_import',
                nonce: s2w_import_shopify_to_woocommerce_import_params.nonce,
                file_url: s2w_import_shopify_to_woocommerce_import_params.file_url,
                products_per_request: products_per_request,
                download_description_images: download_description_images,
                download_images: download_images,
                download_images_later: download_images_later,
                keep_slug: keep_slug,
                global_attributes: global_attributes,
                product_status: product_status,
                product_categories: product_categories,
                s2w_index: s2w_index,
                step: 'import',
                ftell: ftell,
                start: start,
                total: total,
            },
            success: function (response) {
                console.log(response);
                if (response.status === 'success') {
                    ftell = response.ftell;
                    start = response.start;
                    let percent = response.percent;
                    $progress.progress('set percent', percent);
                    s2w_import();
                } else if (response.status === 'finish') {
                    $progress.progress('complete');
                    $progress.progress('set label', 'Import completed.');
                    let message = 'Import completed.';
                    if (download_images || download_description_images) {
                        message += ' Products images are being downloaded in the background.';
                    }
                    alert(message);
                } else {
                    $progress.progress('set error');
                    $progress.progress('set label', response.message);
                }
            },
            error: function (err) {
                console.log(err);
                $progress.progress('set error');
                $progress.progress('set label', 'Error');
            },
        });
    }

    $('#s2w-download_images').on('change', function () {
        let $download_images_later = $('.s2w-download_images_later_container');
        if ($(this).prop('checked')) {
            $download_images_later.fadeIn(200);
        } else {
            $download_images_later.fadeOut(200);
        }
    });
    $('.search-category').select2({
        closeOnSelect: false,
        placeholder: "Please fill in your category title",
        ajax: {
            url: "admin-ajax.php?action=s2w_search_cate",
            dataType: 'json',
            type: "GET",
            quietMillis: 50,
            delay: 250,
            data: function (params) {
                return {
                    keyword: params.term
                };
            },
            processResults: function (data) {
                return {
                    results: data
                };
            },
            cache: true
        },
        escapeMarkup: function (markup) {
            return markup;
        }, // let our custom formatter work
        minimumInputLength: 2
    });
});

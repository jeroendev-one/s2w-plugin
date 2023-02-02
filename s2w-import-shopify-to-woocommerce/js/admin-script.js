'use strict';
jQuery(document).ready(function ($) {
    $('.vi-ui.accordion')
        .vi_accordion('refresh');
    /*import product options*/
    $('.s2w-save-products-options').on('click', function (e) {
        let $button = $(this);
        $button.addClass('loading');
        let $saving_overlay = $('.s2w-import-products-options-saving-overlay');
        $saving_overlay.removeClass('s2w-hidden');
        _s2w_nonce = $('#_s2w_nonce').val();
        domain = $('#s2w-domain').val();
        product_status = $('#s2w-product_status').val();
        variable_sku = $('#s2w-variable_sku').val();
        product_categories = $('#s2w-product_categories').val();
        download_images = $('#s2w-download_images').prop('checked') ? 1 : 0;
        download_description_images = $('#s2w-download_description_images').prop('checked') ? 1 : 0;
        disable_background_process = $('#s2w-disable_background_process').prop('checked') ? 1 : 0;
        keep_slug = $('#s2w-keep_slug').prop('checked') ? 1 : 0;
        global_attributes = $('#s2w-global_attributes').prop('checked') ? 1 : 0;
        products_per_request = $('#s2w-products_per_request').val();
        product_import_sequence = $('#s2w-product_import_sequence').val();
        product_since_id = $('#s2w-product_since_id').val();
        product_product_type = $('#s2w-product_product_type').val();
        product_vendor = $('#s2w-product_vendor').val();
        product_collection_id = $('#s2w-product_collection_id').val();
        product_created_at_min = $('#s2w-product_created_at_min').val();
        product_created_at_max = $('#s2w-product_created_at_max').val();
        product_published_at_min = $('#s2w-product_published_at_min').val();
        product_published_at_max = $('#s2w-product_published_at_max').val();
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_save_settings_product_options',
                domain: domain,
                _s2w_nonce: _s2w_nonce,
                download_images: download_images,
                disable_background_process: disable_background_process,
                download_description_images: download_description_images,
                keep_slug: keep_slug,
                global_attributes: global_attributes,
                product_status: product_status,
                variable_sku: variable_sku,
                product_categories: product_categories ? product_categories : [],
                products_per_request: products_per_request,
                product_import_sequence: product_import_sequence,
                product_since_id: product_since_id,
                product_product_type: product_product_type,
                product_vendor: product_vendor,
                product_collection_id: product_collection_id,
                product_created_at_min: product_created_at_min,
                product_created_at_max: product_created_at_max,
                product_published_at_min: product_published_at_min,
                product_published_at_max: product_published_at_max,
            },
            success: function (response) {
                total_products = parseInt(response.total_products);
                total_pages = response.total_pages;
                current_import_id = response.current_import_id;
                current_import_product = parseInt(response.current_import_product);
                current_import_page = response.current_import_page;
                $button.removeClass('loading');
                $saving_overlay.addClass('s2w-hidden');
                s2w_product_options_close();
            },
            error: function (err) {
                $button.removeClass('loading');
                $saving_overlay.addClass('s2w-hidden');
                s2w_product_options_close();
            }
        })
    });
    $('.s2w-import-products-options-close').on('click', function (e) {
        s2w_product_options_close();
        s2w_product_options_cancel();
    });
    $('.s2w-import-products-options-overlay').on('click', function (e) {
        $('.s2w-import-products-options-close').click();
    });
    $('.s2w-import-products-options-shortcut').on('click', function (e) {
        if (!$('.s2w-accordion').find('.content').eq(0).hasClass('active')) {
            e.preventDefault();
            s2w_product_options_show();
            $('.s2w-import-products-options-main').append($('.s2w-import-products-options-content'));
        } else if (!$('#s2w-import-products-options-anchor').hasClass('active')) {
            $('#s2w-import-products-options-anchor').vi_accordion('open')
        }
    });

    function s2w_product_options_cancel() {
        $('#s2w-product_status').val(product_status);
        $('#s2w-variable_sku').val(variable_sku);
        $('#s2w-download_images').prop('checked', (download_images == 1));
        $('#s2w-disable_background_process').prop('checked', (disable_background_process == 1));
        $('#s2w-download_description_images').prop('checked', (download_description_images == 1));
        $('#s2w-keep_slug').prop('checked', (keep_slug == 1));
        $('#s2w-global_attributes').prop('checked', (global_attributes == 1));
        $('#s2w-products_per_request').val(products_per_request);
        $('#s2w-product_import_sequence').val(product_import_sequence);

        $('#s2w-product_since_id').val(product_since_id);
        $('#s2w-product_product_type').val(product_product_type);
        $('#s2w-product_vendor').val(product_vendor);
        $('#s2w-product_collection_id').val(product_collection_id);
        $('#s2w-product_created_at_min').val(product_created_at_min);
        $('#s2w-product_created_at_max').val(product_created_at_max);
        $('#s2w-product_published_at_min').val(product_published_at_min);
        $('#s2w-product_published_at_max').val(product_published_at_max);
        if (product_categories) {
            $('#s2w-product_categories').val(product_categories).trigger('change');
        } else {
            $('#s2w-product_categories').val(null).trigger('change');
        }
    }

    /*import order options*/
    $('.s2w-save-orders-options').on('click', function (e) {
        let $button = $(this);
        $button.addClass('loading');
        let $saving_overlay = $('.s2w-import-orders-options-saving-overlay');
        $saving_overlay.removeClass('s2w-hidden');
        _s2w_nonce = $('#_s2w_nonce').val();
        domain = $('#s2w-domain').val();
        orders_per_request = $('#s2w-orders_per_request').val();
        order_import_sequence = $('#s2w-order_import_sequence').val();
        order_since_id = $('#s2w-order_since_id').val();
        order_processed_at_min = $('#s2w-order_processed_at_min').val();
        order_financial_status = $('#s2w-order_financial_status').val();
        order_fulfillment_status = $('#s2w-order_fulfillment_status').val();
        order_processed_at_max = $('#s2w-order_processed_at_max').val();
        $('.s2w-order_status_mapping').map(function () {
            let order_select = $(this).find('select').eq(0);
            order_status_mapping[order_select.data('from_status')] = order_select.val();
        });
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_save_settings_order_options',
                domain: domain,
                _s2w_nonce: _s2w_nonce,
                orders_per_request: orders_per_request,
                order_import_sequence: order_import_sequence,
                order_since_id: order_since_id,
                order_processed_at_min: order_processed_at_min,
                order_financial_status: order_financial_status,
                order_fulfillment_status: order_fulfillment_status,
                order_processed_at_max: order_processed_at_max,
                order_status_mapping: order_status_mapping,
            },
            success: function (response) {
                total_orders = parseInt(response.total_orders);
                orders_total_pages = response.orders_total_pages;
                orders_current_import_id = response.orders_current_import_id;
                current_import_order = parseInt(response.current_import_order);
                orders_current_import_page = response.orders_current_import_page;
                $button.removeClass('loading');
                $saving_overlay.addClass('s2w-hidden');
                s2w_order_options_close();
            },
            error: function (err) {
                $button.removeClass('loading');
                $saving_overlay.addClass('s2w-hidden');
                s2w_order_options_close();
            }
        })
    });
    $('.s2w-import-orders-options-close').on('click', function (e) {
        s2w_order_options_close();
        s2w_order_options_cancel();
    });
    $('.s2w-import-orders-options-overlay').on('click', function (e) {
        $('.s2w-import-orders-options-close').click();
    });
    $('.s2w-import-orders-options-shortcut').on('click', function (e) {
        if (!$('.s2w-accordion').find('.content').eq(0).hasClass('active')) {
            e.preventDefault();
            s2w_order_options_show();
            $('.s2w-import-orders-options-main').append($('.s2w-import-orders-options-content'));
        } else if (!$('#s2w-import-orders-options-anchor').hasClass('active')) {
            $('#s2w-import-orders-options-anchor').vi_accordion('open')
        }
    });

    function s2w_order_options_cancel() {
        $('#s2w-orders_per_request').val(orders_per_request);
        $('#s2w-order_import_sequence').val(order_import_sequence);
        $('#s2w-order_since_id').val(order_since_id);
        $('#s2w-order_processed_at_min').val(order_processed_at_min);
        $('#s2w-order_financial_status').val(order_financial_status);
        $('#s2w-order_fulfillment_status').val(order_fulfillment_status);
        $('#s2w-order_processed_at_max').val(order_processed_at_max);
        $('.s2w-order_status_mapping').map(function () {
            let order_select = $(this).find('select').eq(0);
            order_select.dropdown('set selected', order_status_mapping[order_select.data('from_status')]);
        });
    }

    /*import coupon options*/
    $('.s2w-save-coupons-options').on('click', function (e) {
        let $button = $(this);
        $button.addClass('loading');
        let $saving_overlay = $('.s2w-import-coupons-options-saving-overlay');
        $saving_overlay.removeClass('s2w-hidden');
        _s2w_nonce = $('#_s2w_nonce').val();
        domain = $('#s2w-domain').val();
        coupons_per_request = $('#s2w-coupons_per_request').val();
        coupon_starts_at_min = $('#s2w-coupon_starts_at_min').val();
        coupon_starts_at_max = $('#s2w-coupon_starts_at_max').val();
        coupon_ends_at_min = $('#s2w-coupon_ends_at_min').val();
        coupon_ends_at_max = $('#s2w-coupon_ends_at_max').val();
        coupon_zero_times_used = $('#s2w-coupon_zero_times_used').prop('checked') ? 1 : 0;
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_save_settings_coupon_options',
                domain: domain,
                _s2w_nonce: _s2w_nonce,
                coupons_per_request: coupons_per_request,
                coupon_starts_at_min: coupon_starts_at_min,
                coupon_starts_at_max: coupon_starts_at_max,
                coupon_ends_at_min: coupon_ends_at_min,
                coupon_ends_at_max: coupon_ends_at_max,
                coupon_zero_times_used: coupon_zero_times_used,
            },
            success: function (response) {
                total_coupons = parseInt(response.total_coupons);
                coupons_total_pages = response.coupons_total_pages;
                coupons_current_import_id = response.coupons_current_import_id;
                current_import_coupon = parseInt(response.current_import_coupon);
                coupons_current_import_page = response.coupons_current_import_page;
                $button.removeClass('loading');
                $saving_overlay.addClass('s2w-hidden');
                s2w_coupon_options_close();
            },
            error: function (err) {
                $button.removeClass('loading');
                $saving_overlay.addClass('s2w-hidden');
                s2w_coupon_options_close();
            }
        })
    });
    $('.s2w-import-coupons-options-close').on('click', function (e) {
        s2w_coupon_options_close();
        s2w_coupon_options_cancel();
    });
    $('.s2w-import-coupons-options-overlay').on('click', function (e) {
        $('.s2w-import-coupons-options-close').click();
    });
    $('.s2w-import-coupons-options-shortcut').on('click', function (e) {
        if (!$('.s2w-accordion').find('.content').eq(0).hasClass('active')) {
            e.preventDefault();
            s2w_coupon_options_show();
            $('.s2w-import-coupons-options-main').append($('.s2w-import-coupons-options-content'));
        } else if (!$('#s2w-import-coupons-options-anchor').hasClass('active')) {
            $('#s2w-import-coupons-options-anchor').vi_accordion('open')
        }
    });

    function s2w_coupon_options_cancel() {
        $('#s2w-coupons_per_request').val(coupons_per_request);
        $('#s2w-coupon_starts_at_min').val(coupon_starts_at_min);
        $('#s2w-coupon_starts_at_max').val(coupon_starts_at_max);
        $('#s2w-coupon_ends_at_min').val(coupon_ends_at_min);
        $('#s2w-coupon_ends_at_max').val(coupon_ends_at_max);
        $('#s2w-coupon_zero_times_used').prop('checked', (coupon_zero_times_used == 1));
    }

    searchCategoriesSelect2();

    function searchCategoriesSelect2() {
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

    }

    $('.vi-ui.checkbox').checkbox();
    $('.vi-ui.dropdown').dropdown();
    /**
     * Start Get download key
     */
    jQuery('.villatheme-get-key-button').one('click', function (e) {
        let v_button = jQuery(this);
        v_button.addClass('loading');
        let item_id = v_button.data('id');
        let app_url = v_button.data('href');
        let main_domain = window.location.hostname;
        main_domain = main_domain.toLowerCase();
        let popup_frame;
        e.preventDefault();
        let download_url = v_button.attr('data-download');
        popup_frame = window.open(app_url, "myWindow", "width=380,height=600");
        window.addEventListener('message', function (event) {
            /*Callback when data send from child popup*/
            let obj = JSON.parse(event.data);
            let update_key = '';
            let message = obj.message;
            let support_until = '';
            let check_key = '';
            if (obj['data'].length > 0) {
                for (let i = 0; i < obj['data'].length; i++) {
                    if (obj['data'][i].id == item_id && (obj['data'][i].domain == main_domain || obj['data'][i].domain == '' || obj['data'][i].domain == null)) {
                        if (update_key == '') {
                            update_key = obj['data'][i].download_key;
                            support_until = obj['data'][i].support_until;
                        } else if (support_until < obj['data'][i].support_until) {
                            update_key = obj['data'][i].download_key;
                            support_until = obj['data'][i].support_until;
                        }
                        if (obj['data'][i].domain == main_domain) {
                            update_key = obj['data'][i].download_key;
                            break;
                        }
                    }
                }
                if (update_key) {
                    check_key = 1;
                    jQuery('.villatheme-autoupdate-key-field').val(update_key);
                }
            }
            v_button.removeClass('loading');
            if (check_key) {
                jQuery('<p><strong>' + message + '</strong></p>').insertAfter(".villatheme-autoupdate-key-field");
                jQuery(v_button).closest('form').submit();
            } else {
                jQuery('<p><strong> Your key is not found. Please contact support@villatheme.com </strong></p>').insertAfter(".villatheme-autoupdate-key-field");
            }
        });
    });
    /**
     * End get download key
     */
    $('.s2w-import-element-enable-bulk').on('change', function () {
        $('.s2w-import-element-enable').prop('checked', $(this).prop('checked'));
    });
    $('#s2w-domain').on('change', function () {
        let domain = $(this).val();
        domain = domain.replace(/https:\/\//g, '');
        domain = domain.replace(/\//g, '');

        $(this).val(domain);
    });
    $('#s2w-variable_sku').on('change', function () {
        let variable_sku = $(this).val();
        variable_sku = variable_sku.replace(/\s/g, '');

        $(this).val(variable_sku);
    });
    let selected_elements = [];
    let progress_bars = {};

    function get_selected_elements() {
        selected_elements = [];
        progress_bars = [];
        $('.s2w-import-element-enable').map(function () {
            if ($(this).prop('checked')) {
                let element_name = $(this).data('element_name');
                selected_elements.push(element_name);
                progress_bars[element_name] = $('#s2w-' + element_name.replace('_', '-') + '-progress');
            }
        });
    }

    function s2w_import_element() {
        if (selected_elements.length) {
            let element = selected_elements.shift();
            progress_bars[element].progress('set label', 'Importing...');
            progress_bars[element].progress('set active');
            switch (element) {
                case 'store_settings':
                    s2w_import_store_settings();
                    break;
                case 'shipping_zones':
                    s2w_import_shipping_zones();
                    break;
                case 'taxes':
                    s2w_import_taxes();
                    break;
                case 'pages':
                    s2w_import_spages();
                    break;
                case 'blogs':
                    s2w_import_blogs();
                    break;
                case 'coupons':
                    s2w_import_coupons();
                    break;
                case 'customers':
                    s2w_import_customers();
                    break;
                case 'products':
                    s2w_import_products();
                    break;
                case 'product_categories':
                    s2w_import_product_categories();
                    break;
                case 'orders':
                    s2w_import_orders();
                    break;
            }
        } else {
            s2w_unlock_buttons();
            import_active = false;
            $('.s2w-sync').removeClass('loading');
            setTimeout(function () {
                alert('Import completed.');
            }, 400);
        }
    }

    let total_orders = parseInt(s2w_params_admin.total_orders),
        orders_total_pages = s2w_params_admin.orders_total_pages,
        orders_current_import_id = s2w_params_admin.orders_current_import_id,
        current_import_order = parseInt(s2w_params_admin.current_import_order),
        orders_current_import_page = s2w_params_admin.orders_current_import_page;

    let total_customers = parseInt(s2w_params_admin.total_customers),
        customers_total_pages = s2w_params_admin.customers_total_pages,
        customers_current_import_id = s2w_params_admin.customers_current_import_id,
        current_import_customer = parseInt(s2w_params_admin.current_import_customer),
        customers_current_import_page = s2w_params_admin.customers_current_import_page;
    let total_spages = parseInt(s2w_params_admin.total_spages),
        spages_total_pages = s2w_params_admin.spages_total_pages,
        spages_current_import_id = s2w_params_admin.spages_current_import_id,
        current_import_spage = parseInt(s2w_params_admin.current_import_spage),
        spages_current_import_page = s2w_params_admin.spages_current_import_page;
    let total_coupons = parseInt(s2w_params_admin.total_coupons),
        coupons_total_pages = s2w_params_admin.coupons_total_pages,
        coupons_current_import_id = s2w_params_admin.coupons_current_import_id,
        current_import_coupon = parseInt(s2w_params_admin.current_import_coupon),
        coupons_current_import_page = s2w_params_admin.coupons_current_import_page;

    let total_products = parseInt(s2w_params_admin.total_products),
        total_pages = s2w_params_admin.total_pages,
        current_import_id = s2w_params_admin.current_import_id,
        current_import_product = parseInt(s2w_params_admin.current_import_product),
        current_import_page = s2w_params_admin.current_import_page,
        product_percent_old = 0,

        imported_elements = s2w_params_admin.imported_elements,
        elements_titles = s2w_params_admin.elements_titles,
        _s2w_nonce = $('#_s2w_nonce').val(),
        domain = $('#s2w-domain').val(),
        api_key = $('#s2w-api_key').val(),
        api_secret = $('#s2w-api_secret').val(),
        download_images = $('#s2w-download_images').prop('checked') ? 1 : 0,
        disable_background_process = $('#s2w-disable_background_process').prop('checked') ? 1 : 0,
        download_description_images = $('#s2w-download_description_images').prop('checked') ? 1 : 0,
        keep_slug = $('#s2w-keep_slug').prop('checked') ? 1 : 0,
        global_attributes = $('#s2w-global_attributes').prop('checked') ? 1 : 0,
        product_status = $('#s2w-product_status').val(),
        variable_sku = $('#s2w-variable_sku').val(),
        order_status_mapping = {},
        request_timeout = $('#s2w-request_timeout').val(),
        products_per_request = $('#s2w-products_per_request').val(),
        customers_per_request = $('#s2w-customers_per_request').val(),
        blogs_update_if_exist = $('#s2w-blogs_update_if_exist').val(),
        customers_role = $('#s2w-customers_role').val(),
        customers_with_purchases_only = $('#s2w-customers_with_purchases_only').prop('checked') ? 1 : 0,
        coupons_per_request = $('#s2w-coupons_per_request').val(),
        coupon_starts_at_min = $('#s2w-coupon_starts_at_min').val(),
        coupon_starts_at_max = $('#s2w-coupon_starts_at_max').val(),
        coupon_ends_at_min = $('#s2w-coupon_ends_at_min').val(),
        coupon_ends_at_max = $('#s2w-coupon_ends_at_max').val(),
        coupon_zero_times_used = $('#s2w-coupon_zero_times_used').prop('checked') ? 1 : 0,
        orders_per_request = $('#s2w-orders_per_request').val(),
        order_import_sequence = $('#s2w-order_import_sequence').val(),
        product_import_sequence = $('#s2w-product_import_sequence').val(),
        product_categories = $('#s2w-product_categories').val();
    $('.s2w-order_status_mapping').map(function () {
        let order_select = $(this).find('select').eq(0);
        order_status_mapping[order_select.data('from_status')] = order_select.val();
    });
    let save_active = false,
        import_complete = false,
        orders_import_complete = false,
        customers_import_complete = false,
        spages_import_complete = false,
        coupons_import_complete = false,
        error_log = '',
        import_active = false;
    let warning,
        warning_empty_store = s2w_params_admin.warning_empty_store,
        warning_empty_api_key = s2w_params_admin.warning_empty_api_key,
        warning_empty_api_secret = s2w_params_admin.warning_empty_api_secret;

    let product_since_id = $('#s2w-product_since_id').val(),
        product_product_type = $('#s2w-product_product_type').val(),
        product_vendor = $('#s2w-product_vendor').val(),
        product_collection_id = $('#s2w-product_collection_id').val(),
        product_created_at_min = $('#s2w-product_created_at_min').val(),
        product_created_at_max = $('#s2w-product_created_at_max').val(),
        product_published_at_min = $('#s2w-product_published_at_min').val(),
        product_published_at_max = $('#s2w-product_published_at_max').val();

    let order_since_id = $('#s2w-order_since_id').val(),
        order_processed_at_min = $('#s2w-order_processed_at_min').val(),
        order_financial_status = $('#s2w-order_financial_status').val(),
        order_fulfillment_status = $('#s2w-order_fulfillment_status').val(),
        order_processed_at_max = $('#s2w-order_processed_at_max').val();

    function s2w_validate_data() {
        warning = '';
        let validate = true;
        let domain = $('#s2w-domain').val();
        if (!domain) {
            validate = false;
            warning += warning_empty_store;
        }
        if (!$('#s2w-api_key').val()) {
            validate = false;
            warning += warning_empty_api_key;
        }
        if (!$('#s2w-api_secret').val()) {
            validate = false;
            warning += warning_empty_api_secret;
        }
        return validate;
    }

    $('.s2w-delete-history').on('click', function () {
        if (!confirm('You are about to delete import history of selected elements. Continue?')) {
            return false;
        }
    });
    $('.s2w-save').on('click', function () {
        if (!s2w_validate_data()) {
            alert(warning);
            return;
        }
        if (import_active || save_active) {
            return;
        }
        save_active = true;
        product_status = $('#s2w-product_status').val();
        variable_sku = $('#s2w-variable_sku').val();
        product_categories = $('#s2w-product_categories').val();
        _s2w_nonce = $('#_s2w_nonce').val();
        domain = $('#s2w-domain').val();
        api_key = $('#s2w-api_key').val();
        api_secret = $('#s2w-api_secret').val();
        download_images = $('#s2w-download_images').prop('checked') ? 1 : 0;
        disable_background_process = $('#s2w-disable_background_process').prop('checked') ? 1 : 0;
        download_description_images = $('#s2w-download_description_images').prop('checked') ? 1 : 0;
        keep_slug = $('#s2w-keep_slug').prop('checked') ? 1 : 0;
        global_attributes = $('#s2w-global_attributes').prop('checked') ? 1 : 0;
        request_timeout = $('#s2w-request_timeout').val();
        products_per_request = $('#s2w-products_per_request').val();
        customers_per_request = $('#s2w-customers_per_request').val();
        blogs_update_if_exist = $('#s2w-blogs_update_if_exist').val();
        customers_role = $('#s2w-customers_role').val();
        customers_with_purchases_only = $('#s2w-customers_with_purchases_only').prop('checked') ? 1 : 0;
        coupons_per_request = $('#s2w-coupons_per_request').val();
        orders_per_request = $('#s2w-orders_per_request').val();
        order_import_sequence = $('#s2w-order_import_sequence').val();
        product_import_sequence = $('#s2w-product_import_sequence').val();

        product_since_id = $('#s2w-product_since_id').val();
        product_product_type = $('#s2w-product_product_type').val();
        product_vendor = $('#s2w-product_vendor').val();
        product_collection_id = $('#s2w-product_collection_id').val();
        product_created_at_min = $('#s2w-product_created_at_min').val();
        product_created_at_max = $('#s2w-product_created_at_max').val();
        product_published_at_min = $('#s2w-product_published_at_min').val();
        product_published_at_max = $('#s2w-product_published_at_max').val();

        order_since_id = $('#s2w-order_since_id').val();
        order_processed_at_min = $('#s2w-order_processed_at_min').val();
        order_financial_status = $('#s2w-order_financial_status').val();
        order_fulfillment_status = $('#s2w-order_fulfillment_status').val();
        order_processed_at_max = $('#s2w-order_processed_at_max').val();
        $('.s2w-order_status_mapping').map(function () {
            let order_select = $(this).find('select').eq(0);
            order_status_mapping[order_select.data('from_status')] = order_select.val();
        });
        let $button = $(this);
        $button.addClass('loading');
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_save_settings',
                _s2w_nonce: _s2w_nonce,
                step: 'save',
                domain: domain,
                api_key: api_key,
                api_secret: api_secret,
                download_images: download_images,
                disable_background_process: disable_background_process,
                download_description_images: download_description_images,
                keep_slug: keep_slug,
                global_attributes: global_attributes,
                product_status: product_status,
                variable_sku: variable_sku,
                product_categories: product_categories ? product_categories : [],
                order_status_mapping: order_status_mapping,
                request_timeout: request_timeout,
                products_per_request: products_per_request,
                customers_per_request: customers_per_request,
                blogs_update_if_exist: blogs_update_if_exist ? blogs_update_if_exist : [],
                customers_role: customers_role,
                customers_with_purchases_only: customers_with_purchases_only,
                coupons_per_request: coupons_per_request,
                orders_per_request: orders_per_request,
                order_import_sequence: order_import_sequence,
                product_import_sequence: product_import_sequence,

                product_since_id: product_since_id,
                product_product_type: product_product_type,
                product_vendor: product_vendor,
                product_collection_id: product_collection_id,
                product_created_at_min: product_created_at_min,
                product_created_at_max: product_created_at_max,
                product_published_at_min: product_published_at_min,
                product_published_at_max: product_published_at_max,

                order_since_id: order_since_id,
                order_processed_at_min: order_processed_at_min,
                order_financial_status: order_financial_status,
                order_fulfillment_status: order_fulfillment_status,
                order_processed_at_max: order_processed_at_max,
                auto_update_key: $('#auto-update-key').val(),
            },
            success: function (response) {
                total_products = parseInt(response.total_products);
                total_pages = response.total_pages;
                current_import_id = response.current_import_id;
                current_import_product = parseInt(response.current_import_product);
                current_import_page = response.current_import_page;

                total_orders = parseInt(response.total_orders);
                orders_total_pages = response.orders_total_pages;
                orders_current_import_id = response.orders_current_import_id;
                current_import_order = parseInt(response.current_import_order);
                orders_current_import_page = response.orders_current_import_page;

                total_customers = parseInt(response.total_customers);
                customers_total_pages = response.customers_total_pages;
                customers_current_import_id = response.customers_current_import_id;
                current_import_customer = parseInt(response.current_import_customer);
                customers_current_import_page = response.customers_current_import_page;

                total_coupons = parseInt(response.total_coupons);
                coupons_total_pages = response.coupons_total_pages;
                coupons_current_import_id = response.coupons_current_import_id;
                current_import_coupon = parseInt(response.current_import_coupon);
                coupons_current_import_page = response.coupons_current_import_page;

                imported_elements = response.imported_elements;
                save_active = false;
                $button.removeClass('loading');
                if (response.api_error) {
                    alert(response.api_error);
                    $('.s2w-import-container').hide();
                    $('.s2w-error-warning').show();
                } else if (response.validate) {
                    $('.s2w-import-element-enable').map(function () {
                        let element = $(this).data('element_name');

                        if (imported_elements[element] == 1) {
                            $(this).prop('checked', false);
                            $('.s2w-import-' + element.replace(/_/g, '-') + '-check-icon').addClass('green').removeClass('grey');
                        } else {
                            $(this).prop('checked', true);
                            $('.s2w-import-' + element.replace(/_/g, '-') + '-check-icon').addClass('grey').removeClass('green');
                        }
                    });
                    $('.s2w-import-container').show();
                    $('.s2w-error-warning').hide();
                    $('.s2w-accordion>.title').removeClass('active');
                    $('.s2w-accordion>.content').removeClass('active');
                }
            },
            error: function (err) {
                save_active = false;
                $button.removeClass('loading');
            }
        })
    });
    $('.s2w-sync').on('click', function () {
        if (!s2w_validate_data()) {
            alert(warning);
            return;
        }
        get_selected_elements();
        if (selected_elements.length == 0) {
            alert('Please select which data you want to import.');
            return;
        } else {
            let imported = [];
            for (let i in selected_elements) {
                let element = selected_elements[i];
                if (imported_elements[element] == 1) {
                    imported.push(elements_titles[element]);
                }
            }
            if (imported.length > 0) {
                if (!confirm('You already imported ' + imported.join(', ') + '. Do you want to continue?')) {
                    return;
                }
            }
        }
        let $button = $(this);
        if (import_active || save_active) {
            return;
        }
        $('.s2w-import-progress').css({'visibility': 'hidden'});
        for (let ele in progress_bars) {
            progress_bars[ele].css({'visibility': 'visible'});
            progress_bars[ele].progress('set label', 'Waiting...');
        }
        import_active = true;
        $button.addClass('loading');
        s2w_lock_buttons();
        s2w_jump_to_import();
        s2w_import_element();
    });

    function s2w_import_products() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'products',
                total_products: total_products,
                total_pages: total_pages,
                current_import_id: current_import_id,
                current_import_page: current_import_page,
                current_import_product: current_import_product,
                error_log: error_log,
            },
            success: function (response) {
                if (response.status === 'retry') {
                    total_products = parseInt(response.total_products);
                    total_pages = parseInt(response.total_pages);
                    current_import_id = response.current_import_id;
                    current_import_page = parseInt(response.current_import_page);
                    current_import_product = parseInt(response.current_import_product);
                    s2w_import_products();
                } else {
                    error_log = '';
                    progress_bars['products'].progress('set label', response.message.toString());
                    if (response.status === 'error') {
                        if (response.code === 'no_data') {
                            import_complete = true;
                            progress_bars['products'].progress('set error');
                            s2w_import_element();
                        } else if (parseInt(response.code) < 400) {
                            setTimeout(function () {
                                s2w_import_products();
                            }, 3000)
                        }
                    } else {
                        current_import_id = response.current_import_id;
                        current_import_page = parseInt(response.current_import_page);
                        current_import_product = parseInt(response.current_import_product);
                        let imported_products = parseInt(response.imported_products);
                        let percent = Math.ceil(imported_products * 100 / total_products);
                        if (percent > 100) {
                            percent = 100;
                        }
                        progress_bars['products'].progress('set percent', percent);
                        if (response.logs) {
                            $('.s2w-logs').append(response.logs).scrollTop($('.s2w-logs')[0].scrollHeight);
                        }
                        if (response.status === 'successful') {
                            if (current_import_page <= total_pages) {
                                s2w_import_products();
                            } else {
                                import_complete = true;

                                progress_bars['products'].progress('complete');
                                s2w_import_element();
                            }
                        } else {
                            import_complete = true;

                            progress_bars['products'].progress('complete');
                            s2w_import_element();
                        }
                    }
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['products'].progress('set error');
                if (!import_complete) {
                    selected_elements.unshift('products');
                }
                setTimeout(function () {
                    s2w_import_element();
                }, 3000);
            }
        })
    }

    let categories_current_page = 0;
    let total_categories = 0;

    function s2w_import_product_categories() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'product_categories',
                categories_current_page: categories_current_page,
                total_categories: total_categories,
            },
            success: function (response) {
                if (response.status === 'retry') {
                    categories_current_page = parseInt(response.categories_current_page);
                    total_categories = parseInt(response.total_categories);
                    s2w_import_product_categories();
                } else if (response.status === 'success') {
                    categories_current_page = parseInt(response.categories_current_page);
                    total_categories = parseInt(response.total_categories);
                    let percent = categories_current_page * 100 / total_categories;
                    progress_bars['product_categories'].progress('set percent', percent);
                    s2w_import_product_categories();
                } else if (response.status === 'error') {
                    progress_bars['product_categories'].progress('set label', response.message.toString());
                    progress_bars['product_categories'].progress('set error');
                    setTimeout(function () {
                        s2w_import_product_categories();
                    }, 2000)
                } else {
                    categories_current_page = parseInt(response.categories_current_page);
                    total_categories = parseInt(response.total_categories);
                    progress_bars['product_categories'].progress('set label', response.message.toString());
                    progress_bars['product_categories'].progress('complete');
                    s2w_import_element();
                }
            },
            error: function (err) {
                progress_bars['product_categories'].progress('set error');
                setTimeout(function () {
                    s2w_import_element();
                }, 3000)
            },
        });

    }

    let blogs_current_page = 0;
    let total_blogs = 0;

    function s2w_import_blogs() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'blogs',
                blogs_current_page: blogs_current_page,
                total_blogs: total_blogs,
            },
            success: function (response) {
                if (response.status === 'retry') {
                    blogs_current_page = parseInt(response.blogs_current_page);
                    total_blogs = parseInt(response.total_blogs);
                    s2w_import_blogs();
                } else {
                    if (response.status === 'success') {
                        blogs_current_page = parseInt(response.blogs_current_page);
                        total_blogs = parseInt(response.total_blogs);
                        let percent = blogs_current_page * 100 / total_blogs;
                        progress_bars['blogs'].progress('set percent', percent);
                        s2w_import_blogs();
                    } else if (response.status === 'error') {
                        progress_bars['blogs'].progress('set label', response.message.toString());
                        progress_bars['blogs'].progress('set error');
                        setTimeout(function () {
                            s2w_import_blogs();
                        }, 5000)
                    } else {
                        blogs_current_page = parseInt(response.blogs_current_page);
                        total_blogs = parseInt(response.total_blogs);
                        progress_bars['blogs'].progress('set label', response.message.toString());
                        progress_bars['blogs'].progress('complete');
                        s2w_import_element();
                    }
                    if (response.hasOwnProperty('logs') && response.logs) {
                        $('.s2w-logs').append(response.logs).scrollTop($('.s2w-logs')[0].scrollHeight);
                    }
                }
            },
            error: function (err) {
                progress_bars['blogs'].progress('set error');
                setTimeout(function () {
                    s2w_import_element();
                }, 5000)
            },
        });

    }

    function s2w_import_orders() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'orders',
                total_orders: total_orders,
                orders_total_pages: orders_total_pages,
                orders_current_import_id: orders_current_import_id,
                orders_current_import_page: orders_current_import_page,
                current_import_order: current_import_order,
                error_log: error_log,
            },
            success: function (response) {
                if (response.status === 'retry') {
                    total_orders = parseInt(response.total_orders);
                    orders_total_pages = parseInt(response.orders_total_pages);
                    orders_current_import_id = response.orders_current_import_id;
                    orders_current_import_page = parseInt(response.orders_current_import_page);
                    current_import_order = parseInt(response.current_import_order);
                    s2w_import_orders();
                } else {
                    error_log = '';
                    progress_bars['orders'].progress('set label', response.message.toString());
                    if (response.status === 'error') {
                        if (response.code === 'no_data') {
                            orders_import_complete = true;
                            progress_bars['orders'].progress('set error');
                            s2w_import_element();
                        } else if (parseInt(response.code) < 400) {
                            setTimeout(function () {
                                s2w_import_orders();
                            }, 9000)
                        }
                    } else {
                        orders_current_import_id = response.orders_current_import_id;
                        orders_current_import_page = parseInt(response.orders_current_import_page);
                        current_import_order = parseInt(response.current_import_order);
                        let imported_orders = parseInt(response.imported_orders);
                        let percent = Math.ceil(imported_orders * 100 / total_orders);
                        if (percent > 100) {
                            percent = 100;
                        }
                        progress_bars['orders'].progress('set percent', percent);
                        if (response.logs) {
                            $('.s2w-logs').append(response.logs).scrollTop($('.s2w-logs')[0].scrollHeight);
                        }
                        if (response.status === 'successful') {
                            if (orders_current_import_page <= orders_total_pages) {
                                s2w_import_orders();
                            } else {
                                orders_import_complete = true;
                                progress_bars['orders'].progress('complete');
                                s2w_import_element();
                            }
                        } else {
                            orders_import_complete = true;
                            progress_bars['orders'].progress('complete');
                            s2w_import_element();
                        }
                    }
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['orders'].progress('set error');
                if (!orders_import_complete) {
                    selected_elements.unshift('orders');
                }
                setTimeout(function () {
                    s2w_import_element();
                }, 9000)
            }
        })
    }

    function s2w_import_customers() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'customers',
                total_customers: total_customers,
                customers_total_pages: customers_total_pages,
                customers_current_import_id: customers_current_import_id,
                customers_current_import_page: customers_current_import_page,
                current_import_customer: current_import_customer,
                error_log: error_log,
            },
            success: function (response) {
                if (response.status === 'retry') {
                    total_customers = parseInt(response.total_customers);
                    customers_total_pages = parseInt(response.customers_total_pages);
                    customers_current_import_id = response.customers_current_import_id;
                    customers_current_import_page = parseInt(response.customers_current_import_page);
                    current_import_customer = parseInt(response.current_import_customer);
                    s2w_import_customers();
                } else {
                    progress_bars['customers'].progress('set label', response.message.toString());
                    error_log = '';
                    if (response.status === 'error') {
                        if (response.code === 'no_data') {
                            customers_import_complete = true;
                            progress_bars['customers'].progress('set error');
                            s2w_import_element();
                        } else if (parseInt(response.code) < 400) {
                            setTimeout(function () {
                                s2w_import_customers();
                            }, 3000)
                        }
                    } else {
                        customers_current_import_id = response.customers_current_import_id;
                        customers_current_import_page = parseInt(response.customers_current_import_page);
                        current_import_customer = parseInt(response.current_import_customer);
                        let imported_customers = parseInt(response.imported_customers);
                        let percent = Math.ceil(imported_customers * 100 / total_customers);
                        if (percent > 100) {
                            percent = 100;
                        }
                        progress_bars['customers'].progress('set percent', percent);
                        if (response.logs) {
                            $('.s2w-logs').append(response.logs).scrollTop($('.s2w-logs')[0].scrollHeight);
                        }
                        if (response.status === 'successful') {
                            if (customers_current_import_page <= customers_total_pages) {
                                s2w_import_customers();
                            } else {
                                customers_import_complete = true;
                                progress_bars['customers'].progress('complete');
                                s2w_import_element();
                            }
                        } else {
                            customers_import_complete = true;
                            progress_bars['customers'].progress('complete');
                            s2w_import_element();
                        }
                    }
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['customers'].progress('set error');
                if (!customers_import_complete) {
                    selected_elements.unshift('customers');
                }
                setTimeout(function () {
                    s2w_import_element();
                }, 3000)
            }
        })
    }

    function s2w_import_spages() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'pages',
                total_spages: total_spages,
                spages_total_pages: spages_total_pages,
                spages_current_import_id: spages_current_import_id,
                spages_current_import_page: spages_current_import_page,
                current_import_spage: current_import_spage,
                error_log: error_log,
            },
            success: function (response) {
                if (response.status === 'retry') {
                    total_spages = parseInt(response.total_spages);
                    spages_total_pages = parseInt(response.spages_total_pages);
                    spages_current_import_id = response.spages_current_import_id;
                    spages_current_import_page = parseInt(response.spages_current_import_page);
                    current_import_spage = parseInt(response.current_import_spage);
                    s2w_import_spages();
                } else {
                    progress_bars['pages'].progress('set label', response.message.toString());
                    error_log = '';
                    if (response.status === 'error') {
                        if (response.code === 'no_data') {
                            spages_import_complete = true;
                            progress_bars['pages'].progress('set error');
                            s2w_import_element();
                        } else if (parseInt(response.code) < 400) {
                            setTimeout(function () {
                                s2w_import_spages();
                            }, 3000)
                        }
                    } else {
                        spages_current_import_id = response.spages_current_import_id;
                        spages_current_import_page = parseInt(response.spages_current_import_page);
                        current_import_spage = parseInt(response.current_import_spage);
                        let imported_spages = parseInt(response.imported_spages);
                        let percent = Math.ceil(imported_spages * 100 / total_spages);
                        if (percent > 100) {
                            percent = 100;
                        }
                        progress_bars['pages'].progress('set percent', percent);
                        if (response.logs) {
                            $('.s2w-logs').append(response.logs).scrollTop($('.s2w-logs')[0].scrollHeight);
                        }
                        if (response.status === 'successful') {
                            if (spages_current_import_page <= spages_total_pages) {
                                s2w_import_spages();
                            } else {
                                spages_import_complete = true;
                                progress_bars['pages'].progress('complete');
                                s2w_import_element();
                            }
                        } else {
                            spages_import_complete = true;
                            progress_bars['pages'].progress('complete');
                            s2w_import_element();
                        }
                    }
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['pages'].progress('set error');
                if (!spages_import_complete) {
                    selected_elements.unshift('pages');
                }
                setTimeout(function () {
                    s2w_import_element();
                }, 3000)
            }
        })
    }

    function s2w_import_coupons() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'coupons',
                total_coupons: total_coupons,
                coupons_total_pages: coupons_total_pages,
                coupons_current_import_id: coupons_current_import_id,
                coupons_current_import_page: coupons_current_import_page,
                current_import_coupon: current_import_coupon,
                error_log: error_log,
            },
            success: function (response) {
                if (response.status === 'retry') {
                    total_coupons = parseInt(response.total_coupons);
                    coupons_total_pages = parseInt(response.coupons_total_pages);
                    coupons_current_import_id = response.coupons_current_import_id;
                    coupons_current_import_page = parseInt(response.coupons_current_import_page);
                    current_import_coupon = parseInt(response.current_import_coupon);
                    s2w_import_coupons();
                } else {
                    progress_bars['coupons'].progress('set label', response.message.toString());
                    error_log = '';
                    if (response.status === 'error') {
                        if (response.code === 'no_data') {
                            coupons_import_complete = true;
                            progress_bars['coupons'].progress('set error');
                            s2w_import_element();
                        } else if (parseInt(response.code) < 400) {
                            setTimeout(function () {
                                s2w_import_coupons();
                            }, 3000)
                        }
                    } else {
                        coupons_current_import_id = response.coupons_current_import_id;
                        coupons_current_import_page = parseInt(response.coupons_current_import_page);
                        current_import_coupon = parseInt(response.current_import_coupon);
                        let imported_coupons = parseInt(response.imported_coupons);
                        let percent = Math.ceil(imported_coupons * 100 / total_coupons);
                        if (percent > 100) {
                            percent = 100;
                        }
                        progress_bars['coupons'].progress('set percent', percent);
                        if (response.logs) {
                            $('.s2w-logs').append(response.logs).scrollTop($('.s2w-logs')[0].scrollHeight);
                        }
                        if (response.status === 'successful') {
                            if (coupons_current_import_page <= coupons_total_pages) {
                                s2w_import_coupons();
                            } else {
                                coupons_import_complete = true;
                                progress_bars['coupons'].progress('complete');
                                s2w_import_element();
                            }
                        } else {
                            coupons_import_complete = true;
                            progress_bars['coupons'].progress('complete');
                            s2w_import_element();
                        }
                    }
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['coupons'].progress('set error');
                if (!coupons_import_complete) {
                    selected_elements.unshift('coupons');
                }
                setTimeout(function () {
                    s2w_import_element();
                }, 3000)
            }
        })
    }

    function s2w_import_store_settings() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'store_settings',
                error_log: error_log,
            },
            success: function (response) {
                error_log = '';
                progress_bars['store_settings'].progress('set label', response.message.toString());
                if (response.status !== 'error') {
                    s2w_mark_imported('store_settings');
                    progress_bars['store_settings'].progress('complete');
                } else {
                    progress_bars['store_settings'].progress('set error');
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['store_settings'].progress('set label', error_log);
                progress_bars['store_settings'].progress('set error');
            },
            complete: function () {
                s2w_import_element();
            }
        })
    }

    function s2w_import_blogs1() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'blogs',
                error_log: error_log,
            },
            success: function (response) {
                error_log = '';
                progress_bars['blogs'].progress('set label', response.message.toString());
                if (response.status !== 'error') {
                    s2w_mark_imported('blogs');
                    progress_bars['blogs'].progress('complete');
                } else {
                    progress_bars['blogs'].progress('set error');
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['blogs'].progress('set label', error_log);
                progress_bars['blogs'].progress('set error');
            },
            complete: function () {
                s2w_import_element();
            }
        })
    }

    function s2w_import_shipping_zones() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'shipping_zones',
                error_log: error_log,
            },
            success: function (response) {
                error_log = '';
                progress_bars['shipping_zones'].progress('set label', response.message.toString());
                if (response.status !== 'error') {
                    progress_bars['shipping_zones'].progress('complete');
                    s2w_mark_imported('shipping_zones');
                } else {
                    progress_bars['shipping_zones'].progress('set error');
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['shipping_zones'].progress('set label', error_log);
                progress_bars['shipping_zones'].progress('set error');
            },
            complete: function () {
                s2w_import_element();
            }
        })
    }

    function s2w_import_taxes() {
        $.ajax({
            url: s2w_params_admin.url,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 's2w_import_shopify_to_woocommerce',
                _s2w_nonce: _s2w_nonce,
                step: 'taxes',
                error_log: error_log,
            },
            success: function (response) {
                error_log = '';
                progress_bars['taxes'].progress('set label', response.message.toString());
                if (response.status !== 'error') {
                    progress_bars['taxes'].progress('complete');
                    s2w_mark_imported('taxes');
                } else {
                    progress_bars['taxes'].progress('set error');
                }
            },
            error: function (err) {
                error_log = 'error ' + err.status + ' : ' + err.statusText;
                progress_bars['taxes'].progress('set label', error_log);
                progress_bars['taxes'].progress('set error');
            },
            complete: function () {
                s2w_import_element();
            }
        })
    }

    function s2w_lock_buttons() {
        $('.s2w-import-element-enable').prop('readonly', true);
    }

    function s2w_unlock_buttons() {
        $('.s2w-import-element-enable').prop('readonly', false);
    }

    function s2w_mark_imported(name) {
        imported_elements[name] = 1;
        $('.s2w-import-' + name.replace(/_/g, '-') + '-check-icon').removeClass('grey').addClass('green');
    }

    function s2w_jump_to_import() {
        $('html').prop('scrollTop', $('.s2w-import-container').prop('offsetTop'))
    }

    function s2w_product_options_close() {
        s2w_product_options_hide();
        $('#s2w-import-products-options').append($('.s2w-import-products-options-content'));
    }

    function s2w_product_options_hide() {
        $('.s2w-import-products-options-modal').addClass('s2w-hidden');
        s2w_enable_scroll();
    }

    function s2w_product_options_show() {
        $('.s2w-import-products-options-modal').removeClass('s2w-hidden');
        s2w_disable_scroll();
    }

    function s2w_order_options_close() {
        s2w_order_options_hide();
        $('#s2w-import-orders-options').append($('.s2w-import-orders-options-content'));
    }

    function s2w_order_options_hide() {
        $('.s2w-import-orders-options-modal').addClass('s2w-hidden');
        s2w_enable_scroll();
    }

    function s2w_order_options_show() {
        $('.s2w-import-orders-options-modal').removeClass('s2w-hidden');
        s2w_disable_scroll();
    }

    function s2w_coupon_options_close() {
        s2w_coupon_options_hide();
        $('#s2w-import-coupons-options').append($('.s2w-import-coupons-options-content'));
    }

    function s2w_coupon_options_hide() {
        $('.s2w-import-coupons-options-modal').addClass('s2w-hidden');
        s2w_enable_scroll();
    }

    function s2w_coupon_options_show() {
        $('.s2w-import-coupons-options-modal').removeClass('s2w-hidden');
        s2w_disable_scroll();
    }

    function s2w_enable_scroll() {
        let html = $('html');
        let scrollTop = parseInt(html.css('top'));
        html.removeClass('s2w-noscroll');
        $('html,body').scrollTop(-scrollTop);
    }

    function s2w_disable_scroll() {
        let html = $('html');
        if ($(document).height() > $(window).height()) {
            let scrollTop = (html.scrollTop()) ? html.scrollTop() : $('body').scrollTop(); // Works for Chrome, Firefox, IE...
            html.addClass('s2w-noscroll').css('top', -scrollTop);
        }
    }
});

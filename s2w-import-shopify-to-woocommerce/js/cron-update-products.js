'use strict';
jQuery(document).ready(function ($) {
    $('.vi-ui.dropdown').dropdown();
    $('input[name="s2w_save_cron_update_products"]').on('click', function (e) {
        if (!$('#s2w-cron_update_products_options').val()) {
            alert('Please select at least one option to update');
            e.preventDefault();
        }
        if (!$('#s2w-cron_update_products_status').val()) {
            alert('Please select product status you want to update');
            e.preventDefault();
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

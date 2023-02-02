<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_REST_ADMIN_VERSION', '2021-01' );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DIR . "admin" . DIRECTORY_SEPARATOR );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_LANGUAGES', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DIR . "languages" . DIRECTORY_SEPARATOR );
if ( ! defined( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE' ) ) {
	define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE', WP_CONTENT_DIR . "/cache/import-shopify-to-woocommerce/" );//use the same cache folder with free version
}
$plugin_url = plugins_url( '', __FILE__ );
$plugin_url = str_replace( '/includes', '', $plugin_url );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS', $plugin_url . "/css/" );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS_DIR', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DIR . "css" . DIRECTORY_SEPARATOR );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS', $plugin_url . "/js/" );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS_DIR', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DIR . "js" . DIRECTORY_SEPARATOR );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_IMAGES', $plugin_url . "/images/" );
define( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_IMAGES_DIR', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DIR . "/images/" );
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "data.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "data.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "support.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "support.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "update.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "update.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "check_update.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "check_update.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "wp-async-request.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "wp-async-request.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "wp-background-process.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "wp-background-process.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "functions.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "functions.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-new.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-new.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-for-import-csv.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-for-import-csv.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-for-update.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-for-update.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-single-new.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-single-new.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-post-image.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-post-image.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-products.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-products.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-products-get-data.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-products-get-data.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-orders.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-orders.php";
}
if ( is_file( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-orders-get-data.php" ) ) {
	require_once VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_INCLUDES . "class-s2w-process-cron-update-orders-get-data.php";
}
vi_include_folder( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN, 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_' );

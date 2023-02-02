<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Users' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Users {
		protected $settings;
		protected $is_page;
		protected $request;
		protected $process;
		protected $gmt_offset;

		public function __construct() {
			$this->settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
//			add_filter( 'views_users', array( $this, 'imported_by_s2w' ) );
		}

		public static function set( $name, $set_name = false ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
		}

		public function imported_by_s2w( $views ) {
			$count                    = count( get_users( array(
					'meta_key'    => '_s2w_shopify_customer_id',
					'count_total' => true,
				) )
			);
			$views['imported_by_s2w'] = '<a href="' . add_query_arg( array( 'imported_by_s2w' => 1 ), remove_query_arg( 'role' ) ) . '">' . esc_html__( 'Imported by S2W' ) . '<span class="count">(' . $count . ')</span></a>';

			return $views;
		}
	}
}
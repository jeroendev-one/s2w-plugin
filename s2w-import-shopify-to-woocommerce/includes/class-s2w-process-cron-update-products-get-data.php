<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Products_Get_Data extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_cron_update_products_get_data';

	/**
	 * Task
	 *
	 * Override this method to perform any actions required on each
	 * queue item. Return the modified item for further processing
	 * in the next pass through. Or, return false to remove the
	 * item from the queue.
	 *
	 * @param mixed $item Queue item to iterate over
	 *
	 * @return mixed
	 */
	protected function task( $item ) {
		$data = isset( $item['data'] ) ? $item['data'] : array();

		if ( is_array( $data ) && count( $data ) ) {
			try {
				$settings   = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
				$domain     = $settings->get_params( 'domain' );
				$api_key    = $settings->get_params( 'api_key' );
				$api_secret = $settings->get_params( 'api_secret' );
				if ( $domain && $api_key && $api_secret ) {
					vi_s2w_init_set();
					$path = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $api_key, $api_secret ) . '/';
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::create_cache_folder( $path );
					$file             = $path . 'update_products_data.txt';
					$log_file         = $path . 'cron_update_products_logs.txt';
					$old_product_data = array();
					if ( is_file( $file ) ) {
						$old_product_data = file_get_contents( $file );
						if ( $old_product_data ) {
							$old_product_data = vi_s2w_json_decode( $old_product_data );
						}
					}
					add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );
					$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $api_key, $api_secret, 'products', false, array(
						'fields' => array(
							'id',
							'options',
							'variants',
						),
						'ids'    => array_values( $data )
					) );
					if ( $request['status'] === 'success' ) {
						$products = $request['data'];
						if ( is_array( $products ) && count( $products ) ) {
							$change = 0;
							foreach ( $products as $product ) {
								$shopify_product_id = strval( $product['id'] );
								$variants           = $product['variants'];
								$new_data_get       = array( 'variants' => $variants );
								$product_id         = array_search( $shopify_product_id, $data );
								if ( $product_id !== false ) {
									if ( array_key_exists( $shopify_product_id, $old_product_data ) ) {
										if ( json_encode( $old_product_data[ $shopify_product_id ] ) !== json_encode( $new_data_get ) ) {
											$old_product_data[ $shopify_product_id ] = $new_data_get;
											$this->queue_item_to_update( $product_id, $shopify_product_id, $variants );
											$change ++;
										}
									} else {
										$old_product_data[ $shopify_product_id ] = $new_data_get;
										$this->queue_item_to_update( $product_id, $shopify_product_id, $variants );
										$change ++;
									}
								}
							}
							if ( $change > 0 ) {
								file_put_contents( $file, json_encode( $old_product_data ) );
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products::$update_products->save()->dispatch();
							}
						} else {
							VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::log( $log_file, 'Error: No data' );
						}
					} else {
						VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::log( $log_file, 'Error: ' . $request['data'] );
					}
				}
			} catch ( Exception $e ) {
				error_log( 'S2W error log - cron get data to update products: ' . $e->getMessage() );

				return false;
			}
		}

		return false;
	}

	public function queue_item_to_update( $product_id, $shopify_product_id, $variants ) {
		$new_data = array(
			'product_id' => $product_id,
			'shopify_id' => $shopify_product_id,
			'variants'   => json_encode( $variants ),
		);

		S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Products::$update_products->push_to_queue( $new_data );
	}

	/**
	 * Is the updater running?
	 *
	 * @return boolean
	 */
	public function is_downloading() {
		return $this->is_process_running();
	}

	/**
	 * Complete
	 *
	 * Override if applicable, but ensure that the below actions are
	 * performed, or, call parent::complete().
	 */
	protected function complete() {
		// Show notice to user or perform some other arbitrary task...
		parent::complete();
	}

	/**
	 * Delete all batches.
	 *
	 * @return WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Products_Get_Data
	 */
	public function delete_all_batches() {
		global $wpdb;

		$table  = $wpdb->options;
		$column = 'option_name';

		if ( is_multisite() ) {
			$table  = $wpdb->sitemeta;
			$column = 'meta_key';
		}

		$key = $wpdb->esc_like( $this->identifier . '_batch_' ) . '%';

		$wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE {$column} LIKE %s", $key ) ); // @codingStandardsIgnoreLine.

		return $this;
	}

	/**
	 * Kill process.
	 *
	 * Stop processing queue items, clear cronjob and delete all batches.
	 */
	public function kill_process() {
		if ( ! $this->is_queue_empty() ) {
			$this->delete_all_batches();
			wp_clear_scheduled_hook( $this->cron_hook_identifier );
		}
	}

	public function bump_request_timeout( $val ) {
		return 600;
	}
}
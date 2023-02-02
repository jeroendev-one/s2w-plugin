<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_cron_update_orders';

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
		$order_id         = isset( $item['order_id'] ) ? $item['order_id'] : '';
		$billing_address  = isset( $item['billing_address'] ) ? vi_s2w_json_decode( $item['billing_address']) : array();
		$shipping_address = isset( $item['shipping_address'] ) ? vi_s2w_json_decode( $item['shipping_address'] ) : array();
		$fulfillments     = isset( $item['fulfillments'] ) ? vi_s2w_json_decode( $item['fulfillments']) : array();
		$financial_status = isset( $item['financial_status'] ) ? $item['financial_status'] : '';
		$email            = isset( $item['email'] ) ? $item['email'] : '';

		if ( $order_id ) {
			try {
				$settings   = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
				$domain     = $settings->get_params( 'domain' );
				$api_key    = $settings->get_params( 'api_key' );
				$api_secret = $settings->get_params( 'api_secret' );
				if ( $domain && $api_key && $api_secret ) {
					$path                       = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $api_key, $api_secret ) . '/';
					$log_file                   = $path . 'cron_update_orders_logs.txt';
					$cron_update_orders_options = $settings->get_params( 'cron_update_orders_options' );
					if ( is_array( $cron_update_orders_options ) && count( $cron_update_orders_options ) ) {
						$order_obj = wc_get_order( $order_id );
						if ( $order_obj ) {
							VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::log( $log_file, "Cron update order #'{$order_id}'." );
							$update_data = array();
							if ( in_array( 'status', $cron_update_orders_options ) ) {
								$order_status_mapping = $settings->get_params( 'order_status_mapping' );
								$order_status         = isset( $order_status_mapping[ $financial_status ] ) ? ( 'wc-' . $order_status_mapping[ $financial_status ] ) : '';
								if ( $order_status ) {
									$update_data['post_status'] = $order_status;
									$update_data['ID']          = $order_id;
									wp_update_post( $update_data );
								}
							}
							if ( in_array( 'fulfillments', $cron_update_orders_options ) && $fulfillments ) {
								update_post_meta( $order_id, '_s2w_shopify_order_fulfillments', $fulfillments );
							}
							$data = array();
							if ( in_array( 'billing_address', $cron_update_orders_options ) && $billing_address ) {
								$data = array_merge( array(
									'billing_first_name' => isset( $billing_address['first_name'] ) ? $billing_address['first_name'] : '',
									'billing_last_name'  => isset( $billing_address['last_name'] ) ? $billing_address['last_name'] : '',
									'billing_company'    => isset( $billing_address['company'] ) ? $billing_address['company'] : '',
									'billing_country'    => isset( $billing_address['country'] ) ? $billing_address['country'] : '',
									'billing_address_1'  => isset( $billing_address['address1'] ) ? $billing_address['address1'] : '',
									'billing_address_2'  => isset( $billing_address['address2'] ) ? $billing_address['address2'] : '',
									'billing_postcode'   => isset( $billing_address['zip'] ) ? $billing_address['zip'] : '',
									'billing_city'       => isset( $billing_address['city'] ) ? $billing_address['city'] : '',
									'billing_state'      => isset( $billing_address['province'] ) ? $billing_address['province'] : '',
									'billing_phone'      => isset( $billing_address['phone'] ) ? $billing_address['phone'] : '',
									'billing_email'      => $email,
								), $data );
							}
							if ( in_array( 'shipping_address', $cron_update_orders_options ) && $shipping_address ) {
								$data = array_merge( array(
									'shipping_first_name' => isset( $shipping_address['first_name'] ) ? $shipping_address['first_name'] : '',
									'shipping_last_name'  => isset( $shipping_address['last_name'] ) ? $shipping_address['last_name'] : '',
									'shipping_company'    => isset( $shipping_address['company'] ) ? $shipping_address['company'] : '',
									'shipping_country'    => isset( $shipping_address['country'] ) ? $shipping_address['country'] : '',
									'shipping_address_1'  => isset( $shipping_address['address1'] ) ? $shipping_address['address1'] : '',
									'shipping_address_2'  => isset( $shipping_address['address2'] ) ? $shipping_address['address2'] : '',
									'shipping_postcode'   => isset( $shipping_address['zip'] ) ? $shipping_address['zip'] : '',
									'shipping_city'       => isset( $shipping_address['city'] ) ? $shipping_address['city'] : '',
									'shipping_state'      => isset( $shipping_address['province'] ) ? $shipping_address['province'] : '',
								), $data );
							}
							if ( count( $data ) ) {
								foreach ( $data as $key => $value ) {
									if ( is_callable( array( $order_obj, "set_{$key}" ) ) && $value ) {
										$order_obj->{"set_{$key}"}( $value );
										// Store custom fields prefixed with wither shipping_ or billing_. This is for backwards compatibility with 2.6.x.
									} elseif ( isset( $fields_prefix[ current( explode( '_', $key ) ) ] ) ) {
										if ( ! isset( $shipping_fields[ $key ] ) ) {
											$order_obj->update_meta_data( '_' . $key, $value );
										}
									}
								}
								$order_obj->save();
							}
						}
					}
				}
			} catch ( Exception $e ) {
				error_log( 'S2W error log - cron update orders: ' . $e->getMessage() );

				return false;
			}
		}

		return false;
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
		if ( ! $this->is_process_running() && $this->is_queue_empty() ) {
			$settings   = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			$domain     = $settings->get_params( 'domain' );
			$api_key    = $settings->get_params( 'api_key' );
			$api_secret = $settings->get_params( 'api_secret' );
			if ( $domain && $api_key && $api_secret ) {
				$path     = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_cache_path( $domain, $api_key, $api_secret ) . '/';
				$log_file = $path . 'cron_update_orders_logs.txt';
				VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::log( $log_file, 'Cron update orders finished.' . PHP_EOL );
				set_transient( 's2w_background_processing_cron_update_orders_complete', time() );
			}
		}
		// Show notice to user or perform some other arbitrary task...
		parent::complete();
	}

	/**
	 * Delete all batches.
	 *
	 * @return WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders
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
}
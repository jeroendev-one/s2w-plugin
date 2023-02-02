<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Orders' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Update_Orders {
		protected $settings;
		protected $is_page;
		protected $request;
		protected $process;
		protected $gmt_offset;

		public function __construct() {
			$this->settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_script' ) );
			add_filter( 'manage_edit-shop_order_columns', array( $this, 'button_update_from_shopify' ) );
			add_action( 'manage_shop_order_posts_custom_column', array( $this, 'column_callback_order' ) );
			add_action( 'wp_ajax_s2w_update_orders', array( $this, 'update_orders' ) );
			add_action( 'wp_ajax_s2w_update_order_options_save', array( $this, 'save_options' ) );
		}

		public static function set( $name, $set_name = false ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
		}

		public function button_update_from_shopify( $cols ) {
			$cols['s2w_update_from_shopify'] = '<span class="s2w-button ' . self::set( 'shopify-update-order' ) . '">' . __( 'Update from Shopify', 's2w-import-shopify-to-woocommerce' ) . '</span>';

			return $cols;
		}

		public function column_callback_order( $col ) {
			global $post;
			if ( $col === 's2w_update_from_shopify' ) {
				$all_options = array(
					'order_status'     => esc_html__( 'Order status', 's2w-import-shopify-to-woocommerce' ),
					'order_date'       => esc_html__( 'Order date', 's2w-import-shopify-to-woocommerce' ),
					'fulfillments'     => esc_html__( 'Fulfillments', 's2w-import-shopify-to-woocommerce' ),
					'billing_address'  => esc_html__( 'Billing address', 's2w-import-shopify-to-woocommerce' ),
					'shipping_address' => esc_html__( 'Shipping address', 's2w-import-shopify-to-woocommerce' ),
					'line_items'       => esc_html__( 'Line items', 's2w-import-shopify-to-woocommerce' ),
					'customer'         => esc_html__( 'Customer', 's2w-import-shopify-to-woocommerce' ),
				);
				if ( null === $this->gmt_offset ) {
					$this->gmt_offset = get_option( 'gmt_offset' );
				}
				$post_id        = $post->ID;
				$shopify_id     = get_post_meta( $post_id, '_s2w_shopify_order_id', true );
				$update_history = get_post_meta( $post_id, '_s2w_update_order_history', true );
				if ( $shopify_id ) {
					?>
                    <div class="<?php echo esc_attr( self::set( 'update-from-shopify-history' ) ) ?>">
						<?php
						if ( $update_history ) {
							$update_time        = isset( $update_history['time'] ) ? $update_history['time'] : '';
							$update_status      = isset( $update_history['status'] ) ? $update_history['status'] : '';
							$update_fields      = isset( $update_history['fields'] ) ? $update_history['fields'] : array();
							$update_fields_html = array();
							if ( is_array( $update_fields ) && count( $update_fields ) ) {
								foreach ( $update_fields as $update_field ) {
									$update_fields_html[] = $all_options[ $update_field ];
								}
								$update_fields_html = implode( ', ', $update_fields_html );
							}
							?>
                            <p><?php esc_html_e( 'Last update: ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-time' ) ) ?>"><?php echo esc_html( date_i18n( 'F d, Y H:i:s', $update_time + $this->gmt_offset * 3600 ) ) ?></span></strong>
                            </p>
                            <p><?php esc_html_e( 'Status: ', 's2w-import-shopify-to-woocommerce' ) ?><strong><span
                                            class="<?php echo esc_attr( self::set( array(
												'update-from-shopify-history-status',
												'update-from-shopify-history-status-' . $update_status
											) ) ) ?>"><?php echo esc_html( ucwords( $update_status ) ) ?></span></strong>
                            </p>
                            <p><?php esc_html_e( 'Update field(s): ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-fields' ) ) ?>"><?php echo $update_fields_html ?></span></strong>
                            </p>
							<?php

						} else {
							?>
                            <p><?php esc_html_e( 'Last update: ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-time' ) ) ?>"></span></strong>
                            </p>
                            <p><?php esc_html_e( 'Status: ', 's2w-import-shopify-to-woocommerce' ) ?><strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-status' ) ) ?>"></span></strong>
                            </p>
                            <p><?php esc_html_e( 'Update field(s): ', 's2w-import-shopify-to-woocommerce' ) ?>
                                <strong><span
                                            class="<?php echo esc_attr( self::set( 'update-from-shopify-history-fields' ) ) ?>"></span></strong>
                            </p>
							<?php
						}
						?>
                    </div>
                    <span class="s2w-button <?php echo esc_attr( self::set( 'shopify-order-id' ) ) ?>"
                          data-order_id="<?php echo esc_attr( $post_id ) ?>"
                          data-shopify_order_id="<?php echo esc_attr( $shopify_id ) ?>"><?php esc_html_e( 'Update', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
					<?php
				}
			}
		}

		public function save_options() {
			$update_order_options                  = isset( $_POST['update_order_options'] ) ? stripslashes_deep( $_POST['update_order_options'] ) : array();
			$update_order_options_show             = isset( $_POST['update_order_options_show'] ) ? sanitize_text_field( $_POST['update_order_options_show'] ) : '';
			$settings                              = $this->settings->get_params();
			$settings['update_order_options']      = $update_order_options;
			$settings['update_order_options_show'] = $update_order_options_show;
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::update_option( 's2w_params', $settings );
			wp_send_json( array(
				'status' => 'success'
			) );
		}

		/**
		 * @throws WC_Data_Exception
		 */
		public function update_orders() {
			$gmt_offset    = get_option( 'gmt_offset' );
			$order_id      = isset( $_POST['order_id'] ) ? sanitize_text_field( $_POST['order_id'] ) : '';
			$order         = wc_get_order( $order_id );
			$update_fields = $this->settings->get_params( 'update_order_options' );
			ignore_user_abort( true );
			if ( isset( $_POST['update_order_options'] ) ) {
				$update_fields                         = $_POST['update_order_options'];
				$settings                              = $this->settings->get_params();
				$settings['update_order_options']      = $update_fields;
				$settings['update_order_options_show'] = isset( $_POST['update_order_options_show'] ) ? sanitize_text_field( $_POST['update_order_options_show'] ) : '';
				VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::update_option( 's2w_params', $settings );
			}
			$update_history = array(
				'time'    => current_time( 'timestamp', true ),
				'status'  => 'error',
				'fields'  => $update_fields,
				'message' => '',
			);
			$all_options    = array(
				'order_status'     => esc_html__( 'Order status', 's2w-import-shopify-to-woocommerce' ),
				'order_date'       => esc_html__( 'Order date', 's2w-import-shopify-to-woocommerce' ),
				'fulfillments'     => esc_html__( 'Fulfillments', 's2w-import-shopify-to-woocommerce' ),
				'billing_address'  => esc_html__( 'Billing address', 's2w-import-shopify-to-woocommerce' ),
				'shipping_address' => esc_html__( 'Shipping address', 's2w-import-shopify-to-woocommerce' ),
				'line_items'       => esc_html__( 'Line items', 's2w-import-shopify-to-woocommerce' ),
				'customer'         => esc_html__( 'Customer', 's2w-import-shopify-to-woocommerce' ),
			);
			$fields         = array();
			foreach ( $update_fields as $update_field ) {
				$fields[] = $all_options[ $update_field ];
			}
			$fields = implode( ', ', $fields );
			if ( $order ) {
				$domain     = $this->settings->get_params( 'domain' );
				$api_key    = $this->settings->get_params( 'api_key' );
				$api_secret = $this->settings->get_params( 'api_secret' );
				$shopify_id = get_post_meta( $order_id, '_s2w_shopify_order_id', true );
				if ( $shopify_id ) {
					add_filter( 'http_request_timeout', array( $this, 'bump_request_timeout' ) );
					$request = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::wp_remote_get( $domain, $api_key, $api_secret, 'orders', false, array( 'ids' => $shopify_id ) );
					if ( $request['status'] === 'success' ) {
						$order_data = $request['data'];
						if ( count( $order_data ) ) {
//						    remove_all_actions('woocommerce_before_order_object_save');
//						    remove_all_actions('woocommerce_after_order_object_save');
							$billing_address  = isset( $order_data['billing_address'] ) ? $order_data['billing_address'] : array();
							$shipping_address = isset( $order_data['shipping_address'] ) ? $order_data['shipping_address'] : array();
							$new_data         = array();
							$update_data      = array();
							if ( in_array( 'order_status', $update_fields ) ) {
								$order_status_mapping = $this->settings->get_params( 'order_status_mapping' );
								if ( ! is_array( $order_status_mapping ) || ! count( $order_status_mapping ) ) {
									$order_status_mapping = $this->settings->get_default( 'order_status_mapping' );
								}
								$financial_status = isset( $order_data['financial_status'] ) ? $order_data['financial_status'] : '';
								$order_status     = isset( $order_status_mapping[ $financial_status ] ) ? ( 'wc-' . $order_status_mapping[ $financial_status ] ) : '';

								if ( $order_status ) {
									$update_data['post_status'] = $order_status;
									$new_data['order_status']   = '<mark class="order-status status-' . $order_status_mapping[ $financial_status ] . ' tips"><span>' . ucwords( $order_status_mapping[ $financial_status ] ) . '</span></mark>';
								}
							}
							if ( in_array( 'order_date', $update_fields ) ) {
								$processed_at = $order_data['processed_at'];
								if ( $processed_at ) {
									$processed_at_gmt                 = strtotime( $processed_at );
									$date_gmt                         = date( 'Y-m-d H:i:s', $processed_at_gmt );
									$date                             = date( 'Y-m-d H:i:s', ( $processed_at_gmt + $gmt_offset * 3600 ) );
									$update_data['post_date']         = $date;
									$update_data['post_date_gmt']     = $date_gmt;
									$update_data['post_modified']     = $date;
									$update_data['post_modified_gmt'] = $date_gmt;
									$new_data['order_date']           = '<time datetime="' . date_i18n( 'Y-m-d\TH:i:s', strtotime( $date ) ) . '+00:00' . '" title="' . date_i18n( 'M d, Y h:i A', strtotime( $date ) ) . '">' . date_i18n( 'M d, Y', strtotime( $date ) ) . '</time>';
								}
							}
							if ( in_array( 'fulfillments', $update_fields ) ) {
								$shopify_order_fulfillments = isset( $order_data['fulfillments'] ) ? $order_data['fulfillments'] : array();
								if ( $shopify_order_fulfillments ) {
									update_post_meta( $order_id, '_s2w_shopify_order_fulfillments', $shopify_order_fulfillments );
								}
							}
							if ( in_array( 'line_items', $update_fields ) ) {
								$product_line_items  = array_keys( $order->get_items( 'line_item' ) );
								$shipping_line_items = array_keys( $order->get_items( 'shipping' ) );
								$tax_line_items      = array_keys( $order->get_items( 'tax' ) );
								$coupon_line_items   = array_keys( $order->get_items( 'coupon' ) );
								$order_total         = $order_data['total_price'];
								$order_total_tax     = $order_data['total_tax'];
								$total_discounts     = $order_data['total_discounts'];
								$total_shipping      = isset( $order_data['total_shipping_price_set']['shop_money']['amount'] ) ? floatval( $order_data['total_shipping_price_set']['shop_money']['amount'] ) : 0;
								$shipping_lines      = $order_data['shipping_lines'];
								$discount_codes      = $order_data['discount_codes'];
								$order->set_currency( ! empty( $order_data['currency'] ) ? $order_data['currency'] : get_woocommerce_currency() );
								$order->set_prices_include_tax( $order_data['taxes_included'] );
								$order->set_shipping_total( $total_shipping );
								$order->set_discount_total( $total_discounts );
								//      set discount tax
								$order->set_cart_tax( $order_total_tax );
								if ( isset( $shipping_lines['tax_lines']['price'] ) && $shipping_lines['tax_lines']['price'] ) {
									$order->set_shipping_tax( $shipping_lines['tax_lines']['price'] );
								}
								$order->set_total( $order_total );

								/*create order line items*/
								$line_items = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::validate_line_items( $order_data['line_items'] );
								self::remove_order_item( $product_line_items, $line_items, $order );
								$line_items_ids = array();
								foreach ( $line_items as $line_item_k => $line_item ) {
									$item                 = isset( $product_line_items[ $line_item_k ] ) ? new WC_Order_Item_Product( $product_line_items[ $line_item_k ] ) : new WC_Order_Item_Product();
									$shopify_product_id   = $line_item['product_id'];
									$shopify_variation_id = $line_item['variant_id'];
									$sku                  = $line_item['sku'];
									$product_id           = '';
									if ( $shopify_variation_id ) {
										$found_variation_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_variation_id, true );
										if ( $found_variation_id ) {
											$product_id = $found_variation_id;
										}
									}
									if ( ! $product_id && $shopify_product_id ) {
										$found_product_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::product_get_woo_id_by_shopify_id( $shopify_product_id );
										if ( $found_product_id ) {
											$product_id = $found_product_id;
										}
									}
									if ( ! $product_id && $sku ) {
										$product_id = wc_get_product_id_by_sku( $sku );
									}
									$item->set_props(
										array(
											'quantity' => $line_item['quantity'],
											'subtotal' => $line_item['price'],
											'total'    => intval( $line_item['quantity'] ) * $line_item['price'],
											'name'     => $line_item['name'],
										)
									);
									if ( is_array( $line_item['tax_lines'] ) && count( $line_item['tax_lines'] ) ) {
										$line_item_tax = 0;
										$taxes         = array(
											'subtotal' => array(),
											'total'    => array(),
										);
										foreach ( $line_item['tax_lines'] as $line_item_tax_line ) {
											$line_item_tax       += floatval( $line_item_tax_line['price'] );
											$taxes['subtotal'][] = $line_item_tax_line['price'];
											$taxes['total'][]    = $line_item_tax_line['price'];
										}
										$item->set_props(
											array(
												'subtotal_tax' => $line_item_tax,
												'total_tax'    => $line_item_tax,
												'taxes'        => $taxes,
											)
										);
									}
									if ( $product_id ) {
										$product = wc_get_product( $product_id );
										if ( $product ) {
											$item->set_props(
												array(
													'name'         => $product->get_name(),
													'tax_class'    => $product->get_tax_class(),
													'product_id'   => $product->is_type( 'variation' ) ? $product->get_parent_id() : $product->get_id(),
													'variation_id' => $product->is_type( 'variation' ) ? $product->get_id() : 0,
													'variation'    => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
												)
											);
										}
									}
									$item_id = $item->save();
									$order->add_item( $item );
									$line_items_ids[ $item_id ] = array(
										'variant_id' => $shopify_variation_id,
										'product_id' => $shopify_product_id,
									);
								}

								$refund_items = $order->get_refunds();
								$refunds      = $order_data['refunds'];
								S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::process_refunds( $refund_items, $refunds, $order_id, $line_items_ids );
								//create order shipping line
								$shipping_line_items_count = count( $shipping_line_items );
								if ( $shipping_line_items_count > 1 ) {
									$item = new WC_Order_Item_Shipping( $shipping_line_items[0] );
									for ( $temp = 1; $temp < $shipping_line_items_count; $temp ++ ) {
										$order->remove_item( $shipping_line_items[ $temp ] );
									}
								} elseif ( $shipping_line_items_count > 0 ) {
									$item = new WC_Order_Item_Shipping( $shipping_line_items[0] );
								} else {
									$item = new WC_Order_Item_Shipping();
								}
								if ( is_array( $shipping_lines ) && count( $shipping_lines ) ) {
									foreach ( $shipping_lines as $shipping_line ) {
										$item->set_props(
											array(
												'method_title' => $shipping_line['title'],
												'method_id'    => floatval( $shipping_line['price'] ) > 0 ? 'flat_rate' : 'free_shipping',
												'total'        => $shipping_line['price'],
											)
										);
										if ( is_array( $shipping_line['tax_lines'] ) && count( $shipping_line['tax_lines'] ) ) {
											$shipping_line_tax = array();
											foreach ( $shipping_line['tax_lines'] as $shipping_line_tax_line ) {
												$shipping_line_tax[] = $shipping_line_tax_line['price'];
											}
											$item->set_props(
												array(
													'taxes' => array( 'total' => $shipping_line_tax ),
												)
											);
										}
									}
								} else {
									$item->set_props(
										array(
											'method_title' => isset( $shipping_lines[0]['title'] ) ? $shipping_lines[0]['title'] : ( $total_shipping ? 'Flat rate' : 'Free shipping' ),
											'method_id'    => $total_shipping ? 'flat_rate' : 'free_shipping',
											'total'        => $total_shipping,
										)
									);
								}
								$item->save();
								$order->add_item( $item );
//				create order tax lines
								$tax_lines = $order_data['tax_lines'];
								self::remove_order_item( $tax_line_items, $tax_lines, $order );
								if ( is_array( $tax_lines ) && count( $tax_lines ) ) {
									foreach ( $tax_lines as $tax_line_k => $tax_line ) {
										$item = isset( $tax_line_items[ $tax_line_k ] ) ? new WC_Order_Item_Tax( $tax_line_items[ $tax_line_k ] ) : new WC_Order_Item_Tax();
										$item->set_props(
											array(
												'tax_total' => $tax_line['price'],
												'label'     => $tax_line['title'],
											)
										);
										$item->save();
										$order->add_item( $item );
									}
								}

//				create order coupon lines
								self::remove_order_item( $coupon_line_items, $discount_codes, $order );
								if ( is_array( $discount_codes ) && count( $discount_codes ) ) {
									foreach ( $discount_codes as $discount_code_k => $discount_code ) {
										$item = isset( $coupon_line_items[ $discount_code_k ] ) ? new WC_Order_Item_Coupon( $coupon_line_items[ $discount_code_k ] ) : new WC_Order_Item_Coupon();
										$item->set_props(
											array(
												'code'     => $discount_code['code'],
												'discount' => $discount_code['amount'],
											)
										);
										$item->save();
										$order->add_item( $item );
									}
								}
								$order->save();
							}
							if ( count( $update_data ) ) {
								$update_data['ID'] = $order_id;
								wp_update_post( $update_data );
							}
							$data = array();
							if ( in_array( 'billing_address', $update_fields ) && $billing_address ) {
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
									'billing_email'      => S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::get_billing_email($order_data),
								), $data );
							}

							if ( in_array( 'shipping_address', $update_fields ) && $shipping_address ) {
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
							if ( in_array( 'customer', $update_fields ) ) {
							    $customer_id = S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::get_customer_id( $order_data );
								if ( $customer_id ) {
									$order->set_customer_id( $customer_id );
									$order->save();
								}
							}
							if ( count( $data ) ) {
								foreach ( $data as $key => $value ) {
									if ( is_callable( array( $order, "set_{$key}" ) ) && $value ) {
										$order->{"set_{$key}"}( $value );
										// Store custom fields prefixed with wither shipping_ or billing_. This is for backwards compatibility with 2.6.x.
									} elseif ( isset( $fields_prefix[ current( explode( '_', $key ) ) ] ) ) {
										if ( ! isset( $shipping_fields[ $key ] ) ) {
											$order->update_meta_data( '_' . $key, $value );
										}
									}
								}
								$order->save();
							}
							$update_history['status']  = 'success';
							$update_history['message'] = '';
							update_post_meta( $order_id, '_s2w_update_order_history', $update_history );
							$response           = $update_history;
							$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
							$response['fields'] = $fields;
							wp_send_json( array_merge( $response, $new_data ) );
						} else {
							$update_history['status']  = 'error';
							$update_history['message'] = esc_html__( 'Not found', 's2w-import-shopify-to-woocommerce' );
							update_post_meta( $order_id, '_s2w_update_order_history', $update_history );
							$response           = $update_history;
							$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
							$response['fields'] = $fields;
							wp_send_json( $response );
						}
					} else {
						$update_history['status']  = 'error';
						$update_history['message'] = $request['data'];
						update_post_meta( $order_id, '_s2w_update_order_history', $update_history );
						$response           = $update_history;
						$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
						$response['fields'] = $fields;
						wp_send_json( $response );
					}
				}
				$response           = $update_history;
				$response['time']   = date_i18n( 'F d, Y H:i:s', $response['time'] + $gmt_offset * 3600 );
				$response['fields'] = $fields;
				wp_send_json( $response );

			} else {
				wp_send_json( array(
					'status'  => 'error',
					'message' => ''
				) );
			}
		}

		/**
		 * @param $current_line_items
		 * @param $line_items
		 * @param $order WC_Order
		 */
		public static function remove_order_item( $current_line_items, $line_items, &$order ) {
			if ( count( $current_line_items ) > $line_items_count = count( $line_items ) ) {
				$removed_items = array_splice( $current_line_items, $line_items_count );
				foreach ( $removed_items as $item_id ) {
					$order->remove_item( $item_id );
				}
			}
		}

		public function bump_request_timeout( $val ) {
			return $this->settings->get_params( 'request_timeout' );
		}

		public function admin_enqueue_script() {
			global $pagenow;
			$post_type = isset( $_REQUEST['post_type'] ) ? sanitize_text_field( $_REQUEST['post_type'] ) : '';
			if ( $pagenow === 'edit.php' && $post_type === 'shop_order' ) {
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-update-order', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'update-orders.css' );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-update-order', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'update-orders.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
				wp_localize_script( 's2w-import-shopify-to-woocommerce-update-order', 's2w_params_admin_update_orders', array(
					'url'                       => admin_url( 'admin-ajax.php' ),
					'update_order_options'      => $this->settings->get_params( 'update_order_options' ),
					'update_order_options_show' => $this->settings->get_params( 'update_order_options_show' ),
				) );
				add_action( 'admin_footer', array( $this, 'wp_footer' ) );
			}
		}

		public function wp_footer() {
			$all_options    = array(
				'order_status'     => esc_html__( 'Order status', 's2w-import-shopify-to-woocommerce' ),
				'order_date'       => esc_html__( 'Order date', 's2w-import-shopify-to-woocommerce' ),
				'fulfillments'     => esc_html__( 'Order fulfillments', 's2w-import-shopify-to-woocommerce' ),
				'billing_address'  => esc_html__( 'Billing address', 's2w-import-shopify-to-woocommerce' ),
				'shipping_address' => esc_html__( 'Shipping address', 's2w-import-shopify-to-woocommerce' ),
				'line_items'       => esc_html__( 'Line items', 's2w-import-shopify-to-woocommerce' ),
				'customer'         => esc_html__( 'Customer', 's2w-import-shopify-to-woocommerce' ),
			);
			$update_options = $this->settings->get_params( 'update_order_options' );
			?>
            <div class="<?php echo esc_attr( self::set( array(
				'update-order-options-container',
				'hidden'
			) ) ) ?>">
				<?php wp_nonce_field( 's2w_update_order_options_action_nonce', '_s2w_update_order_options_nonce' ) ?>
                <div class="<?php echo esc_attr( self::set( 'overlay' ) ) ?>"></div>
                <div class="<?php echo esc_attr( self::set( 'update-order-options-content' ) ) ?>">
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-header' ) ) ?>">
                        <h2><?php esc_html_e( 'Update orders options', 's2w-import-shopify-to-woocommerce' ) ?></h2>
                        <span class="<?php echo esc_attr( self::set( 'update-order-options-close' ) ) ?>"></span>
                    </div>
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body' ) ) ?>">
						<?php
						foreach ( $all_options as $option_key => $option_value ) {
							?>
                            <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body-row' ) ) ?>">
                                <div class="<?php echo esc_attr( self::set( 'update-order-options-option-wrap' ) ) ?>">
                                    <input type="checkbox" value="1"
                                           data-order_option="<?php echo $option_key ?>"
										<?php if ( in_array( $option_key, $update_options ) ) {
											echo esc_attr( 'checked' );
										} ?>
                                           id="<?php echo esc_attr( self::set( 'update-order-options-' . $option_key ) ) ?>"
                                           class="<?php echo esc_attr( self::set( 'update-order-options-option' ) ) ?>">
                                    <label for="<?php echo esc_attr( self::set( 'update-order-options-' . $option_key ) ) ?>"><?php echo $option_value ?></label>
                                </div>
                            </div>
							<?php
						}

						?>
                    </div>
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body-1' ) ) ?>">
                        <div class="<?php echo esc_attr( self::set( 'update-order-options-content-body-row' ) ) ?>">
                            <input type="checkbox" value="1"
								<?php checked( '1', $this->settings->get_params( 'update_order_options_show' ) ) ?>
                                   id="<?php echo esc_attr( self::set( 'update-order-options-show' ) ) ?>"
                                   class="<?php echo esc_attr( self::set( 'update-order-options-show' ) ) ?>">
                            <label for="<?php echo esc_attr( self::set( 'update-order-options-show' ) ) ?>"><?php esc_html_e( 'Show these options when clicking on button "Update" for each order', 's2w-import-shopify-to-woocommerce' ) ?></label>
                        </div>
                    </div>
                    <div class="<?php echo esc_attr( self::set( 'update-order-options-content-footer' ) ) ?>">
                        <span class="button-primary <?php echo esc_attr( self::set( array(
	                        'update-order-options-button-save',
	                        'button',
	                        'hidden'
                        ) ) ) ?>">
                            <?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
                        <span class="button-primary <?php echo esc_attr( self::set( array(
							'update-order-options-button-update',
							'button',
							'hidden'
						) ) ) ?>">
                            <?php esc_html_e( 'Update selected', 's2w-import-shopify-to-woocommerce' ) ?>(<span
                                    class="<?php echo esc_attr( self::set( 'selected-number' ) ) ?>">0</span>)
                        </span>
                        <span class="button-primary <?php echo esc_attr( self::set( array(
							'update-order-options-button-update-single',
							'button',
							'hidden'
						) ) ) ?>" data-update_order_id="">
                            <?php esc_html_e( 'Update', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
                        <span class="<?php echo esc_attr( self::set( array(
							'update-order-options-button-cancel',
							'button'
						) ) ) ?>">
                            <?php esc_html_e( 'Cancel', 's2w-import-shopify-to-woocommerce' ) ?>
                        </span>
                    </div>
                </div>
                <div class="<?php echo esc_attr( self::set( 'saving-overlay' ) ) ?>"></div>
            </div>
			<?php
		}
	}
}
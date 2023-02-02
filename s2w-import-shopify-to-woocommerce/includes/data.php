<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA {
	private $params;
	private $default;
	private static $prefix;
	protected $my_options;
	protected static $instance = null;

	/**
	 * VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA constructor.
	 * Init setting
	 */
	public function __construct() {
		self::$prefix = 's2w-';
		global $s2w_settings;
		if ( ! $s2w_settings ) {
			$s2w_settings = get_option( 's2w_params', array() );
		}
		$this->default = array(
			'domain'                      => '',
			'api_key'                     => '',
			'api_secret'                  => '',
			'download_images'             => '1',
			'disable_background_process'  => '',
			'download_description_images' => '1',
			'download_images_later'       => '1',
			'keep_slug'                   => '1',
			'product_status'              => 'publish',
			'product_categories'          => array(),
			'import_categories'           => '1',
			'number'                      => '',
			'validate'                    => '',
			'auto_update_key'             => '',
			'request_timeout'             => '60',

			'products_per_request'        => '5',
			'product_import_sequence'     => 'title asc',
			'update_product_options_show' => 1,
			'update_product_options'      => array(
				'images',
				'price'
			),
			'product_since_id'            => '',
			'product_product_type'        => '',
			'product_collection_id'       => '',
			'product_published_at_min'    => '',
			'product_published_at_max'    => '',
			'global_attributes'           => 0,
			'variable_sku'                => '{shopify_product_id}',
			'order_since_id'              => '',
			'order_processed_at_min'      => '',
			'order_processed_at_max'      => '',
			'order_status'                => 'any',
			'order_financial_status'      => 'any',
			'order_fulfillment_status'    => 'any',
			'orders_per_request'          => '50',

			'customers_per_request'         => '100',
			'customers_role'                => 'customer',
			'customers_with_purchases_only' => '',

			'coupons_per_request'    => '100',
			'coupon_starts_at_min'   => '',
			'coupon_starts_at_max'   => '',
			'coupon_ends_at_min'     => '',
			'coupon_ends_at_max'     => '',
			'coupon_zero_times_used' => '1',
			'order_status_mapping'   => array(
				'pending'            => 'pending',
				'authorized'         => 'processing',
				'partially_paid'     => 'completed',
				'paid'               => 'completed',
				'refunded'           => 'refunded',
				'partially_refunded' => 'refunded',
				'voided'             => 'cancelled',
			),
			'order_import_sequence'  => 'desc',

			'spages_per_request' => 10,

			'update_order_options_show'       => 1,
			'update_order_options'            => array(
				'order_status',
				'order_date',
			),
			'cron_update_products'            => 0,
			'cron_update_products_options'    => array( 'inventory' ),
			'cron_update_products_status'     => array( 'publish' ),
			'cron_update_products_categories' => array(),
			'cron_update_products_interval'   => 5,
			'cron_update_products_hour'       => 0,
			'cron_update_products_minute'     => 0,
			'cron_update_products_second'     => 0,

			'cron_update_orders'              => 0,
			'cron_update_orders_options'      => array( 'status' ),
			'cron_update_orders_status'       => array( 'wc-pending', 'wc-on-hold', 'wc-processing' ),
			'cron_update_orders_range'        => 30,
			'cron_update_orders_interval'     => 5,
			'cron_update_orders_hour'         => 0,
			'cron_update_orders_minute'       => 0,
			'cron_update_orders_second'       => 0,
			'webhooks_shared_secret'          => '',
			'webhooks_orders_enable'          => '',
			'webhooks_orders_create_customer' => '',
			'webhooks_orders_options'         => array(
				'order_status',
			),
			'webhooks_order_status_mapping'   => $this->get_params( 'order_status_mapping' ),
			'webhooks_products_enable'        => '',
			'webhooks_products_options'       => array(
				'inventory',
			),
			'webhooks_customers_enable'       => '',
			'blogs_update_if_exist'           => array(),
		);

		$this->params = apply_filters( 's2w_params', wp_parse_args( $s2w_settings, $this->default ) );
	}

	public static function get_instance( $new = false ) {
		if ( $new || null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public static function get_blogs_update_options() {
		return array(
			'description' => esc_html__( 'Description', 's2w-import-shopify-to-woocommerce' ),
			'tags'        => esc_html__( 'Tags', 's2w-import-shopify-to-woocommerce' ),
			'categories'  => esc_html__( 'Categories', 's2w-import-shopify-to-woocommerce' ),
			'date'        => esc_html__( 'Date & Status', 's2w-import-shopify-to-woocommerce' ),
		);
	}

	public function get_params( $name = "" ) {
		if ( ! $name ) {
			return $this->params;
		} elseif ( isset( $this->params[ $name ] ) ) {
			return apply_filters( 's2w_params' . $name, $this->params[ $name ] );
		} else {
			return false;
		}
	}

	public function get_default( $name = "" ) {
		if ( ! $name ) {
			return $this->default;
		} elseif ( isset( $this->default[ $name ] ) ) {
			return apply_filters( 's2w_params_default' . $name, $this->default[ $name ] );
		} else {
			return false;
		}
	}

	public static function set( $name, $set_name = false ) {
		if ( is_array( $name ) ) {
			return implode( ' ', array_map( array( 'VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA', 'set' ), $name ) );
		} else {
			if ( $set_name ) {
				return esc_attr__( str_replace( '-', '_', self::$prefix . $name ) );

			} else {
				return esc_attr__( self::$prefix . $name );

			}
		}
	}

	public static function log( $log_file, $logs_content ) {
		$logs_content = PHP_EOL . "[" . date( "Y-m-d H:i:s" ) . "] " . $logs_content;
		if ( is_file( $log_file ) ) {
			file_put_contents( $log_file, $logs_content, FILE_APPEND );
		} else {
			file_put_contents( $log_file, $logs_content );
		}
	}

	public static function sku_exists( $sku = '' ) {
		global $wpdb;
		$sku_exists = false;
		if ( $sku ) {
			/*Not sure which method is faster
			$id_from_sku = wc_get_product_id_by_sku( $sku );
			$product     = $id_from_sku ? wc_get_product( $id_from_sku ) : false;
			$sku_exists  = $product && 'importing' !== $product->get_status();
			*/
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			$query          = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type in ('product','product_variation') and {$table_posts}.post_status in ('publish','draft','private','pending') and {$table_postmeta}.meta_key = '_sku' and {$table_postmeta}.meta_value = %s";
			$results        = $wpdb->get_var( $wpdb->prepare( $query, $sku ) );
			if ( intval( $results ) > 0 ) {
				$sku_exists = true;
			}
		}

		return $sku_exists;
	}

	/**
	 * @param $shopify_id
	 * @param bool $is_variation
	 * @param bool $count
	 * @param bool $multiple
	 *
	 * @return array|null|object|string
	 */
	public static function product_get_woo_id_by_shopify_id( $shopify_id, $is_variation = false, $count = false, $multiple = false ) {
		global $wpdb;
		if ( $shopify_id ) {
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			if ( $is_variation ) {
				$post_type = 'product_variation';
				$meta_key  = '_shopify_variation_id';
			} else {
				$post_type = 'product';
				$meta_key  = '_shopify_product_id';
			}
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ) );
			} else {
				$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $shopify_id ), ARRAY_A );
				} else {
					$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	/**
	 * @param $shopify_id
	 * @param bool $count
	 * @param string $type
	 * @param bool $multiple
	 * @param string $meta_key
	 *
	 * @return array|bool|null|object|string
	 */
	public static function query_get_id_by_shopify_id( $shopify_id, $type = 'order', $count = false, $multiple = false, $meta_key = '' ) {
		global $wpdb;
		if ( $shopify_id ) {
			$table_posts    = "{$wpdb->prefix}posts";
			$table_postmeta = "{$wpdb->prefix}postmeta";
			switch ( $type ) {
				case 'image':
					$post_type = 'attachment';
					break;
				case 'post':
				case 'blog':
					$post_type = 'post';
					break;
				case 'page':
					$post_type = 'page';
					break;
				case 'coupon':
				case 'price_rule':
					$post_type = 'shop_coupon';
					break;
				case 'order':
				default:
					$post_type = 'shop_order';
			}
			if ( ! $meta_key ) {
				$meta_key = "_s2w_shopify_{$type}_id";
			}
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ) );
			} else {
				$query = "SELECT {$table_postmeta}.* from {$table_postmeta} join {$table_posts} on {$table_postmeta}.post_id={$table_posts}.ID where {$table_posts}.post_type = '{$post_type}' and {$table_posts}.post_status != 'trash' and {$table_postmeta}.meta_key = '{$meta_key}' and {$table_postmeta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $shopify_id ), ARRAY_A );
				} else {
					$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	/**
	 * @param $shopify_id
	 * @param bool $count
	 * @param bool $multiple
	 * @param string $meta_key
	 *
	 * @return array|bool|object|string|null
	 */
	public static function customer_get_id_by_shopify_id( $shopify_id, $count = false, $multiple = false, $meta_key = '' ) {
		global $wpdb;
		if ( $shopify_id ) {
			$table      = "{$wpdb->prefix}users";
			$table_meta = "{$wpdb->prefix}usermeta";
			if ( ! $meta_key ) {
				$meta_key = '_s2w_shopify_customer_id';
			}
			if ( $count ) {
				$query   = "SELECT count(*) from {$table_meta} join {$table} on {$table_meta}.user_id={$table}.ID WHERE {$table_meta}.meta_key = '{$meta_key}' and {$table_meta}.meta_value = %s";
				$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ) );
			} else {
				$query = "SELECT {$table_meta}.* from {$table_meta} join {$table} on {$table_meta}.user_id={$table}.ID WHERE {$table_meta}.meta_key = '{$meta_key}' and {$table_meta}.meta_value = %s";
				if ( $multiple ) {
					$results = $wpdb->get_results( $wpdb->prepare( $query, $shopify_id ), ARRAY_A );
				} else {
					$results = $wpdb->get_var( $wpdb->prepare( $query, $shopify_id ), 1 );
				}
			}

			return $results;
		} else {
			return false;
		}
	}

	public static function get_woo_id_by_shopify_id( $shopify_id, $is_variation = false ) {
		$product_id = '';
		if ( $shopify_id ) {
			$args = array(
				'post_status'    => array( 'publish', 'pending', 'draft' ),
				'posts_per_page' => '1',
				'cache_results'  => false,
				'no_found_rows'  => true,
				'fields'         => 'ids',
			);
			if ( ! $is_variation ) {
				$args['post_type']  = 'product';
				$args['meta_query'] = array(
					'relation' => 'AND',
					array(
						'relation' => 'OR',
						array(
							'key'     => '_shopify_product_id',
							'value'   => $shopify_id,
							'compare' => '=',
						),
						array(
							'key'     => '_s2w_shopipy_product_id',
							'value'   => $shopify_id,
							'compare' => '=',
						),
					)
				);
			} else {
				$args['meta_key']   = '_shopify_variation_id';
				$args['meta_value'] = $shopify_id;
				$args['post_type']  = 'product_variation';
			}
			$the_query = new WP_Query( $args );

			if ( $the_query->have_posts() ) {
				$the_query->the_post();
				$product_id = get_the_ID();
			}
			wp_reset_postdata();
		}

		return $product_id;
	}

	public static function get_order_id_by_meta( $value, $key = '_s2w_shopify_order_id' ) {
		$order_id  = '';
		$args      = array(
			'post_type'      => 'shop_order',
			'post_status'    => array(
				'wc-pending',
				'wc-processing',
				'wc-on-hold',
				'wc-completed',
				'wc-cancelled',
				'wc-refunded',
				'wc-failed'
			),
			'meta_key'       => $key,
			'meta_value'     => $value,
			'posts_per_page' => 1,
			'cache_results'  => false,
			'no_found_rows'  => true,
			'fields'         => 'ids',
		);
		$the_query = new WP_Query( $args );
		if ( $the_query->have_posts() ) {
			$the_query->the_post();
			$order_id = get_the_ID();
		}
		wp_reset_postdata();

		return $order_id;
	}

	public static function get_option( $option_name, $default = false ) {
		return get_option( $option_name, $default );
	}

	public static function update_option( $option_name, $option_value ) {
		return update_option( $option_name, $option_value );
	}

	public static function delete_option( $option_name ) {
		return delete_option( $option_name );
	}

	/**
	 * @param $files
	 */
	public static function delete_files( $files ) {
		if ( is_array( $files ) ) {
			if ( count( $files ) ) {
				foreach ( $files as $file ) { // iterate files
					if ( is_file( $file ) ) {
						unlink( $file );
					} // delete file
				}
			}
		} elseif ( is_file( $files ) ) {
			unlink( $files );
		}
	}

	public static function deleteDir( $dirPath ) {
		if ( is_dir( $dirPath ) ) {
			if ( substr( $dirPath, strlen( $dirPath ) - 1, 1 ) != '/' ) {
				$dirPath .= '/';
			}
			$files = glob( $dirPath . '*', GLOB_MARK );
			foreach ( $files as $file ) {
				if ( is_dir( $file ) ) {
					self::deleteDir( $file );
				} else {
					unlink( $file );
				}
			}
			rmdir( $dirPath );
		}
	}

	protected static function create_plugin_cache_folder() {
		if ( ! is_dir( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE ) ) {
			wp_mkdir_p( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE );
			file_put_contents( VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . '.htaccess', '<IfModule !mod_authz_core.c>
Order deny,allow
Deny from all
</IfModule>
<IfModule mod_authz_core.c>
  <RequireAll>
    Require all denied
  </RequireAll>
</IfModule>
' );
		}
	}

	public static function create_cache_folder( $path ) {
		self::create_plugin_cache_folder();
		if ( ! is_dir( $path ) ) {
			wp_mkdir_p( $path );
		}
	}

	public static function get_cache_path( $domain, $api_key, $api_secret ) {
		return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CACHE . md5( $api_key ) . '_' . md5( $api_secret ) . '_' . $domain;
	}

	public static function implode_args( $args ) {
		foreach ( $args as $key => $value ) {
			if ( is_array( $value ) ) {
				$args[ $key ] = implode( ',', $value );
			}
		}

		return $args;
	}

	public static function wp_remote_get( $domain, $api_key, $api_secret, $type = 'products', $count = false, $original_args = array(), $timeout = 300, $return_pagination_link = false, $version = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_REST_ADMIN_VERSION ) {
		$args = self::implode_args( wp_parse_args( $original_args, array( 'limit' => 250 ) ) );
		$url  = "https://{$api_key}:{$api_secret}@{$domain}/admin";
		if ( $version ) {
			$url .= "/api/{$version}";
		}
		if ( $count ) {
			$url .= "/{$type}/count.json";
		} else {
			$url  .= "/{$type}.json";
			$type = explode( '/', $type )[0];
		}
		$url = add_query_arg( $args, $url );
		$request = wp_remote_get(
			$url, array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => $timeout,
				'headers'    => array( 'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ) ),
			)
		);
		$return  = array(
			'status' => 'error',
			'data'   => '',
			'code'   => '',
		);
		if ( ! is_wp_error( $request ) ) {
			if ( isset( $request['response']['code'] ) ) {
				$return['code'] = $request['response']['code'];
			}
			if ( $return_pagination_link ) {
				$return['pagination_link'] = self::get_pagination_link( $request );
			}
			$body = vi_s2w_json_decode( $request['body'] );
			if ( isset( $body['errors'] ) ) {
				$return['data'] = $body['errors'];
			} else {
				$return['status'] = 'success';
				if ( $count ) {
					$return['data'] = absint( $body['count'] );
				} else {
					if ( ! empty( $original_args['ids'] ) && ! is_array( $original_args['ids'] ) ) {
						$return['data'] = isset( $body[ $type ][0] ) ? $body[ $type ][0] : array();
					} else {
						$return['data'] = $body[ $type ];
					}
				}
			}
		} else {
			$return['data'] = $request->get_error_message();
			$return['code'] = $request->get_error_code();
		}

		return $return;
	}

	public static function wp_remote_get_metafields( $domain, $api_key, $api_secret, $id, $type = 'products', $count = false, $original_args = array(), $timeout = 300, $return_pagination_link = false, $version = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_REST_ADMIN_VERSION ) {
		$args = self::implode_args( wp_parse_args( $original_args, array( 'limit' => 250 ) ) );
		$url  = "https://{$api_key}:{$api_secret}@{$domain}/admin";
		if ( $version ) {
			$url .= "/api/{$version}";
		}
		if ( $count ) {
			$url .= "/{$type}/{$id}/metafields/count.json";
		} else {
			$url .= "/{$type}/{$id}/metafields.json";
		}
		$url     = add_query_arg( $args, $url );
		$request = wp_remote_get(
			$url, array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => $timeout,
				'headers'    => array( 'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ) ),
			)
		);
		$return  = array(
			'status' => 'error',
			'data'   => '',
			'code'   => '',
		);
		if ( ! is_wp_error( $request ) ) {
			if ( isset( $request['response']['code'] ) ) {
				$return['code'] = $request['response']['code'];
			}
			if ( $return_pagination_link ) {
				$return['pagination_link'] = self::get_pagination_link( $request );
			}
			$body = vi_s2w_json_decode( $request['body'] );
			if ( isset( $body['errors'] ) ) {
				$return['data'] = $body['errors'];
			} else {
				$return['status'] = 'success';
				if ( $count ) {
					$return['data'] = absint( $body['count'] );
				} else {
					$return['data'] = $body['metafields'];
				}
			}
		} else {
			$return['data'] = $request->get_error_message();
			$return['code'] = $request->get_error_code();
		}

		return $return;
	}

	public static function wp_remote_get_articles( $domain, $api_key, $api_secret, $blog_id, $count = false, $original_args = array(), $timeout = 300, $return_pagination_link = false, $version = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_REST_ADMIN_VERSION ) {
		$args           = self::implode_args( wp_parse_args( $original_args, array( 'limit' => 250 ) ) );
		$url            = "https://{$api_key}:{$api_secret}@{$domain}/admin";
		$single_article = false;
		if ( $version ) {
			$url .= "/api/{$version}";
		}
		if ( $count ) {
			$url .= "/blogs/{$blog_id}/articles/count.json";
		} else {
			if ( ! empty( $original_args['ids'] ) && ( ! is_array( $original_args['ids'] ) || count( $original_args['ids'] ) === 1 ) ) {
				$url .= "/blogs/{$blog_id}/articles/{$original_args['ids']}.json";
				unset( $args['ids'] );
				$single_article = true;
			} else {
				$url .= "/blogs/{$blog_id}/articles.json";
			}
		}
		$url = add_query_arg( $args, $url );

		$request = wp_remote_get(
			$url, array(
				'user-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/61.0.3163.100 Safari/537.36',
				'timeout'    => $timeout,
				'headers'    => array( 'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ) ),
			)
		);
		$return  = array(
			'status' => 'error',
			'data'   => '',
			'code'   => isset( $request['response']['code'] ) ? $request['response']['code'] : '',
		);
		if ( ! is_wp_error( $request ) ) {
			if ( $return_pagination_link ) {
				$return['pagination_link'] = self::get_pagination_link( $request );
			}
			$body = vi_s2w_json_decode( $request['body'] );
			if ( isset( $body['errors'] ) ) {
				$return['data'] = $body['errors'];
			} else {
				$return['status'] = 'success';
				if ( $count ) {
					$return['data'] = absint( $body['count'] );
				} else {
					if ( $single_article ) {
						$return['data'] = isset( $body['article'] ) ? $body['article'] : array();
					} else {
						$return['data'] = $body['articles'];
					}
				}
			}
		} else {
			$return['data'] = $request->get_error_message();
		}

		return $return;
	}

	/**
	 * @param $page_number
	 * @param $domain
	 * @param $api_key
	 * @param $api_secret
	 * @param string $type
	 * @param array $args
	 *
	 * @return array|bool|mixed
	 */
	public static function get_pagination_link_by_page_number( $page_number, $domain, $api_key, $api_secret, $type = 'products', $args = array() ) {
		$args           = wp_parse_args( $args, array( 'limit' => 250 ) );
		$args['fields'] = 'id';
		$response       = self::wp_remote_get( $domain, $api_key, $api_secret, $type, true, $args );
		if ( $response['status'] === 'success' ) {
			$pagination_link = array(
				'previous' => '',
				'next'     => '',
			);
			$count           = $response['data'];
			$limit           = intval( $args['limit'] );
			$total_pages     = ceil( $count / $limit );
			if ( $page_number <= $total_pages ) {
				$new_args = array( 'fields' => 'id', 'limit' => $args['limit'] );
				for ( $i = 0; $i < $page_number; $i ++ ) {
					$response = self::wp_remote_get( $domain, $api_key, $api_secret, $type, false, $new_args, 300, true );
					if ( $response['status'] === 'success' ) {
						$pagination_link = $response['pagination_link'];
						if ( $response['pagination_link']['next'] ) {
							$new_args['page_info'] = $response['pagination_link']['next'];
						}
					} else {
						return false;
					}
				}
			}

			return $pagination_link;
		} else {
			return false;
		}
	}

	/**
	 * @param $request
	 *
	 * @return mixed|string
	 */
	public static function get_pagination_link( $request ) {
		$link      = wp_remote_retrieve_header( $request, 'link' );
		$page_link = array( 'previous' => '', 'next' => '' );
		if ( $link ) {
			$links = explode( ',', $link );
			foreach ( $links as $url ) {
				$params = wp_parse_url( $url );
				parse_str( $params['query'], $query );
				if ( ! empty( $query['page_info'] ) ) {
					$query_params = explode( '>;', $query['page_info'] );
					if ( trim( $query_params[1] ) === 'rel="next"' ) {
						$page_link['next'] = $query_params[0];
					} else {
						$page_link['previous'] = $query_params[0];
					}
				}
			}
		}

		return $page_link;
	}

	public static function sanitize_taxonomy_name( $name ) {
		return strtolower( urlencode( wc_sanitize_taxonomy_name( $name ) ) );
	}

	public static function download_image( &$shopify_id, $url, $post_parent = 0, $exclude = array(), $post_title = '', $desc = null ) {
		global $wpdb;
		$new_url   = $url;
		$parse_url = wp_parse_url( $new_url );
		$scheme    = empty( $parse_url['scheme'] ) ? 'http' : $parse_url['scheme'];
		$image_id  = "{$parse_url['host']}{$parse_url['path']}";
		$new_url   = "{$scheme}://{$image_id}";
		preg_match( '/[^\?]+\.(jpg|JPG|jpeg|JPEG|jpe|JPE|gif|GIF|png|PNG)/', $new_url, $matches );
		if ( ! is_array( $matches ) || ! count( $matches ) ) {
			preg_match( '/[^\?]+\.(jpg|JPG|jpeg|JPEG|jpe|JPE|gif|GIF|png|PNG)/', $url, $matches );
			if ( is_array( $matches ) && count( $matches ) ) {
				$new_url  .= "?{$matches[0]}";
				$image_id .= "?{$matches[0]}";
			}
		}
		if ( ! $shopify_id ) {
			$shopify_id = $image_id;
		}

		$thumb_id = self::query_get_id_by_shopify_id( $shopify_id, 'image' );
		if ( ! $thumb_id ) {
			$thumb_id = s2w_upload_image( $new_url, $post_parent, $exclude, $post_title, $desc );
		} elseif ( $post_parent ) {
			$table_postmeta = "{$wpdb->prefix}posts";
			$wpdb->query( $wpdb->prepare( "UPDATE {$table_postmeta} set post_parent=%s WHERE ID=%s AND post_parent = 0 LIMIT 1", array(
				$post_parent,
				$thumb_id
			) ) );
		}

		return $thumb_id;
	}
}
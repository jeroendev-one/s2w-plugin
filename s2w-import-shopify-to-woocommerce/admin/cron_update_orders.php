<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders' ) ) {
	class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Cron_Update_Orders {
		protected $settings;
		public static $update_orders;
		public static $get_data_to_update;
		protected $next_schedule;

		public function __construct() {
			$this->settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
			add_action( 'init', array( $this, 'background_process' ) );
			add_action( 'admin_init', array( $this, 'admin_init' ) );
			add_action( 'admin_init', array( $this, 'save_options' ) );
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_script' ) );
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 16 );
			add_action( 's2w_cron_update_orders', array( $this, 'cron_update_orders' ) );
			add_filter( 'cron_schedules', array( $this, 'cron_schedules' ) );
			$this->next_schedule = wp_next_scheduled( 's2w_cron_update_orders' );
		}

		public static function set( $name, $set_name = false ) {
			return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
		}

		public function cron_schedules( $schedules ) {
			$schedules['s2w_cron_update_orders_interval'] = array(
				'interval' => 86400 * absint( $this->settings->get_params( 'cron_update_orders_interval' ) ),
				'display'  => __( 'Cron update orders', 's2w-import-shopify-to-woocommerce' ),
			);

			return $schedules;
		}

		public function admin_menu() {
			$menu_slug = 's2w-import-shopify-to-woocommerce-cron-update-orders';
			add_submenu_page( 's2w-import-shopify-to-woocommerce',
				esc_html__( 'Cron Update Orders', 's2w-import-shopify-to-woocommerce' ),
				esc_html__( 'Cron Update Orders', 's2w-import-shopify-to-woocommerce' ),
				apply_filters( 'vi_s2w_admin_sub_menu_capability', 'manage_options', $menu_slug ), $menu_slug, array(
					$this,
					'page_callback'
				) );
		}

		public function page_callback() {
			?>
            <div class="wrap">
                <h2><?php esc_html_e( 'Cron Update Orders', 's2w-import-shopify-to-woocommerce' ) ?></h2>
				<?php S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::security_recommendation_html(); ?>
                <p></p>
                <form class="vi-ui form" method="post">
					<?php wp_nonce_field( 's2w_action_nonce', '_s2w_nonce' ); ?>
                    <div class="vi-ui segment">
						<?php
						if ( ! $this->settings->get_params( 'validate' ) ) {
							?>
                            <div class="vi-ui negative message"><?php esc_html_e( 'You need to enter correct domain, API key and API secret to use this function', 's2w-import-shopify-to-woocommerce' );; ?></div>
							<?php
						}
						if ( $this->next_schedule ) {
							$gmt_offset = intval( get_option( 'gmt_offset' ) );
							?>
                            <div class="vi-ui positive message"><?php printf( __( 'Next schedule: <strong>%s</strong>', 's2w-import-shopify-to-woocommerce' ), date_i18n( 'F j, Y g:i:s A', ( $this->next_schedule + HOUR_IN_SECONDS * $gmt_offset ) ) ); ?></div>
							<?php
						} else {
							?>
                            <div class="vi-ui negative message"><?php esc_html_e( 'Cron Update Orders is currently DISABLED', 's2w-import-shopify-to-woocommerce' );; ?></div>
							<?php
						}
						?>
                        <table class="form-table">
                            <tbody>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders' ) ) ?>"><?php esc_html_e( 'Enable cron', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui toggle checkbox checked">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders' ) ) ?>"
                                               value="1" <?php checked( $this->settings->get_params( 'cron_update_orders' ), '1' ) ?>>
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders' ) ) ?>"><?php esc_html_e( 'Automatically sync orders data with your Shopify store', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_interval' ) ) ?>"><?php esc_html_e( 'Run update every', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui right labeled input">
                                        <input type="number" min="1"
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders_interval', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders_interval' ) ) ?>"
                                               value="<?php echo esc_attr( $this->settings->get_params( 'cron_update_orders_interval' ) ) ?>">
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders_interval' ) ) ?>"
                                               class="vi-ui label"><?php esc_html_e( 'Day(s)', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                    <p><?php esc_html_e( 'You should run cron update for less than 300 orders per day', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_hour' ) ) ?>"><?php esc_html_e( 'Run update at', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="equal width fields">
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="23"
                                                       name="<?php echo esc_attr( self::set( 'cron_update_orders_hour', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_orders_hour' ) ) ?>"
                                                       value="<?php echo esc_attr( $this->settings->get_params( 'cron_update_orders_hour' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_orders_hour' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Hour', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="59"
                                                       name="<?php echo esc_attr( self::set( 'cron_update_orders_minute', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_orders_minute' ) ) ?>"
                                                       value="<?php echo esc_attr( $this->settings->get_params( 'cron_update_orders_minute' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_orders_minute' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Minute', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                        <div class="field">
                                            <div class="vi-ui right labeled input">
                                                <input type="number" min="0" max="59"
                                                       name="<?php echo esc_attr( self::set( 'cron_update_orders_second', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'cron_update_orders_second' ) ) ?>"
                                                       value="<?php echo esc_attr( $this->settings->get_params( 'cron_update_orders_second' ) ) ?>">
                                                <label for="<?php echo esc_attr( self::set( 'cron_update_orders_second' ) ) ?>"
                                                       class="vi-ui label"><?php esc_html_e( 'Second', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </div>
                                    </div>

                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_status' ) ) ?>"><?php esc_html_e( 'Only update orders with status:', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <select class="vi-ui fluid dropdown"
                                            name="<?php echo esc_attr( self::set( 'cron_update_orders_status', true ) ) ?>[]"
                                            multiple="multiple"
                                            id="<?php echo esc_attr( self::set( 'cron_update_orders_status' ) ) ?>">
										<?php
										$cron_update_orders_status = $this->settings->get_params( 'cron_update_orders_status' );
										$options                   = wc_get_order_statuses();
										foreach ( $options as $option_k => $option_v ) {
											?>
                                            <option value="<?php echo $option_k ?>"<?php if ( in_array( $option_k, $cron_update_orders_status ) )
												echo esc_attr( 'selected' ) ?>><?php echo $option_v; ?></option>
											<?php
										}
										?>
                                    </select>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_range' ) ) ?>"><?php esc_html_e( 'Only update orders created in the last x day(s):', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <div class="vi-ui right labeled input">
                                        <input type="number" min="0" max=""
                                               name="<?php echo esc_attr( self::set( 'cron_update_orders_range', true ) ) ?>"
                                               id="<?php echo esc_attr( self::set( 'cron_update_orders_range' ) ) ?>"
                                               value="<?php echo esc_attr( $this->settings->get_params( 'cron_update_orders_range' ) ) ?>">
                                        <label for="<?php echo esc_attr( self::set( 'cron_update_orders_range' ) ) ?>"
                                               class="vi-ui label"><?php esc_html_e( 'Day(s)', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                    </div>
                                    <p class="description"><?php esc_html_e( 'Set 0 to skip filtering orders by date range', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            <tr>
                                <th>
                                    <label for="<?php echo esc_attr( self::set( 'cron_update_orders_options' ) ) ?>"><?php esc_html_e( 'Select options to update', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                </th>
                                <td>
                                    <select class="vi-ui fluid dropdown"
                                            name="<?php echo esc_attr( self::set( 'cron_update_orders_options', true ) ) ?>[]"
                                            multiple="multiple"
                                            id="<?php echo esc_attr( self::set( 'cron_update_orders_options' ) ) ?>">
										<?php
										$cron_update_orders_options = $this->settings->get_params( 'cron_update_orders_options' );
										$options                    = array(
											'status'           => esc_html__( 'Status', 's2w-import-shopify-to-woocommerce' ),
											'billing_address'  => esc_html__( 'Billing Address', 's2w-import-shopify-to-woocommerce' ),
											'shipping_address' => esc_html__( 'Shipping Address', 's2w-import-shopify-to-woocommerce' ),
											'fulfillments'     => esc_html__( 'Fulfillments', 's2w-import-shopify-to-woocommerce' ),
										);
										foreach ( $options as $option_k => $option_v ) {
											?>
                                            <option value="<?php echo $option_k ?>"<?php if ( in_array( $option_k, $cron_update_orders_options ) )
												echo esc_attr( 'selected' ) ?>><?php echo $option_v; ?></option>
											<?php
										}
										?>
                                    </select>
                                    <p class="description"><?php esc_html_e( 'Which order data do you want to update?', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </div>
                    <p>
                        <input type="submit" class="vi-ui button primary" name="s2w_save_cron_update_orders"
                               value="<?php esc_html_e( 'Save', 's2w-import-shopify-to-woocommerce' ) ?> "/>
                    </p>
                </form>
            </div>
			<?php
		}

		public function background_process() {
			self::$get_data_to_update = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders_Get_Data();
			self::$update_orders      = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Cron_Update_Orders();
		}

		public function admin_init() {
			if ( isset( $_REQUEST['s2w_cron_update_orders_cancel'] ) && $_REQUEST['s2w_cron_update_orders_cancel'] ) {
				self::$get_data_to_update->kill_process();
				self::$update_orders->kill_process();
				wp_safe_redirect( @remove_query_arg( 's2w_cron_update_orders_cancel' ) );
				exit;
			}
		}

		public function admin_notices() {
			if ( self::$get_data_to_update->is_downloading() || self::$update_orders->is_downloading() ) {
				?>
                <div class="updated">
                    <p>
						<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: "Cron update orders" is running in the background.', 's2w-import-shopify-to-woocommerce' ) ?>
                    </p>
                </div>
				<?php
			} elseif ( get_transient( 's2w_background_processing_cron_update_orders_complete' ) ) {
				delete_transient( 's2w_background_processing_cron_update_orders_complete' );
				?>
                <div class="updated">
                    <p>
						<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: "Cron update orders" finishes.', 's2w-import-shopify-to-woocommerce' ) ?>
                    </p>
                </div>
				<?php
			}
		}

		public function cron_update_orders() {
			vi_s2w_init_set();
			$args       = array(
				'post_type'      => 'shop_order',
				'post_status'    => $this->settings->get_params( 'cron_update_orders_status' ),
				'posts_per_page' => 250,
				'meta_key'       => '_s2w_shopify_order_id',
				'orderby'        => 'ID',
				'order'          => 'ASC',
				'fields'         => 'ids',
			);
			$date_range = $this->settings->get_params( 'cron_update_orders_range' );
			if ( $date_range ) {
				$args['date_query'] = array(
					array(
						'after'     => date( 'Y-m-d', strtotime( "-{$date_range} days" ) ),
						'inclusive' => true
					)
				);
			}
			$the_query         = new WP_Query( $args );
			$shopify_order_ids = array( 'data' => array() );
			if ( $the_query->have_posts() ) {
				$max_num_pages = $the_query->max_num_pages;
				while ( $the_query->have_posts() ) {
					$the_query->the_post();
					$order_id                               = get_the_ID();
					$shopify_order_id                       = get_post_meta( $order_id, '_s2w_shopify_order_id', true );
					$shopify_order_ids['data'][ $order_id ] = $shopify_order_id;
				}
				wp_reset_postdata();
				self::$get_data_to_update->push_to_queue( $shopify_order_ids );
				if ( $max_num_pages > 1 ) {
					for ( $i = 2; $i <= $max_num_pages; $i ++ ) {
						vi_s2w_set_time_limit();
						$args ['paged']    = $i;
						$the_query         = new WP_Query( $args );
						$shopify_order_ids = array( 'data' => array() );
						if ( $the_query->have_posts() ) {
							while ( $the_query->have_posts() ) {
								$the_query->the_post();
								$order_id                               = get_the_ID();
								$shopify_order_id                       = get_post_meta( $order_id, '_s2w_shopify_order_id', true );
								$shopify_order_ids['data'][ $order_id ] = $shopify_order_id;
							}
						}
						wp_reset_postdata();
						self::$get_data_to_update->push_to_queue( $shopify_order_ids )->save()->dispatch();
					}
				}
				self::$get_data_to_update->save()->dispatch();
			}
			wp_reset_postdata();
		}

		public function save_options() {
			global $s2w_settings;
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			if ( ! isset( $_POST['s2w_save_cron_update_orders'] ) || ! $_POST['s2w_save_cron_update_orders'] ) {
				return;
			}
			if ( ! isset( $_POST['_s2w_nonce'] ) || ! wp_verify_nonce( $_POST['_s2w_nonce'], 's2w_action_nonce' ) ) {
				return;
			}
			$s2w_settings['cron_update_orders']          = isset( $_POST['s2w_cron_update_orders'] ) ? sanitize_text_field( $_POST['s2w_cron_update_orders'] ) : '';
			$s2w_settings['cron_update_orders_range']    = isset( $_POST['s2w_cron_update_orders_range'] ) ? absint( sanitize_text_field( $_POST['s2w_cron_update_orders_range'] ) ) : 0;
			$s2w_settings['cron_update_orders_interval'] = isset( $_POST['s2w_cron_update_orders_interval'] ) ? absint( sanitize_text_field( $_POST['s2w_cron_update_orders_interval'] ) ) : 1;
			$s2w_settings['cron_update_orders_hour']     = isset( $_POST['s2w_cron_update_orders_hour'] ) ? absint( sanitize_text_field( $_POST['s2w_cron_update_orders_hour'] ) ) : 0;
			$s2w_settings['cron_update_orders_minute']   = isset( $_POST['s2w_cron_update_orders_minute'] ) ? absint( sanitize_text_field( $_POST['s2w_cron_update_orders_minute'] ) ) : 0;
			$s2w_settings['cron_update_orders_second']   = isset( $_POST['s2w_cron_update_orders_second'] ) ? absint( sanitize_text_field( $_POST['s2w_cron_update_orders_second'] ) ) : 0;
			$s2w_settings['cron_update_orders_options']  = isset( $_POST['s2w_cron_update_orders_options'] ) ? array_map( 'sanitize_text_field', $_POST['s2w_cron_update_orders_options'] ) : array();
			$s2w_settings['cron_update_orders_status']   = isset( $_POST['s2w_cron_update_orders_status'] ) ? array_map( 'sanitize_text_field', $_POST['s2w_cron_update_orders_status'] ) : array();
			VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::update_option( 's2w_params', $s2w_settings );
			if ( $s2w_settings['cron_update_orders'] && ( ! $this->settings->get_params( 'cron_update_orders' ) || $s2w_settings['cron_update_orders_interval'] != $this->settings->get_params( 'cron_update_orders_interval' ) || $s2w_settings['cron_update_orders_hour'] != $this->settings->get_params( 'cron_update_orders_hour' ) || $s2w_settings['cron_update_orders_minute'] != $this->settings->get_params( 'cron_update_orders_minute' ) || $s2w_settings['cron_update_orders_second'] != $this->settings->get_params( 'cron_update_orders_second' ) ) ) {
				if ( $s2w_settings['validate'] ) {
					$gmt_offset = intval( get_option( 'gmt_offset' ) );
					$this->unschedule_event();
					$schedule_time_local = strtotime( 'today' ) + HOUR_IN_SECONDS * abs( $s2w_settings['cron_update_orders_hour'] ) + MINUTE_IN_SECONDS * abs( $s2w_settings['cron_update_orders_minute'] ) + $s2w_settings['cron_update_orders_second'];
					if ( $gmt_offset < 0 ) {
						$schedule_time_local -= DAY_IN_SECONDS;
					}
					$schedule_time = $schedule_time_local - HOUR_IN_SECONDS * $gmt_offset;
					if ( $schedule_time < time() ) {
						$schedule_time += DAY_IN_SECONDS;
					}
					/*Call here to apply new interval to cron_schedules filter when calling method wp_schedule_event*/
					$this->settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
					$schedule       = wp_schedule_event( $schedule_time, 's2w_cron_update_orders_interval', 's2w_cron_update_orders' );

					if ( $schedule !== false ) {
						$this->next_schedule = $schedule_time;
					} else {
						$this->next_schedule = '';
					}
				} else {
					$s2w_settings['cron_update_products'] = '';
					$s2w_settings['cron_update_orders']   = '';
					VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::update_option( 's2w_params', $s2w_settings );
					$this->settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
					$this->unschedule_event();
				}
			} else {
				$this->settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance( true );
				if ( ! $s2w_settings['cron_update_orders'] ) {
					$this->unschedule_event();
				}
			}
		}

		public function unschedule_event() {
			if ( $this->next_schedule ) {
				wp_unschedule_event( $this->next_schedule, 's2w_cron_update_orders' );
				$this->next_schedule = '';
			}
			self::$get_data_to_update->kill_process();
			self::$update_orders->kill_process();
		}

		public function enqueue_semantic() {
			/*Stylesheet*/
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-form', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'form.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-table', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'table.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-icon', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'icon.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-segment', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'segment.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-button', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'button.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-label', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'label.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-input', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'input.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-checkbox', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'checkbox.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-transition', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'transition.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-dropdown', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'dropdown.min.css' );
			wp_enqueue_style( 's2w-import-shopify-to-woocommerce-message', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'message.min.css' );
			wp_enqueue_style( 'select2', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'select2.min.css' );
			wp_enqueue_script( 'select2-v4', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'select2.js', array( 'jquery' ) );
			wp_enqueue_script( 's2w-import-shopify-to-woocommerce-transition', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'transition.min.js' );
			wp_enqueue_script( 's2w-import-shopify-to-woocommerce-dropdown', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'dropdown.min.js' );
		}

		public function admin_enqueue_script( $page ) {
			if ( $page === 'shopify-to-woo_page_s2w-import-shopify-to-woocommerce-cron-update-orders' ) {
				$this->enqueue_semantic();
				wp_enqueue_style( 's2w-import-shopify-to-woocommerce-cron-update-orders', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'cron-update-orders.css' );
				wp_enqueue_script( 's2w-import-shopify-to-woocommerce-cron-update-orders', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'cron-update-orders.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
			}
		}
	}
}

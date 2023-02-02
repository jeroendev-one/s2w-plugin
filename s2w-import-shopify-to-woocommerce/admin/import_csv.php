<?php

/**
 * Class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Import_Csv
 *
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
ini_set( 'auto_detect_line_endings', true );

class S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_ADMIN_Import_Csv {
	protected $settings;
	public static $process;
	protected $request;
	protected $step;
	protected $file_url;
	protected $header;
	protected $error;
	protected $index;
	protected $products_per_request;
	protected $nonce;

	public function __construct() {
		$this->settings = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::get_instance();
		add_action( 'init', array( $this, 'plugins_loaded' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'admin_menu', array( $this, 'add_menu' ), 19 );
		add_action( 'admin_init', array( $this, 'import_csv' ) );
		add_action( 'wp_ajax_s2w_import_shopify_to_woocommerce_import', array( $this, 'import' ) );
		add_action( 's2w_import_shopify_to_woocommerce_importer_scheduled_cleanup', array(
			$this,
			'scheduled_cleanup'
		) );
	}

	public static function set( $name, $set_name = false ) {
		return VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::set( $name, $set_name );
	}

	public function scheduled_cleanup( $attachment_id ) {
		if ( $attachment_id ) {
			wp_delete_attachment( $attachment_id, true );
		}
	}

	public function admin_notices() {
		if ( self::$process->is_downloading() ) {
			?>
            <div class="updated">
                <p>
					<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: Product images are being downloaded in the background.', 's2w-import-shopify-to-woocommerce' ) ?>
                </p>
                <p>
					<?php printf( __( 'Please goto <a target="_blank" href="%s">Media</a> and view downloaded product images. If <strong>some images are downloaded repeatedly and no new images are downloaded</strong>, please <strong>1. Stop importing product</strong>, <strong>2.  <a class="s2w-cancel-download-images-button" href="%s">Cancel downloading</a></strong> immediately and contact <strong>support@villatheme.com</strong> for help.', 's2w-import-shopify-to-woocommerce' ), admin_url( 'upload.php' ), add_query_arg( array( 's2w_cancel_download_image_for_import_csv' => '1', ), $_SERVER['REQUEST_URI'] ) ) ?>
                </p>
            </div>
			<?php
		} elseif ( ! self::$process->is_queue_empty() ) {
			?>
            <div class="updated">
                <p>
					<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: There are products images in the queue.', 's2w-import-shopify-to-woocommerce' ) ?>
                </p>
                <p>
					<?php _e( 'If your importing from CSV is still <strong>in progress</strong>, you can skip this message. It will automatically start downloading images after all products are imported.', 's2w-import-shopify-to-woocommerce' ) ?>
                </p>
                <p>
					<?php printf( __( 'If your importing from CSV is <strong>completed or interrupted in the middle</strong>, you can <strong><a class="s2w-start-download-images-button" href="%s">Start downloading</a></strong> to download images for imported products Or <strong><a class="s2w-empty-queue-images-button" href="%s">Empty queue</a></strong> if you don\'t need those images to be downloaded anymore.', 's2w-import-shopify-to-woocommerce' ), add_query_arg( array( 's2w_start_download_image_for_import_csv' => '1', ), $_SERVER['REQUEST_URI'] ), add_query_arg( array( 's2w_cancel_download_image_for_import_csv' => '1', ), $_SERVER['REQUEST_URI'] ) ) ?>
                </p>
            </div>
			<?php
		} elseif ( get_transient( 's2w_background_processing_complete_for_import_csv' ) ) {
			delete_transient( 's2w_background_processing_complete_for_import_csv' );
			?>
            <div class="updated">
                <p>
					<?php esc_html_e( 'S2W - Import Shopify to WooCommerce: Product images are downloaded successfully.', 's2w-import-shopify-to-woocommerce' ) ?>
                </p>
            </div>
			<?php
		}
	}

	public function plugins_loaded() {
		self::$process = new WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_For_Import_Csv();
		if ( isset( $_REQUEST['s2w_cancel_download_image_for_import_csv'] ) && $_REQUEST['s2w_cancel_download_image_for_import_csv'] ) {
			delete_transient( 's2w_background_processing_complete_for_import_csv' );
			self::$process->kill_process();
			wp_safe_redirect( @remove_query_arg( 's2w_cancel_download_image_for_import_csv' ) );
			exit;
		} elseif ( isset( $_REQUEST['s2w_start_download_image_for_import_csv'] ) && $_REQUEST['s2w_start_download_image_for_import_csv'] ) {
			self::$process->dispatch();
			wp_safe_redirect( @remove_query_arg( 's2w_start_download_image_for_import_csv' ) );
			exit;
		}
	}

	public function add_menu() {
		$menu_slug = 's2w-import-shopify-to-woocommerce-import-csv';
		add_submenu_page(
			's2w-import-shopify-to-woocommerce',
			esc_html__( 'Import CSV', 's2w-import-shopify-to-woocommerce' ),
			esc_html__( 'Import CSV', 's2w-import-shopify-to-woocommerce' ), apply_filters( 'vi_s2w_admin_sub_menu_capability', 'manage_options', $menu_slug ),
            $menu_slug, array(
				$this,
				'import_csv_callback'
			)
		);
	}

	public function import_csv() {
		global $pagenow;
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
		if ( $pagenow === 'admin.php' && $page === 's2w-import-shopify-to-woocommerce-import-csv' ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}
			$this->step     = isset( $_REQUEST['step'] ) ? sanitize_text_field( $_REQUEST['step'] ) : '';
			$this->file_url = isset( $_REQUEST['file_url'] ) ? urldecode_deep( $_REQUEST['file_url'] ) : '';
			if ( $this->step == 'mapping' ) {
				if ( is_file( $this->file_url ) ) {
					if ( ( $handle = fopen( $this->file_url, "r" ) ) !== false ) {
						$this->header = fgetcsv( $handle, 0, "," );
						fclose( $handle );
						if ( ! count( $this->header ) ) {
							$this->step  = '';
							$this->error = esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' );
						}
					} else {
						$this->step  = '';
						$this->error = esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' );
					}
				} else {
					$this->step  = '';
					$this->error = esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' );
				}
			}

			if ( ! isset( $_POST['_s2w_import_shopify_to_woocommerce_import_nonce'] ) || ! wp_verify_nonce( $_POST['_s2w_import_shopify_to_woocommerce_import_nonce'], 's2w_import_shopify_to_woocommerce_import_action_nonce' ) ) {
				return;
			}
			if ( isset( $_POST['s2w_import_shopify_to_woocommerce_import'] ) ) {
				$this->step                 = 'import';
				$this->file_url             = isset( $_POST['s2w_import_shopify_to_woocommerce_file_url'] ) ? stripslashes( $_POST['s2w_import_shopify_to_woocommerce_file_url'] ) : '';
				$this->nonce                = isset( $_POST['_s2w_import_shopify_to_woocommerce_import_nonce'] ) ? sanitize_text_field( $_POST['_s2w_import_shopify_to_woocommerce_import_nonce'] ) : '';
				$this->products_per_request = isset( $_POST['s2w_products_per_request'] ) ? sanitize_text_field( $_POST['s2w_products_per_request'] ) : '1';
				$map_to                     = isset( $_POST['s2w_map_to'] ) ? array_map( 'sanitize_text_field', $_POST['s2w_map_to'] ) : array();
				if ( is_file( $this->file_url ) ) {
					if ( ( $file_handle = fopen( $this->file_url, "r" ) ) !== false ) {
						$header  = fgetcsv( $file_handle, 0, "," );
						$headers = array(
							'handle'           => 'Handle',
							'title'            => 'Title',
							'body_html'        => 'Body (HTML)',
							'type'             => 'Type',
							'tags'             => 'Tags',
							'option1_name'     => 'Option1 Name',
							'option1_value'    => 'Option1 Value',
							'option2_name'     => 'Option2 Name',
							'option2_value'    => 'Option2 Value',
							'option3_name'     => 'Option3 Name',
							'option3_value'    => 'Option3 Value',
							'sku'              => 'Variant SKU',
//									'weight'=>'Variant Grams',
							'price'            => 'Variant Price',
							'compare_at_price' => 'Variant Compare At Price',
							'image'            => 'Image Src',
							'image_alt'        => 'Image Alt Text',
							'variant_image'    => 'Variant Image',
						);
						$index   = array();
						foreach ( $headers as $header_k => $header_v ) {
							$field_index = array_search( $map_to[ $header_k ], $header );
							if ( $field_index === false ) {
								$index[ $header_k ] = - 1;
							} else {
								$index[ $header_k ] = $field_index;
							}
						}
						$required_fields = array(
							'handle',
							'title',
							'price',
							'compare_at_price',
						);
						foreach ( $required_fields as $required_field ) {
							if ( 0 > $index[ $required_field ] ) {
								wp_safe_redirect( add_query_arg( array( 's2w_error' => 1 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&step=mapping&file_url=' . urlencode( $this->file_url ) ) ) );
								exit();
							}
						}
						if ( ( ( 0 > $index['option2_name'] && - 1 < $index['option2_value'] ) || ( - 1 < $index['option2_name'] && 0 > $index['option2_value'] ) ) || ( 0 > $index['option3_name'] && - 1 < $index['option3_value'] ) || ( - 1 < $index['option3_name'] && 0 > $index['option3_value'] ) ) {
							wp_safe_redirect( add_query_arg( array( 's2w_error' => 2 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&step=mapping&file_url=' . urlencode( $this->file_url ) ) ) );
							exit();
						}
						$this->index = $index;
					} else {
						wp_safe_redirect( add_query_arg( array( 's2w_error' => 3 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&file_url=' . urlencode( $this->file_url ) ) ) );
						exit();
					}
				} else {
					wp_safe_redirect( add_query_arg( array( 's2w_error' => 4 ), admin_url( 'admin.php?page=s2w-import-shopify-to-woocommerce-import-csv&file_url=' . urlencode( $this->file_url ) ) ) );
					exit();
				}

			} else if ( isset( $_POST['s2w_import_shopify_to_woocommerce_select_file'] ) ) {
				if ( ! isset( $_FILES['s2w_import_shopify_to_woocommerce_file'] ) ) {
					$error = new WP_Error( 's2w_import_shopify_to_woocommerce_csv_importer_upload_file_empty', __( 'File is empty. Please upload something more substantial. This error could also be caused by uploads being disabled in your php.ini or by post_max_size being defined as smaller than upload_max_filesize in php.ini.', 's2w-import-shopify-to-woocommerce' ) );
					wp_die( $error->get_error_messages() );
				} elseif ( ! empty( $_FILES['s2w_import_shopify_to_woocommerce_file']['error'] ) ) {
					$error = new WP_Error( 's2w_import_shopify_to_woocommerce_csv_importer_upload_file_error', __( 'File is error.', 's2w-import-shopify-to-woocommerce' ) );
					wp_die( $error->get_error_messages() );
				} else {
					$import    = $_FILES['s2w_import_shopify_to_woocommerce_file'];
					$overrides = array(
						'test_form' => false,
						'mimes'     => array(
							'csv' => 'text/csv',
						),
						'test_type' => false,
					);
					$upload    = wp_handle_upload( $import, $overrides );
					if ( isset( $upload['error'] ) ) {
						wp_die( $upload['error'] );
					}
					// Construct the object array.
					$object = array(
						'post_title'     => basename( $upload['file'] ),
						'post_content'   => $upload['url'],
						'post_mime_type' => $upload['type'],
						'guid'           => $upload['url'],
						'context'        => 'import',
						'post_status'    => 'private',
					);

					// Save the data.
					$id = wp_insert_attachment( $object, $upload['file'] );
					if ( is_wp_error( $id ) ) {
						wp_die( $id->get_error_messages() );
					}
					/*
					 * Schedule a cleanup for one day from now in case of failed
					 * import or missing wp_import_cleanup() call.
					 */
					wp_schedule_single_event( time() + DAY_IN_SECONDS, 's2w_import_shopify_to_woocommerce_importer_scheduled_cleanup', array( $id ) );
					wp_safe_redirect( add_query_arg( array(
						'step'     => 'mapping',
						'file_url' => urlencode( $upload['file'] ),
					) ) );
					exit();
				}
			}

		}
	}

	public function import_product( $product, $import_options ) {
		if ( ! count( $product ) ) {
			return;
		}
		global $wp_taxonomies;
		wp_suspend_cache_invalidation( true );
		vi_s2w_set_time_limit();
		$attr_data                   = array();
		$options                     = isset( $product['options'] ) ? $product['options'] : array();
		$variations                  = isset( $product['variants'] ) ? $product['variants'] : array();
		$download_images             = $import_options['download_images'];
		$disable_background_process  = $import_options['disable_background_process'];
		$download_description_images = $import_options['download_description_images'];
		$download_images_later       = $import_options['download_images_later'];
		$global_attributes           = $import_options['global_attributes'];
		$product_status              = $import_options['product_status'];
		$keep_slug                   = $import_options['keep_slug'];
		$manage_stock                = $import_options['manage_stock'];
		$product_categories          = $import_options['product_categories'];
		$placeholder_image_id        = $import_options['placeholder_image_id'];
		if ( $download_images ) {
			$history['last_product_error'] = 1;
			if ( ( is_array( $options ) && count( $options ) ) || ( is_array( $variations ) && count( $variations ) ) ) {
				if ( ( count( $options ) == 1 && count( $options[0]['values'] ) == 1 ) || ( count( $variations ) == 1 ) ) {
					$regular_price = $variations[0]['compare_at_price'];
					$sale_price    = $variations[0]['price'];
					if ( ! floatval( $regular_price ) || floatval( $regular_price ) == floatval( $sale_price ) ) {
						$regular_price = $sale_price;
						$sale_price    = '';
					}
					$description = isset( $product['body_html'] ) ? html_entity_decode( $product['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
					if ( $options[0]['name'] !== 'Title' && $options[0]['values'][0] !== 'Default Title' ) {
						if ( $global_attributes ) {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::create_product_global_attribute( $options[0], $attr_data );
						} else {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::create_product_custom_attribute( $options[0], $attr_data );
						}
					}
					$data = array( // Set up the basic post data to insert for our product
						'post_type'    => 'product',
						'post_excerpt' => '',
						'post_content' => $description,
						'post_title'   => isset( $product['title'] ) ? $product['title'] : '',
						'post_status'  => $product_status,
						'post_parent'  => '',

						'meta_input' => array(
							'_sku'           => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $variations[0]['sku'] ) ? '' : $variations[0]['sku'],
							'_visibility'    => 'visible',
							'_regular_price' => $regular_price,
							'_price'         => $regular_price,
						)
					);
					if ( $keep_slug && $product['handle'] ) {
						$data['post_name'] = $product['handle'];
					}

					if ( $sale_price ) {
						$data['meta_input']['_sale_price'] = $sale_price;
						$data['meta_input']['_price']      = $sale_price;
					}
					$product_id = wp_insert_post( $data );
					if ( ! is_wp_error( $product_id ) ) {
						$dispatch = false;
						if ( $description && $download_description_images ) {
							preg_match_all( '/src="([\s\S]*?)"/im', $description, $matches );
							if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
								$description_images = array_unique( $matches[1] );
								if ( $disable_background_process ) {
									foreach ( $description_images as $description_image ) {
										S2W_Error_Images_Table::insert( $product_id, '', $description_image, '', 2, '' );
									}
								} else {
									$dispatch = true;
									foreach ( $description_images as $description_image ) {
										$images_data = array(
											'id'          => '',
											'src'         => $description_image,
											'alt'         => '',
											'parent_id'   => $product_id,
											'product_ids' => array(),
											'set_gallery' => 2,
										);
										self::$process->push_to_queue( $images_data );
									}
								}
							}
						}
						$images_d = array();
						$images   = isset( $product['images'] ) ? $product['images'] : array();
						if ( count( $images ) ) {
							foreach ( $images as $image ) {
								$images_d[] = array(
									'src'         => $image['src'],
									'alt'         => $image['alt'],
									'parent_id'   => $product_id,
									'product_ids' => array(),
									'set_gallery' => 1,
								);
							}
							$images_d[0]['product_ids'][] = $product_id;
							$images_d[0]['set_gallery']   = 0;
							if ( $placeholder_image_id ) {
								update_post_meta( $product_id, '_thumbnail_id', $placeholder_image_id );
							}
						}
						wp_set_object_terms( $product_id, 'simple', 'product_type' );
//						if ( ! empty( $product['product_type'] ) ) {
//							wp_set_object_terms( $product_id, $product['product_type'], 'product_cat', true );
//						}
						if ( is_array( $product_categories ) && count( $product_categories ) ) {
							wp_set_post_terms( $product_id, $product_categories, 'product_cat', true );
						}

						$tags = isset( $product['tags'] ) ? $product['tags'] : '';
						if ( $tags ) {
							wp_set_object_terms( $product_id, explode( ',', $product['tags'] ), 'product_tag' );
						}
						$product_obj = wc_get_product( $product_id );
						if ( $product_obj ) {
							if ( count( $attr_data ) ) {
								$product_obj->set_attributes( $attr_data );
							}
							$product_obj->save();
						}
						if ( count( $images_d ) ) {
							if ( $disable_background_process ) {
								foreach ( $images_d as $images_d_k => $images_d_v ) {
									S2W_Error_Images_Table::insert( $product_id, implode( ',', $images_d_v['product_ids'] ), $images_d_v['src'], $images_d_v['alt'], intval( $images_d_v['set_gallery'] ), $images_d_v['id'] );
								}
							} else {
								$dispatch = true;
								foreach ( $images_d as $images_d_k => $images_d_v ) {
									self::$process->push_to_queue( $images_d_v );
								}
							}
						}
						if ( $dispatch ) {
							if ( $download_images_later ) {
								self::$process->save();
							} else {
								self::$process->save()->dispatch();
							}
						}
						$history['last_product_error'] = '';
					}
				} else {
					self::create_product_attributes( $global_attributes, $options, $attr_data );
					$description = isset( $product['body_html'] ) ? html_entity_decode( $product['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
					$data        = array( // Set up the basic post data to insert for our product
						'post_type'    => 'product',
						'post_excerpt' => '',
						'post_content' => $description,
						'post_title'   => isset( $product['title'] ) ? $product['title'] : '',
						'post_status'  => $product_status,
						'post_parent'  => '',
						'meta_input'   => array(
							'_visibility'   => 'visible',
							'_manage_stock' => 'no',
						)
					);
					if ( $keep_slug && $product['handle'] ) {
						$data['post_name'] = $product['handle'];
					}
					$product_id = wp_insert_post( $data );
					if ( ! is_wp_error( $product_id ) ) {
						$dispatch = false;
						if ( $description && $download_description_images ) {
							preg_match_all( '/src="([\s\S]*?)"/im', $description, $matches );
							if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
								$description_images = array_unique( $matches[1] );
								if ( $disable_background_process ) {
									foreach ( $description_images as $description_image ) {
										S2W_Error_Images_Table::insert( $product_id, '', $description_image, '', 2, '' );
									}
								} else {
									$dispatch = true;
									foreach ( $description_images as $description_image ) {
										$images_data = array(
											'id'          => '',
											'src'         => $description_image,
											'alt'         => '',
											'parent_id'   => $product_id,
											'product_ids' => array(),
											'set_gallery' => 2,
										);
										self::$process->push_to_queue( $images_data );
									}
								}
							}
						}
						wp_set_object_terms( $product_id, 'variable', 'product_type' );
						if ( count( $attr_data ) ) {
							$product_obj = wc_get_product( $product_id );
							if ( $product_obj ) {
								$product_obj->set_attributes( $attr_data );
								$product_obj->save();
								wp_set_object_terms( $product_id, 'variable', 'product_type' );
							}
						}
						$images_d   = array();
						$images_src = array();
						$images     = isset( $product['images'] ) ? $product['images'] : array();
						if ( count( $images ) ) {
							foreach ( $images as $image ) {
								$images_d[]   = array(
									'src'         => $image['src'],
									'alt'         => $image['alt'],
									'parent_id'   => $product_id,
									'product_ids' => array(),
									'set_gallery' => 1,
								);
								$images_src[] = $image['src'];
							}
							$images_d[0]['product_ids'][] = $product_id;
							$images_d[0]['set_gallery']   = 0;
							if ( $placeholder_image_id ) {
								update_post_meta( $product_id, '_thumbnail_id', $placeholder_image_id );
							}
						}
//						if ( ! empty( $product['product_type'] ) ) {
//							wp_set_object_terms( $product_id, $product['product_type'], 'product_cat', true );
//						}
						if ( is_array( $product_categories ) && count( $product_categories ) ) {
							wp_set_post_terms( $product_id, $product_categories, 'product_cat', true );
						}
						$tags = isset( $product['tags'] ) ? $product['tags'] : '';
						if ( $tags ) {
							wp_set_object_terms( $product_id, explode( ',', $product['tags'] ), 'product_tag' );
						}
						if ( is_array( $variations ) && count( $variations ) ) {
							foreach ( $variations as $variation ) {
								vi_s2w_set_time_limit();
								$regular_price = $variation['compare_at_price'];
								$sale_price    = $variation['price'];
								if ( ! floatval( $regular_price ) || floatval( $regular_price ) == floatval( $sale_price ) ) {
									$regular_price = $sale_price;
									$sale_price    = '';
								}
								$variation_obj = new WC_Product_Variation();
								$variation_obj->set_parent_id( $product_id );
								$attributes = array();
								if ( $global_attributes ) {
									foreach ( $options as $option_k => $option_v ) {
										$j = $option_k + 1;
										if ( isset( $variation[ 'option' . $j ] ) && $variation[ 'option' . $j ] ) {
											$attribute_id  = wc_attribute_taxonomy_id_by_name( $option_v['name'] );
											$attribute_obj = wc_get_attribute( $attribute_id );
											if ( $attribute_obj ) {
												$attribute_value = get_term_by( 'name', $variation[ 'option' . $j ], $attribute_obj->slug );
												if ( $attribute_value ) {
													$attributes[ VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $attribute_obj->slug ) ] = $attribute_value->slug;
												}
											}
										}
									}
								} else {
									foreach ( $options as $option_k => $option_v ) {
										$j = $option_k + 1;
										if ( isset( $variation[ 'option' . $j ] ) && $variation[ 'option' . $j ] ) {
											$attributes[ VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $option_v['name'] ) ] = $variation[ 'option' . $j ];
										}
									}
								}
								$variation_obj->set_attributes( $attributes );
								$fields = array(
									'sku'           => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $variation['sku'] ) ? '' : $variation['sku'],
									'regular_price' => $regular_price,
								);

								if ( $sale_price ) {
									$fields['sale_price'] = $sale_price;
								}
								foreach ( $fields as $field => $field_v ) {
									$variation_obj->{"set_$field"}( wc_clean( $field_v ) );
								}
								do_action( 'product_variation_linked', $variation_obj->save() );
								$variation_obj_id = $variation_obj->get_id();
								if ( $variation['image'] ) {
									$variation_image_search = array_search( $variation['image'], $images_src );
									if ( $variation_image_search !== false ) {
										$images_d[ $variation_image_search ]['product_ids'][] = $variation_obj_id;
									} else {
										$images_d[] = array(
											'src'         => $variation['image'],
											'alt'         => '',
											'parent_id'   => $product_id,
											'product_ids' => array( $variation_obj_id ),
											'set_gallery' => 0,
										);
									}
									if ( $placeholder_image_id ) {
										update_post_meta( $variation_obj_id, '_thumbnail_id', $placeholder_image_id );
									}
								}
							}
						}
						if ( count( $images_d ) ) {
							if ( $disable_background_process ) {
								foreach ( $images_d as $images_d_k => $images_d_v ) {
									S2W_Error_Images_Table::insert( $product_id, implode( ',', $images_d_v['product_ids'] ), $images_d_v['src'], $images_d_v['alt'], intval( $images_d_v['set_gallery'] ), $images_d_v['id'] );
								}
							} else {
								$dispatch = true;
								foreach ( $images_d as $images_d_k => $images_d_v ) {
									self::$process->push_to_queue( $images_d_v );
								}
							}
						}
						if ( $dispatch ) {
							if ( $download_images_later ) {
								self::$process->save();
							} else {
								self::$process->save()->dispatch();
							}
						}
						$history['last_product_error'] = '';
					}
				}
			}
		} else {
			if ( ( is_array( $options ) && count( $options ) ) || ( is_array( $variations ) && count( $variations ) ) ) {
				if ( ( count( $options ) == 1 && count( $options[0]['values'] ) == 1 ) || ( count( $variations ) == 1 ) ) {
					$regular_price = $variations[0]['compare_at_price'];
					$sale_price    = $variations[0]['price'];
					if ( ! floatval( $regular_price ) || floatval( $regular_price ) == floatval( $sale_price ) ) {
						$regular_price = $sale_price;
						$sale_price    = '';
					}
					$description = isset( $product['body_html'] ) ? html_entity_decode( $product['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
					if ( $options[0]['name'] !== 'Title' && $options[0]['values'][0] !== 'Default Title' ) {
						if ( $global_attributes ) {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::create_product_global_attribute( $options[0], $attr_data );
						} else {
							S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE::create_product_custom_attribute( $options[0], $attr_data );
						}
					}
					$data = array( // Set up the basic post data to insert for our product
						'post_type'    => 'product',
						'post_excerpt' => '',
						'post_content' => $description,
						'post_title'   => isset( $product['title'] ) ? $product['title'] : '',
						'post_status'  => $product_status,
						'post_parent'  => '',

						'meta_input' => array(
							'_sku'           => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $variations[0]['sku'] ) ? '' : $variations[0]['sku'],
							'_visibility'    => 'visible',
							'_regular_price' => $regular_price,
							'_price'         => $regular_price,
						)
					);
					if ( $keep_slug && $product['handle'] ) {
						$data['post_name'] = $product['handle'];
					}

					if ( $sale_price ) {
						$data['meta_input']['_sale_price'] = $sale_price;
						$data['meta_input']['_price']      = $sale_price;
					}
					$product_id = wp_insert_post( $data );
					if ( ! is_wp_error( $product_id ) ) {
						if ( $description && $download_description_images ) {
							preg_match_all( '/src="([\s\S]*?)"/im', $description, $matches );
							if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
								$description_images = array_unique( $matches[1] );
								if ( $disable_background_process ) {
									foreach ( $description_images as $description_image ) {
										S2W_Error_Images_Table::insert( $product_id, '', $description_image, '', 2, '' );
									}
								} else {
									foreach ( $description_images as $description_image ) {
										$images_data = array(
											'id'          => '',
											'src'         => $description_image,
											'alt'         => '',
											'parent_id'   => $product_id,
											'product_ids' => array(),
											'set_gallery' => 2,
										);
										self::$process->push_to_queue( $images_data );
									}
									if ( $download_images_later ) {
										self::$process->save();
									} else {
										self::$process->save()->dispatch();
									}
								}
							}
						}
						wp_set_object_terms( $product_id, 'simple', 'product_type' );
//						if ( ! empty( $product['product_type'] ) ) {
//							wp_set_object_terms( $product_id, $product['product_type'], 'product_cat', true );
//						}
						if ( is_array( $product_categories ) && count( $product_categories ) ) {
							wp_set_post_terms( $product_id, $product_categories, 'product_cat', true );
						}
						$tags = isset( $product['tags'] ) ? $product['tags'] : '';
						if ( $tags ) {
							wp_set_object_terms( $product_id, explode( ',', $product['tags'] ), 'product_tag' );
						}
						$product_obj = wc_get_product( $product_id );
						if ( $product_obj ) {
							if ( count( $attr_data ) ) {
								$product_obj->set_attributes( $attr_data );
							}
							$product_obj->save();
						}
					}
				} else {
					self::create_product_attributes( $global_attributes, $options, $attr_data );
					$description = isset( $product['body_html'] ) ? html_entity_decode( $product['body_html'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) : '';
					$data        = array( // Set up the basic post data to insert for our product
						'post_type'    => 'product',
						'post_excerpt' => '',
						'post_content' => $description,
						'post_title'   => isset( $product['title'] ) ? $product['title'] : '',
						'post_status'  => $product_status,
						'post_parent'  => '',

						'meta_input' => array(
							'_visibility'   => 'visible',
							'_manage_stock' => 'no',
						)
					);
					if ( $keep_slug && $product['handle'] ) {
						$data['post_name'] = $product['handle'];
					}
					$product_id = wp_insert_post( $data );
					if ( ! is_wp_error( $product_id ) ) {
						if ( $description && $download_description_images ) {
							preg_match_all( '/src="([\s\S]*?)"/im', $description, $matches );
							if ( isset( $matches[1] ) && is_array( $matches[1] ) && count( $matches[1] ) ) {
								$description_images = array_unique( $matches[1] );
								if ( $disable_background_process ) {
									foreach ( $description_images as $description_image ) {
										S2W_Error_Images_Table::insert( $product_id, '', $description_image, '', 2, '' );
									}
								} else {
									foreach ( $description_images as $description_image ) {
										$images_data = array(
											'id'          => '',
											'src'         => $description_image,
											'alt'         => '',
											'parent_id'   => $product_id,
											'product_ids' => array(),
											'set_gallery' => 2,
										);
										self::$process->push_to_queue( $images_data );
									}
									if ( $download_images_later ) {
										self::$process->save();
									} else {
										self::$process->save()->dispatch();
									}
								}
							}
						}
						wp_set_object_terms( $product_id, 'variable', 'product_type' );
						if ( count( $attr_data ) ) {
							$product_obj = wc_get_product( $product_id );
							if ( $product_obj ) {
								$product_obj->set_attributes( $attr_data );
								$product_obj->save();
								wp_set_object_terms( $product_id, 'variable', 'product_type' );
							}
						}
//						if ( ! empty( $product['product_type'] ) ) {
//							wp_set_object_terms( $product_id, $product['product_type'], 'product_cat', true );
//						}
						if ( is_array( $product_categories ) && count( $product_categories ) ) {
							wp_set_post_terms( $product_id, $product_categories, 'product_cat', true );
						}
						$tags = isset( $product['tags'] ) ? $product['tags'] : '';
						if ( $tags ) {
							wp_set_object_terms( $product_id, explode( ',', $product['tags'] ), 'product_tag' );
						}
						if ( is_array( $variations ) && count( $variations ) ) {
							foreach ( $variations as $variation ) {
								vi_s2w_set_time_limit();
								$regular_price = $variation['compare_at_price'];
								$sale_price    = $variation['price'];
								if ( ! floatval( $regular_price ) || floatval( $regular_price ) == floatval( $sale_price ) ) {
									$regular_price = $sale_price;
									$sale_price    = '';
								}
								$variation_obj = new WC_Product_Variation();
								$variation_obj->set_parent_id( $product_id );
								$attributes = array();
								if ( $global_attributes ) {
									foreach ( $options as $option_k => $option_v ) {
										$j = $option_k + 1;
										if ( isset( $variation[ 'option' . $j ] ) && $variation[ 'option' . $j ] ) {
											$attribute_id  = wc_attribute_taxonomy_id_by_name( $option_v['name'] );
											$attribute_obj = wc_get_attribute( $attribute_id );
											if ( $attribute_obj ) {
												$attribute_value = get_term_by( 'name', $variation[ 'option' . $j ], $attribute_obj->slug );
												if ( $attribute_value ) {
													$attributes[ VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $attribute_obj->slug ) ] = $attribute_value->slug;
												}
											}
										}
									}
								} else {
									foreach ( $options as $option_k => $option_v ) {
										$j = $option_k + 1;
										if ( isset( $variation[ 'option' . $j ] ) && $variation[ 'option' . $j ] ) {
											$attributes[ VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $option_v['name'] ) ] = $variation[ 'option' . $j ];
										}
									}
								}
								$variation_obj->set_attributes( $attributes );
								$fields = array(
									'sku'           => VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sku_exists( $variation['sku'] ) ? '' : $variation['sku'],
									'regular_price' => $regular_price,
								);

								if ( $sale_price ) {
									$fields['sale_price'] = $sale_price;
								}
								foreach ( $fields as $field => $field_v ) {
									$variation_obj->{"set_$field"}( wc_clean( $field_v ) );
								}
								do_action( 'product_variation_linked', $variation_obj->save() );
							}
						}
					}
				}
			}
		}
		wp_suspend_cache_invalidation( false );

	}

	public function import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => esc_html__( 'You do not have permission.', 's2w-import-shopify-to-woocommerce' ),
				)
			);
		}
		ignore_user_abort( true );
		$file_url             = isset( $_POST['file_url'] ) ? stripslashes( $_POST['file_url'] ) : '';
		$start                = isset( $_POST['start'] ) ? absint( sanitize_text_field( $_POST['start'] ) ) : 0;
		$ftell                = isset( $_POST['ftell'] ) ? absint( sanitize_text_field( $_POST['ftell'] ) ) : 0;
		$total                = isset( $_POST['total'] ) ? absint( sanitize_text_field( $_POST['total'] ) ) : 0;
		$step                 = isset( $_POST['step'] ) ? sanitize_text_field( $_POST['step'] ) : '';
		$index                = isset( $_POST['s2w_index'] ) ? array_map( 'intval', $_POST['s2w_index'] ) : array();
		$products_per_request = isset( $_POST['products_per_request'] ) ? absint( sanitize_text_field( $_POST['products_per_request'] ) ) : 1;
		if ( is_file( $file_url ) ) {
			if ( ( $file_handle = fopen( $file_url, "r" ) ) !== false ) {
				$header = fgetcsv( $file_handle, 0, "," );
				unset( $header );
				$count = 0;
				if ( $step === 'check' ) {
					$count = 1;
					while ( ( $item = fgetcsv( $file_handle, 0, "," ) ) !== false ) {
						$count ++;
					}
					fclose( $file_handle );
					wp_send_json( array(
						'status' => 'success',
						'total'  => $count,
					) );
				}
				$import_options = array(
					'download_images'             => isset( $_POST['download_images'] ) ? sanitize_text_field( $_POST['download_images'] ) : '',
					'disable_background_process'  => isset( $_POST['disable_background_process'] ) ? sanitize_text_field( $_POST['disable_background_process'] ) : '',
					'download_description_images' => isset( $_POST['download_description_images'] ) ? sanitize_text_field( $_POST['download_description_images'] ) : '',
					'download_images_later'       => isset( $_POST['download_images_later'] ) ? sanitize_text_field( $_POST['download_images_later'] ) : '',
					'keep_slug'                   => isset( $_POST['keep_slug'] ) ? sanitize_text_field( $_POST['keep_slug'] ) : '',
					'global_attributes'           => isset( $_POST['global_attributes'] ) ? sanitize_text_field( $_POST['global_attributes'] ) : '',
					'product_status'              => isset( $_POST['product_status'] ) ? sanitize_text_field( $_POST['product_status'] ) : 'publish',
					'product_categories'          => isset( $_POST['product_categories'] ) ? array_map( 'sanitize_text_field', ( $_POST['product_categories'] ) ) : array(),
					'manage_stock'                => ( 'yes' === get_option( 'woocommerce_manage_stock' ) ) ? true : false,
					'placeholder_image_id'        => s2w_get_placeholder_image(),
				);
				$products       = array();
				$new_product    = array();
				$ftell_2        = 0;
				if ( $ftell > 0 ) {
					fseek( $file_handle, $ftell );
				} elseif ( $start > 1 ) {
					for ( $i = 0; $i < $start; $i ++ ) {
						$buff = fgetcsv( $file_handle, 0, "," );
						unset( $buff );
					}
				}
				while ( ( $item = fgetcsv( $file_handle, 0, "," ) ) !== false ) {
					$count ++;
					$handle = $item[ $index['handle'] ];
					$start ++;
					if ( empty( $handle ) ) {
						continue;
					}
					$ftell_1 = ftell( $file_handle );
					vi_s2w_set_time_limit();
					if ( ! in_array( $handle, $products ) ) {
						/*create previous product*/
						$this->import_product( $new_product, $import_options );
						if ( count( $products ) < $products_per_request ) {
							if ( empty( $item[ $index['title'] ] ) ) {
								$ftell_2 = $ftell_1;
								continue;
							}
							$products[]  = $handle;
							$new_product = array(
								'handle'   => $handle,
								'title'    => $item[ $index['title'] ],
								'variants' => array(),
								'options'  => array(),
								'images'   => array(),
							);
							if ( - 1 < $index['body_html'] ) {
								$new_product['body_html'] = $item[ $index['body_html'] ];
							}
							if ( - 1 < $index['tags'] ) {
								$new_product['tags'] = $item[ $index['tags'] ];
							}
							if ( - 1 < $index['type'] ) {
								$new_product['product_type'] = $item[ $index['type'] ];
							}
							$variants = array(
								'sku'              => - 1 < $index['sku'] ? $item[ $index['sku'] ] : '',
								'price'            => $item[ $index['price'] ],
								'compare_at_price' => $item[ $index['compare_at_price'] ],
								'image'            => - 1 < $index['variant_image'] ? $item[ $index['variant_image'] ] : '',
							);
							if ( - 1 < $index['option1_name'] && ! empty( $item[ $index['option1_name'] ] ) ) {
								$variants['option1']      = $item[ $index['option1_value'] ];
								$new_product['options'][] = array(
									'name'   => $item[ $index['option1_name'] ],
									'values' => array( $item[ $index['option1_value'] ] ),
								);
							}
							if ( - 1 < $index['option2_name'] && ! empty( $item[ $index['option2_name'] ] ) ) {
								$variants['option2']      = $item[ $index['option2_value'] ];
								$new_product['options'][] = array(
									'name'   => $item[ $index['option2_name'] ],
									'values' => array( $item[ $index['option2_value'] ] ),
								);
							}
							if ( - 1 < $index['option3_name'] && ! empty( $item[ $index['option3_name'] ] ) ) {
								$variants['option3']      = $item[ $index['option3_value'] ];
								$new_product['options'][] = array(
									'name'   => $item[ $index['option3_name'] ],
									'values' => array( $item[ $index['option3_value'] ] ),
								);
							}
							if ( - 1 < $index['image'] && ! empty( $item[ $index['image'] ] ) ) {
								$new_product['images'][] = array(
									'src' => $item[ $index['image'] ],
									'alt' => - 1 < $index['image_alt'] ? $item[ $index['image_alt'] ] : '',
								);
							}
							$new_product['variants'][] = $variants;
						} else {
							fclose( $file_handle );
							wp_send_json( array(
								'status'   => 'success',
								'products' => $new_product,
								'start'    => $start - 1,
								'ftell'    => $ftell_2,
								'percent'  => intval( 100 * ( $start ) / $total ),
							) );
						}
					} else {
						$variants = array(
							'sku'              => - 1 < $index['sku'] ? $item[ $index['sku'] ] : '',
							'price'            => $item[ $index['price'] ],
							'compare_at_price' => $item[ $index['compare_at_price'] ],
							'image'            => - 1 < $index['variant_image'] ? $item[ $index['variant_image'] ] : '',
						);
						if ( ! empty( $item[ $index['option1_value'] ] ) ) {
							$variants['option1'] = $item[ $index['option1_value'] ];
							if ( ! in_array( $item[ $index['option1_value'] ], $new_product['options'][0]['values'] ) ) {
								$new_product['options'][0]['values'][] = $item[ $index['option1_value'] ];
							}
						}
						if ( - 1 < $index['option2_value'] && ! empty( $item[ $index['option2_value'] ] ) ) {
							$variants['option2'] = $item[ $index['option2_value'] ];
							if ( ! in_array( $item[ $index['option2_value'] ], $new_product['options'][1]['values'] ) ) {
								$new_product['options'][1]['values'][] = $item[ $index['option2_value'] ];
							}
						}
						if ( - 1 < $index['option3_value'] && ! empty( $item[ $index['option3_value'] ] ) ) {
							$variants['option3'] = $item[ $index['option3_value'] ];
							if ( ! in_array( $item[ $index['option3_value'] ], $new_product['options'][2]['values'] ) ) {
								$new_product['options'][2]['values'][] = $item[ $index['option3_value'] ];
							}
						}
						if ( - 1 < $index['image'] && ! empty( $item[ $index['image'] ] ) ) {
							$new_product['images'][] = array(
								'src' => $item[ $index['image'] ],
								'alt' => - 1 < $index['image_alt'] ? $item[ $index['image_alt'] ] : '',
							);
						}
						$new_product['variants'][] = $variants;
					}
					unset( $item );
					$next_item = fgetcsv( $file_handle, 0, "," );
					if ( false === $next_item ) {
						/*create previous product*/
						$this->import_product( $new_product, $import_options );
						if ( ! $import_options['disable_background_process'] && $import_options['download_images'] && $import_options['download_images_later'] ) {
							self::$process->dispatch();
						}
						fclose( $file_handle );
						wp_send_json( array(
							'status'  => 'finish',
							'start'   => $start,
							'ftell'   => $ftell_1,
							'percent' => intval( 100 * ( $start ) / $total ),
						) );
					} else {
						$count ++;
						$handle = $next_item[ $index['handle'] ];
						$start ++;
						if ( empty( $handle ) ) {
							continue;
						}
						$ftell_2 = ftell( $file_handle );
						if ( ! in_array( $handle, $products ) ) {
							/*create previous product*/
							$this->import_product( $new_product, $import_options );
							if ( count( $products ) < $products_per_request ) {
								if ( empty( $next_item[ $index['title'] ] ) ) {
									continue;
								}
								$products[]  = $handle;
								$new_product = array(
									'handle'   => $handle,
									'title'    => $next_item[ $index['title'] ],
									'variants' => array(),
									'options'  => array(),
									'images'   => array(),
								);
								if ( - 1 < $index['body_html'] ) {
									$new_product['body_html'] = $next_item[ $index['body_html'] ];
								}
								if ( - 1 < $index['tags'] ) {
									$new_product['tags'] = $next_item[ $index['tags'] ];
								}
								if ( - 1 < $index['type'] ) {
									$new_product['product_type'] = $next_item[ $index['type'] ];
								}
								$variants = array(
									'sku'              => - 1 < $index['sku'] ? $next_item[ $index['sku'] ] : '',
									'price'            => $next_item[ $index['price'] ],
									'compare_at_price' => $next_item[ $index['compare_at_price'] ],
									'image'            => - 1 < $index['variant_image'] ? $next_item[ $index['variant_image'] ] : '',
								);
								if ( - 1 < $index['option1_name'] && ! empty( $next_item[ $index['option1_name'] ] ) ) {
									$variants['option1']      = $next_item[ $index['option1_value'] ];
									$new_product['options'][] = array(
										'name'   => $next_item[ $index['option1_name'] ],
										'values' => array( $next_item[ $index['option1_value'] ] ),
									);
								}
								if ( - 1 < $index['option2_name'] && ! empty( $next_item[ $index['option2_name'] ] ) ) {
									$variants['option2']      = $next_item[ $index['option2_value'] ];
									$new_product['options'][] = array(
										'name'   => $next_item[ $index['option2_name'] ],
										'values' => array( $next_item[ $index['option2_value'] ] ),
									);
								}
								if ( - 1 < $index['option3_name'] && ! empty( $next_item[ $index['option3_name'] ] ) ) {
									$variants['option3']      = $next_item[ $index['option3_value'] ];
									$new_product['options'][] = array(
										'name'   => $next_item[ $index['option3_name'] ],
										'values' => array( $next_item[ $index['option3_value'] ] ),
									);
								}
								if ( - 1 < $index['image'] && ! empty( $next_item[ $index['image'] ] ) ) {
									$new_product['images'][] = array(
										'src' => $next_item[ $index['image'] ],
										'alt' => - 1 < $index['image_alt'] ? $next_item[ $index['image_alt'] ] : '',
									);
								}
								$new_product['variants'][] = $variants;
							} else {
								fclose( $file_handle );
								wp_send_json( array(
									'status'   => 'success',
									'products' => $new_product,
									'start'    => $start - 1,
									'ftell'    => $ftell_1,
									'percent'  => intval( 100 * ( $start ) / $total ),
								) );
							}

						} else {
							$variants = array(
								'sku'              => - 1 < $index['sku'] ? $next_item[ $index['sku'] ] : '',
								'price'            => $next_item[ $index['price'] ],
								'compare_at_price' => $next_item[ $index['compare_at_price'] ],
								'image'            => - 1 < $index['variant_image'] ? $next_item[ $index['variant_image'] ] : '',
							);
							if ( ! empty( $next_item[ $index['option1_value'] ] ) ) {
								$variants['option1'] = $next_item[ $index['option1_value'] ];
								if ( ! in_array( $next_item[ $index['option1_value'] ], $new_product['options'][0]['values'] ) ) {
									$new_product['options'][0]['values'][] = $next_item[ $index['option1_value'] ];
								}
							}
							if ( - 1 < $index['option2_value'] && ! empty( $next_item[ $index['option2_value'] ] ) ) {
								$variants['option2'] = $next_item[ $index['option2_value'] ];
								if ( ! in_array( $next_item[ $index['option2_value'] ], $new_product['options'][1]['values'] ) ) {
									$new_product['options'][1]['values'][] = $next_item[ $index['option2_value'] ];
								}
							}
							if ( - 1 < $index['option3_value'] && ! empty( $next_item[ $index['option3_value'] ] ) ) {
								$variants['option3'] = $next_item[ $index['option3_value'] ];
								if ( ! in_array( $next_item[ $index['option3_value'] ], $new_product['options'][2]['values'] ) ) {
									$new_product['options'][2]['values'][] = $next_item[ $index['option3_value'] ];
								}
							}
							if ( - 1 < $index['image'] && ! empty( $next_item[ $index['image'] ] ) ) {
								$new_product['images'][] = array(
									'src' => $next_item[ $index['image'] ],
									'alt' => - 1 < $index['image_alt'] ? $next_item[ $index['image_alt'] ] : '',
								);
							}
							$new_product['variants'][] = $variants;
						}
						unset( $next_item );
					}
				}
				$this->import_product( $new_product, $import_options );
				if ( ! $import_options['disable_background_process'] && $import_options['download_images'] && $import_options['download_images_later'] ) {
					self::$process->dispatch();
				}
				fclose( $file_handle );
				wp_send_json( array(
					'status'  => 'finish',
					'start'   => $start,
					'percent' => intval( 100 * ( $start ) / $total ),
				) );
			} else {
				wp_send_json(
					array(
						'status'  => 'error',
						'message' => esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' ),
					)
				);
			}
		} else {
			wp_send_json(
				array(
					'status'  => 'error',
					'message' => esc_html__( 'Invalid file.', 's2w-import-shopify-to-woocommerce' ),
				)
			);
		}
	}

	public function admin_enqueue_scripts() {
		global $wp_scripts;
		$scripts = $wp_scripts->registered;
		foreach ( $scripts as $k => $script ) {
			preg_match( '/select2/i', $k, $result );
			if ( count( array_filter( $result ) ) ) {
				unset( $wp_scripts->registered[ $k ] );
				wp_dequeue_script( $script->handle );
			}
			preg_match( '/bootstrap/i', $k, $result );
			if ( count( array_filter( $result ) ) ) {
				unset( $wp_scripts->registered[ $k ] );
				wp_dequeue_script( $script->handle );
			}
		}
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-semantic-js-form', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'form.min.js', array( 'jquery' ) );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-form', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'form.min.css' );
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-semantic-js-progress', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'progress.min.js', array( 'jquery' ) );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-progress', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'progress.min.css' );
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-semantic-js-checkbox', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'checkbox.min.js', array( 'jquery' ) );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-checkbox', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'checkbox.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-input', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'input.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-table', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'table.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-segment', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'segment.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-label', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'label.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-menu', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'menu.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-button', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'button.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-css-dropdown', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'dropdown.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-transition-css', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'transition.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-message-css', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'message.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-icon-css', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'icon.min.css' );
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-select2', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'select2.js', array( 'jquery' ) );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-select2', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'select2.min.css' );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-semantic-step-css', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'step.min.css' );
		/*Color picker*/
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );
		wp_enqueue_script(
			'iris', admin_url( 'js/iris.min.js' ), array(
			'jquery-ui-draggable',
			'jquery-ui-slider',
			'jquery-touch-punch'
		), false, 1 );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-transition-css', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'transition.min.css' );
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-transition', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'transition.min.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-dropdown', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'dropdown.min.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
		wp_enqueue_script( 's2w-import-shopify-to-woocommerce-import', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_JS . 'import-csv.js', array( 'jquery' ), VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
		wp_enqueue_style( 's2w-import-shopify-to-woocommerce-import', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_CSS . 'import-csv.css', '', VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_VERSION );
		wp_localize_script( 's2w-import-shopify-to-woocommerce-import', 's2w_import_shopify_to_woocommerce_import_params', array(
			'url'                         => admin_url( 'admin-ajax.php' ),
			'step'                        => $this->step,
			'file_url'                    => $this->file_url,
			'nonce'                       => $this->nonce,
			's2w_index'                   => $this->index,
			'products_per_request'        => $this->products_per_request,
			'custom_start'                => isset( $_POST['s2w_custom_start'] ) ? sanitize_text_field( $_POST['s2w_custom_start'] ) : 1,
			'disable_background_process'  => isset( $_POST['s2w_disable_background_process'] ) ? sanitize_text_field( $_POST['s2w_disable_background_process'] ) : '',
			'download_description_images' => isset( $_POST['s2w_download_description_images'] ) ? sanitize_text_field( $_POST['s2w_download_description_images'] ) : '',
			'download_images'             => isset( $_POST['s2w_download_images'] ) ? sanitize_text_field( $_POST['s2w_download_images'] ) : '',
			'download_images_later'       => isset( $_POST['s2w_download_images_later'] ) ? sanitize_text_field( $_POST['s2w_download_images_later'] ) : '',
			'keep_slug'                   => isset( $_POST['s2w_keep_slug'] ) ? sanitize_text_field( $_POST['s2w_keep_slug'] ) : '',
			'global_attributes'           => isset( $_POST['s2w_global_attributes'] ) ? sanitize_text_field( $_POST['s2w_global_attributes'] ) : '',
			'product_status'              => isset( $_POST['s2w_product_status'] ) ? sanitize_text_field( $_POST['s2w_product_status'] ) : 'publish',
			'product_categories'          => isset( $_POST['s2w_product_categories'] ) ? array_map( 'sanitize_text_field', ( $_POST['s2w_product_categories'] ) ) : array(),
			'required_fields'             => array(
				'handle'           => 'Handle',
				'title'            => 'Title',
				'price'            => 'Variant Price',
				'compare_at_price' => 'Variant Compare At Price',
			),
		) );
	}

	public function import_csv_callback() {
		?>
        <div class="wrap">
            <h2><?php esc_html_e( 'Import Product From CSV file', 's2w-import-shopify-to-woocommerce' ); ?></h2>
			<?php
			$steps_state = array(
				'start'   => '',
				'mapping' => '',
				'import'  => '',
			);
			if ( $this->step == 'mapping' ) {
				$steps_state['start']   = '';
				$steps_state['mapping'] = 'active';
				$steps_state['import']  = 'disabled';
			} elseif ( $this->step == 'import' ) {
				$steps_state['start']   = '';
				$steps_state['mapping'] = '';
				$steps_state['import']  = 'active';
			} else {
				$steps_state['start']   = 'active';
				$steps_state['mapping'] = 'disabled';
				$steps_state['import']  = 'disabled';
			}
			?>
            <div class="vi-ui segment">
                <div class="vi-ui steps fluid">
                    <div class="step <?php echo esc_attr( $steps_state['start'] ) ?>">
                        <i class="upload icon"></i>
                        <div class="content">
                            <div class="title"><?php esc_html_e( 'Select file', 's2w-import-shopify-to-woocommerce' ); ?></div>
                        </div>
                    </div>
                    <div class="step <?php echo esc_attr( $steps_state['mapping'] ) ?>">
                        <i class="exchange icon"></i>
                        <div class="content">
                            <div class="title"><?php esc_html_e( 'Settings & Mapping', 's2w-import-shopify-to-woocommerce' ); ?></div>
                        </div>
                    </div>
                    <div class="step <?php echo esc_attr( $steps_state['import'] ) ?>">
                        <i class="refresh icon"></i>
                        <div class="content">
                            <div class="title"><?php esc_html_e( 'Import', 's2w-import-shopify-to-woocommerce' ); ?></div>
                        </div>
                    </div>
                </div>
				<?php
				if ( isset( $_REQUEST['s2w_error'] ) ) {
					$file_url = isset( $_REQUEST['file_url'] ) ? urldecode( $_REQUEST['file_url'] ) : '';
					?>
                    <div class="vi-ui negative message">
                        <div class="header">
							<?php
							switch ( $_REQUEST['s2w_error'] ) {
								case 1:
									esc_html_e( 'Please set mapping for all required fields', 's2w-import-shopify-to-woocommerce' );
									break;
								case 2:
									esc_html_e( 'Name & Value pair for Option2/Option3 should be mapped or should not be mapped together(eg: If Option2 Name is mapped, Option2 Value must be mapped, too)', 's2w-import-shopify-to-woocommerce' );
									break;
								case 3:
									if ( $file_url ) {
										_e( "Can not open file: <strong>{$file_url}</strong>", 's2w-import-shopify-to-woocommerce' );
									} else {
										esc_html_e( 'Can not open file', 's2w-import-shopify-to-woocommerce' );
									}
									break;
								default:
									if ( $file_url ) {
										_e( "File not exists: <strong>{$file_url}</strong>", 's2w-import-shopify-to-woocommerce' );
									} else {
										esc_html_e( 'File not exists', 's2w-import-shopify-to-woocommerce' );
									}
							}
							?>
                        </div>
                    </div>
					<?php
				}
				switch ( $this->step ) {
					case 'mapping':
						?>
                        <form class="<?php echo esc_attr( self::set( 'import-container-form' ) ) ?> vi-ui form"
                              method="post"
                              enctype="multipart/form-data"
                              action="<?php echo esc_url( remove_query_arg( array(
							      'step',
							      'file_url',
							      's2w_error'
						      ) ) ) ?>">
							<?php
							wp_nonce_field( 's2w_import_shopify_to_woocommerce_import_action_nonce', '_s2w_import_shopify_to_woocommerce_import_nonce' );
							if ( $this->error ) {
								?>
                                <div class="error">
									<?php
									echo $this->error;
									?>
                                </div>
								<?php
							}
							?>

                            <div class="vi-ui segment">
                                <table class="form-table">
                                    <tbody>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"><?php esc_html_e( 'Products per step', 's2w-import-shopify-to-woocommerce' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="number"
                                                   class="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'products_per_request' ) ) ?>"
                                                   name="<?php echo esc_attr( self::set( 'products_per_request', true ) ) ?>"
                                                   min="1"
                                                   value="<?php echo esc_attr( $this->settings->get_params( 'products_per_request' ) ) ?>">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'custom_start' ) ) ?>"><?php esc_html_e( 'Start line', 's2w-import-shopify-to-woocommerce' ); ?></label>
                                        </th>
                                        <td>
                                            <input type="number"
                                                   class="<?php echo esc_attr( self::set( 'custom_start' ) ) ?>"
                                                   id="<?php echo esc_attr( self::set( 'custom_start' ) ) ?>"
                                                   name="<?php echo esc_attr( self::set( 'custom_start', true ) ) ?>"
                                                   min="2"
                                                   value="2">
                                            <p class="description"><?php esc_html_e( 'Only import products from this line on.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"><?php esc_html_e( 'Use global attributes', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( self::set( 'global_attributes', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"
                                                       value="1" <?php checked( $this->settings->get_params( 'global_attributes' ), '1' ) ?>>
                                                <label for="<?php echo esc_attr( self::set( 'global_attributes' ) ) ?>"><?php esc_html_e( 'WC product filters plugin, Variations Swatch plugin... only work with global attributes.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"><?php esc_html_e( 'Download description images', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( self::set( 'download_description_images', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"
                                                       value="1" <?php checked( $this->settings->get_params( 'download_description_images' ), '1' ) ?>>
                                                <label for="<?php echo esc_attr( self::set( 'download_description_images' ) ) ?>"><?php esc_html_e( 'Download images from product description in the background.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"><?php esc_html_e( 'Disable background processing', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( self::set( 'disable_background_process', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"
                                                       value="1" <?php checked( $this->settings->get_params( 'disable_background_process' ), '1' ) ?>>
                                                <label for="<?php echo esc_attr( self::set( 'disable_background_process' ) ) ?>"><?php _e( 'Product images and description images will be added to <a href="admin.php?page=s2w-import-shopify-to-woocommerce-error-images" target="_blank">Failed images</a> list so that you can go there to download all images with 1 click. This is recommended if your server is weak or if you usually have duplicated images issue.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"><?php esc_html_e( 'Download images', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( self::set( 'download_images', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"
                                                       value="1" <?php checked( $this->settings->get_params( 'download_images' ), '1' ) ?>>
                                                <label for="<?php echo esc_attr( self::set( 'download_images' ) ) ?>"><?php esc_html_e( 'Product images will be downloaded in the background.', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr class="<?php echo esc_attr( self::set( 'download_images_later_container' ) ) ?>"
                                        style="<?php if ( ! $this->settings->get_params( 'download_images' ) )
										    echo esc_attr( 'display:none' ) ?>">
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'download_images_later' ) ) ?>"><?php esc_html_e( 'Download images after importing products', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( self::set( 'download_images_later', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'download_images_later' ) ) ?>"
                                                       value="1" <?php checked( $this->settings->get_params( 'download_images_later' ), '1' ) ?>>
                                                <label for="<?php echo esc_attr( self::set( 'download_images_later' ) ) ?>"><?php esc_html_e( 'Only start downloading images after all products are imported', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                            </div>
                                            <p class="description"><?php esc_html_e( '*It\' faster than downloading images while importing products.', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"><?php esc_html_e( 'Keep product slug', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div class="vi-ui toggle checkbox checked">
                                                <input type="checkbox"
                                                       name="<?php echo esc_attr( self::set( 'keep_slug', true ) ) ?>"
                                                       id="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"
                                                       value="1" <?php checked( $this->settings->get_params( 'keep_slug' ), '1' ) ?>>
                                                <label for="<?php echo esc_attr( self::set( 'keep_slug' ) ) ?>"></label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'product_status' ) ) ?>"><?php esc_html_e( 'Product status', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div>
                                                <select class="vi-ui fluid dropdown"
                                                        id="<?php echo esc_attr( self::set( 'product_status' ) ) ?>"
                                                        name="<?php echo esc_attr( self::set( 'product_status', true ) ) ?>">
                                                    <option value="publish" <?php selected( $this->settings->get_params( 'product_status' ), 'publish' ) ?>><?php esc_html_e( 'Publish', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                    <option value="pending" <?php selected( $this->settings->get_params( 'product_status' ), 'pending' ) ?>><?php esc_html_e( 'Pending', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                    <option value="draft" <?php selected( $this->settings->get_params( 'product_status' ), 'draft' ) ?>><?php esc_html_e( 'Draft', 's2w-import-shopify-to-woocommerce' ) ?></option>
                                                </select>
                                            </div>
                                            <p><?php esc_html_e( 'Status of products after importing successfully', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>
                                            <label for="<?php echo esc_attr( self::set( 'product_categories' ) ) ?>"><?php esc_html_e( 'Product categories', 's2w-import-shopify-to-woocommerce' ) ?></label>
                                        </th>
                                        <td>
                                            <div>
                                                <select class="search-category"
                                                        id="<?php echo esc_attr( self::set( 'product_categories' ) ) ?>"
                                                        name="<?php echo esc_attr( self::set( 'product_categories', true ) ) ?>[]"
                                                        multiple="multiple">
													<?php

													if ( is_array( $this->settings->get_params( 'product_categories' ) ) && count( $this->settings->get_params( 'product_categories' ) ) ) {
														foreach ( $this->settings->get_params( 'product_categories' ) as $category_id ) {
															$category = get_term( $category_id );
															if ( $category ) {
																?>
                                                                <option value="<?php echo esc_attr( $category_id ) ?>"
                                                                        selected><?php echo esc_html( $category->name ); ?></option>
																<?php
															}
														}
													}
													?>
                                                </select>
                                            </div>
                                            <p><?php esc_html_e( 'Choose categories you want to add imported products to', 's2w-import-shopify-to-woocommerce' ) ?></p>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                            <div class="vi-ui segment">
                                <table class="form-table">
                                    <thead>
                                    <tr>
                                        <th><?php esc_html_e( 'Column name', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                        <th><?php esc_html_e( 'Map to field', 's2w-import-shopify-to-woocommerce' ) ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
									<?php
									$required_fields = array(
										'handle',
										'title',
										'price',
										'compare_at_price',
									);
									$headers         = array(
										'handle'           => 'Handle',
										'title'            => 'Title',
										'body_html'        => 'Body (HTML)',
										'type'             => 'Type',
										'tags'             => 'Tags',
										'option1_name'     => 'Option1 Name',
										'option1_value'    => 'Option1 Value',
										'option2_name'     => 'Option2 Name',
										'option2_value'    => 'Option2 Value',
										'option3_name'     => 'Option3 Name',
										'option3_value'    => 'Option3 Value',
										'sku'              => 'Variant SKU',
//									'weight'=>'Variant Grams',
										'price'            => 'Variant Price',
										'compare_at_price' => 'Variant Compare At Price',
										'image'            => 'Image Src',
										'image_alt'        => 'Image Alt Text',
										'variant_image'    => 'Variant Image',
									);
									foreach ( $headers as $header_k => $header_v ) {
										?>
                                        <tr>
                                            <td>
                                                <select id="<?php echo esc_attr( self::set( $header_k ) ) ?>"
                                                        class="vi-ui fluid dropdown"
                                                        name="<?php echo self::set( 'map_to', true ) ?>[<?php echo $header_k ?>]">
                                                    <option value=""><?php esc_html_e( 'Do not import', 's2w-import-shopify-to-woocommerce' ) ?></option>
													<?php
													foreach ( $this->header as $file_header ) {
														?>
                                                        <option value="<?php echo $file_header ?>"<?php selected( $header_v, $file_header ) ?>><?php echo $file_header ?></option>
														<?php
													}
													?>
                                                </select>
                                            </td>
                                            <td>
												<?php
												$label = $header_v;
												if ( in_array( $header_k, $required_fields ) ) {
													$label .= '(*Required)';
												}
												?>
                                                <label for="<?php echo esc_attr( self::set( $header_k ) ) ?>"><?php echo esc_html( $label ); ?></label>
                                            </td>
                                        </tr>
										<?php
									}
									?>
                                    </tbody>
                                </table>
                            </div>
                            <input type="hidden" name="s2w_import_shopify_to_woocommerce_file_url"
                                   value="<?php echo esc_attr( stripslashes( $this->file_url ) ) ?>">
                            <p>
                                <input type="submit" name="s2w_import_shopify_to_woocommerce_import"
                                       class="vi-ui primary button <?php echo esc_attr( self::set( 'import-continue' ) ) ?>"
                                       value="<?php esc_attr_e( 'Import', 's2w-import-shopify-to-woocommerce' ); ?>">
                            </p>
                        </form>
						<?php
						break;
					case 'import':
						?>
                        <div>
                            <div class="vi-ui indicating progress standard <?php echo esc_attr( self::set( 'import-progress' ) ) ?>">
                                <div class="label"></div>
                                <div class="bar">
                                    <div class="progress"></div>
                                </div>
                            </div>
                        </div>
						<?php
						break;
					default:
						?>
                        <form class="<?php echo esc_attr( self::set( 'import-container-form' ) ) ?> vi-ui form"
                              method="post"
                              enctype="multipart/form-data">
							<?php
							wp_nonce_field( 's2w_import_shopify_to_woocommerce_import_action_nonce', '_s2w_import_shopify_to_woocommerce_import_nonce' );
							if ( $this->error ) {
								?>
                                <div class="error">
									<?php
									echo $this->error;
									?>
                                </div>
								<?php
							}
							?>
                            <div class="<?php echo esc_attr( self::set( 'import-container' ) ) ?>">
                                <label for="<?php echo esc_attr( self::set( 'import-file' ) ) ?>"><?php esc_html_e( 'Select csv file to import', 's2w-import-shopify-to-woocommerce' ); ?></label>
                                <div>
                                    <input type="file" name="s2w_import_shopify_to_woocommerce_file"
                                           id="<?php echo esc_attr( self::set( 'import-file' ) ) ?>"
                                           class="<?php echo esc_attr( self::set( 'import-file' ) ) ?>"
                                           accept=".csv"
                                           required>
                                </div>
                            </div>
                            <p><input type="submit" name="s2w_import_shopify_to_woocommerce_select_file"
                                      class="vi-ui primary button <?php echo esc_attr( self::set( 'import-continue' ) ) ?>"
                                      value="<?php esc_attr_e( 'Continue', 's2w-import-shopify-to-woocommerce' ); ?>">
                            </p>
                        </form>
					<?php
				}
				?>
            </div>
        </div>
		<?php
	}

	private static function create_product_attributes( $global_attributes, $options, &$attr_data ) {
		global $wp_taxonomies;
		if ( $global_attributes ) {
			$position = 1;
			foreach ( $options as $option_k => $option_v ) {
				$attribute_slug = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::sanitize_taxonomy_name( $option_v['name'] );
				$attribute_id   = wc_attribute_taxonomy_id_by_name( $option_v['name'] );
				if ( ! $attribute_id ) {
					$attribute_id = wc_create_attribute( array(
						'name'         => $option_v['name'],
						'slug'         => $attribute_slug,
						'type'         => 'select',
						'order_by'     => 'menu_order',
						'has_archives' => false,
					) );
				}
				if ( $attribute_id && ! is_wp_error( $attribute_id ) ) {
					$attribute_obj     = wc_get_attribute( $attribute_id );
					$attribute_options = array();
					if ( ! empty( $attribute_obj ) ) {
						$taxonomy                   = $attribute_obj->slug; // phpcs:ignore
						$wp_taxonomies[ $taxonomy ] = new WP_Taxonomy( $taxonomy, 'product' );
						if ( count( $option_v['values'] ) ) {
							foreach ( $option_v['values'] as $term_k => $term_v ) {
								$option_v['values'][ $term_k ] = strval( wc_clean( $term_v ) );
								$insert_term                   = wp_insert_term( $option_v['values'][ $term_k ], $taxonomy );
								if ( ! is_wp_error( $insert_term ) ) {
									$attribute_options[] = $insert_term['term_id'];
								} elseif ( isset( $insert_term->error_data ) && isset( $insert_term->error_data['term_exists'] ) ) {
									$attribute_options[] = $insert_term->error_data['term_exists'];
								}
							}
						}
					}
					$attribute_object = new WC_Product_Attribute();
					$attribute_object->set_id( $attribute_id );
					$attribute_object->set_name( wc_attribute_taxonomy_name_by_id( $attribute_id ) );
					if ( count( $attribute_options ) ) {
						$attribute_object->set_options( $attribute_options );
					} else {
						$attribute_object->set_options( $option_v['values'] );
					}
					$attribute_object->set_position( $position );
					$attribute_object->set_visible( apply_filters( 's2w_create_product_attribute_set_visible', 0, $option_v ) );
					$attribute_object->set_variation( 1 );
					$attr_data[] = $attribute_object;
				}
				$position ++;
			}
		} else {
			$position = 1;
			foreach ( $options as $option_k => $option_v ) {
				$attribute_object = new WC_Product_Attribute();
				$attribute_object->set_name( $option_v['name'] );
				$attribute_object->set_options( $option_v['values'] );
				$attribute_object->set_position( $position );
				$attribute_object->set_visible( apply_filters( 's2w_create_product_attribute_set_visible', 0, $option_v ) );
				$attribute_object->set_variation( 1 );
				$attr_data[] = $attribute_object;
				$position ++;
			}
		}
	}
}

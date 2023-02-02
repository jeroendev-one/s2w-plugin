<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Single_New extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_single_new';

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
		$product_id  = isset( $item['parent_id'] ) ? $item['parent_id'] : '';
		$set_gallery = isset( $item['set_gallery'] ) ? $item['set_gallery'] : '';
		$product_ids = isset( $item['product_ids'] ) ? $item['product_ids'] : array();
		$src         = isset( $item['src'] ) ? $item['src'] : '';
		$id          = isset( $item['id'] ) ? $item['id'] : '';
		$image_id    = $id;//This is the real ID from Shopify
		$alt         = isset( $item['alt'] ) ? $item['alt'] : '';
		try {
			if ( $product_id && $src ) {
				$post = get_post( $product_id );
				if ( $post && $post->post_type === 'product' ) {
					vi_s2w_set_time_limit();
					$thumb_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::download_image( $id, $src, $product_id, array( 'gif' ) );
					if ( $thumb_id && ! is_wp_error( $thumb_id ) ) {
						update_post_meta( $thumb_id, '_s2w_shopify_image_id', $id );
						if ( $set_gallery == 2 ) {
							$downloaded_url = wp_get_attachment_url( $thumb_id );
							$description    = html_entity_decode( $post->post_content, ENT_QUOTES | ENT_XML1, 'UTF-8' );
							$description    = preg_replace( '/[^"]{0,}' . preg_quote( $id, '/' ) . '[^"]{0,}/U', $downloaded_url, $description );
							$description    = str_replace( $src, $downloaded_url, $description );
							wp_update_post( array( 'ID' => $product_id, 'post_content' => $description ) );
						} else {
							if ( $alt ) {
								update_post_meta( $thumb_id, '_wp_attachment_image_alt', $alt );
							}
							if ( count( $product_ids ) ) {
								foreach ( $product_ids as $v_id ) {
									if ( in_array( get_post_type( $v_id ), array(
										'product',
										'product_variation'
									) ) ) {
										update_post_meta( $v_id, '_thumbnail_id', $thumb_id );
									}
								}
							}

							if ( 1 == $set_gallery ) {
								$gallery = get_post_meta( $product_id, '_product_image_gallery', true );
								if ( $gallery ) {
									$gallery_array = explode( ',', $gallery );
								} else {
									$gallery_array = array();
								}
								$gallery_array[] = $thumb_id;
								update_post_meta( $product_id, '_product_image_gallery', implode( ',', array_unique( $gallery_array ) ) );
							}
						}
					} else {
						S2W_Error_Images_Table::insert( $product_id, implode( ',', $product_ids ), $src, $alt, intval( $set_gallery ), $image_id );
						if ( is_wp_error( $thumb_id ) ) {
							error_log( 'S2W error log - background download images: ' . $thumb_id->get_error_code() . ' - ' . $thumb_id->get_error_message() );
						}
					}
				}
			}
		} catch ( Exception $e ) {
			S2W_Error_Images_Table::insert( $product_id, implode( ',', $product_ids ), $src, $alt, intval( $set_gallery ), $image_id );
			error_log( 'S2W error log - background download images: ' . $e->getMessage() );

			return false;
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
			set_transient( 's2w_background_processing_complete', time() );
		}
		// Show notice to user or perform some other arbitrary task...
		parent::complete();
	}

	/**
	 * Delete all batches.
	 *
	 * @return WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Single_New
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

	/**
	 * Is queue empty
	 *
	 * @return bool
	 */
	public function is_queue_empty() {
		return parent::is_queue_empty();
	}
}
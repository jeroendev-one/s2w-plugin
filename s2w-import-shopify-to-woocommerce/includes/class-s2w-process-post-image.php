<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WP_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_Process_Post_Image extends WP_Background_Process {

	/**
	 * @var string
	 */
	protected $action = 's2w_process_post_image';

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
		$post_id           = isset( $item['post_id'] ) ? $item['post_id'] : '';
		$src               = isset( $item['src'] ) ? $item['src'] : '';
		$description_image = isset( $item['description_image'] ) ? $item['description_image'] : '';
		$alt               = isset( $item['alt'] ) ? $item['alt'] : '';
		try {
			if ( $post_id && $src ) {
				$post = get_post( $post_id );
				if ( $post && $post->post_type === 'post' ) {
					vi_s2w_set_time_limit();
					$thumb_id = VI_S2W_IMPORT_SHOPIFY_TO_WOOCOMMERCE_DATA::download_image( $id, $src, $post_id, array( 'gif' ) );
					if ( $thumb_id && ! is_wp_error( $thumb_id ) ) {
						update_post_meta( $thumb_id, '_s2w_shopify_image_id', $id );
						if ( $description_image == 1 ) {
							$downloaded_url = wp_get_attachment_url( $thumb_id );
							$description    = html_entity_decode( $post->post_content, ENT_QUOTES | ENT_XML1, 'UTF-8' );
							$description    = preg_replace( '/[^"]{0,}' . preg_quote( $id, '/' ) . '[^"]{0,}/U', $downloaded_url, $description );
							$description    = str_replace( $src, $downloaded_url, $description );
							wp_update_post( array( 'ID' => $post_id, 'post_content' => $description ) );
						} else {
							if ( $alt ) {
								update_post_meta( $thumb_id, '_wp_attachment_image_alt', $alt );
							}
							update_post_meta( $post_id, '_thumbnail_id', $thumb_id );
						}
					} else {
						S2W_Error_Images_Table::insert( $post_id, '', $src, '', $description_image );
						if ( is_wp_error( $thumb_id ) ) {
							error_log( 'S2W error log - background download images: ' . $thumb_id->get_error_code() . ' - ' . $thumb_id->get_error_message() );
						}
					}
				}
			}
		} catch ( Exception $e ) {
			S2W_Error_Images_Table::insert( $post_id, '', $src, '', $description_image );
			error_log( 'S2W error log - background download images of posts: ' . $e->getMessage() );

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
		// Show notice to user or perform some other arbitrary task...
		parent::complete();
	}

}
<?php

class IceNox_Pay_Method_Icon_Handler {

	public function __construct() {
		add_action( "wp_ajax_get-attachment-by-url", [ $this, "ajax_get_attachment_by_url" ], 15 );
	}

	public function ajax_get_attachment_by_url() {
		if ( ! isset( $_REQUEST["url"] ) ) {
			wp_send_json_error();
		}

		$url = $_REQUEST["url"];
		$id  = attachment_url_to_postid( $url );

		if ( $id === 0 ) {
			//Manually search posts for post guid matching the url
			$id = $this->get_postid_by_guid( $url );
		}

		if ( $id === 0 ) {
			//Create new external attachment to be used in media gallery
			$file_details = $this->analyze_file_from_url( $url );
			$id           = $this->create_attachment_image_from_url( $url, $file_details );
			if ( $file_details["ext"] === "svg" ) {
				wp_update_attachment_metadata( $id, $this->generate_svg_metadata( $file_details["name"], $url ) );
			} else {
				wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $url ) );
			}
		}

		$_REQUEST["id"] = $id;

		wp_ajax_get_attachment();
		die();
	}


	private function get_postid_by_guid( $url ): int {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT ID, guid FROM $wpdb->posts WHERE guid = %s",
			$url
		);

		$results = $wpdb->get_results( $sql );
		$post_id = 0;

		if ( $results ) {
			// Use the first available result, but prefer a case-sensitive match, if exists.
			$post_id = reset( $results )->ID;

			if ( count( $results ) > 1 ) {
				foreach ( $results as $result ) {
					if ( $url === $result->guid ) {
						$post_id = $result->ID;
						break;
					}
				}
			}
		}

		return $post_id;
	}

	private function analyze_file_from_url( $url ): array {
		add_filter( "upload_mimes", function ( $mimes ) {
			//Allow SVG as filetype, even if it's not in the currently allowed upload_mimes
			if ( ! isset( $mimes["svg"] ) ) {
				$mimes["svg"] = "image/svg+xml";
			}

			return $mimes;
		} );

		$filetype = wp_check_filetype( $url );

		return [
			"name" => basename( $url ),
			"type" => $filetype["type"],
			"ext"  => $filetype["ext"],
		];
	}

	private function create_attachment_image_from_url( $file_url, $file_details ): int {
		return wp_insert_attachment(
			[
				"guid"           => $file_url,
				"post_title"     => str_replace( "." . $file_details["ext"], "", $file_details["name"] ),
				"post_content"   => "",
				"post_status"    => "inherit",
				"post_mime_type" => $file_details["type"],
			]
		);
	}

	private function generate_svg_metadata( $file_name, $file_url = null ): array {
		$dimensions = $this->get_svg_dimensions( $file_url );
		$svg_width  = $dimensions[0];
		$svg_height = $dimensions[1];

		return [
			"width"  => $svg_width,
			"height" => $svg_height,
			"file"   => $file_name,
			"sizes"  => [
				"thumbnail"    => [
					"width"     => $svg_width,
					"height"    => $svg_height,
					"crop"      => false,
					"file"      => $file_name,
					"mime-type" => "image/svg+xml",
				],
				"medium"       => [
					"width"     => $svg_width,
					"height"    => $svg_height,
					"crop"      => false,
					"file"      => $file_name,
					"mime-type" => "image/svg+xml",
				],
				"medium_large" => [
					"width"     => $svg_width,
					"height"    => $svg_height,
					"crop"      => false,
					"file"      => $file_name,
					"mime-type" => "image/svg+xml",
				],
				"large"        => [
					"width"     => $svg_width,
					"height"    => $svg_height,
					"crop"      => false,
					"file"      => $file_name,
					"mime-type" => "image/svg+xml",
				],
			],
		];
	}

	/**
	 * @param $file_url
	 *
	 * @return array|int[] Returns the dimensions of the svg as array [width, height] or [0, 0] on error.
	 */
	private function get_svg_dimensions( $file_url ): array {
		if ( empty( $file_url ) ) {
			return [ 0, 0 ];
		}

		$svg_content = file_get_contents( $file_url );
		if ( empty( $svg_content ) || ! function_exists( "simplexml_load_string" ) ) {
			return [ 0, 0 ];
		}

		$svg = simplexml_load_string( $svg_content );
		if ( $svg === false || ! isset( $svg->attributes()->width ) || ! isset( $svg->attributes()->height ) ) {
			return [ 0, 0 ];
		}

		return [
			intval( $svg->attributes()->width ),
			intval( $svg->attributes()->height ),
		];
	}
}

new IceNox_Pay_Method_Icon_Handler();
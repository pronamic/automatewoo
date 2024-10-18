<?php
// phpcs:ignoreFile

namespace AutomateWoo\DataTypes;

use AutomateWoo\Clean;
use AutomateWoo\Guest as GuestModel;
use AutomateWoo\Guest_Factory;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * @class Guest
 */
class Guest extends AbstractDataType {

	/**
	 * @param $item
	 * @return bool
	 */
	function validate( $item ) {
		return $item instanceof GuestModel;
	}


	/**
	 * @param GuestModel $item
	 * @return mixed
	 */
	function compress( $item ) {
		return $item->get_email();
	}


	/**
	 * @param $compressed_item
	 * @param $compressed_data_layer
	 * @return mixed
	 */
	function decompress( $compressed_item, $compressed_data_layer ) {
		if ( aw_is_email_anonymized( $compressed_item ) ) {
			$guest = new GuestModel();
			$guest->set_email( Clean::string( $compressed_item ) );
			return $guest;
		}

		// Guests are compressed by their email not their ID
		if ( is_email( $compressed_item ) ) {
			$guest_email = $compressed_item;
		}
		elseif ( isset( $compressed_data_layer['comment'] ) ) {
			// If there is a comment fetch the guest info from that
			$comment = get_comment( $compressed_data_layer['comment'] );
			$guest_email = $comment->comment_author_email;
		}
		else {
			return false;
		}

		$guest_email = Clean::email( $guest_email );
		$guest = Guest_Factory::get_by_email( $guest_email );

		if ( ! $guest ) {
			// still pass the guest object even if it doesn't exist in the database
			// In most cases it should have been stored but there is no harm since all we need is an email
			$guest = new GuestModel();
			$guest->set_email( $guest_email );
		}

		return $guest;
	}

}

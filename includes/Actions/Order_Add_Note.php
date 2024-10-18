<?php

namespace AutomateWoo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @class Action_Order_Add_Note
 * @since 3.5
 */
class Action_Order_Add_Note extends Action {

	/**
	 * The data items required by the action.
	 *
	 * @var array
	 */
	public $required_data_items = [ 'order' ];

	/**
	 * Method to set title, group, description and other admin props.
	 */
	public function load_admin_details() {
		$this->title = __( 'Add Note', 'automatewoo' );
		$this->group = __( 'Order', 'automatewoo' );
	}

	/**
	 * Method to load the action's fields.
	 */
	public function load_fields() {
		$type = new Fields\Order_Note_Type();
		$type->set_required();

		$author = new Fields\Text();
		$author->set_name( 'note_author' );
		$author->set_title( __( 'Note author', 'automatewoo' ) );
		$author->set_placeholder( 'WooCommerce' );
		$author->set_description(
			__( "Author of the Note. If not set, will default to 'WooCommerce'", 'automatewoo' )
		);
		$author->set_required( false );

		$note = new Fields\Text_Area();
		$note->set_name( 'note' );
		$note->set_title( __( 'Note', 'automatewoo' ) );
		$note->set_variable_validation();
		$note->set_required();

		$this->add_field( $type );
		$this->add_field( $author );
		$this->add_field( $note );
	}

	/**
	 * Run the action.
	 *
	 * @throws \Exception When an error occurs.
	 */
	public function run() {
		$note_type = $this->get_option( 'note_type' );
		$author    = $this->get_option( 'note_author' );
		$note      = $this->get_option( 'note', true );
		$order     = $this->workflow->data_layer()->get_order();

		if ( ! $note || ! $note_type || ! $order ) {
			return;
		}

		if ( ! empty( $author ) && is_string( $author ) ) {
			$this->add_custom_author( $author );
		}

		$order->add_order_note( $note, 'customer' === $note_type, false );
	}

	/**
	 * Method to process custom Note author name set for the Action
	 * In case 'WooCommerce' is set for the Author field, we do not apply any filters since that is the default behaviour
	 * our system has.
	 *
	 * @param string $note_author
	 */
	protected function add_custom_author( string $note_author ) {
		if ( 'WooCommerce' !== $note_author ) {
			add_filter(
				'woocommerce_new_order_note_data',
				function ( $note ) use ( $note_author ) {
					$note['comment_author'] = $note_author;
					return $note;
				}
			);
		}
	}
}

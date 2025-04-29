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
	 * The filter for replacing the order note author.
	 *
	 * @var callable
	 */
	private $filter_order_note_author = null;

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

		$should_set_custom_author = ! empty( $author ) && is_string( $author );

		if ( $should_set_custom_author ) {
			$this->add_custom_author( $author );
		}

		$order->add_order_note( $note, 'customer' === $note_type, false );

		if ( $should_set_custom_author ) {
			$this->remove_custom_author();
		}
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
			$this->filter_order_note_author = function ( $note ) use ( $note_author ) {
				$note['comment_author'] = $note_author;
				return $note;
			};

			add_filter( 'woocommerce_new_order_note_data', $this->filter_order_note_author );
		}
	}

	/**
	 * Method to remove custom note author set for the Action.
	 * This method is expected to be called in pair with `add_custom_author`,
	 * and its call time is after calling `$order->add_order_note`.
	 */
	protected function remove_custom_author() {
		if ( $this->filter_order_note_author ) {
			remove_filter( 'woocommerce_new_order_note_data', $this->filter_order_note_author );
			$this->filter_order_note_author = null;
		}
	}
}

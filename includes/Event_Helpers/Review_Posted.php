<?php

namespace AutomateWoo\Event_Helpers;

use AutomateWoo\Review_Factory;
use WP_Comment;

/**
 * Class Review_Posted.
 */
class Review_Posted {

	/**
	 * Init the event helper.
	 */
	public static function init() {
		add_action( 'wp_insert_comment', [ __CLASS__, 'catch_new_comments' ], 20, 2 );
		add_action( 'transition_comment_status', [ __CLASS__, 'catch_comment_approval' ], 20, 3 );
	}

	/**
	 * Catch any comments approved on creation
	 *
	 * @param int        $comment_id
	 * @param WP_Comment $comment
	 */
	public static function catch_new_comments( int $comment_id, WP_Comment $comment ) {
		if ( $comment->comment_approved ) {
			self::maybe_dispatch_event( $comment );
		}
	}

	/**
	 * Catch any comments that were approved after creation
	 *
	 * @param int|string $new_status
	 * @param int|string $old_status
	 * @param WP_Comment $comment
	 */
	public static function catch_comment_approval( $new_status, $old_status, WP_Comment $comment ) {
		if ( $new_status === 'approved' ) {
			self::maybe_dispatch_event( $comment );
		}
	}

	/**
	 * Maybe do the review posted action.
	 *
	 * @param WP_Comment $comment
	 */
	private static function maybe_dispatch_event( WP_Comment $comment ) {
		$review = Review_Factory::get( $comment );

		// validates if the comment is actually a review
		if ( ! $review ) {
			return;
		}

		do_action( 'automatewoo/review/posted', $review );
	}
}

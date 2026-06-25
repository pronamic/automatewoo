<?php

namespace AutomateWoo;

/**
 * @class Replace_Helper
 * @since 2.1.9
 */
class Replace_Helper {

	/** @var array */
	public $patterns = [
		'text_urls' => [
			'match'      => 0,
			'expression' => '/(?<!a href=")(?<!src=")((http|ftp)+(s)?:\/\/[^<>\s]+)/i',
		],
		'href_urls' => [
			'match'      => 1,
			'expression' => '/href=["\']?([^"\'>]+)["\']?/',
		],
		'variables' => [
			'match'      => 1,
			'expression' => '/{{(.*?)}}/',
		],
	];

	/** @var string */
	public $selected_pattern;

	/** @var string */
	public $selected_pattern_name;

	/** @var string */
	public $string;

	/** @var callable */
	public $callback;


	/**
	 * @param string   $string
	 * @param callable $callback
	 * @param string   $pattern_name
	 */
	public function __construct( $string, $callback, $pattern_name = '' ) {

		$this->string   = $string;
		$this->callback = $callback;

		if ( $pattern_name && isset( $this->patterns[ $pattern_name ] ) ) {
			$this->selected_pattern      = $this->patterns[ $pattern_name ];
			$this->selected_pattern_name = $pattern_name;
		}
	}


	/**
	 * @return mixed
	 */
	public function process() {

		if ( ! $this->selected_pattern ) {
			return false;
		}

		return preg_replace_callback( $this->selected_pattern['expression'], [ $this, 'callback' ], $this->string );
	}


	/**
	 * Pre process match before using the actual callback
	 *
	 * @param array $match
	 * @return string
	 */
	public function callback( $match ) {
		if ( is_array( $match ) ) {
			$match = $match[ $this->selected_pattern['match'] ];
		}

		$trailing_punctuation = '';

		if ( $this->selected_pattern_name === 'text_urls' ) {
			list( $match, $trailing_punctuation ) = $this->split_trailing_text_url_punctuation( $match );
		}

		return call_user_func( $this->callback, $match ) . $trailing_punctuation;
	}


	/**
	 * Split sentence punctuation from the end of a plain-text URL match.
	 *
	 * @param string $url
	 * @return array
	 */
	public function split_trailing_text_url_punctuation( $url ) {
		$trailing_punctuation = '';
		$sentence_punctuation = [ '.', ',', '!', '?', ';', ':' ];

		while ( $url !== '' ) {
			$last_character = substr( $url, -1 );

			if ( in_array( $last_character, $sentence_punctuation, true ) ) {
				$trailing_punctuation = $last_character . $trailing_punctuation;
				$url                  = substr( $url, 0, -1 );
				continue;
			}

			if ( ')' === $last_character && substr_count( $url, ')' ) > substr_count( $url, '(' ) ) {
				$trailing_punctuation = $last_character . $trailing_punctuation;
				$url                  = substr( $url, 0, -1 );
				continue;
			}

			if ( ']' === $last_character && substr_count( $url, ']' ) > substr_count( $url, '[' ) ) {
				$trailing_punctuation = $last_character . $trailing_punctuation;
				$url                  = substr( $url, 0, -1 );
				continue;
			}

			break;
		}

		return [ $url, $trailing_punctuation ];
	}
}

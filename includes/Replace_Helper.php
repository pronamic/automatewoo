<?php
// phpcs:ignoreFile

namespace AutomateWoo;

/**
 * @class Replace_Helper
 * @since 2.1.9
 */
class Replace_Helper {

	/** @var array */
	public $patterns = [
		'text_urls' => [
			'match' => 0,
			'expression' => "/(?<!a href=\")(?<!src=\")((http|ftp)+(s)?:\/\/[^<>\s]+)/i"
		],
		'href_urls' => [
			'match' => 1,
			'expression' => '/href=["\']?([^"\'>]+)["\']?/'
		],
		'variables' => [
			'match' => 1,
			'expression' => '/{{(.*?)}}/'
		]
	];

	/** @var string */
	public $selected_pattern;

	/** @var string */
	public $string;

	/** @var callable */
	public $callback;


	/**
	 * @param $string
	 * @param callable $callback
	 * @param string $pattern_name
	 */
	function __construct( $string, $callback, $pattern_name = '' ) {

		$this->string = $string;
		$this->callback = $callback;

		if ( $pattern_name && isset( $this->patterns[$pattern_name] ) ) {
			$this->selected_pattern = $this->patterns[$pattern_name];
		}
	}


	/**
	 * @return mixed
	 */
	function process() {

		if ( ! $this->selected_pattern )
			return false;

		return preg_replace_callback( $this->selected_pattern['expression'], [ $this, 'callback' ] , $this->string );
	}


	/**
	 * Pre process match before using the actual callback
	 * @param $match
	 * @return string
	 */
	function callback( $match ) {
		if ( is_array( $match ) ) {
			$match = $match[ $this->selected_pattern['match'] ];
		}
		return call_user_func( $this->callback, $match );
	}

}

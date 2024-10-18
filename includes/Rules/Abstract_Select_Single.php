<?php
// phpcs:ignoreFile

namespace AutomateWoo\Rules;

defined( 'ABSPATH' ) || exit;

/**
 * @class Abstract_Select_Single
 */
abstract class Abstract_Select_Single extends Rule {

	public $type = 'select';

	public $is_single_select = true;

	/** @var array - leave public for json */
	public $select_choices;

	/** @var string */
	public $placeholder;

	/**
	 * Don't gather select choices on construct as there could be a lot of data
	 * @return array
	 */
	abstract function get_select_choices();

}

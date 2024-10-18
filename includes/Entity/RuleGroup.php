<?php

namespace AutomateWoo\Entity;

/**
 * RuleGroup class.
 *
 * @since   5.1.0
 * @package AutomateWoo\Entity
 */
class RuleGroup implements ToArray {

	/**
	 * Array of rule objects.
	 *
	 * @var Rule[]
	 */
	protected $rules;

	/**
	 * RuleGroup constructor.
	 *
	 * @param Rule[] $rules
	 */
	public function __construct( $rules = [] ) {
		$this->set_rules( $rules );
	}

	/**
	 * Get the rules in this group.
	 *
	 * @return Rule[]
	 */
	public function get_rules() {
		return $this->rules;
	}

	/**
	 * Set the array of rules within this group.
	 *
	 * @param Rule[] $rules Array of rule objects.
	 *
	 * @return $this
	 */
	public function set_rules( $rules ) {
		$this->rules = [];
		foreach ( $rules as $rule ) {
			$this->add_rule( $rule );
		}

		return $this;
	}

	/**
	 * Add a rule to this group.
	 *
	 * @param Rule $rule The rule to add.
	 *
	 * @return $this
	 */
	public function add_rule( Rule $rule ) {
		$this->rules[] = $rule;

		return $this;
	}

	/**
	 * Convert the object's data to an array.
	 *
	 * @return array
	 */
	public function to_array(): array {
		return array_map(
			function ( Rule $rule ) {
				return $rule->to_array();
			},
			$this->rules
		);
	}
}

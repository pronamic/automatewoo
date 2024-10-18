<?php

namespace AutomateWoo\Workflows\Presets;

/**
 * @class ArrayPreset
 * @since 5.1.0
 */
class ArrayPreset implements PresetInterface, \ArrayAccess {

	/**
	 * @var array
	 */
	protected $data;

	/**
	 * @param string $name
	 * @param array  $data
	 */
	public function __construct( string $name, array $data ) {
		$this->data = $data;
		$this->set_name( $name );
	}

	/**
	 * @param string $key
	 *
	 * @return bool
	 */
	public function has( string $key ): bool {
		return $this->offsetExists( $key );
	}

	/**
	 * @param string     $key
	 * @param mixed|null $default
	 *
	 * @return mixed|null
	 */
	public function get( string $key, $default = null ) {
		return $this->has( $key ) ? $this->offsetGet( $key ) : $default;
	}

	/**
	 * @param string     $key
	 * @param mixed|null $value
	 *
	 * @return mixed|null
	 */
	public function set( string $key, $value ): PresetInterface {
		$this->offsetSet( $key, $value );

		return $this;
	}

	/**
	 * @param string $offset
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->data[ $offset ] );
	}

	/**
	 * @param string $offset
	 *
	 * @return mixed
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->data[ $offset ];
	}

	/**
	 * @param string $offset
	 * @param mixed  $value
	 */
	public function offsetSet( $offset, $value ): void {
		$this->data[ $offset ] = $value;
	}

	/**
	 * @param string $offset
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->data[ $offset ] );
	}

	/**
	 * @return string
	 */
	public function get_name(): string {
		return $this->get( self::NAME_KEY );
	}

	/**
	 * @param string $name
	 *
	 * @return PresetInterface
	 */
	public function set_name( string $name ): PresetInterface {
		$this->set( self::NAME_KEY, $name );

		return $this;
	}
}

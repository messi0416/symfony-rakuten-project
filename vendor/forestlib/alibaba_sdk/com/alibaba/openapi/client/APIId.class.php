<?php
class APIId {
	
	/**
	 * namespace of API, it is required
	 *
	 * @var string
	 */
	var $namespace;
	/**
	 * name of API, it is required
	 * @var string
	 */
	var $name;
	/**
	 * version of API, optional.
	 * If not setup, the default version defined in requestPolicy is used.
	 * @var integer
	 */
	var $version;
	function APIId( $namespace,  $name,  $version) {
		$this->namespace = $namespace;
		$this->name = $name;
		$this->version = $version;
	}
	
}
?>
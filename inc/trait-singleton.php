<?php

namespace WildWolf\WordPress\LoginLogger;

trait Singleton {
	/** @var self|null */
	protected static $instance = null;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		// Do nothing
	}
}

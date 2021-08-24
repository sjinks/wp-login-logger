<?php

use WildWolf\WordPress\LoginLogger\EventWatcher;
use WildWolf\WordPress\LoginLogger\Logger;
use WildWolf\WordPress\LoginLogger\Plugin;
use WildWolf\WordPress\LoginLogger\Schema;

class Test_Singleton extends WP_UnitTestCase /* NOSONAR */ {
	public function test_instantiation(): void {
		/** @psalm-var (class-string<Plugin>|class-string<Schema>|class-string<Logger>|class-string<EventWatcher>)[] */
		static $classes = [
			Plugin::class,
			Schema::class,
			Logger::class,
			EventWatcher::class,
		];

		foreach ( $classes as $class ) {
			$instance1 = $class::instance();
			$instance2 = $class::instance();

			self::assertSame( $instance1, $instance2 );
			self::assertInstanceOf( $class, $instance1 );
		}
	}
}

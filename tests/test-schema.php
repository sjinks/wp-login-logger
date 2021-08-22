<?php

use WildWolf\WordPress\LoginLogger\Schema;

class Test_Schema extends WP_UnitTestCase {
	// NOSONAR

	public function test_is_update_needed(): void {
		delete_site_option( Schema::VERSION_KEY );
		$schema = Schema::instance();
		self::assertTrue( $schema->is_update_needed() );
	}

	public function test_schema_updates_are_idempotent(): void {
		delete_site_option( Schema::VERSION_KEY );

		$schema = Schema::instance();
		$schema->update();

		self::assertSame( Schema::LATEST_VERSION, (int) get_site_option( Schema::VERSION_KEY ) );
		self::assertFalse( $schema->is_update_needed() );
		self::assertTrue( $schema->is_installed() );
	}

	public function test_table_creation_failures_are_handled(): void {
		delete_site_option( Schema::VERSION_KEY );
		$schema = Schema::instance();

		// This will make dbDelta a no-op. This is a much safer and clean option
		// than modifying the query on the fly making it invalid (and no errors will be shown)
		add_filter( 'dbdelta_queries', [ $this, 'fail_dbdelta' ] );

		// We need SHOW TABLE to return nothing. One of the possible options is to DROP the table first
		// and recreate it after the test. However, this introduces unnecessary complexity
		// because the test runner rewrites CREATE/DROP TABLE statements to operate on TEMPORARY tables.
		// And InnoDB does not support full-text indices on temporary tables
		add_filter( 'query', [ $this, 'fail_show_tables' ] );

		$schema->update();
		self::assertNotEquals( Schema::LATEST_VERSION, (int) get_site_option( Schema::VERSION_KEY ) );
	}

	/**
	 * Emulates failed CREATE TABLE statement
	 */
	public function fail_dbdelta(): array {
		return [];
	}

	/**
	 * Makes SHOW TABLES return nothing
	 * 
	 * @global wpdb $wpdb
	 */
	public function fail_show_tables( $query ): string {
		global $wpdb;

		if ( "SHOW TABLES LIKE '{$wpdb->login_log_table}'" === $query ) {
			$query = "SHOW TABLES LIKE 'non_existing_table'";
		}

		return $query;
	}
}

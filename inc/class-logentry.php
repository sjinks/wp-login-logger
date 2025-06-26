<?php

namespace WildWolf\WordPress\LoginLogger;

use DateTimeImmutable;
use WP_User;

/**
 * @psalm-type Outcome = (self::OUTCOME_ATTEMPTED|self::OUTCOME_FAILED|self::OUTCOME_SUCCEEDED)
 */
final class LogEntry {
	const OUTCOME_ATTEMPTED = -1;
	const OUTCOME_FAILED    = 0;
	const OUTCOME_SUCCEEDED = 1;

	/** @var string */
	private $ip;

	/** @var int */
	private $dt;

	/** @var string */
	private $username;

	/** @var int */
	private $user_id;

	/**
	 * @var int
	 * @psalm-var Outcome
	 */
	private $outcome;

	/**
	 * @param scalar $username
	 * @param scalar $user_id
	 * @param int $outcome
	 * @psalm-param Outcome $outcome
	 */
	public function __construct( $username, $user_id, int $outcome ) {
		// phpcs:ignore WordPressVIPMinimum.Variables.ServerVariables.UserControlledHeaders, WordPressVIPMinimum.Variables.RestrictedVariables.cache_constraints___SERVER__REMOTE_ADDR__
		$this->ip       = (string) inet_pton( filter_var( $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', FILTER_VALIDATE_IP, [ 'options' => [ 'default' => '0.0.0.0' ] ] ) );
		$this->dt       = time();
		$this->username = (string) $username;
		$this->user_id  = (int) $user_id;
		$this->outcome  = $outcome;
	}

	/**
	 * @psalm-return array{ip: string, dt: int, username: string, user_id: int, outcome: Outcome}
	 */
	public function to_array(): array {
		return [
			'ip'       => $this->ip,
			'dt'       => $this->dt,
			'username' => $this->username,
			'user_id'  => $this->user_id,
			'outcome'  => $this->outcome,
		];
	}

	public function get_ip(): string {
		return $this->ip;
	}

	public function get_dt(): DateTimeImmutable {
		/** @var DateTimeImmutable */
		return DateTimeImmutable::createFromFormat( 'U', (string) $this->dt );
	}

	public function get_username(): string {
		return $this->username;
	}

	public function get_user_id(): int {
		return $this->user_id;
	}

	public function get_user(): ?WP_User {
		return $this->user_id ? new WP_User( $this->user_id ) : null;
	}

	/**
	 * @psalm-return Outcome
	 */
	public function get_outcome(): int {
		return $this->outcome;
	}
}

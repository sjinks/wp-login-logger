<?php

namespace WildWolf\WordPress\LoginLogger;

use WildWolf\Utils\Singleton;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

final class RESTController {
	use Singleton;

	const NAMESPACE = 'login-logger/v1';

	private function __construct() {
		$this->register_rest_routes();
	}

	public function register_rest_routes(): void {
		$user_id_schema = [
			'required' => true,
			'type'     => 'integer',
			'min'      => 1,
		];

		$session_id_schema = [
			'required' => true,
			'type'     => 'string',
		];

		register_rest_route(
			self::NAMESPACE,
			'/sessions',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'list_current_user_sessions' ],
					'permission_callback' => [ $this, 'user_logged_in_check' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/(?P<user_id>\\d+)/sessions',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'list_user_sessions' ],
					'permission_callback' => [ $this, 'can_edit_user_check' ],
					'args'                => [ 'user_id' => $user_id_schema ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/sessions',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_all_current_user_sessions' ],
					'permission_callback' => [ $this, 'user_logged_in_check' ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/(?P<user_id>\\d+)/sessions',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_all_user_sessions' ],
					'permission_callback' => [ $this, 'can_edit_user_check' ],
					'args'                => [ 'user_id' => $user_id_schema ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/sessions/(?P<session_id>[a-f0-9]{32,})',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_current_user_session' ],
					'permission_callback' => [ $this, 'user_logged_in_check' ],
					'args'                => [ 'session_id' => $session_id_schema ],
				],
			]
		);

		register_rest_route(
			self::NAMESPACE,
			'/(?P<user_id>\\d+)/sessions/(?P<session_id>[a-f0-9]{32,})',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_user_session' ],
					'permission_callback' => [ $this, 'can_edit_user_check' ],
					'args'                => [
						'user_id'    => $user_id_schema,
						'session_id' => $session_id_schema,
					],
				],
			]
		);
	}

	/**
	 * @return true|WP_Error
	 */
	public function user_logged_in_check() {
		if ( get_current_user_id() > 0 ) {
			return true;
		}

		return new WP_Error(
			'rest_operation_not_allowed',
			__( 'Operation is not allowed.', 'login-logger' ),
			[ 'status' => rest_authorization_required_code() ]
		);
	}

	/**
	 * @return true|WP_Error
	 */
	public function can_edit_user_check( WP_REST_Request $request ) {
		$user_id = (int) $request->get_param( 'user_id' );
		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			return new WP_Error(
				'rest_operation_not_allowed',
				__( 'Operation is not allowed.', 'login-logger' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		return true;
	}

	public function list_current_user_sessions(): WP_REST_Response {
		$user_id = get_current_user_id();
		return $this->list_sessions( $user_id );
	}

	public function list_user_sessions( WP_REST_Request $request ): WP_REST_Response {
		$user_id = (int) $request->get_param( 'user_id' );
		return $this->list_sessions( $user_id );
	}

	private function list_sessions( int $user_id ): WP_REST_Response {
		$sessions = SessionManager::get_all( $user_id );
		$items    = array_map( [ AuthUtils::class, 'prepare_session' ], array_keys( $sessions ), array_values( $sessions ) );
		/** @var WP_REST_Response */
		return rest_ensure_response( $items );
	}

	public function delete_current_user_session( WP_REST_Request $request ): WP_REST_Response {
		$user_id    = get_current_user_id();
		$session_id = (string) $request->get_param( 'session_id' );
		return $this->delete_session( $user_id, $session_id );
	}

	public function delete_user_session( WP_REST_Request $request ): WP_REST_Response {
		$user_id    = (int) $request->get_param( 'user_id' );
		$session_id = (string) $request->get_param( 'session_id' );
		return $this->delete_session( $user_id, $session_id );
	}

	private function delete_session( int $user_id, string $verifier ): WP_REST_Response {
		SessionManager::delete( $user_id, $verifier );
		/** @var WP_REST_Response */
		return rest_ensure_response( [
			'success' => true,
			'data'    => [ 'message' => __( 'Session has been terminated.', 'login-logger' ) ],
		] );
	}

	public function delete_all_current_user_sessions(): WP_REST_Response {
		$user_id = get_current_user_id();
		return $this->delete_all_sessions( $user_id );
	}

	public function delete_all_user_sessions( WP_REST_Request $request ): WP_REST_Response {
		$user_id = (int) $request->get_param( 'user_id' );
		return $this->delete_all_sessions( $user_id );
	}

	private function delete_all_sessions( int $user_id ): WP_REST_Response {
		SessionManager::delete_all( $user_id );
		/** @var WP_REST_Response */
		return rest_ensure_response( [
			'success' => true,
			'data'    => [ 'message' => __( 'All sessions have been terminated.', 'login-logger' ) ],
		] );
	}
}

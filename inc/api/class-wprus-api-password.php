<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class Wprus_Api_Password extends Wprus_Api_Abstract {

	/*******************************************************************
	 * Public methods
	 *******************************************************************/

	public function init_notification_hooks() {
		add_action( 'password_reset', array( $this, 'schedule_shutdown' ), PHP_INT_MAX - 100, 2 );
		add_action( 'wprus_password', array( $this, 'handle_password_creation' ), 10, 1 );
		add_action( 'wp_set_password', array( $this, 'handle_password_creation' ), PHP_INT_MAX - 100, 1 );
		add_action( 'wp_update_user', array( $this, 'handle_password_update' ), PHP_INT_MAX - 100, 3 );
	}

	public function handle_password_creation( $password ) {
		$plain_text_pwd = wp_cache_get( 'wprus_api_password_plain_text_pwd', 'wprus' );
		$handled        = (
			'*' === $password ||
			(
				$plain_text_pwd &&
				(
					0 === strpos( $password, '$P$' ) ||
					0 === strpos( $password, '$wp' )
				)
			)
		);

		if ( apply_filters( 'wprus_password_handled', $handled, $password, $plain_text_pwd ) ) {
			return;
		}

		wp_cache_set( 'wprus_api_password_plain_text_pwd', $password, 'wprus' );
	}

	public function handle_password_update( $user_id, $user_data, $userdata_raw ) {

		if ( isset( $userdata_raw['user_pass'] ) ) {
			wp_cache_set( 'wprus_api_password_plain_text_pwd', $userdata_raw['user_pass'], 'wprus' );
		}
	}

	public function handle_notification_password_data( $data, $site, $user = false ) {

		if ( $user ) {

			if ( $data['user_pass'] ) {
				$data['user_pass'] = wp_hash_password( $data['user_pass'] );
			}
		}

		if ( ! $site['incoming_actions']['password'] || ! $data['user_pass'] ) {

			if ( $user ) {
				unset( $data['user_pass'] );
			} else {
				$data['user_pass'] = wp_generate_password( 16 );
			}
		}

		return $data;
	}

	public function handle_notify_remote_data( $data, $site ) {

		if ( $site['outgoing_actions']['password'] ) {
			$pass = wp_cache_get( 'wprus_api_password_plain_text_pwd', 'wprus' );

			if ( $pass ) {
				$data['user_pass'] = $pass;
			}
		}

		return $data;
	}

	public function handle_notification() {
		$data   = $this->get_data_post();
		$result = false;

		if ( ! $this->validate( $data ) ) {
			Wprus_Logger::log(
				__( 'Password action failed - received invalid data.', 'wprus' ),
				'alert',
				'db_log'
			);

			return $result;
		}

		$data = $this->sanitize( $data );
		$site = $this->get_active_site_for_action( $this->endpoint, $data['base_url'] );

		if ( $site ) {
			$user = get_user_by( 'login', $data['username'] );

			if ( $user ) {
				$result = true;

				wp_set_password( $data['user_pass'], $user->ID );
				Wprus_Logger::log(
					sprintf(
						// translators: %1$s is the username, %2$s is the caller
						__( 'Password action - password successfully reset for user "%1$s" from %2$s.', 'wprus' ),
						$data['username'],
						$site['url']
					),
					'success',
					'db_log'
				);
			} else {
				Wprus_Logger::log(
					sprintf(
						// translators: %1$s is the username, %2$s is the caller
						__( 'Password action aborted - cannot reset, user "%1$s" from %2$s does not exist locally.', 'wprus' ),
						$data['username'],
						$site['url']
					),
					'warning',
					'db_log'
				);
			}
		} else {
			Wprus_Logger::log(
				sprintf(
					// translators: %s is the url of the caller
					__( 'Password action failed - incoming password action not enabled for %s', 'wprus' ),
					$data['base_url']
				),
				'alert',
				'db_log'
			);
		}

		return $result;
	}

	public function schedule_shutdown( $user, $new_pass ) {
		wp_cache_set( 'wprus_api_password_plain_text_pwd', $new_pass, 'wprus' );
		wp_cache_set( 'wprus_api_password_user', $user, 'wprus' );
		add_action( 'shutdown', array( $this, 'notify_remote' ), PHP_INT_MAX - 100 );
	}

	public function notify_remote() {
		$sites = $this->settings->get_sites( $this->endpoint, 'outgoing' );
		$pass  = wp_cache_get( 'wprus_api_password_plain_text_pwd', 'wprus' );
		$user  = wp_cache_get( 'wprus_api_password_user', 'wprus' );

		if ( ! empty( $sites ) ) {
			$data = array(
				'username'  => $user->user_login,
				'user_pass' => $pass,
			);

			Wprus_Logger::log(
				sprintf(
					// translators: %s is the username
					__( 'Password action - firing action reset for username "%s"', 'wprus' ),
					$user->user_login
				),
				'info',
				'db_log'
			);

			foreach ( $sites as $index => $site ) {
				$this->fire_action( $site['url'], $data );
			}
		}
	}

	/*******************************************************************
	 * Protected methods
	 *******************************************************************/

	protected function validate( $data ) {
		$valid = parent::validate( $data ) && ! empty( $data['user_pass'] );

		return $valid;
	}
}

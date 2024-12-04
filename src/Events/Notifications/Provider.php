<?php
/**
 * Service Provider for interfacing with TEC\Common\Notifications.
 *
 * @since   6.4.0
 *
 * @package TEC\Events\Notifications
 */

namespace TEC\Events\Notifications;

use TEC\Common\Contracts\Service_Provider;

/**
 * Class Provider
 *
 * @since   6.4.0
 * @package TEC\Events\Notifications
 */
class Provider extends Service_Provider {

	/**
	 * Handles the registering of the provider.
	 *
	 * @since 6.4.0
	 */
	public function register() {
		add_action( 'admin_footer', [ $this, 'render_icon' ] );
	}

	/**
	 * Renders the Notification icon.
	 *
	 * @since 6.4.0
	 */
	public function render_icon() {
		return $this->container->get( Notifications::class )->render_icon();
	}
}

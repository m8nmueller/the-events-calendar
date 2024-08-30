<?php
/**
 * Service Provider for Community upsell/settings.
 *
 * @since
 *
 * @package TEC\Events\Admin\Settings
 */

namespace TEC\Events\Admin\Settings;

use TEC\Common\Contracts\Service_Provider;
use Tribe__Settings_Tab;
use Tribe__Template;
use Tribe\Events\Admin\Settings;

/**
 * Class Upsell
 *
 * @since TBD
 */
class Community_Upsell extends Service_Provider {
	/**
	 * The slug of the upsell tab.
	 *
	 * @since TBD
	 *
	 * @var string
	 */
	protected string $slug = 'community';
	/**
	 * Binds and sets up implementations.
	 *
	 * @since TBD
	 */
	public function register(): void {
		if ( tec_should_hide_upsell() ) {
			return;
		}

		// Bail if Community is already installed/registered.
		if ( has_action( 'tribe_common_loaded', 'tribe_register_community' ) ) {
			return;
		}

		$this->add_actions();
		$this->add_filters();
	}

	/**
	 * Add actions.
	 *
	 * @since TBD
	 */
	public function add_actions(): void {
		add_action( 'tribe_settings_do_tabs', [ $this, 'add_tab' ] );
	}

	/**
	 * Add filters.
	 *
	 * @since TBD
	 */
	public function add_filters(): void {
		add_filter( 'tribe_settings_form_class', [ $this, 'filter_tribe_settings_form_classes' ] );
		add_filter( 'tribe_settings_no_save_tabs', [ $this, 'filter_tribe_settings_no_save_tabs' ] );
	}

	/**
	 * Adds a class to the settings form to allow for custom styling.
	 *
	 * @since TBD
	 *
	 * @param array $classes
	 */
	public function filter_tribe_settings_form_classes( $classes ): array {
		if ( ! in_array( 'tec-settings__community-tab--active', $classes ) ) {
			return $classes;
		}

		$classes[] = 'tec-events-settings__upsell-form';

		return $classes;
	}

	/**
	 * Adds the Community tab to the list of tabs that should not be saved.
	 *
	 * @since TBD
	 *
	 * @param array $tabs
	 *
	 * @return array
	 */
	public function filter_tribe_settings_no_save_tabs( $tabs ): array {
		$tabs[] = $this->slug;

		return $tabs;
	}

	/**
	 * Stores the instance of the template engine that we will use for rendering the elements.
	 *
	 * @since TBD
	 *
	 * @var Tribe__Template
	 */
	protected $template;

	/**
	 * Create a Community upsell tab.
	 *
	 * @since TBD
	 *
	 * @param string $admin_page The current admin page.
	 */
	public function add_tab( $admin_page ): void {
		$tec_settings_page_id = tribe( Settings::class )::$settings_page_id;

		if ( ! empty( $admin_page ) && $tec_settings_page_id !== $admin_page ) {
			return;
		}

		$tec_events_community_upsell_tab = [
			'community-upsell-info-box' => [
				'type' => 'html',
				'html' => $this->get_upsell_html(),
			],
		];

		/**
		* Allows the fields displayed in the Community upsell tab to be modified.
		*
		* @since TBD
		*
		* @param array $tec_events_community_upsell_tab Array of fields used to setup the Community upsell Tab.
		*/
		$tec_events_admin_community_upsell_fields = apply_filters(
			'tec_events_settings_community_tab_content',
			$tec_events_community_upsell_tab
		);

		new Tribe__Settings_Tab(
			$this->slug,
			esc_html_x( 'Community', 'Label for the Community tab.', 'the-events-calendar' ),
			[
				'priority'      => 45,
				'fields'        => $tec_events_admin_community_upsell_fields,
				'network_admin' => is_network_admin(),
				'show_save'     => false,
			]
		);

		add_filter(
			'tec_events_settings_tabs_ids',
			function ( $tabs ) {
				$tabs[] = $this->slug;
				return $tabs;
			}
		);
	}

	/**
	 * Returns html of the Community upsell banner.
	 *
	 * @since TBD
	 *
	 * @param array   $context Context of template.
	 * @param boolean $echo    Whether or not to output the HTML or just return it.
	 *
	 * @return string|false HTML of the Community upsell banner. False if the template is not found.
	 */
	public function get_upsell_html( $context = [], $echo = false ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.echoFound
		return $this->get_template()->template( $this->slug, wp_parse_args( $context ), $echo );
	}

	/**
	 * Gets the template instance used to setup the rendering html.
	 *
	 * @since TBD
	 *
	 * @return Tribe__Template
	 */
	public function get_template(): Tribe__Template {
		if ( empty( $this->template ) ) {
			$this->template = new Tribe__Template();
			$this->template->set_template_origin( \Tribe__Events__Main::instance() );
			$this->template->set_template_folder( 'src/admin-views/settings/upsells/' );
			$this->template->set_template_context_extract( true );
			$this->template->set_template_folder_lookup( false );
		}

		return $this->template;
	}
}

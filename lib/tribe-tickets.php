<?php

/**
	 * Joey's notes on this file
	 * 	-- fixed typos in comments: s/definition/definitions (API) & s/this/these ... functions / properties
	 *  -- // instead of /* * / for single line comments
	 *  -- added start/end for comments (though I don't think those comments are necessary at all)
	 *  -- fixed minor spacing/indentation niggles (WP coding standards)
	 *  -- great overall use of OOP :)
	 *  -- added an early return in do_meta_box
	 *  -- minor simplifications done in ajax_handler_ticket_add()
	 *  -- when you call ajax_error() should you also return out of the function? not sure.
	 *  -- cleaned up attendees_row_action()'s handling of the URL + added early return
	 *  -- null should never be NULL (WP coding standards - same goes for TRUE, FALSE, etc...)
	 *  -- admin_print_styles and admin_print_scripts is not the right hook, use admin_enqueue_scripts for both and check the page with the global $pagenow instead (I didn't do this for you)
	 *  -- I think that call_user_func( array( $class, 'get_instance' ) ); will fail in php 5.2, just going off the top of my head though
	 *  -- getTemplateHierarchy() seems hacky but I am not sure off the top how to fix it
	 */

if ( ! class_exists( 'TribeEventsTickets' ) ) {
	abstract class TribeEventsTickets {

		// All TribeEventsTickets api consumers. It's static, so it's shared across childs.
		protected static $active_modules = array();
		public static $active = false;

		public $className;
		private $parentPath;
		private $parentUrl;
		private $attendees_slug = 'tickets-attendees';

		// prevent re-doing the metabox by different childs
		private static $done_metabox = false;
		private static $done_attendees_admin_page = false;

		// start API Definitions
		// Child classes must implement all these functions / properties

		public $pluginName;
		protected $pluginPath;
		protected $pluginUrl;

		abstract function get_event_reports_link( $event_id );

		abstract function get_ticket_reports_link( $event_id, $ticket_id );

		abstract function save_ticket( $event_id, $ticket, $raw_data = array() );

		abstract protected function get_tickets( $event_id );

		abstract protected function get_attendees( $event_id );

		abstract protected function checkin( $order_id );

		abstract function get_ticket( $event_id, $ticket_id );

		abstract function delete_ticket( $event_id, $ticket_id );

		abstract function do_metabox_advanced_options( $event_id, $ticket_id );

		abstract function front_end_tickets_form();

		abstract function get_attendee_pdf( $attendee_id, $grouped_by_order = true );

		abstract static function get_instance();

		// end API Definitions

		function __construct() {

			// As this is an abstract class, we want to know which child
			// instantiated it
			$this->className = get_class( $this );

			$this->parentPath = trailingslashit( dirname( dirname( __FILE__ ) ) );
			$this->parentUrl  = trailingslashit( plugins_url( '', $this->parentPath ) );

			// Register all TribeEventsTickets api consumers
			self::$active_modules[$this->className] = $this->pluginName;

			self::$active = true;

			if ( is_admin() ) {
				add_action( 'tribe_events_event_save', array( $this, 'save_tickets' ), 10, 1 );

				add_action( 'tribe_events_tickets_metabox_advanced', array( $this, 'do_metabox_advanced_options' ), 10, 2 );
			}

			add_filter( 'tribe_events_tickets_modules', array( $this, 'modules' ) );

			// Admin AJAX actions
			add_action( 'wp_ajax_tribe-ticket-add-' . $this->className, array( $this, 'ajax_handler_ticket_add' ) );
			add_action( 'wp_ajax_tribe-ticket-delete-' . $this->className, array( $this, 'ajax_handler_ticket_delete' ) );
			add_action( 'wp_ajax_tribe-ticket-edit-' . $this->className, array( $this, 'ajax_handler_ticket_edit' ) );
			add_action( 'wp_ajax_tribe-ticket-checkin-' . $this->className, array( $this, 'ajax_handler_attendee_checkin' ) );

			// Attendees list
			add_filter( 'post_row_actions', array( $this, 'attendees_row_action' ) );

			add_action( 'admin_menu', array( $this, 'attendees_page_register' ) );

			// Front end
			add_filter( 'tribe_get_ticket_form', array( $this, 'front_end_tickets_form' ) );

		}

		public final function do_meta_box( $post_id ) {

			if ( !self::$done_metabox ) {

				$tickets = self::get_event_tickets( $post_id );

				include $this->parentPath . 'admin-views/tickets-meta-box.php';

				self::$done_metabox = true;
			}

		}

		public final function load_pdf_libraries() {

			if ( !class_exists( "FPDF" ) ) {
				include $this->parentPath . 'vendor/fpdf/fpdf.php';
			}

		}

		/* AJAX Handlers */

		public final function ajax_handler_ticket_add() {

			if ( ! isset( $_POST["formdata"] ) ) $this->ajax_error( 'Bad post' );
			if ( ! isset( $_POST["post_ID"] ) ) $this->ajax_error( 'Bad post' );

			$data    = wp_parse_args( $_POST["formdata"] );
			$post_id = $_POST["post_ID"];

			if ( !isset( $data["ticket_provider"] ) || !$this->module_is_valid( $data["ticket_provider"] ) ) $this->ajax_error( 'Bad module' );

			$ticket = new TribeEventsTicketObject();

			$ticket->ID          = isset( $data["ticket_id"] ) ? $data["ticket_id"] : null;
			$ticket->name        = isset( $data["ticket_name"] ) ? $data["ticket_name"] : null;
			$ticket->description = isset( $data["ticket_description"] ) ? $data["ticket_description"] : null;
			$ticket->price       = isset( $data["ticket_price"] ) ? trim( $data["ticket_price"] ) : 0;

			if ( empty( $ticket->price ) ) {
				$ticket->price = 0;
			} else {
				//remove non-money characters
				$ticket->price = preg_replace( '/[^0-9\.]/Uis', '', $ticket->price );
			}

			$ticket->provider_class = $this->className;

			// Pass the control to the child object
			$return = $this->save_ticket( $post_id, $ticket, $data );

			// If saved OK, let's create a tickets list markup to return
			if ( $return ) {
				$tickets = $this->get_event_tickets( $post_id );
				$return  = $this->get_ticket_list_markup( $tickets );
			}


			$this->ajax_ok( $return );
		}

		public final function ajax_handler_attendee_checkin() {

			if ( ! isset( $_POST["order_ID"] ) || intval( $_POST["order_ID"] ) == 0 )
				$this->ajax_error( 'Bad post' );
			if ( ! isset( $_POST["provider"] ) || ! $this->module_is_valid( $_POST["provider"] ) )
				$this->ajax_error( 'Bad module' );

			$order_id = $_POST["order_ID"];

			// Pass the control to the child object
			$return = $this->checkin( $order_id );

			$this->ajax_ok( $return );
		}

		public final function ajax_handler_ticket_delete() {

			if ( ! isset( $_POST["post_ID"] ) )
				$this->ajax_error( 'Bad post' );
			if ( ! isset( $_POST["ticket_id"] ) )
				$this->ajax_error( 'Bad post' );

			$post_id   = $_POST["post_ID"];
			$ticket_id = $_POST["ticket_id"];

			// Pass the control to the child object
			$return = $this->delete_ticket( $post_id, $ticket_id );

			// If deleted OK, let's create a tickets list markup to return
			if ( $return ) {
				$tickets = $this->get_event_tickets( $post_id );
				$return  = $this->get_ticket_list_markup( $tickets );
			}


			$this->ajax_ok( $return );
		}

		public final function ajax_handler_ticket_edit() {

			if ( ! isset( $_POST["post_ID"] ) )
				$this->ajax_error( 'Bad post' );
			if ( ! isset( $_POST["ticket_id"] ) )
				$this->ajax_error( 'Bad post' );

			$post_id   = $_POST["post_ID"];
			$ticket_id = $_POST["ticket_id"];

			$return = get_object_vars( $this->get_ticket( $post_id, $ticket_id ) );

			ob_start();
			$this->do_metabox_advanced_options( $post_id, $ticket_id );
			$extra = ob_get_contents();
			ob_end_clean();

			$return["advanced_fields"] = $extra;

			$this->ajax_ok( $return );
		}

		protected final function ajax_error( $message = "" ) {
			header( 'Content-type: application/json' );
			echo json_encode( array( "success" => false,
			                         "message" => $message ) );
			exit;
		}

		protected final function ajax_ok( $data ) {
			$return = array();
			if ( is_object( $data ) ) {
				$return = get_object_vars( $data );
			} elseif ( is_array( $data ) || is_string( $data ) ) {
				$return = $data;
			} elseif ( is_bool( $data ) && !$data ) {
				$this->ajax_error( "Something went wrong" );
			}

			header( 'Content-type: application/json' );
			echo json_encode( array( "success" => true,
			                         "data"    => $return ) );
			exit;
		}

		// end AJAX Handlers

		// start Attendees

		public function attendees_row_action( $actions ) {
			global $post;
			if ( $post->post_type == TribeEvents::POSTTYPE ) {

			$url = add_query_arg( array( 'post_type' => TribeEvents::POSTTYPE, 'page' => $this->attendees_slug, 'event_id' => $post->ID ), admin_url( 'edit.php' ) );
			$actions['tickets_attendees'] = sprintf( '<a title="See who purchased tickets to this event" href="%s">Attendees</a>', esc_url( $url ) );

			}
			return $actions;
		}

		public function attendees_page_register() {
			if ( ! self::$done_attendees_admin_page ) {
				$page = add_submenu_page( null, 'Attendee list', 'Attendee list', 'edit_posts', $this->attendees_slug, array( $this, 'attendees_page_inside' ) );

				add_action( 'admin_print_styles-' . $page, array( $this, 'attendees_page_load_css' ) );
				add_action( 'admin_print_scripts-' . $page, array( $this, 'attendees_page_load_js' ) );

				self::$done_attendees_admin_page = true;
			}
		}

		public function attendees_page_load_css() {
			$ecp = TribeEvents::instance();

			wp_register_style( $this->attendees_slug, trailingslashit( $ecp->pluginUrl ) . '/resources/tickets-attendees.css' );
			wp_enqueue_style( $this->attendees_slug );
		}

		public function attendees_page_load_js() {
			$ecp = TribeEvents::instance();

			wp_register_script( $this->attendees_slug, trailingslashit( $ecp->pluginUrl ) . '/resources/tickets-attendees.js', array( 'jquery' ) );
			wp_enqueue_script( $this->attendees_slug );
		}

		public function attendees_page_inside() {

			require_once 'tribe-tickets-attendees.php';
			$attendees_table = new TribeEventsTicketsAttendeesTable();
			$attendees_table->prepare_items();

			include $this->parentPath . 'admin-views/tickets-attendees.php';
		}

		final static public function get_event_attendees( $event_id ) {

			$attendees = array();

			foreach ( self::$active_modules as $class=> $module ) {
				$obj = call_user_func( array( $class, 'get_instance' ) );
				$attendees = array_merge( $attendees, $obj->get_attendees( $event_id ) );
			}

			return $attendees;

		}


		// endA ttendees

		// start Helpers

		private function module_is_valid( $module ) {
			return array_key_exists( $module, self::$active_modules );
		}

		private function ticket_list_markup( $tickets = array() ) {
			if ( ! empty( $tickets ) )
				include $this->parentPath . 'admin-views/tickets-list.php';
		}

		private function get_ticket_list_markup( $tickets = array() ) {

			ob_start();
			$this->ticket_list_markup( $tickets );
			$return = ob_get_contents();
			ob_end_clean();

			return $return;
		}

		protected function tr_class() {
			echo "ticket_advanced ticket_advanced_" . $this->className;
		}

		public function modules() {
			return self::$active_modules;
		}

		final static public function get_event_tickets( $event_id ) {

			$tickets = array();

			foreach ( self::$active_modules as $class=> $module ) {
				$obj = call_user_func( array( $class, 'get_instance' ) );
				$tickets = array_merge( $tickets, $obj->get_tickets( $event_id ) );
			}

			return $tickets;
		}

		public function getTemplateHierarchy( $template ) {

			if ( substr( $template, -4 ) != '.php' ) {
				$template .= '.php';
			}

			if ( $theme_file = locate_template( array( 'events/' . $template ) ) ) {
				$file = $theme_file;
			} else {
				$file = $this->pluginPath . 'views/' . $template;
			}
			return apply_filters( 'tribe_events_tickets_template_' . $template, $file );
		}

		// end Helpers

	}
}

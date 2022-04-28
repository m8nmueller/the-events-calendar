<?php

/**
 * Tests event creation functionality
 *
 * @group   core
 *
 * @package TribeEvents
 */
class Event_Creation_Test extends \Codeception\TestCase\WPTestCase {
	protected $post_example_settings;

	function setUp() {
		parent::setUp(); // @todo: [BTRIA-586] Change the autogenerated stub
		$this->post_example_settings = array(
			'post_author'           => 3,
			'post_title'            => 'Test event',
			'post_content'          => 'This is event content!',
			'post_status'           => 'publish',
			'EventAllDay'           => false,
			'EventHideFromUpcoming' => true,
			'EventOrganizerID'      => 5,
			'EventVenueID'          => 8,
			'EventShowMapLink'      => true,
			'EventShowMap'          => true,
			'EventStartDate'        => '2012-01-01',
			'EventEndDate'          => '2012-01-03',
			'EventStartHour'        => '01',
			'EventStartMinute'      => '15',
			'EventStartMeridian'    => 'am',
			'EventEndHour'          => '03',
			'EventEndMinute'        => '25',
			'EventEndMeridian'      => 'pm',
		);
	}

	/**
	 * Check to make sure that the post object is created from a returned post ID.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_create_event_template_tag_post_object_created() {
		$post = get_post( tribe_create_event( $this->post_example_settings ) );

		$this->assertInternalType( 'object', $post );
	}

	/**
	 * Check to make sure that an event is not successfully created if an invalid start hour meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_create_event_false_if_invalid_meta_detected_start_hour() {
		$post_settings = $this->post_example_settings;

		$post_settings['EventStartHour'] = 24; // greater than 23

		$event_result = tribe_create_event( $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that an event is not successfully created if an invalid start minute meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_create_event_false_if_invalid_meta_detected_start_minute() {
		$post_settings = $this->post_example_settings;

		$post_settings['EventStartMinute'] = 60; // greater than 59

		$event_result = tribe_create_event( $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that an event is not successfully created if an invalid end hour meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_create_event_false_if_invalid_meta_detected_end_hour() {
		$post_settings = $this->post_example_settings;

		$post_settings['EventEndHour'] = 24; // greater than 23

		$event_result = tribe_create_event( $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that an event is not successfully created if an invalid end minute meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_create_event_false_if_invalid_meta_detected_end_minute() {
		$post_settings = $this->post_example_settings;

		$post_settings['EventEndMinute'] = 60; // greater than 59

		$event_result = tribe_create_event( $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that an event is not successfully updated if an invalid start hour meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_update_event_false_if_invalid_meta_detected_start_hour() {
		$post_settings = $this->post_example_settings;

		$event_id = tribe_create_event( $post_settings );

		$post_settings['EventStartHour'] = 24; // greater than 23

		$event_result = tribe_update_event( $event_id, $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that an event is not successfully updated if an invalid start minute meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_update_event_false_if_invalid_meta_detected_start_minute() {
		$post_settings = $this->post_example_settings;

		$event_id = tribe_create_event( $post_settings );

		$post_settings['EventStartMinute'] = 60; // greater than 59

		$event_result = tribe_update_event( $event_id, $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that an event is not successfully updated if an invalid end hour meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_update_event_false_if_invalid_meta_detected_end_hour() {
		$post_settings = $this->post_example_settings;

		$event_id = tribe_create_event( $post_settings );

		$post_settings['EventEndHour'] = 24; // greater than 23

		$event_result = tribe_update_event( $event_id, $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that an event is not successfully updated if an invalid end minute meta value is detected.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_update_event_false_if_invalid_meta_detected_end_minute() {
		$post_settings = $this->post_example_settings;

		$event_id = tribe_create_event( $post_settings );

		$post_settings['EventEndMinute'] = 60; // greater than 59

		$event_result = tribe_update_event( $event_id, $post_settings );

		$this->assertFalse( $event_result );
	}

	/**
	 * Check to make sure that the event data is saved properly.
	 *
	 */
	public function test_tribe_create_event_template_tag_meta_information() {
		$post = get_post( tribe_create_event( $this->post_example_settings ) );

		$this->assertEquals( 3, $post->post_author );
		$this->assertEquals( 'This is event content!', $post->post_content );
		//The Event does not go all day so it is 'no'
		$this->assertFalse( tribe_is_truthy( get_post_meta( $post->ID, '_EventAllDay', true ) ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventHideFromUpcoming', true ) );
		$this->assertEquals( 5, get_post_meta( $post->ID, '_EventOrganizerID', true ) );
		$this->assertEquals( 8, get_post_meta( $post->ID, '_EventVenueID', true ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventShowMapLink', true ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventShowMapLink', true ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventShowMap', true ) );
		$this->assertEquals( '2012-01-03 15:25:00', get_post_meta( $post->ID, '_EventEndDate', true ) );
	}

	/**
	 * Check to make sure that the post object is created from a returned post ID.
	 *
	 * @uses $post_example_settings
	 */
	public function test_tribe_create_event_API_post_object_created() {
		$post = get_post( Tribe__Events__API::createEvent( $this->post_example_settings ) );

		$this->assertInternalType( 'object', $post );
	}

	/**
	 * Check to make sure that the event data is saved properly.
	 */
	public function test_tribe_create_event_API_meta_information() {
		$post = get_post( Tribe__Events__API::createEvent( $this->post_example_settings ) );

		$this->assertEquals( 3, $post->post_author );
		$this->assertEquals( 'This is event content!', $post->post_content );
		$this->assertFalse( tribe_is_truthy( get_post_meta( $post->ID, '_EventAllDay', true ) ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventHideFromUpcoming', true ) );
		$this->assertEquals( 5, get_post_meta( $post->ID, '_EventOrganizerID', true ) );
		$this->assertEquals( 8, get_post_meta( $post->ID, '_EventVenueID', true ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventShowMapLink', true ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventShowMapLink', true ) );
		$this->assertEquals( 1, get_post_meta( $post->ID, '_EventShowMap', true ) );
		$this->assertEquals( '2012-01-03 15:25:00', get_post_meta( $post->ID, '_EventEndDate', true ) );
	}

}

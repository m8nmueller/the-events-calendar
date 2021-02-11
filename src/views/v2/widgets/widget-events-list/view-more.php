<?php
/**
 * Widget: Events List Event
 *
 * Override this template in your own theme by creating a file at:
 * [your-theme]/tribe/events/v2/widgets/widget-events-list/event.php
 *
 * See more documentation about our views templating system.
 *
 * @link http://evnt.is/1aiy
 *
 * @version 5.2.1
 *
 * @var string $view_more_url   The URL to view all events.
 * @var string $view_more_text  The text for the "view more" link.
 * @var string $view_more_title The widget "view more" link title attribute. Adds some context to the link for screen readers.
 *
 * @see tribe_get_event() For the format of the event object.
 */

if ( empty( $view_more_url ) ) {
	return;
}
?>
<div class="tribe-events-widget-events-list__view-more tribe-common-b1 tribe-common-b2--min-medium">
	<a
		href="<?php echo esc_url( $view_more_url ); ?>"
		class="tribe-events-widget-events-list__view-more-link tribe-common-anchor-thin"
		title="<?php echo esc_attr( $view_more_title ); ?>"
	>
		<?php echo esc_html( $view_more_text ); ?>
	</a>
</div>

<?php
/**
 * Validates an End Date UTC input.
 *
 * @since   TBD
 *
 * @package TEC\Custom_Tables\V1\Models\Validators
 */

namespace TEC\Custom_Tables\V1\Models\Validators;


use TEC\Custom_Tables\V1\Models\Model;

/**
 * Class Valid_Event
 *
 * @since   TBD
 *
 * @package TEC\Custom_Tables\V1\Models\Validators
 */
class Valid_Event extends Validation {
	/**
	 * {@inheritDoc}
	 */
	public function validate( Model $model, $name, $value ) {
		$this->error_message = '';

		$is_valid_event = tribe_is_event( $value );

		if ( ! $is_valid_event ) {
			$this->error_message = 'The provided input is not a valid Event type.';
		}

		return $is_valid_event;
	}
}

/**
 * WPCalibrate License Manager Admin JavaScript
 */
(function($) {
	'use strict';

	$(document).ready(function() {
		// Toggle custom expiry field based on is_lifetime checkbox
		const $lifetimeCheckbox = $('#lic_is_lifetime');
		const $expiryField = $('#lic_expiry_field');

		if ($lifetimeCheckbox.length && $expiryField.length) {
			function toggleExpiry() {
				if ($lifetimeCheckbox.is(':checked')) {
					$expiryField.slideUp(150);
				} else {
					$expiryField.slideDown(150);
				}
			}

			$lifetimeCheckbox.on('change', toggleExpiry);
			toggleExpiry();
		}
	});
})(jQuery);

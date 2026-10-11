/**
 * Live preview bindings for the Customizer.
 */
(function ($) {
	'use strict';

	if (!window.wp || !window.wp.customize) {
		return;
	}

	wp.customize('blogname', function (value) {
		value.bind(function (to) {
			$('.site-logo__name').text(to);
		});
	});

	wp.customize('treats_business_name', function (value) {
		value.bind(function (to) {
			$('.site-logo__name').text(to);
		});
	});

	wp.customize('treats_logo_tagline', function (value) {
		value.bind(function (to) {
			$('.site-logo__tag').text(to);
		});
	});

	wp.customize('treats_accent_color', function (value) {
		value.bind(function (to) {
			var style = document.getElementById('treats-accent-live');

			if (!style) {
				style = document.createElement('style');
				style.id = 'treats-accent-live';
				document.head.appendChild(style);
			}

			style.textContent = ':root{--c-accent:' + to + ';--c-accent-strong:' + to + '}';
		});
	});
})(jQuery);

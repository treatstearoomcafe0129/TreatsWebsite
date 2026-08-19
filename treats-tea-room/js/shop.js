/**
 * Basket behaviour.
 *
 * Every basket form works as a plain POST without this file. What it adds is
 * the drawer, the running count, and the ability to change your basket without
 * losing your place on the page.
 */
(function () {
	'use strict';

	var data = window.treatsData || {};
	var shop = window.treatsShopData || {};
	var i18n = shop.i18n || {};

	function $(selector, scope) {
		return (scope || document).querySelector(selector);
	}

	function $$(selector, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
	}

	var drawer = $('.basket-drawer');
	var body = $('[data-basket-body]');
	var toast = $('[data-basket-toast]');
	var lastFocus = null;
	var toastTimer = null;

	/* ---------------------------------------------------------------------
	 * The drawer
	 * ------------------------------------------------------------------ */

	function focusable() {
		return $$(
			'a[href], button:not([disabled]), input:not([disabled]), textarea, select, [tabindex]:not([tabindex="-1"])',
			drawer
		).filter(function (el) {
			return el.offsetParent !== null;
		});
	}

	function openDrawer() {
		if (!drawer || drawer.classList.contains('is-open')) {
			return;
		}

		lastFocus = document.activeElement;

		drawer.hidden = false;

		// Let the browser paint the hidden state before transitioning, or the
		// panel jumps into place instead of sliding.
		requestAnimationFrame(function () {
			drawer.classList.add('is-open');
		});

		document.body.classList.add('has-drawer');

		$$('[data-basket-open]').forEach(function (button) {
			button.setAttribute('aria-expanded', 'true');
		});

		var first = focusable()[0];

		if (first) {
			first.focus();
		}
	}

	function closeDrawer() {
		if (!drawer || !drawer.classList.contains('is-open')) {
			return;
		}

		drawer.classList.remove('is-open');
		document.body.classList.remove('has-drawer');

		$$('[data-basket-open]').forEach(function (button) {
			button.setAttribute('aria-expanded', 'false');
		});

		window.setTimeout(function () {
			if (!drawer.classList.contains('is-open')) {
				drawer.hidden = true;
			}
		}, 300);

		if (lastFocus && document.contains(lastFocus)) {
			lastFocus.focus();
		}
	}

	document.addEventListener('click', function (event) {
		if (event.target.closest('[data-basket-open]')) {
			event.preventDefault();
			openDrawer();
			return;
		}

		if (event.target.closest('[data-basket-close]')) {
			event.preventDefault();
			closeDrawer();
		}
	});

	document.addEventListener('keydown', function (event) {
		if (!drawer || !drawer.classList.contains('is-open')) {
			return;
		}

		if (event.key === 'Escape') {
			closeDrawer();
			return;
		}

		if (event.key !== 'Tab') {
			return;
		}

		// Keep the keyboard inside the drawer while it is open.
		var items = focusable();

		if (!items.length) {
			return;
		}

		var first = items[0];
		var last = items[items.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	});

	/* ---------------------------------------------------------------------
	 * Talking to the server
	 * ------------------------------------------------------------------ */

	function announce(message, isError) {
		if (!toast || !message) {
			return;
		}

		toast.textContent = message;
		toast.hidden = false;
		toast.classList.toggle('basket-toast--error', !!isError);
		toast.classList.add('is-visible');

		window.clearTimeout(toastTimer);

		toastTimer = window.setTimeout(function () {
			toast.classList.remove('is-visible');

			window.setTimeout(function () {
				toast.hidden = true;
			}, 300);
		}, 4000);
	}

	function setCount(count) {
		$$('[data-basket-count]').forEach(function (el) {
			el.textContent = String(count);
		});

		$$('.basket-fab').forEach(function (fab) {
			fab.classList.toggle('basket-fab--full', count > 0);
		});
	}

	function submit(form, options) {
		var payload = new FormData(form);
		var settings = options || {};

		if (!payload.get('treats_nonce')) {
			payload.set('treats_nonce', data.nonce || '');
		}

		var button = form.querySelector('button[type="submit"]');

		if (button) {
			button.disabled = true;
		}

		return window
			.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(function (response) {
				return response.json().then(function (json) {
					return { ok: response.ok, json: json };
				});
			})
			.then(function (result) {
				var payloadData = (result.json && result.json.data) || {};

				if (payloadData.markup && body) {
					body.innerHTML = payloadData.markup;
				}

				if (typeof payloadData.count !== 'undefined') {
					setCount(payloadData.count);
				}

				announce(payloadData.message, !result.ok);

				if (result.ok && settings.open) {
					openDrawer();
				}

				// A basket change alters prices on the checkout page, which is
				// rendered server side. Reloading is simpler and safer than
				// patching half a dozen figures in place.
				if (result.ok && settings.reload) {
					window.location.reload();
				}
			})
			.catch(function () {
				announce(i18n.error || 'Something went wrong. Please try again.', true);
			})
			.then(function () {
				if (button) {
					button.disabled = false;
				}
			});
	}

	document.addEventListener('submit', function (event) {
		var form = event.target.closest('[data-treats-basket]');

		if (!form || !window.fetch || !data.ajaxUrl) {
			return;
		}

		event.preventDefault();

		// Adding deliberately does not open the drawer. On a listing people
		// add several things in a row, and a panel that covers the grid after
		// every one of them is an interruption, not a confirmation. The toast
		// and the basket button appearing say enough.
		submit(form, { reload: !!$('[data-treats-checkout]') });
	});

	// Changing a quantity should not need a second tap on "Update".
	document.addEventListener('change', function (event) {
		var input = event.target.closest('.basket-line__input');

		if (!input) {
			return;
		}

		var form = input.closest('form');

		if (form && window.fetch && data.ajaxUrl) {
			submit(form, { reload: !!$('[data-treats-checkout]') });
		}
	});

	/* ---------------------------------------------------------------------
	 * Product gallery
	 * ------------------------------------------------------------------ */

	var gallery = $('[data-product-gallery]');

	if (gallery) {
		gallery.addEventListener('click', function (event) {
			var thumb = event.target.closest('.product__thumb');

			if (!thumb) {
				return;
			}

			var main = document.getElementById('product-main-image');
			var full = thumb.getAttribute('data-full');

			if (main && full) {
				main.src = full;
				main.removeAttribute('srcset');
				main.removeAttribute('sizes');
			}

			$$('.product__thumb', gallery).forEach(function (other) {
				other.classList.toggle('product__thumb--active', other === thumb);
			});
		});
	}
})();

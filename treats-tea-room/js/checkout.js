/**
 * Checkout.
 *
 * Square's Web Payments SDK renders the card fields inside an iframe it owns,
 * hands back a single-use token, and that token is all this page ever sees.
 * Apple Pay and Google Pay are offered when the browser supports them.
 */
(function () {
	'use strict';

	var data = window.treatsData || {};
	var checkout = window.treatsCheckoutData || {};
	var i18n = checkout.i18n || {};

	var form = document.querySelector('[data-treats-checkout]');

	if (!form) {
		return;
	}

	var submitButton = form.querySelector('[data-checkout-submit]');
	var errorBox = form.querySelector('[data-checkout-error]');
	var loading = form.querySelector('[data-square-loading]');
	var totalLabel = form.querySelector('[data-checkout-total]');
	var summaryPostage = document.querySelector('[data-summary-postage]');
	var summaryTotal = document.querySelector('[data-summary-total]');

	var payments = null;
	var card = null;
	var busy = false;

	function money(pence) {
		return (checkout.currency || '£') + (Number(pence) / 100).toFixed(2);
	}

	/**
	 * Subtotal and postage, as numbers.
	 *
	 * Coerced rather than trusted: anything that hands these over as strings
	 * turns the addition below into concatenation, and £208 silently becomes
	 * £2080 on the button the customer is about to press.
	 */
	function amounts() {
		var mode = currentFulfilment();
		var postage = Number((checkout.postage || {})[mode]) || 0;
		var subtotal = Number(checkout.subtotal) || 0;

		return { mode: mode, postage: postage, total: subtotal + postage };
	}

	function showError(message) {
		if (!errorBox) {
			return;
		}

		errorBox.textContent = message;
		errorBox.hidden = !message;

		if (message) {
			errorBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}
	}

	/**
	 * The card form has finished trying, one way or the other.
	 *
	 * Leaving "Loading the secure card form…" on screen underneath an error
	 * saying it could not be loaded reads as though it might still arrive.
	 */
	function stopLoading() {
		if (loading) {
			loading.hidden = true;
		}
	}

	function setBusy(state) {
		busy = state;

		if (!submitButton) {
			return;
		}

		submitButton.disabled = state;
		submitButton.classList.toggle('is-busy', state);
	}

	/* ---------------------------------------------------------------------
	 * Fulfilment
	 * ------------------------------------------------------------------ */

	function currentFulfilment() {
		var chosen = form.querySelector('[data-fulfilment]:checked');

		return chosen ? chosen.value : 'collect';
	}

	function requiredForPost(state) {
		['treats_address1', 'treats_city', 'treats_postcode'].forEach(function (name) {
			var field = form.querySelector('[name="' + name + '"]');

			if (!field) {
				return;
			}

			if (state) {
				field.setAttribute('required', 'required');
			} else {
				field.removeAttribute('required');
			}
		});
	}

	function updateTotals() {
		var sums = amounts();
		var mode = sums.mode;
		var postage = sums.postage;
		var total = sums.total;

		if (summaryPostage) {
			summaryPostage.textContent = postage > 0 ? money(postage) : i18n.free || 'Free';
		}

		if (summaryTotal) {
			summaryTotal.textContent = money(total);
		}

		if (totalLabel) {
			totalLabel.textContent = money(total);
		}

		Array.prototype.forEach.call(
			form.querySelectorAll('[data-when]'),
			function (block) {
				block.hidden = block.getAttribute('data-when') !== mode;
			}
		);

		requiredForPost(mode === 'post');
	}

	Array.prototype.forEach.call(
		form.querySelectorAll('[data-fulfilment]'),
		function (radio) {
			radio.addEventListener('change', updateTotals);
		}
	);

	updateTotals();

	/* ---------------------------------------------------------------------
	 * Square
	 * ------------------------------------------------------------------ */

	function orderRequest() {
		var total = amounts().total;

		return {
			countryCode: 'GB',
			currencyCode: 'GBP',
			total: {
				amount: (total / 100).toFixed(2),
				label: checkout.businessName || 'Total'
			}
		};
	}

	function attachWallet(instance, elementId) {
		var element = document.getElementById(elementId);

		if (!element) {
			return Promise.resolve(false);
		}

		return instance
			.attach('#' + elementId)
			.then(function () {
				element.hidden = false;

				var wallets = form.querySelector('[data-square-wallets]');

				if (wallets) {
					wallets.hidden = false;
				}

				element.addEventListener('click', function (event) {
					event.preventDefault();
					pay(instance);
				});

				return true;
			})
			.catch(function () {
				return false;
			});
	}

	function initWallets() {
		// Digital wallets are best-effort: a browser that cannot offer them
		// should simply see the card form, never an error.
		var request;

		try {
			request = payments.paymentRequest(orderRequest());
		} catch (error) {
			return;
		}

		if (payments.applePay) {
			payments
				.applePay(request)
				.then(function (applePay) {
					return attachWallet(applePay, 'square-apple-pay');
				})
				.catch(function () {});
		}

		if (payments.googlePay) {
			payments
				.googlePay(request)
				.then(function (googlePay) {
					return attachWallet(googlePay, 'square-google-pay');
				})
				.catch(function () {});
		}
	}

	function initSquare() {
		if (!window.Square) {
			stopLoading();
			showError(i18n.sdkFailed || 'The payment form could not be loaded. Please ring us to order.');

			return;
		}

		try {
			payments = window.Square.payments(checkout.appId, checkout.locationId);
		} catch (error) {
			stopLoading();
			showError(i18n.sdkFailed || 'The payment form could not be loaded. Please ring us to order.');

			return;
		}

		payments
			.card()
			.then(function (instance) {
				card = instance;

				return card.attach('#square-card');
			})
			.then(function () {
				stopLoading();
				setBusy(false);
				initWallets();
			})
			.catch(function () {
				stopLoading();
				showError(i18n.sdkFailed || 'The payment form could not be loaded. Please ring us to order.');
			});
	}

	/* ---------------------------------------------------------------------
	 * Paying
	 * ------------------------------------------------------------------ */

	function validate() {
		var missing = null;

		Array.prototype.forEach.call(form.querySelectorAll('[required]'), function (field) {
			if (field.offsetParent === null) {
				return;
			}

			var wrapper = field.closest('.field');
			var target = wrapper ? wrapper.querySelector('.field__error') : null;
			var valid = field.checkValidity() && String(field.value || '').trim() !== '';

			if (wrapper) {
				wrapper.classList.toggle('has-error', !valid);
			}

			if (target) {
				target.textContent = valid ? '' : i18n.required || 'Please complete this field.';
			}

			if (!valid && !missing) {
				missing = field;
			}
		});

		if (missing) {
			missing.focus();
			showError(i18n.checkFields || 'Please check the highlighted fields.');
		}

		return !missing;
	}

	function pay(source) {
		if (busy) {
			return;
		}

		if (!validate()) {
			return;
		}

		showError('');
		setBusy(true);

		var tokeniser = source || card;

		if (!tokeniser) {
			showError(i18n.sdkFailed || 'The payment form is not ready. Please refresh and try again.');
			setBusy(false);

			return;
		}

		tokeniser
			.tokenize(source ? orderRequest() : undefined)
			.then(function (result) {
				if (result.status !== 'OK') {
					var detail =
						result.errors && result.errors.length
							? result.errors[0].message
							: i18n.cardFailed || 'Please check your card details.';

					throw new Error(detail);
				}

				var payload = new FormData(form);

				payload.set('treats_source_id', result.token);

				return window.fetch(data.ajaxUrl, {
					method: 'POST',
					credentials: 'same-origin',
					body: payload
				});
			})
			.then(function (response) {
				return response.json().then(function (json) {
					return { ok: response.ok, json: json };
				});
			})
			.then(function (result) {
				var body = (result.json && result.json.data) || {};

				if (!result.ok || !body.redirect) {
					throw new Error(body.message || i18n.generic || 'We could not take that payment.');
				}

				// Leave the button disabled: the order is placed and a second
				// press would be a second attempt at the same basket.
				window.location.href = body.redirect;
			})
			.catch(function (error) {
				showError(error.message || i18n.generic || 'We could not take that payment.');
				setBusy(false);
			});
	}

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		pay(null);
	});

	if (window.Square) {
		initSquare();
	} else {
		window.addEventListener('load', initSquare);
	}
})();

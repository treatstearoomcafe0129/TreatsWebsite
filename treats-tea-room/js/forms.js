/**
 * Form behaviour: client-side validation, AJAX submission and the booking
 * date/time rules.
 *
 * Every form still works with JavaScript disabled — this layer only removes
 * the page reload and catches mistakes earlier.
 */
(function () {
	'use strict';

	var data = window.treatsData || {};
	var formData = window.treatsFormData || {};
	var i18n = data.i18n || {};

	function $(selector, scope) {
		return (scope || document).querySelector(selector);
	}

	function $$(selector, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
	}

	function on(el, type, handler) {
		if (el) {
			el.addEventListener(type, handler);
		}
	}

	/* ---------------------------------------------------------------------
	 * Validation
	 * ------------------------------------------------------------------ */

	function fieldWrapper(input) {
		return input.closest('.field') || input.parentNode;
	}

	function setError(input, message) {
		var wrapper = fieldWrapper(input);
		var target = $('.field__error', wrapper);

		if (message) {
			wrapper.classList.add('has-error');
			input.setAttribute('aria-invalid', 'true');

			if (target) {
				target.textContent = message;
			}
		} else {
			wrapper.classList.remove('has-error');
			input.removeAttribute('aria-invalid');

			if (target) {
				target.textContent = '';
			}
		}
	}

	function validateInput(input) {
		var value = (input.value || '').trim();

		if (input.hasAttribute('required') && value === '') {
			setError(input, i18n.requiredField || 'Please complete this field.');

			return false;
		}

		if (value !== '' && input.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(value)) {
			setError(input, i18n.invalidEmail || 'Please enter a valid email address.');

			return false;
		}

		if (value !== '' && input.type === 'tel' && value.replace(/[^0-9]/g, '').length < 7) {
			setError(input, 'Please enter a contact number we can reach you on.');

			return false;
		}

		if (input.type === 'date' && value !== '') {
			if (input.min && value < input.min) {
				setError(input, 'Please choose a later date.');

				return false;
			}

			if (input.max && value > input.max) {
				setError(input, 'Please choose an earlier date.');

				return false;
			}
		}

		setError(input, '');

		return true;
	}

	function validateForm(form) {
		var valid = true;
		var firstInvalid = null;

		$$('input, select, textarea', form).forEach(function (input) {
			if (input.type === 'hidden' || input.name === 'treats_website') {
				return;
			}

			if (!validateInput(input)) {
				valid = false;

				if (!firstInvalid) {
					firstInvalid = input;
				}
			}
		});

		// Radio groups marked as required need at least one selection.
		$$('[data-required-group]', form).forEach(function (group) {
			var checked = $('input:checked', group);

			if (!checked) {
				valid = false;
				group.classList.add('has-error');

				if (!firstInvalid) {
					firstInvalid = $('input', group);
				}
			} else {
				group.classList.remove('has-error');
			}
		});

		if (firstInvalid) {
			firstInvalid.focus();
		}

		return valid;
	}

	/* ---------------------------------------------------------------------
	 * AJAX submission
	 * ------------------------------------------------------------------ */

	function showResponse(form, message, isSuccess) {
		var box = $('[data-form-response]', form);

		if (!box) {
			box = document.createElement('div');
			box.setAttribute('data-form-response', '');
			form.insertBefore(box, form.firstChild);
		}

		box.className = 'form-response form-response--' + (isSuccess ? 'success' : 'error');
		box.textContent = message;
		box.hidden = false;
		box.setAttribute('role', 'status');
		box.setAttribute('tabindex', '-1');
		box.focus();
	}

	function submit(form, event) {
		event.preventDefault();

		if (!validateForm(form)) {
			return;
		}

		var button = $('[type="submit"]', form);
		var originalLabel = button ? button.innerHTML : '';
		var action = form.dataset.treatsForm || '';

		if (button) {
			button.disabled = true;
			button.innerHTML = '<span class="spinner" aria-hidden="true"></span> ' + (i18n.sending || 'Sending…');
		}

		var payload = new FormData(form);

		window
			.fetch(data.ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: payload
			})
			.then(function (response) {
				return response.json().catch(function () {
					return { success: false, data: { message: '' } };
				});
			})
			.then(function (result) {
				var body = result.data || {};
				var message = body.message || (i18n.genericError || 'Something went wrong.').replace('%s', formData.phone || '');

				showResponse(form, message, !!result.success);

				if (result.success) {
					form.reset();

					$$('.field.has-error', form).forEach(function (field) {
						field.classList.remove('has-error');
					});

					document.dispatchEvent(
						new CustomEvent('treats:form-success', {
							detail: { action: action, form: form }
						})
					);

					if (body.redirect) {
						window.setTimeout(function () {
							window.location.href = body.redirect;
						}, 1200);
					}
				}
			})
			.catch(function () {
				showResponse(
					form,
					(i18n.genericError || 'Something went wrong.').replace('%s', formData.phone || ''),
					false
				);
			})
			.then(function () {
				if (button) {
					button.disabled = false;
					button.innerHTML = originalLabel;
				}
			});
	}

	function initForms() {
		$$('[data-treats-form]').forEach(function (form) {
			// Native validation bubbles would fight our own messages.
			form.setAttribute('novalidate', '');

			on(form, 'submit', function (event) {
				submit(form, event);
			});

			$$('input, select, textarea', form).forEach(function (input) {
				on(input, 'blur', function () {
					if ((input.value || '').trim() !== '' || input.hasAttribute('required')) {
						validateInput(input);
					}
				});

				on(input, 'input', function () {
					if (fieldWrapper(input).classList.contains('has-error')) {
						validateInput(input);
					}
				});
			});
		});
	}

	/* ---------------------------------------------------------------------
	 * Booking date & time rules
	 * ------------------------------------------------------------------ */

	function pad(value) {
		return value < 10 ? '0' + value : '' + value;
	}

	function toISODate(date) {
		return date.getFullYear() + '-' + pad(date.getMonth() + 1) + '-' + pad(date.getDate());
	}

	function initBooking() {
		var dateInput = $('[data-booking-date]');

		if (!dateInput) {
			return;
		}

		var timeGroup = $('[data-booking-times]');
		var closedDays = formData.closedDays || [];
		var slots = formData.slots || [];
		var leadHours = parseInt(formData.leadTimeHours, 10) || 0;
		var maxDays = parseInt(formData.maxDaysAhead, 10) || 90;
		var notice = $('[data-booking-notice]');

		var earliest = new Date();

		earliest.setHours(earliest.getHours() + leadHours);

		var latest = new Date();

		latest.setDate(latest.getDate() + maxDays);

		dateInput.min = toISODate(earliest);
		dateInput.max = toISODate(latest);

		function updateTimes() {
			var value = dateInput.value;

			if (!value || !timeGroup) {
				return;
			}

			var parts = value.split('-');
			var chosen = new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10));
			var isClosed = closedDays.indexOf(chosen.getDay()) !== -1;

			if (notice) {
				notice.textContent = isClosed ? 'We are closed on that day — please choose another date.' : '';
				notice.hidden = !isClosed;
			}

			if (isClosed) {
				setError(dateInput, 'We are closed on that day.');
			} else {
				setError(dateInput, '');
			}

			var isToday = toISODate(new Date()) === value;
			var cutoff = new Date();

			cutoff.setHours(cutoff.getHours() + leadHours);

			$$('input[type="radio"]', timeGroup).forEach(function (input) {
				var slot = input.value;
				var disabled = isClosed;

				if (!disabled && isToday) {
					var slotParts = slot.split(':');
					var slotTime = new Date();

					slotTime.setHours(parseInt(slotParts[0], 10), parseInt(slotParts[1], 10), 0, 0);
					disabled = slotTime < cutoff;
				}

				input.disabled = disabled;

				if (disabled && input.checked) {
					input.checked = false;
				}
			});

			// If every slot is out, say so rather than leaving a dead form.
			var available = $$('input[type="radio"]:not(:disabled)', timeGroup).length;

			if (!available && notice && !isClosed) {
				notice.textContent = 'No times left today — please choose another date, or call us.';
				notice.hidden = false;
			}
		}

		on(dateInput, 'change', updateTimes);
		on(dateInput, 'input', updateTimes);

		if (dateInput.value) {
			updateTimes();
		}

		// Keep the slot count honest for screen readers.
		if (slots.length && timeGroup) {
			timeGroup.setAttribute('aria-label', slots.length + ' available times');
		}
	}

	/* ---------------------------------------------------------------------
	 * Voucher amount — “other” unlocks a free-text box
	 * ------------------------------------------------------------------ */

	function initVoucher() {
		var custom = $('[data-voucher-custom]');

		if (!custom) {
			return;
		}

		$$('[data-voucher-amount]').forEach(function (input) {
			on(input, 'change', function () {
				var isOther = input.value === 'other' && input.checked;

				custom.hidden = !isOther;

				if (isOther) {
					var field = $('input', custom);

					if (field) {
						field.focus();
					}
				}
			});
		});
	}

	function init() {
		initForms();
		initBooking();
		initVoucher();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

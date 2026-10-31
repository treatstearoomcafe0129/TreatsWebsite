/**
 * Treats loyalty card — customer card, join forms and the staff till.
 *
 * One file for both pages; each part only starts if its markup is present.
 * No dependencies beyond the bundled QR generator, and the QR reader, which is
 * only fetched on the till when the browser cannot read QR codes itself.
 */
(function () {
	'use strict';

	var cfg = window.treatsLoyalty || {};
	var i18n = cfg.i18n || {};

	function $(sel, root) {
		return (root || document).querySelector(sel);
	}

	function $$(sel, root) {
		return Array.prototype.slice.call((root || document).querySelectorAll(sel));
	}

	function post(action, data) {
		var body = new FormData();

		body.append('action', action);

		Object.keys(data || {}).forEach(function (key) {
			body.append(key, data[key]);
		});

		return fetch(cfg.ajaxUrl, { method: 'POST', body: body, credentials: 'same-origin' })
			.then(function (res) {
				return res.json().catch(function () {
					return { success: false, data: { message: i18n.generic } };
				});
			})
			.catch(function () {
				return { success: false, data: { message: i18n.offline } };
			});
	}

	function message(res) {
		return (res && res.data && res.data.message) || i18n.generic;
	}

	function esc(text) {
		var div = document.createElement('div');
		div.textContent = text == null ? '' : String(text);
		return div.innerHTML;
	}

	function toGo(next) {
		if (!next) {
			return '';
		}

		return (next.to_go === 1 ? (i18n.toGoOne || '1 more hot drink until your %2$s') : (i18n.toGo || '%1$d more hot drinks until your %2$s'))
			.replace('%1$d', next.to_go)
			.replace('%2$s', String(next.label).toLowerCase());
	}

	/* ------------------------------------------------------------------
	 * Shared: draw the 18 boxes
	 * --------------------------------------------------------------- */

	function renderGrid(list, boxes) {
		list.innerHTML = boxes.map(function (box) {
			var cls = 'loyalty-box loyalty-box--' + box.type + (box.filled ? ' is-filled' : '') + (box.kind ? ' loyalty-box--' + box.kind : '');
			return '<li class="' + cls + '"></li>';
		}).join('');
	}

	/* ------------------------------------------------------------------
	 * QR
	 * --------------------------------------------------------------- */

	function drawQr(el) {
		if (!el || typeof window.qrcode !== 'function') {
			return;
		}

		var qr = window.qrcode(0, 'M');
		qr.addData(el.getAttribute('data-code'));
		qr.make();
		el.innerHTML = qr.createSvgTag({ cellSize: 6, margin: 2, scalable: true });
	}

	/* ------------------------------------------------------------------
	 * Customer: join and resend forms
	 * --------------------------------------------------------------- */

	function initForms() {
		$$('[data-loyalty-form]').forEach(function (form) {
			var kind = form.getAttribute('data-loyalty-form');
			var box = $('[data-form-response]', form);
			var button = $('button[type="submit"]', form);

			form.addEventListener('submit', function (event) {
				event.preventDefault();

				$$('.field.has-error', form).forEach(function (f) {
					f.classList.remove('has-error');
				});

				var invalid = $$('input[required]', form).filter(function (input) {
					return input.type === 'checkbox' ? !input.checked : !input.value.trim() || !input.checkValidity();
				});

				if (invalid.length) {
					invalid.forEach(function (input) {
						var field = input.closest('.field');
						if (field) {
							field.classList.add('has-error');
							var err = $('.field__error', field);
							if (err) {
								var words = (window.treatsData && window.treatsData.i18n) || {};
								err.textContent = input.type === 'email'
									? (words.invalidEmail || 'Please enter a valid email address.')
									: (words.requiredField || 'Please complete this field.');
							}
						}
					});
					invalid[0].focus();
					return;
				}

				var data = {};
				new FormData(form).forEach(function (value, key) {
					data[key] = value;
				});

				button.disabled = true;
				var label = button.textContent;
				button.textContent = i18n.sending;

				post('treats_loyalty_' + kind, data).then(function (res) {
					button.disabled = false;
					button.textContent = label;

					if (res.success && res.data && res.data.redirect) {
						window.location.href = res.data.redirect;
						return;
					}

					box.hidden = false;
					box.className = 'form-response form-response--' + (res.success ? 'success' : 'error');
					box.textContent = message(res);
					box.setAttribute('role', 'status');

					if (res.success) {
						form.reset();
					}
				});
			});
		});
	}

	/* ------------------------------------------------------------------
	 * Customer: the card, kept fresh while it is on screen
	 * --------------------------------------------------------------- */

	function initCard() {
		var card = $('[data-loyalty-card]');

		if (!card) {
			return;
		}

		drawQr($('[data-loyalty-qr]', card));

		var token = card.getAttribute('data-token');
		var version = card.getAttribute('data-version');
		var timer = null;

		function apply(state) {
			if (state.version === version) {
				return;
			}

			var hadStamps = card.querySelectorAll('.loyalty-box.is-filled').length;

			version = state.version;
			renderGrid($('[data-loyalty-grid]', card), state.boxes);
			$('[data-loyalty-next]', card).textContent = toGo(state.next);

			var rewards = $('[data-loyalty-rewards]', card);
			rewards.hidden = !state.rewards.length;
			$('[data-loyalty-reward-list]', card).innerHTML = state.rewards.map(function (r) {
				return '<li class="loyalty-rewards__item loyalty-rewards__item--' + esc(r.kind) + '">' + esc(r.label) + '</li>';
			}).join('');

			if (card.querySelectorAll('.loyalty-box.is-filled').length !== hadStamps || state.rewards.length) {
				card.classList.remove('is-bumped');
				void card.offsetWidth;
				card.classList.add('is-bumped');
			}
		}

		function poll() {
			if (document.hidden) {
				return;
			}

			fetch(cfg.ajaxUrl + '?action=treats_loyalty_card&token=' + encodeURIComponent(token), { credentials: 'same-origin', cache: 'no-store' })
				.then(function (res) { return res.json(); })
				.then(function (res) {
					if (res && res.success) {
						apply(res.data);
					}
				})
				.catch(function () {});
		}

		// Quick while someone is likely standing at the counter, then easing off.
		var started = Date.now();

		function schedule() {
			var wait = Date.now() - started < 10 * 60 * 1000 ? 4000 : 20000;
			timer = window.setTimeout(function () {
				poll();
				schedule();
			}, wait);
		}

		schedule();

		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) {
				started = Date.now();
				poll();
			}
		});
	}

	/* ------------------------------------------------------------------
	 * Staff till
	 * --------------------------------------------------------------- */

	function initTill() {
		var till = $('[data-till]');

		if (!till) {
			return;
		}

		var views = {
			locked: $('[data-till-view="locked"]', till),
			find: $('[data-till-view="find"]', till),
			card: $('[data-till-view="card"]', till)
		};
		var current = null; // Open card state.
		var qty = 1;
		var lastLog = 0;
		var toastTimer = null;
		var max = parseInt(cfg.maxPerOrder, 10) || 6;

		function show(name) {
			Object.keys(views).forEach(function (key) {
				views[key].hidden = key !== name;
			});

			if (name !== 'find') {
				stopScan();
			}
		}

		function tillPost(action, data) {
			data = data || {};
			data.treats_nonce = cfg.tillNonce;

			return post('treats_till_' + action, data).then(function (res) {
				if (!res.success && res.data && res.data.locked) {
					lock(false);
				}

				if (!res.success && res.data && res.data.refresh) {
					window.location.reload();
				}

				return res;
			});
		}

		/* -- Unlock ----------------------------------------------------- */

		var pin = '';
		var dots = $('[data-pin-dots]', till);
		var pinMsg = $('[data-pin-msg]', till);

		function drawDots() {
			dots.innerHTML = pin.split('').map(function () { return '<span></span>'; }).join('');
		}

		function submitPin() {
			if (pin.length < 4) {
				return;
			}

			var code = pin;
			pin = '';
			drawDots();
			pinMsg.hidden = true;

			tillPost('login', { code: code }).then(function (res) {
				if (!res.success) {
					pinMsg.textContent = message(res);
					pinMsg.hidden = false;
					return;
				}

				$('[data-till-name]', till).textContent = res.data.name;
				$('[data-till-who]', till).hidden = false;
				show('find');
			});
		}

		$$('[data-pin-key]', till).forEach(function (key) {
			key.addEventListener('click', function () {
				if (pin.length < 8) {
					pin += key.getAttribute('data-pin-key');
					drawDots();
				}
			});
		});

		$('[data-pin-clear]', till).addEventListener('click', function () {
			pin = pin.slice(0, -1);
			drawDots();
		});

		$('[data-pin-go]', till).addEventListener('click', submitPin);

		document.addEventListener('keydown', function (event) {
			if (views.locked.hidden) {
				return;
			}

			if (/^\d$/.test(event.key) && pin.length < 8) {
				pin += event.key;
				drawDots();
			} else if (event.key === 'Backspace') {
				pin = pin.slice(0, -1);
				drawDots();
			} else if (event.key === 'Enter') {
				submitPin();
			}
		});

		function lock(callServer) {
			if (callServer !== false) {
				tillPost('logout');
			}

			current = null;
			$('[data-till-who]', till).hidden = true;
			show('locked');
		}

		$('[data-till-lock]', till).addEventListener('click', function () {
			lock(true);
		});

		/* -- Finding a card --------------------------------------------- */

		var findMsg = $('[data-find-msg]', till);
		var search = $('[data-till-search]', till);
		var queryInput = $('#till-query', till);

		function find(query) {
			findMsg.hidden = true;

			tillPost('lookup', { query: query }).then(function (res) {
				if (!res.success) {
					findMsg.textContent = message(res);
					findMsg.hidden = false;
					return;
				}

				queryInput.value = '';
				openCard(res.data.card);
			});
		}

		search.addEventListener('submit', function (event) {
			event.preventDefault();

			if (queryInput.value.trim()) {
				find(queryInput.value.trim());
			}
		});

		/* -- Camera ----------------------------------------------------- */

		var video = $('[data-scan-video]', till);
		var startBtn = $('[data-scan-start]', till);
		var stopBtn = $('[data-scan-stop]', till);
		var stream = null;
		var scanning = false;
		var detector = null;
		var canvas = document.createElement('canvas');
		var ctx = canvas.getContext('2d', { willReadFrequently: true });

		function loadJsQr() {
			if (window.jsQR) {
				return Promise.resolve();
			}

			return new Promise(function (resolve, reject) {
				var s = document.createElement('script');
				s.src = cfg.jsqrUrl;
				s.onload = resolve;
				s.onerror = reject;
				document.head.appendChild(s);
			});
		}

		function readFrame() {
			if (!scanning) {
				return;
			}

			if (video.readyState < 2) {
				window.requestAnimationFrame(readFrame);
				return;
			}

			var found = function (text) {
				if (text && /^TREATS:/i.test(text)) {
					stopScan();
					if (navigator.vibrate) {
						navigator.vibrate(60);
					}
					find(text);
					return true;
				}
				return false;
			};

			if (detector) {
				detector.detect(video).then(function (codes) {
					if (!codes.some(function (c) { return found(c.rawValue); })) {
						window.setTimeout(readFrame, 120);
					}
				}).catch(function () {
					window.setTimeout(readFrame, 250);
				});
				return;
			}

			var w = video.videoWidth;
			var h = video.videoHeight;
			var scale = Math.min(1, 640 / Math.max(w, h));

			canvas.width = Math.round(w * scale);
			canvas.height = Math.round(h * scale);
			ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

			var img = ctx.getImageData(0, 0, canvas.width, canvas.height);
			var result = window.jsQR(img.data, img.width, img.height, { inversionAttempts: 'dontInvert' });

			if (!(result && found(result.data))) {
				window.setTimeout(readFrame, 120);
			}
		}

		function startScan() {
			findMsg.hidden = true;

			if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
				findMsg.textContent = i18n.cameraNo;
				findMsg.hidden = false;
				return;
			}

			var ready = ('BarcodeDetector' in window)
				? window.BarcodeDetector.getSupportedFormats().then(function (formats) {
					if (formats.indexOf('qr_code') !== -1) {
						detector = new window.BarcodeDetector({ formats: ['qr_code'] });
						return;
					}
					return loadJsQr();
				})
				: loadJsQr();

			ready
				.then(function () {
					return navigator.mediaDevices.getUserMedia({ video: { facingMode: 'environment' }, audio: false });
				})
				.then(function (s) {
					stream = s;
					video.srcObject = s;
					video.hidden = false;
					startBtn.hidden = true;
					stopBtn.hidden = false;
					till.classList.add('is-scanning');
					return video.play();
				})
				.then(function () {
					scanning = true;
					readFrame();
				})
				.catch(function () {
					stopScan();
					findMsg.textContent = i18n.cameraNo;
					findMsg.hidden = false;
				});
		}

		function stopScan() {
			scanning = false;

			if (stream) {
				stream.getTracks().forEach(function (t) { t.stop(); });
				stream = null;
			}

			video.hidden = true;
			startBtn.hidden = false;
			stopBtn.hidden = true;
			till.classList.remove('is-scanning');
		}

		startBtn.addEventListener('click', startScan);
		stopBtn.addEventListener('click', stopScan);

		/* -- An open card ----------------------------------------------- */

		var qtyOut = $('[data-qty]', till);
		var stampBtn = $('[data-stamp]', till);
		var toast = $('[data-toast]', till);
		var undoBtn = $('[data-undo]', till);

		function setQty(n) {
			qty = Math.max(1, Math.min(max, n));
			qtyOut.textContent = qty;
			stampBtn.textContent = qty === 1 ? 'Add 1 stamp' : 'Add ' + qty + ' stamps';
		}

		function say(text, kind, undoable) {
			window.clearTimeout(toastTimer);
			toast.hidden = false;
			toast.className = 'till-toast till-toast--' + (kind || 'ok');
			$('[data-toast-text]', toast).textContent = text;
			undoBtn.hidden = !undoable;
			toastTimer = window.setTimeout(function () {
				toast.hidden = true;
			}, undoable ? 20000 : 6000);
		}

		function drawCard(state) {
			current = state;
			$('[data-card-name]', till).textContent = state.name;
			$('[data-card-code]', till).textContent = state.code;
			$('[data-card-stamps]', till).textContent = state.stamps;
			$('[data-card-next]', till).textContent = toGo(state.next);
			renderGrid($('[data-card-grid]', till), state.boxes);

			var wrap = $('[data-card-rewards]', till);
			var list = $('[data-card-reward-list]', till);

			wrap.hidden = !state.rewards.length;
			list.innerHTML = state.rewards.map(function (r) {
				return '<button type="button" class="till-btn till-btn--reward till-btn--' + esc(r.kind) + '" data-redeem="' + esc(r.id) + '">' +
					'Give ' + esc(String(r.label).toLowerCase()) + '</button>';
			}).join('');
		}

		function openCard(state) {
			toast.hidden = true;
			lastLog = 0;
			setQty(1);
			drawCard(state);
			show('card');
		}

		$('[data-qty-minus]', till).addEventListener('click', function () { setQty(qty - 1); });
		$('[data-qty-plus]', till).addEventListener('click', function () { setQty(qty + 1); });

		stampBtn.addEventListener('click', function () {
			if (!current || stampBtn.disabled) {
				return;
			}

			stampBtn.disabled = true;

			tillPost('stamp', { code: current.code, qty: qty }).then(function (res) {
				stampBtn.disabled = false;

				if (!res.success) {
					say(message(res), 'error');
					return;
				}

				drawCard(res.data.card);
				lastLog = res.data.logId;

				var text = (i18n.stamped || 'Stamped') + ' ✓ ' + (qty === 1 ? '1 stamp' : qty + ' stamps');

				if (res.data.earned && res.data.earned.length) {
					text += ' — ' + (i18n.earned || 'Earned') + ': ' + res.data.earned.join(', ').toLowerCase();
				}

				say(text, res.data.earned && res.data.earned.length ? 'reward' : 'ok', true);
				setQty(1);
			});
		});

		undoBtn.addEventListener('click', function () {
			if (!lastLog) {
				return;
			}

			tillPost('undo', { log: lastLog }).then(function (res) {
				if (!res.success) {
					say(message(res), 'error');
					return;
				}

				lastLog = 0;
				drawCard(res.data.card);
				say(i18n.undone || 'Undone', 'ok');
			});
		});

		till.addEventListener('click', function (event) {
			var btn = event.target.closest('[data-redeem]');

			if (!btn || !current) {
				return;
			}

			btn.disabled = true;

			tillPost('redeem', { code: current.code, reward: btn.getAttribute('data-redeem') }).then(function (res) {
				if (res.data && res.data.card) {
					drawCard(res.data.card);
				}

				if (!res.success) {
					say(message(res), 'error');
					return;
				}

				lastLog = 0;
				say((i18n.redeemed || 'Given') + ' ✓ ' + String(res.data.label).toLowerCase(), 'reward');
			});
		});

		$('[data-next]', till).addEventListener('click', function () {
			current = null;
			toast.hidden = true;
			show('find');
		});

		// Locked or not, the page loads in the right state from the server.
		drawDots();
		setQty(1);
	}

	function boot() {
		initForms();
		initCard();
		initTill();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
})();

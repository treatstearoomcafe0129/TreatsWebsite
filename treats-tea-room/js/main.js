/**
 * Treats Tea Room — core behaviour.
 *
 * No dependencies. Every module is optional: if its markup is not on the page
 * it simply does not initialise.
 */
(function () {
	'use strict';

	var data = window.treatsData || {};
	var i18n = data.i18n || {};

	/* ---------------------------------------------------------------------
	 * Helpers
	 * ------------------------------------------------------------------ */

	function $(selector, scope) {
		return (scope || document).querySelector(selector);
	}

	function $$(selector, scope) {
		return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
	}

	function on(el, type, handler, options) {
		if (el) {
			el.addEventListener(type, handler, options || false);
		}
	}

	function prefersReducedMotion() {
		return window.matchMedia('(prefers-reduced-motion: reduce)').matches || data.reducedMotion === true;
	}

	/** Keep focus inside a container while it is open. */
	function trapFocus(container, event) {
		var focusable = $$(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
			container
		).filter(function (el) {
			return el.offsetParent !== null;
		});

		if (!focusable.length) {
			return;
		}

		var first = focusable[0];
		var last = focusable[focusable.length - 1];

		if (event.shiftKey && document.activeElement === first) {
			event.preventDefault();
			last.focus();
		} else if (!event.shiftKey && document.activeElement === last) {
			event.preventDefault();
			first.focus();
		}
	}

	/* ---------------------------------------------------------------------
	 * Progressive enhancement flag
	 * ------------------------------------------------------------------ */

	document.documentElement.classList.remove('no-js');
	document.documentElement.classList.add('js');

	/* ---------------------------------------------------------------------
	 * Colour scheme
	 *
	 * The inline script in the header has already applied the stored choice to
	 * avoid a flash; this only wires up the toggle.
	 * ------------------------------------------------------------------ */

	function initThemeToggle() {
		var toggle = $('.theme-toggle');

		if (!toggle) {
			return;
		}

		function current() {
			var explicit = document.documentElement.getAttribute('data-theme');

			if (explicit) {
				return explicit;
			}

			return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
		}

		function label() {
			var next = current() === 'dark' ? i18n.themeLight : i18n.themeDark;
			toggle.setAttribute('aria-label', next || '');
			toggle.setAttribute('title', next || '');
		}

		label();

		on(toggle, 'click', function () {
			var next = current() === 'dark' ? 'light' : 'dark';

			document.documentElement.setAttribute('data-theme', next);

			try {
				window.localStorage.setItem('treats-theme', next);
			} catch (e) {
				/* Storage blocked — the choice simply will not persist. */
			}

			label();
		});
	}

	/* ---------------------------------------------------------------------
	 * Header: shrink on scroll, hide on scroll down
	 * ------------------------------------------------------------------ */

	function initHeader() {
		var header = $('.site-header');

		if (!header) {
			return;
		}

		var lastY = window.scrollY;
		var ticking = false;
		var overHero = header.classList.contains('site-header--over');
		var heroThreshold = 80;

		function update() {
			var y = window.scrollY;

			if (y > heroThreshold) {
				header.classList.add('is-stuck');
				header.classList.remove('site-header--over');
			} else {
				header.classList.remove('is-stuck');

				if (overHero) {
					header.classList.add('site-header--over');
				}
			}

			// Hide when scrolling down past the fold, show again on the way up.
			var goingDown = y > lastY && y > 320;
			var menuOpen = document.body.classList.contains('is-locked');

			if (goingDown && !menuOpen) {
				header.classList.add('is-hidden');
			} else {
				header.classList.remove('is-hidden');
			}

			// Sticky sub-bars (the menu filter row) follow the header, and rise
			// to the top edge when it hides.
			document.documentElement.style.setProperty(
				'--t-sticky-top',
				header.classList.contains('is-hidden') ? '0px' : header.offsetHeight + 'px'
			);

			lastY = y;
			ticking = false;
		}

		on(
			window,
			'scroll',
			function () {
				if (!ticking) {
					window.requestAnimationFrame(update);
					ticking = true;
				}
			},
			{ passive: true }
		);

		update();
	}

	/* ---------------------------------------------------------------------
	 * Mobile navigation
	 * ------------------------------------------------------------------ */

	function initMobileNav() {
		var toggle = $('.nav-toggle');
		var drawer = $('.mobile-nav');

		if (!toggle || !drawer) {
			return;
		}

		var lastFocused = null;

		function open() {
			lastFocused = document.activeElement;
			drawer.classList.add('is-open');
			drawer.removeAttribute('inert');
			toggle.setAttribute('aria-expanded', 'true');
			toggle.setAttribute('aria-label', i18n.menuClose || 'Close menu');
			document.body.classList.add('is-locked');

			var header = $('.site-header');

			if (header) {
				header.classList.add('is-nav-open');
			}

			var firstLink = $('a, button', drawer);

			if (firstLink) {
				window.setTimeout(function () {
					firstLink.focus();
				}, 120);
			}
		}

		function close() {
			drawer.classList.remove('is-open');
			drawer.setAttribute('inert', '');
			toggle.setAttribute('aria-expanded', 'false');
			toggle.setAttribute('aria-label', i18n.menuOpen || 'Open menu');
			document.body.classList.remove('is-locked');

			var header = $('.site-header');

			if (header) {
				header.classList.remove('is-nav-open');
			}

			if (lastFocused && typeof lastFocused.focus === 'function') {
				lastFocused.focus();
			}
		}

		drawer.setAttribute('inert', '');

		on(toggle, 'click', function () {
			if (drawer.classList.contains('is-open')) {
				close();
			} else {
				open();
			}
		});

		on(document, 'keydown', function (event) {
			if (!drawer.classList.contains('is-open')) {
				return;
			}

			if (event.key === 'Escape') {
				close();
			} else if (event.key === 'Tab') {
				trapFocus(drawer, event);
			}
		});

		// Close when a link is followed.
		$$('a', drawer).forEach(function (link) {
			on(link, 'click', function () {
				if (link.getAttribute('href') && link.getAttribute('href').charAt(0) !== '#') {
					close();
				} else {
					close();
				}
			});
		});

		// Reset when growing past the breakpoint.
		var desktop = window.matchMedia('(min-width: 1080px)');
		var handleChange = function (event) {
			if (event.matches && drawer.classList.contains('is-open')) {
				close();
			}
		};

		if (typeof desktop.addEventListener === 'function') {
			desktop.addEventListener('change', handleChange);
		} else if (typeof desktop.addListener === 'function') {
			desktop.addListener(handleChange);
		}
	}

	/* ---------------------------------------------------------------------
	 * Submenus (shared by the desktop dropdowns and the mobile drawer)
	 * ------------------------------------------------------------------ */

	function initSubmenus() {
		$$('.nav__toggle').forEach(function (button) {
			var item = button.closest('.has-dropdown');

			if (!item) {
				return;
			}

			var panel = $('.sub-menu', item);

			if (panel && button.getAttribute('aria-controls')) {
				panel.id = button.getAttribute('aria-controls');
			}

			var inDrawer = !!button.closest('.mobile-nav');

			on(button, 'click', function (event) {
				event.preventDefault();

				var isOpen = item.classList.toggle('is-open');
				button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

				if (inDrawer && panel) {
					panel.style.maxHeight = isOpen ? panel.scrollHeight + 'px' : '0px';
				}
			});

			// Keep an open panel the right height if the viewport changes.
			if (inDrawer && panel) {
				on(window, 'resize', function () {
					if (item.classList.contains('is-open')) {
						panel.style.maxHeight = panel.scrollHeight + 'px';
					}
				});
			}
		});

		// Close desktop dropdowns on Escape or an outside click.
		on(document, 'keydown', function (event) {
			if (event.key !== 'Escape') {
				return;
			}

			$$('.primary-nav .has-dropdown.is-open').forEach(function (item) {
				item.classList.remove('is-open');

				var button = $('.nav__toggle', item);

				if (button) {
					button.setAttribute('aria-expanded', 'false');
				}
			});
		});

		on(document, 'click', function (event) {
			$$('.primary-nav .has-dropdown.is-open').forEach(function (item) {
				if (!item.contains(event.target)) {
					item.classList.remove('is-open');

					var button = $('.nav__toggle', item);

					if (button) {
						button.setAttribute('aria-expanded', 'false');
					}
				}
			});
		});
	}

	/* ---------------------------------------------------------------------
	 * Reveal on scroll
	 * ------------------------------------------------------------------ */

	function initReveal() {
		var items = $$('[data-reveal]');

		if (!items.length) {
			return;
		}

		function show(item) {
			item.classList.add('is-visible');
		}

		if (prefersReducedMotion() || !('IntersectionObserver' in window)) {
			items.forEach(show);

			return;
		}

		var pending = [];

		items.forEach(function (item) {
			// The stagger is carried as data so it cannot collide with an
			// existing style attribute on the element.
			var delay = item.getAttribute('data-reveal-delay');

			if (delay) {
				item.style.setProperty('--reveal-delay', delay + 'ms');
			}

			// Anything already in view on load appears without animating.
			var rect = item.getBoundingClientRect();

			if (rect.top < window.innerHeight * 0.9 && rect.bottom > 0) {
				show(item);

				return;
			}

			pending.push(item);
		});

		if (!pending.length) {
			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					// `isIntersecting` alone misses anything the viewport jumped
					// straight past — a flick scroll or an in-page anchor — which
					// would otherwise leave that content invisible for good.
					if (entry.isIntersecting || entry.boundingClientRect.top < 0) {
						reveal(entry.target);
					}
				});
			},
			{
				rootMargin: '0px 0px -12% 0px',
				threshold: 0.08
			}
		);

		function reveal(item) {
			show(item);
			observer.unobserve(item);

			var index = pending.indexOf(item);

			if (index !== -1) {
				pending.splice(index, 1);
			}
		}

		pending.forEach(function (item) {
			observer.observe(item);
		});

		// Safety net: the observer only reports a *change* of state, so an
		// element scrolled past between two deliveries can be missed entirely.
		// This sweep costs nothing once everything has been revealed.
		var ticking = false;

		function sweep() {
			ticking = false;

			if (!pending.length) {
				window.removeEventListener('scroll', onScroll);

				return;
			}

			pending.slice().forEach(function (item) {
				if (item.getBoundingClientRect().top < window.innerHeight * 0.95) {
					reveal(item);
				}
			});
		}

		function onScroll() {
			if (!ticking) {
				window.requestAnimationFrame(sweep);
				ticking = true;
			}
		}

		on(window, 'scroll', onScroll, { passive: true });
		on(window, 'resize', onScroll, { passive: true });
	}

	/* ---------------------------------------------------------------------
	 * Accordions (FAQ and anywhere else)
	 * ------------------------------------------------------------------ */

	function initAccordions() {
		$$('.accordion').forEach(function (accordion) {
			var single = accordion.dataset.single === 'true';

			$$('.accordion__trigger', accordion).forEach(function (trigger) {
				var item = trigger.closest('.accordion__item');
				var panel = $('.accordion__panel', item);

				if (!panel) {
					return;
				}

				on(trigger, 'click', function () {
					var isOpen = item.classList.contains('is-open');

					if (single && !isOpen) {
						$$('.accordion__item.is-open', accordion).forEach(function (other) {
							other.classList.remove('is-open');

							var otherTrigger = $('.accordion__trigger', other);

							if (otherTrigger) {
								otherTrigger.setAttribute('aria-expanded', 'false');
							}
						});
					}

					item.classList.toggle('is-open', !isOpen);
					trigger.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
				});
			});
		});
	}

	/* ---------------------------------------------------------------------
	 * FAQ topic filter
	 * ------------------------------------------------------------------ */

	function initFilterTabs() {
		$$('[data-filter-group]').forEach(function (group) {
			var targetSelector = group.dataset.filterTarget;
			var targets = $$(targetSelector);

			$$('.tab', group).forEach(function (tab) {
				on(tab, 'click', function () {
					var value = tab.dataset.filter || '';

					$$('.tab', group).forEach(function (other) {
						other.classList.toggle('is-active', other === tab);
						other.setAttribute('aria-pressed', other === tab ? 'true' : 'false');
					});

					targets.forEach(function (target) {
						var topics = (target.dataset.topics || '').split(' ');
						var show = value === '' || topics.indexOf(value) !== -1;

						target.hidden = !show;
					});
				});
			});
		});
	}

	/* ---------------------------------------------------------------------
	 * Reviews carousel (a scroll-snap track with buttons)
	 * ------------------------------------------------------------------ */

	function initReviews() {
		var track = $('.reviews__track');

		if (!track) {
			return;
		}

		function step(direction) {
			var card = $('.review', track);
			var amount = card ? card.offsetWidth + 24 : track.clientWidth * 0.8;

			track.scrollBy({
				left: amount * direction,
				behavior: prefersReducedMotion() ? 'auto' : 'smooth'
			});
		}

		on($('.reviews__btn--prev'), 'click', function () {
			step(-1);
		});

		on($('.reviews__btn--next'), 'click', function () {
			step(1);
		});
	}

	/* ---------------------------------------------------------------------
	 * Gallery lightbox
	 * ------------------------------------------------------------------ */

	function initLightbox() {
		var triggers = $$('[data-lightbox]');

		if (!triggers.length) {
			return;
		}

		var lightbox = document.createElement('div');
		var index = 0;
		var lastFocused = null;

		lightbox.className = 'lightbox';
		lightbox.setAttribute('role', 'dialog');
		lightbox.setAttribute('aria-modal', 'true');
		lightbox.setAttribute('aria-label', 'Image viewer');
		lightbox.innerHTML =
			'<button class="lightbox__close" type="button" aria-label="Close">' +
			'<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>' +
			'</button>' +
			'<button class="lightbox__nav lightbox__nav--prev" type="button" aria-label="Previous">' +
			'<svg class="icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>' +
			'</button>' +
			'<button class="lightbox__nav lightbox__nav--next" type="button" aria-label="Next">' +
			'<svg class="icon" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>' +
			'</button>' +
			'<figure style="margin:0;display:grid;justify-items:center">' +
			'<img class="lightbox__img" src="" alt="">' +
			'<figcaption class="lightbox__caption"></figcaption>' +
			'</figure>';

		document.body.appendChild(lightbox);

		var img = $('.lightbox__img', lightbox);
		var caption = $('.lightbox__caption', lightbox);

		function show(i) {
			index = (i + triggers.length) % triggers.length;

			var trigger = triggers[index];
			var full = trigger.dataset.lightbox || trigger.getAttribute('href') || '';
			var thumb = $('img', trigger);

			img.src = full;
			img.alt = thumb ? thumb.alt : '';
			caption.textContent = trigger.dataset.caption || (thumb ? thumb.alt : '');
			caption.hidden = !caption.textContent;
		}

		function open(i) {
			lastFocused = document.activeElement;
			show(i);
			lightbox.classList.add('is-open');
			document.body.classList.add('is-locked');
			$('.lightbox__close', lightbox).focus();
		}

		function close() {
			lightbox.classList.remove('is-open');
			document.body.classList.remove('is-locked');
			img.src = '';

			if (lastFocused && typeof lastFocused.focus === 'function') {
				lastFocused.focus();
			}
		}

		triggers.forEach(function (trigger, i) {
			on(trigger, 'click', function (event) {
				event.preventDefault();
				open(i);
			});
		});

		on($('.lightbox__close', lightbox), 'click', close);

		on($('.lightbox__nav--prev', lightbox), 'click', function () {
			show(index - 1);
		});

		on($('.lightbox__nav--next', lightbox), 'click', function () {
			show(index + 1);
		});

		on(lightbox, 'click', function (event) {
			if (event.target === lightbox) {
				close();
			}
		});

		on(document, 'keydown', function (event) {
			if (!lightbox.classList.contains('is-open')) {
				return;
			}

			if (event.key === 'Escape') {
				close();
			} else if (event.key === 'ArrowLeft') {
				show(index - 1);
			} else if (event.key === 'ArrowRight') {
				show(index + 1);
			} else if (event.key === 'Tab') {
				trapFocus(lightbox, event);
			}
		});
	}

	/* ---------------------------------------------------------------------
	 * Map — the iframe is only loaded once the visitor asks for it, which
	 * keeps a third-party request (and its cookies) off the initial load.
	 * ------------------------------------------------------------------ */

	function initMap() {
		$$('.map__placeholder').forEach(function (placeholder) {
			var wrapper = placeholder.closest('.map');

			if (!wrapper) {
				return;
			}

			function load() {
				var src = wrapper.dataset.mapSrc;

				if (!src) {
					return;
				}

				var frame = document.createElement('iframe');

				frame.src = src;
				frame.loading = 'lazy';
				frame.title = wrapper.dataset.mapTitle || 'Map';
				frame.referrerPolicy = 'no-referrer-when-downgrade';
				frame.setAttribute('allowfullscreen', '');

				wrapper.appendChild(frame);
				placeholder.remove();
			}

			on(placeholder, 'click', load);

			on(placeholder, 'keydown', function (event) {
				if (event.key === 'Enter' || event.key === ' ') {
					event.preventDefault();
					load();
				}
			});
		});
	}

	/* ---------------------------------------------------------------------
	 * Back to top
	 * ------------------------------------------------------------------ */

	function initToTop() {
		var button = $('.to-top');

		if (!button) {
			return;
		}

		var ticking = false;

		function update() {
			button.classList.toggle('is-visible', window.scrollY > 700);
			ticking = false;
		}

		on(
			window,
			'scroll',
			function () {
				if (!ticking) {
					window.requestAnimationFrame(update);
					ticking = true;
				}
			},
			{ passive: true }
		);

		on(button, 'click', function () {
			window.scrollTo({
				top: 0,
				behavior: prefersReducedMotion() ? 'auto' : 'smooth'
			});
		});

		update();
	}

	/* ---------------------------------------------------------------------
	 * Counters — the small stat numbers on the home page
	 * ------------------------------------------------------------------ */

	function initCounters() {
		var counters = $$('[data-count-to]');

		if (!counters.length || !('IntersectionObserver' in window)) {
			return;
		}

		if (prefersReducedMotion()) {
			counters.forEach(function (counter) {
				counter.textContent = counter.dataset.countTo;
			});

			return;
		}

		var observer = new IntersectionObserver(
			function (entries) {
				entries.forEach(function (entry) {
					if (!entry.isIntersecting) {
						return;
					}

					var el = entry.target;
					var target = parseFloat(el.dataset.countTo);
					var suffix = el.dataset.countSuffix || '';
					var start = null;
					var duration = 1400;

					function tick(now) {
						if (start === null) {
							start = now;
						}

						var progress = Math.min((now - start) / duration, 1);
						// Ease-out cubic.
						var eased = 1 - Math.pow(1 - progress, 3);

						el.textContent = Math.round(target * eased).toLocaleString() + suffix;

						if (progress < 1) {
							window.requestAnimationFrame(tick);
						}
					}

					window.requestAnimationFrame(tick);
					observer.unobserve(el);
				});
			},
			{ threshold: 0.4 }
		);

		counters.forEach(function (counter) {
			observer.observe(counter);
		});
	}

	/* ---------------------------------------------------------------------
	 * Shared toast, used by the menu and form modules
	 * ------------------------------------------------------------------ */

	var toastTimer = null;

	window.treatsToast = function (message) {
		var toast = $('.toast');

		if (!toast) {
			toast = document.createElement('div');
			toast.className = 'toast';
			toast.setAttribute('role', 'status');
			toast.setAttribute('aria-live', 'polite');
			document.body.appendChild(toast);
		}

		toast.textContent = message;
		toast.classList.add('is-visible');

		window.clearTimeout(toastTimer);

		toastTimer = window.setTimeout(function () {
			toast.classList.remove('is-visible');
		}, 3200);
	};

	/* ---------------------------------------------------------------------
	 * Boot
	 * ------------------------------------------------------------------ */

	function init() {
		initThemeToggle();
		initHeader();
		initMobileNav();
		initSubmenus();
		initReveal();
		initAccordions();
		initFilterTabs();
		initReviews();
		initLightbox();
		initMap();
		initToTop();
		initCounters();

		// Move focus to a form response so screen readers announce it.
		var response = $('#form-response');

		if (response && response.textContent.trim()) {
			response.focus();
		}
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

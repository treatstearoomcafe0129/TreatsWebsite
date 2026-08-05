/**
 * Interactive menu: filtering, live search and Click & Collect.
 *
 * The basket is held in localStorage only — no prices are trusted from the
 * browser. What the visitor submits is a request; the café confirms the
 * total when it calls back.
 */
(function () {
	'use strict';

	var data = window.treatsData || {};
	var menuData = window.treatsMenuData || {};
	var i18n = data.i18n || {};
	var STORAGE_KEY = 'treats-collect';

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

	/**
	 * "3 dishes" / "1 dish", using the strings WordPress localised for us.
	 *
	 * @param {number}  n      Count.
	 * @param {boolean} shown  Use the "… shown." wording.
	 * @return {string}
	 */
	function dishLabel(n, shown) {
		var forms = menuData.i18n || {};
		var key = shown ? (n === 1 ? 'dishShownOne' : 'dishShownMany') : (n === 1 ? 'dishOne' : 'dishMany');
		var template = forms[key] || (shown ? '%s dishes shown.' : '%s dishes');

		return template.replace('%s', n);
	}

	function normalise(value) {
		return (value || '')
			.toString()
			.toLowerCase()
			.normalize('NFD')
			.replace(/[\u0300-\u036f]/g, '');
	}

	/* ---------------------------------------------------------------------
	 * Filtering & search
	 * ------------------------------------------------------------------ */

	function initMenuFilter() {
		var root = $('[data-menu-root]');

		if (!root) {
			return;
		}

		var filters = $$('[data-menu-filter]');
		var search = $('[data-menu-search]');
		var searchWrap = search ? search.closest('.menu-search') : null;
		var clear = $('[data-menu-search-clear]');
		var empty = $('[data-menu-empty]');
		var counter = $('[data-menu-count]');
		var items = $$('.menu-entry', root);
		var sections = $$('.menu-section', root);
		var activeFilter = '';
		var query = '';

		function apply() {
			var visible = 0;
			var needle = normalise(query).trim();

			items.forEach(function (item) {
				var matchesFilter =
					activeFilter === '' ||
					(item.dataset.categories || '').split(' ').indexOf(activeFilter) !== -1 ||
					(item.dataset.diet || '').split(' ').indexOf(activeFilter) !== -1;

				var matchesQuery = needle === '' || normalise(item.dataset.search).indexOf(needle) !== -1;
				var show = matchesFilter && matchesQuery;

				item.hidden = !show;

				if (show) {
					visible += 1;
				}
			});

			// Hide a section heading when every item beneath it is filtered out.
			sections.forEach(function (section) {
				var shown = $$('.menu-entry', section).filter(function (item) {
					return !item.hidden;
				});

				section.hidden = shown.length === 0;

				var count = $('[data-section-count]', section);

				if (count) {
					count.textContent = dishLabel(shown.length);
				}
			});

			if (empty) {
				empty.hidden = visible !== 0;
			}

			if (counter) {
				counter.textContent = dishLabel(visible, true);
			}
		}

		filters.forEach(function (button) {
			on(button, 'click', function () {
				activeFilter = button.dataset.menuFilter || '';

				filters.forEach(function (other) {
					var isActive = other === button;

					other.classList.toggle('is-active', isActive);
					other.setAttribute('aria-pressed', isActive ? 'true' : 'false');
				});

				apply();
			});
		});

		if (search) {
			var debounce = null;

			on(search, 'input', function () {
				query = search.value;

				if (searchWrap) {
					searchWrap.classList.toggle('has-value', query !== '');
				}

				window.clearTimeout(debounce);
				debounce = window.setTimeout(apply, 140);
			});

			on(search, 'keydown', function (event) {
				if (event.key === 'Escape') {
					search.value = '';
					query = '';

					if (searchWrap) {
						searchWrap.classList.remove('has-value');
					}

					apply();
				}
			});
		}

		on(clear, 'click', function () {
			if (search) {
				search.value = '';
				search.focus();
			}

			query = '';

			if (searchWrap) {
				searchWrap.classList.remove('has-value');
			}

			apply();
		});

		apply();
	}

	/* ---------------------------------------------------------------------
	 * Click & Collect basket
	 * ------------------------------------------------------------------ */

	function Basket() {
		this.lines = this.read();
	}

	Basket.prototype.read = function () {
		try {
			var stored = window.localStorage.getItem(STORAGE_KEY);

			return stored ? JSON.parse(stored) : [];
		} catch (e) {
			return [];
		}
	};

	Basket.prototype.save = function () {
		try {
			window.localStorage.setItem(STORAGE_KEY, JSON.stringify(this.lines));
		} catch (e) {
			/* Storage unavailable — the basket lives for this page view only. */
		}
	};

	Basket.prototype.find = function (id) {
		for (var i = 0; i < this.lines.length; i++) {
			if (this.lines[i].id === id) {
				return this.lines[i];
			}
		}

		return null;
	};

	Basket.prototype.add = function (item) {
		var line = this.find(item.id);

		if (line) {
			line.qty += 1;
		} else {
			this.lines.push({
				id: item.id,
				title: item.title,
				price: item.price,
				qty: 1
			});
		}

		this.save();
	};

	Basket.prototype.setQty = function (id, qty) {
		var line = this.find(id);

		if (!line) {
			return;
		}

		line.qty = qty;

		if (line.qty <= 0) {
			this.lines = this.lines.filter(function (other) {
				return other.id !== id;
			});
		}

		this.save();
	};

	Basket.prototype.count = function () {
		return this.lines.reduce(function (total, line) {
			return total + line.qty;
		}, 0);
	};

	Basket.prototype.total = function () {
		return this.lines.reduce(function (total, line) {
			return total + line.price * line.qty;
		}, 0);
	};

	Basket.prototype.summary = function () {
		return this.lines
			.map(function (line) {
				return line.qty + ' × ' + line.title;
			})
			.join('\n');
	};

	Basket.prototype.clear = function () {
		this.lines = [];
		this.save();
	};

	function initCollect() {
		if (menuData.collectEnabled === false) {
			return;
		}

		var drawer = $('.collect-drawer');
		var fab = $('.collect-fab');

		if (!drawer || !fab) {
			return;
		}

		var basket = new Basket();
		var list = $('[data-collect-list]');
		var emptyState = $('[data-collect-empty]');
		var totalEl = $('[data-collect-total]');
		var countEl = $('[data-collect-count]');
		var itemsField = $('[data-collect-items]');
		var totalField = $('[data-collect-total-field]');
		var currency = menuData.currency || '£';
		var lastFocused = null;

		function money(value) {
			return currency + value.toFixed(2);
		}

		function render() {
			var count = basket.count();

			fab.classList.toggle('is-visible', count > 0);

			if (countEl) {
				countEl.textContent = count;
			}

			if (emptyState) {
				emptyState.hidden = count > 0;
			}

			if (totalEl) {
				totalEl.textContent = money(basket.total());
			}

			if (itemsField) {
				itemsField.value = basket.summary();
			}

			if (totalField) {
				totalField.value = money(basket.total());
			}

			if (!list) {
				return;
			}

			list.innerHTML = '';

			basket.lines.forEach(function (line) {
				var li = document.createElement('li');

				li.className = 'collect-line';
				li.innerHTML =
					'<div class="collect-line__body">' +
					'<div class="collect-line__title"></div>' +
					'<div class="collect-line__price"></div>' +
					'</div>' +
					'<div class="qty">' +
					'<button class="qty__btn" type="button" data-qty="-1" aria-label="Reduce quantity">' +
					'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M5 12h14"/></svg>' +
					'</button>' +
					'<span class="qty__value"></span>' +
					'<button class="qty__btn" type="button" data-qty="1" aria-label="Increase quantity">' +
					'<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>' +
					'</button>' +
					'</div>';

				$('.collect-line__title', li).textContent = line.title;
				$('.collect-line__price', li).textContent = money(line.price) + ' each';
				$('.qty__value', li).textContent = line.qty;

				$$('[data-qty]', li).forEach(function (button) {
					on(button, 'click', function () {
						basket.setQty(line.id, line.qty + parseInt(button.dataset.qty, 10));
						render();
						syncButtons();
					});
				});

				list.appendChild(li);
			});
		}

		function syncButtons() {
			$$('[data-collect-add]').forEach(function (button) {
				var inBasket = basket.find(button.dataset.collectAdd);

				button.classList.toggle('is-added', !!inBasket);

				var label = $('[data-collect-label]', button);

				if (label) {
					label.textContent = inBasket ? inBasket.qty + ' added' : button.dataset.addLabel || 'Add';
				}
			});
		}

		function open() {
			lastFocused = document.activeElement;
			drawer.classList.add('is-open');
			document.body.classList.add('is-locked');

			var close = $('.collect-drawer__close', drawer);

			if (close) {
				close.focus();
			}
		}

		function close() {
			drawer.classList.remove('is-open');
			document.body.classList.remove('is-locked');

			if (lastFocused && typeof lastFocused.focus === 'function') {
				lastFocused.focus();
			}
		}

		$$('[data-collect-add]').forEach(function (button) {
			on(button, 'click', function () {
				basket.add({
					id: button.dataset.collectAdd,
					title: button.dataset.title || '',
					price: parseFloat(button.dataset.price) || 0
				});

				render();
				syncButtons();

				if (typeof window.treatsToast === 'function') {
					window.treatsToast(i18n.added || 'Added to your order');
				}
			});
		});

		on(fab, 'click', open);
		on($('.collect-drawer__close', drawer), 'click', close);
		on($('.collect-drawer__scrim', drawer), 'click', close);

		on($('[data-collect-clear]'), 'click', function () {
			basket.clear();
			render();
			syncButtons();
		});

		on(document, 'keydown', function (event) {
			if (event.key === 'Escape' && drawer.classList.contains('is-open')) {
				close();
			}
		});

		// Clear the basket once an order has been sent.
		document.addEventListener('treats:form-success', function (event) {
			if (event.detail && event.detail.action === 'order') {
				basket.clear();
				render();
				syncButtons();
				close();
			}
		});

		render();
		syncButtons();
	}

	function init() {
		initMenuFilter();
		initCollect();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();

/* BizReviews — front-end (vanilla JS, no jQuery) */
(function () {
	'use strict';
	if (typeof BizRevData === 'undefined') return;

	var D = BizRevData;

	function rawApi(path, opts) {
		opts = opts || {};
		opts.headers = Object.assign({
			'Content-Type': 'application/json',
			'X-BizRev-Nonce': D.nonce,
			'X-WP-Nonce': D.restNonce || D.nonce
		}, opts.headers || {});
		opts.credentials = 'same-origin';
		return fetch(D.rest + path, opts).then(function (r) {
			return r.json().then(function (body) {
				return { ok: r.ok, status: r.status, body: body };
			});
		});
	}

	function refreshNonce() {
		return fetch(D.rest + '/nonce', { credentials: 'same-origin' })
			.then(function (r) { return r.json(); })
			.then(function (j) {
				if (j && j.nonce) { D.nonce = j.nonce; D.restNonce = j.restNonce; }
			})
			.catch(function () {});
	}

	function api(path, opts) {
		return rawApi(path, opts).then(function (res) {
			// اگر نانس منقضی شده بود، یک‌بار تازه می‌کنیم و دوباره تلاش می‌کنیم
			if (res.status === 403 && !(opts && opts._retried)) {
				return refreshNonce().then(function () {
					var retryOpts = Object.assign({}, opts || {}, { _retried: true });
					return rawApi(path, retryOpts);
				});
			}
			return res;
		});
	}

	/* ---------- انتخاب ستاره ---------- */
	function initStarInputs(scope) {
		scope.querySelectorAll('.bizrev-star-input').forEach(function (group) {
			var picks = group.querySelectorAll('.bizrev-star-pick');
			var input = group.querySelector('input[type=hidden]');

			function paint(val) {
				picks.forEach(function (p) {
					p.classList.toggle('on', parseInt(p.dataset.val, 10) <= val);
				});
			}
			picks.forEach(function (p) {
				p.addEventListener('mouseenter', function () {
					var v = parseInt(p.dataset.val, 10);
					picks.forEach(function (x) {
						x.classList.toggle('hot', parseInt(x.dataset.val, 10) <= v);
					});
				});
				p.addEventListener('mouseleave', function () {
					picks.forEach(function (x) { x.classList.remove('hot'); });
				});
				p.addEventListener('click', function () {
					input.value = p.dataset.val;
					paint(parseInt(p.dataset.val, 10));
				});
			});
		});
	}

	/* ---------- ارسال فرم ---------- */
	function initForm(form) {
		form.addEventListener('submit', function (e) {
			e.preventDefault();
			var msg = form.querySelector('.bizrev-form-msg');
			var btn = form.querySelector('.bizrev-submit');
			msg.className = 'bizrev-form-msg';
			msg.textContent = '';

			var payload = {
				post_id: parseInt(form.dataset.post, 10),
				content: val(form, 'content'),
				rating: parseFloat(val(form, 'rating')) || 0,
				recommended: form.querySelector('[name=recommended]') ? form.querySelector('[name=recommended]').checked : false,
				author_name: val(form, 'author_name'),
				author_email: val(form, 'author_email'),
				website_url: val(form, 'website_url'),
				pros: vals(form, 'pros[]'),
				cons: vals(form, 'cons[]'),
				criteria: {}
			};

			form.querySelectorAll('[name^="criteria["]').forEach(function (inp) {
				var m = inp.name.match(/criteria\[(.+?)\]/);
				if (m) payload.criteria[m[1]] = parseFloat(inp.value) || 0;
			});

			btn.disabled = true;
			btn.dataset.label = btn.textContent;
			btn.textContent = D.i18n.submitting;

			api('/submit', { method: 'POST', body: JSON.stringify(payload) })
				.then(function (res) {
					btn.disabled = false;
					btn.textContent = btn.dataset.label;
					if (res.ok) {
						msg.className = 'bizrev-form-msg ok';
						msg.textContent = res.body.message;
						form.reset();
						form.querySelectorAll('.bizrev-star-pick').forEach(function (x) { x.classList.remove('on'); });
					} else {
						msg.className = 'bizrev-form-msg err';
						msg.textContent = (res.body && res.body.message) || D.i18n.error;
					}
				})
				.catch(function () {
					btn.disabled = false;
					btn.textContent = btn.dataset.label;
					msg.className = 'bizrev-form-msg err';
					msg.textContent = D.i18n.error;
				});
		});
	}

	function val(form, name) {
		var el = form.querySelector('[name="' + name + '"]');
		return el ? el.value.trim() : '';
	}
	function vals(form, name) {
		return Array.prototype.map.call(form.querySelectorAll('[name="' + name + '"]'), function (e) {
			return e.value.trim();
		}).filter(Boolean);
	}

	/* ---------- رأی مفید بودن ---------- */
	function initVotes(scope) {
		scope.addEventListener('click', function (e) {
			var b = e.target.closest('.bizrev-vote');
			if (!b || b.classList.contains('voted') || !b.dataset.id) return;
			var sibs = b.parentNode.querySelectorAll('.bizrev-vote');
			api('/vote', {
				method: 'POST',
				body: JSON.stringify({ review_id: parseInt(b.dataset.id, 10), vote: parseInt(b.dataset.vote, 10) })
			}).then(function (res) {
				if (res.ok) {
					var bb = b.querySelector('b');
					if (bb) bb.textContent = b.dataset.vote === '1' ? res.body.yes : res.body.no;
					sibs.forEach(function (s) { s.classList.add('voted'); });
				} else {
					sibs.forEach(function (s) { s.classList.add('voted'); });
				}
			});
		});
	}

	/* ---------- مرتب‌سازی و بارگذاری بیشتر ---------- */
	function initListControls(wrap) {
		var list = wrap.querySelector('.bizrev-list');
		var sort = wrap.querySelector('.bizrev-sort');
		var more = wrap.querySelector('.bizrev-loadmore');
		var postId = list ? list.dataset.post : 0;
		var currentSort = sort ? sort.value : 'recent';

		function load(page, replace) {
			var url = '/list?post_id=' + postId + '&page=' + page + '&sort=' + currentSort;
			return api(url, { method: 'GET' }).then(function (res) {
				if (!res.ok) return;
				if (replace) list.innerHTML = res.body.html;
				else list.insertAdjacentHTML('beforeend', res.body.html);
				if (more) {
					more.dataset.page = res.body.page;
					more.style.display = res.body.has_more ? '' : 'none';
				}
			});
		}

		if (sort) {
			sort.addEventListener('change', function () {
				currentSort = sort.value;
				load(1, true);
			});
		}
		if (more) {
			more.addEventListener('click', function () {
				load(parseInt(more.dataset.page, 10) + 1, false);
			});
		}
	}

	/* ---------- پاسخ‌ها ---------- */
	function initReplies(wrap) {
		// باز/بسته کردن فرم پاسخ
		wrap.addEventListener('click', function (e) {
			var btn = e.target.closest('.bizrev-reply-btn');
			if (btn) {
				var item = btn.closest('.bizrev-item');
				var f = item.querySelector('.bizrev-reply-form');
				if (f) {
					f.hidden = !f.hidden;
					if (!f.hidden) {
						var ta = f.querySelector('textarea');
						if (ta) ta.focus();
					}
				}
				return;
			}
			var cancel = e.target.closest('.bizrev-reply-cancel');
			if (cancel) {
				var form = cancel.closest('.bizrev-reply-form');
				if (form) form.hidden = true;
			}
		});

		// ارسال فرم پاسخ (با delegation تا فرم‌های بارگذاری‌شده با AJAX هم کار کنند)
		wrap.addEventListener('submit', function (e) {
			var form = e.target.closest('.bizrev-reply-form');
			if (!form) return;
			e.preventDefault();
			var msg = form.querySelector('.bizrev-form-msg');
			var btn = form.querySelector('.bizrev-reply-send');
			msg.className = 'bizrev-form-msg';
			msg.textContent = '';

			var payload = {
				post_id: parseInt(form.dataset.post, 10),
				parent_id: parseInt(form.dataset.parent, 10),
				content: val(form, 'content'),
				author_name: val(form, 'author_name'),
				author_email: val(form, 'author_email'),
				website_url: val(form, 'website_url')
			};

			btn.disabled = true;
			var label = btn.textContent;
			btn.textContent = D.i18n.submitting;

			api('/submit', { method: 'POST', body: JSON.stringify(payload) })
				.then(function (res) {
					btn.disabled = false;
					btn.textContent = label;
					if (res.ok) {
						msg.className = 'bizrev-form-msg ok';
						msg.textContent = res.body.message;
						form.reset();
					} else {
						msg.className = 'bizrev-form-msg err';
						msg.textContent = (res.body && res.body.message) || D.i18n.error;
					}
				})
				.catch(function () {
					btn.disabled = false;
					btn.textContent = label;
					msg.className = 'bizrev-form-msg err';
					msg.textContent = D.i18n.error;
				});
		});
	}

	/* ---------- راه‌اندازی ---------- */
	document.querySelectorAll('.bizrev-wrap').forEach(function (wrap) {
		initStarInputs(wrap);
		var form = wrap.querySelector('.bizrev-form');
		if (form) initForm(form);
		initVotes(wrap);
		initListControls(wrap);
		initReplies(wrap);
	});
})();

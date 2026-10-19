/* BizReviews — ادمین: پیش‌نمایش زنده + تکرارگر معیار */
(function () {
	'use strict';

	function ready(fn) {
		if (document.readyState !== 'loading') fn();
		else document.addEventListener('DOMContentLoaded', fn);
	}

	ready(function () {
		/* ---------- تب‌بندی تنظیمات ---------- */
		var tabBtns = document.querySelectorAll('.bizrev-tab-btn');
		var tabPanels = document.querySelectorAll('.bizrev-tab-panel');
		if (tabBtns.length) {
			tabBtns.forEach(function (btn) {
				btn.addEventListener('click', function () {
					var target = btn.dataset.tab;
					tabBtns.forEach(function (b) { b.classList.remove('active'); });
					tabPanels.forEach(function (p) {
						p.classList.toggle('active', p.dataset.tabPanel === target);
					});
					btn.classList.add('active');
				});
			});
		}

		var preview = document.getElementById('bizrev-preview');

		/* رنگ‌ها (wp-color-picker اگر jQuery موجود باشد) */
		/* پیش‌نمایش زنده برای فیلدهای data-preview قدیمی (قالب) */
		document.querySelectorAll('[data-preview]').forEach(function (el) {
			el.addEventListener('input', function () {
				applyPreview(el.dataset.preview, el.value);
			});
			el.addEventListener('change', function () {
				applyPreview(el.dataset.preview, el.value);
			});
		});

		function applyPreview(key, value) {
			if (!preview) return;
			if (key === 'template') {
				preview.className = preview.className.replace(/bizrev-tpl-\S+/, 'bizrev-tpl-' + value);
			}
		}

		/* ---------- پیش‌نمایش زنده استایل پیشرفته ---------- */
		// نقشه: کلید استایل => [نام متغیر CSS, واحد]
		var styleMap = {
			accent:['accent',''], star:['star',''], text:['text',''], muted:['muted',''],
			bg:['bg',''], soft:['soft',''], border:['border',''],
			pros_color:['pros',''], cons_color:['cons',''], rec_color:['rec',''], verified_color:['verified',''],
			radius:['radius','px'],
			wrap_margin:['wrap-margin','px'], summary_pad:['summary-pad','px'], summary_gap:['summary-gap','px'],
			form_pad:['form-pad','px'], form_gap:['form-gap','px'], field_gap:['field-gap','px'],
			rating_box_gap:['rating-box-gap','px'], criteria_gap:['criteria-gap','px'],
			item_pad:['item-pad','px'], item_gap:['item-gap','px'],
			avatar_size:['avatar-size','px'], star_size:['star-size','px'], star_input_size:['star-input-size','px'],
			font_size:['font-size','px'], line_height:['line-height','']
		};

		function applyStyle(key, value) {
			if (!preview || !styleMap[key]) return;
			var cssVar = '--bizrev-' + styleMap[key][0];
			var unit = styleMap[key][1];
			preview.style.setProperty(cssVar, value + (unit === 'px' ? 'px' : ''));
		}

		// رنج‌ها (px)
		document.querySelectorAll('.bizrev-style-px').forEach(function (el) {
			el.addEventListener('input', function () {
				applyStyle(el.dataset.style, el.value);
				var lbl = el.parentNode.querySelector('.bizrev-px-val');
				if (lbl) lbl.textContent = el.value + 'px';
			});
		});
		// عددها
		document.querySelectorAll('.bizrev-style-num').forEach(function (el) {
			el.addEventListener('input', function () { applyStyle(el.dataset.style, el.value); });
		});
		// رنگ‌ها (با color picker)
		if (window.jQuery && jQuery.fn.wpColorPicker) {
			jQuery('.bizrev-style-color').each(function () {
				var input = this;
				var key = input.dataset.style;
				jQuery(input).wpColorPicker({
					change: function (e, ui) {
						// با تأخیر کوتاه تا مقدار input به‌روز شود
						setTimeout(function () {
							var v = ui && ui.color ? ui.color.toString() : input.value;
							applyStyle(key, v);
						}, 10);
					},
					clear: function () {
						setTimeout(function () { applyStyle(key, input.value); }, 10);
					}
				});
			});
		} else {
			// fallback: اگر color picker در دسترس نبود، فیلد متنی ساده با پیش‌نمایش
			document.querySelectorAll('.bizrev-style-color').forEach(function (el) {
				el.addEventListener('input', function () { applyStyle(el.dataset.style, el.value); });
			});
		}

		/* تکرارگر معیارها */
		var addBtn = document.getElementById('bizrev-add-crit');
		var rows = document.getElementById('bizrev-criteria-rows');
		if (addBtn && rows) {
			addBtn.addEventListener('click', function () {
				var i = rows.children.length;
				var sample = rows.querySelector('input[type=text]');
				var base = sample ? sample.name.replace(/\[criteria\]\[\d+\]\[label\]/, '') : '';
				var div = document.createElement('div');
				div.className = 'bizrev-crit-edit';
				div.innerHTML =
					'<input type="text" name="' + base + '[criteria][' + i + '][label]" placeholder="عنوان معیار">' +
					'<input type="hidden" name="' + base + '[criteria][' + i + '][key]" value="c' + (Date.now() % 100000) + '">' +
					'<button type="button" class="button bizrev-crit-remove">حذف</button>';
				rows.appendChild(div);
			});
			rows.addEventListener('click', function (e) {
				if (e.target.classList.contains('bizrev-crit-remove')) {
					e.target.closest('.bizrev-crit-edit').remove();
				}
			});
		}

		/* ---------- فونت ---------- */
		var fontMode = document.getElementById('bizrev-font-mode');
		var fontRows = document.querySelectorAll('.bizrev-font-row');
		var gfontSelect = document.querySelector('.bizrev-gfont-select');
		var customFamily = document.getElementById('bizrev-font-family-custom');

		function toggleFontRows() {
			if (!fontMode) return;
			var mode = fontMode.value;
			fontRows.forEach(function (row) {
				var show = row.dataset.mode === mode;
				row.style.display = show ? '' : 'none';
				// فیلدهای مخفی را غیرفعال کن تا در ذخیره تداخل نکنند
				row.querySelectorAll('input,select').forEach(function (inp) {
					if (inp.name && inp.name.indexOf('[font_family]') !== -1) {
						inp.disabled = !show;
					}
				});
			});
			applyFontPreview();
		}

		function applyFontPreview() {
			if (!preview || !fontMode) return;
			var mode = fontMode.value;
			var family = 'inherit';
			if (mode === 'google' && gfontSelect && gfontSelect.value) {
				family = '"' + gfontSelect.value + '", Tahoma, sans-serif';
				loadGoogleFontPreview(gfontSelect.value);
			} else if (mode === 'custom' && customFamily && customFamily.value) {
				family = '"' + customFamily.value + '", Tahoma, sans-serif';
			}
			preview.style.setProperty('--bizrev-font', family);
		}

		var loadedGFonts = {};
		function loadGoogleFontPreview(name) {
			if (loadedGFonts[name]) return;
			loadedGFonts[name] = true;
			var link = document.createElement('link');
			link.rel = 'stylesheet';
			link.href = 'https://fonts.googleapis.com/css2?family=' + encodeURIComponent(name) + ':wght@400;500;700&display=swap';
			document.head.appendChild(link);
		}

		if (fontMode) {
			fontMode.addEventListener('change', toggleFontRows);
			toggleFontRows();
		}
		if (gfontSelect) gfontSelect.addEventListener('change', applyFontPreview);
		if (customFamily) customFamily.addEventListener('input', applyFontPreview);

		/* آپلودگر رسانه برای فایل فونت */
		var upBtn = document.getElementById('bizrev-font-upload');
		if (upBtn && window.wp && wp.media) {
			var frame;
			upBtn.addEventListener('click', function (e) {
				e.preventDefault();
				if (frame) { frame.open(); return; }
				frame = wp.media({
					title: 'انتخاب فایل فونت',
					button: { text: 'استفاده از این فونت' },
					multiple: false
				});
				frame.on('select', function () {
					var att = frame.state().get('selection').first().toJSON();
					var urlField = document.getElementById('bizrev-font-url');
					if (urlField) urlField.value = att.url;
					// اگر نام فونت خالی بود از نام فایل بساز
					if (customFamily && !customFamily.value) {
						var base = (att.title || att.filename || 'CustomFont').replace(/\.(woff2|woff|ttf|otf)$/i, '');
						customFamily.value = base;
						applyFontPreview();
					}
				});
				frame.open();
			});
		}
	});
})();

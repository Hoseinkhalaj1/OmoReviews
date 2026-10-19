<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $s */
$opt = BizRev_Settings::OPTION;
?>
<div class="wrap bizrev-admin bizrev-settings-wrap" dir="rtl">
	<h1>تنظیمات OmoReviews</h1>

	<div class="bizrev-settings-layout">
		<form method="post" action="options.php" class="bizrev-settings-form" id="bizrev-settings-form">
			<?php settings_fields( 'bizrev_group' ); ?>

			<div class="bizrev-tabs-nav">
				<button type="button" class="bizrev-tab-btn active" data-tab="general">عمومی</button>
				<button type="button" class="bizrev-tab-btn" data-tab="rating">امتیاز و فرم</button>
				<button type="button" class="bizrev-tab-btn" data-tab="texts">متن‌ها</button>
				<button type="button" class="bizrev-tab-btn" data-tab="style">استایل</button>
				<button type="button" class="bizrev-tab-btn" data-tab="advanced">پیشرفته</button>
			</div>

			<div class="bizrev-tab-panel active" data-tab-panel="general">
			<h2 class="bizrev-sec">ظاهر و قالب</h2>

			<table class="form-table">
				<tr>
					<th>قالب نمایش</th>
					<td>
						<select name="<?php echo esc_attr( $opt ); ?>[template]" data-preview="template">
							<?php foreach ( BizRev_Settings::templates() as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['template'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">رنگ‌ها، گردی گوشه و اندازه‌ها در بخش «استایل پیشرفته» پایین صفحه تنظیم می‌شوند.</p>
					</td>
				</tr>
				<tr>
					<th>راست‌چین (RTL)</th>
					<td><label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[rtl]" value="1" <?php checked( $s['rtl'], 1 ); ?>> فعال</label></td>
				</tr>
			</table>

			<h2 class="bizrev-sec">فونت</h2>
			<table class="form-table">
				<tr>
					<th>منبع فونت</th>
					<td>
						<select name="<?php echo esc_attr( $opt ); ?>[font_mode]" id="bizrev-font-mode">
							<option value="theme" <?php selected( $s['font_mode'], 'theme' ); ?>>ارث‌بری از قالب (پیش‌فرض)</option>
							<option value="google" <?php selected( $s['font_mode'], 'google' ); ?>>فونت گوگل (فارسی)</option>
							<option value="custom" <?php selected( $s['font_mode'], 'custom' ); ?>>آپلود فونت سفارشی</option>
						</select>
						<p class="description">«ارث‌بری از قالب» یعنی همان فونت سایت استفاده شود.</p>
					</td>
				</tr>

				<tr class="bizrev-font-row" data-mode="google">
					<th>انتخاب فونت گوگل</th>
					<td>
						<select name="<?php echo esc_attr( $opt ); ?>[font_family]" class="bizrev-gfont-select" data-preview="font">
							<option value="">— انتخاب کنید —</option>
							<?php foreach ( BizRev_Settings::google_fonts() as $key => $label ) : ?>
								<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $s['font_family'], $key ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
						<p class="description">فونت‌های فارسی محبوب از Google Fonts.</p>
					</td>
				</tr>

				<tr class="bizrev-font-row" data-mode="custom">
					<th>فایل فونت سفارشی</th>
					<td>
						<input type="text" id="bizrev-font-url" name="<?php echo esc_attr( $opt ); ?>[font_url]" value="<?php echo esc_attr( $s['font_url'] ); ?>" class="regular-text" placeholder="آدرس فایل woff2/woff/ttf" readonly>
						<button type="button" class="button" id="bizrev-font-upload">آپلود / انتخاب فونت</button>
						<p class="description">فایل را آپلود کنید؛ نام فونت به‌صورت خودکار از نام فایل ساخته می‌شود. می‌توانید نام دلخواه هم وارد کنید.</p>
						<label>نام فونت (family):
							<input type="text" name="<?php echo esc_attr( $opt ); ?>[font_family]" value="<?php echo esc_attr( $s['font_family'] ); ?>" id="bizrev-font-family-custom" placeholder="مثلاً IRANSans">
						</label>
					</td>
				</tr>
			</table>

			</div><!-- /general -->

			<div class="bizrev-tab-panel" data-tab-panel="rating">
			<h2 class="bizrev-sec">امتیازدهی</h2>
			<table class="form-table">
				<tr>
					<th>حالت امتیاز</th>
					<td>
						<label><input type="radio" name="<?php echo esc_attr( $opt ); ?>[rating_mode]" value="multi" <?php checked( $s['rating_mode'], 'multi' ); ?>> چندمعیاره</label>
						<label style="margin-right:16px"><input type="radio" name="<?php echo esc_attr( $opt ); ?>[rating_mode]" value="single" <?php checked( $s['rating_mode'], 'single' ); ?>> تک‌معیاره</label>
					</td>
				</tr>
				<tr>
					<th>حداکثر ستاره</th>
					<td><input type="number" min="3" max="10" name="<?php echo esc_attr( $opt ); ?>[max_stars]" value="<?php echo esc_attr( $s['max_stars'] ); ?>"></td>
				</tr>
				<tr>
					<th>معیارهای امتیازدهی</th>
					<td>
						<div id="bizrev-criteria-rows">
							<?php foreach ( $s['criteria'] as $i => $c ) : ?>
								<div class="bizrev-crit-edit">
									<input type="text" name="<?php echo esc_attr( $opt ); ?>[criteria][<?php echo $i; ?>][label]" value="<?php echo esc_attr( $c['label'] ); ?>" placeholder="عنوان معیار">
									<input type="hidden" name="<?php echo esc_attr( $opt ); ?>[criteria][<?php echo $i; ?>][key]" value="<?php echo esc_attr( $c['key'] ); ?>">
									<button type="button" class="button bizrev-crit-remove">حذف</button>
								</div>
							<?php endforeach; ?>
						</div>
						<button type="button" class="button" id="bizrev-add-crit">+ افزودن معیار</button>
						<p class="description">مثلاً: کیفیت، قیمت، برخورد پرسنل…</p>
					</td>
				</tr>
			</table>

			<h2 class="bizrev-sec">فرم و امکانات</h2>
			<table class="form-table">
				<?php
				$toggles = array(
					'enable_pros_cons'    => 'نقاط قوت و ضعف',
					'enable_recommend'    => 'گزینه «توصیه می‌کنم»',
					'enable_helpful'      => 'رأی مفید بودن نظر',
					'enable_replies'      => 'پاسخ مدیر/صاحب کسب‌وکار',
					'show_summary_bar'    => 'نوار خلاصه امتیاز',
					'show_verified_badge' => 'نشان مشتری تأییدشده',
					'require_login'       => 'الزام ورود برای ثبت نظر',
					'moderation'          => 'تأیید نظرات پیش از نمایش',
					'auto_approve_logged_in' => 'تأیید خودکار نظر کاربران وارد‌شده',
					'honeypot'            => 'محافظت ضد اسپم (هانی‌پات)',
				);
				foreach ( $toggles as $k => $label ) : ?>
					<tr>
						<th><?php echo esc_html( $label ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[<?php echo esc_attr( $k ); ?>]" value="1" <?php checked( $s[ $k ], 1 ); ?>> فعال</label></td>
					</tr>
				<?php endforeach; ?>
			</table>

			<h2 class="bizrev-sec">تأیید هویت خریدار</h2>
			<table class="form-table">
				<tr>
					<th>فعال‌سازی نشان «خریدار تأییدشده»</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[verified_enabled]" value="1" <?php checked( $s['verified_enabled'], 1 ); ?>> فعال</label>
						<p class="description">نمایش نشان کنار نظرِ کاربرانی که واقعاً از سرویس/محصول استفاده کرده‌اند.</p>
					</td>
				</tr>
				<tr>
					<th>تشخیص خودکار خرید (ووکامرس)</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[verified_woo]" value="1" <?php checked( $s['verified_woo'], 1 ); ?>> فعال</label>
						<p class="description">اگر ووکامرس نصب باشد، نظرِ کاربری که آن محصول را خریده به‌صورت خودکار «تأییدشده» علامت می‌خورد. روی سایت‌های بدون ووکامرس بی‌اثر است.</p>
					</td>
				</tr>
				<tr>
					<th>تأیید دستی</th>
					<td>
						<p class="description">در «مدیریت نظرات» می‌توانید هر نظر را دستی «تأیید خریدار» کنید — برای سایت‌های بدون ووکامرس یا مواردی که خرید خارج از سایت انجام شده. متن و رنگ نشان در تب «متن‌ها» و «استایل» قابل تغییر است.</p>
					</td>
				</tr>
			</table>

			<h2 class="bizrev-sec">نمایش</h2>
			<table class="form-table">
				<tr>
					<th>تعداد در هر صفحه</th>
					<td><input type="number" min="1" max="100" name="<?php echo esc_attr( $opt ); ?>[per_page]" value="<?php echo esc_attr( $s['per_page'] ); ?>"></td>
				</tr>
				<tr>
					<th>مرتب‌سازی پیش‌فرض</th>
					<td>
						<select name="<?php echo esc_attr( $opt ); ?>[default_sort]">
							<?php
							$sorts = array( 'recent' => 'جدیدترین', 'rating_high' => 'بیشترین امتیاز', 'rating_low' => 'کمترین امتیاز', 'helpful' => 'مفیدترین' );
							foreach ( $sorts as $k => $label ) : ?>
								<option value="<?php echo esc_attr( $k ); ?>" <?php selected( $s['default_sort'], $k ); ?>><?php echo esc_html( $label ); ?></option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
				<tr>
					<th>فاصله بین ارسال‌ها (ثانیه)</th>
					<td><input type="number" min="0" name="<?php echo esc_attr( $opt ); ?>[min_interval]" value="<?php echo esc_attr( $s['min_interval'] ); ?>"></td>
				</tr>
				<tr>
					<th>فعال برای نوع‌های محتوا</th>
					<td>
						<?php
						// همه نوع‌های محتوا (عمومی و سفارشی) به‌جز نوع‌های داخلی وردپرس
						$builtin_skip = array( 'attachment', 'revision', 'nav_menu_item', 'custom_css', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_block', 'wp_template', 'wp_template_part', 'wp_global_styles', 'wp_navigation' );
						$all_pts = get_post_types( array(), 'objects' );
						$enabled = (array) $s['enabled_post_types'];
						echo '<div class="bizrev-pt-grid">';
						foreach ( $all_pts as $pt ) :
							if ( in_array( $pt->name, $builtin_skip, true ) ) {
								continue;
							}
							$label = $pt->labels->singular_name ? $pt->labels->singular_name : $pt->name;
							?>
							<label class="bizrev-pt-item">
								<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[enabled_post_types][]" value="<?php echo esc_attr( $pt->name ); ?>" <?php checked( in_array( $pt->name, $enabled, true ) ); ?>>
								<span><?php echo esc_html( $label ); ?> <code><?php echo esc_html( $pt->name ); ?></code></span>
							</label>
						<?php endforeach; ?>
						</div>
						<p class="description">برای سایت دایرکتوری، نوع پست مشاغل خود را تیک بزنید. همه نوع‌های محتوای ثبت‌شده اینجا فهرست می‌شوند.</p>
				</td>
				</tr>
			</table>

			<h2 class="bizrev-sec">تنظیمات اختصاصی نوع محتوا</h2>
			<p class="description" style="margin-bottom:14px">می‌توانید برای هر نوع محتوا، بخش‌هایی را جداگانه مخفی کنید. مثلاً در مقالات، نقاط قوت/ضعف یا معیار «قیمت» را نمایش ندهید. اگر چیزی تیک نخورد، تنظیمات اصلی بالا اعمال می‌شود.</p>
			<?php
			$overrides = isset( $s['pt_overrides'] ) && is_array( $s['pt_overrides'] ) ? $s['pt_overrides'] : array();
			$enabled_pts = (array) $s['enabled_post_types'];
			$crit_list = ! empty( $s['criteria'] ) ? $s['criteria'] : array();
			if ( empty( $enabled_pts ) ) :
				echo '<p class="description">ابتدا حداقل یک نوع محتوا را در بخش «نمایش» فعال کنید.</p>';
			else :
				foreach ( $enabled_pts as $pt_name ) :
					$pt_obj = get_post_type_object( $pt_name );
					if ( ! $pt_obj ) { continue; }
					$pt_label = $pt_obj->labels->singular_name ? $pt_obj->labels->singular_name : $pt_name;
					$ov = isset( $overrides[ $pt_name ] ) ? $overrides[ $pt_name ] : array();
					$hide_pc = ! empty( $ov['hide_pros_cons'] );
					$hide_rec = ! empty( $ov['hide_recommend'] );
					$hidden_crit = ! empty( $ov['hidden_criteria'] ) ? (array) $ov['hidden_criteria'] : array();
					?>
					<div class="bizrev-pt-override">
						<div class="bizrev-pt-override-head"><?php echo esc_html( $pt_label ); ?> <code><?php echo esc_html( $pt_name ); ?></code></div>
						<div class="bizrev-pt-override-body">
							<label class="bizrev-pt-ov-opt">
								<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[pt_overrides][<?php echo esc_attr( $pt_name ); ?>][hide_pros_cons]" value="1" <?php checked( $hide_pc ); ?>>
								مخفی کردن نقاط قوت و ضعف
							</label>
							<label class="bizrev-pt-ov-opt">
								<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[pt_overrides][<?php echo esc_attr( $pt_name ); ?>][hide_recommend]" value="1" <?php checked( $hide_rec ); ?>>
								مخفی کردن گزینه «توصیه می‌کنم»
							</label>
							<?php if ( $crit_list ) : ?>
								<div class="bizrev-pt-ov-crit">
									<span class="bizrev-pt-ov-crit-label">مخفی کردن معیارها:</span>
									<?php foreach ( $crit_list as $c ) : ?>
										<label class="bizrev-pt-ov-opt">
											<input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[pt_overrides][<?php echo esc_attr( $pt_name ); ?>][hidden_criteria][]" value="<?php echo esc_attr( $c['key'] ); ?>" <?php checked( in_array( $c['key'], $hidden_crit, true ) ); ?>>
											<?php echo esc_html( $c['label'] ); ?>
										</label>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					</div>
				<?php endforeach;
			endif;
			?>

			<h2 class="bizrev-sec">ورود کاربران</h2>
			<table class="form-table">
				<tr>
					<th>لینک ورود سفارشی</th>
					<td>
						<input type="text" name="<?php echo esc_attr( $opt ); ?>[login_url]" value="<?php echo esc_attr( $s['login_url'] ); ?>" class="regular-text ltr" dir="ltr" placeholder="https://example.com/login?redirect={redirect}">
						<p class="description">
							اگر خالی بماند، صفحه ورود پیش‌فرض وردپرس استفاده می‌شود.<br>
							می‌توانید از <code>{redirect}</code> برای جای‌گذاری آدرس بازگشت (به همان صفحه) استفاده کنید؛ مثلاً:<br>
							<code>https://site.com/my-account/?redirect_to={redirect}</code><br>
							اگر <code>{redirect}</code> را قرار ندهید، آدرس بازگشت به‌صورت پارامتر <code>redirect_to</code> اضافه می‌شود.
						</p>
					</td>
				</tr>
			</table>

			</div><!-- /rating -->

			<div class="bizrev-tab-panel" data-tab-panel="texts">
			<h2 class="bizrev-sec">شخصی‌سازی متن‌ها</h2>
			<p class="description">همه نوشته‌ها و راهنماهای داخل فرم را می‌توانید تغییر دهید. خالی گذاشتن = استفاده از متن پیش‌فرض.</p>
			<?php
			$text_groups = array(
				'فرم نظر' => array(
					'form_title' => 'عنوان فرم',
					'rating_label' => 'برچسب امتیاز (تک‌معیاره)',
					'content_ph' => 'راهنمای متن نظر',
					'pros_label' => 'برچسب نقاط قوت',
					'cons_label' => 'برچسب نقاط ضعف',
					'pros_ph1' => 'راهنمای نقطه قوت ۱',
					'pros_ph2' => 'راهنمای نقطه قوت ۲',
					'cons_ph1' => 'راهنمای نقطه ضعف ۱',
					'cons_ph2' => 'راهنمای نقطه ضعف ۲',
					'recommend_label' => 'متن گزینه توصیه (در فرم)',
					'name_ph' => 'راهنمای نام',
					'email_ph' => 'راهنمای ایمیل',
					'submit_btn' => 'متن دکمه ارسال',
					'submitting' => 'متن حین ارسال',
				),
				'نمایش نظرات' => array(
					'recommend_badge' => 'متن نشان توصیه (روی نظر)',
					'verified_badge' => 'متن نشان تأیید',
					'verified_title' => 'توضیح نشان تأیید',
					'helpful_q' => 'سؤال مفید بودن',
					'helpful_yes' => 'دکمه بله',
					'helpful_no' => 'دکمه خیر',
					'reply_prefix' => 'پیشوند پاسخ مدیر',
					'count_suffix' => 'واحد شمارش نظر',
					'empty' => 'متن نبود نظر',
					'loadmore' => 'دکمه نمایش بیشتر',
				),
				'مرتب‌سازی' => array(
					'sort_recent' => 'جدیدترین',
					'sort_rating_high' => 'بیشترین امتیاز',
					'sort_rating_low' => 'کمترین امتیاز',
					'sort_helpful' => 'مفیدترین',
				),
				'ورود و پیام‌ها' => array(
					'login_required' => 'متن الزام ورود',
					'login_link_text' => 'متن لینک ورود',
					'msg_pending' => 'پیام ثبت (در انتظار تأیید)',
					'msg_approved' => 'پیام ثبت موفق',
				),
				'پیام‌های خطا' => array(
					'err_generic' => 'خطای عمومی',
					'err_session' => 'خطای نشست منقضی',
					'err_name' => 'خطای نام',
					'err_email' => 'خطای ایمیل',
					'err_short' => 'خطای متن کوتاه',
					'err_rating' => 'خطای نبود امتیاز',
					'err_login' => 'خطای الزام ورود',
					'err_duplicate' => 'خطای نظر تکراری',
					'err_throttle' => 'خطای ارسال سریع',
					'err_voted' => 'خطای رأی تکراری',
				),
			);
			foreach ( $text_groups as $group_label => $fields ) :
				?>
				<h3 class="bizrev-text-group-title"><?php echo esc_html( $group_label ); ?></h3>
				<table class="form-table bizrev-text-table">
					<?php foreach ( $fields as $key => $label ) : ?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<td><input type="text" class="regular-text" name="<?php echo esc_attr( $opt ); ?>[texts][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $s['texts'][ $key ] ?? '' ); ?>"></td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>

			</div><!-- /texts -->

			<div class="bizrev-tab-panel" data-tab-panel="style">
			<h2 class="bizrev-sec">استایل پیشرفته</h2>
			<p class="description">رنگ‌ها، فاصله‌ها و اندازه‌های هر بخش از فرم و نظرات را دقیق تنظیم کنید.</p>
			<?php
			$schema = BizRev_Settings::style_schema();
			$groups = BizRev_Settings::style_groups();
			foreach ( $groups as $gkey => $glabel ) :
				?>
				<h3 class="bizrev-text-group-title"><?php echo esc_html( $glabel ); ?></h3>
				<table class="form-table bizrev-style-table">
					<?php
					foreach ( $schema as $key => $info ) :
						list( $label, $type, $group ) = $info;
						if ( $group !== $gkey ) {
							continue;
						}
						$val = $s['style'][ $key ] ?? '';
						?>
						<tr>
							<th><?php echo esc_html( $label ); ?></th>
							<td>
								<?php if ( 'color' === $type ) : ?>
									<input type="text" class="bizrev-style-color" data-style="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $opt ); ?>[style][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $val ); ?>">
								<?php elseif ( 'num' === $type ) : ?>
									<input type="number" step="0.1" min="0" class="bizrev-style-num" data-style="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $opt ); ?>[style][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $val ); ?>">
								<?php else : ?>
									<input type="range" min="0" max="60" class="bizrev-style-px" data-style="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $opt ); ?>[style][<?php echo esc_attr( $key ); ?>]" value="<?php echo esc_attr( $val ); ?>">
									<span class="bizrev-px-val"><?php echo (int) $val; ?>px</span>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</table>
			<?php endforeach; ?>

			</div><!-- /style -->

			<div class="bizrev-tab-panel" data-tab-panel="advanced">
			<h2 class="bizrev-sec">سئو و یکپارچگی</h2>
			<table class="form-table">
				<tr>
					<th>داده ساختاریافته (Schema.org)</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[schema_enabled]" value="1" <?php checked( $s['schema_enabled'], 1 ); ?>> فعال</label>
						<p class="description">افزودن microdata و JSON-LD برای نمایش ستاره‌های امتیاز در نتایج گوگل (rich snippets). اگر قالب یا افزونه سئوی شما خودش Schema تولید می‌کند، برای جلوگیری از تداخل این را خاموش کنید.</p>
					</td>
				</tr>
				<tr>
					<th>نوع موجودیت Schema</th>
					<td>
						<select name="<?php echo esc_attr( $opt ); ?>[schema_type]">
							<option value="Product" <?php selected( $s['schema_type'], 'Product' ); ?>>محصول / آیتم (Product)</option>
							<option value="LocalBusiness" <?php selected( $s['schema_type'], 'LocalBusiness' ); ?>>کسب‌وکار محلی (LocalBusiness)</option>
							<option value="Service" <?php selected( $s['schema_type'], 'Service' ); ?>>خدمت (Service)</option>
						</select>
						<p class="description">برای دایرکتوری مشاغل معمولاً «کسب‌وکار محلی» مناسب‌تر است.</p>
					</td>
				</tr>
				<tr>
					<th>غیرفعال‌سازی کامنت پیش‌فرض وردپرس</th>
					<td>
						<label><input type="checkbox" name="<?php echo esc_attr( $opt ); ?>[disable_wp_comments]" value="1" <?php checked( $s['disable_wp_comments'], 1 ); ?>> فعال</label>
						<p class="description">روی نوع‌های محتوایی که OmoReviews فعال است، سیستم کامنت پیش‌فرض وردپرس بسته و پنهان می‌شود تا دو سیستم نظر همزمان نمایش داده نشوند (توصیه می‌شود).</p>
					</td>
				</tr>
			</table>

			<h2 class="bizrev-sec">CSS سفارشی</h2>
			<table class="form-table">
				<tr>
					<th>کد CSS اضافی</th>
					<td>
						<textarea name="<?php echo esc_attr( $opt ); ?>[custom_css]" rows="6" class="large-text code" dir="ltr" placeholder=".bizrev-item{ ... }"><?php echo esc_textarea( $s['custom_css'] ?? '' ); ?></textarea>
						<p class="description">برای تنظیمات خیلی ریز که در بالا نیست. این کد مستقیم به صفحه اضافه می‌شود.</p>
					</td>
				</tr>
			</table>
			</div><!-- /advanced -->

			<?php submit_button( 'ذخیره تنظیمات' ); ?>

			<p class="description">برای نمایش دستی در هر صفحه از شورت‌کد استفاده کنید: <code>[omo_reviews]</code> یا با شناسه مشخص <code>[omo_reviews id="123"]</code></p>
		</form>

		<aside class="bizrev-preview-pane">
			<h2>پیش‌نمایش زنده</h2>
			<div class="bizrev-preview-frame">
				<div class="bizrev-wrap bizrev-tpl-<?php echo esc_attr( $s['template'] ); ?>" id="bizrev-preview"
					style="<?php echo esc_attr( BizRev_Public::inline_vars( $s ) ); ?>" dir="rtl">

					<div class="bizrev-summary">
						<div class="bizrev-summary-score">
							<div class="bizrev-bignum">۴٫۵</div>
							<?php echo BizRev_Public::stars_html( 4.5, $s['max_stars'] ); // phpcs:ignore ?>
							<div class="bizrev-summary-count">۱۲۸ نظر</div>
						</div>
						<div class="bizrev-summary-dist">
							<?php
							$demo = array( 5 => 80, 4 => 30, 3 => 10, 2 => 5, 1 => 3 );
							foreach ( $demo as $star => $pct ) : ?>
								<div class="bizrev-dist-row">
									<span class="bizrev-dist-label"><?php echo esc_html( $star ); ?> ★</span>
									<span class="bizrev-dist-bar"><i style="width:<?php echo (int) $pct; ?>%"></i></span>
									<span class="bizrev-dist-pct"><?php echo (int) $pct; ?>%</span>
								</div>
							<?php endforeach; ?>
						</div>
					</div>

					<article class="bizrev-item">
						<header class="bizrev-item-head">
							<div class="bizrev-avatar">ر</div>
							<div class="bizrev-meta">
								<span class="bizrev-author">رضا محمدی <span class="bizrev-verified">✔ تأییدشده</span></span>
								<time class="bizrev-date">۱۲ خرداد ۱۴۰۵</time>
							</div>
							<div class="bizrev-item-rating"><?php echo BizRev_Public::stars_html( 5, $s['max_stars'] ); // phpcs:ignore ?></div>
						</header>
						<div class="bizrev-item-content"><p>برخورد کارکنان فوق‌العاده بود و کیفیت خدمات بالاتر از انتظارم. حتماً دوباره مراجعه می‌کنم.</p></div>
						<div class="bizrev-item-pc">
							<ul class="bizrev-pros"><li>برخورد عالی</li><li>کیفیت بالا</li></ul>
							<ul class="bizrev-cons"><li>کمی شلوغ</li></ul>
						</div>
						<div class="bizrev-rec">👍 این کسب‌وکار را توصیه می‌کند</div>
						<footer class="bizrev-item-foot">
							<span class="bizrev-helpful-q">این نظر مفید بود؟</span>
							<button type="button" class="bizrev-vote">بله (<b>۲۴</b>)</button>
							<button type="button" class="bizrev-vote">خیر (<b>۱</b>)</button>
						</footer>
					</article>

					<form class="bizrev-form" onsubmit="return false">
						<h3 class="bizrev-form-title">ثبت نظر شما</h3>
						<div class="bizrev-criteria-input">
							<div class="bizrev-crit-input-row"><label>کیفیت</label><span class="bizrev-star-input"><i class="bizrev-star-pick on">★</i><i class="bizrev-star-pick on">★</i><i class="bizrev-star-pick on">★</i><i class="bizrev-star-pick">★</i><i class="bizrev-star-pick">★</i></span></div>
							<div class="bizrev-crit-input-row"><label>قیمت</label><span class="bizrev-star-input"><i class="bizrev-star-pick on">★</i><i class="bizrev-star-pick on">★</i><i class="bizrev-star-pick on">★</i><i class="bizrev-star-pick on">★</i><i class="bizrev-star-pick">★</i></span></div>
						</div>
						<textarea class="bizrev-input bizrev-textarea" placeholder="تجربه خود را بنویسید…"></textarea>
						<button type="button" class="bizrev-submit">ارسال نظر</button>
					</form>
				</div>
			</div>
		</aside>
	</div>
</div>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/** @var array $rows @var array $counts @var string $status @var bool $licensed */
?>
<div class="wrap bizrev-admin" dir="rtl">
	<h1>مدیریت نظرات</h1>

	<?php if ( empty( $licensed ) ) : ?>
		<div class="notice notice-warning bizrev-license-banner">
			<p>
				<strong>🔒 حالت فقط‌خواندنی</strong> —
				لایسنس محصول فعال نیست. شما می‌توانید نظرات را مشاهده کنید، اما برای پاسخ دادن، تأیید، رد یا حذف نظرات و دسترسی به تنظیمات، لطفاً محصول را از طریق مارکت فعال‌سازی کنید.
			</p>
		</div>
	<?php endif; ?>

	<ul class="subsubsub">
		<?php
		$labels = array( 'pending' => 'در انتظار', 'approved' => 'تأییدشده', 'spam' => 'اسپم', 'trash' => 'زباله‌دان' );
		$i = 0;
		foreach ( $labels as $st => $label ) :
			$i++;
			$url = admin_url( 'admin.php?page=bizrev-reviews&status=' . $st );
			?>
			<li>
				<a href="<?php echo esc_url( $url ); ?>" class="<?php echo $status === $st ? 'current' : ''; ?>">
					<?php echo esc_html( $label ); ?> <span class="count">(<?php echo (int) $counts[ $st ]; ?>)</span>
				</a>
				<?php echo $i < count( $labels ) ? ' |' : ''; ?>
			</li>
		<?php endforeach; ?>
	</ul>

	<table class="wp-list-table widefat fixed striped">
		<thead>
			<tr>
				<th style="width:140px">نویسنده</th>
				<th style="width:80px">امتیاز</th>
				<th>نظر</th>
				<th style="width:160px">مورد</th>
				<th style="width:120px">تاریخ</th>
				<th style="width:260px">عملیات</th>
			</tr>
		</thead>
		<tbody>
		<?php if ( empty( $rows ) ) : ?>
			<tr><td colspan="6">موردی یافت نشد.</td></tr>
		<?php else : foreach ( $rows as $r ) :
			$pros = json_decode( (string) $r->pros, true );
			$cons = json_decode( (string) $r->cons, true );
			?>
			<tr>
				<td>
					<strong><?php echo esc_html( $r->author_name ); ?></strong>
					<?php if ( $r->verified ) : ?><span class="bizrev-adm-verified" title="خریدار تأییدشده">✔</span><?php endif; ?>
					<br>
					<small><?php echo esc_html( $r->author_email ); ?></small>
				</td>
				<td><?php echo esc_html( number_format_i18n( $r->rating_overall, 1 ) ); ?> ★</td>
				<td>
					<?php echo esc_html( wp_trim_words( $r->content, 30 ) ); ?>
					<?php if ( $pros ) : ?><div class="bizrev-adm-pc">✓ <?php echo esc_html( implode( '، ', $pros ) ); ?></div><?php endif; ?>
					<?php if ( $cons ) : ?><div class="bizrev-adm-pc bad">✗ <?php echo esc_html( implode( '، ', $cons ) ); ?></div><?php endif; ?>

					<?php
					$replies = BizRev_Data::get_all_replies( $r->id );
					if ( $replies ) : ?>
						<div class="bizrev-adm-replies">
							<?php foreach ( $replies as $rep ) :
								$is_owner = user_can( (int) $rep->user_id, 'moderate_comments' );
								?>
								<div class="bizrev-adm-reply <?php echo 'approved' === $rep->status ? '' : 'is-pending'; ?>">
									<span class="bizrev-adm-reply-meta">
										↳ <strong><?php echo esc_html( $rep->author_name ); ?></strong>
										<?php if ( $is_owner ) : ?><span class="bizrev-adm-owner">مدیر</span><?php endif; ?>
										<?php if ( 'approved' !== $rep->status ) : ?><span class="bizrev-adm-pending-tag">(<?php echo esc_html( $rep->status ); ?>)</span><?php endif; ?>
									</span>
									<div class="bizrev-adm-reply-text"><?php echo esc_html( $rep->content ); ?></div>
									<?php if ( $licensed ) : ?>
									<div class="bizrev-adm-reply-actions">
										<?php if ( 'approved' !== $rep->status ) : ?>
											<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
												<?php wp_nonce_field( 'bizrev_moderate' ); ?>
												<input type="hidden" name="action" value="bizrev_moderate">
												<input type="hidden" name="do" value="approve">
												<input type="hidden" name="id" value="<?php echo (int) $rep->id; ?>">
												<button class="button button-small button-primary">تأیید</button>
											</form>
										<?php endif; ?>
										<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline">
											<?php wp_nonce_field( 'bizrev_moderate' ); ?>
											<input type="hidden" name="action" value="bizrev_moderate">
											<input type="hidden" name="do" value="delete">
											<input type="hidden" name="id" value="<?php echo (int) $rep->id; ?>">
											<button class="button button-small bizrev-danger">حذف</button>
										</form>
									</div>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>

					<?php if ( $licensed ) : ?>
					<details class="bizrev-reply-box">
						<summary>پاسخ مدیر</summary>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'bizrev_reply' ); ?>
							<input type="hidden" name="action" value="bizrev_reply">
							<input type="hidden" name="parent_id" value="<?php echo (int) $r->id; ?>">
							<textarea name="reply" rows="2" style="width:100%" placeholder="پاسخ شما به این نظر…"></textarea>
							<button class="button button-small">ارسال پاسخ</button>
						</form>
					</details>
					<?php endif; ?>
				</td>
				<td><a href="<?php echo esc_url( get_permalink( $r->post_id ) ); ?>" target="_blank"><?php echo esc_html( get_the_title( $r->post_id ) ); ?></a></td>
				<td><?php echo esc_html( BizRev_Public::jalali_or_date( $r->created_at ) ); ?></td>
				<td>
					<?php if ( $licensed ) : ?>
					<?php
					$do_btn = function( $do, $label, $cls = '' ) use ( $r ) {
						// از فرم POST برای امنیت بیشتر استفاده می‌کنیم
						echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="display:inline">';
						echo wp_nonce_field( 'bizrev_moderate', '_wpnonce', true, false );
						echo '<input type="hidden" name="action" value="bizrev_moderate">';
						echo '<input type="hidden" name="do" value="' . esc_attr( $do ) . '">';
						echo '<input type="hidden" name="id" value="' . (int) $r->id . '">';
						echo '<button class="button button-small ' . esc_attr( $cls ) . '">' . esc_html( $label ) . '</button>';
						echo '</form> ';
					};
					if ( 'approved' !== $r->status ) {
						$do_btn( 'approve', 'تأیید', 'button-primary' );
					}
					if ( 'pending' !== $r->status ) {
						$do_btn( 'pending', 'بازگردانی' );
					}
					if ( ! (int) $r->parent_id ) {
						if ( $r->verified ) {
							$do_btn( 'unverify', 'لغو تأیید خریدار' );
						} else {
							$do_btn( 'verify', 'تأیید خریدار', 'bizrev-verify-btn' );
						}
					}
					$do_btn( 'spam', 'اسپم' );
					$do_btn( 'delete', 'حذف', 'bizrev-danger' );
					?>
					<?php else : ?>
						<span class="bizrev-locked-hint">🔒 فعال‌سازی لایسنس</span>
					<?php endif; ?>
				</td>
			</tr>
		<?php endforeach; endif; ?>
		</tbody>
	</table>
</div>

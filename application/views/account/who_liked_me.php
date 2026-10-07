<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ai đã thích bạn — dựng theo `src/routes/tai-khoan.ai-thich-ban.tsx`.
 * @var array $list @var int $tong @var bool $la_vip
 *
 * Nút Thích lại / Bỏ qua là `[data-like-reply]` trong khối `[data-like-request]`
 * (app.js gửi ajax/tra-loi-thich rồi gỡ cả khối).
 */
// Người thường thấy rõ 2 người đầu để tin là thật, còn lại làm mờ
$so_ro = $la_vip ? count($list) : 2;
$con_lai = max(0, $tong - $so_ro);

// Thẻ người đang chờ trả lời: thẻ hồ sơ + hai nút
$cho_tra_loi = function ($m, $o) {
    $id = (int) $m['id']; ?>
    <div class="tk-wl-req<?= !empty($o['compact']) ? ' tk-wl-req--compact' : '' ?>" data-like-request="<?= $id ?>">
        <?php $this->load->view('account/_person', array('p' => $m, 'o' => $o + array(
            'action' => 'none',
            'meta'   => !empty($m['liked_at']) ? 'Thích bạn ' . e(time_ago($m['liked_at'])) : '',
        ))); ?>
        <div class="tk-wl-req__act">
            <button type="button" class="tk-btn tk-btn--brand tk-btn--sm"
                    data-like-reply="accept" data-user="<?= $id ?>"><?= tk_icon('heart') ?>Thích lại</button>
            <button type="button" class="tk-btn tk-btn--outline tk-btn--icon-sm"
                    data-like-reply="skip" data-user="<?= $id ?>" aria-label="Bỏ qua" title="Bỏ qua"><?= tk_icon('x') ?></button>
        </div>
    </div>
<?php };

// Người bị khoá: ảnh mờ, nút Nâng cấp VIP
$ve = function ($i, $m, $o) use ($so_ro, $cho_tra_loi) {
    if ($i < $so_ro) {
        $cho_tra_loi($m, $o);
    } else {
        $this->load->view('account/_person', array('p' => $m, 'o' => $o + array('locked' => true)));
    }
};
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Ai đã thích bạn</h1>
        <p>Thích lại để thể hiện sự quan tâm, hoặc nhắn tin ngay từ hồ sơ.</p>
    </div>
    <div class="tk-ph__act"><span class="tk-pill tk-pill--brand"><?= number_format($tong) ?> lượt thích</span></div>
</header>

<?php if (empty($list)): ?>
    <div class="tk-empty">
        <span class="tk-empty__ic"><?= tk_icon('heart') ?></span>
        <h3>Chưa có ai thích bạn</h3>
        <p>Hãy hoàn thiện hồ sơ để tăng cơ hội được để ý!</p>
        <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/ho-so') ?>">Hoàn thiện hồ sơ</a></div>
    </div>
<?php else: ?>
    <section class="tk-card tk-wl-ban">
        <div class="tk-wl-ban__b">
            <span class="tk-wl-ban__ic"><?= tk_icon('heart') ?></span>
            <div style="min-width:0">
                <p class="tk-wl-ban__t"><?= number_format($tong) ?> người đang quan tâm bạn</p>
                <p class="tk-wl-ban__d">
                    <?php if ($la_vip): ?>
                        Thích lại để ghép đôi. Bạn cũng có thể nhắn tin ngay từ hồ sơ.
                    <?php else: ?>
                        Thành viên thường xem rõ được <?= min(2, count($list)) ?> người đầu. Nâng cấp VIP để xem tất cả và thích lại.
                    <?php endif; ?>
                </p>
            </div>
        </div>
        <?php if (!$la_vip): ?>
            <a class="tk-btn tk-btn--gold" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('crown') ?>Nâng cấp VIP</a>
        <?php endif; ?>
    </section>

    <section class="tk-card">
        <div class="tk-stack tk-stack--sm tk-only-mobile">
            <?php foreach ($list as $i => $m) { $ve($i, $m, array('compact' => true)); } ?>
        </div>
        <div class="tk-people tk-people--3 tk-only-desktop">
            <?php foreach ($list as $i => $m) { $ve($i, $m, array()); } ?>
        </div>

        <?php if (!$la_vip && $con_lai > 0): ?>
            <div class="tk-wl-more">
                <a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('crown') ?>Nâng cấp để xem <?= number_format($con_lai) ?> người còn lại</a>
            </div>
        <?php endif; ?>
    </section>
<?php endif; ?>

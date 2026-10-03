<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Quan tâm & ghép đôi — dựng theo `src/routes/tai-khoan.quan-tam.tsx`.
 *
 * Chỉ khi hai bên cùng thích nhau mới thành ghép đôi và mở được khung chat,
 * nên thẻ "Người thích bạn" có hai nút quyết định: Thích lại hoặc Bỏ qua
 * (`[data-like-reply]`, app.js gửi tới ajax/tra-loi-thich).
 *
 * Bốn danh sách chia thành bốn thẻ. Cả bốn đều in sẵn từ máy chủ, thẻ đang mở
 * lấy từ ?tab= nên dán link hay tải lại vẫn đúng thẻ; JS bên dưới chỉ đổi
 * thẻ tại chỗ cho khỏi tải lại trang.
 */
$tabs = array(
    'thich-ban' => array('Người thích bạn', count($liked_me)),
    'ghep-doi'  => array('Đã ghép đôi',     count($matches)),
    'da-xem'    => array('Đã xem hồ sơ',    count($viewers)),
    'ban-thich' => array('Bạn đã thích',    count($my_likes)),
);
$tab = (string) $this->input->get('tab');
if (!isset($tabs[$tab])) { $tab = 'thich-ban'; }

// In một danh sách người hai lần: thẻ ngang trên điện thoại, thẻ lớn từ sm
$luoi = function ($list, $o, $ve = null) {
    $ve = $ve ?: function ($m, $o) { $this->load->view('account/_person', array('p' => $m, 'o' => $o)); };
    echo '<div class="tk-stack tk-stack--sm tk-only-mobile">';
    foreach ($list as $m) { $ve($m, $o + array('compact' => true)); }
    echo '</div><div class="tk-people tk-people--3 tk-only-desktop">';
    foreach ($list as $m) { $ve($m, $o); }
    echo '</div>';
};

// Thẻ người đang chờ trả lời: thẻ hồ sơ + hai nút Thích lại / Bỏ qua
$cho_tra_loi = function ($m, $o) {
    $id = (int) $m['id']; ?>
    <div class="tk-lk-req<?= !empty($o['compact']) ? ' tk-lk-req--compact' : '' ?>" data-like-request="<?= $id ?>">
        <?php $this->load->view('account/_person', array('p' => $m, 'o' => $o + array(
            'action' => 'none',
            'meta'   => !empty($m['liked_at']) ? 'Thích bạn ' . e(time_ago($m['liked_at'])) : '',
        ))); ?>
        <div class="tk-lk-req__act">
            <button type="button" class="tk-btn tk-btn--brand tk-btn--sm"
                    data-like-reply="accept" data-user="<?= $id ?>"><?= tk_icon('heart') ?>Thích lại</button>
            <button type="button" class="tk-btn tk-btn--outline tk-btn--icon-sm"
                    data-like-reply="skip" data-user="<?= $id ?>" aria-label="Bỏ qua" title="Bỏ qua"><?= tk_icon('x') ?></button>
        </div>
    </div>
<?php };
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Quan tâm &amp; ghép đôi</h1>
        <p>Khung chat chỉ mở khi hai người cùng thích nhau.</p>
    </div>
    <div class="tk-ph__act">
        <a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/goi-y') ?>"><?= tk_icon('sparkles') ?>Gợi ý hôm nay</a>
    </div>
</header>

<nav class="tk-tabs" role="tablist" data-lk-tabs>
    <?php foreach ($tabs as $k => $t): ?>
        <a href="?tab=<?= $k ?>" role="tab" id="tk-lk-tab-<?= $k ?>" data-tab="<?= $k ?>"
           aria-controls="tk-lk-<?= $k ?>" aria-selected="<?= $k === $tab ? 'true' : 'false' ?>"
           class="<?= $k === $tab ? 'is-active' : '' ?>"><?= e($t[0]) ?><span class="tk-tabs__n"><?= $t[1] ?></span></a>
    <?php endforeach; ?>
</nav>

<!-- Người thích bạn -->
<div class="tk-lk-panel" id="tk-lk-thich-ban" role="tabpanel" aria-labelledby="tk-lk-tab-thich-ban"<?= $tab === 'thich-ban' ? '' : ' hidden' ?>>
    <?php if (empty($liked_me)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('heart') ?></span>
            <h3>Chưa có ai đang chờ bạn trả lời</h3>
            <p>Hồ sơ đầy đủ, có ảnh đẹp sẽ nhận được nhiều lượt thích hơn.</p>
            <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/ho-so') ?>">Hoàn thiện hồ sơ</a></div>
        </div>
    <?php else: ?>
        <section class="tk-card">
            <div class="tk-card__h">
                <div>
                    <h2 class="tk-card__t"><?= count($liked_me) ?> người đang chờ bạn thích lại</h2>
                    <p class="tk-card__d">Thích lại để mở khung chat ngay. Nếu bỏ qua, người kia sẽ không được báo gì cả.</p>
                </div>
            </div>
            <?php $luoi($liked_me, array(), $cho_tra_loi); ?>
        </section>
    <?php endif; ?>
</div>

<!-- Đã ghép đôi -->
<div class="tk-lk-panel" id="tk-lk-ghep-doi" role="tabpanel" aria-labelledby="tk-lk-tab-ghep-doi"<?= $tab === 'ghep-doi' ? '' : ' hidden' ?>>
    <?php if (empty($matches)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('match') ?></span>
            <h3>Chưa có ghép đôi nào</h3>
            <p>Thích lại những người đã thích bạn để ghép đôi ngay và bắt đầu trò chuyện.</p>
            <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('swipe-match') ?>"><?= tk_icon('match') ?>Khám phá ngay</a></div>
        </div>
    <?php else: ?>
        <section class="tk-card">
            <div class="tk-card__h">
                <div>
                    <h2 class="tk-card__t">Đã ghép đôi</h2>
                    <p class="tk-card__d">Hai bạn đã thích nhau — hãy mở lời trước nhé</p>
                </div>
            </div>
            <?php $luoi($matches, array('matched' => true), function ($m, $o) {
                $this->load->view('account/_person', array('p' => $m, 'o' => $o + array(
                    'chat_url' => site_url('tai-khoan/tin-nhan') . '?to=' . (int) $m['id'],
                )));
            }); ?>
        </section>
    <?php endif; ?>
</div>

<!-- Đã xem hồ sơ -->
<div class="tk-lk-panel" id="tk-lk-da-xem" role="tabpanel" aria-labelledby="tk-lk-tab-da-xem"<?= $tab === 'da-xem' ? '' : ' hidden' ?>>
    <?php if (empty($viewers)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('eye') ?></span>
            <h3>Chưa có ai ghé xem hồ sơ của bạn</h3>
            <p>Cập nhật ảnh đại diện và lời giới thiệu để hồ sơ nổi bật hơn.</p>
        </div>
    <?php else: ?>
        <section class="tk-card">
            <div class="tk-card__h">
                <div>
                    <h2 class="tk-card__t">Đã xem hồ sơ của bạn</h2>
                    <p class="tk-card__d">Những người ghé qua hồ sơ của bạn trong 7 ngày gần nhất</p>
                </div>
                <a class="tk-btn tk-btn--soft tk-btn--sm tk-card__act" href="<?= site_url('tai-khoan/ai-xem-ho-so') ?>"><?= tk_icon('eye') ?>Xem chi tiết</a>
            </div>
            <?php $luoi($viewers, array('label' => 'Thả tim'), function ($m, $o) {
                $this->load->view('account/_person', array('p' => $m, 'o' => $o + array(
                    'meta' => !empty($m['viewed_at']) ? 'Đã xem ' . e(time_ago($m['viewed_at'])) : '',
                )));
            }); ?>
        </section>
    <?php endif; ?>
</div>

<!-- Bạn đã thích -->
<div class="tk-lk-panel" id="tk-lk-ban-thich" role="tabpanel" aria-labelledby="tk-lk-tab-ban-thich"<?= $tab === 'ban-thich' ? '' : ' hidden' ?>>
    <?php if (empty($my_likes)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('heart') ?></span>
            <h3>Bạn chưa thích ai</h3>
            <p>Hãy khám phá những hồ sơ phù hợp và thả tim cho người bạn thấy hợp gu.</p>
            <div class="tk-empty__act"><a class="tk-btn tk-btn--brand tk-btn--lg" href="<?= site_url('swipe-match') ?>"><?= tk_icon('match') ?>Khám phá ngay</a></div>
        </div>
    <?php else: ?>
        <section class="tk-card">
            <div class="tk-card__h">
                <div>
                    <h2 class="tk-card__t">Bạn đã thích</h2>
                    <p class="tk-card__d">Đang chờ người ấy thích lại. Khi cả hai cùng thích, chat sẽ mở ra.</p>
                </div>
            </div>
            <?php $luoi($my_likes, array('action' => 'view', 'label' => 'Xem hồ sơ', 'pass' => false)); ?>
        </section>
    <?php endif; ?>
</div>

<script>
/* Đổi thẻ tại chỗ, ghi ?tab= lên thanh địa chỉ để dán link / tải lại vẫn đúng thẻ */
(function () {
    var nav = document.querySelector('[data-lk-tabs]');
    if (!nav) { return; }
    function mo(k, ghi) {
        var a = nav.querySelector('[data-tab="' + k + '"]');
        if (!a) { return; }
        nav.querySelectorAll('[data-tab]').forEach(function (t) {
            var on = t === a;
            t.classList.toggle('is-active', on);
            t.setAttribute('aria-selected', on ? 'true' : 'false');
            document.getElementById('tk-lk-' + t.getAttribute('data-tab')).hidden = !on;
        });
        if (ghi && window.history.replaceState) {
            var u = new URL(window.location.href);
            u.searchParams.set('tab', k);
            history.replaceState(null, '', u);
        }
    }
    nav.addEventListener('click', function (e) {
        var a = e.target.closest('[data-tab]');
        if (!a || e.metaKey || e.ctrlKey || e.shiftKey) { return; }
        e.preventDefault();
        mo(a.getAttribute('data-tab'), true);
    });
})();
</script>

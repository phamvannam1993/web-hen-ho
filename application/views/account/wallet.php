<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Nạp xu / VIP — dựng theo `src/routes/tai-khoan.nap-xu.tsx`.
 *
 * Gói, đơn và lịch sử xu đều lấy thật từ M_billing. Bản thiết kế có nhãn
 * "Phổ biến" và hộp xác nhận trước khi tạo đơn — dữ liệu không có cờ "phổ biến"
 * nên bỏ nhãn, còn đơn được tạo thẳng như trước.
 */

$la_vip  = !empty($me['is_vip']) && (empty($me['vip_expired_at']) || strtotime($me['vip_expired_at']) > time());
$vip_den = $la_vip && !empty($me['vip_expired_at']) ? date('d/m/Y', strtotime($me['vip_expired_at'])) : null;
$gia_mo  = (int) setting('unlock_cost', 20);

// value => (nhãn, biểu tượng) — đúng ba lựa chọn trang cũ có
$cach_tra = array(
    'bank'  => array('Chuyển khoản ngân hàng', 'bank'),
    'momo'  => array('Ví MoMo', 'wallet'),
    'vnpay' => array('VNPay', 'card'),
);
$ten_cach = array('bank' => 'Chuyển khoản', 'momo' => 'MoMo', 'vnpay' => 'VNPay', 'card' => 'Thẻ', 'manual' => 'Thủ công');

// trạng thái đơn => (nhãn, sắc thái)
$tt_don = array(
    'paid'     => array('Hoàn tất', 'success'),
    'pending'  => array('Chờ xác nhận', 'warning'),
    'failed'   => array('Thất bại', 'danger'),
    'refunded' => array('Đã hoàn tiền', ''),
    'canceled' => array('Đã huỷ', ''),
);
// lý do biến động xu khi không có ghi chú
$ly_do = array(
    'nap'            => 'Nạp xu',
    'unlock_contact' => 'Mở thông tin liên hệ',
    'bonus'          => 'Thưởng xu',
    'spend'          => 'Dùng xu',
    'admin_adjust'   => 'Điều chỉnh từ ban quản trị',
);
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Nạp xu / VIP</h1>
        <p>Xu dùng để mở thông tin liên hệ và các tiện ích khác.</p>
    </div>
</header>

<!-- Số dư & VIP -->
<section class="tk-card tk-wl-bal">
    <div class="tk-wl-bal__i">
        <span class="tk-wl-bal__ic tk-wl-bal__ic--gold"><?= tk_icon('coins') ?></span>
        <div class="tk-wl-bal__b">
            <p class="tk-wl-bal__n"><?= number_format((int) $me['coin_balance']) ?> xu</p>
            <p class="tk-wl-bal__l">Số dư hiện tại</p>
        </div>
    </div>
    <div class="tk-wl-bal__i">
        <span class="tk-wl-bal__ic tk-wl-bal__ic--brand"><?= tk_icon('crown') ?></span>
        <div class="tk-wl-bal__b">
            <?php if ($la_vip): ?>
                <p class="tk-wl-bal__v">Đang là VIP</p>
                <p class="tk-wl-bal__l"><?= $vip_den ? 'Hiệu lực đến ' . $vip_den : 'Đang hiệu lực' ?></p>
            <?php else: ?>
                <p class="tk-wl-bal__v">Chưa có VIP</p>
                <p class="tk-wl-bal__l">Chọn một gói VIP bên dưới để nâng cấp</p>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Một form bọc cả phần chọn gói và phương thức; display:contents để hai thẻ vẫn cách nhau như mọi khối khác -->
<form method="post" id="tk-wl-form" style="display:contents">
    <section class="tk-card">
        <div class="tk-card__h">
            <div>
                <h2 class="tk-card__t">Chọn gói</h2>
                <p class="tk-card__d">Xu/VIP được cộng sau khi đơn nạp được xác nhận</p>
            </div>
        </div>
        <?php if (empty($packages)): ?>
            <div class="tk-empty">
                <span class="tk-empty__ic"><?= tk_icon('coins') ?></span>
                <h3>Chưa có gói nào</h3>
                <p>Hiện chưa có gói nạp đang mở bán, vui lòng quay lại sau.</p>
            </div>
        <?php else: ?>
            <div class="tk-wl-pkgs">
                <?php foreach ($packages as $pk): ?>
                    <?php $la_goi_vip = $pk['type'] !== 'coin'; ?>
                    <label class="tk-wl-pkg">
                        <input class="tk-sr" type="radio" name="package_id" value="<?= (int) $pk['id'] ?>"
                               data-price="<?= e(money($pk['price'])) ?>" required>
                        <span class="tk-wl-pkg__t"><?= tk_icon($la_goi_vip ? 'crown' : 'coins') ?><?= e($pk['name']) ?></span>
                        <?php if ($la_goi_vip): ?>
                            <span class="tk-wl-pkg__s">VIP <?= (int) $pk['duration_days'] ?> ngày</span>
                        <?php elseif ($pk['bonus_coin']): ?>
                            <span class="tk-wl-pkg__s"><?= number_format($pk['coin_amount']) ?> xu + <?= number_format($pk['bonus_coin']) ?> xu tặng</span>
                        <?php endif; ?>
                        <span class="tk-wl-pkg__p"><?= money($pk['price']) ?></span>
                        <?php if (!empty($pk['description'])): ?>
                            <span class="tk-wl-pkg__d"><?= e($pk['description']) ?></span>
                        <?php endif; ?>
                        <span class="tk-wl-pkg__on"><?= tk_icon('check') ?>Đang chọn</span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="tk-card">
        <div class="tk-card__h">
            <div><h2 class="tk-card__t">Quyền lợi VIP</h2></div>
        </div>
        <ul class="tk-wl-perks">
            <li><?= tk_icon('check') ?>Mở thông tin liên hệ không giới hạn, không tốn xu</li>
            <li><?= tk_icon('check') ?>Xem đầy đủ ai đã thích bạn và thả tim lại</li>
            <li><?= tk_icon('check') ?>Xem ai đã xem hồ sơ và thời điểm xem</li>
            <li><?= tk_icon('check') ?>Nhắn tin cho người chỉ nhận tin từ thành viên VIP</li>
        </ul>
        <p class="tk-wl-info"><?= tk_icon('info') ?><span>Mở thông tin liên hệ của một tin đăng tốn <?= $gia_mo ?> xu.
            Thành viên VIP không bị trừ xu khi mở thông tin liên hệ.</span></p>
    </section>

    <section class="tk-card">
        <div class="tk-card__h">
            <div><h2 class="tk-card__t">Phương thức thanh toán</h2></div>
        </div>
        <div class="tk-wl-methods">
            <?php $dau = true; foreach ($cach_tra as $val => $m): ?>
                <label class="tk-wl-method">
                    <input class="tk-sr" type="radio" name="method" value="<?= $val ?>"<?= $dau ? ' checked' : '' ?>>
                    <?= tk_icon($m[1]) ?><span class="tk-truncate"><?= $m[0] ?></span>
                </label>
            <?php $dau = false; endforeach; ?>
        </div>
        <?php if (setting('bank_info')): ?>
            <p class="tk-wl-bank"><?= tk_icon('bank') ?><span><b>Thông tin chuyển khoản:</b> <?= e(setting('bank_info')) ?></span></p>
        <?php endif; ?>
        <p class="tk-wl-hint">Sau khi tạo đơn, hãy chuyển khoản với nội dung là mã đơn. Hệ thống cộng xu/VIP khi xác nhận thanh toán.</p>
        <button class="tk-btn tk-btn--brand tk-btn--lg tk-wl-submit" type="submit"<?= empty($packages) ? ' disabled' : '' ?>>
            Tạo đơn nạp<span class="tk-wl-submit__p"></span>
        </button>
    </section>
</form>

<div class="tk-wl-hist">
    <section class="tk-card">
        <div class="tk-card__h">
            <div><h2 class="tk-card__t">Đơn của tôi</h2></div>
        </div>
        <?php if (empty($orders)): ?>
            <div class="tk-empty">
                <span class="tk-empty__ic"><?= tk_icon('file') ?></span>
                <h3>Chưa có đơn nào</h3>
                <p>Đơn nạp bạn tạo sẽ hiện ở đây cùng trạng thái xử lý.</p>
            </div>
        <?php else: ?>
            <ul class="tk-list">
                <?php foreach ($orders as $o): ?>
                    <?php $tt = $tt_don[$o['status']] ?? array($o['status'], ''); ?>
                    <li>
                        <div class="tk-wl-row__b">
                            <p class="tk-wl-row__t"><?= e($o['package_name'] ?: 'Gói đã xoá') ?> · <?= money($o['amount']) ?></p>
                            <p class="tk-wl-row__m">#<?= e($o['code']) ?> • <?= e($ten_cach[$o['method']] ?? $o['method']) ?> • <?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></p>
                        </div>
                        <span class="tk-pill<?= $tt[1] ? ' tk-pill--' . $tt[1] : '' ?>"><?= e($tt[0]) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>

    <section class="tk-card">
        <div class="tk-card__h">
            <div><h2 class="tk-card__t">Lịch sử xu</h2></div>
        </div>
        <?php if (empty($coins)): ?>
            <div class="tk-empty">
                <span class="tk-empty__ic"><?= tk_icon('coins') ?></span>
                <h3>Chưa có giao dịch</h3>
                <p>Mỗi lần nạp, nhận thưởng hay dùng xu đều được ghi lại ở đây.</p>
            </div>
        <?php else: ?>
            <ul class="tk-list">
                <?php foreach ($coins as $c): ?>
                    <?php $am = (int) $c['amount']; ?>
                    <li>
                        <div class="tk-wl-row__b">
                            <p class="tk-wl-row__t"><?= e($c['note'] ?: ($ly_do[$c['reason']] ?? $c['reason'])) ?></p>
                            <p class="tk-wl-row__m"><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?> • Số dư <?= number_format($c['balance_after']) ?> xu</p>
                        </div>
                        <span class="tk-wl-amt <?= $am >= 0 ? 'is-up' : 'is-down' ?>"><?= $am > 0 ? '+' : '' ?><?= number_format($am) ?> xu</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    </section>
</div>

<script>
(function () {
    // Ghi giá gói đang chọn lên nút tạo đơn, như bản thiết kế
    var form = document.getElementById('tk-wl-form');
    if (!form) return;
    var out = form.querySelector('.tk-wl-submit__p');
    function cap() {
        var r = form.querySelector('input[name="package_id"]:checked');
        out.textContent = r ? ' — ' + r.getAttribute('data-price') : '';
    }
    form.addEventListener('change', cap);
    cap();
})();
</script>

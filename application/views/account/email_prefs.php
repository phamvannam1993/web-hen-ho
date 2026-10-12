<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Cài đặt email — dựng theo `src/routes/tai-khoan.email.tsx`.
 */
$loai = array(
    'new_message'   => array('Tin nhắn mới', 'Báo khi có người nhắn mà bạn chưa đọc sau 5 phút.'),
    'notification'  => array('Ghép đôi & lượt thích', 'Ghép đôi báo ngay; lượt thích và lượt xem gom lại gửi một lần mỗi tối.'),
    'match_suggest' => array('Gợi ý người phù hợp', 'Chúng tôi chọn sẵn một người hợp tiêu chí và gửi tới bạn.'),
    're_engage'     => array('Nhắc nhở', 'Nhắc xác nhận email, bổ sung hồ sơ hoặc quay lại khám phá. Người vắng trên 30 ngày nhận tối đa mỗi tuần một lần.'),
);
$da_huy = !empty($p['disabled_at']);
?>
<div class="tk-ph">
    <div class="tk-ph__b">
        <h1>Cài đặt email</h1>
        <p>Tối đa 2 email gợi ý/thông báo tổng hợp mỗi ngày. Email tin nhắn và ghép đôi được gửi riêng.</p>
    </div>
</div>

<form method="post" class="tk-em">
    <?php if ($da_huy): ?>
        <div class="tk-alert tk-alert--warning">
            Bạn đang tắt toàn bộ email. Lưu cài đặt bên dưới là nhận lại bình thường.
        </div>
    <?php endif; ?>

    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Loại email</h2></div></div>
        <div class="tk-toggles">
            <?php foreach ($loai as $key => $mo_ta): ?>
                <label class="tk-toggle">
                    <span class="tk-toggle__b">
                        <span class="tk-toggle__t"><?= e($mo_ta[0]) ?></span>
                        <span class="tk-toggle__d"><?= e($mo_ta[1]) ?></span>
                    </span>
                    <span class="tk-switch">
                        <input type="checkbox" name="<?= $key ?>" value="1" <?= !$da_huy && !empty($p[$key]) ? 'checked' : '' ?>>
                        <span class="tk-switch__track"></span><span class="tk-switch__knob"></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="tk-card">
        <div class="tk-card__h"><div><h2 class="tk-card__t">Tần suất nhận gợi ý</h2></div></div>
        <div class="tk-field">
            <label for="match_every_days">Gửi gợi ý mỗi</label>
            <select id="match_every_days" name="match_every_days">
                <?php foreach (array(1 => '1 ngày', 2 => '2 ngày', 3 => '3 ngày', 7 => '7 ngày') as $n => $nhan): ?>
                    <option value="<?= $n ?>" <?= (int) $p['match_every_days'] === $n ? 'selected' : '' ?>><?= $nhan ?></option>
                <?php endforeach; ?>
            </select>
            <p class="tk-hint">Áp dụng cho email gợi ý và nhắc quay lại. Mỗi chu kỳ chỉ chọn một nội dung phù hợp với trạng thái tài khoản.</p>
        </div>
        <p class="tk-em-tip">
            <?= tk_icon('info') ?>
            <span>Chỉ gửi những việc liên quan trực tiếp tới bạn: có người nhắn tin, có người thích bạn, hoặc có hồ sơ hợp tiêu chí.
                Không quảng cáo, không thư rác. Mọi thư đều có đường huỷ nhận ngay ở chân thư.</span>
        </p>
    </section>

    <div class="tk-row tk-em-acts">
        <button type="submit" class="tk-btn tk-btn--brand tk-btn--lg"><?= tk_icon('save') ?>Lưu cài đặt</button>
        <button type="submit" name="huy_tat_ca" value="1" class="tk-btn tk-btn--outline tk-btn--lg"
                data-confirm="Huỷ nhận toàn bộ email? Bạn sẽ không nhận được thông báo nào qua email nữa.">
            <?= tk_icon('mail-x') ?>Huỷ nhận tất cả
        </button>
    </div>
</form>

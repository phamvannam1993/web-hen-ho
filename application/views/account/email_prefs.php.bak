<?php defined('BASEPATH') OR exit('No direct script access allowed');
$loai = array(
    'new_message'   => array('Tin nhắn mới', 'Báo khi có người nhắn mà bạn chưa đọc sau 5 phút.'),
    'notification'  => array('Ghép đôi & lượt thích', 'Ghép đôi báo ngay; lượt thích và lượt xem gom lại gửi một lần mỗi tối.'),
    'match_suggest' => array('Gợi ý người phù hợp', 'Chúng tôi chọn sẵn một người hợp tiêu chí và gửi tới bạn.'),
    're_engage'     => array('Nhắc quay lại', 'Chỉ gửi khi bạn đã lâu không ghé, tối đa một lần mỗi tuần.'),
);
$da_huy = !empty($p['disabled_at']);
?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h2 class="section-title">Cài đặt email</h2>
            <p class="section-hint">Chọn loại thư bạn muốn nhận. Chúng tôi gửi tối đa 2 thư mỗi ngày.</p>

            <?php if ($da_huy): ?>
                <div class="alert alert-warning">
                    Bạn đang tắt toàn bộ email. Lưu cài đặt bên dưới là nhận lại bình thường.
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <div class="email-prefs">
                    <?php foreach ($loai as $key => $mo_ta): ?>
                        <label class="email-pref">
                            <input type="checkbox" name="<?= $key ?>" value="1"
                                   <?= !$da_huy && !empty($p[$key]) ? 'checked' : '' ?>>
                            <span>
                                <b><?= e($mo_ta[0]) ?></b>
                                <small><?= e($mo_ta[1]) ?></small>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>

                <label for="match_every_days">Tần suất gợi ý người phù hợp</label>
                <select id="match_every_days" name="match_every_days">
                    <?php foreach (array(2 => 'Mỗi 2 ngày', 3 => 'Mỗi 3 ngày') as $n => $nhan): ?>
                        <option value="<?= $n ?>" <?= (int) $p['match_every_days'] === $n ? 'selected' : '' ?>>
                            <?= $nhan ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <div class="auth-actions">
                    <button type="submit" class="btn btn-primary">Lưu cài đặt</button>
                    <button type="submit" name="huy_tat_ca" value="1" class="btn btn-ghost"
                            data-confirm="Huỷ nhận toàn bộ email? Bạn sẽ không nhận được thông báo nào qua email nữa.">
                        Huỷ nhận tất cả
                    </button>
                </div>
            </form>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Chúng tôi gửi thư khi nào?</h3>
            <p>Chỉ gửi những việc liên quan trực tiếp tới bạn: có người nhắn tin, có người thích bạn,
                hoặc có hồ sơ hợp tiêu chí. Không quảng cáo, không thư rác.</p>
            <p>Mọi thư đều có đường huỷ nhận ngay ở chân thư.</p>
        </div>
    </aside>
</div>

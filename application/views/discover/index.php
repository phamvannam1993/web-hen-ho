<?php defined('BASEPATH') OR exit('No direct script access allowed');
$nhom = array('female' => 'Bạn gái', 'male' => 'Bạn trai', 'gay' => 'Gay', 'les' => 'Les');
?>
<div class="discover-page">

    <div class="sw-stage" id="sw-stage"
         data-guest="<?= $user ? '0' : '1' ?>"
         data-login="<?= site_url('dang-nhap') ?>"
         data-register="<?= site_url('dang-ky') ?>">

        <?php /* Nút lọc nổi ở góc trên khung, thay cho thanh trên đã bỏ */ ?>
        <button type="button" class="sw-filter-btn" id="sw-filter-btn" aria-label="Bộ lọc nhanh">
            <svg viewBox="0 0 24 24"><path d="M3 5h18l-7 8v6l-4 2v-8z"/></svg>
        </button>

        <?php if (empty($candidates)): ?>
            <!-- Hết hồ sơ -->
            <div class="sw-none">
                <div class="sw-radar"><span></span><span></span><span></span></div>
                <h2>Đã xem hết hồ sơ phù hợp</h2>
                <p>Không còn ai quanh bạn theo tiêu chí hiện tại. Hãy nới rộng bộ lọc,
                    hoặc cập nhật hồ sơ để hệ thống gợi ý chính xác hơn.</p>
                <div class="sw-none-actions">
                    <button type="button" class="btn btn-primary" id="sw-open-filter">Cài đặt lại bộ lọc</button>
                    <?php if ($user): ?>
                        <a class="btn btn-ghost" href="<?= site_url('tai-khoan/ho-so') ?>">Sửa hồ sơ của tôi</a>
                    <?php else: ?>
                        <a class="btn btn-ghost" href="<?= site_url('dang-ky') ?>">Tạo hồ sơ</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php /* Cuộn dọc để sang người tiếp theo, mỗi lần một hồ sơ */ ?>
            <div class="sw-deck" id="sw-deck">
                <?php foreach ($candidates as $c): ?>
                    <?php $this->load->view('discover/_card', array('c' => $c, 'user' => $user)); ?>
                <?php endforeach; ?>
            </div>

            <div class="sw-none" id="sw-empty" hidden>
                <div class="sw-radar"><span></span><span></span><span></span></div>
                <h2>Đã xem hết lượt này</h2>
                <p>Tải thêm hồ sơ mới, nới bộ lọc, hoặc bổ sung hồ sơ của bạn
                    để được gợi ý đúng người hơn.</p>
                <div class="sw-none-actions">
                    <a class="btn btn-primary" href="<?= site_url('kham-pha') ?>">Tải thêm hồ sơ</a>
                    <?php if ($user): ?>
                        <a class="btn btn-ghost" href="<?= site_url('tai-khoan/ho-so') ?>">Sửa hồ sơ của tôi</a>
                    <?php else: ?>
                        <a class="btn btn-ghost" href="<?= site_url('dang-ky') ?>">Tạo hồ sơ</a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="sw-controls" id="sw-controls">
                <button type="button" class="sw-btn sw-btn-undo" data-swipe="undo" aria-label="Xem lại hồ sơ vừa bỏ qua" title="Xem lại">
                    <svg viewBox="0 0 24 24"><path d="M4 9h11a5 5 0 0 1 0 10H8"/><path d="M8 5L4 9l4 4"/></svg>
                </button>
                <button type="button" class="sw-btn sw-btn-nope" data-swipe="left" aria-label="Bỏ qua" title="Bỏ qua">
                    <svg viewBox="0 0 24 24"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
                <button type="button" class="sw-btn sw-btn-like" data-swipe="right" aria-label="Thích" title="Thích">
                    <svg viewBox="0 0 24 24"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>
                </button>
                <button type="button" class="sw-btn sw-btn-info" data-swipe="info" aria-label="Xem hồ sơ" title="Xem hồ sơ">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6v.2"/></svg>
                </button>
            </div>

            <p class="sw-hint">Còn <b id="sw-count"><?= number_format($remaining) ?></b> hồ sơ phù hợp
                · <span class="sw-key">←</span> bỏ qua <span class="sw-key">→</span> thích
                <span class="sw-key">↑</span> xem thêm</p>
        <?php endif; ?>

<?php /* Thanh điều hướng nằm luôn trong khung */ ?>
<nav class="sw-tabbar">
    <a href="<?= site_url() ?>">
    <svg viewBox="0 0 24 24"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1"/></svg>
    <span>Trang chủ</span>
</a>
    <a class="on" href="<?= site_url('swipe-match') ?>">
        <svg viewBox="0 0 24 24"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>
        <span>Khám phá</span>
    </a>
    <a href="<?= site_url('hen-ho') ?>">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.6-3.6"/></svg>
        <span>Hẹn hò</span>
    </a>
    <a href="<?= site_url('tam-su') ?>">
        <svg viewBox="0 0 24 24"><path d="M4 5h16v11H8l-4 3z"/></svg>
        <span>Tâm sự</span>
    </a>

    <a href="<?= site_url($user ? 'tai-khoan' : 'dang-nhap') ?>">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8.5" r="4"/><path d="M4.5 20c1.2-3.6 4-5.5 7.5-5.5s6.3 1.9 7.5 5.5"/></svg>
        <span><?= $user ? 'Tôi' : 'Đăng nhập' ?></span>
    </a>
</nav>
    </div>

    <?php if ($matches): ?>
        <section class="home-block">
            <h2 class="block-title">Đã ghép đôi</h2>
            <div class="member-grid is-compact">
                <?php foreach ($matches as $m): ?>
                    <?php $this->load->view('members/_card', array('m' => $m)); ?>
                <?php endforeach; ?>
            </div>
            <div class="block-more"><a class="btn btn-more" href="<?= site_url('tai-khoan/tin-nhan') ?>">Nhắn tin →</a></div>
        </section>
    <?php endif; ?>
</div>

<?php /* Khách chưa chọn nhóm: hỏi ngay khi vào trang */ ?>
<?php if (!empty($need_pick)): ?>
<div class="sw-onboard" id="sw-onboard">
    <div class="sw-onboard-box">
        <h2>Bạn muốn khám phá ai?</h2>
        <p>Chọn nhóm bạn quan tâm để chúng tôi hiển thị đúng người.</p>
        <div class="sw-onboard-opts">
            <?php foreach (array('male' => 'Bạn trai', 'female' => 'Bạn gái', 'gay' => 'Gay', 'les' => 'Les') as $k => $t): ?>
                <a href="<?= site_url('kham-pha') ?>?xem=<?= $k ?>"><?= $t ?></a>
            <?php endforeach; ?>
        </div>
        <p class="sw-onboard-note">Đã có tài khoản? <a href="<?= site_url('dang-nhap') ?>">Đăng nhập</a>
            để được gợi ý theo hồ sơ của bạn.</p>
    </div>
</div>
<?php endif; ?>

<?php /* Bộ lọc nhanh */ ?>
<div class="sw-modal" id="sw-filter" hidden>
    <div class="sw-modal-box">
        <div class="sw-modal-head">
            <h2>Bộ lọc nhanh</h2>
            <button type="button" class="sw-modal-close" data-close-filter aria-label="Đóng">&times;</button>
        </div>
        <form method="get" action="<?= site_url('kham-pha') ?>">
            <?php if (!$user && $view): ?><input type="hidden" name="xem" value="<?= e($view) ?>"><?php endif; ?>
            <label class="filter-label">Khoảng tuổi</label>
            <div class="range-row">
                <input type="number" name="age_min" min="18" max="80" placeholder="Từ 18" value="<?= e($this->input->get('age_min')) ?>">
                <span>–</span>
                <input type="number" name="age_max" min="18" max="80" placeholder="Đến 70" value="<?= e($this->input->get('age_max')) ?>">
            </div>
            <label class="filter-label">Khu vực</label>
            <select name="province_id">
                <option value="">Tất cả tỉnh/thành</option>
                <?php foreach ($provinces as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $this->input->get('province_id') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <div class="sw-modal-foot">
                <a class="btn btn-ghost" href="<?= site_url('kham-pha') ?><?= (!$user && $view) ? '?xem=' . e($view) : '' ?>">Xoá lọc</a>
                <button class="btn btn-primary" type="submit">Áp dụng</button>
            </div>
        </form>
    </div>
</div>

<?php /* Hộp chúc mừng khi hai bên cùng thích */ ?>
<div class="sw-match" id="sw-match" hidden>
    <div class="sw-match-inner">
        <p class="sw-match-title">Ghép đôi thành công!</p>
        <div class="sw-match-avatars">
            <img id="sw-match-me" src="" alt="">
            <span class="sw-match-heart">
                <svg viewBox="0 0 24 24"><path d="M12 20.5s-7-4.3-7-9.1A4.4 4.4 0 0 1 12 8a4.4 4.4 0 0 1 7 3.4c0 4.8-7 9.1-7 9.1z"/></svg>
            </span>
            <img id="sw-match-you" src="" alt="">
        </div>
        <p class="sw-match-text">Bạn và <b id="sw-match-name"></b> đã thích nhau!</p>
        <div class="sw-match-actions">
            <a class="btn btn-primary" id="sw-match-chat" href="<?= site_url('tai-khoan/tin-nhan') ?>">Nhắn tin ngay</a>
            <button type="button" class="btn btn-ghost" data-close-match>Tiếp tục vuốt</button>
        </div>
    </div>
</div>

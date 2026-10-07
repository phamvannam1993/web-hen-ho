<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Thẻ một thành viên — ứng với `ProfileCard` trong bản thiết kế.
 *
 * Nạp:  $this->load->view('account/_person', array('p' => $row, 'o' => array(...)));
 *
 * $p  một dòng users (cần: id, slug, display_name/full_name, avatar, gender,
 *     birthday, province_name, job, marital_status, last_active_at).
 * $o  tuỳ chọn, đều không bắt buộc:
 *     compact  bool    bản hàng ngang (ảnh nhỏ bên trái), dùng trên điện thoại
 *     matched  bool    đã ghép đôi: hiện nhãn và nút "Nhắn tin"
 *     locked   bool    ảnh mờ, ẩn tên/khu vực (người chưa phải VIP xem danh sách bị khoá)
 *     action   string  'like' (mặc định) | 'view' | 'none'
 *                      'like' dùng bộ nút chung `[data-card-action]` của app.js
 *     label    string  chữ trên nút chính (mặc định "Thích")
 *     pass     bool    có nút ✕ Bỏ qua hay không (mặc định có, trừ khi đã ghép đôi)
 *     meta     string  một dòng chú thích thêm dưới nghề nghiệp (đã escape sẵn)
 *     chat_url string  đường dẫn nút "Nhắn tin" (mặc định trang Tin nhắn)
 */
$o = isset($o) ? $o : array();
$compact = !empty($o['compact']);
$matched = !empty($o['matched']);
$locked  = !empty($o['locked']);
$liked   = !empty($p['liked']);
$action  = $o['action'] ?? 'like';
$label   = $o['label'] ?? 'Thích';
$pass    = array_key_exists('pass', $o) ? (bool) $o['pass'] : !$matched;

$hon_nhan = array('doc_than' => 'Độc thân', 'ly_hon' => 'Đã ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');

$ten    = display_name($p);
$tuoi   = age_from($p['birthday'] ?? null);
$online = !empty($p['last_active_at']) && strtotime($p['last_active_at']) > time() - 300;
$khu    = $p['province_name'] ?? '';
$nghe   = trim(implode(' • ', array_filter(array(
    $p['job'] ?? '',
    $hon_nhan[$p['marital_status'] ?? ''] ?? '',
))));
$link   = site_url('profile/' . ($p['slug'] ?? $p['id']));
$anh    = avatar_url($p['avatar'] ?? null, $p['gender'] ?? 'other');
$chat   = $o['chat_url'] ?? site_url('tai-khoan/tin-nhan');

$tieu_de = $locked ? 'Hồ sơ bị giới hạn' : $ten . ($tuoi ? ', ' . $tuoi : '');
?>
<article class="tk-person<?= $compact ? ' tk-person--compact' : '' ?><?= $locked ? ' is-locked' : '' ?>"
         data-user="<?= (int) $p['id'] ?>">
    <?php if ($compact): ?><div class="tk-person__row"><?php endif; ?>

    <a class="tk-person__ph" href="<?= $locked ? site_url('tai-khoan/nap-xu') : $link ?>"
       <?= $locked ? 'aria-label="Nâng cấp VIP để xem"' : '' ?>>
        <img src="<?= e($anh) ?>" alt="<?= $locked ? '' : e($ten) ?>" loading="lazy">
        <?php if ($locked && !$compact): ?>
            <span class="tk-person__lock"><span><?= tk_icon('lock') ?>Chỉ thành viên VIP xem được</span></span>
        <?php endif; ?>
        <?php if (!$compact && ($online || $matched)): ?>
            <span class="tk-person__tags">
                <?php if ($online && !$locked): ?><span class="tk-pill tk-pill--success"><span class="tk-dot tk-dot--on"></span>Đang online</span><?php endif; ?>
                <?php if ($matched): ?><span class="tk-pill tk-pill--brand"><?= tk_icon('heart') ?>Đã ghép đôi</span><?php endif; ?>
            </span>
        <?php endif; ?>
    </a>

    <div class="tk-person__b">
        <div class="tk-row" style="flex-wrap:nowrap;gap:6px">
            <h3 class="tk-person__n">
                <?php if ($locked): ?><?= e($tieu_de) ?><?php else: ?><a href="<?= $link ?>"><?= e($tieu_de) ?></a><?php endif; ?>
            </h3>
            <?php if ($compact && $matched): ?><span class="tk-pill tk-pill--brand" style="flex:0 0 auto"><?= tk_icon('heart') ?>Đã ghép đôi</span><?php endif; ?>
        </div>
        <div class="tk-person__m">
            <p>
                <?php if ($compact): ?>
                    <span class="tk-dot<?= $online && !$locked ? ' tk-dot--on' : '' ?>"></span>
                <?php else: ?>
                    <?= tk_icon('pin') ?>
                <?php endif; ?>
                <span><?= $locked ? 'Đã ẩn' : e($khu ?: 'Chưa rõ khu vực') ?></span>
            </p>
            <p><?= tk_icon('briefcase') ?><span><?= $locked ? 'Đã ẩn' : e($nghe ?: 'Chưa cập nhật') ?></span></p>
            <?php if (!empty($o['meta'])): ?><p><?= tk_icon('clock') ?><span><?= $o['meta'] ?></span></p><?php endif; ?>
        </div>

        <?php if ($action !== 'none'): ?>
            <div class="tk-person__act">
                <?php if ($matched): ?>
                    <a class="tk-btn tk-btn--brand tk-btn--sm" href="<?= e($chat) ?>"><?= tk_icon('message') ?>Nhắn tin</a>
                <?php elseif ($locked): ?>
                    <a class="tk-btn tk-btn--brand tk-btn--sm" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('crown') ?>Nâng cấp VIP</a>
                <?php elseif ($action === 'view'): ?>
                    <a class="tk-btn tk-btn--brand tk-btn--sm" href="<?= $link ?>"><?= tk_icon('eye') ?><?= e($label) ?></a>
                <?php else: ?>
                    <button type="button" class="tk-btn tk-btn--brand tk-btn--sm<?= $liked ? ' is-liked' : '' ?>" data-card-action="like" data-like-label="<?= e($label) ?>">
                        <?= tk_icon('heart') ?><span class="js-like-text"><?= $liked ? 'Đã thích' : e($label) ?></span>
                    </button>
                <?php endif; ?>
                <?php if (!$matched && !$locked): ?>
                    <a class="tk-btn tk-btn--outline tk-btn--icon-sm" href="<?= e($chat) ?>" aria-label="Nhắn tin" title="Nhắn tin"><?= tk_icon('message') ?></a>
                <?php endif; ?>
                <?php if ($pass && !$locked): ?>
                    <button type="button" class="tk-btn tk-btn--outline tk-btn--icon-sm" data-card-action="pass" aria-label="Bỏ qua" title="Bỏ qua">
                        <?= tk_icon('x') ?>
                    </button>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($compact): ?></div><?php endif; ?>
</article>

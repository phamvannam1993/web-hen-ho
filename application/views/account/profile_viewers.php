<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Ai đã xem hồ sơ — dựng theo `src/routes/tai-khoan.ai-xem-ho-so.tsx`.
 * @var array $list @var int $tong @var bool $la_vip
 *
 * Nút Thả tim dùng bộ nút chung `[data-card-action]` của app.js
 * (cần `data-user` trên dòng chứa nút).
 */
// Người thường thấy rõ 2 người đầu, còn lại làm mờ
$so_ro = $la_vip ? count($list) : 2;
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Ai đã xem hồ sơ</h1>
        <p>Dữ liệu trong 7 ngày gần nhất.</p>
    </div>
    <div class="tk-ph__act"><span class="tk-pill tk-pill--brand"><?= number_format($tong) ?> người xem</span></div>
</header>

<?php if (empty($list)): ?>
    <div class="tk-empty">
        <span class="tk-empty__ic"><?= tk_icon('eye') ?></span>
        <h3>Chưa có ai xem hồ sơ bạn tuần này</h3>
        <p>Cập nhật ảnh đại diện và lời giới thiệu để hồ sơ nổi bật hơn.</p>
        <div class="tk-empty__act"><a class="tk-btn tk-btn--brand" href="<?= site_url('tai-khoan/ho-so') ?>">Cập nhật hồ sơ</a></div>
    </div>
<?php else: ?>
    <section class="tk-card">
        <ul class="tk-list tk-pv-list">
            <?php foreach ($list as $i => $m):
                $ro    = $i < $so_ro;
                $tuoi  = age_from($m['birthday']);
                $link  = site_url('profile/' . $m['slug']);
                $lan   = (int) ($m['view_count'] ?? 1);
                $ghi_chu = 'Đã xem ' . time_ago($m['viewed_at']) . ($lan > 1 ? ' · ' . $lan . ' lần' : '');
            ?>
                <li data-user="<?= (int) $m['id'] ?>">
                    <?php if ($ro): ?>
                        <a class="tk-pv-av" href="<?= $link ?>">
                            <img class="tk-avatar" src="<?= e(avatar_url($m['avatar'], $m['gender'])) ?>" alt="<?= e(display_name($m)) ?>" loading="lazy">
                        </a>
                        <div class="tk-pv-b">
                            <a class="tk-pv-n" href="<?= $link ?>"><?= e(display_name($m)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></a>
                            <p class="tk-pv-t"><?= e($ghi_chu) ?></p>
                        </div>
                        <button type="button" class="tk-btn tk-btn--brand tk-btn--sm" data-card-action="like" data-like-label="Thả tim">
                            <?= tk_icon('heart') ?><span class="js-like-text">Thả tim</span>
                        </button>
                    <?php else: ?>
                        <span class="tk-pv-av is-locked">
                            <img class="tk-avatar" src="<?= e(avatar_url($m['avatar'], $m['gender'])) ?>" alt="" loading="lazy">
                            <span class="tk-pv-lock"><?= tk_icon('lock') ?></span>
                        </span>
                        <div class="tk-pv-b">
                            <p class="tk-pv-n">Hồ sơ bị giới hạn</p>
                            <p class="tk-pv-t"><?= e($ghi_chu) ?></p>
                        </div>
                        <a class="tk-btn tk-btn--soft tk-btn--sm" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('crown') ?>Mở khoá</a>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </section>

    <?php if (!$la_vip): ?>
        <section class="tk-card tk-pv-vip">
            <div style="min-width:0">
                <h2>Xem toàn bộ danh sách với VIP</h2>
                <p>Thành viên thường chỉ xem rõ <?= min(2, count($list)) ?> người xem gần nhất. VIP xem được mọi lượt xem trong 7 ngày.</p>
            </div>
            <a class="tk-btn tk-btn--gold tk-btn--lg" href="<?= site_url('tai-khoan/nap-xu') ?>"><?= tk_icon('crown') ?>Nâng cấp VIP</a>
        </section>
    <?php endif; ?>
<?php endif; ?>

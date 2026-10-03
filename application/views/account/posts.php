<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Tin đăng của tôi — bản thiết kế không có trang này, dựng bằng bộ kit
 * cho đồng bộ với các trang tài khoản khác.
 */

// trạng thái tin => (nhãn, sắc thái pill)
$tt_tin = array(
    'approved' => array('Đã duyệt', 'success'),
    'pending'  => array('Chờ duyệt', 'warning'),
    'rejected' => array('Bị từ chối', 'danger'),
    'expired'  => array('Hết hạn', ''),
    'hidden'   => array('Đã ẩn', ''),
    'draft'    => array('Nháp', ''),
);
$so_ngay = (int) setting('post_expire_days', 30);
?>
<header class="tk-ph">
    <div class="tk-ph__b">
        <h1>Tin đăng của tôi</h1>
        <p>Quản lý tin hẹn hò bạn đã đăng và theo dõi trạng thái duyệt.</p>
    </div>
    <div class="tk-ph__act">
        <a class="tk-btn tk-btn--brand" href="<?= site_url('dang-tin') ?>"><?= tk_icon('plus') ?><span class="tk-only-desktop">Đăng tin mới</span><span class="tk-only-mobile">Đăng tin</span></a>
    </div>
</header>

<section class="tk-card">
    <div class="tk-card__h">
        <div>
            <h2 class="tk-card__t">Danh sách tin</h2>
            <p class="tk-card__d"><?= count($posts) ?> tin • Tin được duyệt trước khi hiển thị và tự hết hạn sau <?= $so_ngay ?> ngày</p>
        </div>
    </div>

    <?php if (empty($posts)): ?>
        <div class="tk-empty">
            <span class="tk-empty__ic"><?= tk_icon('file') ?></span>
            <h3>Bạn chưa có tin nào</h3>
            <p>Hãy đăng tin đầu tiên để bắt đầu kết bạn.</p>
            <a class="tk-empty__act tk-btn tk-btn--brand" href="<?= site_url('dang-tin') ?>"><?= tk_icon('plus') ?>Đăng tin mới</a>
        </div>
    <?php else: ?>
        <ul class="tk-list tk-ps-list">
            <?php foreach ($posts as $p): ?>
                <?php $tt = $tt_tin[$p['status']] ?? array($p['status'], ''); ?>
                <li class="tk-ps-item">
                    <a class="tk-ps-item__ph" href="<?= site_url('tin/' . $p['slug']) ?>" aria-hidden="true" tabindex="-1">
                        <?php if (!empty($p['cover'])): ?>
                            <img src="<?= e(base_url(ltrim($p['cover'], '/'))) ?>" alt="" loading="lazy">
                        <?php else: ?>
                            <?= tk_icon('image') ?>
                        <?php endif; ?>
                    </a>
                    <div class="tk-ps-item__b">
                        <div class="tk-ps-item__top">
                            <a class="tk-ps-item__t" href="<?= site_url('tin/' . $p['slug']) ?>"><?= e($p['title']) ?></a>
                            <span class="tk-pill<?= $tt[1] ? ' tk-pill--' . $tt[1] : '' ?>"><?= e($tt[0]) ?></span>
                        </div>
                        <p class="tk-ps-item__m">
                            <?php if (!empty($p['category_name'])): ?><span><?= tk_icon('file') ?><?= e($p['category_name']) ?></span><?php endif; ?>
                            <span><?= tk_icon('eye') ?><?= number_format($p['view_count']) ?> lượt xem</span>
                            <span><?= tk_icon('clock') ?><?= $p['expired_at'] ? 'Hết hạn ' . date('d/m/Y', strtotime($p['expired_at'])) : 'Chưa có hạn' ?></span>
                        </p>
                        <?php if ($p['status'] === 'rejected' && $p['reject_reason']): ?>
                            <p class="tk-ps-item__why"><?= tk_icon('alert') ?><span>Lý do: <?= e($p['reject_reason']) ?></span></p>
                        <?php endif; ?>
                    </div>
                    <div class="tk-ps-item__acts">
                        <a class="tk-btn tk-btn--outline tk-btn--sm" href="<?= site_url('tai-khoan/sua-tin/' . $p['id']) ?>"><?= tk_icon('edit') ?>Sửa</a>
                        <a class="tk-btn tk-btn--ghost tk-btn--icon-sm tk-ps-del" href="<?= site_url('tai-khoan/xoa-tin/' . $p['id']) ?>"
                           data-confirm="Xoá tin này?" data-confirm-danger aria-label="Xoá tin" title="Xoá tin"><?= tk_icon('trash') ?></a>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>

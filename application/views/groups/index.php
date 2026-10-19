<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/chat-groups.css') ?>?v=<?= filemtime(FCPATH . 'assets/site/css/chat-groups.css') ?>">
<main class="container cg-discovery">
<header class="cg-heading"><p class="cg-eyebrow"><?= tk_icon('message', 'cg-icon') ?> TRÒ CHUYỆN &amp; KẾT NỐI</p><h1>Khám phá nhóm chat</h1><p>Tìm một nhóm hợp với bạn, chia sẻ câu chuyện và làm quen những người bạn mới.</p></header>
<form class="cg-filters" method="get" action="<?= site_url('clubs') ?>">
<input name="q" value="<?= e($q) ?>" placeholder="Tìm tên hoặc mô tả nhóm" aria-label="Tìm nhóm">
<select name="mode" aria-label="Chế độ"><option value="">Mọi nhóm</option><option value="guest" <?= $mode === 'guest' ? 'selected' : '' ?>>Cho khách ẩn danh</option><?php if ($this->auth->check()): ?><option value="public" <?= $mode === 'public' ? 'selected' : '' ?>>Cần tài khoản</option><?php endif; ?></select>
<input name="category" value="<?= e($category) ?>" placeholder="Chủ đề" aria-label="Chủ đề">
<select name="province" aria-label="Khu vực"><option value="">Toàn quốc</option><?php foreach ($provinces as $p): ?><option value="<?= (int) $p['id'] ?>" <?= $province === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option><?php endforeach; ?></select><button><?= tk_icon('search', 'cg-icon') ?> Tìm nhóm</button>
</form>
<div class="cg-catalog"><?php foreach ($groups as $g): ?>
<article class="cg-group-card"><div class="cg-card-head"><img src="<?= base_url($g['avatar'] ?: 'assets/images/' . ($g['identity_mode'] === 'guest' ? 'chat-guest.svg' : 'chat-community.svg')) ?>" alt="">
<?php if ($g['identity_mode'] === 'guest'): ?><span class="cg-tag">Khách ẩn danh</span><?php endif; ?>
</div><h2><?= e($g['name']) ?></h2><p><?= e($g['description']) ?></p>
<div class="cg-card-meta"><span><i class="cg-online-dot" aria-hidden="true"></i><?= (int) $g['online'] ?> đang online<?= $g['status'] === 'readonly' ? ' · Chỉ đọc' : '' ?></span>
<?php if ($g['last_activity_at']): ?><span>Hoạt động <?= e(time_ago($g['last_activity_at'])) ?></span><?php endif; ?></div>
<div class="cg-card-actions"><a class="cg-button" href="<?= site_url('clubs/' . $g['slug']) ?>">Mở nhóm <?= tk_icon('chevron-right', 'cg-icon') ?></a></div></article>
<?php endforeach; ?></div>
<?php if (!$groups): ?><p class="cg-empty">Chưa tìm thấy nhóm phù hợp. Thử đổi bộ lọc hoặc <a href="<?= site_url('clubs') ?>">xem tất cả nhóm</a>.</p><?php endif; ?>
<div class="cg-pagination"><?php foreach (array($page - 1 => 'Trang trước', $page + 1 => 'Xem thêm') as $n => $label): if ($n < 1 || ($n > $page && !$more)) continue; ?><a href="<?= e(site_url('clubs') . '?' . http_build_query(array('q'=>$q,'mode'=>$mode,'category'=>$category,'province'=>$province,'page'=>$n))) ?>"><?= $label ?></a><?php endforeach; ?></div>
</main>

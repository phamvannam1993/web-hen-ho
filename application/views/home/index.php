<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>

<?php /* Nút "Đăng ký hẹn hò" chỉ hiện khi bật lại tính năng đăng tin
         (Quản trị -> Cấu hình -> Kiểm duyệt -> Bật tính năng đăng tin hẹn hò). */ ?>
<?php if (!empty($settings['enable_posts'])): ?>
<section class="hero">
    <div class="container">
        <a class="hero-cta" href="<?= site_url('dang-tin') ?>">Đăng ký hẹn hò</a>
    </div>
</section>
<?php endif; ?>

<?php /* Thanh tìm kiếm ở đầu trang chủ đang TẠM ẨN.
         Bỏ dấu chú thích của khối dưới đây là hiện lại.

<section class="search-bar">
    <div class="container">
        <form method="get" action="<?= site_url('tim-kiem') ?>" class="search-form">
            <input type="text" name="q" value="<?= e($this->input->get('q')) ?>" placeholder="Nhập tên, khu vực hoặc mô tả bạn muốn tìm...">
            <select name="province_id">
                <option value="">Toàn quốc</option>
                <?php foreach ($provinces as $p): ?>
                    <option value="<?= (int) $p['id'] ?>" <?= $this->input->get('province_id') == $p['id'] ? 'selected' : '' ?>><?= e($p['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-search">Tìm kiếm</button>
        </form>
    </div>
</section>

*/ ?>

<?php if (!empty($suggestions)): ?>
<section class="home-block alt">
    <div class="container">
        <h2 class="block-title">Gợi ý ghép đôi cho bạn</h2>
        <p class="block-sub">Sắp xếp theo mức tương hợp với hồ sơ và tiêu chí của bạn.</p>
        <div class="member-grid is-compact">
            <?php foreach ($suggestions as $m): ?>
                <?php $this->load->view('members/_card', array('m' => $m)); ?>
            <?php endforeach; ?>
        </div>
        <div class="block-more">
            <a class="btn btn-more" href="<?= site_url('kham-pha') ?>">Khám phá thêm →</a>
        </div>
    </div>
</section>
<?php endif; ?>

<?php foreach ($sections as $section): ?>
    <section class="home-block">
        <div class="container">
            <h2 class="block-title"><?= e($section['label']) ?></h2>
            <div class="member-grid is-compact">
                <?php foreach ($section['members'] as $m): ?>
                    <?php $this->load->view('members/_card', array('m' => $m)); ?>
                <?php endforeach; ?>
            </div>
            <div class="block-more">
                <a class="btn btn-more" href="<?= site_url('thanh-vien') ?>?purpose=<?= e($section['key']) ?>">Xem thêm →</a>
            </div>
        </div>
    </section>
<?php endforeach; ?>

<section class="home-block">
    <div class="container">
        <h2 class="block-title">Thành viên nổi bật</h2>
        <?php if (empty($online_members)): ?>
            <p class="empty">Chưa có thành viên nào.</p>
        <?php else: ?>
            <div class="member-grid is-compact">
                <?php foreach ($online_members as $m): ?>
                    <?php $this->load->view('members/_card', array('m' => $m)); ?>
                <?php endforeach; ?>
            </div>
            <div class="block-more">
                <a class="btn btn-more" href="<?= site_url('thanh-vien') ?>">Xem tất cả →</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($new_members)): ?>
<section class="home-block alt">
    <div class="container">
        <h2 class="block-title">Thành viên mới tham gia</h2>
        <div class="member-grid is-compact">
            <?php foreach ($new_members as $m): ?>
                <?php $this->load->view('members/_card', array('m' => $m)); ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<section class="home-block alt">
    <div class="container">
        <h2 class="block-title">Khu vực nổi bật</h2>
        <div class="province-cloud">
            <?php foreach ($top_provinces as $p): ?>
                <a href="<?= site_url($p['slug']) ?>">
                    <?= e($p['name']) ?> <span>(<?= (int) $p['member_count'] ?>)</span>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php if (!empty($articles)): ?>
<section class="home-block">
    <div class="container">
        <h2 class="block-title">Cẩm nang hẹn hò</h2>
        <div class="article-grid">
            <?php foreach ($articles as $a): ?>
                <article class="article-card">
                    <a class="article-thumb" href="<?= site_url('tin-tuc/' . $a['slug']) ?>">
                        <img src="<?= $a['thumbnail'] ? base_url(ltrim($a['thumbnail'], '/')) : base_url('assets/site/img/placeholder.svg') ?>" alt="<?= e($a['title']) ?>">
                    </a>
                    <div class="article-body">
                        <h3><a href="<?= site_url('tin-tuc/' . $a['slug']) ?>"><?= e($a['title']) ?></a></h3>
                    <p><?= e(excerpt($a['excerpt'] ?: $a['content'], 110)) ?></p>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

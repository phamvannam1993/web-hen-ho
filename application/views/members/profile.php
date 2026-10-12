<?php defined('BASEPATH') OR exit('No direct script access allowed');
$age = age_from($m['birthday']);
$marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$edu = array('thpt' => 'THPT', 'trung_cap' => 'Trung cấp', 'cao_dang' => 'Cao đẳng',
             'dai_hoc' => 'Đại học', 'sau_dai_hoc' => 'Sau đại học');
$is_me = $user && (int) $user['id'] === (int) $m['id'];
$profile_cover = base_url(!empty($m['cover_image']) ? ltrim($m['cover_image'], '/') : 'assets/site/images/profile-cover.jpg');
$pp_icon = function ($name) {
    $paths = array(
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M5 21v-2a7 7 0 0 1 14 0v2"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.7l-1.1-1.1a5.5 5.5 0 0 0-7.8 7.8L12 21l8.8-8.6a5.5 5.5 0 0 0 0-7.8Z"/>',
        'lock' => '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4m-4 4v2"/>',
        'photos' => '<rect x="5" y="3" width="16" height="16" rx="2"/><circle cx="10" cy="8" r="1"/><path d="m21 14-5-5L5 19M1 7v14a2 2 0 0 0 2 2h14"/>',
        'message' => '<path d="M21 11.5a8.5 8.5 0 0 1-8.5 8.5 9 9 0 0 1-4-.9L3 21l1.9-5.5a9 9 0 0 1-.9-4A8.5 8.5 0 0 1 12.5 3 8.5 8.5 0 0 1 21 11.5Z"/>',
        'sparkles' => '<path d="m12 3 2.7 6.3L21 12l-6.3 2.7L12 21l-2.7-6.3L3 12l6.3-2.7L12 3ZM5 3v4M3 5h4m12 12v4m-2-2h4"/>',
        'pin' => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'share' => '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.6 10.5 6.8-4m-6.8 7 6.8 4"/>',
    );
    return '<svg class="pp-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($paths[$name] ?? $paths['user']) . '</svg>';
};
?>

<link rel="stylesheet" href="<?= base_url('assets/site/css/public-profile.css') ?>?v=<?= filemtime(FCPATH.'assets/site/css/public-profile.css') ?>">
<div class="container public-profile" data-public-profile>
    <nav class="pp-breadcrumb" aria-label="Đường dẫn"><a href="<?= site_url('hen-ho') ?>">← Khám phá hồ sơ</a><span>Hồ sơ cá nhân › <?= e(display_name($m)) ?></span></nav>
                <?php
            /* Khung hồ sơ dạng bảng: mỗi nhóm một thẻ, nhãn bên trái, giá trị bên phải.
               Giá trị nào là thông tin phân loại (giới tính, nơi ở, mục tiêu…) thì tô
               xanh cho dễ quét mắt; thông tin liên hệ thì che lại nếu chưa đăng nhập. */
            $freq  = array('khong' => 'Không', 'thinh_thoang' => 'Thỉnh thoảng', 'thuong_xuyen' => 'Thường xuyên');
            $nam_sinh = $m['birthday'] ? date('Y', strtotime($m['birthday'])) : null;
            $muc_tieu = !empty($prefs['purpose']) ? purpose_label($prefs['purpose']) : null;

            // [nhãn, giá trị, có tô xanh không]
            $nhom_chinh = array(
                array('Họ tên',   display_name($m), false),
                array('Giới tính', gender_label($m['gender']), true),
                array('Năm sinh', $nam_sinh ? $nam_sinh . ($age ? ' (' . $age . ' tuổi)' : '') : null, false),
                array('Nghề nghiệp', $m['job'], false),
                array('Mục tiêu', $muc_tieu, true),
            );
            $nhom_noi_o = array(
                array('Chỗ ở hiện tại', $m['province_name'], true),
                array('Học vấn',        $m['education'] ? ($edu[$m['education']] ?? null) : null, true),
                array('Hôn nhân',       $m['marital_status'] ? ($marital[$m['marital_status']] ?? null) : null, true),
                array('Con cái',        (int) $m['has_children'] === 1 ? 'Đã có con' : 'Chưa có con', true),
            );
            $nhom_khac = array(
                array('Ngoại hình', trim(($m['height_cm'] ? 'Cao ' . (int) $m['height_cm'] . ' cm' : '')
                    . ($m['weight_kg'] ? ($m['height_cm'] ? ' · ' : '') . 'Nặng ' . (int) $m['weight_kg'] . ' kg' : '')), false),
                array('Hút thuốc',     $m['smoking']  ? ($freq[$m['smoking']]  ?? null) : null, false),
                array('Uống rượu bia', $m['drinking'] ? ($freq[$m['drinking']] ?? null) : null, false),
                array('Sở thích', $interests ? implode(', ', array_column($interests, 'name')) : null, false),
            );

            /** Đổ một nhóm ra bảng, bỏ qua dòng không có dữ liệu. */
            $ve_nhom = function ($rows) {
                $co = array_filter($rows, function ($r) { return $r[1] !== null && $r[1] !== ''; });
                if (!$co) return;
                echo '<section class="fact-card"><dl class="fact-list">';
                foreach ($co as $r) {
                    echo '<div><dt>' . e($r[0]) . '</dt><dd' . ($r[2] ? ' class="is-key"' : '') . '>'
                       . e($r[1]) . '</dd></div>';
                }
                echo '</dl></section>';
            };
            ?>


    <div class="pp-layout">
        <aside class="pp-left">
            <section class="pp-identity"><div class="pp-mini-cover"><img src="<?= e($profile_cover) ?>" alt=""></div><header class="profile-head">
                <?php
                $p_online = is_online($m['last_active_at']);
                $p_new    = !empty($m['created_at']) && strtotime($m['created_at']) > strtotime('-7 days');
                ?>
                <div class="profile-photo">
    <button type="button" class="profile-image-trigger" data-profile-image aria-label="Xem ảnh đại diện lớn hơn">
        <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>">
    </button>
    <?php if ($p_online): ?>
        <span class="dot-online" title="Đang hoạt động"></span>
    <?php endif; ?>
</div>
                <div class="profile-headline">
                    <h1><?= e(display_name($m)) ?></h1>
                    <p class="profile-tags">
                        <!--<?php if ($p_online): ?><span class="tag tag-online">Online</span><?php endif; ?>-->
                        <!--<?php if ($p_new): ?><span class="tag tag-new">Mới tham gia</span><?php endif; ?>-->
                        <span><?= gender_label($m['gender']) ?></span>
                        <?php if ($age): ?><span><?= $age ?> tuổi</span><?php endif; ?>
                        <?php if (!empty($m['province_name'])): ?><span><?= e($m['province_name']) ?></span><?php endif; ?>
                        <!--<?php if ($m['province_name']): ?><span><?= e($m['province_name']) ?></span><?php endif; ?>-->
                        <?php if ($m['is_vip']): ?><span class="tag-vip">VIP</span><?php endif; ?>
                        <?php if ($m['kyc_status'] === 'verified'): ?><span class="tag-verified">Đã xác minh</span><?php endif; ?>
                    </p>
                    <p class="profile-active">
                        <?= is_online($m['last_active_at']) ? 'Đang online' : 'Hoạt động ' . time_ago($m['last_active_at']) ?>
                        · <?= number_format($like_count) ?> lượt thích
                    </p>

                    <?php if (!$is_me): ?>
                        <div class="profile-actions <?= $user ? 'profile-actions--connect' : '' ?>">
                            <?php if ($user): ?>
                                <?php // Cùng cấu trúc với thứ JS dựng lại sau khi bấm,
                                      // để trạng thái trước và sau khi tải lại trang giống nhau ?>
                                <button class="btn btn-primary btn-like-toggle <?= $liked ? 'is-liked' : '' ?>"
        type="button" data-like-user="<?= (int) $m['id'] ?>" data-like-label="<?= $liked_me ? 'Thích lại' : 'Thích' ?>">
    <svg viewBox="0 0 24 24" class="ic">
        <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
    </svg>
    <span class="js-like-text"><?= $liked ? 'Đã thích' : ($liked_me ? 'Thích lại' : 'Thích') ?></span>
</button>
                                    <button class="btn btn-blue-outline" type="button" data-chat-with="<?= (int) $m['id'] ?>"><?= $pp_icon('message') ?>Nhắn tin</button>
                                <button class="btn btn-ghost" type="button" data-report-user="<?= (int) $m['id'] ?>">Báo cáo</button>
                            <?php else: ?>
                                <a class="btn btn-primary" href="<?= site_url('dang-nhap') ?>">Đăng nhập để kết nối</a>
                            <?php endif; ?>
                        </div>
                        <?php if ($matched): ?>
                            <p class="matched-note">Hai bạn đã ghép đôi — hãy bắt đầu trò chuyện!</p>
                        <?php elseif ($user): ?>
                            <p class="matched-note matched-note-wait">
                                <?= $liked
                                    ? 'Bạn đã gửi lượt thích. Hãy nhắn tin để làm quen!'
                                    : 'Bạn có thể nhắn tin ngay để làm quen hoặc bấm Thích để gửi lời quan tâm.' ?>
                            </p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="profile-actions">
                            <a class="btn btn-primary" href="<?= site_url('tai-khoan/ho-so') ?>">Sửa hồ sơ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </header></section>
            <section class="pp-panel pp-basic"><h2><span class="pp-heading-icon"><?= $pp_icon('user') ?></span>Thông tin cơ bản</h2>
                <?php $ve_nhom(array_merge($nhom_chinh, $nhom_noi_o)); ?>
                <details><summary>Xem tất cả thông tin</summary><?php $ve_nhom($nhom_khac); ?></details>
            </section>
            <section class="pp-panel pp-trust"><h2>♡ Kết nối bằng sự chân thành</h2><p>Tôn trọng thông tin riêng tư của nhau. Không chia sẻ thông tin cá nhân khi chưa thực sự tin tưởng.</p></section>
        </aside>
        <main class="pp-main">
            <section class="pp-overview pp-panel">
                <div class="pp-cover"><img src="<?= e($profile_cover) ?>" alt="Ảnh bìa của <?= e(display_name($m)) ?>"><span><?= $pp_icon('pin') ?><?= e($m['province_name'] ?: 'Việt Nam') ?></span><button type="button" class="pp-share" data-profile-share aria-label="Sao chép liên kết hồ sơ" title="Chia sẻ hồ sơ"><?= $pp_icon('share') ?></button></div>
                <div class="pp-title"><h2>Hẹn hò kết bạn với <?= e(display_name($m)) ?></h2><p>Một kết nối mới, một câu chuyện mới.</p></div>
                <div class="pp-tabs" role="tablist" aria-label="Nội dung hồ sơ">
                    <button type="button" role="tab" id="pp-tab-about" aria-selected="true" aria-controls="pp-about" data-profile-tab="about"><?= $pp_icon('user') ?>Giới thiệu</button>
                    <button type="button" role="tab" id="pp-tab-photos" aria-selected="false" aria-controls="pp-photos" data-profile-tab="photos" tabindex="-1"><?= $pp_icon('photos') ?>Ảnh</button>
                    <button type="button" role="tab" id="pp-tab-posts" aria-selected="false" aria-controls="pp-posts" data-profile-tab="posts" tabindex="-1"><?= $pp_icon('message') ?>Bài viết</button>
                </div>
            </section>
            <p class="pp-share-status" role="status" data-profile-share-status></p>
            <div id="pp-about" role="tabpanel" aria-labelledby="pp-tab-about">
                <section class="pp-panel pp-about"><h2><span class="pp-heading-icon"><?= $pp_icon('sparkles') ?></span>Về mình</h2><h3>Đôi lời giới thiệu</h3><p><?= !empty($m['bio']) ? nl2br(e($m['bio'])) : 'Chưa có lời giới thiệu.' ?></p>
                    <div class="pp-about-facts"><div><h3>Bạn đang tìm kiếm ai?</h3><p><?= e($muc_tieu ?: 'Chưa cập nhật') ?></p></div><div><h3>Con cái</h3><p><?= (int) $m['has_children'] === 1 ? 'Đã có con' : 'Chưa có con' ?></p></div></div>
                </section>
                <?php if (!$is_me): ?><section class="pp-panel pp-contact"><h2><span class="pp-heading-icon"><?= $pp_icon('lock') ?></span>Thông tin liên hệ</h2>                <?php if (!$is_me): ?>
                    <section class="fact-card">
                        <dl class="fact-list">
                            <div><dt>Số điện thoại</dt><dd>
                                <?php if ($user): ?><?= e(mask_contact($m['phone'])) ?>
                                <?php else: ?><a class="is-key" href="<?= site_url('dang-ky') ?>">Đăng ký để xem</a><?php endif; ?>
                            </dd></div>
                            <div><dt>Zalo</dt><dd>
                                <?php if ($user): ?><?= e(mask_contact($m['phone'])) ?>
                                <?php else: ?><a class="is-key" href="<?= site_url('dang-ky') ?>">Đăng ký để xem</a><?php endif; ?>
                            </dd></div>
                        </dl>
                    </section>
                <?php endif; ?>

</section><?php endif; ?>
                <section class="pp-panel"><h2>Sở thích</h2><div class="pp-interests"><?php foreach ($interests as $interest): ?><span><?= e($interest['name']) ?></span><?php endforeach; ?><?php if (!$interests): ?><p>Chưa cập nhật sở thích.</p><?php endif; ?></div></section>
            </div>
            <div id="pp-photos" role="tabpanel" aria-labelledby="pp-tab-photos" hidden>
                <section class="pp-panel"><h2>Album ảnh</h2><?php if (!$photos): ?><p><?= $user ? 'Chưa có ảnh trong album.' : 'Đăng nhập để xem ảnh trong album.' ?></p><?php if (!$user): ?><a class="btn btn-primary" href="<?= site_url('dang-nhap') ?>">Đăng nhập để xem ảnh</a><?php endif; ?><?php endif; ?>            <?php if ($photos): ?>
                <section class="profile-section">
                    <h2 class="info-heading">Album ảnh</h2>
                    <div class="photo-grid">
                        <?php foreach ($photos as $ph): ?>
                            <figure><button type="button" class="profile-image-trigger" data-profile-image aria-label="Xem ảnh trong album lớn hơn"><img src="<?= e(base_url(ltrim($ph['path'], '/'))) ?>" alt="Ảnh trong album" loading="lazy"></button></figure>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

</section>
            </div>
            <div id="pp-posts" role="tabpanel" aria-labelledby="pp-tab-posts" hidden><section class="pp-panel"><h2><span class="pp-heading-icon"><?= $pp_icon('message') ?></span>Bài viết</h2><?php if (!$posts): ?><div class="pp-posts-empty"><?= $pp_icon('message') ?><h3>Chưa có bài viết</h3><p>Các bài viết được chia sẻ sẽ xuất hiện tại đây.</p></div><?php endif; ?>            <?php if ($posts): ?>
                <section class="profile-section">
                    <h2 class="info-heading">Tin đăng của <?= e(display_name($m)) ?></h2>
                    <div class="card-grid">
                        <?php foreach ($posts as $p): ?>
                            <?php $this->load->view('posts/_card', array('post' => $p)); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
</section></div>
            <section class="content-box comment-box" id="binh-luan">
            <h2 class="info-heading"><?= count($comments) ?> bình luận</h2>

            <?php if ($user): ?>
                <form class="comment-form" method="post" enctype="multipart/form-data"
                      action="<?= site_url('profile/' . $m['slug'] . '/binh-luan') ?>">
                    <img src="<?= avatar_url($user['avatar'], $user['gender']) ?>" alt="">
                    <div>
                        <textarea name="content" rows="3" placeholder="Viết bình luận của bạn..."></textarea>
                        <div class="comment-bar">
                            <label class="attach-btn" title="Đính kèm ảnh">
                                <input type="file" name="image" accept="image/*" hidden>
                                <span>🖼 Ảnh</span>
                            </label>
                            <button class="btn btn-primary btn-small" type="submit">Gửi bình luận</button>
                        </div>
                    </div>
                </form>
            <?php else: ?>
                <p class="comment-login"><a href="<?= site_url('dang-nhap') ?>">Đăng nhập</a> để bình luận.</p>
            <?php endif; ?>

            <?php
            // gom bình luận thành cây: gốc và các trả lời theo parent_id
            $roots = array();
            $replies = array();
            foreach ($comments as $c) {
                if ($c['parent_id']) {
                    $replies[$c['parent_id']][] = $c;
                } else {
                    $roots[] = $c;
                }
            }

            /** Render một bình luận kèm các trả lời của nó. */
            $render = function ($c, $depth = 0) use (&$render, $replies, $m, $user, $is_me) {
                $can_delete = $user && ((int) $user['id'] === (int) $c['user_id'] || $is_me);
                ?>
                <li class="<?= $depth ? 'is-reply' : '' ?>">
                    <div class="comment-row">
                        <img src="<?= avatar_url($c['avatar'], $c['gender']) ?>" alt="">
                        <div class="comment-content">
                            <div class="comment-bubble">
                                <a class="comment-author" href="<?= site_url('profile/' . $c['user_slug']) ?>"><?= e(display_name($c)) ?></a>
                                <?php if (trim((string) $c['content']) !== ''): ?>
                                    <p><?= nl2br(e($c['content'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($c['image'])): ?>
                                <button class="comment-image" type="button" data-comment-image aria-label="Xem ảnh bình luận lớn hơn">
                                    <img src="<?= base_url(ltrim($c['image'], '/')) ?>" alt="Ảnh bình luận" loading="lazy">
                                </button>
                            <?php endif; ?>
                            <div class="comment-tools">
                                <time><?= time_ago($c['created_at']) ?></time>
                                <?php if ($user): ?>
                                    <button type="button" class="comment-reply-btn" data-reply-to="<?= (int) $c['id'] ?>"
                                            data-reply-name="<?= e(display_name($c)) ?>">Trả lời</button>
                                <?php endif; ?>
                                <?php if ($can_delete): ?>
                                    <a class="comment-del" href="<?= site_url('profile/' . $m['slug'] . '/xoa-binh-luan/' . $c['id']) ?>"
                                       data-confirm="Xoá bình luận này?" data-confirm-danger>Xoá</a>
                                <?php endif; ?>
                            </div>

                            <?php if ($user): ?>
                                <form class="comment-form reply-form" id="reply-form-<?= (int) $c['id'] ?>" method="post"
                                      enctype="multipart/form-data"
                                      action="<?= site_url('profile/' . $m['slug'] . '/binh-luan') ?>" hidden>
                                    <img src="<?= avatar_url($user['avatar'], $user['gender']) ?>" alt="">
                                    <div>
                                        <input type="hidden" name="parent_id" value="<?= (int) $c['id'] ?>">
                                        <textarea name="content" rows="2"
                                                  placeholder="Trả lời <?= e(display_name($c)) ?>..."></textarea>
                                        <div class="comment-bar">
                                            <label class="attach-btn" title="Đính kèm ảnh">
                                                <input type="file" name="image" accept="image/*" hidden>
                                                <span>🖼 Ảnh</span>
                                            </label>
                                            <div class="reply-actions">
                                                <button class="btn btn-primary btn-small" type="submit">Gửi</button>
                                                <button class="btn btn-ghost btn-small" type="button" data-cancel-reply>Huỷ</button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($replies[$c['id']])): ?>
                        <ul class="comment-children">
                            <?php foreach ($replies[$c['id']] as $child) { $render($child, $depth + 1); } ?>
                        </ul>
                    <?php endif; ?>
                </li>
                <?php
            };
            ?>

            <?php if (empty($roots)): ?>
                <p class="empty">Chưa có bình luận nào. Hãy là người đầu tiên!</p>
            <?php else: ?>
                <ul class="comment-thread">
                    <?php foreach ($roots as $c) { $render($c); } ?>
                </ul>
            <?php endif; ?>
        </section>
        </main>
        <aside class="pp-right">                <?php if (!$user): ?>
                    <section class="fact-card fact-cta">
                        <p class="fact-cta-lead"><b><?= e(display_name($m)) ?></b> là hồ sơ
                            <?= $m['is_vip'] ? 'VIP, ' : '' ?>đẹp. Nên không công khai nhiều thông tin.</p>
                        <p>Bạn vui lòng đăng nhập để xem ảnh, xem số điện thoại và trò chuyện
                            với <b><?= e(display_name($m)) ?></b> bạn nhé!</p>
                        <div class="fact-cta-btns">
                            <a class="btn btn-primary" href="<?= site_url('dang-ky') ?>">Đăng ký tài khoản</a>
                            <a class="btn btn-blue-outline" href="<?= site_url('dang-nhap') ?>">Đăng nhập</a>
                        </div>
                    </section>
                <?php endif; ?>        <div class="sidebar-box">
            <h3>Tìm theo khu vực</h3>
            <?php /* Dạng thẻ nhiều cột cho gọn, thay vì danh sách dọc dài lê thê */ ?>
            <p class="area-chips">
                <?php foreach (array_slice($quick_links, 0, 14) as $link): ?>
                    <a href="<?= site_url($link['slug']) ?>"><?= e($link['name']) ?></a>
                <?php endforeach; ?>
            </p>
            <a class="sidebar-more" href="<?= site_url('khu-vuc') ?>">Xem tất cả khu vực →</a>
        </div>
</aside>
    </div>
</div>
<script defer src="<?= base_url('assets/site/js/public-profile.js') ?>?v=<?= filemtime(FCPATH.'assets/site/js/public-profile.js') ?>"></script>

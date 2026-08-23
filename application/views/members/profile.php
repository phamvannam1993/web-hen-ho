<?php defined('BASEPATH') OR exit('No direct script access allowed');
$age = age_from($m['birthday']);
$marital = array('doc_than' => 'Độc thân', 'ly_hon' => 'Ly hôn', 'goa' => 'Goá', 'phuc_tap' => 'Phức tạp');
$edu = array('thpt' => 'THPT', 'trung_cap' => 'Trung cấp', 'cao_dang' => 'Cao đẳng',
             'dai_hoc' => 'Đại học', 'sau_dai_hoc' => 'Sau đại học');
$is_me = $user && (int) $user['id'] === (int) $m['id'];
?>
<div class="container page-layout">
    <div>
        <article class="content-box profile-page">
            <header class="profile-head">
                <div class="profile-photo">
                    <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>">
                    <?php if (is_online($m['last_active_at'])): ?><span class="dot-online"></span><?php endif; ?>
                </div>
                <div class="profile-headline">
                    <h1><?= e(display_name($m)) ?></h1>
                    <p class="profile-tags">
                        <span><?= gender_label($m['gender']) ?></span>
                        <?php if ($age): ?><span><?= $age ?> tuổi</span><?php endif; ?>
                        <?php if ($m['province_name']): ?><span><?= e($m['province_name']) ?></span><?php endif; ?>
                        <?php if ($m['is_vip']): ?><span class="tag-vip">VIP</span><?php endif; ?>
                        <?php if ($m['kyc_status'] === 'verified'): ?><span class="tag-verified">Đã xác minh</span><?php endif; ?>
                    </p>
                    <p class="profile-active">
                        <?= is_online($m['last_active_at']) ? 'Đang online' : 'Hoạt động ' . time_ago($m['last_active_at']) ?>
                        · <?= number_format($like_count) ?> lượt thích
                    </p>

                    <?php if (!$is_me): ?>
                        <div class="profile-actions">
                            <?php if ($user): ?>
                                <button class="btn <?= $liked ? 'btn-ghost' : 'btn-primary' ?>" type="button"
                                        data-like-user="<?= (int) $m['id'] ?>">
                                    <?= $liked ? '♥ Đã thích' : '♥ Thích' ?>
                                </button>
                                <button class="btn btn-blue-outline" type="button" data-chat-with="<?= (int) $m['id'] ?>">Nhắn tin</button>
                                <button class="btn btn-ghost" type="button" data-report-user="<?= (int) $m['id'] ?>">Báo cáo</button>
                            <?php else: ?>
                                <a class="btn btn-primary" href="<?= site_url('dang-nhap') ?>">Đăng nhập để kết nối</a>
                            <?php endif; ?>
                        </div>
                        <?php if ($matched): ?>
                            <p class="matched-note">Hai bạn đã ghép đôi — hãy bắt đầu trò chuyện!</p>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="profile-actions">
                            <a class="btn btn-primary" href="<?= site_url('tai-khoan/ho-so') ?>">Sửa hồ sơ</a>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <section class="info-panel">
                <h2 class="info-heading">Thông tin</h2>
                <dl class="info-list">
                    <?php if ($m['bio']): ?>
                        <div><dt>Giới thiệu</dt><dd><?= nl2br(e($m['bio'])) ?></dd></div>
                    <?php endif; ?>
                    <?php if ($age): ?><div><dt>Tuổi</dt><dd><?= $age ?> tuổi</dd></div><?php endif; ?>
                    <div><dt>Giới tính</dt><dd><?= gender_label($m['gender']) ?></dd></div>
                    <?php if ($m['marital_status']): ?>
                        <div><dt>Hôn nhân</dt><dd><?= e($marital[$m['marital_status']] ?? '') ?></dd></div>
                    <?php endif; ?>
                    <?php if ($m['province_name']): ?>
                        <div><dt>Nơi ở hiện tại</dt><dd><?= e($m['province_name']) ?></dd></div>
                    <?php endif; ?>
                    <?php if ($m['job']): ?><div><dt>Nghề nghiệp</dt><dd><?= e($m['job']) ?></dd></div><?php endif; ?>
                    <?php if ($m['education']): ?>
                        <div><dt>Học vấn</dt><dd><?= e($edu[$m['education']] ?? '') ?></dd></div>
                    <?php endif; ?>
                    <?php if ($m['height_cm'] || $m['weight_kg']): ?>
                        <div><dt>Ngoại hình</dt><dd>
                            <?= $m['height_cm'] ? 'Cao ' . (int) $m['height_cm'] . ' cm' : '' ?>
                            <?= $m['weight_kg'] ? ' · Nặng ' . (int) $m['weight_kg'] . ' kg' : '' ?>
                        </dd></div>
                    <?php endif; ?>
                    <?php if ($interests): ?>
                        <div><dt>Sở thích</dt><dd><?= e(implode(', ', array_column($interests, 'name'))) ?></dd></div>
                    <?php endif; ?>
                </dl>

                <?php if (!$is_me): ?>
                    <div class="contact-row" data-member="<?= e($m['slug']) ?>">
                        <b>Số điện thoại:</b>
                        <button type="button" class="btn-pass" data-action="get-pass">Lấy pass</button>
                        <input type="text" data-field="pass" placeholder="Nhập pass ở đây để lấy số điện thoại">
                        <button type="button" class="btn-confirm" data-action="reveal">Xác nhận</button>
                        <span class="contact-revealed" data-field="result"><?= e(mask_contact($m['phone'])) ?></span>
                    </div>
                    <p class="contact-note">Mỗi lần lấy pass trừ <?= (int) setting('unlock_cost', 20) ?> xu. Thành viên VIP xem miễn phí.</p>
                <?php endif; ?>
            </section>

            <?php if ($photos): ?>
                <section class="profile-section">
                    <h2 class="info-heading">Album ảnh</h2>
                    <div class="photo-grid">
                        <?php foreach ($photos as $ph): ?>
                            <figure><img src="<?= base_url(ltrim($ph['path'], '/')) ?>" alt="" loading="lazy"></figure>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>

            <?php if ($posts): ?>
                <section class="profile-section">
                    <h2 class="info-heading">Tin đăng của <?= e(display_name($m)) ?></h2>
                    <div class="card-grid">
                        <?php foreach ($posts as $p): ?>
                            <?php $this->load->view('posts/_card', array('post' => $p)); ?>
                        <?php endforeach; ?>
                    </div>
                </section>
            <?php endif; ?>
        </article>

        <section class="content-box comment-box" id="binh-luan">
            <h2 class="info-heading"><?= count($comments) ?> bình luận</h2>

            <?php if ($user): ?>
                <form class="comment-form" method="post" enctype="multipart/form-data"
                      action="<?= site_url('thanh-vien/' . $m['slug'] . '/binh-luan') ?>">
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
                                <a class="comment-author" href="<?= site_url('thanh-vien/' . $c['user_slug']) ?>"><?= e(display_name($c)) ?></a>
                                <?php if (trim((string) $c['content']) !== ''): ?>
                                    <p><?= nl2br(e($c['content'])) ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($c['image'])): ?>
                                <a class="comment-image" href="<?= base_url(ltrim($c['image'], '/')) ?>" target="_blank">
                                    <img src="<?= base_url(ltrim($c['image'], '/')) ?>" alt="Ảnh bình luận" loading="lazy">
                                </a>
                            <?php endif; ?>
                            <div class="comment-tools">
                                <time><?= time_ago($c['created_at']) ?></time>
                                <?php if ($user): ?>
                                    <button type="button" class="comment-reply-btn" data-reply-to="<?= (int) $c['id'] ?>"
                                            data-reply-name="<?= e(display_name($c)) ?>">Trả lời</button>
                                <?php endif; ?>
                                <?php if ($can_delete): ?>
                                    <a class="comment-del" href="<?= site_url('thanh-vien/' . $m['slug'] . '/xoa-binh-luan/' . $c['id']) ?>"
                                       data-confirm="Xoá bình luận này?" data-confirm-danger>Xoá</a>
                                <?php endif; ?>
                            </div>

                            <?php if ($user): ?>
                                <form class="comment-form reply-form" id="reply-form-<?= (int) $c['id'] ?>" method="post"
                                      enctype="multipart/form-data"
                                      action="<?= site_url('thanh-vien/' . $m['slug'] . '/binh-luan') ?>" hidden>
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
    </div>

    <aside>
        <div class="sidebar-box">
            <h3><?= e($settings['site_name'] ?? 'Saigon Cupid') ?></h3>
            <p><?= e($settings['site_desc'] ?? '') ?></p>
        </div>
        <div class="sidebar-box">
            <h3>Liên kết nhanh</h3>
            <ul class="sidebar-list">
                <?php foreach (array_slice($quick_links, 0, 20) as $link): ?>
                    <li><a href="<?= site_url('danh-muc/' . $link['slug']) ?>"><?= e($link['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</div>

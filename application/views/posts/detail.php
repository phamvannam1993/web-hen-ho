<?php defined('BASEPATH') OR exit('No direct script access allowed');
$marital = array(
    'doc_than' => 'Độc thân', 'ly_hon' => 'Ly dị', 'goa' => 'Goá',
    'dang_co_nguoi_yeu' => 'Đang có người yêu', 'phuc_tap' => 'Phức tạp',
);
$place = trim(($post['district'] ? $post['district'] . ', ' : '') . ($post['province_name'] ?? ''), ', ');
?>
<div class="container page-layout">
    <div>
        <article class="content-box">
            <h1 class="detail-title"><?= e($post['title']) ?></h1>

            <?php if ($post['cover']): ?>
                <img src="<?= base_url(ltrim($post['cover'], '/')) ?>" alt="<?= e($post['title']) ?>">
            <?php endif; ?>

            <?php if ($post['category_name']): ?>
                <h2 class="detail-category">Chuyên mục: <span><?= e($post['category_name']) ?></span></h2>
            <?php endif; ?>

            <div class="detail-info">
                <p class="info-head">=== THÔNG TIN ===</p>
                <?php if ($post['intro']): ?><p class="info-row"><b>Giới thiệu:</b> <?= e($post['intro']) ?></p><?php endif; ?>
                <?php if ($post['age']): ?><p class="info-row"><b>Tuổi:</b> <?= (int) $post['age'] ?> tuổi</p><?php endif; ?>
                <p class="info-row"><b>Giới tính:</b> <?= gender_label($post['gender']) ?></p>
                <?php if ($post['marital_status']): ?>
                    <p class="info-row"><b>Hôn nhân:</b> <?= e($marital[$post['marital_status']] ?? '') ?></p>
                <?php endif; ?>
                <?php if ($place): ?><p class="info-row"><b>Nơi ở hiện tại:</b> <?= e($place) ?></p><?php endif; ?>
                <?php if ($post['job']): ?><p class="info-row"><b>Nghề nghiệp:</b> <?= e($post['job']) ?></p><?php endif; ?>
                <?php if ($post['wish']): ?><p class="info-row"><b>Mong muốn:</b> <?= e($post['wish']) ?></p><?php endif; ?>
                <?php if ($post['personality']): ?><p class="info-row"><b>Tính cách:</b> <?= e($post['personality']) ?></p><?php endif; ?>
                <?php if ($post['height_cm'] || $post['weight_kg']): ?>
                    <p class="info-row"><b>Ngoại hình:</b>
                        <?= $post['height_cm'] ? 'Cao ' . (int) $post['height_cm'] . ' cm' : '' ?>
                        <?= $post['weight_kg'] ? 'Nặng ' . (int) $post['weight_kg'] . ' kg' : '' ?>
                    </p>
                <?php endif; ?>
            </div>

            <!-- Mở số điện thoại bằng mã pass -->
            <div class="contact-row" data-post="<?= (int) $post['id'] ?>">
                <b>Số điện thoại:</b>
                <?php if ($unlocked): ?>
                    <span class="contact-revealed"><?= e($post['contact_value']) ?></span>
                <?php else: ?>
                    <button type="button" class="btn-pass" data-action="get-pass">Lấy pass</button>
                    <input type="text" data-field="pass" placeholder="Nhập pass ở đây để lấy số điện thoại">
                    <button type="button" class="btn-confirm" data-action="reveal">Xác nhận</button>
                    <span class="contact-revealed" data-field="result"><?= e(mask_contact($post['contact_value'])) ?></span>
                <?php endif; ?>
            </div>

            <div class="post-content"><?= nl2br(e($post['content'])) ?></div>

            <?php if ($images): ?>
                <div class="post-gallery">
                    <?php foreach ($images as $img): ?>
                        <img src="<?= base_url(ltrim($img['path'], '/')) ?>" alt="<?= e($post['title']) ?>" loading="lazy">
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <div class="post-nav">
            <?php if ($prev): ?>
                <a href="<?= site_url('tin/' . $prev['slug']) ?>"><span>Bài trước</span><?= e($prev['title']) ?></a>
            <?php else: ?><span></span><?php endif; ?>
            <?php if ($next): ?>
                <a href="<?= site_url('tin/' . $next['slug']) ?>"><span>Bài sau</span><?= e($next['title']) ?></a>
            <?php endif; ?>
        </div>

        <section class="content-box comment-box" id="binh-luan">
            <h2 class="info-heading"><?= count($comments) ?> bình luận</h2>

            <?php if ($user): ?>
                <form class="comment-form" method="post" enctype="multipart/form-data"
                      action="<?= site_url('ajax/comment/' . $post['id']) ?>">
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

            $render = function ($c, $depth = 0) use (&$render, $replies, $post, $user) {
                $can_delete = $user && ((int) $user['id'] === (int) $c['user_id']
                    || (int) $user['id'] === (int) $post['user_id']);
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
                                    <button type="button" class="comment-reply-btn" data-reply-to="<?= (int) $c['id'] ?>">Trả lời</button>
                                <?php endif; ?>
                                <?php if ($can_delete): ?>
                                    <a class="comment-del" href="<?= site_url('ajax/delete_comment/' . $c['id']) ?>"
                                       data-confirm="Xoá bình luận này?" data-confirm-danger>Xoá</a>
                                <?php endif; ?>
                            </div>

                            <?php if ($user): ?>
                                <form class="comment-form reply-form" id="reply-form-<?= (int) $c['id'] ?>" method="post"
                                      enctype="multipart/form-data"
                                      action="<?= site_url('ajax/comment/' . $post['id']) ?>" hidden>
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

        <?php if ($related): ?>
            <section class="home-block">
                <h2 class="block-title">Tin liên quan</h2>
                <div class="card-grid">
                    <?php foreach ($related as $r): ?>
                        <?php $this->load->view('posts/_card', array('post' => $r)); ?>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3><?= e($settings['site_name'] ?? 'Saigon Cupid') ?></h3>
            <p><?= e($settings['site_desc'] ?? '') ?></p>
            <p>Nghiêm cấm mọi hành vi đăng ảnh không đúng quy định, vi phạm thuần phong mỹ tục Việt Nam.</p>
        </div>
        <div class="sidebar-box">
            <h3>Liên kết nhanh</h3>
            <ul class="sidebar-list">
                <?php foreach (array_slice($quick_links, 0, 25) as $link): ?>
                    <li><a href="<?= site_url('danh-muc/' . $link['slug']) ?>"><?= e($link['name']) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </div>
    </aside>
</div>

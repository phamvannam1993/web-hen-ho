<?php defined('BASEPATH') OR exit('No direct script access allowed');
$ic = function ($path, $extra = '') {
    return '<svg viewBox="0 0 24 24" class="hm-ic ' . $extra . '">' . $path . '</svg>';
};
$ic_heart  = '<path d="M12 20.8s-7.2-4.5-7.2-9.6a4.5 4.5 0 0 1 7.2-3.6 4.5 4.5 0 0 1 7.2 3.6c0 5.1-7.2 9.6-7.2 9.6z"/>';
$ic_chat   = '<path d="M4 5.5h16v11H9.5L5.5 20v-3.5H4z"/>';
$ic_close  = '<path d="M6 6l12 12M18 6L6 18"/>';
?>
            <?php
                $tuoi     = age_from($m['birthday']);
                $da_match = in_array((int) $m['id'], $matched_ids ?? array(), true);
                // match_score tối đa lý thuyết là 155 nhưng thực tế hiếm khi vượt
                // 120, nên lấy 120 làm mốc 100% rồi kẹp trong khoảng 60–99 để
                // con số vừa sát thực vừa không hiện những mức khó tin.
                $hop = max(60, min(99, (int) round(($m['match_score'] ?? 0) * 100 / 120)));
                $chips = array_filter(array(
                    !empty($m['province_name']) ? $m['province_name'] : null,
                    !empty($m['job'])           ? $m['job'] : null,
                    !empty($m['height_cm'])     ? (int) $m['height_cm'] . 'cm' : null,
                    !empty($m['marital_status']) && $m['marital_status'] === 'doc_than' ? 'Độc thân' : null,
                ));
            ?>
                <article class="hm-sug" data-user="<?= (int) $m['id'] ?>">
                    <a class="hm-sug-photo" href="<?= site_url('profile/' . $m['slug']) ?>">
                        <img src="<?= avatar_url($m['avatar'], $m['gender']) ?>" alt="<?= e(display_name($m)) ?>" loading="lazy">
                    </a>

                    <div class="hm-sug-body">
                        <div class="hm-sug-top">
                            <h3><a href="<?= site_url('profile/' . $m['slug']) ?>"><?= e(display_name($m)) ?><?= $tuoi ? ', ' . $tuoi : '' ?></a></h3>
                            <span class="hm-pill hm-pill-pink">Tương hợp: <?= $hop ?>%</span>
                            <?php if ($da_match): ?>
                                <span class="hm-pill hm-pill-green">Đã match!</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($chips): ?>
                            <p class="hm-sug-chips">
                                <?php foreach ($chips as $c): ?><span><?= e($c) ?></span><?php endforeach; ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <div class="hm-sug-actions">
                        <?php if ($da_match): ?>
                            <button type="button" class="btn-hm btn-hm-solid" data-chat-with="<?= (int) $m['id'] ?>">
                                <?= $ic($ic_chat) ?>Trò chuyện ngay
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-hm btn-hm-line" data-card-action="pass">
                                <?= $ic($ic_close) ?>Bỏ qua
                            </button>
                            <button type="button" class="btn-hm btn-hm-solid <?= !empty($m['liked']) ? 'is-liked' : '' ?>"
                                    data-card-action="like" data-like-label="Thả tim">
                                <?= $ic($ic_heart) ?><span class="js-like-text"><?= !empty($m['liked']) ? 'Đã thích' : 'Thả tim' ?></span>
                            </button>
                        <?php endif; ?>
                    </div>
                </article>

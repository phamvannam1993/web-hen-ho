<?php defined('BASEPATH') OR exit('No direct script access allowed');
/**
 * Trang Tin nhắn — dựng theo `src/routes/tai-khoan.tin-nhan.tsx`.
 *
 * Máy tính: danh sách hội thoại bên trái, khung chat bên phải.
 * Điện thoại: chưa chọn hội thoại thì chỉ hiện danh sách; đã chọn (URL có id)
 * thì chỉ hiện khung chat, kèm nút quay lại.
 *
 * Giữ nguyên các id/lớp mà app.js dùng: #chat-body (data-conversation,
 * data-last-id), .chat-form, .chat-attach, #chat-input, #emoji-btn,
 * #emoji-panel, .emoji-tabs, #emoji-list, #chat-seen, .chat-msg/.mine/.is-image/.emoji-only.
 */
$dang_mo = (bool) $partner;

/** Giờ của tin cuối: hôm nay thì H:i, hôm qua thì "Hôm qua", còn lại d/m. */
$gio_ngan = function ($t) {
    if (!$t) return '';
    $ts = strtotime($t);
    if (date('Y-m-d', $ts) === date('Y-m-d')) return date('H:i', $ts);
    if (date('Y-m-d', $ts) === date('Y-m-d', strtotime('-1 day'))) return 'Hôm qua';
    return date('d/m', $ts);
};
?>
<div class="tk-card tk-ms<?= $dang_mo ? ' is-open' : '' ?>">
    <!-- Danh sách hội thoại -->
    <div class="tk-ms-side">
        <div class="tk-ms-side__h">
            <h1 class="tk-ms-side__t">Tin nhắn</h1>
            <?php if (!empty($conversations)): ?>
                <div class="tk-input-wrap tk-input-wrap--lead tk-ms-search">
                    <?= tk_icon('search') ?>
                    <input class="tk-input" type="search" id="tk-ms-search" placeholder="Tìm cuộc trò chuyện" autocomplete="off">
                </div>
            <?php endif; ?>
        </div>

        <?php if (empty($conversations)): ?>
            <div class="tk-ms-none">
                <span class="tk-empty__ic"><?= tk_icon('message') ?></span>
                <h3>Chưa có cuộc trò chuyện nào</h3>
                <p>Chọn một hồ sơ và nhắn tin để bắt đầu làm quen.</p>
                <a class="tk-btn tk-btn--brand tk-btn--sm" href="<?= site_url('swipe-match') ?>"><?= tk_icon('match') ?>Khám phá &amp; ghép đôi</a>
            </div>
        <?php else: ?>
            <ul class="tk-ms-list" id="tk-ms-list">
                <?php foreach ($conversations as $c): ?>
                    <?php
                    $ten = display_name($c);
                    $cuoi = !$c['last_message_id'] ? 'Bắt đầu trò chuyện'
                        : ($c['last_type'] === 'image' ? 'Đã gửi một ảnh' : excerpt($c['last_content'], 60));
                    if ((int) $c['last_sender_id'] === (int) $user['id']) $cuoi = 'Bạn: ' . $cuoi;
                    ?>
                    <li data-name="<?= e(mb_strtolower($ten)) ?>">
                        <a class="tk-ms-item<?= $conversation_id == $c['id'] ? ' is-active' : '' ?>"
                           href="<?= site_url('tai-khoan/tin-nhan/' . $c['id']) ?>"<?= $conversation_id == $c['id'] ? ' aria-current="page"' : '' ?>>
                            <span class="tk-ms-av">
                                <img src="<?= e(avatar_url($c['avatar'], $c['gender'])) ?>" alt="<?= e($ten) ?>" loading="lazy">
                                <?php if (is_online($c['last_active_at'])): ?><span class="tk-ms-online"></span><?php endif; ?>
                            </span>
                            <span class="tk-ms-item__b">
                                <span class="tk-ms-item__n"><?= e($ten) ?></span>
                                <span class="tk-ms-item__l<?= $c['unread'] ? ' is-unread' : '' ?>"><?= e($cuoi) ?></span>
                            </span>
                            <span class="tk-ms-item__r">
                                <span class="tk-ms-item__t"><?= $gio_ngan($c['last_at']) ?></span>
                                <?php if ($c['unread']): ?><span class="tk-count"><?= (int) $c['unread'] ?></span><?php endif; ?>
                            </span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
            <p class="tk-ms-nores" id="tk-ms-nores" hidden>Không tìm thấy cuộc trò chuyện nào.</p>
        <?php endif; ?>
    </div>

    <!-- Khung chat -->
    <div class="tk-ms-pane">
        <?php if (!$partner): ?>
            <div class="tk-ms-idle">
                <span class="tk-empty__ic"><?= tk_icon('message') ?></span>
                <h3>Chọn một cuộc trò chuyện</h3>
                <p>Chọn một hội thoại ở bên trái để bắt đầu trò chuyện.</p>
            </div>
        <?php else: ?>
            <div class="tk-ms-head">
                <a class="tk-btn tk-btn--ghost tk-btn--icon-sm tk-ms-back" href="<?= site_url('tai-khoan/tin-nhan') ?>" aria-label="Quay lại"><?= tk_icon('arrow-left') ?></a>
                <a class="tk-ms-head__who" href="<?= site_url('profile/' . ($partner['slug'] ?: $partner['id'])) ?>">
                    <span class="tk-ms-av tk-ms-av--sm">
                        <img src="<?= e(avatar_url($partner['avatar'], $partner['gender'])) ?>" alt="<?= e(display_name($partner)) ?>">
                    </span>
                    <span class="tk-ms-item__b">
                        <span class="tk-ms-head__n"><?= e(display_name($partner)) ?></span>
                        <span class="tk-ms-head__s"><?= is_online($partner['last_active_at']) ? 'Đang online' : 'Hoạt động ' . time_ago($partner['last_active_at']) ?></span>
                    </span>
                </a>
                <a class="tk-btn tk-btn--ghost tk-btn--icon-sm" href="<?= site_url('profile/' . ($partner['slug'] ?: $partner['id'])) ?>"
                   aria-label="Xem hồ sơ" title="Xem hồ sơ"><?= tk_icon('user') ?></a>
            </div>

            <div class="tk-ms-body" id="chat-body"
                 data-conversation="<?= (int) $conversation_id ?>"
                 data-last-id="<?= $messages ? (int) end($messages)['id'] : 0 ?>">
                <div class="tk-ms-safe">
                    <span class="tk-pill tk-pill--warning"><?= tk_icon('shield-alert') ?>Không chuyển tiền cho người lạ và hạn chế chia sẻ thông tin cá nhân.</span>
                </div>
                <?php if (empty($messages) && $can_send): ?>
                    <div class="tk-ms-hello" id="chat-hello">
                        <span class="tk-ms-hello__icon" aria-hidden="true">👋</span>
                        <strong>Bắt đầu bằng một lời chào</strong>
                        <p id="chat-hello-hint">Bấm bên dưới để gửi 👋 cho <?= e(display_name($partner)) ?>.</p>
                        <button class="tk-btn tk-btn--brand" type="button" id="chat-hello-send" aria-describedby="chat-hello-hint">Gửi lời chào 👋</button>
                    </div>
                <?php endif; ?>
                <?php foreach ($messages as $index => $m): ?>
                    <?php $mine = (int) $m['sender_id'] === (int) $user['id']; ?>
                    <?php $next = $messages[$index + 1] ?? null;
                    $continues = $next && (int) $next['sender_id'] === (int) $m['sender_id']
                        && substr($next['created_at'], 0, 10) === substr($m['created_at'], 0, 10); ?>
                    <div class="chat-msg <?= $mine ? 'mine' : '' ?> <?= $m['type'] === 'image' ? 'is-image' : '' ?>" data-day="<?= e(substr($m['created_at'], 0, 10)) ?>">
                        <?php if ($m['type'] === 'image'): ?>
                            <a href="<?= base_url(ltrim($m['content'], '/')) ?>" target="_blank">
                                <img src="<?= base_url(ltrim($m['content'], '/')) ?>" alt="Ảnh" loading="lazy">
                            </a>
                        <?php else: ?>
                            <?php
                            // Tin nhắn chỉ gồm icon thì phóng to cho dễ nhìn
                            $text = trim($m['content']);
                            $only_emoji = $text !== '' && preg_match('/^[\p{Emoji_Presentation}\p{Extended_Pictographic}\x{FE0F}\s]{1,8}$/u', $text);
                            ?>
                            <p class="<?= $only_emoji ? 'emoji-only' : '' ?>"><?= nl2br(e($m['content'])) ?></p>
                        <?php endif; ?>
                        <small <?= $continues ? 'hidden' : '' ?>><?= date('H:i d/m', strtotime($m['created_at'])) ?><?php if ($mine && !empty($m['read_at'])): ?><span class="chat-message-seen"> · Đã xem</span><?php endif; ?></small>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$can_send): ?>
                <div class="tk-ms-locked">
                    <div class="tk-alert tk-alert--warning">
                        <?= e($send_error ?: 'Hiện không thể gửi tin nhắn tới thành viên này.') ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="tk-ms-seen" id="chat-seen"></p>
                <form class="chat-form tk-ms-form" method="post" enctype="multipart/form-data"
                      action="<?= site_url('ajax/send-message') ?>">
                    <input type="hidden" name="receiver_id" value="<?= (int) $partner['id'] ?>">

                    <label class="chat-attach tk-btn tk-btn--ghost tk-btn--icon" title="Gửi ảnh" aria-label="Gửi ảnh">
                        <input type="file" name="image" accept="image/*" hidden data-no-preview>
                        <?= tk_icon('image') ?>
                    </label>

                    <div class="tk-ms-inwrap">
                        <input type="text" name="content" id="chat-input" class="tk-input"
                               placeholder="Nhập tin nhắn..." autocomplete="off">
                        <button type="button" class="tk-ms-emoji" id="emoji-btn" title="Chèn biểu tượng cảm xúc" aria-label="Chèn biểu tượng cảm xúc"><?= tk_icon('smile') ?></button>

                        <!-- Bảng chọn icon, mở khi bấm nút mặt cười -->
                        <div class="emoji-panel tk-ms-epanel" id="emoji-panel" hidden>
                            <div class="emoji-tabs">
                                <button type="button" class="active" data-group="cam-xuc">Cảm xúc</button>
                                <button type="button" data-group="cu-chi">Cử chỉ</button>
                                <button type="button" data-group="tinh-yeu">Tình yêu</button>
                                <button type="button" data-group="khac">Khác</button>
                            </div>
                            <div class="emoji-list" id="emoji-list"></div>
                        </div>
                    </div>

                    <button class="tk-btn tk-btn--brand tk-btn--icon" type="submit" aria-label="Gửi" title="Gửi"><?= tk_icon('send') ?></button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($conversations)): ?>
<script>
/* Lọc danh sách hội thoại theo tên ngay trên trang */
(function () {
    var q = document.getElementById('tk-ms-search');
    var list = document.getElementById('tk-ms-list');
    var none = document.getElementById('tk-ms-nores');
    if (!q || !list) return;
    q.addEventListener('input', function () {
        var v = q.value.trim().toLowerCase(), con = 0;
        list.querySelectorAll('li').forEach(function (li) {
            var ok = !v || li.getAttribute('data-name').indexOf(v) !== -1;
            li.hidden = !ok;
            if (ok) con++;
        });
        none.hidden = con > 0;
    });
})();
</script>
<?php endif; ?>

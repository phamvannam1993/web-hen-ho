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
$chat_groups = array_values(array_filter($chat_groups ?? array(), function ($group) {
    return ($group['mode'] ?? '') === 'guest';
}));
?>
<div class="tk-card tk-ms<?= $dang_mo ? ' is-open' : '' ?>"
     data-conversation-token="<?= e($message_action_token) ?>"
     data-current-conversation="<?= (int) $conversation_id ?>" data-inbox-url="<?= e(site_url('messenger')) ?>">
    <!-- Danh sách hội thoại -->
    <div class="tk-ms-side">
        <div class="tk-ms-side__h">
            <h1 class="tk-ms-side__t">Tin nhắn</h1>
                <div class="tk-input-wrap tk-input-wrap--lead tk-ms-search">
                    <?= tk_icon('search') ?>
                    <input class="tk-input" type="search" id="tk-ms-search" placeholder="Tìm cuộc trò chuyện" autocomplete="off">
                </div>
        </div>

        <div class="tk-ms-tabs" role="tablist" aria-label="Danh sách tin nhắn">
            <button type="button" id="tk-ms-tab-priority" role="tab" aria-selected="true"
                    aria-controls="tk-ms-inbox-panel" data-inbox-tab="priority">Ưu tiên</button>
            <button type="button" id="tk-ms-tab-clubs" role="tab" aria-selected="false" tabindex="-1"
                    aria-controls="tk-ms-inbox-panel" data-inbox-tab="clubs">Clubs Chat's</button>
        </div>
        <div class="tk-ms-inbox-panel" id="tk-ms-inbox-panel" role="tabpanel"
             aria-labelledby="tk-ms-tab-priority" tabindex="0">
        <?php if (!empty($chat_groups)): ?>
                <ul class="tk-ms-groups__list" aria-label="Nhóm chat ẩn danh">
                    <?php foreach ($chat_groups as $g): ?>
                        <li data-name="<?= e(mb_strtolower($g['name'])) ?>"><a class="tk-ms-item" href="<?= e($g['url']) ?>" data-inbox-group="<?= (int) $g['id'] ?>" data-panel-url="<?= site_url('ajax/nhom-panel/' . (int) $g['id']) ?>">
                            <span class="tk-ms-av"><img src="<?= e($g['avatar']) ?>" alt="" width="40" height="40" loading="lazy"></span>
                            <span class="tk-ms-item__b">
                                <span class="tk-ms-item__n"><?= e($g['name']) ?></span>
                                <span class="tk-ms-item__l"><?= e($g['last'] !== '' ? (!empty($g['last_sender']) ? $g['last_sender'] . ': ' : '') . $g['last'] : 'Chưa có tin nhắn') ?></span>
                            </span>
                            <?php if ($g['unread']): ?><span class="tk-count"><?= $g['unread'] > 99 ? '99+' : (int) $g['unread'] ?></span><?php endif; ?>
                        </a></li>
                    <?php endforeach; ?>
                </ul>
        <?php endif; ?>

        <section class="tk-ms-private is-expanded" aria-label="Tin nhắn riêng">
        <div class="tk-ms-private-content" id="tk-ms-private-content">
        <?php if (empty($conversations)): ?>
            <div class="tk-ms-none" data-private-empty <?= $chat_groups ? 'hidden' : '' ?>>
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
                    <li class="tk-ms-conversation-row" data-name="<?= e(mb_strtolower($ten)) ?>">
                        <a class="tk-ms-item<?= $conversation_id == $c['id'] ? ' is-active' : '' ?>"
                           data-conversation-id="<?= (int) $c['id'] ?>" data-last-message-id="<?= (int) $c['last_message_id'] ?>"
                           href="<?= site_url('messenger/' . $c['id']) ?>"<?= $conversation_id == $c['id'] ? ' aria-current="page"' : '' ?>>
                            <span class="tk-ms-av">
                                <img src="<?= e(avatar_url($c['avatar'], $c['gender'])) ?>" alt="<?= e($ten) ?>" loading="lazy">
                                <?php if (is_online($c['last_active_at'])): ?><span class="tk-ms-online"></span><?php endif; ?>
                            </span>
                            <span class="tk-ms-item__b">
                                <span class="tk-ms-item__n"><?= e($ten) ?></span>
                                <span class="inbox-muted-indicator" <?= (!isset($c['notifications_enabled']) || $c['notifications_enabled']) && (!isset($c['calls_notifications_enabled']) || $c['calls_notifications_enabled']) ? 'hidden' : '' ?>
                                      aria-label="Đã tắt thông báo" title="Đã tắt thông báo"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m3 3 18 18M10.6 4.2A6 6 0 0 1 18 10v4l2 3M6 6a6 6 0 0 0-1 4v4l-2 3h14M10 21h4"/></svg></span>
                                <span class="tk-ms-preview-line">
                                    <span class="tk-ms-item__l<?= $c['unread'] ? ' is-unread' : '' ?>"><?= e($cuoi) ?></span>
                                    <?php if ($c['last_at']): ?><span class="tk-ms-preview-time">· <?= e(time_ago($c['last_at'])) ?></span><?php endif; ?>
                                </span>
                            </span>
                            <span class="tk-ms-item__r">
                                <?php if ($c['unread']): ?><span class="tk-count"><?= (int) $c['unread'] ?></span><?php endif; ?>
                            </span>
                        </a>
                        <button class="tk-ms-conversation-more" type="button"
                                data-conversation-id="<?= (int) $c['id'] ?>"
                                data-delete-url="<?= e(site_url('ajax/tin-nhan/' . (int) $c['id'] . '/xoa-hoi-thoai')) ?>"
                                data-notification-url="<?= e(site_url('ajax/tin-nhan/' . (int) $c['id'] . '/thong-bao')) ?>"
                                data-notifications-enabled="<?= !isset($c['notifications_enabled']) || $c['notifications_enabled'] ? '1' : '0' ?>"
                                data-notifications-muted="<?= (isset($c['notifications_enabled']) && !$c['notifications_enabled']) || (isset($c['calls_notifications_enabled']) && !$c['calls_notifications_enabled']) ? '1' : '0' ?>"
                                aria-label="<?= e('Thao tác cuộc trò chuyện với ' . $ten) ?>"
                                aria-haspopup="true" aria-expanded="false">⋯</button>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        </div>
        </section>
        <p class="tk-ms-nores" id="tk-ms-nores" hidden>Không tìm thấy cuộc trò chuyện nào.</p>
        </div>
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
                <a class="tk-btn tk-btn--ghost tk-btn--icon-sm tk-ms-back" href="<?= site_url('messenger') ?>" aria-label="Quay lại"><?= tk_icon('arrow-left') ?></a>
                <a class="tk-ms-head__who" href="<?= site_url('profile/' . ($partner['slug'] ?: $partner['id'])) ?>">
                    <span class="tk-ms-av tk-ms-av--sm">
                        <img src="<?= e(avatar_url($partner['avatar'], $partner['gender'])) ?>" alt="<?= e(display_name($partner)) ?>">
                    </span>
                    <span class="tk-ms-item__b">
                        <span class="tk-ms-head__n"><?= e(display_name($partner)) ?></span>
                        <span class="tk-ms-head__s<?= is_online($partner['last_active_at']) ? ' is-online' : '' ?>"><?= is_online($partner['last_active_at']) ? 'Đang online' : (!empty($partner['last_active_at']) ? 'Hoạt động ' . time_ago($partner['last_active_at']) : 'Chưa có thông tin hoạt động') ?></span>
                    </span>
                </a>
                <?php if ($can_send && getenv('VOICE_ENABLED') === '1'): ?>
                <button type="button" class="tk-btn tk-btn--ghost tk-btn--icon-sm"
                        data-voice-call="<?= (int) $conversation_id ?>"
                        aria-label="Gọi thoại" title="Gọi thoại"
                        disabled><?= tk_icon('phone') ?></button>
                <?php endif; ?>
                <details class="tk-ms-more">
                    <summary aria-label="Tùy chọn hội thoại" title="Tùy chọn hội thoại"><span aria-hidden="true">⋯</span></summary>
                    <div class="tk-ms-more-menu">
                        <a href="<?= e(site_url('profile/' . ($partner['slug'] ?: $partner['id']))) ?>">
                            <?= tk_icon('user') ?><span>Xem hồ sơ</span>
                        </a>
                        <button type="button"
                                data-message-history="<?= e(site_url('ajax/tin-nhan/' . (int) $conversation_id . '/tim-kiem')) ?>">
                            <?= tk_icon('search') ?><span>Tìm tin nhắn cũ</span>
                        </button>
                        <form method="post" action="<?= e(site_url('ajax/tin-nhan/' . (int) $conversation_id . '/chan')) ?>">
                            <input type="hidden" name="action_token" value="<?= e($message_action_token) ?>">
                            <input type="hidden" name="action" value="<?= $blocked_by_me ? 'unblock' : 'block' ?>">
                            <button type="submit" data-confirm="<?= $blocked_by_me ? 'Bỏ chặn người này ở phía bạn?' : 'Chặn người này? Hai bạn sẽ không thể nhắn tin và xem hồ sơ của nhau.' ?>">
                                <?= tk_icon('shield-alert') ?><span><?= $blocked_by_me ? 'Bỏ chặn người này' : 'Chặn người này' ?></span>
                            </button>
                        </form>
                    </div>
                </details>
            </div>

            <div class="tk-ms-body" id="chat-body"
                 data-pin-url="<?= e(site_url('ajax/tin-nhan/' . (int) $conversation_id . '/ghim')) ?>"
                 data-recall-url="<?= e(site_url('ajax/tin-nhan/' . (int) $conversation_id . '/thu-hoi')) ?>"
                 data-peer-avatar="<?= e(avatar_url($partner['avatar'] ?? null, $partner['gender'] ?? 'other')) ?>"
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
                <?php $latest_seen_id = 0; foreach ($messages as $message) {
                    if ((int) $message['sender_id'] === (int) $user['id'] && !empty($message['read_at'])) $latest_seen_id = max($latest_seen_id, (int) $message['id']);
                } ?>
                <?php foreach ($messages as $index => $m): ?>
                    <?php $mine = (int) $m['sender_id'] === (int) $user['id']; ?>
                    <?php $next = $messages[$index + 1] ?? null;
                    $continues = $next && (int) $next['sender_id'] === (int) $m['sender_id']
                        && substr($next['created_at'], 0, 10) === substr($m['created_at'], 0, 10); ?>
                    <div class="chat-msg <?= $mine ? 'mine' : '' ?> <?= $m['type'] === 'image' ? 'is-image' : '' ?> <?= !empty($m['recalled']) ? 'is-recalled' : '' ?>" data-message-type="<?= e($m['type']) ?>" data-recalled="<?= !empty($m['recalled']) ? '1' : '0' ?>" data-message-id="<?= (int) $m['id'] ?>" data-day="<?= e(substr($m['created_at'], 0, 10)) ?>">
                        <?php if (!$mine): ?><img class="chat-peer-avatar" src="<?= e(avatar_url($partner['avatar'] ?? null, $partner['gender'] ?? 'other')) ?>" alt=""><?php endif; ?>
                        <?php if (!empty($m['reply'])): $reply = $m['reply']; ?>
                            <button type="button" class="pm-reply-quote" data-reply-to-id="<?= (int) $reply['id'] ?>" <?= !$reply['available'] ? 'disabled' : '' ?>>
                                <strong><?= !$reply['available'] ? 'Tin nhắn' : ($reply['mine'] ? 'Bạn' : 'Đối phương') ?></strong>
                                <span><?= e($reply['content']) ?></span>
                            </button>
                        <?php endif; ?>
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
                        <small <?= $continues && (int) $m['id'] !== $latest_seen_id ? 'hidden' : '' ?>><?= date('H:i d/m', strtotime($m['created_at'])) ?><?php if ($mine && (int) $m['id'] === $latest_seen_id): ?><span class="chat-message-seen"> · Đã xem</span><?php endif; ?></small>
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

<script src="<?= base_url('assets/site/js/inbox-layout.js') ?>?v=<?= @filemtime(FCPATH . 'assets/site/js/inbox-layout.js') ?>" defer></script>

<script src="<?= base_url('assets/site/js/inbox-tabs.js') ?>?v=<?= filemtime(FCPATH . 'assets/site/js/inbox-tabs.js') ?>" defer></script>

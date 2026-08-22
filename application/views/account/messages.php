<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="chat-layout">
            <div class="chat-list">
                <h2 class="section-title">Hội thoại</h2>
                <?php if (empty($conversations)): ?>
                    <p class="empty">Chưa có cuộc trò chuyện nào.</p>
                <?php endif; ?>
                <?php foreach ($conversations as $c): ?>
                    <a class="chat-item <?= $conversation_id == $c['id'] ? 'active' : '' ?>"
                       href="<?= site_url('tai-khoan/tin-nhan/' . $c['id']) ?>">
                        <img src="<?= avatar_url($c['avatar'], $c['gender']) ?>" alt="">
                        <div>
                            <b><?= e(display_name($c)) ?></b>
                            <p><?= e(excerpt($c['last_content'], 40)) ?></p>
                        </div>
                        <?php if ($c['unread']): ?><span class="chat-unread"><?= (int) $c['unread'] ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <div class="chat-window content-box">
                <?php if (!$partner): ?>
                    <p class="empty">Chọn một hội thoại ở bên trái để bắt đầu trò chuyện.</p>
                <?php else: ?>
                    <div class="chat-head">
                        <img src="<?= avatar_url($partner['avatar'], $partner['gender']) ?>" alt="">
                        <div>
                            <b><?= e(display_name($partner)) ?></b>
                            <small><?= is_online($partner['last_active_at']) ? 'Đang online' : 'Hoạt động ' . time_ago($partner['last_active_at']) ?></small>
                        </div>
                    </div>

                    <div class="chat-body" id="chat-body"
                         data-conversation="<?= (int) $conversation_id ?>"
                         data-last-id="<?= $messages ? (int) end($messages)['id'] : 0 ?>">
                        <?php foreach ($messages as $m): ?>
                            <?php $mine = (int) $m['sender_id'] === (int) $user['id']; ?>
                            <div class="chat-msg <?= $mine ? 'mine' : '' ?> <?= $m['type'] === 'image' ? 'is-image' : '' ?>">
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
                                <small><?= date('H:i d/m', strtotime($m['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <form class="chat-form" method="post" enctype="multipart/form-data"
                          action="<?= site_url('ajax/send-message') ?>">
                        <input type="hidden" name="receiver_id" value="<?= (int) $partner['id'] ?>">

                        <label class="chat-attach" title="Gửi ảnh">
                            <input type="file" name="image" accept="image/*" hidden>
                            <span>🖼</span>
                        </label>

                        <div class="chat-input-wrap">
                            <input type="text" name="content" id="chat-input"
                                   placeholder="Nhập tin nhắn..." autocomplete="off">
                            <button type="button" class="chat-emoji-btn" id="emoji-btn" title="Chèn biểu tượng cảm xúc">☺</button>

                            <!-- Bảng chọn icon, mở khi bấm nút mặt cười -->
                            <div class="emoji-panel" id="emoji-panel" hidden>
                                <div class="emoji-tabs">
                                    <button type="button" class="active" data-group="cam-xuc">Cảm xúc</button>
                                    <button type="button" data-group="cu-chi">Cử chỉ</button>
                                    <button type="button" data-group="tinh-yeu">Tình yêu</button>
                                    <button type="button" data-group="khac">Khác</button>
                                </div>
                                <div class="emoji-list" id="emoji-list"></div>
                            </div>
                        </div>

                        <button class="btn btn-primary" type="submit">Gửi</button>
                    </form>
                    <p class="chat-seen" id="chat-seen"></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>An toàn khi trò chuyện</h3>
            <p>Không chuyển tiền hoặc cung cấp thông tin tài khoản cho người lạ. Gặp dấu hiệu lừa đảo,
               hãy dùng chức năng báo cáo.</p>
        </div>
    </aside>
</div>

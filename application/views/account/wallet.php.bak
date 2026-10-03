<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="container page-layout">
    <div>
        <?php $this->load->view('account/_nav'); ?>

        <div class="content-box">
            <h1 class="section-title">Ví của tôi</h1>
            <div class="account-stats">
                <div><span><?= number_format($me['coin_balance']) ?></span>Xu hiện có</div>
                <div><span><?= $me['is_vip'] ? 'VIP' : '—' ?></span><?= $me['is_vip'] ? 'đến ' . date('d/m/Y', strtotime($me['vip_expired_at'])) : 'Chưa có VIP' ?></div>
            </div>
        </div>

        <div class="content-box">
            <h2 class="section-title">Chọn gói</h2>
            <form method="post" class="package-grid">
                <?php foreach ($packages as $pk): ?>
                    <label class="package-card">
                        <input type="radio" name="package_id" value="<?= (int) $pk['id'] ?>" required>
                        <b><?= e($pk['name']) ?></b>
                        <span class="price"><?= money($pk['price']) ?></span>
                        <small>
                            <?php if ($pk['type'] === 'coin'): ?>
                                <?= number_format($pk['coin_amount']) ?> xu
                                <?= $pk['bonus_coin'] ? ' + ' . number_format($pk['bonus_coin']) . ' tặng' : '' ?>
                            <?php else: ?>
                                VIP <?= (int) $pk['duration_days'] ?> ngày
                            <?php endif; ?>
                        </small>
                        <p><?= e($pk['description']) ?></p>
                    </label>
                <?php endforeach; ?>

                <div class="full">
                    <label for="method">Hình thức thanh toán</label>
                    <select id="method" name="method">
                        <option value="bank">Chuyển khoản ngân hàng</option>
                        <option value="momo">Ví MoMo</option>
                        <option value="vnpay">VNPay</option>
                    </select>
                    <p class="bank-note"><?= e(setting('bank_info')) ?></p>
                    <button class="btn btn-primary" type="submit">Tạo đơn nạp</button>
                </div>
            </form>
        </div>

        <div class="content-box">
            <h2 class="section-title">Đơn của tôi</h2>
            <?php if (empty($orders)): ?>
                <p class="empty">Chưa có đơn nào.</p>
            <?php else: ?>
                <table class="simple-table">
                    <tr><th>Mã đơn</th><th>Gói</th><th>Số tiền</th><th>Trạng thái</th><th>Ngày tạo</th></tr>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?= e($o['code']) ?></td>
                            <td><?= e($o['package_name']) ?></td>
                            <td><?= money($o['amount']) ?></td>
                            <td><?= status_label($o['status']) ?></td>
                            <td><?= date('d/m/Y H:i', strtotime($o['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>

        <div class="content-box">
            <h2 class="section-title">Lịch sử xu</h2>
            <?php if (empty($coins)): ?>
                <p class="empty">Chưa có giao dịch.</p>
            <?php else: ?>
                <table class="simple-table">
                    <tr><th>Thời gian</th><th>Biến động</th><th>Số dư</th><th>Nội dung</th></tr>
                    <?php foreach ($coins as $c): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($c['created_at'])) ?></td>
                            <td style="color:<?= $c['amount'] >= 0 ? '#158a3b' : '#c62828' ?>">
                                <?= $c['amount'] > 0 ? '+' : '' ?><?= number_format($c['amount']) ?>
                            </td>
                            <td><?= number_format($c['balance_after']) ?></td>
                            <td><?= e($c['note'] ?: $c['reason']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <aside>
        <div class="sidebar-box">
            <h3>Xu dùng để làm gì?</h3>
            <p>Xu dùng để mở thông tin liên hệ của tin đăng (<?= (int) setting('unlock_cost', 20) ?> xu/tin).
               Thành viên VIP xem không giới hạn và không tốn xu.</p>
        </div>
    </aside>
</div>

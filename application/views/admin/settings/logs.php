<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<div class="panel">
    <div class="panel-head"><h2>200 thao tác gần nhất</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Thời gian</th><th>Quản trị viên</th><th>Hành động</th><th>Đối tượng</th><th>IP</th></tr></thead>
            <tbody>
            <?php if (empty($logs)): ?><tr><td colspan="5">Chưa có nhật ký.</td></tr><?php endif; ?>
            <?php foreach ($logs as $l): ?>
                <tr>
                    <td><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
                    <td><?= e($l['display_name']) ?></td>
                    <td><code><?= e($l['action']) ?></code></td>
                    <td><?= e($l['target']) ?><?= $l['target_id'] ? ' #' . (int) $l['target_id'] : '' ?></td>
                    <td><?= e($l['ip']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

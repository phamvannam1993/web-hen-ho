<?php defined('BASEPATH') OR exit('No direct script access allowed');
$ten_loai = array(
    'welcome' => 'Chào mừng', 'new_message' => 'Tin nhắn mới',
    'notify_match' => 'Ghép đôi', 'notify_like' => 'Lượt thích',
    'notify_view' => 'Lượt xem hồ sơ', 'match_suggest' => 'Gợi ý ghép đôi',
    're_engage' => 'Kéo quay lại',
);
$pt = function ($tu, $mau) { return $mau > 0 ? round($tu * 100 / $mau, 1) . '%' : '—'; };
?>
<form class="filter-form" method="get">
    <div>
        <label>Khoảng thời gian</label>
        <select name="ngay" onchange="this.form.submit()">
            <?php foreach (array(7 => '7 ngày', 30 => '30 ngày', 90 => '90 ngày') as $n => $nhan): ?>
                <option value="<?= $n ?>" <?= $ngay === $n ? 'selected' : '' ?>><?= $nhan ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</form>

<div class="panel">
    <div class="panel-head"><h2>Hàng đợi hiện tại</h2></div>
    <div class="panel-body">
        <?php if (empty($hang_doi)): ?>
            <p>Hàng đợi trống.</p>
        <?php else: ?>
            <?php foreach ($hang_doi as $h): ?>
                <span style="display:inline-block; margin-right:18px;">
                    <b><?= number_format($h['so']) ?></b> <?= e($h['status']) ?>
                </span>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Theo loại thư (<?= $ngay ?> ngày)</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr>
                <th>Loại</th><th>Tổng</th><th>Đã gửi</th><th>Bỏ qua</th><th>Hỏng</th>
                <th>Tỉ lệ mở</th><th>Tỉ lệ bấm</th>
            </tr></thead>
            <tbody>
            <?php if (empty($theo_loai)): ?>
                <tr><td colspan="7">Chưa có dữ liệu.</td></tr>
            <?php endif; ?>
            <?php foreach ($theo_loai as $r): ?>
                <tr>
                    <td><?= e($ten_loai[$r['type']] ?? $r['type']) ?></td>
                    <td><?= number_format($r['tong']) ?></td>
                    <td><?= number_format($r['da_gui']) ?></td>
                    <td><?= number_format($r['bo_qua']) ?></td>
                    <td><?= number_format($r['hong']) ?></td>
                    <td><?= $pt($r['da_mo'], $r['da_gui']) ?></td>
                    <td><?= $pt($r['da_bam'], $r['da_gui']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>Thử nghiệm tiêu đề A/B</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Loại</th><th>Nhánh</th><th>Đã gửi</th><th>Tỉ lệ mở</th><th>Tỉ lệ bấm</th></tr></thead>
            <tbody>
            <?php if (empty($ab)): ?>
                <tr><td colspan="5">Chưa đủ dữ liệu để so sánh.</td></tr>
            <?php endif; ?>
            <?php foreach ($ab as $r): ?>
                <tr>
                    <td><?= e($ten_loai[$r['type']] ?? $r['type']) ?></td>
                    <td><b><?= e($r['variant']) ?></b></td>
                    <td><?= number_format($r['da_gui']) ?></td>
                    <td><?= $pt($r['da_mo'], $r['da_gui']) ?></td>
                    <td><?= $pt($r['da_bam'], $r['da_gui']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="panel">
    <div class="panel-head"><h2>30 thư gần nhất</h2></div>
    <div class="table-scroll">
        <table class="table">
            <thead><tr><th>Thời điểm</th><th>Loại</th><th>Gửi tới</th><th>Tiêu đề</th><th>Trạng thái</th></tr></thead>
            <tbody>
            <?php if (empty($gan_day)): ?>
                <tr><td colspan="5">Chưa gửi thư nào.</td></tr>
            <?php endif; ?>
            <?php foreach ($gan_day as $r): ?>
                <tr>
                    <td><?= date('H:i d/m', strtotime($r['sent_at'] ?: $r['created_at'])) ?></td>
                    <td><?= e($ten_loai[$r['type']] ?? $r['type']) ?></td>
                    <td><?= e($r['to_email']) ?></td>
                    <td><?= e(excerpt($r['subject'], 46)) ?></td>
                    <td>
                        <?= e($r['status']) ?>
                        <?php if ($r['clicked_at']): ?> · đã bấm
                        <?php elseif ($r['opened_at']): ?> · đã mở<?php endif; ?>
                        <?php if ($r['error']): ?>
                            <br><small style="color:#b91c1c;"><?= e(excerpt($r['error'], 60)) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

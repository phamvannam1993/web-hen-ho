<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<section class="container profile-access-locked">
    <img src="<?= e($preview['avatar']) ?>" width="96" height="96" alt="">
    <h1><?= e($preview['name']) ?></h1>
    <p><?= e(implode(' · ',array_filter(array($preview['age'] ? $preview['age'].' tuổi' : '',$preview['province'],$preview['job'])))) ?></p>
    <p><?= e($preview['bio']) ?></p>
    <p>Thông tin chi tiết đang được khóa. Mở popup để xem điều kiện và xác nhận.</p>
    <button class="btn btn-primary" type="button" data-profile-preview="<?= e($preview['slug']) ?>">Xem hồ sơ<?= $preview['cost'] ? ' chi tiết · 20 xu' : ' chi tiết' ?></button>
</section>

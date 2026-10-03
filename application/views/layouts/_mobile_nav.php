<?php defined('BASEPATH') OR exit('No direct script access allowed');
$nav_path = trim($this->uri->uri_string(), '/');
$mobile_items = array(
    array('', 'Trang chủ', 'home'),
    array('hen-ho', 'Hẹn hò', 'match'),
    array('swipe-match', 'Ghép đôi', 'sparkles'),
    array('tai-khoan/tin-nhan', 'Tin nhắn', 'message'),
    array('tai-khoan/ho-so', 'Hồ sơ', 'user'),
);
?>
<nav class="tk-bnav site-mobile-nav<?= $nav_path === 'swipe-match' ? ' site-mobile-nav--swipe' : '' ?>" aria-label="Điều hướng nhanh">
    <ul>
        <?php foreach ($mobile_items as $item): ?>
            <?php $active = $nav_path === $item[0]; ?>
            <li>
                <a href="<?= site_url($item[0]) ?>"<?= $active ? ' class="is-active" aria-current="page"' : '' ?>>
                    <?= tk_icon($item[2]) ?>
                    <span><?= e($item[1]) ?></span>
                    <?php if ($item[0] === 'tai-khoan/tin-nhan' && !empty($tk['msg'])): ?>
                        <span class="tk-dot-count"><?= (int) $tk['msg'] > 99 ? '99+' : (int) $tk['msg'] ?></span>
                    <?php endif; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>
</nav>

<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/chat-groups.css') ?>?v=<?= filemtime(FCPATH . 'assets/site/css/chat-groups.css') ?>">
<main class="container cg-page"><a class="cg-back" href="<?= site_url('nhom') ?>"><?= tk_icon('chevron-left', 'cg-icon') ?> Khám phá nhóm</a>
<header class="cg-heading"><h1><?= e($group['name']) ?></h1><p><?= e($group['description']) ?></p></header>
<div class="cg-workspace"><aside class="cg-sidebar"><h2><?= tk_icon('message', 'cg-icon') ?> Nhóm của bạn</h2><a href="<?= site_url('nhom/hen-ho-4-phuong') ?>">Nhóm Hẹn Hò 4 Phương</a><div data-cg-sidebar></div><a href="<?= site_url('nhom') ?>">Khám phá nhóm <?= tk_icon('chevron-right', 'cg-icon') ?></a></aside><div>
<?php if ($group['status'] === 'closed' && !$this->m_chat_group->moderator($group['id'])): ?><p class="cg-notice">Nhóm đã đóng. Bạn có thể khám phá những nhóm khác.</p>
<?php else: $this->load->view('groups/panel', array('group'=>$group,'chat_csrf'=>$chat_csrf)); endif; ?>
</div></div>
</main>

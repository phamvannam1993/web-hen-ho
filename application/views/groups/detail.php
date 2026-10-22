<?php defined('BASEPATH') OR exit('No direct script access allowed'); ?>
<link rel="stylesheet" href="<?= base_url('assets/site/css/chat-groups.css') ?>?v=<?= filemtime(FCPATH . 'assets/site/css/chat-groups.css') ?>">
<main class="container cg-page"><a class="cg-back" href="<?= site_url('clubs') ?>"><?= tk_icon('chevron-left', 'cg-icon') ?> Khám phá nhóm</a>
<header class="cg-heading"><h1><?= e($group['name']) ?></h1><p><?= e($group['description']) ?></p></header>
<div class="cg-workspace"><aside class="cg-sidebar"><h2><?= tk_icon('message', 'cg-icon') ?> Clubs Chat's</h2><div data-cg-sidebar data-url="<?= site_url('ajax/nhom/0/sticky') ?>" data-current-group="<?= (int) $group['id'] ?>"></div></aside><div>
<?php if ($group['status'] === 'closed' && !$this->m_chat_group->moderator($group['id'])): ?><p class="cg-notice">Nhóm đã đóng. Bạn có thể khám phá những nhóm khác.</p>
<?php else: $this->load->view('groups/panel', array('group'=>$group,'chat_csrf'=>$chat_csrf)); endif; ?>
</div></div>
</main>

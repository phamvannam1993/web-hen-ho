<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Legacy readers must remain scoped even when the new feature is disabled. */
function chat_group_schema_ready()
{
    static $ready;
    if ($ready === null) {
        $CI =& get_instance();
        $ready = method_exists($CI->db, 'field_exists') && $CI->db->field_exists('room_id', 'room_messages');
    }
    return $ready;
}

function chat_groups_enabled()
{
    return getenv('CHAT_GROUPS_ENABLED') === '1' && chat_group_schema_ready();
}

function chat_group_default_scope($db, $alias = 'r')
{
    if (chat_group_schema_ready()) $db->where($alias . '.room_id', 1);
}

function chat_group_legacy_error($send = false)
{
    if (!chat_group_schema_ready()) return null;
    $CI =& get_instance();
    $CI->load->model('m_chat_group');
    try { $CI->m_chat_group->access($CI->m_chat_group->group(1), $CI->m_chat_group->actor(), $send); }
    catch (RuntimeException $e) { return $e->getMessage(); }
    return null;
}

/** Content validation shared by controller and tests. */
function chat_group_content($text)
{
    $text = trim((string) $text);
    if ($text === '' || mb_strlen($text) > 2000 || preg_match('/^[\s\p{Cf}]*$/u', $text)) {
        throw new InvalidArgumentException('Tin nhắn cần có nội dung và tối đa 2.000 ký tự.');
    }
    return $text;
}

/** Random public IDs give guests a stable avatar without a schema change. */
function chat_group_guest_avatar_url($public_id)
{
    if (!$public_id) return base_url('assets/images/chat-guest.svg');
    $number = (hexdec(substr(hash('sha256', (string) $public_id), 0, 8)) % 30) + 1;
    return base_url('assets/images/monster-avatar/' . $number . '.png');
}

function chat_group_image_url($row)
{
    return preg_match('/^[a-f0-9]{48}\.webp$/D', $row['content'])
        ? site_url('nhom-media/' . (int) $row['id']) : base_url(ltrim($row['content'], '/'));
}

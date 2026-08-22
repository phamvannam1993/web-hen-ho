<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Bình luận trên trang cá nhân thành viên. */
class M_user_comment extends CI_Model
{
    public function for_profile($profile_id)
    {
        return $this->db->select('c.*, u.display_name, u.slug AS user_slug, u.avatar, u.gender')
            ->from('user_comments c')->join('users u', 'u.id = c.user_id')
            ->where('c.profile_id', $profile_id)->where('c.status', 'approved')
            ->order_by('c.id', 'ASC')->get()->result_array();
    }

    public function create($profile_id, $user_id, $content, $parent_id = null, $image = null)
    {
        $this->db->insert('user_comments', array(
            'profile_id' => $profile_id,
            'user_id'    => $user_id,
            'parent_id'  => $parent_id ?: null,
            'content'    => $content,
            'image'      => $image,
            'status'     => setting('auto_approve_comment', '1') === '1' ? 'approved' : 'pending',
        ));
        $id = $this->db->insert_id();

        if ((int) $profile_id !== (int) $user_id) {
            $this->load->model('m_notification');
            $this->m_notification->push($profile_id, 'comment', 'Bình luận mới trên trang của bạn',
                excerpt($content, 80));
        }
        return $id;
    }

    public function delete_own($id, $user_id)
    {
        $this->db->where('id', $id)
            ->group_start()->where('user_id', $user_id)->or_where('profile_id', $user_id)->group_end()
            ->delete('user_comments');
    }
}

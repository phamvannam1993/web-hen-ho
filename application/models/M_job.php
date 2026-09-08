<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/** Danh mục nghề nghiệp, dùng cho ô chọn nghề trong hồ sơ. */
class M_job extends CI_Model
{
    private static $cache = null;

    /** Toàn bộ nghề đang bật, xếp theo cột sort rồi tới tên. */
    public function all()
    {
        if (self::$cache === null) {
            self::$cache = $this->db->where('is_active', 1)
                ->order_by('sort')->order_by('name')->get('jobs')->result_array();
        }
        return self::$cache;
    }

    /** Danh sách tên nghề, dùng để dựng thẻ option. */
    public function names()
    {
        $ten = array();
        foreach ($this->all() as $j) {
            $ten[] = $j['name'];
        }
        return $ten;
    }

    /**
     * Lọc giá trị người dùng gửi lên: chỉ nhận tên có trong danh mục,
     * ngoài ra trả về null để không ai nhét chữ tuỳ ý vào hồ sơ.
     */
    public function valid_name($name, $dang_dung = null)
    {
        $name = trim((string) $name);
        if ($name === '') {
            return null;
        }
        if (in_array($name, $this->names(), true)) {
            return $name;
        }
        // Nghề đã bị tắt hoặc xoá khỏi danh mục: chủ hồ sơ vẫn được giữ nguyên
        // giá trị cũ của mình, chỉ không ai chọn mới được nữa.
        return $name === trim((string) $dang_dung) ? $name : null;
    }

    /* ------------------------- Dùng cho khu quản trị ------------------------- */

    /** Danh sách kèm số thành viên đang chọn nghề đó. */
    public function admin_list($keyword = null, $status = null)
    {
        $this->db->select('j.*,
            (SELECT COUNT(*) FROM users u
              WHERE u.job = j.name AND u.deleted_at IS NULL) AS member_count')
            ->from('jobs j');

        if ($keyword) {
            $this->db->like('j.name', $keyword);
        }
        if ($status === 'on' || $status === 'off') {
            $this->db->where('j.is_active', $status === 'on' ? 1 : 0);
        }
        return $this->db->order_by('j.sort')->order_by('j.name')->get()->result_array();
    }

    public function find($id)
    {
        return $this->db->where('id', (int) $id)->get('jobs')->row_array();
    }

    public function name_exists($name, $except_id = null)
    {
        $this->db->where('name', trim($name));
        if ($except_id) {
            $this->db->where('id !=', (int) $except_id);
        }
        return $this->db->count_all_results('jobs') > 0;
    }

    /**
     * Lưu một nghề. Hồ sơ lưu tên nghề chứ không lưu id, nên khi đổi tên phải
     * cập nhật luôn cho những người đang mang tên cũ, kẻo nghề của họ thành
     * giá trị lạ và ô chọn trong hồ sơ hiện trống.
     */
    public function save(array $data, $id = null)
    {
        $data['name'] = trim($data['name']);

        if ($id) {
            $cu = $this->find($id);
            $this->db->where('id', (int) $id)->update('jobs', $data);
            if ($cu && $cu['name'] !== $data['name']) {
                $this->db->where('job', $cu['name'])->update('users', array('job' => $data['name']));
            }
            self::$cache = null;
            return (int) $id;
        }

        $this->db->insert('jobs', $data);
        self::$cache = null;
        return (int) $this->db->insert_id();
    }

    /** Số thành viên đang chọn nghề này, để cảnh báo trước khi xoá. */
    public function member_count($id)
    {
        $j = $this->find($id);
        if (!$j) {
            return 0;
        }
        return (int) $this->db->where('job', $j['name'])
            ->where('deleted_at', null)->count_all_results('users');
    }

    /** Xoá nghề; hồ sơ đang chọn nghề đó chuyển về bỏ trống. */
    public function remove($id)
    {
        $j = $this->find($id);
        if (!$j) {
            return;
        }
        $this->db->where('job', $j['name'])->update('users', array('job' => null));
        $this->db->where('id', (int) $id)->delete('jobs');
        self::$cache = null;
    }
}

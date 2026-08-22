<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class M_setting extends CI_Model
{
    private static $cache = null;

    /** Trả về toàn bộ cấu hình dạng key => value. */
    public function all()
    {
        if (self::$cache === null) {
            self::$cache = array();
            foreach ($this->db->get('settings')->result_array() as $row) {
                self::$cache[$row['key']] = $row['value'];
            }
        }
        return self::$cache;
    }

    public function grouped()
    {
        return $this->db->order_by('group')->order_by('key')->get('settings')->result_array();
    }

    public function set($key, $value, $group = 'general')
    {
        $exists = $this->db->where('key', $key)->count_all_results('settings') > 0;
        if ($exists) {
            $this->db->where('key', $key)->update('settings', array('value' => $value));
        } else {
            $this->db->insert('settings', array('key' => $key, 'value' => $value, 'group' => $group));
        }
        self::$cache = null;
    }
}

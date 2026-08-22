<?php

class MNew extends CI_Model {
    protected $table;

    function __construct() {
        parent::__construct();
        $this->table = "news";
    }

    public function insert($data = []) {
        $data["created_at"] = date('Y-m-d H:i:s');
        $data["updated_at"] = date('Y-m-d H:i:s');
        $this->db->insert($this->table, $data);
        return $this->db->insert_id();
    }

    function update($id, $data = []) {
        $data['updated_at'] = date('Y-m-d H:i:s');
        $this->db->where(["id" => $id])->update($this->table, $data);
    }

    public function delete($id) {
        return $this->db->where(["id" => $id])->delete($this->table);
    }

    public function getOneByID($id) {
        $this->db->select('*')->where(["id" => $id]);
        $this->db->from($this->table);
        return $this->db->get()->row_array();
    }

    public function getOneBy($params = []) {
        $query = $this->db->select('*');
        if(isset($params["id"])) {
            $query = $query->where(["id" => $params["id"]]);
        }
        if(isset($params["title"])) {
            $query = $query->where(["title" => $params["title"]]);
        }
        $query->from($this->table);
        return  $query->get()->row();
    }

    public function get($params = []) {
        $this->db->select('*');
        $this->db->from($this->table);
        if(isset($params["limit"]) && isset($params["offset"]))  {
            $this->db->limit($params["limit"], $params["offset"]);
        }
        if(isset($params["search"]))  {
            $this->db->like("name", $params["search"]);
        }
        $this->db->order_by('id', 'DESC');
        return $this->db->get()->result_array();
    }
    public function get_by($params) {
        $query = $this->db->select('*');
        if ($params != '') {
            $query = $query->where($params);
        }
        $query->from($this->table);
        return  $query->get()->row();
    }

    public function count_all()
    {
        return $this->db->count_all($this->table);
    }

    public function get_data($limit, $offset)
    {
        return $this->db->limit($limit, $offset)->get($this->table)->result();
    }
}

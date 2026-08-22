<?php

class MOpeningSchedule extends CI_Model {
    protected $table;

    function __construct() {
        parent::__construct();
        $this->table = "opening_schedules";
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
        if(isset($params["day"])) {
            $query = $query->where(["day" => $params["day"]]);
        }
        if(isset($params["product_id"])) {
            $query = $query->where(["product_id" => $params["product_id"]]);
        }
        if(isset($params["day_week"])) {
            $query = $query->where(["day_week" => $params["day_week"]]);
        }
        if(isset($params["address_id"])) {
            $query = $query->where(["product_id" => $params["product_id"]]);
        }
        $query->from($this->table);
        return  $query->get()->row();
    }

    public function get($params = []) {
        $this->db->select('*');
        $this->db->from($this->table);
        if(isset($params["limit"]) && isset($params["start"]))  {
            $this->db->limit($params["limit"], $params["start"]);
        }
        if(isset($params["search"]))  {
            $this->db->like("name", $params["search"]);
        }
        if(isset($params["product_id"]))  {
            $this->db->where("product_id", $params["product_id"]);
        }
        if(isset($params["address_id"]))  {
            $this->db->where("address_id", $params["address_id"]);
        }
        $this->db->order_by('day', 'ASC');
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

    public function count_all($params)
    {
        $query = $this->db;
        if(isset($params["product_id"]))  {
            $query = $query->where("product_id", $params["product_id"]);
        }
        if(isset($params["address_id"]))  {
            $query = $query->where("address_id", $params["address_id"]);
        }
        return $query->from($this->table)->count_all_results();
    }

    public function get_data($limit, $offset)
    {
        return $this->db->limit($limit, $offset)->get($this->table)->result();
    }
}

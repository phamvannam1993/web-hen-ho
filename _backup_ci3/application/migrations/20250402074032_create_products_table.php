<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_products_table extends CI_Migration {
    public function up(){
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'product_type_id' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'slug' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'name' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'image' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'created_at' => array(
                'type'      => 'timestamp',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type'      => 'timestamp',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('products', TRUE);
    }

    public function down(){
        
    }
}
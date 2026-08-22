<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_categories_table extends CI_Migration {
    public function up(){
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'name' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'title_name' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'slug' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'svg' => array(
                'type' => 'text',
                'null' => TRUE,
            ),
            'color' => array(
                'type' => 'varchar',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'title' => array(
                'type' => 'longtext',
                'null' => TRUE,
            ),
            'meta_des' => array(
                'type' => 'longtext',
                'null' => TRUE,
            ),
            'keyword' => array(
                'type' => 'longtext',
                'null' => TRUE,
            ),
            'type' => array(
                'type' => 'VARCHAR',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'description' => array(
                'type' => 'longtext',
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
        $this->dbforge->create_table('categories', TRUE);
    }

    public function down(){
        
    }
}
<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_books_table extends CI_Migration {
    public function up(){
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
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
            'product_type_id' => array(
                'type' => 'INT',
                'null' => TRUE,
            ),
            'class_id' => array(
                'type' => 'INT',
                'null' => TRUE,
            ),
            'book_array_id' => array(
                'type' => 'INT',
                'null' => TRUE,
            ),
            'subject_id' => array(
                'type' => 'INT',
                'null' => TRUE,
            ),
            'book_type_id' => array(
                'type' => 'INT',
                'null' => TRUE,
            ),
            'publisher' => array(
                'type' => 'text',
                'constraint' => '100',
                'null' => TRUE,
            ),
            'year_production' => array(
                'type' => 'INT',
                'null' => TRUE,
            ),
            'book_block' => array(
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
        $this->dbforge->create_table('books', TRUE);
    }

    public function down(){
        
    }
}
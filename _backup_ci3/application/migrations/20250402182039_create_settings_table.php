<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_settings_table extends CI_Migration {
    public function up(){
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
            ),
            'hotline' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'phone' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'email' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'facebook' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'youtube' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'zalo' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'address' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'logo' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'name_company_short' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'name_company' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'description_company' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'content_1' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'content_2' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'content_3' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'number_1' => array(
                'type' => 'INT',
            ),
            'number_2' => array(
                'type' => 'INT',
            ),
            'number_3' => array(
                'type' => 'INT',
            ),
            'e_product_des' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'd_des' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'business_reg_info' => array(
                'type' => 'text',
                'constraint' => '255',
                'null' => TRUE,
            ),
            'updated_at' => array(
                'type'      => 'timestamp',
                'null' => TRUE,
            ),
            'created_at' => array(
                'type'      => 'timestamp',
                'null' => TRUE,
            ),
        ));
        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('settings', TRUE);
    }

    public function down(){
        
    }
}
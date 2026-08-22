defined('BASEPATH') OR exit('No direct script access allowed');

class <?php echo 'Migration_'.$className ?> extends CI_Migration {
    public function up(){
        $this->dbforge->add_field(array(
            'id' => array(
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => TRUE,
                'auto_increment' => TRUE
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
        $this->dbforge->create_table('table_name', TRUE);
    }

    public function down(){
        
    }
}
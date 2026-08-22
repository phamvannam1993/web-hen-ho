<?php defined("BASEPATH") or exit("No direct script access allowed");

class Migrate extends CI_Controller{


    public function __construct()
    {
        parent::__construct();
    }

    public function index() {
        // if(!$this->input->is_cli_request()) {
        //     show_error("you don't have permission for action this");
        //     return;
        // }
        $this->load->library("migration");
    }

    public function version($version){
      if(!$this->migration->version($version)){
          show_error($this->migration->error_string());
      }   
    }
 
    public function genarate($name = false) {
        if(!$name) {
            echo "vui lòng nhập name";
            die;
        }
        $fileName = date('YmdHis').'_'.$name.'.php';
        $folder = APPPATH.'migrations';
        if(!is_dir($folder)) {
            mkdir($folder);
        }

        $filePath = APPPATH.'migrations/'.$fileName;
        if(file_exists($filePath)) {
            echo "file đã tồn tại";
            return;
        }

        $data["className"] = ucfirst($name);
        $template = $this->load->view("cli/migrations/migration_class_template", $data, TRUE);
        $file = fopen($filePath, "w");
        $content = "<?php\n".$template;
        fwrite($file, $content);
        fclose($file);
    }
}

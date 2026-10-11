<?php
define('BASEPATH', __DIR__);
define('FCPATH', dirname(__DIR__) . '/');
class Member_Controller {}
require FCPATH . 'application/controllers/Account.php';
class ProfileUploadFake {
    public $field, $initialized = 0, $config, $fail = false;
    function initialize($config) { $this->config = $config; $this->initialized++; }
    function do_upload($field) { $this->field = $field; return !$this->fail; }
    function data() { return array('file_name' => $this->field . '-new.jpg'); }
    function display_errors() { return '<p>Invalid image</p>'; }
}
class ProfileUploadLoader { function library($name) {} }
class ProfileUploadAccount extends Account {
    public $load, $upload;
    function __construct() { $this->load = new ProfileUploadLoader(); $this->upload = new ProfileUploadFake(); }
}
function set_flash($type, $message) { $GLOBALS['upload_flash'] = array($type, $message); }
$account = new ProfileUploadAccount();
$upload = new ReflectionMethod(Account::class, 'upload_image');
$upload->setAccessible(true);
$_FILES = array('cover_image' => array('name' => 'cover.jpg'), 'avatar' => array('name' => 'avatar.jpg'));
foreach (array('cover_image', 'avatar') as $field) {
    $path = $upload->invoke($account, $field);
    if ($path !== 'uploads/' . date('Y/m') . '/' . $field . '-new.jpg') throw new Exception('Incorrect image path');
}
if ($account->upload->initialized !== 2) throw new Exception('Each image needs independent upload state');
$account->upload->fail = true;
if ($upload->invoke($account, 'cover_image') !== null || $GLOBALS['upload_flash'][0] !== 'warning') throw new Exception('Failed upload must report a warning');
$_FILES = array();
if ($upload->invoke($account, 'cover_image') !== null) throw new Exception('No image must preserve existing cover');
echo "PASS: cover/avatar upload paths, independent state, upload errors and unchanged cover.\n";

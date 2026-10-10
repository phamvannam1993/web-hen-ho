<?php
require __DIR__ . '/dating_discovery.php';
class PreferenceDb {
    public $seeking = 'male', $user_id;
    function select($field) { return $this; }
    function where($field, $value) { $this->user_id = $value; return $this; }
    function get($table) { return $this; }
    function row_array() { return array('seeking_gender' => $this->seeking); }
}
function redirect($path, $method = 'auto', $code = null) {
    $GLOBALS['preference_redirect'] = array($path, $code);
}
$controller->db = new PreferenceDb();
$controller->data['user'] = array('id' => 17);
$controller->input->query = array();
foreach (array('male' => 'nam', 'female' => 'nu') as $gender => $tab) {
    $controller->db->seeking = $gender;
    $controller->index();
    check_discovery($GLOBALS['preference_redirect'] === array('hen-ho/' . $tab, 302), 'Saved preference opens the matching tab');
    check_discovery($controller->db->user_id === 17, 'Read preference for the current user');
}
$GLOBALS['preference_redirect'] = null;
$controller->db->seeking = 'all';
$controller->index();
check_discovery($GLOBALS['preference_redirect'] === null, 'All genders stays on the general page');
$controller->db->seeking = 'male';
$controller->input->query = array('gender' => 'female');
$controller->index();
check_discovery($GLOBALS['preference_redirect'] === null && $controller->rendered['filters']['gender'] === 'female', 'Explicit filters override preference');
$controller->input->query = array();
$controller->index('nu');
check_discovery($GLOBALS['preference_redirect'] === null && $controller->rendered['tab'] === 'nu', 'Explicit tab stays selected');
echo "PASS: saved gender redirects, all genders and explicit choices.\n";

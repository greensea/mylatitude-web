<?php
function apiout($code, $message = NULL, $data = NULL) {
    $output = array(
        'code' => $code
    );
    if ($message !== NULL) {
        $output['message'] = $message;
    }
    if ($data !== NULL) {
        $output['data'] = $data;
    }
    
    
    echo json_encode($output, JSON_UNESCAPED_UNICODE);
}


function getByUID($uid) {
    global $my;
    
    $sql = sprintf("SELECT * FROM b_user WHERE uid='${uid}'", $my->real_escape_string($uid));
    $res = $my->query($sql) or die($my->error > " ({$sql})");
    
    $user = $res->fetch_array();
    if (!$user) {
        return NULL;
    }
    else {
        return $user;
    }
        
}
?>

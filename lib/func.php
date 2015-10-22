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
    
?>

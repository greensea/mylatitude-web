<?php
$LOG_PATH = '/var/log/latitude.log';
$LOG_LEVEL = LOG_DEBUG;

/// 打印日志到指定的文件中
function LOGS($log) {
    global $LOG_PATH;

    if (/* C('log_traceback') == */ true) {
        $bt = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
        $bt = array_slice($bt, 1);
        
        $log = '> ' . $log;
        
        foreach ($bt as $v) {
            $log = "(" . basename(@$v['file']) . ":" . @$v['line'] . ")" . @$v['function'] . "-${log}";
        }
    }
    
    $log = ' ' . $log;
    
    
    /// 附加请求编号
    static $pid = 0;
    if ($pid == 0) {
        $pid = rand();
    }
    
    $log = "(${pid})"  . $log;

    
    
    //syslog(LOG_INFO, $log);
    
    /// 附加日期
    $log = '[' . date(DATE_RFC822) . ']' . $log . "\n";
    
    file_put_contents($LOG_PATH, $log, FILE_APPEND);
}

/// 打印 Warning 级别的日志到 Syslog
function LOGW($log) {
    global $LOG_LEVEL;
    if (  LOG_WARNING <= $LOG_LEVEL) {
        LOGS($log);
    }
}

/// 打印 Error 级别的日志到 Syslog
function LOGE($log) {
    global $LOG_LEVEL;
    if ( LOG_ERR <= $LOG_LEVEL) {
        LOGS($log);
    }
}

/// 打印 Notice 级别的日志到 Syslog
function LOGN($log) {
    global $LOG_LEVEL;
    if ( LOG_NOTICE <= $LOG_LEVEL) {
        LOGS($log);
    }
}

/// 打印 Info 级别的日志到 Syslog
function LOGI($log) {
    global $LOG_LEVEL;
    if ( LOG_INFO <= $LOG_LEVEL) {
        LOGS($log);
    }
}

/// 打印 DEBUG 级别的日志到 Syslog
function LOGD($log) {
    global $LOG_LEVEL;
    if ( LOG_DEBUG <= $LOG_LEVEL) {
        LOGS($log);
    }
}



function apiout($code, $message = NULL, $data = NULL) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    
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
    
    die();
}

function apiDeleteKeys($inarr, $keys) {
    foreach ($keys as $key) {
        foreach ($inarr as $k => $v) {
            if (isset($inarr[$k][$key])) {
                unset($inarr[$k][$key]);
            }
        }
    }
    
    return $inarr;
}


/**
 * 根据 uid 获取一个用户的信息
 */
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


/**
 * 获取 Goole JWT keys
 */
function google_jwt_keys() {
    if (!file_exists('/tmp/google_jwt_keys.json')) {
        LOGD("/tmp/google_jwt_keys.json 不存在");
        return array();
    }
    return json_decode(file_get_contents('/tmp/google_jwt_keys.json'), TRUE);
}

/**
 * 更新 Google JWT keys
 */
function google_jwt_keys_refresh() {
    LOGD("从谷歌服务器更新 Google JWT key");
    
    $keys = @file_get_contents('https://www.googleapis.com/oauth2/v1/certs');
    if (!$keys) {
        LOGD("无法从谷歌服务器获取 JWT Key");
        apiout(-10, '无法刷新 google jwt keys');
        die();
    }
    
    LOGD("从谷歌服务器取到了 JWT key: " . $keys);
    
    file_put_contents('/tmp/google_jwt_keys.json', $keys);
    
    return json_decode($keys, TRUE);
}


/**
 * 根据 Google UID 获取用户最后的位置信息
 * 
 * @param string    用户的谷歌用户编号
 * @param int       从最近的 5 条记录中，选取精度最大的记录，但这些记录的 rtime 不能小于最新记录的 rtime - $time_range
 * @return array    如果没有位置信息，则返回 null
 */
function getLastLocationByGoogleUID($google_uid, $time_range = 60) {
    global $db;
    
    $where = [
        'AND' => [
            'google_uid' => $google_uid,
        ],
        'ORDER' => 'rtime DESC',
        'LIMIT' => 5,
    ];
    
    $res = $db->select('b_location', '*', $where);
    
    if ($res) {
        $loc = $res[0];
        
        for ($i = 1; $i < count($res); $i++) {
            if ($loc['rtime'] - $res[$i]['rtime'] > $time_range) {
                break;
            }
            
            if ((float)$loc['accurateness'] > (float)$res[$i]['accurateness']) {
                $loc = $res[$i];
            }
        }
        
        unset($loc['uid']);
        unset($loc['google_uid']);
        
        return $loc;
    }
    else {
        return NULL;
    }
}
    


function postv($key, $default = NULL) {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}
function getv($key, $default = NULL) {
    return isset($_GET[$key]) ? $_GET[$key] : $default;
}

/**
 * 根据用户 google uid 获取该用户的好友的信息以及好友最后位置信息，好友的最后位置信息保存在 location 字段中
 */
function getFriendsWithLocationByGoogleUID($google_uid) {
    global $db;

    /// 查询好友关系数据
    $where = ['AND' => [
        'friend1_google_uid' => $google_uid,
        'dtime' => 0,
    ]];

    $relations = array();
    $res = $db->select('b_friend', '*', $where);
    if ($res !== FALSE) {
        $relations = $res;
    }
    else {
        apiout(-10, '查询失败: (' . $db->last_query() . ')' . var_export($db->error(), TRUE));
    }
    
    /// 建立好友数据
    $friends = array();
    foreach ($relations as $relation) {
        $where = [
            'AND' => [
                'google_uid' => $relation['friend2_google_uid']
            ],
            'ORDER' => 'user_id DESC',
        ];

        $user = $db->get('b_user', '*', $where);
        if ($user) {
            $user['latitude_face'] = str_replace('/s96-c/', '/s48-c/', $user['google_face']);
            $friends[] = $user;
        }
    }


    /// 查询好友的位置信息
    foreach ($friends as $k => $v) {
        $friends[$k]['location'] = getLastLocationByGoogleUID($v['google_uid']);
    }
    $friends = apiDeleteKeys($friends, ['google_uid', 'user_id', 'uid', 'friend1_google_uid', 'friend2_google_uid']);

    return $friends;
}


/**
 * 精简轨迹
 * 
 * @param array     所有的轨迹，传入的轨迹应该按照时间倒序排列
 * @param int       精简到多少个点
 */
function compressTracks($tracks, $count = 30) {
    $mytracks = [];
    
    if (count($tracks) > $count) {
        $step = round(count($tracks) / $count) + 1;
        for ($i = 0; $i < count($tracks); $i += $step) {
            $mytracks[] = $tracks[$i];
        }
    }
    else {
        $mytracks = $tracks;
    }
    
    return $mytracks;
}

?>

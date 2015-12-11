<?php
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
    $ms = microtime(TRUE);
    $ms -= floor($ms);
    $ms = round($ms * 1000);
    $ms = sprintf('%03d', $ms);
    $log = '[' . date(DATE_RFC822) . '.' . $ms . ']' . $log . "\n";
    
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
    
    
    $output = json_encode($output, JSON_UNESCAPED_UNICODE);
    echo $output;
    
    if ($code != 0) {
        LOGD("向客户端返回了错误，返回内容是: {$output}");
    }
    
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
 * 根据最新的位置信息，计算用户新增的移动距离
 */
function distanceDelta($location) {
    global $db;
    global $MIN_DISTANCE_ACCURATENESS;
    
    /**
     * 查询在 rtime 上与当前报告位置相邻的两个距离 A 和 B
     * 用户上报的位置为 L, 那么用户新增的移动距离就是 DISTANCE(A, L) + DISTANCE(L, B) - DISTANCE(A, B)
     */
    
    if ($location['accurateness'] > $MIN_DISTANCE_ACCURATENESS) {
        LOGD("输入距离的　accurateness > MIN_DISTANCE_ACCURATENESS ({$location['accurateness']} > $MIN_DISTANCE_ACCURATENESS");
        return 0;
    }
    
    /// 1. 查询用户
    $user = getByUID($location['uid']);
    if (!$user) {
        LOGE("无法获取用户(uid={$location['uid']})");
        return FALSE;
    }
    
    
    /// 2. 查询该用户已经上报的两个相邻的位置
    /// FIXME: 此处应使用事务确保数据一致性
    $where = [
        'AND' => [
            'google_uid' => $user['google_uid'],
            'rtime[<]' => $location['rtime'],
            'accurateness[<]' => $MIN_DISTANCE_ACCURATENESS,
        ],
        'ORDER' => 'rtime DESC'
    ];
    $locA = $db->get('b_location', '*', $where);
    
    $where = [
        'AND' => [
            'google_uid' => $user['google_uid'],
            'rtime[>]' => $location['rtime'],
            'accurateness[<]' => $MIN_DISTANCE_ACCURATENESS,
        ],
        'ORDER' => 'rtime ASC'
    ];
    $locB = $db->get('b_location', '*', $where);
            
    
    /// 3. 计算用户新增的移动距离
    $disDelta = 0;
    if (!$locA && !$locB) {
        LOGD("当前位置没有相邻位置信息，忽略, locaiton=" . json_encode($location));
        return 0;
    }
    else if ($locA && $locB) {
        $disAB = greatCircleDistance($locA['longitude'], $locA['latitude'], $locB['longitude'], $locB['latitude']);
        $disAL = greatCircleDistance($locA['longitude'], $locA['latitude'], $location['longitude'], $location['latitude']);
        $disLB = greatCircleDistance($location['longitude'], $location['latitude'], $locB['longitude'], $locB['latitude']);
        
        $disDelta = $disAL + $disLB - $disAB;
        
        LOGD("用户({$user['name']}<{$user['email']}>)位置增量计算 disAL + disLB - disAB = ${disAL} + {$disLB} - {$disAB} = {$disDelta}");
    }
    else if ($locA && !$locB) {
        /// 当前位置没有更旧或更新的位置信息，则位置增量就是当前位置与更新位置之差
        $disDelta = greatCircleDistance($locA['longitude'], $locA['latitude'], $location['longitude'], $location['latitude']);
    }
    else if (!$locA && $locB) {
        /// 当前位置没有更旧或更新的位置信息，则位置增量就是当前位置与更新位置之差
        $disDelta = greatCircleDistance($locB['longitude'], $locB['latitude'], $location['longitude'], $location['latitude']);
    }
    else {
        LOGE("代码运行到了不可能的位置. " . var_export(debug_backtrace(), TRUE));
        return 0;
    }
    
    return $disDelta;
}


/**
 * 根据最新上报的位置信息，更新用户的统计数据
 */
function updateUserStatData($location) {
    global $db;
    
    $user = getByUID($location['uid']);
    if (!$user) {
        LOGE("编号为 {$uid} 的用户不存在");
        return FALSE;
    }
    
    $where = [
        'AND' => [
            'google_uid' => $user['google_uid']
        ]
    ];
    
    $stat = $db->get('b_stat', '*', $where);
    
    if (!$stat) {
        createUserStatData($uid);
    }
    $stat = $db->get('b_stat', '*', $where);
    
    $data = [];
    if ($stat['min_time'] > $location['rtime']) {
        $data['min_time'] = $location['rtime'];
        $stat['min_time'] = $data['min_time'];
    }
    if ($stat['max_time'] < $location['rtime']) {
        $data['max_time'] = $location['rtime'];
        $stat['max_time'] = $data['max_time'];
    }
    $d = distanceDelta($location);
    if ($d) {
        $data['distance'] = $stat['distance'] + $d;
        $data['distance_per_day'] = $data['distance'] / (($stat['max_time'] - $stat['min_time']) / 86400);
        LOGD("用户（{$user['name']}<{$user['email']}>）移动距离大于 0，移动了 {$d} 米，总计 {$data['distance']} 米，日均 {$data['distance_per_day']} 米");
    }
    
    if (!empty($data)) {
        $res = $db->update('b_stat', $data, $where);
        if (!$res) {
            LOGD(sprintf("更新失败: (%s): %s", $db->last_query(), var_export($db->error(), TRUE)));
            return FALSE;
        }
    }
    
    return TRUE;
}


/**
 * 新建用户统计数据
 */
function createUserStatData($uid) {
    global $db;
    global $MIN_DISTANCE_ACCURATENESS;
    
    $user = getByUID($uid);
    if (!$user) {
        LOGE("编号为 {$uid} 的用户不存在");
        return FALSE;
    }
    
    /// 计算用户移动距离
    $min_time = PHP_INT_MAX;
    $max_time = 0;
    
    $where = [
        'AND' => [
            'google_uid' => $user['google_uid'],
            'accurateness[<]' => $MIN_DISTANCE_ACCURATENESS,
        ],
        'ORDER' => 'rtime ASC',
    ];
    $locs = $db->select('b_location', ['latitude', 'longitude', 'rtime'], $where);
    
    $distance = 0;
    if ($locs) {
        $lastLat = NULL;
        $lastLng = NULL;
        $cnt = count($locs);
        
        /// 每毫秒大约计算 1 个位置，30s 大概计算 15w 个位置
        if ($cnt > 10000) {
            $s = round($cnt / 1000 * 2);
            set_time_limit($s);
            LOGN("将运行超时时间设置为 $s 秒");
        }
            
        
        foreach ($locs as $loc) {
            if ($lastLat !== NULL) {
                $d = greatCircleDistance($lastLng, $lastLat, $loc['longitude'], $loc['latitude']);
                $distance += $d;
                LOGD("计算用户({$user['name']}<{$user['email']}>)移动距离，移动了 {$d} 米，总计 {$distance} 米({$i}/{$cnt})");
            }
            else {
                $min_time = $loc['rtime'];
            }
            
            $lastLat = $loc['latitude'];
            $lastLng = $loc['longitude'];
            $max_time = $loc['rtime'];
            
            $i++;
        }
    }
            
    
    $data = [
        'ctime' => time(),
        'mtime' => time(),
        'distance' => $distance,
        'max_time' => $max_time,
        'min_time' => $min_time,
        'google_uid' => $user['google_uid'],
        'distance_per_day' => $distance / (($max_time - $min_time) / 86400),
    ];
    
    LOGD(var_export($data, TRUE));
    
    $ret = $db->insert('b_stat', $data);
    if (!$ret) {
        LOGD(sprintf("插入失败: (%s): %s", $db->last_query(), var_export($db->error(), TRUE)));
        return FALSE;
    }
    
    return TRUE;
}


/**
 * 计算两个以经纬度给出的点的大圆距离
 * 
 * @return float    两点的大圆距离，单位：米
 */
function greatCircleDistance($lon1, $lat1, $lon2, $lat2){ 
    return (2*atan2(sqrt(sin(($lat1-$lat2)*M_PI/180/2)
    *sin(($lat1-$lat2)*M_PI/180/2)+
    cos($lat2*M_PI/180)*cos($lat1*M_PI/180)
    *sin(($lon1-$lon2)*M_PI/180/2)
    *sin(($lon1-$lon2)*M_PI/180/2)),
    sqrt(1-sin(($lat1-$lat2)*M_PI/180/2)
    *sin(($lat1-$lat2)*M_PI/180/2)
    +cos($lat2*M_PI/180)*cos($lat1*M_PI/180)
    *sin(($lon1-$lon2)*M_PI/180/2)
    *sin(($lon1-$lon2)*M_PI/180/2))))*6378140;
}

?>

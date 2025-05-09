<?php
if (!defined('DEDEINC')) exit('dedebiz');
require_once(DEDEINC."/libraries/agent.class.php");
/**
 * 流量统计操作
 *
 * @version        $id:statistics.class.php 11:42 2022年03月26日 tianya $
 * @package        DedeBIZ.Libraries
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
class DedeStatistics {
    function __construct()
    {
    }
    //获取统计js
    function GetStat()
    {
        global $envs, $cfg_cookie_encode;
        $agent = new Agent();
        $pm = array();
        $pm['dduuid'] = GetCookie("DedeStUUID");
        if (empty($pm['dduuid'])) {
            $pm['dduuid'] = $this->_uniqidReal();
            PutCookie('DedeStUUID', $pm['dduuid'], 60 * 24 * 365);
        }
        $pm['ssid'] = session_id();
        if (empty($pm['ssid'])) {
            session_start();
            $pm['ssid'] = session_id();
        }
        $url_type = isset($_GET['url_type'])? $_GET['url_type'] : 0;
        $typeid = isset($_GET['typeid'])? $_GET['typeid'] : 0;
        $aid = isset($_GET['aid'])? $_GET['aid'] : 0;
        $value = isset($_GET['value'])? $_GET['value'] : '';
        $pm['browser'] = $agent->browser();
        $pm['device'] = $agent->device();
        $pm['device_type'] = $agent->deviceType();
        $pm['os'] = $agent->platform();
        $pm['t'] = time();
        $pm['created_date'] = MyDate("Ymd",$pm['t']);
        $pm['created_hour'] = MyDate("H",$pm['t']);
        $pm['url_type'] = isset($envs['url_type'])? $envs['url_type'] : $url_type;
        if ($crawler = $agent->robot($agent->getUserAgent())) {
            $pm['url_type'] = -1;
            $pm['browser'] = $crawler;
        }
        $pm['typeid'] = isset($envs['typeid'])? $envs['typeid'] : $typeid;
        $pm['aid'] = isset($envs['aid'])? $envs['aid'] : $aid;
        $pm['value'] = isset($envs['value'])? $envs['value'] : $value;
        ksort($pm);
        $pm['sign'] = sha1(http_build_query($pm).md5($cfg_cookie_encode));
        $pm['dopost'] = "stat";
        $url = $GLOBALS['cfg_cmspath'].'/apps/statistics.php?'.http_build_query($pm);
        return "
(function() {
    let u = '{$url}';
    var ms_ie = false;
    var ua = window.navigator.userAgent;
    if ((ua.indexOf('MSIE ') > -1) || (ua.indexOf('Trident/') > -1)) {
        ms_ie = true;
    }
    if (ms_ie) {
        var xhr;
        if (window.XMLHttpRequest) {
            xhr = new XMLHttpRequest();
        } else if (window.ActiveXObject) { //IE
            try {
                xhr = new ActiveXObject('Msxml2.XMLHTTP');
            } catch (e) {
                try {
                    xhr = new ActiveXObject('Microsoft.XMLHTTP');
                } catch (e) {}
            }
        }
        if (xhr) {
            xhr.open('GET', u, true);
            xhr.send(null);
        }
    } else {
        fetch(u);
    }
})();";
    }
    //统计
    function Record()
    {
        global $dsql, $cfg_cookie_encode;
        //进行统计
        $pm = array('dduuid','ssid','browser','device','device_type','os','t','created_date','created_hour','url_type','typeid','aid','value','sign');
        $pmvalue = array();
        foreach ($pm as $v) {
            $pmvalue[$v] = $_GET[$v];
        }
        ksort($pmvalue);
        $sign = $pmvalue['sign'];
        unset($pmvalue['sign']);
        if (time() - $pmvalue['t'] > 5) {
            die("DedeBIZ:time out");
        }
        $cs = sha1(http_build_query($pmvalue).md5($cfg_cookie_encode));
        if ($sign !== $cs) {
            die("DedeBIZ:check sign failed");
        }
        $pmvalue['ip'] = GetIP();
        $kstr = $vstr = array();
        foreach ($pmvalue as $key => $value) {
            $kstr[] = "`{$key}`";
            $vstr[] = "'".addslashes($value)."'";
        }
        $insql = "INSERT INTO `#@__statistics_detail`(".implode(",",$kstr).") VALUES (".implode(",",$vstr).")";
        return $dsql->ExecuteNoneQuery($insql);
    }
    //生成uuid
    function _uniqidReal($lenght = 13) {
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($lenght / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($lenght / 2));
        } else {
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $lenght);
    }
    function GetInfoByDateMulti($ds = array())
    {
        $results = array();
        foreach ($ds as $d) {
            $vv = $this->GetInfoByDate($d);
            $result[] = $vv;
        }
        return $result;
    }
    //获取某天的统计信息
    function GetInfoByDate($d=0)
    {
        global $dsql;
        if ($d == -1) {
            $row = $dsql->GetOne("SELECT * FROM `#@__statistics`ORDER BY pv DESC ");
            return array(
                "sdate" => $d,
                "pv" => $row['pv'],
                "uv" => $row['uv'],
                "ip" => $row['ip'],
                "vv" => $row['vv'],
            );
        }
        $today = MyDate("Ymd",time());
        if ($d==0) {
            $d = $today;
        }
        $d = intval($d);
        //如果统计数据中存在，则直接查询统计表
        $info = $dsql->GetOne("SELECT * FROM `#@__statistics` WHERE sdate = $d");
        if (is_array($info)) {
            return $info;
        }
        $pv = $dsql->GetOne("SELECT COUNT(*) as total FROM `#@__statistics_detail` WHERE created_date = $d AND url_type >= 0");
        $uv = $dsql->GetOne("SELECT COUNT(DISTINCT dduuid) as total FROM `#@__statistics_detail` WHERE created_date = $d AND url_type >= 0");
        $ip = $dsql->GetOne("SELECT COUNT(DISTINCT ip) as total FROM `#@__statistics_detail` WHERE created_date = $d AND url_type >= 0");
        $vv = $dsql->GetOne("SELECT COUNT(DISTINCT ssid) as total FROM `#@__statistics_detail` WHERE created_date = $d AND url_type >= 0");
        if ($d < intval($today)) {
            //缓存数据
            $insql = "INSERT INTO `#@__statistics` (`sdate`,`pv`,`uv`,`ip`,`vv`) VALUES ('$d', '{$pv['total']}','{$uv['total']}','{$ip['total']}','{$vv['total']}')";
            $dsql->ExecuteNoneQuery($insql);
        }
        return array(
            "sdate" => $d,
            "pv" => $pv['total'],
            "uv" => $uv['total'],
            "ip" => $ip['total'],
            "vv" => $vv['total'],
        );
    }
}
?>
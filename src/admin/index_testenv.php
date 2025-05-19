<?php
@set_time_limit(0);
require_once(dirname(__FILE__)."/config.php");
AjaxHead();
if (!function_exists('TestAdminPWD')) {
    //检查默认管理员类型和账号及密码
    function TestAdminPWD()
    {
        global $dsql;
        $sql = "SELECT usertype,userid,pwd FROM `#@__admin` WHERE `userid`='admin'";
        $row = $dsql->GetOne($sql);
        if (is_array($row)) {
            if ($row['pwd'] == 'f297a57a5a743894a0e4') {
                return -2;
            } else {
                return -1;
            }
        } else {
            return 0;
        }
    }
}
if (!function_exists('IsWritable')) {
    //检查data/common.inc.php是否可写
    function IsWritable($pathfile)
    {
        $isDir = substr($pathfile, -1) == '/' ? true : false;
        if ($isDir) {
            if (is_dir($pathfile)) {
                mt_srand((float)microtime() * 1000000);
                $pathfile = $pathfile.'x_'.uniqid(mt_rand()).'.tmp';
            } else if (@mkdir($pathfile)) {
                return IsWritable($pathfile);
            } else {
                return false;
            }
        }
        @chmod($pathfile, 0777);
        $fp = @fopen($pathfile, 'ab');
        if ($fp === false) return false;
        fclose($fp);
        $isDir && @unlink($pathfile);
        return true;
    }
}
//检查权限
$safeMsg = array();
$dirname = str_replace('index_body.php', '', strtolower($_SERVER['PHP_SELF']));
if (!DEDEX_SAFE_MODE) {
    $safeMsg[] = '系统运行环境为开发模式，建议您启用安全模式 <a href="index_body.php?dopost=safe_mode" class="btn btn-primary btn-xs">详情</a>';
}
if (!IsSSL()) {
    $safeMsg[] = '检查到网址非安全链接，建议您部署https';
}
if (IsWritable(DEDEDATA.'/common.inc.php')) {
    $safeMsg[] = '检查到data/common.inc.php数据库配置文件权限可以写入，建议您以最高管理员权限设置禁止写入和执行';
}
if (version_compare(PHP_VERSION, '5.3.0', '<')) {
    $safeMsg[] = '检查到php版本过低会导致无法操作后台，建议您升级到php.x';
}
if (preg_match("#admin#", $dirname)) {
    $safeMsg[] = '检查到后台管理文件夹命名为admin，建议您修改后台管理文件夹名称';
}
$rs = TestAdminPWD();
if ($rs < 0) {
    $linkurl = ' <a href="sys_admin_user.php" class="btn btn-primary btn-xs">修改</a>';
    switch ($rs) {
        case -1:
            $msg = "检查到默认管理员账号，建议您修改{$linkurl}";
            break;
        case -2:
            $msg = "检查到默认管理员账号和密码，建议您修改{$linkurl}";
            break;
    }
    $safeMsg[] = $msg;
}
?>
<?php
if (count($safeMsg) > 0) {
?>
<div class="alert alert-info">
    <ul>
        <?php
        $i = 1;
        foreach ($safeMsg as $key => $val) {
        ?>
        <li><?php echo $i;?>.<?php echo $val;?></li>
        <?php
        $i++;
        }
        ?>
    </ul>
</div>
<?php
}
?>
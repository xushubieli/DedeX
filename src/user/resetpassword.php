<?php
/**
 * 修改密码
 * 
 * @version        $id:resetpassword.php 8:38 2010年7月9日 tianya $
 * @package        DedeBIZ.User
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require_once(dirname(__FILE__)."/config.php");
require_once(DEDEMEMBER."/inc/inc_pwd_functions.php");
if (empty($dopost)) $dopost = '';
$id = isset($id) ? intval($id) : 0;
if ($dopost == "") {
    include(dirname(__FILE__)."/templets/resetpassword.htm");
} elseif ($dopost == "getpwd") {
    //验证验证码
    if (!isset($vdcode)) $vdcode = '';
    $svali = GetCkVdValue();
    $type = intval($type);
    $mail = HtmlReplace($mail, 0);
    if (strtolower($vdcode) != $svali || $svali == '') {
        ResetVdValue();
        ShowMsg("验证码输入错误", "-1");
        exit();
    }
    //验证邮箱
    if (empty($mail) && empty($userid)) {
        showmsg('请输入账号或邮箱', '-1');
        exit;
    } else if (!empty($mail) && !preg_match("#(.*)@(.*)\.(.*)#", $mail)) {
        showmsg('请输入正确的邮箱格式', '-1');
        exit;
    } else if (CheckUserID($userid, '', false) != 'ok') {
        ShowMsg("您输入的账号{$userid}不合法", "-1");
        exit();
    }
    $member = member($mail, $userid);
    //以邮件方式取回密码
    if ($type == 1) {
        //判断系统邮件服务是否开启
        if ($cfg_sendmail_bysmtp == "Y") {
            sn($member['mid'], $userid, $member['email']);
        } else {
            showmsg('邮件服务暂未开启，请联系管理员', 'login.php');
            exit();
        }
        //以安全问题取回密码；
    } else if ($type == 2) {
        if ($member['safequestion'] == 0) {
            showmsg('您尚未设置安全密码，请通过邮件方式重设密码', 'login.php');
            exit;
        }
        require_once(dirname(__FILE__)."/templets/resetpassword3.htm");
    }
    exit();
} else if ($dopost == "safequestion") {
    $mid = preg_replace("#[^0-9]#", "", $id);
    $sql = "SELECT safequestion,safeanswer,userid,email FROM `#@__member` WHERE mid = '$mid'";
    $row = $db->GetOne($sql);
    if (empty($safequestion)) $safequestion = '';
    if (empty($safeanswer)) $safeanswer = '';
    if ($row['safequestion'] === $safequestion && $row['safeanswer'] === $safeanswer) {
        sn($mid, $row['userid'], $row['email'], 'N');
        exit();
    } else {
        ShowMsg("安全问题或答案回答错误", "login.php");
        exit();
    }
} else if ($dopost == "getpasswd") {
    //修改密码
    if (empty($id)) {
        ShowMsg("请不要非法提交", "login.php");
        exit();
    }
    $mid = preg_replace("#[^0-9]#", "", $id);
    $row = $db->GetOne("SELECT * FROM `#@__pwd_tmp` WHERE mid = '$mid'");
    if (empty($row)) {
        ShowMsg("请不要非法提交", "login.php");
        exit();
    }
    if (empty($setp)) {
        $tptim = (60 * 60 * 24 * 3);
        $dtime = time();
        if ($dtime - $tptim > $row['mailtime']) {
            $db->ExecuteNoneQuery("DELETE FROM `#@__pwd_tmp` WHERE `md` = '$id';");
            ShowMsg("临时验证码修改期限已过期", "login.php");
            exit();
        }
        require_once(dirname(__FILE__)."/templets/resetpassword2.htm");
    } elseif ($setp == 2) {
        if (isset($key)) $pwdtmp = $key;
        $sn = md5(trim($pwdtmp));
        if ($row['pwd'] == $sn) {
            if ($pwd != "") {
                if ($pwd == $pwdok) {
                    $pp = "pwd";
                    if (function_exists('password_hash')) {
                        $pp = "pwd_new";
                        $pwdok = password_hash($pwdok, PASSWORD_BCRYPT);
                    } else {
                        $pwdok = md5($pwdok);
                    }
                    $sql = "DELETE FROM `#@__pwd_tmp` WHERE `mid` = '$id';";
                    $db->ExecuteNoneQuery($sql);
                    $sql = "UPDATE `#@__member` SET `$pp` = '$pwdok' WHERE `mid` = '$id';";
                    if ($db->ExecuteNoneQuery($sql)) {
                        ShowMsg('修改密码成功，请牢记新密码', 'login.php');
                        exit;
                    }
                }
            }
            ShowMsg('新密码为空或填写不一致', '-1');
            exit;
        }
        ShowMsg('临时验证码错误', '-1');
        exit;
    }
}
?>
<?php
/**
 * 退出登录
 *
 * @version        $id:exit.php 19:09 2010年7月12日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__).'/../system/common.inc.php');
require_once(DEDEINC.'/userlogin.class.php');
$cuserLogin = new userLogin();
$cuserLogin->exitUser();
if (empty($needclose)) {
    header('Location: index.php');
} else {
    $msg = "<script>
    if (document.all) window.opener=true;
    window.close();
    </script>";
    echo $msg;
}
?>
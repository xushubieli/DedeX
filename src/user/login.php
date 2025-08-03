<?php
/**
 * 会员登录
 * 
 * @version        $id:login.php 8:38 2010年7月9日 tianya $
 * @package        DedeX.User
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
$gourl = RemoveXSS($gourl);
if ($cfg_ml->IsLogin()) {
    header('Location: index.php');
    exit();
}
require_once(dirname(__FILE__)."/templets/login.htm");
?>
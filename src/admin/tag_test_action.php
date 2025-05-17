<?php
/**
 * 标签调用测试操作
 *
 * @version        $id:tag_test_action.php 23:07 2010年7月20日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
if (DEDEX_SAFE_MODE) {
    die(DedeAlert("系统已启用安全模式，无法使用当前功能", ALERT_DANGER));
}
CheckPurview('temp_Test');
require_once(DEDEINC."/archive/partview.class.php");
CheckCSRF();
if (empty($partcode)) {
    ShowMsg('标签测试失败，请稍后重试', 'javascript:;');
    exit;
}
$partcode = stripslashes($partcode);
if (empty($typeid)) $typeid = 0;
if (empty($showsource)) $showsource = '';
if ($typeid > 0) $pv = new PartView($typeid);
else $pv = new PartView();
$pv->SetTemplet($partcode, "string");
if ($showsource == "" || $showsource == "yes") {
    echo "<pre>".dede_htmlspecialchars($partcode)."</pre>";
    echo "<hr>";
}
$pv->Display();
?>
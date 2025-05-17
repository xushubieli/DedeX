<?php
/**
 * 标签调用测试
 *
 * @version        $id:tag_test.php 23:07 2010年7月20日 tianya $
 * @package        DedeX.Administrator
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__)."/config.php");
if (DEDEX_SAFE_MODE) {
    die(DedeAlert("系统已启用安全模式，无法使用当前功能", ALERT_DANGER));
}
CheckPurview('temp_Other');
require_once(DEDEINC."/typelink/typelink.class.php");
include DedeInclude('templets/tag_test.htm');
?>
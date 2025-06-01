<?php
/**
 * DedeX管理员密码修改工具，改完密码记得删除此文件，别留着过年，老铁们～
 * 
 * @version        $id:resetpwd.php tianya $
 * @package        DedeX.Tools
 * @license        GNU GPL v2 (/license.txt)
 * 
 */
define('DEDEX_REPWD_VER', '1.0.2');
/**
 * ToolAlert
 *
 * @param  mixed $content
 * @param  mixed $colors
 * @return string
 */
function ToolAlert($content, $colors = array('#cfe2ff', '#b6d4fe', '#084298'))
{
    define('TOOLS_ALERT_TPL', '<div style="position:relative;padding:0.75rem 1.25rem;margin-bottom:1rem;width:auto;font-size:14px;color:~color~;background:~background~;border-color:~border~;border:1px solid transparent;border-radius:0.5rem">~content~</div>');
    list($background, $border, $color) = $colors;
    return str_replace(array('~color~', '~background~', '~border~', '~content~'), array($color, $background, $border, $content), TOOLS_ALERT_TPL);
}
if (!file_exists(dirname(__FILE__).'/system/common.inc.php')) {
    echo ToolAlert("请把resetpwd.php文件放置网站根目录，通过http://域名/resetpwd.php访问操作");
    exit();
}
require_once dirname(__FILE__).'/system/common.inc.php';
require_once(DEDEINC.'/libraries/oxwindow.class.php');
$dopost = isset($dopost)? $dopost : '';
$adminname = isset($adminname)? HtmlReplace($adminname, -1) : '';
$newpwd = isset($newpwd)? $newpwd : '';
$renewpwd = isset($renewpwd)? $renewpwd : '';
$dbpwd = isset($dbpwd)? $dbpwd : '';
if ($dopost === 'change') {
    if (empty($adminname)) {
        ShowMsg("管理员账号不能为空", -1);
        exit();
    }
    if (empty($newpwd) || $newpwd !== $renewpwd) {
        ShowMsg("管理员密码不能为空，两次输入密码必须一致", -1);
        exit();
    }
    if (empty($dbpwd) || $dbpwd !== $cfg_dbpwd) {
        ShowMsg("数据库连接密码不能为空", -1);
        exit();
    }
    $admin = $dsql->GetOne("SELECT * FROM `#@__admin` WHERE `userid`='$adminname'");
    if (empty($admin)) {
        ShowMsg("输入的管理员账号不存在", -1);
        exit();
    }
    if (function_exists('password_hash')) {
        $pwdm = "pwd='',pwd_new='".password_hash($newpwd, PASSWORD_BCRYPT)."'";
        $pwd = "pwd='',pwd_new='".password_hash($newpwd, PASSWORD_BCRYPT)."'";
    } else {
        $pwdm = "pwd='".md5($newpwd)."'";
        $pwd = "pwd='".substr(md5($newpwd), 5, 20)."'";
    }
    $id = $admin['id'];
    $query = "UPDATE `#@__admin` SET $pwd WHERE id='$id'";
    $dsql->ExecuteNoneQuery($query);
    $query = "UPDATE `#@__member` SET $pwdm WHERE mid='$id'";
    $dsql->ExecuteNoneQuery($query);
    ShowMsg("管理员密码成功修改为<code>{$newpwd}</code>，记得删除resetpwd.php文件", 'javascript:;');
    exit();
}
$wintitle = "DedeX管理员密码修改工具".DEDEX_REPWD_VER;
$win = new OxWindow();
$win->Init(basename(__FILE__), 'js/blank.js', 'POST');
$win->AddHidden('dopost', 'change');
$win->AddHidden('token', $_SESSION['token']);
$win->AddMsgItem('<tr>
    <td width="260">管理员账号</td>
    <td><input type="text" name="adminname" id="adminname" class="admin-input-lg" placeholder="请输入管理员账号"></td>
</tr>
<tr>
    <td>管理员新密码</td>
    <td><input type="password" name="newpwd" id="newpwd" class="admin-input-lg" placeholder="请输入新的管理员密码"></td>
</tr>
<tr>
    <td>验证管理员新密码</td>
    <td><input type="password" name="renewpwd" id="renewpwd" class="admin-input-lg" placeholder="请再次输入管理员密码"></td>
</tr>
<tr>
    <td>数据库连接密码</td>
    <td><input type="password" name="dbpwd" id="dbpwd" class="admin-input-lg" placeholder="请输入数据库连接密码">（查看根目录/data/common.inc.php文件中的$cfg_dbpwd）</td>
</tr>');
$winform = $win->GetWindow('ok');
$win->Display();
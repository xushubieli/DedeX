<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
/**
 * 演示标签
 *
 * @version        $id:demotag.lib.php 9:29 2010年7月6日 tianya $
 * @package        DedeX.Taglib
 * @license        GNU GPL v2 (/license.txt)
 */
function lib_demotag(&$ctag, &$refObj)
{
    global $dsql, $envs;
    $attlist = "row|10,titlelen|30";
    FillAttsDefault($ctag->CAttribute->Items, $attlist);
    extract($ctag->CAttribute->Items, EXTR_SKIP);
    $revalue = '';
    //不能用echo之类语法，把最终返回值传给$revalue
    $revalue = '您好，欢迎使用DedeX';
    return $revalue;
}
?>
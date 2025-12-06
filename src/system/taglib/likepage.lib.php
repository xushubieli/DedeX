<?php
if (!defined('DEDEINC')) {http_response_code(403); exit();}
/**
 * 单页文档相同标识标签
 *
 * @version        $id:likepage.lib.php 9:29 2010年7月6日 tianya $
 * @package        DedeX.Taglib
 * @license        GNU GPL v2 (/license.txt)
 */
require_once(dirname(__FILE__).'/likesgpage.lib.php');
function lib_likepage(&$ctag, &$refObj)
{
    return lib_likesgpage($ctag, $refObj);
}
?>
<?php
if (!defined('DEDEINC')) exit('dedebiz');
/**
 * PHP标签
 *
 * @version        $id:php.lib.php1 9:29 2010年7月6日 tianya $
 * @package        DedeBIZ.Taglib
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
function lib_php(&$ctag, &$refObj)
{
    global $dsql;
    $phpcode = trim($ctag->GetInnerText());
    if ($phpcode == '')
    return '';
    ob_start();
    extract($GLOBALS, EXTR_SKIP);
    @eval($phpcode);
    $revalue = ob_get_contents();
    ob_clean();
    return $revalue;
}
?>
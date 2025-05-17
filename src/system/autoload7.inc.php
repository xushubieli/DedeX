<?php
if (!defined('DEDEINC')) exit('dedex');
/**
 * @version        $id:autoload7.inc.php 17:44 2020-09-22 tianya $
 * @package        DedeX.Libraries
 * @license        GNU GPL v2 (/license.txt)
 */
function dede_autoloader($classname)
{
    $classname = preg_replace("/[^0-9a-z_]/i", '', $classname);
    $classname = strtolower($classname);
    if (class_exists($classname)) {
        return TRUE;
    }
    if (in_array($classname, array("archives","freelist","listview","partview","searchview","sglistview","sgpage","specview","taglist"))) {
        $classname = "archive/".$classname;
    }
    $libclassfile = $classname.'.class.php';
    if (is_file(DEDEINC.'/'.$libclassfile )) {
        require DEDEINC.'/'.$libclassfile;
    } else {
        if (DEBUG_LEVEL === TRUE) {
            echo '<pre>';
            echo $classname.'类找不到';
            echo '</pre>';
            exit();
        } else {
            header ("location:/404.html");
            die ();
        }
    }
}
spl_autoload_register('dede_autoloader');
?>
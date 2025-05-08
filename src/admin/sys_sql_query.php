<?php
/**
 * SQL命令工具
 *
 * @version        $id:sys_sql_query.php 22:28 2010年7月20日 tianya $
 * @package        DedeBIZ.Administrator
 * @copyright      Copyright (c) 2022 DedeBIZ.COM
 * @license        GNU GPL v2 (https://www.dedebiz.com/license)
 * @link           https://www.dedebiz.com
 */
require(dirname(__FILE__)."/config.php");
if (DEDEBIZ_SAFE_MODE) {
    die(DedeAlert("系统已启用安全模式，无法使用当前功能",ALERT_DANGER));
}
CheckPurview('sys_Data');
if (empty($dopost)) $dopost = '';
//查看表结构
if ($dopost == "viewinfo") {
    CheckCSRF();
    if ($cfg_dbtype == 'sqlite') {
        echo "<xmp>";
        if (empty($tablename)) {
            echo "没有指定表名";
        } else {
            //获取创建表的SQL语句
            $dsql->SetQuery("SELECT sql FROM sqlite_master WHERE type='table' AND name='$tablename'");
            $dsql->Execute('me');
            $row = $dsql->GetArray('me', SQLITE3_ASSOC);
            if ($row) {
                $createTableSql = str_replace(" ", "\r\n", $row['sql']);
                echo trim($createTableSql)."\n\n";
            }
        }
        echo '</xmp>';
        exit();
    } else {
        if (empty($tablename)) {
            echo "没有指定表名";
        } else {
            $dsql->SetQuery("SHOW CREATE TABLE ".$dsql->dbName.".".$tablename);
            $dsql->Execute('me');
            $row2 = $dsql->GetArray('me', MYSQL_BOTH);
            $ctinfo = $row2[1];
            echo "<xmp>".trim($ctinfo)."</xmp>";
        }
    }
    exit();
}
//优化表
else if ($dopost == "opimize") {
    CheckCSRF();
    if (empty($tablename)) {
        echo "没有指定表名";
    } else {
        if ($cfg_dbtype == 'sqlite') {
            $rs = $dsql->ExecuteNoneQuery("VACUUM");
            if ($rs) {
                echo "执行优化表 {$tablename} 完成<br>";
            } else {
                echo "执行优化表 {$tablename} 失败，原因是：".$dsql->GetError();
            }
        }  else { 
            $rs = $dsql->ExecuteNoneQuery("OPTIMIZE TABLE `$tablename`");
            if ($rs)  echo "执行优化表".$tablename."完成<br>";
            else echo "执行优化表".$tablename."失败，原因是：".$dsql->GetError();
        }
    }
    exit();
}
//优化全部表
else if ($dopost == "opimizeAll") {
    CheckCSRF();
    $dsql->SetQuery("SHOW TABLES");
    $dsql->Execute('t');
    if ($cfg_dbtype == 'sqlite') {
        $rs = $dsql->ExecuteNoneQuery("VACUUM");
        if ($rs) {
            echo "执行数据库完成<br>";
        } else {
            echo "执行数据库失败，原因是：".$dsql->GetError();
        }
    } else {
        while ($row = $dsql->GetArray('t', MYSQL_BOTH)) {
            $rs = $dsql->ExecuteNoneQuery("OPTIMIZE TABLE `{$row[0]}`");
            if ($rs) {
                echo "优化表{$row[0]}完成<br>";
            } else {
                echo "优化表{$row[0]}失败，原因是: ".$dsql->GetError();
            }
        }
    }
    exit();
}
//修复表
else if ($dopost == "repair") {
    CheckCSRF();
    if (empty($tablename)) {
        echo "没有指定表名";
    } else {
        if ($cfg_dbtype =='sqlite') {
            //SQLite数据库使用VACUUM尝试修复和优化
            $rs = $dsql->ExecuteNoneQuery("VACUUM");
            if ($rs) {
                echo "对表 {$tablename} 尝试修复和优化完成<br>";
            } else {
                echo "对表 {$tablename} 尝试修复和优化失败，原因是：".$dsql->GetError();
            }
        } else {
            //非SQLite数据库（如 MySQL）使用REPAIR TABLE语句
            $rs = $dsql->ExecuteNoneQuery("REPAIR TABLE `{$tablename}`");
            if ($rs) {
                echo "修复表 {$tablename} 完成<br>";
            } else {
                echo "修复表 {$tablename} 失败，原因是：".$dsql->GetError();
            }
        }
    }
    exit();
}
//修复全部表
else if ($dopost == "repairAll") {
    CheckCSRF();
    if ($cfg_dbtype =='sqlite') {
        //SQLite 数据库使用VACUUM尝试修复和优化整个数据库
        $rs = $dsql->ExecuteNoneQuery("VACUUM");
        if ($rs) {
            echo "对所有表尝试修复和优化完成<br>";
        } else {
            echo "对所有表尝试修复和优化失败，原因是：".$dsql->GetError();
        }
    } else {
        $dsql->SetQuery("Show Tables");
        $dsql->Execute('t');
        while ($row = $dsql->GetArray('t', MYSQL_BOTH)) {
            $rs = $dsql->ExecuteNoneQuery("REPAIR TABLE `{$row[0]}`");
            if ($rs) {
                echo "修复表 {$row[0]} 完成<br>";
            } else {
                echo "修复表 {$row[0]} 失败，原因是: ".$dsql->GetError();
            }
        }
    }
    exit();
}
//执行SQL语句
else if ($dopost == "query") {
    CheckCSRF();
    $mysqlVersions = explode('.',trim($row[0]));
    $mysqlVersion = $mysqlVersions[0].".".$mysqlVersions[1];
    $sqlquery = trim(stripslashes($sqlquery));
    if (preg_match("#drop(.*)table#i", $sqlquery) || preg_match("#drop(.*)database#", $sqlquery)) {
        echo "删除数据表或数据库的语句不允许在这里执行";
        exit();
    }
    if ($mysqlVersion >= 4.1 && preg_match('#CREATE#i', $sqlquery)) {
        $sql4tmp = "ENGINE=MyISAM DEFAULT CHARSET=".$$cfg_db_language;
        $sqlquery = preg_replace("#TYPE=MyISAM#i", $sql4tmp, $sqlquery);
    }
    echo '<link rel="stylesheet" href="/static/web/css/bootstrap.min.css">';
    //运行查询语句
    if (preg_match("#^select #i", $sqlquery)) {
        $dsql->SetQuery($sqlquery);
        $dsql->Execute();
        if ($dsql->GetTotalRow() <= 0) {
            echo "运行SQL：{$sqlquery}无返回记录<br>";
        } else {
            echo "运行SQL：{$sqlquery}共有".$dsql->GetTotalRow()."条记录，最大返回100条";
        }
        $j = 0;
        while ($row = $dsql->GetArray()) {
            $j++;
            if ($j > 100) {
                break;
            }
            echo "<hr>";
            echo "记录：$j";
            echo "<hr>";
            foreach ($row as $k => $v) {
                echo "{$k}：{$v}<br>\r\n";
            }
        }
        exit();
    }
    if ($querytype == 2) {
        //普通的SQL语句
        $sqlquery = str_replace("\r", "", $sqlquery);
        $sqls = preg_split("#;[ \t]{0,}\n#", $sqlquery);
        $nerrCode = '';
        $i = 0;
        foreach ($sqls as $q) {
            $q = trim($q);
            if ($q == "") {
                continue;
            }
            $dsql->ExecuteNoneQuery($q);
            $errCode = trim($dsql->GetError());
            if ($errCode == "") {
                $i++;
            } else {
                $nerrCode .= "执行".$q."出错，错误提示：".$errCode."";
            }
        }
        echo "成功执行{$i}个SQL语句";
        echo $nerrCode;
    } else {
        $dsql->ExecuteNoneQuery($sqlquery);
        $nerrCode = trim($dsql->GetError());
        echo "成功执行1个SQL语句";
        echo $nerrCode;
    }
    exit();
} else if ($dopost == "docs") {
    if ($cfg_dbtype == 'sqlite') {
        die("SQLite数据库不支持此功能");
    }
    CheckCSRF();
    $dsql->SetQuery("SHOW TABLES");
    $dsql->Execute('t');
    $output = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/static/web/css/bootstrap.min.css">
    <link rel="stylesheet" href="/static/web/css/admin.css">
    <title>DedeBIZ数据库文档</title>
</head>
<body>
    <div class="container-fluid">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index_body.php">后台面板</a></li>
        <li class="breadcrumb-item active"><a href="sys_sql_query.php">SQL命令工具</a></li>
        <li class="breadcrumb-item">数据库文档</li>
    </ol>';
    while ($row = $dsql->GetArray('t', MYSQL_BOTH)) {
        $tableName = $row[0];
        $output .= '<div class="card shadow-sm mb-3">
            <div class="card-header">'.$tableName.'表</div>
            <div class="card-body">
            <div class="table-responsive">';
        //获取表的注释
        $dsql->SetQuery("SELECT TABLE_COMMENT FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName'");
        $dsql->Execute('c');
        $tableCommentRow = $dsql->GetArray('c', MYSQL_BOTH);
        $tableComment = $tableCommentRow['TABLE_COMMENT'];
        if (!empty($tableComment)) {
            $output .= '<p>表注释：'.$tableComment.'</p>';
        }
        //获取表的字段信息
        $dsql->SetQuery("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$tableName'");
        $dsql->Execute('col');
        $output .= '<table class="table table-borderless table-hover">
            <thead>
                <tr>
                    <th width="10%">字段名</th>
                    <th width="20%">类型</th>
                    <th width="10%">是否可为空</th>
                    <th width="10%">默认值</th>
                    <th scope="col">字段注释</th>
                </tr>
            </thead>
            <tbody>';
        while ($colRow = $dsql->GetArray('col', MYSQL_BOTH)) {
            $columnName = $colRow['COLUMN_NAME'];
            $columnType = $colRow['COLUMN_TYPE'];
            $isNullable = $colRow['IS_NULLABLE'];
            $columnDefault = $colRow['COLUMN_DEFAULT'];
            $columnComment = $colRow['COLUMN_COMMENT'];

            $output .= '<tr>
                <td>'.$columnName.'</td>
                <td>'.$columnType.'</td>
                <td>'.$isNullable.'</td>
                <td>'.($columnDefault !== null? $columnDefault : '无').'</td>
                <td>'.$columnComment. '</td>
            </tr>';
        }
        $output .= '</tbody>
        </table>
        </div>
    </div>
    </div>';
    }
    $output .= '<p class="text-center">版权所有 &copy; '.date('Y').' <a href="https://www.dedebiz.com/?from=dbdocs" class="text-success">DedeBIZ</a> 保留所有权利</p>
    </div>
</body>
</html>';
    //输出网页文档
    header('Content-Type: text/html');
    echo $output;
    exit();
}
make_hash();
include DedeInclude('templets/sys_sql_query.htm');
?>
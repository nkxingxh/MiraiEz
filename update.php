<?php

/**
 * MiraiEz Copyright (c) 2021-2023 NKXingXh
 * License AGPLv3.0: GNU AGPL Version 3 <https://www.gnu.org/licenses/agpl-3.0.html>
 * This is free software: you are free to change and redistribute it.
 * There is NO WARRANTY, to the extent permitted by law.
 * 
 * Github: https://github.com/nkxingxh/MiraiEz
 */

if (php_sapi_name() !== 'cli') {
    exit('请在 cli 环境中运行');
}

require_once './loader.php';

//选择更新版本
echo "请选择更新分支及版本:\n  1. 发行版\n  2. 主分支\n  3. 开发分支\n  0. 取消更新\nMiraiEz > ";
$answer = (int) trim(fgets(STDIN));

switch ($answer) {
    case 1:
        echo "请输入更新版本 (留空自动获取最新版本) > ";
        $answer = trim(fgets(STDIN));
        if (empty($answer)) $answer = null;
        update_releases($answer);
        break;
    case 2:
        break;
    case 3:
        break;
    default:
        exit("操作已取消.\n");
}

function update_releases($ver = null)
{
    if (empty($ver)) {
        echo "正在检查新版本...\n";
        $url = "https://api.github.com/repos/nkxingxh/miraiez/releases/latest";
        $resp = CurlGET($url);
        $resp = json_decode($resp, true);
        if (empty($resp)) {
            echo "获取最新版本信息失败!\n";
            exit(-1);
        }

        echo '当前版本: v' . MIRAIEZ_VERSION . ', 最新版本: ' . $resp['name'] . "\n";

        //对比版本
        switch (version_compare($resp['tag_name'], MIRAIEZ_VERSION)) {
            case 0:
                echo '本地版本已是最新版本, 要强制更新吗? [y/N] ';
                break;
            case 1:
                echo '最新版本高于本地版本, 确认继续更新吗? [y/N] ';
                break;
            case -1:
                echo '本地版本高于最新版本, 要强制更新吗? [y/N] ';
                break;
        }
        if (!get_input_YesOrNo(null, false)) exit("操作已取消.\n");

        $ver = $resp['tag_name'];
    }
    //覆盖检查
    if (file_exists('./update.zip')) {
        echo '发现已存在的 update.zip 是否覆盖? [Y/n]';
        if (!get_input_YesOrNo(null, true)) exit("操作已取消.\n");
        unlink('./update.zip');
    }
    //下载 zip 包
    $url = "https://api.github.com/repos/nkxingxh/MiraiEz/zipball/$ver";
    //$cmd = 'curl -o update.zip ' . escapeshellarg($url);
    $cmd = 'wget -O update.zip ' . escapeshellarg($url);
    if (system($cmd, $return_code) === false || $return_code !== 0) {
        echo "下载更新包失败! " . (isset($return_code) ? "cURL 返回码: $return_code" : '运行 cURL 命令失败') . "\n";
        exit(-1);
    }
    if (!file_exists('./update.zip')) {
        echo "下载文件失败! 未找到 update.zip\n";
        exit(-1);
    }

    update_zip();
}

function update_zip($zip_file = './update.zip', $tmp_dir = './update_tmp')
{
    $tmp_dir = strtolower($tmp_dir);
    echo "正在解压更新包...\n";
    //创建临时文件夹
    if (!file_exists($tmp_dir) || is_dir($tmp_dir)) {
        mkdir($tmp_dir);
    }
    //解压 zip 包
    $zip = new ZipArchive;
    if ($zip->open($zip_file) === TRUE) {
        $zip->extractTo("$tmp_dir/update/");
        $zip->close();
        //echo "解压更新包成功, 正在替换文件...\n";
    } else {
        echo "解压更新包失败!\n";
        exit(-1);
    }
    //获取更新文件/目录列表
    $update_dir = glob("$tmp_dir/update/*");
    if (empty($update_dir)) {
        echo "未找到解压后的更新内容目录!\n";
        exit(-1);
    } else {
        $update_dir = $update_dir[0];
    }

    //备份文件
    echo "正在备份插件与数据...\n";
    $backups = array(
        //dirname(dataDir),
        'config',
        'plugins',
        'config.php'
    );
    mkdir("$tmp_dir/backup");
    foreach ($backups as $v) {
        if (!file_exists($v)) continue;
        echo "[mv] ./$v $tmp_dir/backup/$v\n";
        continue;
        if (!rename("./$v", "$tmp_dir/backup/$v")) {
            echo "移动文件(或目录) $v 失败!\n";
            exit(-1);
        }
    }
    //删除老文件
    echo "正在移除老文件...\n";
    $dir_arr = glob('./*');
    foreach ($dir_arr as $v) {
        if ($v == $tmp_dir || strtolower(substr($v, 0, 7)) == './data_') continue;
        //unlink($v);
        echo "[rm] $v\n";
    }
    //移动更新内容
    echo "正在移动更新内容...\n";
    $update_arr = glob("$update_dir/*");
    $update_dir_len = strlen($update_dir);
    foreach ($update_arr as $v) {
        $fn = substr($v, - (strlen($v) - $update_dir_len - 1));
        //rename($v, "./$fn");
        echo "[mv] $v ./$fn\n";
    }

    unlink('./update.zip');
    unlink($tmp_dir);
}

/**
 * 获取用户输入
 * @param string $input 用户输入字符串
 * @param bool $default 默认值
 * @return bool yes or no
 */
function get_input_YesOrNo($input = null, $default = false)
{
    if ($input === null) {
        $input = fgets(STDIN);
    }
    $input = strtolower(trim($input));
    if ($default) {
        return !in_array($input, ['n', 'no']);
    } else {
        return in_array($input, ['y', 'yes']);
    }
}

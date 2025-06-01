<?php

require __DIR__.'/../vendor/autoload.php';

use ReactphpX\FilesystemS3\Adapter;
use React\EventLoop\Loop;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\DirectoryInterface;



$OSS_ACCESS_KEY_ID="";
$OSS_ACCESS_KEY_SECRET="";
$OSS_BUCKET="xxxx";
$OSS_ENDPOINT="https://oss-cn-beijing.aliyuncs.com";

// 初始化 S3 适配器
$bucket = $OSS_BUCKET;
$adapter = new Adapter([
    'endpoint' => $OSS_ENDPOINT,
    // 'version' => 'latest',
    'version' => '2006-03-01',
    'region'  => 'cn-beijing',
    'use_path_style_endpoint' => false,
    // 'credentials' => $provider,
    'credentials' => [
        'key'    => $OSS_ACCESS_KEY_ID,
        'secret' => $OSS_ACCESS_KEY_SECRET,
        // https://help.aliyun.com/zh/oss/developer-reference/use-temporary-access-credentials-provided-by-sts-to-access-oss#b8aeaf6650mnz
        // 'token' => '',
    ],
], $bucket);

// 示例：上传文件
$destinationPath = 'abc/example.txt';


$adapter->file($destinationPath)->putContents('Hello World!')->then(function () use ($adapter, $destinationPath) {
    echo "File uploaded to S3: $destinationPath\n";
}, function ($error) {
    echo "Error uploading file: " . $error->getMessage() . "\n";
});

// 示例：读取文件

$adapter->file($destinationPath)->getContents()->then(function ($content) {
    echo "File content: $content\n";
}, function ($error) {
    echo "Error reading file: " . $error->getMessage() . "\n";
});

// 示例：删除文件

// $adapter->file($destinationPath)->unlink()->then(function () use ($adapter, $destinationPath) {
//     echo "File deleted from S3: $destinationPath\n";
// }, function ($error) {
//     echo "Error deleting file: " . $error->getMessage() . "\n";
// });

// 示例：列出目录

$adapter->directory('abc')->ls()->then(function ($nodes) {
    foreach ($nodes as $node) {
        if ($node instanceof FileInterface) {
            echo "File: " . $node->path() . "\n";
        } elseif ($node instanceof DirectoryInterface) {
            echo "Directory: " . $node->path() . "\n";
        }
    }
}, function ($error) {
    echo "Error listing directory: " . $error->getMessage() . "\n";
});

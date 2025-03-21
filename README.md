# reactphp-x/filesystem-s3 

## install

```
composer require reactphp-x/filesystem-s3 -vvv
```

## usage

```php
<?php

require __DIR__.'/../vendor/autoload.php';

use ReactphpX\FilesystemS3\Adapter;
use React\EventLoop\Loop;
use React\Filesystem\Node\FileInterface;
use React\Filesystem\Node\DirectoryInterface;

// 初始化 S3 适配器
$bucket = 'xxxx';
$adapter = new Adapter([
    'endpoint' => 'xxxx',
    'version' => 'latest',
    'region'  => 'us-east-1',
    'use_path_style_endpoint' => true,
    'credentials' => [
        'key'    => 'xxx',
        'secret' => 'xxxx',
    ],
], $bucket);

// 示例：上传文件
$destinationPath = 'uploads/example.txt';


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

$adapter->directory('')->ls()->then(function ($nodes) {
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


```
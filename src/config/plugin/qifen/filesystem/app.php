<?php
return [
    'enable'   => true,
    'default' => 'local',
    'hash_append_rand' => false,
    'max_size' => 1024 * 1024 * 10, //单个文件大小10M
    'ext_yes' => [], //允许上传文件类型 为空则为允许所有
    'ext_no' => [], // 不允许上传文件类型 为空则不限制
    'storage' => [
        'public' => [
            'driver' => \Qifen\Filesystem\Adapter\LocalAdapterFactory::class,
            'root' => public_path(),
            'url' => '//127.0.0.1:8787' // 静态文件访问域名
        ],
        'local' => [
            'driver' => \Qifen\Filesystem\Adapter\LocalAdapterFactory::class,
            'root' => runtime_path()
        ],
        'ftp' => [
            'driver' => \Qifen\Filesystem\Adapter\FtpAdapterFactory::class,
            'host' => 'ftp.xxx.com',
            'username' => 'username',
            'password' => 'password',
            // 'port' => 21,
            // 'root' => '/path/to/root',
            // 'passive' => true,
            // 'ssl' => true,
            // 'timeout' => 30,
            // 'ignorePassiveAddress' => false,
            // 'timestampsOnUnixListingsEnabled' => true,
        ],
        'memory' => [
            'driver' => \Qifen\Filesystem\Adapter\MemoryAdapterFactory::class,
        ],
        's3' => [
            'driver' => \Qifen\Filesystem\Adapter\S3AdapterFactory::class,
            'credentials' => [
                'key' => 'S3_KEY',
                'secret' => 'S3_SECRET',
            ],
            'region' => 'S3_REGION',
            'version' => 'latest',
            'bucket_endpoint' => false,
            'use_path_style_endpoint' => false,
            'endpoint' => 'S3_ENDPOINT',
            'bucket_name' => 'S3_BUCKET',
        ],
        'minio' => [
            'driver' => \Qifen\Filesystem\Adapter\S3AdapterFactory::class,
            'credentials' => [
                'key' => 'S3_KEY',
                'secret' => 'S3_SECRET',
            ],
            'region' => 'S3_REGION',
            'version' => 'latest',
            'bucket_endpoint' => false,
            'use_path_style_endpoint' => true,
            'endpoint' => 'S3_ENDPOINT',
            'bucket_name' => 'S3_BUCKET',
        ],
        'oss' => [
            'driver' => \Qifen\Filesystem\Adapter\AliyunOssAdapterFactory::class,
            'accessId' => 'OSS_ACCESS_ID',
            'accessSecret' => 'OSS_ACCESS_SECRET',
            'bucket' => 'OSS_BUCKET',
            'endpoint' => 'OSS_ENDPOINT',
            // 'timeout' => 3600,
            // 'connectTimeout' => 10,
            // 'isCName' => false,
            // 'token' => null,
            // 'proxy' => null,
        ],
        'qiniu' => [
            'driver' => \Qifen\Filesystem\Adapter\QiniuAdapterFactory::class,
            'accessKey' => 'QINIU_ACCESS_KEY',
            'secretKey' => 'QINIU_SECRET_KEY',
            'bucket' => 'QINIU_BUCKET',
            'domain' => 'QINBIU_DOMAIN',
        ],
        'cos' => [
            'driver' => \Qifen\Filesystem\Adapter\CosAdapterFactory::class,
            'region' => 'COS_REGION',
            'app_id' => 'COS_APPID',
            'secret_id' => 'COS_SECRET_ID',
            'secret_key' => 'COS_SECRET_KEY',
            // 可选，如果 bucket 为私有访问请打开此项
            // 'signed_url' => false,
            'bucket' => 'COS_BUCKET',
            'read_from_cdn' => false,
            // 'timeout' => 60,
            // 'connect_timeout' => 60,
            // 'cdn' => '',
            // 'scheme' => 'https',
        ],
    ],
];
# 安装

```
composer require qifen/filesystem
```

请修改 config/plugin/qifen/filesystem 下的配置文件

```php
return [
    'default' => 'local',
    'storage' => [
        'local' => [
            'driver' => \Qifen\Filesystem\Adapter\LocalAdapterFactory::class,
            'root' => runtime_path(),
        ],
        'ftp' => [
            'driver' => \Qifen\Filesystem\Adapter\FtpAdapterFactory::class,
            'host' => 'ftp.example.com',
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
```

- 阿里云 OSS 适配器

```
composer require qifen/flysystem-oss
```

- S3 适配器

```
composer require "league/flysystem-aws-s3-v3:^2.0"
```

- 七牛云适配器

```
composer require "overtrue/flysystem-qiniu:^2.0"
```

- 内存适配器

```
composer require "league/flysystem-memory:^2.0"
```

- 腾讯云 COS 适配器

```
composer require "overtrue/flysystem-cos:^4.0"
```

# 使用

通过 FilesystemFactory::get('local') 来调用不同的适配器

```php
    use Qifen\Filesystem\FilesystemFactory;
    public function upload(Request $request)
    {
        $file = $request->file('file');

        $filesystem =  FilesystemFactory::get('local');
        $stream = fopen($file->getRealPath(), 'r+');
        $filesystem->writeStream(
            'uploads/'.$file-getUploadName(),
            $stream
        );
        if (is_resource($stream)) {
            @fclose($stream);
        }

        // Write Files
        $filesystem->write('path/to/file.txt', 'contents');

        // Add local file
        $stream = fopen('local/path/to/file.txt', 'r+');
        $result = $filesystem->writeStream('path/to/file.txt', $stream);
        if (is_resource($stream)) {
            fclose($stream);
        }

        // Update Files
        $filesystem->update('path/to/file.txt', 'new contents');

        // Check if a file exists
        $exists = $filesystem->has('path/to/file.txt');

        // Read Files
        $contents = $filesystem->read('path/to/file.txt');

        // Delete Files
        $filesystem->delete('path/to/file.txt');

        // Rename Files
        $filesystem->rename('filename.txt', 'newname.txt');

        // Copy Files
        $filesystem->copy('filename.txt', 'duplicate.txt');

        // list the contents
        $filesystem->listContents('path', false);
    }
```

# 便捷式上传

```php
    use Qifen\Filesystem\Facade\Storage;
    public function upload(Request $request){
         // 适配器 local默认是存储在runtime目录下 public默认是存储在public目录下
         // 可访问的静态文件建议public
         // 默认适配器是local
         Storage::adapter('public');
        //单文件上传
        $file = $request->file('file');
        $result = Storage::upload($file);
        //单文件判断
        try {
            $result = Storage::adapter('public')->path('storage/upload/user')->size(1024*1024*5)->extYes(['image/jpeg','image/gif'])->extNo(['image/png'])->upload($file);
         }catch (\Exception $e){
            $e->getMessage();
         }

         //多文件上传
         $files = $request->file();
         $result = Storage::uploads($files);
         try {
         //uploads 第二个参数为限制文件数量 比如设置为10 则只允许上传10个文件 第三个参数为允许上传总大小 则本列表中文件总大小不得超过设定
            $result = Storage::adapter('public')->path('storage/upload/user')->size(1024*1024*5)->extYes(['image/jpeg','image/gif'])->extNo(['image/png'])->uploads($files,10,1024*1024*100);
         }catch (\Exception $e){
            $e->getMessage();
         }

          // 指定文件名上传(同文件将被覆盖)
        try {
            $files = $request->file();
            $fileName = 'storage/upload/user/1.png'; // 文件名中如此带了路径 则下面的path无效 未带路径1.png效果相等
            $ext = true; // 文件尾缀是否替换 开启后则$files上传的任意图片 都会转换为$fileName尾缀（示例: .png），默认false
            $result = Storage::adapter('public')->path('storage/upload/user')->size(1024*1024*5)->extYes(['image/jpeg','image/gif'])->extNo(['image/png'])->reUpload($file,$fileName,$ext);
         }catch (\Exception $e){
            $e->getMessage();
         }
         
        // base64图片上传
        try {
            $files = "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAcIAAAHCCAYAAAB8GMlFAAAAAXNSR0IArs4c6QAAAARnQU1BAACx...";
            $result = Storage::adapter('public')->path('storage/upload/user')->size(1024*1024*5)->extYes(['image/jpeg','image/gif'])->extNo(['image/png'])->base64Upload($files);
         }catch (\Exception $e){
            $e->getMessage();
         }

         //获取文件外网
         $filesName = 'storage/a4bab140776e0c1d57cc316266e1ca05.png';
         $fileUrl = Storage::url($filesName);
         //指定选定器外网
         $fileUrl = Storage::adapter('oss')->url($filesName);
    }

```

###静态方法（可单独设定）

| 方法    | 描述                     | 默认                     |
| ------- | ------------------------ | ------------------------ |
| adapter | 选定器                   | config 中配置的 default  |
| size    | 单文件大小               | config 中配置的 max_size |
| extYes  | 允许上传文件类型         | config 中配置的 ext_yes  |
| extNo   | 不允许上传文件类型       | config 中配置的 ext_no   |
| path    | 文件存放路径(非完整路径) | storage                  |

### 响应字段

| 字段        | 描述                       | 示例值                                                        |
| ----------- | -------------------------- | ------------------------------------------------------------- |
| origin_name | 源文件名称                 | webman.png                                                    |
| file_name   | 文件路径及名称             | storage/a4bab140776e0c1d57cc316266e1ca05.png                  |
| storage_key | 文件随机 key               | a4bab140776e0c1d57cc316266e1ca05                              |
| file_url    | 文件访问外网               | //127.0.0.1:8787/storage/cab473e23b638c2ad2ad58115e28251c.png |
| size        | 文件大小                   | 22175                                                         |
| mime_type   | 文件类型                   | image/jpeg                                                    |
| extension   | 文件尾缀                   | jpg                                                           |
| width       | 图片宽度（图片类型才返回） | 206                                                           |
| height      | 图片高度（图片类型才返回） | 206                                                           |

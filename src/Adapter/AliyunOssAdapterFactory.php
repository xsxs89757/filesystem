<?php
declare(strict_types=1);
namespace Qifen\Filesystem\Adapter;

use Qifen\Filesystem\Contract\AdapterFactoryInterface;
use Qifen\FilesystemOss\OssAdapter;

class AliyunOssAdapterFactory implements AdapterFactoryInterface
{
    public function make(array $options)
    {
        return new OssAdapter($options);
    }
}
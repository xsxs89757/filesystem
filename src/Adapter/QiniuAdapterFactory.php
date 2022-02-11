<?php
declare(strict_types=1);


namespace Qifen\Filesystem\Adapter;

use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use Qifen\Filesystem\Contract\AdapterFactoryInterface;

class QiniuAdapterFactory implements AdapterFactoryInterface
{
    public function make(array $options)
    {
        return new QiniuAdapter($options['accessKey'], $options['secretKey'], $options['bucket'], $options['domain']);
    }
}
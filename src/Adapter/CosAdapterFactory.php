<?php
declare(strict_types=1);


namespace Qifen\Filesystem\Adapter;


use Qifen\Filesystem\Contract\AdapterFactoryInterface;
use Overtrue\Flysystem\Cos\CosAdapter;

class CosAdapterFactory implements AdapterFactoryInterface
{
    public function make(array $options)
    {
        return new CosAdapter($options);
    }
}
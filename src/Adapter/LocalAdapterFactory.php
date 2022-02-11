<?php
declare(strict_types=1);


namespace Qifen\Filesystem\Adapter;


use Qifen\Filesystem\Contract\AdapterFactoryInterface;
use League\Flysystem\Local\LocalFilesystemAdapter;

class LocalAdapterFactory implements AdapterFactoryInterface
{
    public function make(array $options)
    {
        return new LocalFilesystemAdapter($options['root']);
    }
}
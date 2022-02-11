<?php
declare(strict_types=1);

namespace Qifen\Filesystem\Adapter;

use League\Flysystem\InMemory\InMemoryFilesystemAdapter;
use Qifen\Filesystem\Contract\AdapterFactoryInterface;

class MemoryAdapterFactory implements AdapterFactoryInterface
{
    public function make(array $options)
    {
        return new InMemoryFilesystemAdapter();
    }
}
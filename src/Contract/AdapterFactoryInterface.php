<?php

namespace Qifen\Filesystem\Contract;

use League\Flysystem\AdapterInterface;
use League\Flysystem\FilesystemAdapter;
interface AdapterFactoryInterface
{
    /**
     * @param array $options
     * @return mixed
     */
    public function make(array $options);
}
<?php
declare(strict_types=1);
/**
 *-------------------------------------------------------------------------p*
 *
 *-------------------------------------------------------------------------h*
 * @copyright  Copyright (c) 2015-2021 Phcent Inc. (http://www.phcent.com)
 *-------------------------------------------------------------------------c*
 * @license    http://www.phcent.com        p h c e n t . c o m
 *-------------------------------------------------------------------------e*
 * @link       http://www.phcent.com
 *-------------------------------------------------------------------------n*
 * @since      8988354@qq.com
 *-------------------------------------------------------------------------t*
 */


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
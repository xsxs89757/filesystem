<?php
declare(strict_types=1);


namespace Qifen\Filesystem\Adapter;

use League\Flysystem\Adapter\Ftp;
use League\Flysystem\Ftp\ConnectivityCheckerThatCanFail;
use League\Flysystem\Ftp\FtpAdapter;
use League\Flysystem\Ftp\FtpConnectionOptions;
use League\Flysystem\Ftp\NoopCommandConnectivityChecker;
use Qifen\Filesystem\Contract\AdapterFactoryInterface;

class FtpAdapterFactory implements AdapterFactoryInterface
{
    public function make(array $options)
    {
            $options = FtpConnectionOptions::fromArray($options);
            $connectivityChecker = new ConnectivityCheckerThatCanFail(new NoopCommandConnectivityChecker());
            return new FtpAdapter($options, null, $connectivityChecker);
    }
}
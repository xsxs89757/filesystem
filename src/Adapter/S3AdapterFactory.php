<?php
declare(strict_types=1);


namespace Qifen\Filesystem\Adapter;

use Aws\Handler\GuzzleV6\GuzzleHandler;
use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Qifen\Filesystem\Contract\AdapterFactoryInterface;

class S3AdapterFactory implements AdapterFactoryInterface
{
    public function make(array $options)
    {
        $handler = new GuzzleHandler();
        $options = array_merge($options, ['http_handler' => $handler]);
        $client = new S3Client($options);
        return new AwsS3V3Adapter($client, $options['bucket_name'], '');
    }
}
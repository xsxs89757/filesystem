<?php
namespace Qifen\Filesystem;

use Qifen\Filesystem\Adapter\LocalAdapterFactory;
use Qifen\Filesystem\Contract\AdapterFactoryInterface;
use League\Flysystem\Filesystem;
use Psr\Container\ContainerInterface;
use support\Container;

class FilesystemFactory {
    /**
    * @var ContainerInterface
    */
    protected static $_instance = null;

    /**
    * @return ContainerInterface
    */
    public static function instance() {
        return static::$_instance;
    }
    public static function get( $adapterName, array $adapterOptions = [] ): Filesystem {
        $options = \config( 'plugin.qifen.filesystem.app', [
            'default' => 'local',
            'storage' => [
                'local' => [
                    'driver' => LocalAdapterFactory::class,
                    'root' => \public_path(),
                ],
            ],
        ] );
        $adapter = static::getAdapter( $options, $adapterName );

        return new Filesystem( $adapter, empty( $adapterOptions ) ? ( $options[ 'storage' ][ $adapterName ] ?? [] ):$adapterOptions );
    }

    public static function getAdapter( $options, $adapterName ) {
        if ( ! $options[ 'storage' ] || ! $options[ 'storage' ][ $adapterName ] ) {
            throw new \Exception( "file configurations are missing {$adapterName} options" );
        }
        /** @var AdapterFactoryInterface $driver */
        $driver = Container::get( $options[ 'storage' ][ $adapterName ][ 'driver' ] );
        return $driver->make( $options[ 'storage' ][ $adapterName ] );
    }
    /**
    * @param $name
    * @param $arguments
    * @return mixed
    */
    public static function __callStatic( $name, $arguments ) {
        return static::instance()-> {
            $name}
            ( ... $arguments );
        }
    }
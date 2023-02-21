<?php

namespace Qifen\Filesystem;

use Closure;

class Storage {
    protected $adapterType = '';
    protected $adapterOptions = [];
    protected $path = 'storage';
    protected $fileName = '';
    protected $size = 1024 * 1024 * 10;
    protected $extYes = [];
    //允许上传文件类型
    protected $extNo = [];
    // 不允许上传文件类型
    protected $config = [];

    /**
    * @var Closure[]
    */
    protected static $maker = [];

    /**
    * 构造方法
    * @access public
    */

    public function __construct() {
        $this->config = config( 'plugin.qifen.filesystem.app' );
        $this->adapterType = $this->config[ 'default' ] ?? 'local';
        $this->size = $this->config[ 'size' ] ?? 1024 * 1024 * 10;
        $this->extYes = $this->config[ 'ext_yes' ] ?? [];
        $this->extNo = $this->config[ 'ext_no' ] ?? [];
        $this->hashAppendRand = $this->config[ 'hash_append_rand' ] ?? false;
        if ( !empty( static::$maker ) ) {
            foreach ( static::$maker as $maker ) {
                \call_user_func( $maker, $this );
            }
        }
    }

    /**
    * 设置服务注入
    * @access public
    * @param Closure $maker
    * @return void
    */
    public static function maker( Closure $maker ) {
        static::$maker[] = $maker;
    }

    /**
    * 存储路径
    * @param string $name
    * @return $this
    */

    public function adapter( string $name, array $adapterOptions = [] ) {
        $this->adapterType = $name;
        $this->adapterOptions = $adapterOptions;
        return $this;
    }

    /**
    * 存储路径
    * @param string $name
    * @return $this
    */

    public function path( string $name ) :Storage {
        $this->path = $name;
        return $this;
    }

    /**
    * 存储地址 全路径的情况
    * @param string $name
    */

    public function filePath( string $name ) : Storage {
        $this->fileName = $name;
        return $this;
    }

    /**
    * 允许上传文件类型
    * @param array $ext
    * @return $this
    */

    public function extYes( array $ext ) {
        $this->extYes = $ext;
        return $this;
    }

    /**
    * 不允许上传文件类型
    * @param array $ext
    * @return $this
    */

    public function extNo( array $ext ) {
        $this->extNo = $ext;
        return $this;
    }

    /**
    * 设置允许文件大小
    * @param int $size
    * @return $this
    */

    public function size( int $size ) {
        $this->size = $size;
        return $this;
    }

    /**
    * 上传文件
    * @param $file
    * @return void
    * @throws \Exception
    */

    public function upload( $file ) {
        if ( !empty( $this->extYes ) && !in_array( $file->getUploadMineType(), $this->extYes ) ) {
            throw new \Exception( '不允许上传文件类型'.$file->getUploadMineType() );
        }
        if ( !empty( $this->extNo ) && in_array( $file->getUploadMineType(), $this->extNo ) ) {
            throw new \Exception( '文件类型不被允许'.$file->getUploadMineType() );
        }
        if ( $file->getSize() > $this->size ) {
            throw new \Exception( "上传文件过大（当前大小 {$file->getSize()}，需小于 {$this->size})" );
        }
        $filesystem = FilesystemFactory::get( $this->adapterType, $this->adapterOptions );
        $hashFileName = \hash_file( 'md5', $file->getPathname() );
        $storageKey = $this->hashAppendRand ? $hashFileName. rand( 10000, 99999 ) : $hashFileName;
        $fileName = $this->fileName ? $this->fileName : $this->path.'/'.$storageKey.'.'.$file->getUploadExtension();

        $stream = \fopen( $file->getRealPath(), 'r+' );
        $filesystem->writeStream(
            $fileName,
            $stream
        );
        \fclose( $stream );
        $info = [
            'origin_name' => $file->getUploadName(),
            'file_name' => $fileName,
            'storage_key' => $storageKey,
            'file_url' => $this->url( $fileName ),
            'size' => $file->getSize(),
            'mime_type' => $file->getUploadMineType(),
            'extension' => $file->getUploadExtension(),
        ];
        if ( \substr( $file->getUploadMineType(), 0, 5 ) == 'image' ) {
            $size = \getimagesize( $file );
            $info[ 'file_height' ] = $size[ 1 ];
            $info[ 'file_width' ] = $size[ 0 ];
        }
        return \json_decode( \json_encode( $info ) );
    }

    /**
     * 原文件覆盖
     * @param $file
     * @param $storageKey
     * @return mixed
     * @throws \Exception
     */
    public function reUpload($file,$fileName,$ext = false)
    {
        if(!empty($this->extYes) && !in_array($file->getUploadMineType(),$this->extYes)) {
            throw new \Exception('不允许上传文件类型'.$file->getUploadMineType());
        }
        if(!empty($this->extNo) &&in_array($file->getUploadMineType(),$this->extNo)) {
            throw new \Exception('文件类型不被允许'.$file->getUploadMineType());
        }
        if($file->getSize() > $this->size){
            throw new \Exception("上传文件过大（当前大小 {$file->getSize()}，需小于 {$this->size})");
        }
        $filesystem = FilesystemFactory::get($this->adapterType);
        $first = strrpos($fileName,'/');
        if($first === false){
            $path = $this->path;
            $keyAndExt = explode('.',substr($fileName,0,strlen($fileName)));
        }else{
            $path = substr($fileName,0,$first);
            $keyAndExt = explode('.',substr($fileName,$first + 1,strlen($fileName)));
        }
        $storageKey = $keyAndExt[0] ?? \hash_file('md5', $file->getPathname());
        $fileName = $path.'/'.$storageKey.'.'.($ext?$keyAndExt[1]:$file->getUploadExtension());
        if($filesystem->fileExists(trim($fileName, '/'))){
            $filesystem->delete($fileName);
        }
        $result = $this->putFileAs($this->path, $file, $storageKey.'.'.($ext?$keyAndExt[1]:$file->getUploadExtension()));
        $info = [
            'origin_name' => $file->getUploadName(),
            'file_name' => $fileName,
            'storage_key' => $storageKey,
            'file_url' => $this->url($fileName),
            'size' => $file->getSize(),
            'mime_type' => $file->getUploadMineType(),
            'extension' => $file->getUploadExtension(),
        ];
        if (\substr($file->getUploadMineType(), 0, 5) == 'image') {
            $size = \getimagesize($file);
            $info['file_height'] = $size[1];
            $info['file_width'] = $size[0];
        }
        return \json_decode(\json_encode($info));
    }

    /**
     *
     * @param $file
     */
    public function base64Upload($baseImg)
    {

        preg_match('/^(data:\s*image\/(\w+);base64,)/',$baseImg,$res);
        if(count($res) != 3){
           throw new \Exception('格式错误');
        }
        $img = base64_decode(str_replace($res[1],'', $baseImg));
        $size = getimagesizefromstring($img);
        if(count($size) == 0){
            throw new \Exception('图片格式不正确');
        }
        if(!empty($this->extYes) && !in_array($size['mime'],$this->extYes)) {
            throw new \Exception('不允许上传文件类型'.$size['mime']);
        }
        if(!empty($this->extNo) &&in_array($size['mime'],$this->extNo)) {
            throw new \Exception('文件类型不被允许'.$size['mime']);
        }

        $filesystem = FilesystemFactory::get($this->adapterType);
        $storageKey = md5(uniqid());
        $fileName = $this->path.'/'.$storageKey.'.'.$res[2];
        $base_img = str_replace($res[1], '', $baseImg);
        $base_img = str_replace('=','',$baseImg);
        $img_len = strlen($base_img);
        $file_size = intval($img_len - ($img_len/8)*2);

        if($file_size > $this->size){
            throw new \Exception("上传文件过大（当前大小 {$file_size}，需小于 {$this->size})");
        }

        $this->put(
            $path = trim($fileName, '/'), $img
        );

        $info = [
            'origin_name' => $fileName,
            'file_name' => $fileName,
            'storage_key' => $storageKey,
            'file_url' => $this->url($fileName),
            'size' => $file_size,
            'mime_type' => $size['mime'],
            'extension' => $res[2],
            'file_height' => $size[1],
            'file_width' => $size[0]
        ];

        return \json_decode(\json_encode($info));
    }

    /**
    * 批量上传文件
    * @param $files
    * @param int $num
    * @param int $size
    * @return void
    * @throws \Exception
    */

    public function uploads( $files, $num = 0, $size = 0 ) {
        $result = [];
        if ( $num > 0 && count( $files ) > $num ) {
            throw new \Exception( '文件数量超过了'.$num );
        }
        if ( $size > 0 ) {
            $allSize = 0;
            foreach ( $files as $key => $file ) {
                $allSize += $file->getSize();
            }
            if ( $allSize > $size ) {
                throw new \Exception( '文件总大小超过了'.$size );
            }
        }
        foreach ( $files as $key => $file ) {
            if ( !empty( $this->extYes ) && !in_array( $file->getUploadMineType(), $this->extYes ) ) {
                throw new \Exception( '不允许上传文件类型'.$file->getUploadMineType() );
            }
            if ( !empty( $this->extNo ) && in_array( $file->getUploadMineType(), $this->extNo ) ) {
                throw new \Exception( '文件类型不被允许'.$file->getUploadMineType() );
            }
            if ( $file->getSize() > $this->size ) {
                throw new \Exception( "上传文件过大（当前大小 {$file->getSize()}，需小于 {$this->size})" );
            }
            $filesystem = FilesystemFactory::get( $this->adapterType, $this->adapterOptions );
            $hashFileName = \hash_file( 'md5', $file->getPathname() );
            $storageKey = $this->hashAppendRand ? $hashFileName. rand( 10000, 99999 ) : $hashFileName;
            $fileName = $this->path.'/'.$storageKey.'.'.$file->getUploadExtension();

            $stream = \fopen( $file->getRealPath(), 'r+' );
            $filesystem->writeStream(
                $fileName,
                $stream
            );
            \fclose( $stream );
            $info = [
                'key' => $key,
                'origin_name' => $file->getUploadName(),
                'file_name' => $fileName,
                'storage_key' => $storageKey,
                'file_url' => $this->url( $fileName ),
                'size' => $file->getSize(),
                'mime_type' => $file->getUploadMineType(),
                'extension' => $file->getUploadExtension(),
            ];
            if ( \substr( $file->getUploadMineType(), 0, 5 ) == 'image' ) {
                $size = \getimagesize( $file );
                $info[ 'file_height' ] = $size[ 1 ];
                $info[ 'file_width' ] = $size[ 0 ];
            }
            \array_push( $result, $info );
        }
        return \json_decode( \json_encode( $result ) );
    }

    /**
    * 获取url
    * @param string $fileName
    * @return void
    */

    public function url( string $fileName ) {
        $domain = '';
        if ( isset( $this->adapterOptions[ 'url' ] ) ) {
            $domain = $this->adapterOptions[ 'url' ];
        } else {
            $domain = isset( $this->config[ 'storage' ][ $this->adapterType ][ 'url' ] ) ? $this->config[ 'storage' ][ $this->adapterType ][ 'url' ] : '//'.\request()->host();
        }

        return $domain.'/'.$fileName;
    }

    /**
    * 动态方法 直接调用is方法进行验证
    * @access public
    * @param string $method 方法名
    * @param array $args   调用参数
    * @return bool
    */

    public function __call( string $method, array $args ) {
        if ( 'is' == \strtolower( substr( $method, 0, 2 ) ) ) {
            $method = \substr( $method, 2 );
        }

        $args[] = \lcfirst( $method );

        return \call_user_func_array( [ $this, 'is' ], $args );
    }
}
<?php

namespace Qifen\Filesystem;

use Closure;
use League\Flysystem\UnableToSetVisibility;
use League\Flysystem\UnableToWriteFile;
use Psr\Http\Message\StreamInterface;
use support\Request;
use Webman\File;
use Webman\Http\UploadFile;

class Storage
{
    protected $adapterType = '';
    protected $adapterOptions = [];
    protected $path = 'storage';
    protected $size = 1024 * 1024 * 10;
    protected $extYes = []; //允许上传文件类型
    protected $extNo = []; // 不允许上传文件类型
    protected $config = [];

    /**
     * @var Closure[]
     */
    protected static $maker = [];

    /**
     * 构造方法
     * @access public
     */
    public function __construct()
    {
        $this->config = config('plugin.qifen.filesystem.app');
        $this->adapterType = $this->config['default'] ?? 'local';
        $this->size = $this->config['max_size'] ?? 1024 * 1024 * 10;
        $this->extYes = $this->config['ext_yes'] ?? [];
        $this->extNo = $this->config['ext_no'] ?? [];
        if (!empty(static::$maker)) {
            foreach (static::$maker as $maker) {
                \call_user_func($maker, $this);
            }
        }
    }

    /**
     * 设置服务注入
     * @access public
     * @param Closure $maker
     * @return void
     */
    public static function maker(Closure $maker)
    {
        static::$maker[] = $maker;
    }

    /**
     * 存储路径
     * @param string $name
     * @return $this
     */
    public function adapter(string $name, array $adapterOptions = [])
    {
        $this->adapterType = $name;
        $this->adapterOptions = $adapterOptions;
        return $this;
    }

    /**
     * 存储路径
     * @param string $name
     * @return $this
     */
    public function path(string $name) :Storage
    {
        $this->path = $name;
        return $this;
    }

    /**
     * 允许上传文件类型
     * @param array $ext
     * @return $this
     */
    public function extYes(array $ext)
    {
        $this->extYes = $ext;
        return $this;
    }

    /**
     * 不允许上传文件类型
     * @param array $ext
     * @return $this
     */
    public function extNo(array $ext)
    {
        $this->extNo = $ext;
        return $this;
    }

    /**
     * 设置允许文件大小
     * @param int $size
     * @return $this
     */
    public function size(int $size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * 上传文件
     * @param $file
     * @return void
     * @throws \Exception
     */
    public function upload($file,$same = true)
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
        $filesystem = FilesystemFactory::get($this->adapterType, $this->adapterOptions);
        $storageKey = $this->hash($file->getPathname());
        if($same){
            $storageKey = $this->hash($file->getPathname()).'_'.uniqid();
        }else{
            if($filesystem->fileExists(trim($this->path.'/'.$storageKey.'.'.$file->getUploadExtension(), '/'))){
                $filesystem->delete(trim($this->path.'/'.$storageKey.'.'.$file->getUploadExtension(), '/'));
            }
        }
        $result = $this->putFileAs($this->path, $file, $storageKey.'.'.$file->getUploadExtension());


        if($result){
            $info = [
                'adapter' => $this->adapterType,
                'origin_name' => $file->getUploadName(),
                'file_name' => $result,
                'storage_key' => $storageKey,
                'file_url' => $this->url($result),
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

    }

    /**
     * 原文件覆盖
     * @param $file
     * @param $storageKey
     * @return mixed
     * @throws \League\Flysystem\FilesystemException
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
        $filesystem = FilesystemFactory::get($this->adapterType, $this->adapterOptions);
        $first = strrpos($fileName,'/');
        if($first === false){
            $path = $this->path;
            $keyAndExt = explode('.',substr($fileName,0,strlen($fileName)));
        }else{
            $path = substr($fileName,0,$first);
            $keyAndExt = explode('.',substr($fileName,$first + 1,strlen($fileName)));
            $this->path = $path;
        }
        
        $storageKey = $keyAndExt[0] ?? \hash_file('md5', $file->getPathname());
        $fileName = $path.'/'.$storageKey.'.'.($ext?$keyAndExt[1]:$file->getUploadExtension());
        try{
            if($filesystem->fileExists(trim($fileName, '/'))){
                $filesystem->delete($fileName);
            }
        }catch(\Throwable $e){

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
     * 批量上传文件
     * @param $files
     * @param int $num
     * @param int $size
     * @return void
     * @throws \Exception
     */
    public function uploads($files,$num = 0, $size = 0,$same = true)
    {
        $result = [];
        if($num > 0 && count($files) > $num){
            throw new \Exception('文件数量超过了'.$num);
        }
        if($size > 0){
            $allSize = 0;
            foreach ($files as $key => $file) {
                $allSize += $file->getSize();
            }
            if($allSize > $size){
                throw new \Exception('文件总大小超过了'.$size);
            }
        }
        foreach ($files as $key => $file) {
            $info = $this->upload($file,$same);
            \array_push($result, $info);
        }
        return \json_decode(\json_encode($result));
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

        $filesystem = FilesystemFactory::get($this->adapterType, $this->adapterOptions);
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
     * 获取url
     * @param string $fileName
     * @return void
     */
    public function url(string $fileName)
    {
        $domain = '';
        if ( isset( $this->adapterOptions[ 'url' ] ) ) {
            $domain = $this->adapterOptions[ 'url' ];
        } else {
            $domain = isset( $this->config[ 'storage' ][ $this->adapterType ][ 'url' ] ) ? $this->config[ 'storage' ][ $this->adapterType ][ 'url' ] : '//'.\request()->host();
        }

        return $domain.'/'.$fileName;
    }

    /**
     * Determine if two files are the same by comparing their hashes.
     *
     * @param  string  $firstFile
     * @param  string  $secondFile
     * @return bool
     */
    public function hasSameHash($firstFile, $secondFile)
    {
        $hash = @md5_file($firstFile);

        return $hash && $hash === @md5_file($secondFile);
    }
    /**
     * Get the hash of the file at the given path.
     *
     * @param  string  $path
     * @param  string  $algorithm
     * @return string
     */
    public function hash($path, $algorithm = 'md5')
    {
        return hash_file($algorithm, $path);
    }

    /**
     *
     * @param $path
     * @param $file
     * @param $options
     * @return false|string
     */
    public function putFile($path, $file, $options = [])
    {
        $file = is_string($file) ? new File($file) : $file;
        return $this->putFileAs($path, $file, $this->hash($file->getPathname()).'.'.$file->getUploadExtension(), $options);
    }

    /**
     * @param $path
     * @param $file
     * @param $name
     * @param $options
     * @return false|string
     * @throws \Throwable
     */
    public function putFileAs($path, $file, $name, $options = [])
    {
        $stream = fopen(is_string($file) ? $file : $file->getRealPath(), 'r');

        // Next, we will format the path of the file and store the file using a stream since
        // they provide better performance than alternatives. Once we write the file this
        // stream will get closed automatically by us so the developer doesn't have to.
        $result = $this->put(
            $path = trim($path.'/'.$name, '/'), $stream, $options
        );

        if (is_resource($stream)) {
            fclose($stream);
        }

        return $result ? $path : false;
    }

    /**
     *
     * @param $path
     * @param $contents
     * @param $options
     * @return bool
     * @throws \Throwable
     */
    public function put($path, $contents, $options = [])
    {
        $options = is_string($options)
            ? ['visibility' => $options]
            : (array) $options;
        // If the given contents is actually a file or uploaded file instance than we will
        // automatically store the file using a stream. This provides a convenient path
        // for the developer to store streams without managing them manually in code.
        if ($contents instanceof \Symfony\Component\HttpFoundation\File\File ||
            $contents instanceof UploadFile) {
            return $this->putFile($path, $contents, $options);
        }

        try {
            if ($contents instanceof StreamInterface) {
                FilesystemFactory::get($this->adapterType, $this->adapterOptions)->writeStream($path, $contents->detach(), $options);
                return true;
            }
            is_resource($contents)
                ? FilesystemFactory::get($this->adapterType, $this->adapterOptions)->writeStream($path, $contents, $options)
                : FilesystemFactory::get($this->adapterType, $this->adapterOptions)->write($path, $contents, $options);
        } catch (UnableToWriteFile | UnableToSetVisibility $e) {
            throw_if($this->throwsExceptions(), $e);

            return false;
        }

        return true;
    }
}
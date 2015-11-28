# Laravel5 七牛存储组件（使用官方SDK）

这个包依赖七牛官方 PHP-SDK ，使其符合 Laravel 中操作文件的规范，因此可高度信赖。


## 注意
由于七牛并不支持所谓的目录，不存在树形结构，因为目录操作基本可以无视。

建议只是用来上传、更新资源就好了，不要做列表展示！

## 安装

 - ```composer require itbdw/laravel-storage-qiniu```
 - ```config/app.php``` 里面的 ```providers``` 数组， 加上一行 ```itbdw\QiniuStorage\QiniuFilesystemServiceProvider```
 - ```config/filesystem.php``` 里面的 ```disks```数组加上：
 
```php

    'disks' => [

        'qiniu' => [
            'driver' => 'qiniu',
            'domain' => 'xxxxx.com1.z0.glb.clouddn.com',   //你的七牛域名
            'access_key'    => '',                          //AccessKey
            'secret_key' => '',                             //SecretKey
            'bucket' => '',                                 //Bucket名字
        ],
    ],
    
```

 
## 使用

第一种用法

```php

    $disk = \Storage::disk('qiniu');
    $disk->exists('file.jpg');                      //文件是否存在
    $disk->get('file.jpg');                         //获取文件内容
    $disk->put('file.jpg',$contents);               //上传文件，$contents 二进制文件流
    $disk->prepend('file.log', 'Prepended Text');   //附加内容到文件开头
    $disk->append('file.log', 'Appended Text');     //附加内容到文件结尾
    $disk->delete('file.jpg');                      //删除文件
    $disk->delete(['file1.jpg', 'file2.jpg']);
    $disk->copy('old/file1.jpg', 'new/file1.jpg');  //复制文件到新的路径
    $disk->move('old/file1.jpg', 'new/file1.jpg');  //移动文件到新的路径
    $size = $disk->size('file1.jpg');               //取得文件大小
    $time = $disk->lastModified('file1.jpg');       //取得最近修改时间 (UNIX)
    $files = $disk->files($directory);              //取得目录下所有文件
    $files = $disk->allFiles($directory);               //取得目录下所有文件，包括子目录

    这三个对七牛来说无意义
    $directories = $disk->directories($directory);      //这个也没实现。。。
    $directories = $disk->allDirectories($directory);   //这个也没实现。。。
    $disk->makeDirectory($directory);               //这个其实没有任何作用

    $disk->deleteDirectory($directory);             //删除目录，包括目录下所有子文件子目录
    
    $disk->getDriver()->uploadToken('file.jpg');            //获取上传Token
    $disk->getDriver()->putFile('file.jpg', 'local/filepath');            //上传本地大文件
    $disk->getDriver()->downloadUrl('file.jpg');            //获取下载地址
    $disk->getDriver()->privateDownloadUrl('file.jpg');     //获取私有bucket下载地址
    $disk->getDriver()->imageInfo('file.jpg');              //获取图片信息
    $disk->getDriver()->imageExif('file.jpg');              //获取图片EXIF信息
    $disk->getDriver()->imagePreviewUrl('file.jpg','imageView2/0/w/100/h/200');              //获取图片预览URL
    $disk->getDriver()->persistentFop('file.flv','avthumb/m3u8/segtime/40/vcodec/libx264/s/320x240');   //执行持久化数据处理
    $disk->getDriver()->persistentStatus($persistent_fop_id);          //查看持久化数据处理的状态。
    $disk->getDriver()->fetch($url, $key);          //从指定URL抓取资源，并将该资源存储到指定空间中。

```

第二种用法 （就是省略了一个getDriver）

```php

    use itbdw\QiniuStorage\QiniuStorage;

    $disk = QiniuStorage::disk('qiniu');
    $disk->exists('file.jpg');                      //文件是否存在
    $disk->get('file.jpg');                         //获取文件内容
    $disk->put('file.jpg',$contents);               //上传文件，$contents 二进制文件流
    $disk->prepend('file.log', 'Prepended Text');   //附加内容到文件开头
    $disk->append('file.log', 'Appended Text');     //附加内容到文件结尾
    $disk->delete('file.jpg');                      //删除文件
    $disk->delete(['file1.jpg', 'file2.jpg']);
    $disk->copy('old/file1.jpg', 'new/file1.jpg');  //复制文件到新的路径
    $disk->move('old/file1.jpg', 'new/file1.jpg');  //移动文件到新的路径
    $size = $disk->size('file1.jpg');               //取得文件大小
    $time = $disk->lastModified('file1.jpg');       //取得最近修改时间 (UNIX)
    $files = $disk->files($directory);              //取得目录下所有文件
    $files = $disk->allFiles($directory);            //取得目录下所有文件，包括子目录


    这三个对七牛来说无意义
    $directories = $disk->directories($directory);      //这个也没实现。。。
    $directories = $disk->allDirectories($directory);   //这个也没实现。。。
    $disk->makeDirectory($directory);               //这个其实没有任何作用

    $disk->deleteDirectory($directory);             //删除目录，包括目录下所有子文件子目录
    
    $disk->uploadToken('file.jpg');            //获取上传Token
    $disk->putFile('file.jpg', 'local/filepath');            //上传本地大文件
    $disk->downloadUrl('file.jpg');            //获取下载地址
    $disk->privateDownloadUrl('file.jpg');     //获取私有bucket下载地址
    $disk->imageInfo('file.jpg');              //获取图片信息
    $disk->imageExif('file.jpg');              //获取图片EXIF信息
    $disk->imagePreviewUrl('file.jpg','imageView2/0/w/100/h/200');              //获取图片预览URL
    $disk->persistentFop('file.flv','avthumb/m3u8/segtime/40/vcodec/libx264/s/320x240');   //执行持久化数据处理
    $disk->persistentStatus($persistent_fop_id);          //查看持久化数据处理的状态。
    $disk->fetch($url, $key);          //从指定URL抓取资源，并将该资源存储到指定空间中。

```

## 官方SDK / 手册

 - https://github.com/qiniu/php-sdk
 - http://developer.qiniu.com/docs/v6/sdk/php-sdk.html

## 原作者
 - https://github.com/zgldh/qiniu-laravel-storage

```
 这个repo在原来的基础上，改了一些东西，使大家可以通过 composer 的方式正确的引入该组件和七牛组件。
```

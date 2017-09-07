# Laravel5 七牛存储组件（使用官方SDK）

对七牛官方组件再次封装，以简化在 Laravel 中的使用成本。

## 注意

 最初找到了原作者的项目，使用 composer 安装完，发现完全不对。所以变更了代码，以便可以通过 composer 的方式引入该组件和七牛组件，鉴于有同学也在用这个，而且原项目做的某些变更我不认同，因此保留该项目至今，基本不开发新功能，接受 pr。想看原作者项目的请去 https://github.com/zgldh/qiniu-laravel-storage


## 安装

 - ```composer require itbdw/laravel-storage-qiniu```
 - ```config/app.php``` 里面的 ```providers``` 数组， 加上一行 ```itbdw\QiniuStorage\QiniuFilesystemServiceProvider```
 - ```config/filesystem.php``` 里面的 ```disks```数组加上：
 
```php

    'disks' => [

        // 如果有多个 bucket，增加类似配置即可
        'qiniu' => [
            'driver' => 'qiniu',
            'domain' => 'https://www.example.com',          //你的七牛域名，支持 http 和 https，也可以不带协议，默认 http
            'access_key'    => '',                          //AccessKey
            'secret_key' => '',                             //SecretKey
            'bucket' => '',                                 //Bucket名字
        ],
        
        'qiniu_private' => [
            'driver' => 'qiniu',
            'domain' => 'https://www.example.com',          //你的七牛域名，支持 http 和 https，也可以不带协议，默认 http
            'access_key'    => '',                          //AccessKey
            'secret_key' => '',                             //SecretKey
            'bucket' => 'qiniu_private',                    //Bucket名字
        ],
        
        ...
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
    
    $disk->getDriver()->uploadToken();            //获取上传Token ,可选'file.jpg'
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
    
    $disk->uploadToken();            //获取上传Token ,可选参数'file.jpg'
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


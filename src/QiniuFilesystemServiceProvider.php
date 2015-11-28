<?php namespace itbdw\QiniuStorage;

use League\Flysystem\Filesystem;
use Illuminate\Support\ServiceProvider;
use itbdw\QiniuStorage\Plugins\DownloadUrl;
use itbdw\QiniuStorage\Plugins\ImageExif;
use itbdw\QiniuStorage\Plugins\ImageInfo;
use itbdw\QiniuStorage\Plugins\ImagePreviewUrl;
use itbdw\QiniuStorage\Plugins\PersistentFop;
use itbdw\QiniuStorage\Plugins\PersistentStatus;
use itbdw\QiniuStorage\Plugins\PrivateDownloadUrl;
use itbdw\QiniuStorage\Plugins\UploadToken;
use itbdw\QiniuStorage\Plugins\Fetch;
use itbdw\QiniuStorage\Plugins\PutFile;

/**
 * Class QiniuFilesystemServiceProvider
 * @package itbdw\QiniuStorage
 */
class QiniuFilesystemServiceProvider extends ServiceProvider
{

    public function boot()
    {
        \Storage::extend(
            'qiniu',
            function ($app, $config) {
                $qiniu_adapter = new QiniuAdapter(
                    $config['access_key'],
                    $config['secret_key'],
                    $config['bucket'],
                    $config['domain']
                );
                $file_system = new Filesystem($qiniu_adapter);
                $file_system->addPlugin(new PrivateDownloadUrl());
                $file_system->addPlugin(new DownloadUrl());
                $file_system->addPlugin(new ImageInfo());
                $file_system->addPlugin(new ImageExif());
                $file_system->addPlugin(new ImagePreviewUrl());
                $file_system->addPlugin(new PersistentFop());
                $file_system->addPlugin(new PersistentStatus());
                $file_system->addPlugin(new UploadToken());
                $file_system->addPlugin(new Fetch());
                $file_system->addPlugin(new PutFile());

                return $file_system;
            }
        );
    }

    public function register()
    {
        //
    }
}

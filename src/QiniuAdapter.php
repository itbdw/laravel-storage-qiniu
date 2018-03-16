<?php namespace itbdw\QiniuStorage;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Adapter\Polyfill\NotSupportingVisibilityTrait;
use League\Flysystem\Adapter\Polyfill\StreamedCopyTrait;
use League\Flysystem\Adapter\Polyfill\StreamedReadingTrait;
use League\Flysystem\Adapter\Polyfill\StreamedWritingTrait;
use League\Flysystem\Config;
use Qiniu\Auth;
use Qiniu\Http\Error;
use Qiniu\Processing\Operation;
use Qiniu\Processing\PersistentFop;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;

/**
 * Class QiniuAdapter
 * @package itbdw\QiniuStorage
 */
class QiniuAdapter extends AbstractAdapter
{

    use NotSupportingVisibilityTrait, StreamedWritingTrait, StreamedReadingTrait;

    private $access_key = null;
    private $secret_key = null;
    private $bucket = null;
    private $domain = null;

    private $auth = null;
    private $upload_manager = null;
    private $bucket_manager = null;
    private $operation = null;

    public function __construct($access_key, $secret_key, $bucket, $domain)
    {
        $this->access_key = $access_key;
        $this->secret_key = $secret_key;
        $this->bucket = $bucket;
        $this->domain = $domain;
        $this->setPathPrefix(strpos($this->domain, "http") === 0 ? $this->domain : "http://" . $this->domain);
    }

    private function getAuth()
    {
        if ($this->auth == null) {
            $this->auth = new Auth($this->access_key, $this->secret_key);
        }

        return $this->auth;
    }

    private function getUploadManager()
    {
        if ($this->upload_manager == null) {
            $this->upload_manager = new UploadManager();
        }

        return $this->upload_manager;
    }

    private function getBucketManager()
    {
        if ($this->bucket_manager == null) {
            $this->bucket_manager = new BucketManager($this->getAuth());
        }

        return $this->bucket_manager;
    }

    private function getOperation()
    {
        if ($this->operation == null) {
            $this->operation = new Operation($this->domain);
        }

        return $this->operation;
    }

    private function logQiniuError(Error $error)
    {
        //http://developer.qiniu.com/docs/v6/api/reference/codes.html
        $notLogCode = [612];

        if (!in_array($error->code(), $notLogCode)) {
            \Log::error('Qiniu: ' . $error->code() . ' ' .  $error->message());
        }
    }

    /**
     * Write a new file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     * @param bool $isPutFile
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config, $isPutFile = false)
    {
        $auth = $this->getAuth();
        $token = $auth->uploadToken($this->bucket, $path);
        $params = $config->get('params', null);
        $mime = $config->get('mime', 'application/octet-stream');
        $checkCrc = $config->get('checkCrc', false);

        $upload_manager = $this->getUploadManager();

        if ($isPutFile) {
        	list($ret, $error) = $upload_manager->putFile($token, $path, $contents, $params, $mime, $checkCrc);
        } else {
        	list($ret, $error) = $upload_manager->put($token, $path, $contents, $params, $mime, $checkCrc);
        }

        if ($error !== null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return $ret;
        }
    }

    /**
     * Update a file.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        $bucketMgr = $this->getBucketManager();

        list($ret, $error) = $bucketMgr->move($this->bucket, $path, $this->bucket, $newpath);
        if ($error !== null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return true;
        }
    }

    /**
     * Copy a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function copy($path, $newpath)
    {
        $bucketMgr = $this->getBucketManager();

        list($ret, $error) = $bucketMgr->copy($this->bucket, $path, $this->bucket, $newpath);
        if ($error !== null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return true;
        }
    }

    /**
     * Delete a file.
     *
     * @param string $path
     *
     * @return bool
     */
    public function delete($path)
    {
        $bucketMgr = $this->getBucketManager();

        $error = $bucketMgr->delete($this->bucket, $path);
        if ($error !== null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return true;
        }
    }

    /**
     * Fetch a file from url.
     *
     * @param string $url The specified url.
     * @param string $key The target filename.
     *
     * @return string|false
     */
    public function fetch($url, $key)
    {
        $bucketMgr = $this->getBucketManager();
        list($ret, $err) = $bucketMgr->fetch($url, $this->bucket, $key);
        if ($err !== null) {
            $this->logQiniuError($err);
            return false;
        } else {
            return $ret['key'];
        }
    }

    /**
     * Delete a directory.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $files = $this->listContents($dirname);
        foreach ($files as $file) {
            $this->delete($file['path']);
        }

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        return ['path' => $dirname];
    }

    /**
     * Check whether a file exists.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        $meta = $this->getMetadata($path);
        if ($meta) {
            return true;
        }

        return false;
    }

    /**
     * Read a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        $location = $this->applyPathPrefix($path);

        return array('contents' => file_get_contents($location));
    }

    /**
     * List contents of a directory.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $bucketMgr = $this->getBucketManager();

        list($items, $marker, $error) = $bucketMgr->listFiles($this->bucket, $directory);
        if ($error !== null) {
            $this->logQiniuError($error);

            return array();
        } else {
            $contents = array();
            foreach ($items as $item) {
                $normalized = [
                    'type' => 'file',
                    'path' => $item['key'],
                    'timestamp' => $item['putTime']
                ];

                if ($normalized['type'] === 'file') {
                    $normalized['size'] = $item['fsize'];
                }

                array_push($contents, $normalized);
            }

            return $contents;
        }
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        $bucketMgr = $this->getBucketManager();

        list($ret, $error) = $bucketMgr->stat($this->bucket, $path);
        if ($error !== null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return $ret;
        }
    }

    /**
     * Get all the meta data of a file or directory.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        $stat = $this->getMetadata($path);
        if ($stat) {
            return array('size' => $stat['fsize']);
        }

        return false;
    }

    /**
     * Get the mimetype of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        $stat = $this->getMetadata($path);
        if ($stat) {
            return array('mimetype' => $stat['mimeType']);
        }

        return false;
    }

    /**
     * Get the timestamp of a file.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $stat = $this->getMetadata($path);
        if ($stat) {
            return array('timestamp' => $stat['putTime']);
        }

        return false;
    }

    public function privateDownloadUrl($path, $expires = 3600)
    {
        $auth = $this->getAuth();
        $location = $this->applyPathPrefix($path);
        $authUrl = $auth->privateDownloadUrl($location, $expires);

        return $authUrl;
    }

    public function persistentFop($path = null, $fops = null)
    {        
        
        $auth = $this->getAuth();

        $pfop = new PersistentFop($auth);

        list($id, $error) = $pfop->execute($this->bucket, $path, $fops);


        if ($error != null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return $id;
        }
    }

    public function persistentStatus($id)
    {        
        $auth = $this->getAuth();

        $pfop = new PersistentFop($auth);

        return $pfop->status($id);
    }

    public function downloadUrl($path = null)
    {
        $location = $this->applyPathPrefix($path);

        return $location;
    }

    public function imageInfo($path = null)
    {
        $operation = $this->getOperation();

        list($ret, $error) = $operation->execute($path, 'imageInfo');

        if ($error !== null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return $ret;
        }
    }

    public function imageExif($path = null)
    {
        $operation = $this->getOperation();

        list($ret, $error) = $operation->execute($path, 'exif');

        if ($error !== null) {
            $this->logQiniuError($error);

            return false;
        } else {
            return $ret;
        }
    }

    public function imagePreviewUrl($path = null, $ops = null)
    {
        $operation = $this->getOperation();
        $url = $operation->buildUrl($path, $ops);

        return $url;
    }

    public function uploadToken(
        $path = null,
        $expires = 3600,
        $policy = null,
        $strictPolicy = true
    ) {
        $auth = $this->getAuth();

        $token = $auth->uploadToken(
            $this->bucket,
            $path,
            $expires,
            $policy,
            $strictPolicy
        );

        return $token;
    }
}

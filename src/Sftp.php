<?php

namespace Reaway\Net;

use Exception;

/**
 * SFTP类
 * Class sftp
 */
class Sftp
{
    private $connection;
    private $sftp;

    /**
     * @var array 初始配置
     */
    private array $config = [
        'host' => '127.0.0.1',
        'port' => 22,
        'username' => '',
        'password' => '',
        'use_public_key' => false,
        'public_key_file' => '',
        'private_key_file' => '',
        'passphrase' => ''
    ];

    /**
     * 初始化
     *
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        //检测ssh2模块
        if (!\extension_loaded('ssh2')) {
            throw new Exception('ssh2 extension not supported');
        }

        $this->config = array_merge($this->config, $config);
        $this->connect();
    }

    /**
     * 连接ssh, 连接有两种方式: (1) 使用密码 (2) 使用秘钥
     * @throws Exception
     */
    public function connect()
    {
        $methods = $this->config['use_public_key'] ? ['hostkey' => 'ssh-rsa'] : [];
        $this->connection = @ssh2_connect($this->config['host'], $this->config['port'], $methods);
        if (!$this->connection) {
            throw new Exception('Could not connect to ' . $this->config['host'] . ' on port ' . $this->config['port'] . '.');
        }

        if ($this->config['use_public_key']) { // (1) 使用秘钥的时候
            if (!@ssh2_auth_pubkey_file($this->connection, $this->config['username'], $this->config['public_key_file'], $this->config['private_key_file'], $this->config['passphrase'])) {
                throw new Exception('Could not authenticate with username ' . $this->config['username'] . 'and public_key_file ' . $this->config['public_key_file'] . '.');
            }
        } else { // (2) 使用秘钥
            if (!@ssh2_auth_password($this->connection, $this->config['username'], $this->config['password'])) {
                throw new Exception('Could not authenticate with username ' . $this->config['username'] . 'and password ' . $this->config['password'] . '.');
            }
        }

        $this->sftp = @ssh2_sftp($this->connection);
        if (!$this->sftp) {
            throw new Exception('Could not initialize SFTP subsystem.');
        }
    }

    /**
     * 断开连接
     */
    public function disconnect()
    {
        @ssh2_disconnect($this->connection);
    }

    /**
     * 下载文件
     * @param string $remote_file
     * @param string $local_file
     * @return bool
     */
    public function download(string $remote_file, string $local_file): bool
    {
        return @copy('ssh2.sftp://' . $this->sftp . $remote_file, $local_file);
    }

    /**
     * 上传文件
     * @param string $local_file
     * @param string $remote_file
     * @return bool
     */
    public function upload(string $local_file, string $remote_file): bool
    {
        return @copy($local_file, 'ssh2.sftp://' . $this->sftp . $remote_file);
    }

    /**
     * 创建目录
     * @param string $dir
     * @param int $mode
     * @return bool
     */
    public function mkdir(string $dir, int $mode = 0777): bool
    {
        return ssh2_sftp_mkdir($this->sftp, $dir, $mode);
    }

    /**
     * 判段目录是否存在
     * @param string $filename
     * @return bool
     */
    public function exists(string $filename): bool
    {
        return file_exists('ssh2.sftp://' . $this->sftp . $filename);
    }

    /**
     * 列目录
     * @param string $dir
     * @return array
     */
    public function readdir(string $dir = '/'): array
    {
        $files = [];
        $dirHandle = opendir('ssh2.sftp://' . $this->sftp . $dir);
        while (false !== ($file = readdir($dirHandle))) {
            if ($file != '.' && $file != '..') {
                $files[] = $file;
            }
        }
        return $files;
    }

    public function __destruct()
    {
        //$this->disconnect();
    }
}
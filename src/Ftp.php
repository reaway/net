<?php

namespace Reaway\Net;

use Exception;

class Ftp
{
    static private $_instance = null;
    private $ftp = null;

    /**
     * 配置
     * @var array
     */
    public $config = [
        'host' => '127.0.0.1',
        'port' => 21,
        'timeout' => 90,
        'username' => '',
        'password' => '',
        'pasv' => false
    ];

    /**
     * Ftp constructor.
     * @param array $config
     * @throws Exception
     */
    public function __construct(array $config = [])
    {
        $this->config = array_merge($this->config, $config);

        //检测FTP模块
        if (!\extension_loaded('ftp')) {
            throw new Exception('FTP extension not supported');
        }

        //建立一个新的FTP连接
        $this->ftp = \ftp_connect($this->config['host'], $this->config['port'], $this->config['timeout']);
        if (!$this->ftp) {
            throw new Exception('FTP connection fail');
        }

        //登录FTP服务器
        if (\ftp_login($this->ftp, $this->config['username'], $this->config['password'])) {
            throw new Exception('FTP login fail');
        }

        //是否开启被动模式
        if ($this->config['pasv']) {
            \ftp_pasv($this->ftp, true);
        }
    }

    /**
     * Ftp destructor
     */
    public function __destruct()
    {
    }

    /**
     * 公有的静态方法
     * @param $config
     * @return FtpFile|null
     */
    static public function getInstance($config)
    {
        if (!(self::$_instance instanceof self)) {
            self::$_instance = new FtpFile($config);
        }
        return self::$_instance;
    }

    /**
     * 上传文件
     * @param $remote_file
     * @param $local_file
     * @param int $mode
     * @return bool
     */
    function upload($remote_file, $local_file, $mode = FTP_BINARY)
    {
        return ftp_put($this->ftp, $remote_file, $local_file, $mode);
    }

    /**
     * 返回给定目录的文件列表
     * @param $directory
     * @return array
     */
    function list($directory = '/')
    {
        return $contents = ftp_nlist($this->ftp, $directory);
    }

    /**
     * 移动文件
     * @param $oldname
     * @param $newname
     * @return bool
     */
    function move($oldname, $newname)
    {
        return ftp_rename($this->ftp, $oldname, $newname);
    }

    /**
     * 删除文件
     * @param $path
     * @return bool
     */
    function delete($path)
    {
        return ftp_delete($this->ftp, $path);
    }

    /**
     * 生成目录
     * @param $path
     */
    function mkdir($path)
    {
        $path_arr = explode('/', $path); // 取目录数组
        $file_name = array_pop($path_arr); // 弹出文件名
        $path_div = count($path_arr); // 取层数

        foreach ($path_arr as $val) // 创建目录
        {
            if (@ftp_chdir($this->ftp, $val) == false) {
                $tmp = @ftp_mkdir($this->ftp, $val);
                if (!ftp_mkdir($this->ftp, $val)) {
                    exit;
                }
                @ftp_chdir($this->ftp, $val);
            }
        }

        for ($i = 1; $i <= $path_div; $i++)         // 回退到根
        {
            @ftp_cdup($this->ftp);
        }
    }

    /**
     * 关闭连接
     */
    public function close()
    {
        ftp_close($this->ftp);
    }
}
<?php
include(__DIR__.'/vendor/autoload.php');

use phpseclib\Crypt\RSA;
use phpseclib\Net\SFTP;

$config = require_once(__DIR__."/config/config.php");

class EMR_SFTP
{
	/**
	 * @var SFTP
	 */
	public $sftp;

	/**
	 * Array of created directories during upload.
	 * @var array
	 */
	public $directories = [];

	/**
	 * __construct
	 */
	public function __construct()
	{
	$this->sftp = new SFTP($config['hostname'], $config['port']);
	if (!$this->sftp)
		throw new \Exception("Could not connect to $host on port $port.");
	}
	/**
     * Connects to remote server.
     * @return true|null
     * @throws Exception
     */
    public function connect($config)
    {
        $auth_type = $config['auth_type'];
		$username = $config['username'];
        $result = null;
        switch ($auth_type) {
          case 'password':
			  $result = $this->sftp->login($username, $config['password']);
              break;
          case 'rsa':
              $file = $config['file'];
              $keyRSA = new RSA();
              $keyRSA->loadKey(file_get_contents($file));
              $result = $this->sftp->login($username, $keyRSA);
              break;
          case 'rsa_password':
              $file = $config['file'];
              $password = $config['password'];
              $keyRSA = new RSA();
              $keyRSA->loadKey(file_get_contents($file));                
              $keyRSA->setPassword($password);
              $result = $this->sftp->login($username, $keyRSA);
              break;
		  default:
              throw new \Exception('You need to specify authentication method.');
        }
        if (is_null($result)) {
          throw new \Exception("No authentication for given type: {$auth_type}");
        }
	}
	/**
     * Check if not connected and connect.
     */
    public function checkConnection()
    {
        if (null === $this->sftp) {
            $this->connect();
        }
    }
    /**
     * Downloads file(s) to the SFTP server.
     * @param string $remote_file
     * @param string $local_file
     * @return mixed|Exception
     */
    public function upload($remote_file, $local_file)
    {
        $this->checkConnection();
        $dir = dirname($remote_file);
        if (!isset($this->directories[$dir])) {
            $this->sftp->mkdir($dir, -1, true);
            $this->directories[$dir] = true;
        }
        if (!$this->sftp->put($remote_file, $local_file, SFTP::SOURCE_LOCAL_FILE)) {
            throw new \Exception(implode($this->sftp->getSFTPErrors(), "\n"));
        }
	}
	/**
     * Downloads a file from the SFTP server.
     * @param string $remote_file
     * @param string $local_file
     * @return mixed|Exception
     */
    public function download($remote_file, $local_file)
    {
        $this->checkConnection();
        if (!$this->sftp->get($remote_file, $local_file)) {
			throw new \Exception(implode($this->sftp->getSFTPErrors(), "\n"));
        }
    }
    /**
     * Disconnect from SFTP.
     */
    public function disconnect()
    {
        $this->sftp->disconnect();
        $this->sftp = null;
	}
}
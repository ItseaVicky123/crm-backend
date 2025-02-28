<?php


namespace App\Lib\Utilities\Ftp;

use App\Models\FtpProtocolType;

/**
 * Class Credentials
 * @package App\Lib\Utilities\Ftp
 */
class Credentials
{
    /**
     * @var string $username
     */
    protected $username;

    /**
     * @var string $password
     */
    protected $password;

    /**
     * @var string $baseUrl
     */
    protected string $baseUrl;

    /**
     * @var string $downloadPath
     */
    protected string $downloadPath = '';

    /**
     * @var string $uploadPath
     */
    protected string $uploadPath = '';

    /**
     * @var int $protocolType
     */
    protected int $protocolType = FtpProtocolType::SFTP;

    /**
     * @var int $port
     */
    protected int $port = 22;

    /**
     * @var bool $isSsl
     */
    protected bool $isSsl = false;

    /**
     * @var int $timeout
     */
    protected int $timeout = 90;

    /**
     * Credentials constructor.
     * @param string $username
     * @param string $password
     * @param string $baseUrl
     */
    public function __construct(string $username, string $password, string $baseUrl)
    {
        $this->username = $username;
        $this->password = $password;
        $this->baseUrl  = $baseUrl;
    }

    /**
     * Whether or not the protocol is SFTP
     * @return bool
     */
    public function isSftp(): bool
    {
        return $this->protocolType == FtpProtocolType::SFTP;
    }

    /**
     * Whether or not the protocol is FTP
     * @return bool
     */
    public function isFtp(): bool
    {
        return $this->protocolType == FtpProtocolType::FTP;
    }

    /**
     * Whether or not the protocol is FTPS
     * @return bool
     */
    public function isFtps(): bool
    {
        return $this->protocolType == FtpProtocolType::FTPS;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     * @return Credentials
     */
    public function setUsername(string $username): Credentials
    {
        $this->username = $username;
        return $this;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @param string $password
     * @return Credentials
     */
    public function setPassword(string $password): Credentials
    {
        $this->password = $password;
        return $this;
    }

    /**
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    /**
     * @param string $baseUrl
     * @return Credentials
     */
    public function setBaseUrl(string $baseUrl): Credentials
    {
        $this->baseUrl = $baseUrl;
        return $this;
    }

    /**
     * @return string
     */
    public function getDownloadPath(): string
    {
        return $this->downloadPath;
    }

    /**
     * @param string $downloadPath
     * @return Credentials
     */
    public function setDownloadPath(string $downloadPath): Credentials
    {
        $this->downloadPath = $downloadPath;
        return $this;
    }

    /**
     * @return string
     */
    public function getUploadPath(): string
    {
        return $this->uploadPath;
    }

    /**
     * @param string $uploadPath
     * @return Credentials
     */
    public function setUploadPath(string $uploadPath): Credentials
    {
        $this->uploadPath = $uploadPath;
        return $this;
    }

    /**
     * @return int
     */
    public function getProtocolType(): int
    {
        return $this->protocolType;
    }

    /**
     * @param int $protocolType
     * @return Credentials
     */
    public function setProtocolType(int $protocolType): Credentials
    {
        $this->protocolType = $protocolType;
        return $this;
    }

    /**
     * @return int|null
     */
    public function getPort(): ?int
    {
        return $this->port;
    }

    /**
     * @param int $port
     * @return Credentials
     */
    public function setPort(int $port): Credentials
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @return bool
     */
    public function isSsl(): bool
    {
        return $this->isSsl;
    }

    /**
     * @param bool $isSsl
     * @return Credentials
     */
    public function setIsSsl(bool $isSsl): Credentials
    {
        $this->isSsl = $isSsl;
        return $this;
    }

    /**
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * @param int $timeout
     * @return Credentials
     */
    public function setTimeout(int $timeout): Credentials
    {
        $this->timeout = $timeout;
        return $this;
    }
}

<?php


namespace App\Lib\Utilities\Ftp;

use altayalp\FtpClient\Servers\FtpServer;
use altayalp\FtpClient\Servers\SftpServer;
use altayalp\FtpClient\FileFactory;
use altayalp\FtpClient\Exceptions\ExtensionMissingException;
use altayalp\FtpClient\Interfaces\FileInterface;


/**
 * Class Ftp
 * @package App\Lib\Utilities
 */
class Ftp
{
    /**
     * @var Credentials $credentials
     */
    protected Credentials $credentials;

    /**
     * @var FtpServer|null $ftpServer
     */
    protected ?FtpServer $ftpServer = null;

    /**
     * @var SftpServer|null $sftpServer
     */
    protected ?SftpServer $sftpServer = null;

    /**
     * @var FileInterface|null $fileHandler
     */
    protected ?FileInterface $fileHandler = null;

    /**
     * Ftp constructor.
     * @param Credentials $credentials
     * @throws ExtensionMissingException
     */
    public function __construct(Credentials $credentials)
    {
        $this->credentials = $credentials;

        if ($this->credentials->isSftp()) {
            $this->sftpServer = new SftpServer($this->credentials->getBaseUrl(), $this->credentials->getPort());
        } else {
            $this->ftpServer = new FtpServer(
                $this->credentials->getBaseUrl(),
                $this->credentials->getPort(),
                $this->credentials->getTimeout());
        }

        $this->server()
            ->login(
                $this->credentials->getUsername(),
                $this->credentials->getPassword()
            );
        $this->fileHandler = FileFactory::build($this->server());
    }

    /**
     * Fetch the active server
     * @return FtpServer|SftpServer
     */
    public function server()
    {
        if ($this->credentials->isSftp()) {
            return $this->sftpServer;
        }

        return $this->ftpServer;
    }

    /**
     * Upload a file to a remote FTP server location.
     * @param string $localPath
     * @param string $remoteSource
     * @return bool
     * @throws \altayalp\FtpClient\Exceptions\FileException
     */
    public function uploadAsFile(string $localPath, $remoteSource = ''): bool
    {
        return $this->fileHandler
            ->upload($localPath, $remoteSource ?: $this->credentials->getUploadPath());
    }

    /**
     * Upload a string to a remote FTP server location.
     * @param string $content
     * @param string $remoteSource
     * @return bool
     */
    public function uploadAsString(string $content, $remoteSource = ''): bool
    {
        $handle = tmpfile();
        fwrite($handle, $content);
        rewind($handle);
        $metaData = stream_get_meta_data($handle);
        $local    = $metaData['uri'];
        $remote   = $remoteSource ?: $this->credentials->getUploadPath();
        $success  = $this->uploadAsFile($local, $remote);
        fclose($handle);

        return $success;
    }

    /**
     * Download file from the remote FTP server to a local file destination.
     * @param string $local
     * @param string $remoteSource
     * @return bool
     * @throws \altayalp\FtpClient\Exceptions\FileException
     */
    public function downloadToFile(string $local, $remoteSource = ''): bool
    {
        return $this->fileHandler
            ->download($remoteSource ?: $this->credentials->getDownloadPath(), $local);
    }

    /**
     * Download file from the remote FTP server to a string.
     * @param string $remotePath
     * @return string|null
     * @throws \altayalp\FtpClient\Exceptions\FileException
     */
    public function downloadToString($remotePath = ''): ?string
    {
        $content  = null;
        $remote   = $remotePath ?: $this->credentials->getDownloadPath();
        $handle   = tmpfile();
        $metaData = stream_get_meta_data($handle);
        $local    = $metaData['uri'];

        if ($this->downloadToFile($local, $remote)) {
            rewind($handle);
            $content = stream_get_contents($handle);
        }

        fclose($handle);

        return $content;
    }
}

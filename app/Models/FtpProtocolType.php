<?php

namespace App\Models;

/**
 * Class FtpProtocolType
 * @package App\Models
 */
class FtpProtocolType extends ReadOnlyModel
{
    const SFTP = 1;
    const FTPS = 2;
    const FTP  = 3;

    /**
     * @var string $connection
     */
    protected $connection = self::SLAVE_CONNECTION;
    /**
     * @var string
     */
    public $table = 'tlkp_ftp_protocol_types';

    /**
     * @var array
     */
    protected $visible = [
        'id',
        'name',
        'active',
    ];

    /**
     * @var bool
     */
    public $timestamps  = false;
}

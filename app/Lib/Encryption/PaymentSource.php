<?php

namespace App\Lib\Encryption;

use Aws\Kms\KmsClient;
use aws_factory;
use Exception;
use fileLogger;
use kms_key;

/**
 * Class PaymentSource
 * @package App\Lib\Encryption
 */
class PaymentSource
{
    const RETRY_ATTEMPTS = 3;

    /**
     * @var KmsClient|null
     */
    protected static $kms_client;

    public static function init()
    {
        if (! isset(self::$kms_client)) {
            try {
                self::$kms_client = new KmsClient(aws_factory::credentials());
            } catch (Exception $e) {
                fileLogger::notification($e->getMessage(), __METHOD__, 'KMS_ERROR', __FILE__);
            }
        }
    }

    /**
     * @param int $length
     * @return string
     */
    public static function get_cvv_mask($length = 0)
    {
        return str_repeat('X', $length);
    }

    /**
     * @param $card
     * @return string
     */
    public static function get_cc_mask_from_card($card)
    {
        return self::get_cc_mask(
            strlen($card),
            self::get_last_four_from_cc($card),
            ''
        );
    }

    /**
     * @param int    $cc_length
     * @param string $last_four
     * @param string $first_six
     * @return string
     */
    public static function get_cc_mask($cc_length = 16, $last_four = '', $first_six = '')
    {
        if (! empty($last_four)) {
            $cc_length -= 4;
            $last_four  = str_pad($last_four, 4, '0', STR_PAD_LEFT);
        }

        if (! empty($first_six)) {
            $cc_length -= 6;
        }

        return $first_six . str_repeat('X', $cc_length) . $last_four;
    }

    /**
     * @param $cc_number
     * @return bool|string
     */
    public static function get_last_four_from_cc($cc_number)
    {
        return substr($cc_number, -4);
    }

    /**
     * @param $cc_number
     * @return bool|string
     */
    public static function get_first_six_from_cc($cc_number)
    {
        return substr($cc_number, 0, 6);
    }

    /**
     * @param $cc_number
     * @param null $context
     * @return string
     */
    public static function encrypt($cc_number, $context = null)
    {
        $encrypted = '';

        if (! empty($cc_number)) {
            self::init();

            $attempt = 0;

            do {
                try {
                    $kms_key = new kms_key();
                    $kms_key->set_ciphertext(
                        base64_encode(
                            self::$kms_client
                                ->encrypt([
                                    'KeyId'             => "alias/{$kms_key->alias}",
                                    'Plaintext'         => $cc_number,
                                    'EncryptionContext' => ['location' => $context ?? DB_DATABASE],
                                ])
                                ->get('CiphertextBlob')
                        )
                    );

                    $encrypted = $kms_key->to_hash();
                } catch (Exception $e) {
                    fileLogger::notification(
                        sprintf(
                            '[%d] %s',
                            $attempt + 1,
                            $e->getMessage()
                        ),
                        __METHOD__,
                        'KMS_ERROR',
                        __FILE__
                    );
                }
            } while (++$attempt < self::RETRY_ATTEMPTS);
        }

        if (empty($encrypted)) {
            $encrypted = $cc_number;
        }

        return $encrypted;
    }

    /**
     * @param $encrypted
     * @param null $context
     * @return bool|mixed|string|string[]|null
     */
    public static function decrypt($encrypted, $context = null)
    {
        self::init();

        $decrypted = '';

        if (! empty($encrypted)) {
            $failed  = false;
            $kms_key = new kms_key();
            $attempt = 0;

            if ($is_kms = $kms_key->parse($encrypted)) {
                do {
                    try {
                        $decrypted = self::$kms_client
                            ->decrypt([
                                'CiphertextBlob'    => base64_decode($kms_key->ciphertext),
                                'EncryptionContext' => ['location' => $context ?? DB_DATABASE],
                            ])
                            ->get('Plaintext');
                    } catch (Exception $e) {
                        $failed = true;

                        fileLogger::notification(
                            sprintf(
                                '[%d] %s',
                                $attempt + 1,
                                $e->getMessage()
                            ),
                            __METHOD__,
                            'KMS_ERROR',
                            __FILE__
                        );
                    }
                } while (! strlen($decrypted) && ++$attempt < self::RETRY_ATTEMPTS);
            } else {
                $decrypted = \payment_source::_legacy_decrypt_credit_card($encrypted);
            }

            if ($failed) {
                fileLogger::notification(
                    "Failed to decrypt card after {$attempt} attempts",
                    __METHOD__,
                    'KMS_ERROR',
                    __FILE__
                );
            }
        }

        if ($decrypted === false || $decrypted == '') {
            fileLogger::notification('Failed decryption', __METHOD__, 'DECRYPT_ERROR', __FILE__);
        }

        return $decrypted;
    }
}

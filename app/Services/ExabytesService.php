<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExabytesService
{
    protected ?string $username = null;

    protected ?string $password = null;

    protected ?string $brandName = null;

    protected string $baseUrl = 'https://smsportal.exabytes.my/sms_api.php';

    public function __construct()
    {
        $this->username = config('services.exabytes.username');
        $this->password = config('services.exabytes.password');
        $this->brandName = config('services.exabytes.brand_name', 'AURAE');
    }

    /**
     * Send an OTP SMS to a phone number.
     * Following Malaysian standard format: RM0.00 [BrandName]: Your verification code is 123456.
     */
    public function sendOtp(string $phone, string $code): array
    {
        if (! $this->username || ! $this->password) {
            return [
                'success' => false,
                'error' => 'Exabytes is not configured. Please check your credentials.',
            ];
        }

        if (! $this->brandName) {
            return [
                'success' => false,
                'error' => 'Exabytes brand name is not configured.',
            ];
        }

        // Format message according to Malaysian standard
        $message = "Your verification code is {$code}. This code expires in 5 minutes.";

        return $this->sendSms($phone, $message);
    }

    /**
     * Send a custom SMS message to a phone number.
     */
    public function sendSms(string $phone, string $body): array
    {
        if (! $this->username || ! $this->password) {
            return [
                'success' => false,
                'error' => 'Exabytes is not configured. Please check your credentials.',
            ];
        }

        try {
            // Normalize phone number
            $phone = $this->normalizePhoneNumber($phone);

            // Determine message type (1 for ASCII, 2 for Unicode)
            $type = $this->detectMessageType($body);

            // Make HTTPS request to Exabytes API
            $response = Http::timeout(30)->get($this->baseUrl, [
                'un' => $this->username,
                'pwd' => $this->password,
                'dstno' => $phone,
                'msg' => $body,
                'type' => $type,
                'agreedterm' => 'YES',
            ]);

            // Check if request was successful
            if ($response->successful()) {
                $responseBody = $response->body();

                // Check for error responses
                if (str_contains(strtolower($responseBody), 'error') ||
                    str_contains(strtolower($responseBody), 'failed') ||
                    str_contains(strtolower($responseBody), 'invalid')) {
                    Log::error('Exabytes SMS Error', [
                        'phone' => $phone,
                        'response' => $responseBody,
                    ]);

                    return [
                        'success' => false,
                        'error' => 'Failed to send SMS: '.$responseBody,
                    ];
                }

                Log::info('Exabytes SMS Sent', [
                    'phone' => $phone,
                    'response' => $responseBody,
                ]);

                return [
                    'success' => true,
                    'message_id' => $responseBody,
                ];
            }

            Log::error('Exabytes SMS HTTP Error', [
                'phone' => $phone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to send SMS. HTTP Status: '.$response->status(),
            ];
        } catch (\Exception $e) {
            Log::error('Exabytes SMS Exception', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Normalize phone number to ensure proper format for Exabytes.
     * Exabytes expects format like: 60123456789 (country code + number without +)
     */
    protected function normalizePhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters except +
        $phone = preg_replace('/[^0-9+]/', '', $phone);

        // Remove leading + if present
        $phone = ltrim($phone, '+');

        // If phone starts with 0 (Malaysian local format), replace with country code 60
        if (str_starts_with($phone, '0')) {
            $phone = '60'.substr($phone, 1);
        }

        // If phone doesn't start with country code, assume Malaysian and add 60
        if (! str_starts_with($phone, '60') && strlen($phone) < 11) {
            $phone = '60'.$phone;
        }

        return $phone;
    }

    /**
     * Detect message type based on content.
     * Type 1: ASCII (English, Bahasa Melayu)
     * Type 2: Unicode (Chinese, Japanese, Emojis, etc.)
     */
    protected function detectMessageType(string $message): int
    {
        // Check if message contains non-ASCII characters
        if (preg_match('/[^\x00-\x7F]/', $message)) {
            return 2; // Unicode
        }

        return 1; // ASCII
    }
}

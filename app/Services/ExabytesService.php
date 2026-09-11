<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExabytesService
{
    protected ?string $username = null;

    protected ?string $password = null;

    protected ?string $brandName = null;

    protected string $baseUrl = 'https://smsportal.exabytes.my/isms_send.php';

    public function __construct()
    {
        $this->username = config('services.exabytes.username');
        $this->password = config('services.exabytes.password');
        $this->brandName = config('services.exabytes.brand_name', 'AURAE');
    }

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

        $message = "Your verification code is {$code}. This code expires in 5 minutes.";

        return $this->sendSms($phone, $message);
    }

    public function sendSms(string $phone, string $body): array
    {
        if (! $this->username || ! $this->password) {
            return [
                'success' => false,
                'error' => 'Exabytes is not configured. Please check your credentials.',
            ];
        }

        try {

            $phone = $this->normalizePhoneNumber($phone);

            $type = $this->detectMessageType($body);

            $response = Http::timeout(30)->get($this->baseUrl, [
                'un' => $this->username,
                'pwd' => $this->password,
                'dstno' => $phone,
                'msg' => $body,
                'type' => $type,
                'agreedterm' => 'YES',
            ]);

            if ($response->successful()) {
                $responseBody = $response->body();

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

    protected function normalizePhoneNumber(string $phone): string
    {

        $phone = preg_replace('/[^0-9+]/', '', $phone);

        $phone = ltrim($phone, '+');

        if (str_starts_with($phone, '0')) {
            $phone = '60'.substr($phone, 1);
        }

        if (! str_starts_with($phone, '60') && strlen($phone) < 11) {
            $phone = '60'.$phone;
        }

        return $phone;
    }

    protected function detectMessageType(string $message): int
    {

        if (preg_match('/[^\x00-\x7F]/', $message)) {
            return 2;
        }

        return 1;
    }
}

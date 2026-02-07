<?php

namespace App\Services;

use Twilio\Rest\Client;

class TwilioService
{
    protected ?Client $client = null;

    protected ?string $from = null;

    public function __construct()
    {
        $sid = config('services.twilio.sid');
        $token = config('services.twilio.token');
        $this->from = config('services.twilio.from');

        if ($sid && $token) {
            $this->client = new Client($sid, $token);
        }
    }

    /**
     * Send an OTP SMS to a phone number.
     */
    public function sendOtp(string $phone, string $code): array
    {
        if (! $this->client) {
            return [
                'success' => false,
                'error' => 'Twilio is not configured. Please check your credentials.',
            ];
        }

        if (! $this->from) {
            return [
                'success' => false,
                'error' => 'Twilio FROM number is not configured.',
            ];
        }

        try {
            $message = $this->client->messages->create(
                $phone,
                [
                    'from' => $this->from,
                    'body' => "Your Aurae verification code is: {$code}. This code expires in 5 minutes.",
                ]
            );

            return [
                'success' => true,
                'sid' => $message->sid,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a custom SMS message to a phone number.
     */
    public function sendSms(string $phone, string $body): array
    {
        if (! $this->client) {
            return [
                'success' => false,
                'error' => 'Twilio is not configured. Please check your credentials.',
            ];
        }

        if (! $this->from) {
            return [
                'success' => false,
                'error' => 'Twilio FROM number is not configured.',
            ];
        }

        try {
            $message = $this->client->messages->create(
                $phone,
                [
                    'from' => $this->from,
                    'body' => $body,
                ]
            );

            return [
                'success' => true,
                'sid' => $message->sid,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}

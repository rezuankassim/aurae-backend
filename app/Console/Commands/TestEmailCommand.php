<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mail:test {to : The recipient email address}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send a test email to verify mail credentials are configured correctly';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $to = $this->argument('to');

        $mailer = config('mail.default');
        $from = config('mail.from.address');
        $fromName = config('mail.from.name');

        $this->info("Mail driver: {$mailer}");
        $this->info("From: {$fromName} <{$from}>");
        $this->info("To: {$to}");

        if ($mailer === 'smtp') {
            $this->info('SMTP Host: '.config('mail.mailers.smtp.host'));
            $this->info('SMTP Port: '.config('mail.mailers.smtp.port'));
        }

        $this->newLine();
        $this->info('Sending test email...');

        try {
            Mail::raw('This is a test email from Aurae Backend to verify that mail credentials are working correctly.', function ($message) use ($to) {
                $message->to($to)
                    ->subject('Aurae Backend – Test Email');
            });

            $this->newLine();
            $this->info('✅ Test email sent successfully!');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->newLine();
            $this->error('❌ Failed to send test email.');
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }
}

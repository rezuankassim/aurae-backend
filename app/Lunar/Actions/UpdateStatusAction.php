<?php

namespace App\Lunar\Actions;

use Filament\Forms;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Lunar\Admin\Support\Actions\Orders\UpdateStatusAction as BaseUpdateStatusAction;
use Lunar\Models\Order;

class UpdateStatusAction extends BaseUpdateStatusAction
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->form(
            $this->getFormSteps()
        );

        $this->action(
            fn (Order $record, array $data) => $this->updateStatus($record, $data)
        );
    }

    protected function getFormSteps()
    {
        $steps = parent::getFormSteps();

        // Insert tracking number field after the status select
        array_splice($steps, 1, 0, [
            Forms\Components\TextInput::make('tracking_number')
                ->label('Tracking Number')
                ->required(fn (Forms\Get $get) => $get('status') === 'dispatched')
                ->visible(fn (Forms\Get $get) => $get('status') === 'dispatched')
                ->default(fn (?Order $record) => $record?->meta['tracking_number'] ?? null)
                ->placeholder('Enter tracking number')
                ->maxLength(255)
                ->live(),
        ]);

        return $steps;
    }

    protected function updateStatus(Order $record, array $data)
    {
        if ($data['status'] === 'dispatched' && ! empty($data['tracking_number'])) {
            $meta = $record->meta ?? [];
            $meta['tracking_number'] = $data['tracking_number'];

            $record->update([
                'status' => $data['status'],
                'meta' => $meta,
            ]);
        } else {
            $record->update([
                'status' => $data['status'],
            ]);
        }

        if (isset($data['send_notifications']) && ! $data['send_notifications']) {
            Notification::make()->title(
                __('lunarpanel::actions.orders.update_status.notification.label')
            )->success()->send();

            return;
        }

        $emails = collect(
            [...$data['email_addresses'] ?? [], $data['additional_email'] ?? null]
        )->filter()->unique();

        foreach ($data['mailers'] ?? [] as $mailerClass) {
            $mailable = new $mailerClass($record, $data['additional_content']);
            $mailable->with('order', $record)
                ->with('content', $data['additional_content']);
            foreach ($emails as $email) {
                Mail::to($email)
                    ->queue($mailable);

                $storedPath = 'orders/activity/'.Str::random().'.html';

                Storage::put(
                    $storedPath,
                    $mailable->render()
                );

                activity()
                    ->causedBy(auth()->user())
                    ->performedOn($record)
                    ->event('email-notification')
                    ->withProperties([
                        'template' => $storedPath,
                        'email' => $email,
                        'mailer' => $mailerClass,
                    ])->log('email-notification');
            }
        }

        Notification::make()->title(
            __('lunarpanel::actions.orders.update_status.notification.label')
        )->success()->send();
    }
}

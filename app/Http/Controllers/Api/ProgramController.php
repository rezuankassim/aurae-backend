<?php

namespace App\Http\Controllers\Api;

use App\Events\ProgramStopped;
use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Jobs\SendPushNotification;
use App\Models\AdminNotification;
use App\Models\ProgramLog;
use App\Models\UsageHistory;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ProgramController extends Controller
{
    /**
     * Start a program and notify the user.
     */
    public function start(Request $request)
    {
        $request->validate([
            'program_id' => ['required', 'integer', 'exists:therapies,id'],
            'program_name' => ['required', 'string', 'max:255'],
            'program_duration' => ['required', 'string', 'max:50'],
            'program_start_at' => ['required', 'string'],
        ]);

        $programLog = ProgramLog::create([
            'user_id' => $request->user()->id,
            'therapy_id' => $request->input('program_id'),
            'program_duration' => $request->input('program_duration'),
            'action' => 'start',
            'program_started_at' => Carbon::createFromFormat('dmY H:i:s', $request->input('program_start_at')),
        ]);

        $programLog->load('therapy');

        $content = [
            'duration' => 0,
            'force_stopped' => false,
            'started_at' => $request->input('program_start_at'),
            'ended_at' => null,
        ];

        UsageHistory::create([
            'user_id' => $request->user()->id,
            'therapy_id' => $request->input('program_id'),
            'content' => $content,
        ]);

        SendPushNotification::dispatch(
            [$request->user()->id],
            'Program Started',
            "{$programLog->therapy->name} has started. Duration: {$programLog->program_duration}.",
            [
                'therapy_id' => (string) $programLog->therapy_id,
                'program_log_id' => (string) $programLog->id,
                'action' => 'start',
            ],
            'program',
        );

        return BaseResource::make($programLog)
            ->additional([
                'status' => 201,
                'message' => 'Program started successfully.',
            ]);
    }

    /**
     * Stop a program and notify the user.
     */
    public function stop(Request $request)
    {
        $request->validate([
            'program_id' => ['required', 'integer', 'exists:therapies,id'],
            'program_name' => ['required', 'string', 'max:255'],
            'program_duration' => ['required', 'string', 'max:50'],
            'program_end_at' => ['required', 'string'],
            'program_error_message' => ['nullable', 'string'],
            'emergency' => ['nullable', 'boolean'],
        ]);

        $emergency = (bool) $request->input('emergency', false);

        $programLog = ProgramLog::create([
            'user_id' => $request->user()->id,
            'therapy_id' => $request->input('program_id'),
            'program_duration' => $request->input('program_duration'),
            'action' => 'stop',
            'program_ended_at' => Carbon::createFromFormat('dmY H:i:s', $request->input('program_end_at')),
            'program_error_message' => $request->input('program_error_message'),
            'emergency' => $emergency,
        ]);

        $programLog->load('therapy');

        $usageHistory = UsageHistory::where('user_id', $request->user()->id)
            ->where('therapy_id', $request->input('program_id'))
            ->where('content->started_at', $request->input('program_start_at'))
            ->where('content->ended_at', null)
            ->latest()
            ->first();

        // date format 24042026 14:42:57
        $startedAt = Carbon::createFromFormat('dmY H:i:s', $request->input('program_start_at'));
        $endedAt = Carbon::createFromFormat('dmY H:i:s', $request->input('program_end_at'));
        $duration = $startedAt->diffInMinutes($endedAt);
        $content = [
            'duration' => $duration,
            'force_stopped' => $emergency,
            'started_at' => $usageHistory ? $usageHistory->content->started_at : null,
            'ended_at' => $request->input('program_end_at'),
        ];

        $usageHistory->update([
            'content' => $content,
        ]);

        SendPushNotification::dispatch(
            [$request->user()->id],
            'Program Ended',
            "{$programLog->therapy->name} has ended. Duration: {$programLog->program_duration}.",
            [
                'therapy_id' => (string) $programLog->therapy_id,
                'program_log_id' => (string) $programLog->id,
                'action' => 'stop',
            ],
            'program',
        );

        $notificationType = $emergency ? 'emergency' : 'normal';
        $notificationTitle = $emergency
            ? "Emergency Stop: {$programLog->therapy->name}"
            : "Program Stopped: {$programLog->therapy->name}";
        $notificationBody = $emergency
            ? "Emergency stop triggered for {$programLog->therapy->name} by {$programLog->user->name}. Duration: {$programLog->program_duration}."
            : "{$programLog->therapy->name} was stopped by {$programLog->user->name}. Duration: {$programLog->program_duration}.";

        if ($request->input('program_error_message')) {
            $notificationBody .= " Error: {$request->input('program_error_message')}";
        }

        $programLog->load('user.guest');

        $isGuest = $programLog->user->isGuest();

        $adminNotification = AdminNotification::create([
            'type' => $notificationType,
            'title' => $notificationTitle,
            'body' => $notificationBody,
            'data' => [
                'program_log_id' => $programLog->id,
                'therapy_id' => $programLog->therapy_id,
                'therapy_name' => $programLog->therapy->name,
                'user_id' => $programLog->user_id,
                'user_name' => $programLog->user->name,
                'user_phone' => $programLog->user->phone,
                'is_guest' => $isGuest,
                'program_duration' => $programLog->program_duration,
                'program_error_message' => $request->input('program_error_message'),
                'emergency' => $emergency,
            ],
        ]);

        broadcast(new ProgramStopped(
            adminNotificationId: $adminNotification->id,
            type: $notificationType,
            title: $notificationTitle,
            body: $notificationBody,
            data: $adminNotification->data,
            createdAt: $adminNotification->created_at->toIso8601String(),
        ));

        return BaseResource::make($programLog)
            ->additional([
                'status' => 201,
                'message' => 'Program stopped successfully.',
            ]);
    }
}

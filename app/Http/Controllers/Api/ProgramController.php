<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BaseResource;
use App\Jobs\SendPushNotification;
use App\Models\ProgramLog;
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
        ]);

        $programLog = ProgramLog::create([
            'user_id' => $request->user()->id,
            'therapy_id' => $request->input('program_id'),
            'program_duration' => $request->input('program_duration'),
            'action' => 'stop',
            'program_ended_at' => Carbon::createFromFormat('dmY H:i:s', $request->input('program_end_at')),
        ]);

        $programLog->load('therapy');

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

        return BaseResource::make($programLog)
            ->additional([
                'status' => 201,
                'message' => 'Program stopped successfully.',
            ]);
    }
}

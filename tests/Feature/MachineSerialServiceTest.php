<?php

use App\Models\GeneralSetting;
use App\Models\Machine;
use App\Services\MachineSerialService;

beforeEach(function () {
    GeneralSetting::updateOrCreate(
        ['id' => 1],
        [
            'contact_no' => '0123456789',
            'machine_serial_format' => '{MMMM}{YYYY}{SSSS}',
            'machine_serial_prefix' => 'A101',
        ]
    );
});

test('generate next serial uses highest existing serial suffix for configured prefix and year', function () {
    $year = now()->format('Y');

    Machine::create([
        'serial_number' => "A101{$year}0002",
        'name' => 'Latest Created But Lower Serial',
        'status' => 1,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Machine::create([
        'serial_number' => "A101{$year}0005",
        'name' => 'Older Created But Higher Serial',
        'status' => 1,
        'created_at' => now()->subDay(),
        'updated_at' => now()->subDay(),
    ]);

    Machine::create([
        'serial_number' => "B202{$year}9999",
        'name' => 'Different Prefix',
        'status' => 1,
    ]);

    $service = app(MachineSerialService::class);
    $nextSerial = $service->generateNextSerialNumber();

    expect($nextSerial)->toBe("A101{$year}0006");
});

test('create machine with auto serial retries when duplicate serial is generated', function () {
    $year = now()->format('Y');
    $duplicateSerial = "A101{$year}0001";
    $nextSerial = "A101{$year}0002";

    Machine::create([
        'serial_number' => $duplicateSerial,
        'name' => 'Existing Machine',
        'status' => 1,
    ]);

    $service = \Mockery::mock(MachineSerialService::class)->makePartial();
    $service->shouldReceive('generateNextSerialNumber')
        ->twice()
        ->andReturn($duplicateSerial, $nextSerial);

    $machine = $service->createMachineWithAutoSerial([
        'name' => 'Retried Machine',
        'status' => 1,
    ]);

    expect($machine->serial_number)->toBe($nextSerial);
    expect(Machine::where('serial_number', $nextSerial)->exists())->toBeTrue();
});

test('bulk generate skips duplicate serials and still creates requested quantity', function () {
    $year = now()->format('Y');
    $service = app(MachineSerialService::class);

    Machine::create([
        'serial_number' => "A101{$year}0001",
        'name' => 'Existing Machine 1',
        'status' => 1,
    ]);

    Machine::create([
        'serial_number' => "A101{$year}0002",
        'name' => 'Existing Machine 2',
        'status' => 1,
    ]);

    $machines = $service->bulkGenerate(
        quantity: 2,
        baseName: 'Generated Machine',
        model: 'A101',
        year: $year,
        startProductCode: 1,
        status: 1
    );

    expect(collect($machines)->pluck('serial_number')->all())
        ->toBe([
            "A101{$year}0003",
            "A101{$year}0004",
        ]);
});

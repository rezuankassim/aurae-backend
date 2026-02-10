<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Machine;
use App\Services\MachineSerialService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MachineController extends Controller
{
    public function __construct(
        protected MachineSerialService $serialService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Machine::with(['user', 'device']);

        // Search filter
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serial_number', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Status filter
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        // Binding filter
        if ($request->has('bound') && $request->bound !== '') {
            if ($request->bound === '1') {
                $query->whereNotNull('user_id');
            } else {
                $query->whereNull('user_id');
            }
        }

        $machines = $query->latest()->paginate(20);

        return Inertia::render('admin/machine/index', [
            'machines' => $machines,
            'filters' => $request->only(['search', 'status', 'bound']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $nextSerial = $this->serialService->generateNextSerialNumber();

        return Inertia::render('admin/machine/create', [
            'next_serial' => $nextSerial,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'unique:machines,serial_number'],
            'status' => ['required', 'integer', 'in:0,1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'model' => ['nullable', 'string', 'size:4'],
            'year' => ['nullable', 'string', 'size:4'],
            'start_product_code' => ['nullable', 'integer', 'min:1', 'max:9999'],
            'variation_code' => ['nullable', 'string', 'size:1'],
        ]);

        // Bulk generation
        if ($request->has('quantity') && $request->quantity > 1) {
            $this->serialService->bulkGenerate(
                quantity: (int) $request->quantity,
                baseName: $validated['name'],
                model: $validated['model'] ?? 'A101',
                year: $validated['year'] ?? date('Y'),
                startProductCode: (int) ($validated['start_product_code'] ?? 1),
                variationCode: $validated['variation_code'] ?? '1',
                status: (int) $validated['status']
            );

            return to_route('admin.machines.index')
                ->with('success', "Successfully generated {$request->quantity} machines.");
        }

        // Single machine creation
        $serialNumber = $validated['serial_number'] ?? $this->serialService->generateNextSerialNumber();

        Machine::create([
            'serial_number' => $serialNumber,
            'name' => $validated['name'],
            'status' => $validated['status'],
        ]);

        return to_route('admin.machines.index')
            ->with('success', 'Machine created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Machine $machine)
    {
        $machine->load(['user', 'device']);

        return Inertia::render('admin/machine/show', [
            'machine' => $machine,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Machine $machine)
    {
        return Inertia::render('admin/machine/edit', [
            'machine' => $machine,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Machine $machine)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'serial_number' => ['required', 'string', 'unique:machines,serial_number,'.$machine->id],
            'status' => ['required', 'integer', 'in:0,1'],
        ]);

        $machine->update($validated);

        return to_route('admin.machines.index')
            ->with('success', 'Machine updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Machine $machine)
    {
        if ($machine->user_id) {
            return back()->with('error', 'Cannot delete a machine that is bound to a user. Please unbind it first.');
        }

        $machine->delete();

        return to_route('admin.machines.index')
            ->with('success', 'Machine deleted successfully.');
    }

    /**
     * Unbind machine from user.
     */
    public function unbind(Machine $machine)
    {
        if (! $machine->user_id) {
            return back()->with('error', 'This machine is not bound to any user.');
        }

        $machine->update([
            'user_id' => null,
            'device_id' => null,
        ]);

        return back()->with('success', 'Machine unbound successfully.');
    }

    /**
     * Activate machine.
     */
    public function activate(Machine $machine)
    {
        $machine->update(['status' => 1]);

        return back()->with('success', 'Machine activated successfully.');
    }

    /**
     * Deactivate machine.
     */
    public function deactivate(Machine $machine)
    {
        $machine->update(['status' => 0]);

        return back()->with('success', 'Machine deactivated successfully.');
    }
}

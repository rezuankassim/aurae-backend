<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Lunar\Models\Address;
use Lunar\Models\State;

class ConvertAddressStateIdsToNames extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'addresses:convert-state-ids {--dry-run : Preview changes without applying them}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Convert state IDs to state name strings in the lunar_addresses table';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        dd(\Carbon\Carbon::createFromFormat('dmY H:i:s', '24042026 22:54:56'));
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in dry-run mode. No changes will be made.');
        }

        $states = State::all()->keyBy('id');

        $addresses = Address::whereNotNull('state')
            ->where('state', '!=', '')
            ->get();

        $converted = 0;
        $skipped = 0;
        $notFound = 0;

        foreach ($addresses as $address) {
            // Skip if state is already a non-numeric string (already converted)
            if (! is_numeric($address->state)) {
                $skipped++;

                continue;
            }

            $state = $states->get((int) $address->state);

            if (! $state) {
                $this->warn("Address #{$address->id}: State ID '{$address->state}' not found in lunar_states.");
                $notFound++;

                continue;
            }

            if ($dryRun) {
                $this->line("Address #{$address->id}: '{$address->state}' → '{$state->name}'");
            } else {
                $address->update(['state' => $state->name]);
            }

            $converted++;
        }

        $this->newLine();
        $this->info("Results: {$converted} converted, {$skipped} already correct, {$notFound} not found.");

        if ($dryRun && $converted > 0) {
            $this->newLine();
            $this->info('Run without --dry-run to apply changes.');
        }

        return Command::SUCCESS;
    }
}

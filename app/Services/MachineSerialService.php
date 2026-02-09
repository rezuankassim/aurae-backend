<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Machine;

class MachineSerialService
{
    /**
     * Validate serial number format against configured pattern.
     */
    public function validateFormat(string $serialNumber): bool
    {
        $pattern = $this->getValidationPattern();

        return preg_match($pattern, $serialNumber) === 1;
    }

    /**
     * Generate next serial number based on configured format.
     */
    public function generateNextSerialNumber(): string
    {
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? 'AUR-{NNNN}';

        // Get the last machine to determine next number
        $lastMachine = Machine::orderBy('created_at', 'desc')->first();
        $nextNumber = 1;

        if ($lastMachine) {
            // Extract number from last serial
            preg_match('/\d+/', $lastMachine->serial_number, $matches);
            if (! empty($matches)) {
                $nextNumber = (int) $matches[0] + 1;
            }
        }

        return $this->formatSerialNumber($format, $nextNumber, $settings);
    }

    /**
     * Generate regex pattern for validation.
     */
    protected function getValidationPattern(): string
    {
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? 'AUR-{NNNN}';

        // Replace placeholders with regex patterns
        $pattern = preg_quote($format, '/');
        $pattern = str_replace('\{PREFIX\}', $settings->machine_serial_prefix ?? 'AUR', $pattern);
        $pattern = str_replace('\{YYYY\}', '\d{4}', $pattern);
        $pattern = str_replace('\{MM\}', '\d{2}', $pattern);
        $pattern = str_replace('\{NNNN\}', '\d{4}', $pattern);
        $pattern = str_replace('\{NNNNN\}', '\d{5}', $pattern);
        $pattern = str_replace('\{NNN\}', '\d{3}', $pattern);
        $pattern = str_replace('\{NN\}', '\d{2}', $pattern);

        return '/^'.$pattern.'$/';
    }

    /**
     * Format serial number based on pattern.
     */
    protected function formatSerialNumber(string $format, int $number, ?GeneralSetting $settings): string
    {
        $formatted = $format;

        // Replace placeholders
        if ($settings && $settings->machine_serial_prefix) {
            $formatted = str_replace('{PREFIX}', $settings->machine_serial_prefix, $formatted);
        }

        $formatted = str_replace('{YYYY}', date('Y'), $formatted);
        $formatted = str_replace('{MM}', date('m'), $formatted);

        // Replace number placeholders with appropriate padding
        if (str_contains($formatted, '{NNNNN}')) {
            $formatted = str_replace('{NNNNN}', str_pad($number, 5, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{NNNN}')) {
            $formatted = str_replace('{NNNN}', str_pad($number, 4, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{NNN}')) {
            $formatted = str_replace('{NNN}', str_pad($number, 3, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{NN}')) {
            $formatted = str_replace('{NN}', str_pad($number, 2, '0', STR_PAD_LEFT), $formatted);
        }

        return $formatted;
    }

    /**
     * Get example format for user feedback.
     */
    public function getFormatExample(): string
    {
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? 'AUR-{NNNN}';

        return $this->formatSerialNumber($format, 1, $settings);
    }

    /**
     * Bulk generate machines with auto-serial numbers.
     */
    public function bulkGenerate(int $quantity, string $baseName): array
    {
        $machines = [];
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? 'AUR-{NNNN}';

        // Get starting number
        $lastMachine = Machine::orderBy('created_at', 'desc')->first();
        $startNumber = 1;

        if ($lastMachine) {
            preg_match('/\d+/', $lastMachine->serial_number, $matches);
            if (! empty($matches)) {
                $startNumber = (int) $matches[0] + 1;
            }
        }

        for ($i = 0; $i < $quantity; $i++) {
            $serialNumber = $this->formatSerialNumber($format, $startNumber + $i, $settings);

            $machines[] = Machine::create([
                'serial_number' => $serialNumber,
                'name' => $baseName.' #'.($i + 1),
                'status' => 1,
            ]);
        }

        return $machines;
    }

    /**
     * Parse format pattern into components.
     */
    public function parseFormat(string $format): array
    {
        return [
            'has_prefix' => str_contains($format, '{PREFIX}'),
            'has_year' => str_contains($format, '{YYYY}'),
            'has_month' => str_contains($format, '{MM}'),
            'number_length' => $this->getNumberLength($format),
            'pattern' => $this->getValidationPattern(),
        ];
    }

    /**
     * Get number length from format.
     */
    protected function getNumberLength(string $format): int
    {
        if (str_contains($format, '{NNNNN}')) {
            return 5;
        }
        if (str_contains($format, '{NNNN}')) {
            return 4;
        }
        if (str_contains($format, '{NNN}')) {
            return 3;
        }
        if (str_contains($format, '{NN}')) {
            return 2;
        }

        return 4; // default
    }
}

<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Machine;

class MachineSerialService
{
    /**
     * Default format: {MMMM}{YYYY}{SSSS}
     * Example: A10120260001
     * - MMMM: Machine serial prefix/model (3-4 chars, e.g., ABC or A101)
     * - YYYY: Year (4 digits)
     * - SSSS: Product serial (4 digits, zero-padded)
     */
    protected const DEFAULT_FORMAT = '{MMMM}{YYYY}{SSSS}';

    protected const DEFAULT_PREFIX = 'A101';

    /**
     * Validate serial number format against configured pattern.
     */
    public function validateFormat(string $serialNumber): bool
    {
        $pattern = $this->getValidationPattern();

        if (preg_match($pattern, $serialNumber) !== 1) {
            return false;
        }

        return true;
    }

    /**
     * Generate next serial number based on configured format.
     */
    public function generateNextSerialNumber(): string
    {
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? self::DEFAULT_FORMAT;
        $year = date('Y');

        // Get the last machine for the current year to determine next number
        $lastMachine = Machine::where('serial_number', 'like', '%'.$year.'%')
            ->orderBy('created_at', 'desc')
            ->first();

        $nextNumber = 1;

        if ($lastMachine) {
            // Extract the product serial (4 digits after year)
            $productSerial = $this->extractProductSerial($lastMachine->serial_number);
            if ($productSerial !== null) {
                $nextNumber = $productSerial + 1;
            }
        }

        return $this->formatSerialNumber($format, $nextNumber, $settings);
    }

    /**
     * Extract product serial number from a full serial.
     */
    protected function extractProductSerial(string $serialNumber): ?int
    {
        // Format: MODEL(3-4) + YEAR(4) + SERIAL(4)
        if (preg_match('/^[A-Z0-9]{3,4}\d{4}(\d{4})$/', $serialNumber, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    /**
     * Generate regex pattern for validation.
     * Accepts formats like: ABC20260001 or A10120260001
     */
    protected function getValidationPattern(): string
    {
        // Pattern: 3-4 alphanumeric (model) + 4 digits (year) + 4 digits (product code)
        return '/^[A-Z0-9]{3,4}\d{4}\d{4}$/';
    }

    /**
     * Format serial number based on pattern.
     */
    protected function formatSerialNumber(string $format, int $number, ?GeneralSetting $settings): string
    {
        $formatted = $format;
        $prefix = $settings->machine_serial_prefix ?? self::DEFAULT_PREFIX;

        // Replace machine serial prefix
        $formatted = str_replace('{MMMM}', $prefix, $formatted);
        $formatted = str_replace('{PREFIX}', $prefix, $formatted);

        // Replace date placeholders
        $formatted = str_replace('{YYYY}', date('Y'), $formatted);
        $formatted = str_replace('{MM}', date('m'), $formatted);

        // Replace product serial placeholders with appropriate padding
        if (str_contains($formatted, '{SSSSS}')) {
            $formatted = str_replace('{SSSSS}', str_pad($number, 5, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{SSSS}')) {
            $formatted = str_replace('{SSSS}', str_pad($number, 4, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{SSS}')) {
            $formatted = str_replace('{SSS}', str_pad($number, 3, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{SS}')) {
            $formatted = str_replace('{SS}', str_pad($number, 2, '0', STR_PAD_LEFT), $formatted);
        }

        // Legacy support for {NNNN} format
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
        $format = $settings->machine_serial_format ?? self::DEFAULT_FORMAT;

        return $this->formatSerialNumber($format, 1, $settings);
    }

    /**
     * Bulk generate machines with auto-serial numbers.
     *
     * @param  int  $quantity  Number of machines to generate
     * @param  string  $baseName  Base name for machines
     * @param  string  $model  3-4 character model code (e.g., ABC or A101)
     * @param  string  $year  4-digit year
     * @param  int  $startProductCode  Starting product code (will increment)
     * @param  int  $status  Machine status (0 or 1)
     * @param  string|null  $thumbnail  S3 path for thumbnail image
     * @param  string|null  $detailImage  S3 path for detail image
     */
    public function bulkGenerate(
        int $quantity,
        string $baseName,
        string $model = 'A101',
        ?string $year = null,
        int $startProductCode = 1,
        int $status = 1,
        ?string $thumbnail = null,
        ?string $detailImage = null
    ): array {
        $machines = [];
        $year = $year ?? date('Y');

        for ($i = 0; $i < $quantity; $i++) {
            $productCode = str_pad($startProductCode + $i, 4, '0', STR_PAD_LEFT);
            $serialNumber = "{$model}{$year}{$productCode}";

            $machines[] = Machine::create([
                'serial_number' => $serialNumber,
                'name' => $baseName.' #'.($i + 1),
                'status' => $status,
                'thumbnail' => $thumbnail,
                'detail_image' => $detailImage,
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
            'has_prefix' => str_contains($format, '{MMMM}') || str_contains($format, '{PREFIX}'),
            'has_year' => str_contains($format, '{YYYY}'),
            'has_month' => str_contains($format, '{MM}'),
            'serial_length' => $this->getSerialLength($format),
            'pattern' => $this->getValidationPattern(),
        ];
    }

    /**
     * Get serial number length from format.
     */
    protected function getSerialLength(string $format): int
    {
        if (str_contains($format, '{SSSSS}') || str_contains($format, '{NNNNN}')) {
            return 5;
        }
        if (str_contains($format, '{SSSS}') || str_contains($format, '{NNNN}')) {
            return 4;
        }
        if (str_contains($format, '{SSS}') || str_contains($format, '{NNN}')) {
            return 3;
        }
        if (str_contains($format, '{SS}') || str_contains($format, '{NN}')) {
            return 2;
        }

        return 4; // default
    }

    /**
     * Parse a serial number into its components.
     */
    public function parseSerialNumber(string $serialNumber): array
    {
        // Expected format: MODEL(3-4) + YYYY(4) + SSSS(4)
        if (preg_match('/^([A-Z0-9]{3,4})(\d{4})(\d{4})$/', $serialNumber, $matches) !== 1) {
            return [
                'valid' => false,
                'error' => 'Invalid serial number format',
            ];
        }
        $prefix = $matches[1];
        $year = $matches[2];
        $productSerial = $matches[3];

        return [
            'valid' => true,
            'prefix' => $prefix,
            'model' => $prefix,
            'year' => $year,
            'product_serial' => $productSerial,
            'product_code' => $productSerial,
            'full_serial' => $serialNumber,
        ];
    }
}

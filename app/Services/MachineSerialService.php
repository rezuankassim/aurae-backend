<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Machine;

class MachineSerialService
{
    /**
     * Default format: {MMMM}{YYYY}{SSSS} {V}
     * Example: A10120260001 1
     * - MMMM: Machine serial prefix/model (4 chars, e.g., A101)
     * - YYYY: Year (4 digits)
     * - SSSS: Product serial (4 digits, zero-padded)
     * - V: Variation code (1 digit, separated by space)
     */
    protected const DEFAULT_FORMAT = '{MMMM}{YYYY}{SSSS} {V}';

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

        // Note: {V} is now treated as a user-provided variation code, not a calculated check digit
        // No additional validation needed beyond the regex pattern match

        return true;
    }

    /**
     * Validate the check digit of a serial number.
     */
    public function validateCheckDigit(string $serialNumber): bool
    {
        if (strlen($serialNumber) < 2) {
            return false;
        }

        $baseSerial = substr($serialNumber, 0, -1);
        $providedCheckDigit = (int) substr($serialNumber, -1);
        $calculatedCheckDigit = $this->calculateCheckDigit($baseSerial);

        return $providedCheckDigit === $calculatedCheckDigit;
    }

    /**
     * Calculate check digit using Luhn algorithm variant.
     * This ensures serial number validity can be verified.
     */
    protected function calculateCheckDigit(string $baseSerial): int
    {
        $sum = 0;
        $length = strlen($baseSerial);

        for ($i = 0; $i < $length; $i++) {
            $char = $baseSerial[$i];

            // Convert letters to numbers (A=10, B=11, etc.)
            if (ctype_alpha($char)) {
                $value = ord(strtoupper($char)) - ord('A') + 10;
            } else {
                $value = (int) $char;
            }

            // Apply weight based on position (alternating 1, 2)
            $weight = ($i % 2 === 0) ? 1 : 2;
            $weighted = $value * $weight;

            // Sum the digits if weighted value > 9
            $sum += ($weighted > 9) ? $weighted - 9 : $weighted;
        }

        // Return check digit (0-9)
        return (10 - ($sum % 10)) % 10;
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
        // Format: A101YYYYSSSS V - extract SSSS (positions 8-11, 0-indexed)
        // The serial is: PREFIX(4) + YEAR(4) + SERIAL(4) + SPACE(1) + VARIATION(1) = 14 chars
        // Or without space: PREFIX(4) + YEAR(4) + SERIAL(4) + VARIATION(1) = 13 chars
        $cleanSerial = str_replace(' ', '', $serialNumber);
        if (strlen($cleanSerial) >= 12) {
            $serialPart = substr($cleanSerial, 8, 4);
            if (is_numeric($serialPart)) {
                return (int) $serialPart;
            }
        }

        return null;
    }

    /**
     * Generate regex pattern for validation.
     */
    protected function getValidationPattern(): string
    {
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? self::DEFAULT_FORMAT;
        $prefix = $settings->machine_serial_prefix ?? self::DEFAULT_PREFIX;

        // Replace placeholders with regex patterns
        $pattern = preg_quote($format, '/');
        $pattern = str_replace('\{MMMM\}', '[A-Z0-9]{4}', $pattern); // Allow any 4-char model code
        $pattern = str_replace('\{PREFIX\}', preg_quote($prefix, '/'), $pattern);
        $pattern = str_replace('\{YYYY\}', '\d{4}', $pattern);
        $pattern = str_replace('\{MM\}', '\d{2}', $pattern);
        $pattern = str_replace('\{SSSS\}', '\d{4}', $pattern);
        $pattern = str_replace('\{SSSSS\}', '\d{5}', $pattern);
        $pattern = str_replace('\{SSS\}', '\d{3}', $pattern);
        $pattern = str_replace('\{SS\}', '\d{2}', $pattern);
        $pattern = str_replace('\{V\}', '\d{1}', $pattern);
        // Legacy support
        $pattern = str_replace('\{NNNN\}', '\d{4}', $pattern);
        $pattern = str_replace('\{NNNNN\}', '\d{5}', $pattern);
        $pattern = str_replace('\{NNN\}', '\d{3}', $pattern);
        $pattern = str_replace('\{NN\}', '\d{2}', $pattern);
        // Handle escaped space (from preg_quote)
        $pattern = str_replace('\ ', ' ', $pattern);

        return '/^'.$pattern.'$/';
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

        // Calculate and append validation digit if format includes {V}
        if (str_contains($formatted, '{V}')) {
            $baseSerial = str_replace('{V}', '', $formatted);
            $checkDigit = $this->calculateCheckDigit($baseSerial);
            $formatted = str_replace('{V}', (string) $checkDigit, $formatted);
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
     */
    public function bulkGenerate(int $quantity, string $baseName): array
    {
        $machines = [];
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? self::DEFAULT_FORMAT;
        $year = date('Y');

        // Get starting number for current year
        $lastMachine = Machine::where('serial_number', 'like', '%'.$year.'%')
            ->orderBy('created_at', 'desc')
            ->first();

        $startNumber = 1;

        if ($lastMachine) {
            $productSerial = $this->extractProductSerial($lastMachine->serial_number);
            if ($productSerial !== null) {
                $startNumber = $productSerial + 1;
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
            'has_prefix' => str_contains($format, '{MMMM}') || str_contains($format, '{PREFIX}'),
            'has_year' => str_contains($format, '{YYYY}'),
            'has_month' => str_contains($format, '{MM}'),
            'has_validation' => str_contains($format, '{V}'),
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
        // Expected format: A10120260001 1 (14 chars with space) or A101202600011 (13 chars without space)
        // MMMM(4) + YYYY(4) + SSSS(4) + SPACE?(1) + V(1)
        $cleanSerial = str_replace(' ', '', $serialNumber);
        if (strlen($cleanSerial) !== 13) {
            return [
                'valid' => false,
                'error' => 'Invalid serial number length',
            ];
        }

        $prefix = substr($cleanSerial, 0, 4);
        $year = substr($cleanSerial, 4, 4);
        $productSerial = substr($cleanSerial, 8, 4);
        $variation = substr($cleanSerial, 12, 1);

        return [
            'valid' => true,
            'prefix' => $prefix,
            'model' => $prefix,
            'year' => $year,
            'product_serial' => $productSerial,
            'product_code' => $productSerial,
            'variation_code' => $variation,
            'full_serial' => $serialNumber,
        ];
    }
}

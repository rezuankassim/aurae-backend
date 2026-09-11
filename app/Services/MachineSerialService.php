<?php

namespace App\Services;

use App\Models\GeneralSetting;
use App\Models\Machine;
use Illuminate\Database\QueryException;

class MachineSerialService
{
    protected const DEFAULT_FORMAT = '{MMMM}{YYYY}{SSSS}';

    protected const DEFAULT_PREFIX = 'A101';

    public function validateFormat(string $serialNumber): bool
    {
        $pattern = $this->getValidationPattern();

        if (preg_match($pattern, $serialNumber) !== 1) {
            return false;
        }

        return true;
    }

    public function generateNextSerialNumber(): string
    {
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? self::DEFAULT_FORMAT;
        $prefix = strtoupper($settings->machine_serial_prefix ?? self::DEFAULT_PREFIX);
        $year = date('Y');
        $nextNumber = $this->getHighestProductSerial($prefix, $year) + 1;

        return $this->formatSerialNumber($format, $nextNumber, $settings);
    }

    public function createMachineWithAutoSerial(array $machineData, int $maxAttempts = 5): Machine
    {
        $attempt = 0;

        while ($attempt < $maxAttempts) {
            $machineData['serial_number'] = $this->generateNextSerialNumber();

            try {
                return Machine::create($machineData);
            } catch (QueryException $e) {
                if (! $this->isSerialNumberDuplicateException($e)) {
                    throw $e;
                }

                $attempt++;
            }
        }

        throw new \RuntimeException('Unable to allocate a unique machine serial number. Please try again.');
    }

    protected function extractProductSerial(string $serialNumber): ?int
    {

        if (preg_match('/^[A-Z0-9]{3,4}\d{4}(\d{4})$/', $serialNumber, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }

    protected function getHighestProductSerial(string $prefix, string $year): int
    {
        $serialPrefix = strtoupper($prefix.$year);

        return Machine::query()
            ->whereRaw('UPPER(serial_number) LIKE ?', [$serialPrefix.'%'])
            ->pluck('serial_number')
            ->map(function ($serialNumber) {
                return $this->extractProductSerial(strtoupper((string) $serialNumber));
            })
            ->filter(fn ($productSerial) => $productSerial !== null)
            ->max() ?? 0;
    }

    protected function getValidationPattern(): string
    {

        return '/^[A-Z0-9]{3,4}\d{4}\d{4}$/';
    }

    protected function formatSerialNumber(string $format, int $number, ?GeneralSetting $settings): string
    {
        $formatted = $format;
        $prefix = $settings->machine_serial_prefix ?? self::DEFAULT_PREFIX;

        $formatted = str_replace('{MMMM}', $prefix, $formatted);
        $formatted = str_replace('{PREFIX}', $prefix, $formatted);

        $formatted = str_replace('{YYYY}', date('Y'), $formatted);
        $formatted = str_replace('{MM}', date('m'), $formatted);

        if (str_contains($formatted, '{SSSSS}')) {
            $formatted = str_replace('{SSSSS}', str_pad($number, 5, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{SSSS}')) {
            $formatted = str_replace('{SSSS}', str_pad($number, 4, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{SSS}')) {
            $formatted = str_replace('{SSS}', str_pad($number, 3, '0', STR_PAD_LEFT), $formatted);
        } elseif (str_contains($formatted, '{SS}')) {
            $formatted = str_replace('{SS}', str_pad($number, 2, '0', STR_PAD_LEFT), $formatted);
        }

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

    public function getFormatExample(): string
    {
        $settings = GeneralSetting::first();
        $format = $settings->machine_serial_format ?? self::DEFAULT_FORMAT;

        return $this->formatSerialNumber($format, 1, $settings);
    }

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
        $model = strtoupper($model);
        $year = $year ?? date('Y');
        $currentProductCode = $startProductCode;

        for ($i = 0; $i < $quantity; $i++) {
            $attempt = 0;
            $maxAttempts = 10000;

            while (true) {
                $productCode = str_pad($currentProductCode, 4, '0', STR_PAD_LEFT);
                $serialNumber = "{$model}{$year}{$productCode}";

                try {
                    $machines[] = Machine::create([
                        'serial_number' => $serialNumber,
                        'name' => $baseName.' #'.($i + 1),
                        'status' => $status,
                        'thumbnail' => $thumbnail,
                        'detail_image' => $detailImage,
                    ]);

                    $currentProductCode++;
                    break;
                } catch (QueryException $e) {
                    if (! $this->isSerialNumberDuplicateException($e)) {
                        throw $e;
                    }

                    $attempt++;
                    $currentProductCode++;

                    if ($attempt >= $maxAttempts) {
                        throw new \RuntimeException('Unable to generate unique machine serial numbers for the requested quantity.');
                    }
                }
            }
        }

        return $machines;
    }

    protected function isSerialNumberDuplicateException(QueryException $e): bool
    {
        $errorCode = (int) ($e->errorInfo[1] ?? 0);
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        $message = strtolower($e->getMessage());

        return in_array($sqlState, ['23000', '23505'], true)
            || in_array($errorCode, [19, 1062], true)
            || str_contains($message, 'machines_serial_number_unique')
            || str_contains($message, 'machines.serial_number');
    }

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

        return 4;
    }

    public function parseSerialNumber(string $serialNumber): array
    {

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

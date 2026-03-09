<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Repositories\ImportRowRepository;
use App\Modules\Import\Repositories\ImportRunRepository;
use App\Modules\Import\Repositories\SupplierItemRepository;
use RuntimeException;

final class CsvImportService
{
    public function __construct(
        private readonly ImportRunRepository $runs,
        private readonly ImportRowRepository $rows,
        private readonly SupplierItemRepository $supplierItems,
        private readonly ImportProfileService $profiles
    ) {
    }

    public function import(int $profileId, array $upload): int
    {
        $profile = $this->profiles->getWithSupplier($profileId);
        if ($profile === null) {
            throw new RuntimeException('Importprofil saknas.');
        }

        if (($profile['file_type'] ?? 'csv') !== 'csv') {
            throw new RuntimeException('Endast CSV stöds i v1-import.');
        }

        $storedPath = $this->storeUploadedFile($upload);
        $runId = $this->runs->create((int) $profile['supplier_id'], (int) $profile['id'], basename($storedPath));

        try {
            $this->processCsv($runId, $storedPath, $profile);
            $this->runs->markCompleted($runId);
        } catch (\Throwable $exception) {
            $this->runs->markFailed($runId);
            throw $exception;
        }

        return $runId;
    }

    /** @param array<string, mixed> $upload */
    private function storeUploadedFile(array $upload): string
    {
        $tmpName = (string) ($upload['tmp_name'] ?? '');
        $originalName = basename((string) ($upload['name'] ?? 'import.csv'));

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            throw new RuntimeException('Ingen giltig fil uppladdad.');
        }

        $safeName = date('Ymd_His') . '_' . preg_replace('/[^a-zA-Z0-9._-]+/', '_', $originalName);
        $targetDir = dirname(__DIR__, 4) . '/storage/imports';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Kunde inte skapa lagringsmapp för import.');
        }

        $targetPath = $targetDir . '/' . $safeName;
        if (!move_uploaded_file($tmpName, $targetPath)) {
            throw new RuntimeException('Kunde inte spara uppladdad fil.');
        }

        return $targetPath;
    }

    /** @param array<string, mixed> $profile */
    private function processCsv(int $runId, string $path, array $profile): void
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Kunde inte läsa importerad CSV-fil.');
        }

        $delimiter = (string) ($profile['delimiter'] ?? ',');
        $enclosure = (string) (($profile['enclosure'] ?? '"') ?: '"');
        $escape = (string) (($profile['escape_char'] ?? '\\') ?: '\\');

        $headers = fgetcsv($handle, 0, $delimiter, $enclosure, $escape);
        if ($headers === false) {
            fclose($handle);
            return;
        }

        $mapping = json_decode((string) ($profile['column_mapping_json'] ?? '{}'), true);
        if (!is_array($mapping)) {
            $mapping = [];
        }

        $rowNumber = 1;
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure, $escape)) !== false) {
            $rowNumber++;
            $rawRow = $this->combineRow($headers, $row);

            try {
                $mappedRow = $this->mapRow($rawRow, $mapping);
                if (($mappedRow['supplier_sku'] ?? '') === '') {
                    throw new RuntimeException('Obligatoriskt fält supplier_sku saknas efter mapping.');
                }

                $this->supplierItems->upsertFromImport((int) $profile['supplier_id'], $runId, $mappedRow, $rawRow);
                $this->rows->create($runId, $rowNumber, 'success', $rawRow, $mappedRow, null);
                $this->runs->incrementCounters($runId, true);
            } catch (\Throwable $exception) {
                $this->rows->create($runId, $rowNumber, 'failed', $rawRow, null, $exception->getMessage());
                $this->runs->incrementCounters($runId, false);
            }
        }

        fclose($handle);
    }

    /** @param array<int, string> $headers
     * @param array<int, string> $row
     * @return array<string, string>
     */
    private function combineRow(array $headers, array $row): array
    {
        $combined = [];

        foreach ($headers as $index => $header) {
            $key = trim((string) $header);
            if ($key === '') {
                $key = 'column_' . $index;
            }
            $combined[$key] = isset($row[$index]) ? trim((string) $row[$index]) : '';
        }

        return $combined;
    }

    /** @param array<string, string> $rawRow
     * @param array<string, string> $mapping
     * @return array<string, string>
     */
    private function mapRow(array $rawRow, array $mapping): array
    {
        $mapped = [];

        foreach ($mapping as $internalField => $csvColumn) {
            $column = trim((string) $csvColumn);
            if ($column === '') {
                continue;
            }

            $mapped[(string) $internalField] = $rawRow[$column] ?? '';
        }

        return $mapped;
    }
}

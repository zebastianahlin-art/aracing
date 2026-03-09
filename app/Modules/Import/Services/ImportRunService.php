<?php

declare(strict_types=1);

namespace App\Modules\Import\Services;

use App\Modules\Import\Repositories\ImportRowRepository;
use App\Modules\Import\Repositories\ImportRunRepository;

final class ImportRunService
{
    public function __construct(private readonly ImportRunRepository $runs, private readonly ImportRowRepository $rows)
    {
    }

    /** @return array<int, array<string, mixed>> */
    public function list(): array
    {
        return $this->runs->all();
    }

    /** @return array{run: array<string, mixed>|null, rows: array<int, array<string, mixed>>} */
    public function detail(int $runId): array
    {
        $run = $this->runs->findById($runId);

        return [
            'run' => $run,
            'rows' => $run !== null ? $this->rows->forRun($runId) : [],
        ];
    }
}

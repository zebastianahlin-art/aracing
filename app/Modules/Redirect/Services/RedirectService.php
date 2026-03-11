<?php

declare(strict_types=1);

namespace App\Modules\Redirect\Services;

use App\Modules\Redirect\Repositories\RedirectRepository;
use InvalidArgumentException;
use Throwable;

final class RedirectService
{
    /** @var array<int,int> */
    private const ALLOWED_REDIRECT_TYPES = [301, 302];

    public function __construct(private readonly RedirectRepository $redirects)
    {
    }

    /** @return array<int, array<string,mixed>> */
    public function listForAdmin(array $filters): array
    {
        $active = (string) ($filters['is_active'] ?? '');
        $activeFilter = null;

        if ($active === '1' || $active === '0') {
            $activeFilter = (int) $active;
        }

        return $this->redirects->listForAdmin($activeFilter);
    }

    /** @return array<string,mixed>|null */
    public function getById(int $id): ?array
    {
        return $this->redirects->findById($id);
    }

    public function create(array $input): int
    {
        $data = $this->validateAndNormalize($input, null);

        return $this->redirects->create($data);
    }

    public function update(int $id, array $input): void
    {
        $existing = $this->redirects->findById($id);
        if ($existing === null) {
            throw new InvalidArgumentException('Redirecten kunde inte hittas.');
        }

        $data = $this->validateAndNormalize($input, $id);
        $this->redirects->update($id, $data);
    }

    /** @return array{redirect_id:int,target_path:string,redirect_type:int}|null */
    public function resolveForPath(string $requestPath): ?array
    {
        $normalized = $this->normalizePath($requestPath);
        $redirect = $this->redirects->findActiveBySourcePath($normalized);
        if ($redirect === null) {
            return null;
        }

        $targetPath = $this->normalizePath((string) $redirect['target_path']);
        if ($normalized === $targetPath) {
            return null;
        }

        $next = $this->redirects->findActiveBySourcePath($targetPath);
        if ($next !== null && $this->normalizePath((string) $next['target_path']) === $normalized) {
            return null;
        }

        return [
            'redirect_id' => (int) $redirect['id'],
            'target_path' => $targetPath,
            'redirect_type' => (int) $redirect['redirect_type'],
        ];
    }

    public function registerHit(int $redirectId): void
    {
        try {
            $this->redirects->recordHit($redirectId);
        } catch (Throwable) {
            // Hit count får inte stoppa redirect-flödet.
        }
    }

    public function createSlugChangeRedirect(string $oldPath, string $newPath, string $notes): void
    {
        $sourcePath = $this->normalizePath($oldPath);
        $targetPath = $this->normalizePath($newPath);

        if ($sourcePath === $targetPath) {
            return;
        }

        $existing = $this->redirects->findBySourcePath($sourcePath);
        if ($existing !== null) {
            $this->redirects->update((int) $existing['id'], [
                'source_path' => $sourcePath,
                'target_path' => $targetPath,
                'redirect_type' => 301,
                'is_active' => 1,
                'notes' => $notes,
            ]);

            return;
        }

        $this->redirects->create([
            'source_path' => $sourcePath,
            'target_path' => $targetPath,
            'redirect_type' => 301,
            'is_active' => 1,
            'notes' => $notes,
        ]);
    }

    public function normalizePath(string $path): string
    {
        $trimmed = trim($path);
        if ($trimmed === '') {
            return '/';
        }

        $parsedPath = parse_url($trimmed, PHP_URL_PATH);
        $normalized = is_string($parsedPath) ? $parsedPath : $trimmed;
        $normalized = '/' . ltrim($normalized, '/');

        if ($normalized !== '/') {
            $normalized = rtrim($normalized, '/');
            if ($normalized === '') {
                $normalized = '/';
            }
        }

        return $normalized;
    }

    private function containsExternalUrlParts(string $path): bool
    {
        $parts = parse_url($path);

        return is_array($parts) && (isset($parts['scheme']) || isset($parts['host']));
    }

    /** @return array{source_path:string,target_path:string,redirect_type:int,is_active:int,notes:?string} */
    private function validateAndNormalize(array $input, ?int $ignoreId): array
    {
        $rawSourcePath = trim((string) ($input['source_path'] ?? ''));
        $rawTargetPath = trim((string) ($input['target_path'] ?? ''));

        if ($this->containsExternalUrlParts($rawSourcePath) || $this->containsExternalUrlParts($rawTargetPath)) {
            throw new InvalidArgumentException('Ange endast interna paths, utan domän.');
        }

        $sourcePath = $this->normalizePath($rawSourcePath);
        $targetPath = $this->normalizePath($rawTargetPath);
        $redirectType = (int) ($input['redirect_type'] ?? 301);
        $isActive = isset($input['is_active']) ? 1 : 0;
        $notes = trim((string) ($input['notes'] ?? ''));


        if ($sourcePath === $targetPath) {
            throw new InvalidArgumentException('Källsökväg och målsökväg får inte vara identiska.');
        }

        if (!in_array($redirectType, self::ALLOWED_REDIRECT_TYPES, true)) {
            throw new InvalidArgumentException('Endast redirect-typ 301 eller 302 stöds.');
        }

        $existingSource = $this->redirects->findBySourcePath($sourcePath);
        if ($existingSource !== null && (int) $existingSource['id'] !== $ignoreId) {
            throw new InvalidArgumentException('Källsökvägen används redan av en annan redirect.');
        }

        $reverse = $this->redirects->findActiveBySourcePath($targetPath);
        if ($reverse !== null && $this->normalizePath((string) $reverse['target_path']) === $sourcePath) {
            throw new InvalidArgumentException('Redirecten skapar en enkel loop och kan inte sparas.');
        }

        return [
            'source_path' => $sourcePath,
            'target_path' => $targetPath,
            'redirect_type' => $redirectType,
            'is_active' => $isActive,
            'notes' => $notes !== '' ? $notes : null,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Import\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Import\Services\AiProductDraftHandoffService;
use App\Modules\Import\Services\AiProductImportService;
use InvalidArgumentException;

final class AiProductImportAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiProductImportService $imports,
        private readonly AiProductDraftHandoffService $handoff,
    ) {
    }

    public function index(): Response
    {
        $payload = $this->imports->listDrafts($_GET);

        return new Response($this->views->render('admin.ai_product_import.index', [
            'rows' => $payload['rows'],
            'filters' => $payload['filters'],
            'status_options' => $payload['status_options'],
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function importFromUrl(): Response
    {
        try {
            $result = $this->imports->createDraftFromUrl((string) ($_POST['source_url'] ?? ''));
            $message = $result['status'] === 'failed'
                ? 'Utkast sparades med status failed: ' . (string) ($result['error'] ?? 'Okänt fel')
                : 'URL importerad till granskningsutkast.';

            return $this->redirect('/admin/ai-product-import/' . (int) $result['draft_id'] . '?message=' . urlencode($message));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/ai-product-import?error=' . urlencode($e->getMessage()));
        }
    }

    public function show(string $id): Response
    {
        $draft = $this->imports->getDraft((int) $id);

        if ($draft === null) {
            return $this->redirect('/admin/ai-product-import?error=' . urlencode('Utkastet hittades inte.'));
        }

        return new Response($this->views->render('admin.ai_product_import.show', [
            'draft' => $draft,
            'message' => trim((string) ($_GET['message'] ?? '')),
            'error' => trim((string) ($_GET['error'] ?? '')),
        ]));
    }

    public function markReviewed(string $id): Response
    {
        try {
            $this->imports->markReviewed((int) $id, null, (string) ($_POST['review_note'] ?? ''));

            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?message=' . urlencode('Utkast markerat som reviewed.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function reject(string $id): Response
    {
        try {
            $this->imports->markRejected((int) $id, null, (string) ($_POST['review_note'] ?? ''));

            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?message=' . urlencode('Utkast markerat som rejected.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function markImported(string $id): Response
    {
        try {
            $this->imports->markImported((int) $id, null, (string) ($_POST['review_note'] ?? ''));

            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?message=' . urlencode('Utkast markerat som imported.')); 
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    public function handoffToProductDraft(string $id): Response
    {
        try {
            $result = $this->handoff->handoffToProductDraft((int) $id, null);

            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?message=' . urlencode('Handoff klar: produktutkast #' . (int) $result['product_id'] . ' skapades och ligger nu i artikelvårdskön.'));
        } catch (InvalidArgumentException $e) {
            return $this->redirect('/admin/ai-product-import/' . (int) $id . '?error=' . urlencode($e->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}

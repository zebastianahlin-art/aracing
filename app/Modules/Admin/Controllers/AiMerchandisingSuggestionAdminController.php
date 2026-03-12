<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Admin\Services\AiMerchandisingSuggestionService;

final class AiMerchandisingSuggestionAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiMerchandisingSuggestionService $suggestions,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.ai_merch_suggestions.index', [
            'suggestions' => $this->suggestions->listSuggestions(),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function show(int $id): Response
    {
        $suggestion = $this->suggestions->findSuggestion($id);
        if ($suggestion === null) {
            return new Response('Förslag hittades inte.', 404, ['Content-Type' => 'text/plain; charset=UTF-8']);
        }

        return new Response($this->views->render('admin.ai_merch_suggestions.show', [
            'suggestion' => $suggestion,
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function generate(): Response
    {
        $result = $this->suggestions->buildSuggestions();
        $message = sprintf('Generering klar. Skapade: %d, hoppade över: %d.', (int) $result['created'], (int) $result['skipped']);

        return $this->redirect('/admin/ai-merch-suggestions?message=' . urlencode($message));
    }

    public function approve(int $id): Response
    {
        try {
            $this->suggestions->approveSuggestion($id, null);
            return $this->redirect('/admin/ai-merch-suggestions/' . $id . '?message=' . urlencode('Förslag godkänt och homepage draft skapad.'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/ai-merch-suggestions/' . $id . '?message=' . urlencode($exception->getMessage()));
        }
    }

    public function reject(int $id): Response
    {
        try {
            $this->suggestions->rejectSuggestion($id, null);
            return $this->redirect('/admin/ai-merch-suggestions/' . $id . '?message=' . urlencode('Förslag avvisat.'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/ai-merch-suggestions/' . $id . '?message=' . urlencode($exception->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;
use App\Modules\Admin\Services\AiSearchInsightService;

final class AiSearchInsightAdminController
{
    public function __construct(
        private readonly ViewFactory $views,
        private readonly AiSearchInsightService $insights,
    ) {
    }

    public function index(): Response
    {
        return new Response($this->views->render('admin.ai_search_insights', [
            'payload' => $this->insights->insights(),
            'message' => trim((string) ($_GET['message'] ?? '')),
        ]));
    }

    public function generate(): Response
    {
        $result = $this->insights->generateSuggestions();
        $message = sprintf('Generering klar. Skapade %d förslag, hoppade över %d dubbletter.', (int) $result['created'], (int) $result['skipped']);

        return $this->redirect('/admin/ai-search-insights?message=' . urlencode($message));
    }

    public function approve(int $id): Response
    {
        try {
            $this->insights->approveSuggestion($id, null);
            return $this->redirect('/admin/ai-search-insights?message=' . urlencode('Förslag godkänt och alias aktivt.'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/ai-search-insights?message=' . urlencode($exception->getMessage()));
        }
    }

    public function reject(int $id): Response
    {
        try {
            $this->insights->rejectSuggestion($id, null);
            return $this->redirect('/admin/ai-search-insights?message=' . urlencode('Förslag avvisat.'));
        } catch (\Throwable $exception) {
            return $this->redirect('/admin/ai-search-insights?message=' . urlencode($exception->getMessage()));
        }
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}

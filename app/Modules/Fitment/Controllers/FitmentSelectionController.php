<?php

declare(strict_types=1);

namespace App\Modules\Fitment\Controllers;

use App\Core\Http\Response;
use App\Modules\Fitment\Services\FitmentService;

final class FitmentSelectionController
{
    public function __construct(private readonly FitmentService $fitment)
    {
    }

    public function select(): Response
    {
        $ok = $this->fitment->selectVehicleFromInput($_POST);

        return $this->redirect($this->backUrl($ok ? 'Bil vald för katalogfiltrering.' : 'Kunde inte välja bil. Kontrollera valen.'));
    }

    public function clear(): Response
    {
        $this->fitment->clearSelectedVehicle();

        return $this->redirect($this->backUrl('Vald bil rensad.'));
    }

    private function backUrl(string $message): string
    {
        $returnTo = trim((string) ($_POST['return_to'] ?? '/search'));
        if ($returnTo === '' || $returnTo[0] !== '/') {
            $returnTo = '/search';
        }

        $glue = str_contains($returnTo, '?') ? '&' : '?';

        return $returnTo . $glue . 'fitment_notice=' . urlencode($message);
    }

    private function redirect(string $location): Response
    {
        return new Response('', 302, ['Location' => $location, 'Content-Type' => 'text/html; charset=UTF-8']);
    }
}

<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Core\Http\Response;
use App\Core\View\ViewFactory;

final class AdminController
{
    public function __construct(private readonly ViewFactory $views)
    {
    }

    public function dashboard(): Response
    {
        return new Response($this->views->render('admin.dashboard'));
    }
}

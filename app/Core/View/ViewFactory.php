<?php

declare(strict_types=1);

namespace App\Core\View;

final class ViewFactory
{
    public function __construct(private readonly string $basePath)
    {
    }

    public function render(string $view, array $data = []): string
    {
        $viewPath = $this->basePath . '/' . str_replace('.', '/', $view) . '.php';

        if (!is_file($viewPath)) {
            throw new \RuntimeException(sprintf('View "%s" saknas.', $view));
        }

        extract($data, EXTR_SKIP);

        ob_start();
        require $viewPath;

        return (string) ob_get_clean();
    }
}

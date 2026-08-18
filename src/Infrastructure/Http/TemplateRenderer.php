<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

final class TemplateRenderer
{
    public function __construct(
        private readonly string $templateDirectory,
        private readonly array $shared = [],
    ) {
    }

    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->templateDirectory . '/' . $template;
        $layoutPath = $this->templateDirectory . '/layouts/main.php';
        $variables = $this->shared + $data;

        extract($variables, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $content = (string) ob_get_clean();

        extract($variables + ['content' => $content], EXTR_SKIP);
        ob_start();
        require $layoutPath;

        return (string) ob_get_clean();
    }
}

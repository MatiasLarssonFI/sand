<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use RuntimeException;

final class TemplateRenderer
{
    public function __construct(
        private readonly string $templateDirectory,
        private readonly array $shared = [],
    ) {
    }

    public function render(string $template, array $data = []): string
    {
        $templatePath = $this->resolvePath($template);
        $layoutPath = $this->resolvePath('layouts/main.php');
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

    private function resolvePath(string $template): string
    {
        $baseDirectory = realpath($this->templateDirectory);
        $resolvedPath = realpath($this->templateDirectory . '/' . ltrim($template, '/'));

        if ($baseDirectory === false || $resolvedPath === false || !str_starts_with($resolvedPath, $baseDirectory . DIRECTORY_SEPARATOR)) {
            throw new RuntimeException('Invalid template path.');
        }

        return $resolvedPath;
    }
}

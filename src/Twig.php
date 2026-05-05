<?php

namespace Cards;

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Twig
{
    private static ?Environment $instance = null;

    public static function getInstance(): Environment
    {
        if (self::$instance === null) {
            $loader = new FilesystemLoader($_ENV['TWIG_PATH']);
            self::$instance = new Environment($loader, [
                'cache' => false,
                'debug' => ($_ENV['APP_ENV'] ?? 'production') !== 'production',
            ]);
        }
        return self::$instance;
    }

    public static function render(string $template, array $context = []): string
    {
        return self::getInstance()->render($template, $context);
    }
}

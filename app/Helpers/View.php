<?php
namespace App\Helpers;

/**
 * Template Renderer / View Engine
 * Supports layouts, sections, partials, and variable passing
 */
class View
{
    private static array $sections = [];
    private static string $currentSection = '';
    private static ?string $layout = null;
    private static array $sharedData = [];

    /**
     * Render a view file
     */
    public static function render(string $view, array $data = [], ?string $layout = 'layouts/main'): void
    {
        self::$layout = $layout;
        self::$sections = [];

        // Merge shared data
        $data = array_merge(self::$sharedData, $data);

        // Extract data to local variables
        extract($data);

        // Build view file path
        $viewPath = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';

        if (!file_exists($viewPath)) {
            throw new \RuntimeException("View not found: {$view} ({$viewPath})");
        }

        // Capture the view content
        ob_start();
        include $viewPath;
        $content = ob_get_clean();

        // If no layout, output directly
        if (self::$layout === null) {
            echo $content;
            return;
        }

        // Store the content as 'content' section if not already set
        if (!isset(self::$sections['content'])) {
            self::$sections['content'] = $content;
        }

        // Render the layout
        $layoutPath = APP_PATH . '/Views/' . self::$layout . '.php';
        if (!file_exists($layoutPath)) {
            throw new \RuntimeException("Layout not found: " . self::$layout);
        }

        include $layoutPath;
    }

    /**
     * Render a view without layout (for AJAX, partials, etc.)
     */
    public static function partial(string $view, array $data = []): void
    {
        self::render($view, $data, null);
    }

    /**
     * Render and return as string
     */
    public static function renderToString(string $view, array $data = []): string
    {
        ob_start();
        self::render($view, $data, null);
        return ob_get_clean();
    }

    /**
     * Start a section
     */
    public static function startSection(string $name): void
    {
        self::$currentSection = $name;
        ob_start();
    }

    /**
     * End a section
     */
    public static function endSection(): void
    {
        if (self::$currentSection) {
            self::$sections[self::$currentSection] = ob_get_clean();
            self::$currentSection = '';
        }
    }

    /**
     * Yield a section's content
     */
    public static function section(string $name, string $default = ''): string
    {
        return self::$sections[$name] ?? $default;
    }

    /**
     * Include a partial view
     */
    public static function include(string $view, array $data = []): void
    {
        $data = array_merge(self::$sharedData, $data);
        extract($data);

        $viewPath = APP_PATH . '/Views/' . str_replace('.', '/', $view) . '.php';
        if (file_exists($viewPath)) {
            include $viewPath;
        }
    }

    /**
     * Share data with all views
     */
    public static function share(string $key, mixed $value): void
    {
        self::$sharedData[$key] = $value;
    }

    /**
     * Escape HTML output
     */
    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate asset URL
     */
    public static function asset(string $path): string
    {
        $baseUrl = $GLOBALS['appConfig']['url'] ?? '';
        return $baseUrl . '/assets/' . ltrim($path, '/');
    }

    /**
     * Generate URL
     */
    public static function url(string $path = ''): string
    {
        $baseUrl = $GLOBALS['appConfig']['url'] ?? '';
        return $baseUrl . '/' . ltrim($path, '/');
    }

    /**
     * Format date
     */
    public static function date(?string $date, string $format = 'M d, Y'): string
    {
        if (!$date) return '-';
        return date($format, strtotime($date));
    }

    /**
     * Format date as relative time
     */
    public static function timeAgo(?string $datetime): string
    {
        if (!$datetime) return '-';
        
        $now = time();
        $time = strtotime($datetime);
        $diff = $now - $time;

        if ($diff < 60) return 'Just now';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        
        return date('M d, Y', $time);
    }

    /**
     * Get priority badge HTML
     */
    public static function priorityBadge(string $priority): string
    {
        $colors = PRIORITY_COLORS;
        $color = $colors[$priority] ?? 'secondary';
        $label = strtoupper($priority);
        return "<span class=\"badge bg-{$color}\">{$label}</span>";
    }

    /**
     * Get status badge HTML
     */
    public static function statusBadge(string $status): string
    {
        $colors = STATUS_COLORS;
        $color = $colors[$status] ?? 'secondary';
        $label = ucwords(str_replace('_', ' ', $status));
        return "<span class=\"badge bg-{$color}\">{$label}</span>";
    }

    /**
     * Get status dot HTML
     */
    public static function statusDot(string $status): string
    {
        $colors = [
            'new' => '#17a2b8',
            'assigned' => '#0d6efd',
            'in_progress' => '#ffc107',
            'waiting_user' => '#6c757d',
            'waiting_vendor' => '#6c757d',
            'resolved' => '#198754',
            'closed' => '#343a40',
            'active' => '#198754',
            'inactive' => '#dc3545',
            'maintenance' => '#ffc107',
        ];
        $color = $colors[$status] ?? '#6c757d';
        $label = ucwords(str_replace('_', ' ', $status));
        return "<span class=\"status-dot\" style=\"background-color:{$color}\"></span> {$label}";
    }
}

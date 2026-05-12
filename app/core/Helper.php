<?php

class Helper
{
    public const ITEMS_PER_PAGE = 10;

    /**
     * Vygeneruje HTML navigačnú lištu pre stránkovanie.
     *
     * @param int   $page       Aktuálna strana (1-based)
     * @param int   $total      Celkový počet záznamov
     * @param int   $perPage    Počet položiek na stranu
     * @param array $params     Existujúce GET parametre, ktoré sa zachovajú v URL (?stav=, ?search=…)
     */
    public static function paginator(int $page, int $total, int $perPage, array $params = []): string
    {
        if ($total <= $perPage) {
            return '';
        }

        $totalPages = (int)ceil($total / $perPage);

        // URL builder — zachová existujúce filtre a nastaví ?page=X
        $url = static function(int $p) use ($params): string {
            return '?' . http_build_query(array_merge($params, ['page' => $p]));
        };

        // Rozsah stránok: vždy prvá a posledná + okno ±2 okolo aktuálnej
        $inRange = [];
        for ($i = 1; $i <= $totalPages; $i++) {
            if ($i === 1 || $i === $totalPages || ($i >= $page - 2 && $i <= $page + 2)) {
                $inRange[] = $i;
            }
        }

        // Vloženie null = „…" tam, kde je medzera väčšia ako 1
        $window = [];
        $prev   = 0;
        foreach ($inRange as $p) {
            if ($prev && $p - $prev > 1) {
                $window[] = null;
            }
            $window[] = $p;
            $prev = $p;
        }

        // ── HTML ──────────────────────────────────────────────────────────────
        $btnBase     = 'display:inline-flex;align-items:center;justify-content:center;'
                     . 'min-width:2rem;height:2rem;padding:0 .5rem;border-radius:6px;'
                     . 'font-size:.8rem;font-weight:500;text-decoration:none;border:1px solid transparent;';
        $btnGhost    = $btnBase . 'color:var(--gray-600);border-color:var(--gray-200);background:#fff;';
        $btnActive   = $btnBase . 'background:var(--primary);color:#fff;border-color:var(--primary);cursor:default;';
        $btnDisabled = $btnBase . 'color:var(--gray-300);border-color:var(--gray-100);background:#fff;cursor:default;';

        $html = '<div style="display:flex;align-items:center;gap:.3rem;flex-wrap:wrap;">';

        // Predchádzajúca
        if ($page > 1) {
            $html .= '<a href="' . $url($page - 1) . '" style="' . $btnGhost . '">← Predch.</a>';
        } else {
            $html .= '<span style="' . $btnDisabled . '">← Predch.</span>';
        }

        // Čísla stránok
        foreach ($window as $p) {
            if ($p === null) {
                $html .= '<span style="padding:0 .2rem;color:var(--gray-400);font-size:.85rem;">…</span>';
            } elseif ($p === $page) {
                $html .= '<span style="' . $btnActive . '">' . $p . '</span>';
            } else {
                $html .= '<a href="' . $url($p) . '" style="' . $btnGhost . '">' . $p . '</a>';
            }
        }

        // Nasledujúca
        if ($page < $totalPages) {
            $html .= '<a href="' . $url($page + 1) . '" style="' . $btnGhost . '">Nasl. →</a>';
        } else {
            $html .= '<span style="' . $btnDisabled . '">Nasl. →</span>';
        }

        $html .= '</div>';
        return $html;
    }


    public static function log(string $message): void
    {
        $dir = __DIR__ . '/../../storage';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        file_put_contents(
            $dir . '/err.log',
            '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL,
            FILE_APPEND
        );
    }

    public static function h(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }

    public static function eur(float $amount): string
    {
        return number_format($amount, 2, ',', ' ') . ' €';
    }

    public static function stavBadge(string $stav): string
    {
        return match ($stav) {
            'nova'       => '<span class="badge badge-info"><span class="status-dot" style="background:var(--info);"></span>Nová</span>',
            'pripravuje' => '<span class="badge badge-warning"><span class="status-dot" style="background:var(--warning);"></span>Pripravuje sa</span>',
            'ceste'      => '<span class="badge" style="background:#F3E8FF;color:#7C3AED;"><span class="status-dot" style="background:#7C3AED;"></span>Na ceste</span>',
            'dorucena'   => '<span class="badge badge-success"><span class="status-dot" style="background:var(--success);"></span>Doručená</span>',
            'zrusena'    => '<span class="badge badge-danger"><span class="status-dot" style="background:var(--danger);"></span>Zrušená</span>',
            default      => '<span class="badge">' . self::h($stav) . '</span>',
        };
    }

    public static function flash(string $type, string $msg): void
    {
        $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
    }

    public static function renderFlash(): string
    {
        if (empty($_SESSION['flash'])) {
            return '';
        }
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);

        $bg = match ($f['type']) {
            'success' => 'rgba(16,185,129,.12)',
            'danger'  => 'rgba(239,68,68,.12)',
            default   => 'rgba(99,102,241,.12)',
        };
        $color = match ($f['type']) {
            'success' => '#059669',
            'danger'  => '#dc2626',
            default   => '#4F46E5',
        };
        return '<div style="padding:.75rem 1rem;margin:0 0 1.25rem;border-radius:10px;background:' . $bg . ';color:' . $color . ';font-size:.875rem;">'
            . self::h($f['msg']) . '</div>';
    }
}

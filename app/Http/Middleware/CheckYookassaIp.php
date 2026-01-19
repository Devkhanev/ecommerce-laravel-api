<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckYookassaIp
{
    /**
     * Этот метод запускается автоматически при входящем запросе
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Проверяем IP (вызываем наш метод)
        if (! $this->isValidIp($request)) {
            // Если IP левый — сразу отказ. Контроллер даже не запустится.
            return response()->json(['error' => 'Access denied. Invalid IP.'], 403);
        }

        // 2. Если всё ок — пропускаем запрос дальше ($next) к Контроллеру
        return $next($request);
    }

    // --- Сюда переносим твои приватные методы ---

    private function isValidIp(Request $request): bool
    {
        // Не забудь: лучше использовать config(), как мы обсуждали, но пока оставим env
        $allowedCIDRs = explode(',', env('YOOKASSA_WEBHOOK_IPS', ''));
        $clientIP = $request->ip();

        foreach ($allowedCIDRs as $cidr) {
            if ($this->ipInRange($clientIP, trim($cidr))) {
                return true;
            }
        }

        return false;
    }

    private function ipInRange(string $ip, string $cidr): bool
    {
        if (strpos($cidr, '/') === false) {
            return $ip === $cidr;
        }
        [$subnet, $bits] = explode('/', $cidr);
        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertResponseToCamelCase
{
    /**
     * Handle an incoming request.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Transformer seulement les réponses JSON
        if ($response instanceof JsonResponse) {
            $data = $response->getData(true);
            $camelCaseData = $this->convertKeysToCamelCase($data);
            $response->setData($camelCaseData);
        }

        return $response;
    }

    /**
     * Convertit récursivement les clés d'un tableau de snake_case en camelCase
     */
    private function convertKeysToCamelCase($data)
    {
        if (! is_array($data)) {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value) {
            $camelKey = $this->snakeToCamel($key);

            if (is_array($value)) {
                $result[$camelKey] = $this->convertKeysToCamelCase($value);
            } else {
                $result[$camelKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convertit une chaîne snake_case en camelCase
     */
    private function snakeToCamel(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }
}

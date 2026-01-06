<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ConvertRequestToSnakeCase
{
    /**
     * Handle an incoming request.
     *
     * Convertit les clés des données de la requête de camelCase en snake_case
     * pour correspondre aux conventions de Laravel et de la base de données.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Convertir les données de la requête
        $input = $request->all();
        $snakeCaseInput = $this->convertKeysToSnakeCase($input);
        $request->replace($snakeCaseInput);

        return $next($request);
    }

    /**
     * Convertit récursivement les clés d'un tableau de camelCase en snake_case
     */
    private function convertKeysToSnakeCase($data)
    {
        if (! is_array($data)) {
            return $data;
        }

        $result = [];

        foreach ($data as $key => $value) {
            $snakeKey = $this->camelToSnake($key);

            if (is_array($value)) {
                $result[$snakeKey] = $this->convertKeysToSnakeCase($value);
            } else {
                $result[$snakeKey] = $value;
            }
        }

        return $result;
    }

    /**
     * Convertit une chaîne camelCase en snake_case
     */
    private function camelToSnake(string $string): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $string));
    }
}

<?php

declare(strict_types = 1);

namespace App\Http\Controllers;

abstract class Controller
{
    /**
     * @param array<string, string|null> $data
     * @return array<string, string|null>
     */
    protected function convertIsoAndTransformUpperCase(?array $data): ?array
    {
        $data = array_change_key_case($data, CASE_UPPER);

        array_walk($data, function (&$value): void {
            if (is_string($value)) {
                if (! mb_check_encoding($value, 'UTF-8')) {
                    $value = mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
                }

                $value = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            }
        });

        return $data;
    }

    protected function sanitizeInput(?string $input): ?string
    {
        if ($input === null || $input === '' || $input === '0') {
            return null;
        }

        if (preg_match('/^[\d.\-\/()\s]+$/', $input)) {
            return preg_replace('/\D/', '', $input);
        }

        return trim($input);
    }

    /**
     * @param array<string, string|null> $data
     * @param array<string> $fields
     * @return array<string, string|null>
     */
    protected function sanitizeData(?array $data, ?array $fields): ?array
    {
        $sanitize = function (?string $value): ?string {
            if ($value === null || $value === '' || $value === '0') {
                return null;
            }

            // Apenas números se for um campo com formatação
            if (preg_match('/^[\d.\-\/()\s]+$/', $value)) {
                return preg_replace('/\D/', '', $value);
            }

            return trim($value); // Para nomes ou textos comuns
        };

        // Campos que precisam de sanitização
        foreach ($fields as $field) {
            if (isset($data[$field])) {
                $data[$field] = $sanitize($data[$field]);
            }
        }

        return $data;
    }
}

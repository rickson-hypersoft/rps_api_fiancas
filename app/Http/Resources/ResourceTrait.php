<?php

declare(strict_types = 1);

namespace App\Http\Resources;

trait ResourceTrait
{
    private function toUtf8(?string $value): ?string
    {
        return is_string($value) ? mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1') : $value;
    }

    private function formatCpf(?string $cpf): ?string
    {
        if (! $cpf || strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    protected function formatCnpj(?string $cnpj): ?string
    {
        if (! $cnpj || strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }

    protected function formatPhone(?string $phone): ?string
    {
        if (! $phone) {
            return $phone;
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (strlen($phone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $phone);
        }

        if (strlen($phone) === 10) {
            return preg_replace('/(\d{4})(\d{4})/', '$1-$2', $phone);
        }

        if (strlen($phone) === 8) {
            return preg_replace('/(\d{4})(\d{4})/', '$1-$2', $phone);
        }

        return $phone;
    }

    protected function formatZipCode(?string $zipCode): ?string
    {
        if (! $zipCode || strlen($zipCode) !== 8) {
            return $zipCode;
        }

        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $zipCode);
    }
}

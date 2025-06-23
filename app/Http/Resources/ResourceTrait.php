<?php

declare(strict_types = 1);

namespace App\Http\Resources;

trait ResourceTrait
{
    private function toUtf8(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_convert_encoding($value, 'UTF-8', 'ISO-8859-1');
    }

    private function formatCpfCnpj(?string $documento): ?string
    {
        if ($documento === null || $documento === '' || $documento === '0') {
            return $documento;
        }

        $documento = preg_replace('/\D/', '', $documento); // remove tudo que não é número

        if (strlen((string) $documento) === 11) {
            // CPF
            return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', (string) $documento);
        }

        if (strlen((string) $documento) === 14) {
            // CNPJ
            return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', (string) $documento);
        }

        return $documento; // retorna como está se não for CPF nem CNPJ válido
    }

    private function formatCpf(?string $cpf): ?string
    {
        if ($cpf === null || $cpf === '' || $cpf === '0' || strlen($cpf) !== 11) {
            return $cpf;
        }

        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $cpf);
    }

    protected function formatCnpj(?string $cnpj): ?string
    {
        if ($cnpj === null || $cnpj === '' || $cnpj === '0' || strlen($cnpj) !== 14) {
            return $cnpj;
        }

        return preg_replace('/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/', '$1.$2.$3/$4-$5', $cnpj);
    }

    protected function formatPhone(?string $phone): ?string
    {
        if ($phone === null || $phone === '' || $phone === '0') {
            return $phone;
        }

        $phone = preg_replace('/\D/', '', $phone);

        if (strlen((string) $phone) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', (string) $phone);
        }

        if (strlen((string) $phone) === 10) {
            return preg_replace('/(\d{4})(\d{4})/', '$1-$2', (string) $phone);
        }

        if (strlen((string) $phone) === 8) {
            return preg_replace('/(\d{4})(\d{4})/', '$1-$2', (string) $phone);
        }

        return $phone;
    }

    protected function formatZipCode(?string $zipCode): ?string
    {
        if ($zipCode === null || $zipCode === '' || $zipCode === '0' || strlen($zipCode) !== 8) {
            return $zipCode;
        }

        return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $zipCode);
    }
}

<?php

declare(strict_types = 1);

namespace App\Services\Asaas;

use Illuminate\Support\Facades\Http;

class AsaasClientService
{
    protected string $token;

    protected string $url;

    public function __construct()
    {
        $this->token = config('asaas.token');
        $this->url   = 'https://api-sandbox.asaas.com/v3';
    }

    public function getCustomer(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/customers/$id")->json();
    }

    public function getPaymentById(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/payments/$id")->json();
    }

    public function getQRCodeById(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/payments/$id/pixQrCode")->json();
    }

    public function updateCustomer(string $id, array $data): bool
    {
        $response = Http::withHeaders($this->headers())->put("$this->url/customers/$id", $data);

        return $response->successful();
    }

    public function createCustomer(array $data): ?string
    {
        $response = Http::withHeaders($this->headers())->post("$this->url/customers", $data);

        return $response->successful() ? $response->json('id') : null;
    }

    public function createPayment(array $data): array
    {
        $response = Http::withHeaders($this->headers())->post("$this->url/payments", $data);

        return [
            'success' => $response->successful(),
            'data'    => $response->json(),
        ];
    }

    public function needsUpdate(array $asaasData, $propostal): bool
    {
        return $asaasData['email'] !== $propostal->PESSOA_EMAIL ||
            $asaasData['mobilePhone'] !== $propostal->PESSOA_TELEFONE ||
            $asaasData['address'] !== $propostal->IMOVEL_ENDERECO ||
            $asaasData['cpfCnpj'] !== $propostal->PESSOA_DOC;
    }

    protected function headers(): array
    {
        return [
            'accept'       => 'application/json',
            'content-type' => 'application/json',
            'access_token' => $this->token,
        ];
    }
}

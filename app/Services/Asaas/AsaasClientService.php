<?php

declare(strict_types = 1);

namespace App\Services\Asaas;

use Illuminate\Support\Facades\Http;

class AsaasClientService
{
    protected string $token;

    protected string $url = 'https://api-sandbox.asaas.com/v3';

    public function __construct()
    {
        $this->token = config('asaas.token');
    }

    public function getCustomer(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/customers/$id")->json();
    }

    public function findCustomerByCpfCnpj(string $cpfCnpj): ?array
    {
        $response = Http::withHeaders($this->headers())
            ->get("$this->url/customers", [
                'cpfCnpj' => $cpfCnpj,
            ])
            ->json();

        if (! empty($response['data']) && count($response['data']) > 0) {
            return $response['data'][0]; // Retorna o primeiro encontrado
        }

        return null; // Se não encontrou
    }

    public function getPaymentById(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/payments/$id")->json();
    }

    public function cancelPayment(string $id): array
    {
        return Http::withHeaders($this->headers())->delete("$this->url/payments/$id")->json();
    }

    public function getInstallmentId(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/installments/$id/payments")->json();
    }

    public function updatePayment(string $id, array $data): array
    {
        return Http::withHeaders($this->headers())->put("$this->url/payments/$id", $data)->json();
    }

    public function payWithCreditCard(string $id, array $data): array
    {
        $response = Http::withHeaders($this->headers())->post("$this->url/payments/{$id}/payWithCreditCard", $data);

        return [
            'success' => $response->successful(),
            'data'    => $response->json(),
        ];
    }

    public function getQRCodeById(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/payments/$id/pixQrCode")->json();
    }

    public function getLineBoletoById(string $id): array
    {
        return Http::withHeaders($this->headers())->get("$this->url/payments/$id/identificationField")->json();
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

    public function deletePayment(string $id): bool
    {
        $response = Http::withHeaders($this->headers())->delete("$this->url/payments/$id");

        return $response->successful();
    }
}

<?php

declare(strict_types = 1);

namespace App\Actions\Asaas;

use App\Models\Propostal\Propostal;
use App\Services\Asaas\AsaasClientService;

class CreateOrUpdateAsaasCustomerAction
{
    public function __construct(protected AsaasClientService $asaasClient)
    {
    }

    public function execute(Propostal $propostal): ?string
    {
        $asaasId = $propostal->ID_USUARIO_INTEGRACAO;

        $payload = $this->buildPayload($propostal);

        if ($asaasId) {
            $asaasData = $this->asaasClient->getCustomer($asaasId);

            if ($this->asaasClient->needsUpdate($asaasData, $propostal)) {
                $updated = $this->asaasClient->updateCustomer($asaasId, $payload);

                if ($updated) {
                    $propostal->update(['ID_USUARIO_INTEGRACAO' => $asaasId]);
                }
            }

            return $asaasId;
        }

        $newId = $this->asaasClient->createCustomer($payload);

        if ($newId) {
            $propostal->update(['ID_USUARIO_INTEGRACAO' => $newId]);
        }

        return $newId;
    }

    protected function buildPayload(Propostal $propostal): array
    {
        return [
            'name'              => $propostal->PESSOA_NOME,
            'cpfCnpj'           => $propostal->PESSOA_DOC,
            'email'             => $propostal->PESSOA_EMAIL,
            'mobilePhone'       => $propostal->PESSOA_TELEFONE,
            'address'           => $propostal->IMOVEL_ENDERECO,
            'addressNumber'     => $propostal->IMOVEL_NUMERO,
            'complement'        => $propostal->IMOVEL_COMPLEMENTO,
            'province'          => $propostal->IMOVEL_BAIRRO,
            'postalCode'        => $propostal->IMOVEL_CEP,
            'externalReference' => $propostal->ID,
        ];
    }
}

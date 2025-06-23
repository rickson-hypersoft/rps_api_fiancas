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

        if ($newId !== null && $newId !== '' && $newId !== '0') {
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
            'address'           => $propostal->PESSOA_ENDERECO,
            'addressNumber'     => $propostal->PESSOA_NUMERO,
            'complement'        => $propostal->PESSOA_COMPLEMENTO,
            'province'          => $propostal->PESSOA_BAIRRO,
            'postalCode'        => $propostal->PESSOA_CEP,
            'externalReference' => $propostal->ID,
        ];
    }
}

<?php

declare(strict_types = 1);

namespace App\Actions\Asaas;

use App\Models\Propostal\Propostal;
use App\Services\Asaas\AsaasClientService;
use Illuminate\Support\Facades\Log;

class CreateOrUpdateAsaasCustomerAction
{
    public function __construct(protected AsaasClientService $asaasClient)
    {
    }

    public function execute(Propostal $propostal): ?string
    {
        $propostalId = $propostal->ID ?? null;
        $asaasId     = $propostal->ID_USUARIO_INTEGRACAO ?? null;

        // Contexto padrão de log
        $ctx = [
            'propostal_id' => $propostalId,
            'asaas_id'     => $asaasId,
        ];

        Log::info('[ASAAS][CUSTOMER] Iniciando CreateOrUpdate', $ctx);

        try {
            $payload = $this->buildPayload($propostal);

            // Log seguro do payload (sem valores)
            Log::debug('[ASAAS][CUSTOMER] Payload gerado (safe)', $ctx + [
                'payload_keys'      => array_keys($payload),
                'payload_lengths'   => $this->payloadLengths($payload),
                'has_cpfCnpj'       => ! empty($payload['cpfCnpj']),
                'has_name'          => ! empty($payload['name']),
                'has_postalCode'    => ! empty($payload['postalCode']),
                'externalReference' => $payload['externalReference'] ?? null,
            ]);

            // 1) Se já tem ID no Asaas -> GET + maybe UPDATE
            if (! empty($asaasId)) {
                Log::info('[ASAAS][CUSTOMER] Fluxo: Já possui asaas_id, buscando customer', $ctx);

                $asaasData = $this->asaasClient->getCustomer($asaasId);

                Log::debug('[ASAAS][CUSTOMER] getCustomer retornou', $ctx + [
                    'asaas_data_type'   => gettype($asaasData),
                    'asaas_data_has_id' => is_array($asaasData) ? isset($asaasData['id']) : null,
                    'asaas_data_keys'   => is_array($asaasData) ? array_keys($asaasData) : null,
                ]);

                $needsUpdate = $this->asaasClient->needsUpdate($asaasData, $propostal);

                Log::info('[ASAAS][CUSTOMER] needsUpdate resultado', $ctx + [
                    'needs_update' => $needsUpdate,
                ]);

                if ($needsUpdate) {
                    Log::info('[ASAAS][CUSTOMER] Atualizando customer no Asaas', $ctx);

                    $updated = $this->asaasClient->updateCustomer($asaasId, $payload);

                    Log::info('[ASAAS][CUSTOMER] updateCustomer retorno', $ctx + [
                        'updated' => $updated,
                    ]);

                    if ($updated) {
                        $propostal->update(['ID_USUARIO_INTEGRACAO' => $asaasId]);

                        Log::info('[ASAAS][CUSTOMER] Propostal atualizada com asaas_id (mantido)', $ctx);
                    } else {
                        Log::warning('[ASAAS][CUSTOMER] updateCustomer retornou falso', $ctx);
                    }
                }

                Log::info('[ASAAS][CUSTOMER] Finalizado: retornando asaas_id existente', $ctx);

                return (string) $asaasId;
            }

            // 2) Não tem asaas_id -> tentar achar por CPF/CNPJ
            Log::info('[ASAAS][CUSTOMER] Fluxo: Sem asaas_id, buscando customer por cpf/cnpj', $ctx + [
                'cpf_last4' => $this->last4((string) $propostal->PESSOA_DOC),
            ]);

            $existingCustomer = $this->asaasClient->findCustomerByCpfCnpj($propostal->PESSOA_DOC);

            Log::debug('[ASAAS][CUSTOMER] findCustomerByCpfCnpj retorno', $ctx + [
                'found'  => $existingCustomer !== null && $existingCustomer !== [],
                'type'   => gettype($existingCustomer),
                'has_id' => is_array($existingCustomer) ? isset($existingCustomer['id']) : null,
            ]);

            if ($existingCustomer !== null && $existingCustomer !== [] && is_array($existingCustomer) && ! empty($existingCustomer['id'])) {
                $propostal->update(['ID_USUARIO_INTEGRACAO' => $existingCustomer['id']]);

                Log::info('[ASAAS][CUSTOMER] Customer existente encontrado, salvando asaas_id', $ctx + [
                    'asaas_id_new' => $existingCustomer['id'],
                ]);

                return (string) $existingCustomer['id'];
            }

            // 3) Se não existe -> criar
            Log::info('[ASAAS][CUSTOMER] Fluxo: Criando customer no Asaas', $ctx);

            $newId = $this->asaasClient->createCustomer($payload);

            Log::info('[ASAAS][CUSTOMER] createCustomer retorno', $ctx + [
                'new_id'       => $newId,
                'new_id_valid' => $newId !== null && $newId !== '' && $newId !== '0' && $newId !== '0',
            ]);

            if ($newId !== null && $newId !== '' && $newId !== '0' && $newId !== '0') {
                $propostal->update(['ID_USUARIO_INTEGRACAO' => $newId]);

                Log::info('[ASAAS][CUSTOMER] Propostal atualizada com novo asaas_id', $ctx + [
                    'asaas_id_new' => $newId,
                ]);
            } else {
                Log::warning('[ASAAS][CUSTOMER] createCustomer retornou id inválido', $ctx + [
                    'new_id' => $newId,
                ]);
            }

            Log::info('[ASAAS][CUSTOMER] Finalizado: retornando newId', $ctx + [
                'new_id' => $newId,
            ]);

            return $newId !== null && $newId !== '' && $newId !== '0' ? $newId : null;
        } catch (\Throwable $e) {
            Log::error('[ASAAS][CUSTOMER] Exceção no fluxo de customer', $ctx + [
                'exception' => $e->getMessage(),
                'file'      => $e->getFile(),
                'line'      => $e->getLine(),
                'trace'     => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    protected function buildPayload(Propostal $propostal): array
    {
        $payload = [
            'name'              => mb_convert_encoding((string) $propostal->PESSOA_NOME, 'UTF-8', 'ISO-8859-1'),
            'cpfCnpj'           => $propostal->PESSOA_DOC,
            'email'             => $propostal->PESSOA_EMAIL,
            'mobilePhone'       => $propostal->PESSOA_TELEFONE,
            'address'           => mb_convert_encoding((string) $propostal->PESSOA_ENDERECO, 'UTF-8', 'ISO-8859-1'),
            'addressNumber'     => $propostal->PESSOA_NUMERO,
            'complement'        => mb_convert_encoding((string) $propostal->PESSOA_COMPLEMENTO, 'UTF-8', 'ISO-8859-1'),
            'province'          => mb_convert_encoding((string) $propostal->PESSOA_BAIRRO, 'UTF-8', 'ISO-8859-1'),
            'postalCode'        => $propostal->PESSOA_CEP,
            'externalReference' => $propostal->ID,
        ];

        return $this->convertUtf8ToIso($payload);
    }

    private function convertUtf8ToIso(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
            }
        }

        return $data;
    }

    private function payloadLengths(array $payload): array
    {
        $out = [];

        foreach ($payload as $k => $v) {
            if (is_string($v)) {
                $out[$k] = mb_strlen($v);
            } elseif ($v === null) {
                $out[$k] = null;
            } else {
                $out[$k] = gettype($v);
            }
        }

        return $out;
    }

    private function last4(string $doc): string
    {
        $digits = preg_replace('/\D+/', '', $doc) ?? '';

        return $digits === '' ? '' : substr($digits, -4);
    }
}

<?php

declare(strict_types = 1);

namespace App\Http\Resources;

use App\Models\Extract;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource; // Import the Propostal model

/**
 * @mixin Extract
 * @property Extract $Extract
 */
class ExtractResource extends JsonResource
{
    use ResourceTrait;

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                   => $this->ID,
            'object'               => $this->OBJECT,
            'id_extract'           => $this->ID_EXTRACT,
            'value'                => $this->VALUE,
            'balance'              => $this->BALANCE,
            'type'                 => $this->TYPE,
            'date'                 => $this->DATE,
            'description'          => $this->toUtf8($this->DESCRIPTION),
            'paymentId'            => $this->PAYMENTID,
            'externalReference'    => $this->EXTERNALREFERENCE,
            'splitId'              => $this->SPLITID,
            'transferId'           => $this->TRANSFERID,
            'anticipationId'       => $this->ANTICIPATIONID,
            'billId'               => $this->BILLID,
            'invoiceId'            => $this->INVOICEID,
            'paymentDunningId'     => $this->PAYMENTDUNNINGID,
            'creditBureauReportId' => $this->CREDITBUREAUREPORTID,
        ];
    }
}

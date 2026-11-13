<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Morilog\Jalali\Jalalian;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'currency' => $this->currency ?? 'toman',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'signed_up_at' => $this->getSignedUpAtJalali(),
        ];
    }

    /**
     * Get the signed up date in Jalali format
     *
     * @return string
     */
    private function getSignedUpAtJalali(): string
    {
        if (!$this->created_at) {
            return '—';
        }

        try {
            $jalaliDate = Jalalian::fromCarbon($this->created_at);
            return $jalaliDate->format('Y/m/d');
        } catch (\Exception $e) {
            return '—';
        }
    }
}

<?php

namespace App\Services\Setup;

use App\Models\Structure\Diocese;
use App\Models\Structure\Parish;
use Illuminate\Support\Facades\DB;

class SetupService
{
    public function getCurrentSetupState(): array
    {
        $diocese = Diocese::query()->orderBy('id')->first();
        $parish = Parish::query()->orderBy('id')->first();

        return [
            'diocese' => $diocese?->only([
                'uuid',
                'name',
                'archbishop_name',
                'established_year',
                'address',
                'phone',
                'email',
                'website',
                'country',
            ]),
            'parish' => $parish?->only([
                'uuid',
                'name',
                'code',
                'patron_saint',
                'established_year',
                'address',
                'phone',
                'email',
            ]),
        ];
    }

    public function saveSetup(array $validated): void
    {
        DB::transaction(function () use ($validated) {
            $dioceseData = $validated['diocese'];
            $parishData = $validated['parish'];

            $dioceseName = $this->clean($dioceseData['name']);
            $parishName = $this->clean($parishData['name']);

            $diocese = Diocese::query()->orderBy('id')->lockForUpdate()->first();
            if ($diocese) {
                $diocese->name = $dioceseName;
                $diocese->archbishop_name = $this->clean($dioceseData['archbishop_name'] ?? null);
                $diocese->established_year = $dioceseData['established_year'] ?? null;
                $diocese->address = $this->clean($dioceseData['address'] ?? null);
                $diocese->phone = $this->cleanPhone($dioceseData['phone'] ?? null);
                $diocese->email = $this->cleanEmail($dioceseData['email'] ?? null);
                $diocese->website = $this->cleanUrl($dioceseData['website'] ?? null);
                $diocese->country = $this->clean($dioceseData['country'] ?? null);
                $diocese->singleton = 1;
                $diocese->is_active = true;
                $diocese->save();
            } else {
                $diocese = new Diocese();
                $diocese->name = $dioceseName;
                $diocese->archbishop_name = $this->clean($dioceseData['archbishop_name'] ?? null);
                $diocese->established_year = $dioceseData['established_year'] ?? null;
                $diocese->address = $this->clean($dioceseData['address'] ?? null);
                $diocese->phone = $this->cleanPhone($dioceseData['phone'] ?? null);
                $diocese->email = $this->cleanEmail($dioceseData['email'] ?? null);
                $diocese->website = $this->cleanUrl($dioceseData['website'] ?? null);
                $diocese->country = $this->clean($dioceseData['country'] ?? null);
                $diocese->singleton = 1;
                $diocese->is_active = true;
                $diocese->save();
            }

            $parish = Parish::query()->orderBy('id')->lockForUpdate()->first();
            if ($parish) {
                $parish->diocese_id = $diocese->id;
                $parish->name = $parishName;
                $parish->code = $this->clean($parishData['code'] ?? null);
                $parish->patron_saint = $this->clean($parishData['patron_saint'] ?? null);
                $parish->established_year = $parishData['established_year'] ?? null;
                $parish->address = $this->clean($parishData['address'] ?? null);
                $parish->phone = $this->cleanPhone($parishData['phone'] ?? null);
                $parish->email = $this->cleanEmail($parishData['email'] ?? null);
                $parish->singleton = 1;
                $parish->is_active = true;
                $parish->save();
            } else {
                $parish = new Parish();
                $parish->diocese_id = $diocese->id;
                $parish->name = $parishName;
                $parish->code = $this->clean($parishData['code'] ?? null);
                $parish->patron_saint = $this->clean($parishData['patron_saint'] ?? null);
                $parish->established_year = $parishData['established_year'] ?? null;
                $parish->address = $this->clean($parishData['address'] ?? null);
                $parish->phone = $this->cleanPhone($parishData['phone'] ?? null);
                $parish->email = $this->cleanEmail($parishData['email'] ?? null);
                $parish->singleton = 1;
                $parish->is_active = true;
                $parish->save();
            }
        });
    }

    private function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);
        $value = strip_tags($value);

        return $value === '' ? null : $value;
    }

    private function cleanEmail(?string $value): ?string
    {
        $clean = $this->clean($value);
        return $clean ? strtolower($clean) : null;
    }

    private function cleanUrl(?string $value): ?string
    {
        return $this->clean($value);
    }

    private function cleanPhone(?string $value): ?string
    {
        $clean = $this->clean($value);
        if (!$clean) {
            return null;
        }

        $clean = str_replace([' ', '-'], '', $clean);

        return $clean;
    }
}

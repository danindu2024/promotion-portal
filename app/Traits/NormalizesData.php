<?php

namespace App\Traits;

trait NormalizesData
{
    // sanitize, remove non digit characters, add 0 if missing, convert 94 to 0
    protected function normalizePhoneNumber($number)
    {
        if (empty($number)) return null;
        $number = trim((string) $number);
        $number = preg_replace('/\D/', '', $number);
        if (str_starts_with($number, '94') && strlen($number) === 11) {
            $number = '0' . substr($number, 2);
        } else {
            if (strlen($number) === 9 && !str_starts_with($number, '0')) {
                $number = '0' . $number;
            }
        }
        return $number;
    }

    // sanitize, convert excel scientific format to string, capitalize last letter if exists
    protected function normalizeNationalId($id)
    {
        if (empty($id)) return null;
        if (is_numeric($id)) {
            return number_format((float) $id, 0, '', '');
        }
        return strtoupper(trim((string) $id));
    }

    // capitalize first letter of each word for locations (e.g. "colombo" -> "Colombo")
    protected function normalizeLocationName($name)
    {
        if (empty($name)) return null;
        return ucwords(strtolower(trim((string) $name)));
    }
}

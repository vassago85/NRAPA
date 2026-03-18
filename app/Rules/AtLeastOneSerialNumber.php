<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class AtLeastOneSerialNumber implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $data = request()->all();

        // Check if at least one serial number is provided
        $hasSerial = ! empty($data['barrel_serial'] ?? null)
            || ! empty($data['frame_serial'] ?? null)
            || ! empty($data['receiver_serial'] ?? null)
            || ! empty($data['barrel_serial_number'] ?? null)
            || ! empty($data['frame_serial_number'] ?? null)
            || ! empty($data['receiver_serial_number'] ?? null)
            || ! empty($data['serial_number'] ?? null);

        return $hasSerial;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Provide at least one serial number (Barrel, Frame, or Receiver) as per SAPS 271.';
    }
}

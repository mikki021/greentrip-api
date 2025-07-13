<?php

namespace App\Services\DTOs;

class PassengerData
{
    public function __construct(
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $date_of_birth,
        public readonly string $passport_number
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            first_name: $data['first_name'],
            last_name: $data['last_name'],
            date_of_birth: $data['date_of_birth'],
            passport_number: $data['passport_number']
        );
    }

    public function toArray(): array
    {
        return [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'passport_number' => $this->passport_number,
        ];
    }

    public function createModel(int $bookingId): \App\Models\Passenger
    {
        return \App\Models\Passenger::create([
            'booking_id' => $bookingId,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'date_of_birth' => $this->date_of_birth,
            'passport_number' => $this->passport_number,
        ]);
    }
}
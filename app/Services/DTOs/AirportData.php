<?php

namespace App\Services\DTOs;

class AirportData
{
    public function __construct(
        public readonly string $code,
        public readonly string $name,
        public readonly string $city,
        public readonly string $country,
        public readonly float $latitude,
        public readonly float $longitude
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            code: $data['code'],
            name: $data['name'],
            city: $data['city'],
            country: $data['country'],
            latitude: $data['latitude'],
            longitude: $data['longitude']
        );
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'name' => $this->name,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude
        ];
    }
}
<?php

namespace App\DataTransferObjects;

class FlightData
{
    public function __construct(
        public readonly string $id,
        public readonly string $airline,
        public readonly string $flight_number,
        public readonly string $from,
        public readonly string $to,
        public readonly string $departure_time,
        public readonly string $arrival_time,
        public readonly string $duration,
        public readonly float $price,
        public readonly int $seats_available,
        public readonly string $aircraft,
        public readonly float $carbon_footprint,
        public readonly float $eco_rating,
        public readonly ?string $date = null,
        public readonly ?float $total_price = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            airline: $data['airline'],
            flight_number: $data['flight_number'],
            from: $data['from'],
            to: $data['to'],
            departure_time: $data['departure_time'],
            arrival_time: $data['arrival_time'],
            duration: $data['duration'],
            price: $data['price'],
            seats_available: $data['seats_available'],
            aircraft: $data['aircraft'],
            carbon_footprint: $data['carbon_footprint'],
            eco_rating: $data['eco_rating'],
            date: $data['date'] ?? null,
            total_price: $data['total_price'] ?? null
        );
    }

    public function toArray(): array
    {
        $array = [
            'id' => $this->id,
            'airline' => $this->airline,
            'flight_number' => $this->flight_number,
            'from' => $this->from,
            'to' => $this->to,
            'departure_time' => $this->departure_time,
            'arrival_time' => $this->arrival_time,
            'duration' => $this->duration,
            'price' => $this->price,
            'seats_available' => $this->seats_available,
            'aircraft' => $this->aircraft,
            'carbon_footprint' => $this->carbon_footprint,
            'eco_rating' => $this->eco_rating,
        ];

        if ($this->date !== null) {
            $array['date'] = $this->date;
        }

        if ($this->total_price !== null) {
            $array['total_price'] = $this->total_price;
        }

        return $array;
    }
}
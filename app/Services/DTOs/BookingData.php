<?php

namespace App\Services\DTOs;

class BookingData
{
    public function __construct(
        public readonly string $booking_reference,
        public readonly string $flight_id,
        public readonly FlightData $flight_details,
        public readonly int $passengers,
        public readonly float $total_price,
        public readonly array $passenger_details, // Array of PassengerData objects
        public readonly string $contact_email,
        public readonly ?string $contact_phone,
        public readonly string $booking_date,
        public readonly string $status,
        public readonly float $carbon_offset_contribution
    ) {}

    public static function fromArray(array $data): self
    {
        $passengerDetails = array_map(
            fn($passenger) => PassengerData::fromArray($passenger),
            $data['passenger_details']
        );

        return new self(
            booking_reference: $data['booking_reference'],
            flight_id: $data['flight_id'],
            flight_details: FlightData::fromArray($data['flight_details']),
            passengers: $data['passengers'],
            total_price: $data['total_price'],
            passenger_details: $passengerDetails,
            contact_email: $data['contact_email'],
            contact_phone: $data['contact_phone'] ?? null,
            booking_date: $data['booking_date'],
            status: $data['status'],
            carbon_offset_contribution: $data['carbon_offset_contribution']
        );
    }

    public function toArray(): array
    {
        return [
            'booking_reference' => $this->booking_reference,
            'flight_id' => $this->flight_id,
            'flight_details' => $this->flight_details->toArray(),
            'passengers' => $this->passengers,
            'total_price' => $this->total_price,
            'passenger_details' => array_map(
                fn($passenger) => $passenger->toArray(),
                $this->passenger_details
            ),
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'booking_date' => $this->booking_date,
            'status' => $this->status,
            'carbon_offset_contribution' => $this->carbon_offset_contribution
        ];
    }
}
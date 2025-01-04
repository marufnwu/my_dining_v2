<?php

namespace App\DTOs;

use Carbon\Carbon;

class UserDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly string $phone,
        public readonly string $country,
        public readonly string $city,
        public readonly string $gender,
        public readonly string $password,
        public ?string $join_date = null,
    ) {
        $this->join_date = $this->join_date ?? Carbon::now();
    }

    public static function fromRequest(array $data): UserDto
    {
        return new UserDto(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'],
            country: $data['country'],
            city: $data['city'],
            gender: $data['gender'],
            password: $data['password'],
            join_date: $data['join_date'],
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country' => $this->country,
            'city' => $this->city,
            'gender' => $this->gender,
            'password' => $this->password,
            'join_date' => $this->join_date,
        ];
    }

    public static function fromArray(array $data) : self
    {
        return new self(
            name: $data['name'],
            email: $data['email'],
            phone: $data['phone'],
            country: $data['country'],
            city: $data['city'],
            gender: $data['gender'],
            password: $data['password'],
            join_date: $data['join_date'],
        );

    }

}

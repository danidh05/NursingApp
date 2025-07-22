<?php

namespace App\Repositories\Interfaces;

use App\Models\Slider;
use Illuminate\Database\Eloquent\Collection;

interface ISliderRepository
{
    public function getAll(): Collection;
    public function findById(int $id): Slider;
    public function create(array $data): Slider;
    public function update(int $id, array $data): Slider;
    public function delete(int $id): void;
    public function getAllOrdered(): Collection;
} 
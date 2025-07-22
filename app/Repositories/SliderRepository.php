<?php

namespace App\Repositories;

use App\Models\Slider;
use App\Repositories\Interfaces\ISliderRepository;
use Illuminate\Database\Eloquent\Collection;

class SliderRepository implements ISliderRepository
{
    public function getAll(): Collection
    {
        return Slider::all();
    }

    public function getAllOrdered(): Collection
    {
        return Slider::ordered()->get();
    }

    public function findById(int $id): Slider
    {
        return Slider::findOrFail($id);
    }

    public function create(array $data): Slider
    {
        return Slider::create($data);
    }

    public function update(int $id, array $data): Slider
    {
        $slider = $this->findById($id);
        $slider->update($data);
        return $slider;
    }

    public function delete(int $id): void
    {
        $slider = $this->findById($id);
        $slider->delete();
    }
} 
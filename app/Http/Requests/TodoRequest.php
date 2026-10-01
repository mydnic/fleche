<?php

namespace App\Http\Requests;

use App\Support\Images;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

/**
 * A one-shot todo, from the app or the API.
 */
class TodoRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:'.Images::MAX_KB],
            'date' => ['nullable', 'date'],
            'points' => ['nullable', 'integer', 'min:0', 'max:100000'],
        ];
    }

    /**
     * Lands tomorrow (the user's tomorrow) unless told otherwise.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            ...Arr::except($this->validated(), 'image'),
            'image' => $this->hasFile('image') ? app(Images::class)->store($this->file('image')) : null,
            'date' => $this->validated('date') ?? $this->user()->today()->addDay()->toDateString(),
            'points' => $this->validated('points') ?? 0,
        ];
    }
}

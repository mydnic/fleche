<?php

namespace App\Http\Requests;

use App\Enums\EveryUnit;
use App\Models\TodoSetting;
use App\Support\Images;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

/**
 * Shared by the rule builder form and the API.
 */
class TodoSettingRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $sometimes = $this->isMethod('PATCH') ? ['sometimes'] : [];

        return [
            'name' => [...$sometimes, 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'max:'.Images::MAX_KB],
            'remove_image' => ['boolean'],
            'active' => ['boolean'],
            'days' => ['nullable', 'array'],
            'days.*' => [Rule::in(TodoSetting::WEEKDAYS)],
            'random_day' => ['boolean'],
            'every_value' => ['nullable', 'integer', 'min:1', 'max:1000', 'required_with:every_unit'],
            'every_unit' => ['nullable', Rule::enum(EveryUnit::class), 'required_with:every_value'],
            'day_of_month' => ['nullable', 'integer', Rule::in([-1, ...range(1, 31)])],
            'months' => ['nullable', 'array'],
            'months.*' => ['integer', 'between:1,12'],
            'start_after' => ['nullable', 'date'],
            'chance' => ['numeric', 'gt:0', 'max:1'],
            'allow_duplicates' => ['boolean'],
            'points' => ['integer', 'min:0', 'max:100000'],
            'reward_cost' => ['nullable', 'integer', 'min:1', 'max:100000'],
        ];
    }

    /**
     * Validated attributes, with an uploaded image stored and swapped for its path.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        $data = Arr::except($this->validated(), ['image', 'remove_image']);

        if ($this->hasFile('image')) {
            $data['image'] = app(Images::class)->store($this->file('image'));
        } elseif ($this->boolean('remove_image')) {
            $data['image'] = null;
        }

        return $data;
    }
}

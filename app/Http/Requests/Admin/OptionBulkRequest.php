<?php

namespace App\Http\Requests\Admin;

use App\Support\OptionLists;
use Illuminate\Foundation\Http\FormRequest;

/** Add several names at once to a name-only option list: one name per line. */
class OptionBulkRequest extends FormRequest
{
    public const MAX_NAMES = 200;

    public function authorize(): bool
    {
        return $this->user()->can('manage-option-lists')
            && in_array($this->route('list'), OptionLists::BULK_LISTS, true);
    }

    public function rules(): array
    {
        return ['names' => ['required', 'string', 'max:20000']];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $names = $this->names();

            if ($validator->errors()->has('names')) {
                return;
            }
            if ($names === []) {
                $validator->errors()->add('names', 'Enter at least one name.');
            } elseif (count($names) > self::MAX_NAMES) {
                $validator->errors()->add('names', 'Enter at most '.self::MAX_NAMES.' names at once.');
            } elseif ($long = collect($names)->first(fn ($name) => mb_strlen($name) > 100)) {
                $validator->errors()->add('names', "A name can have at most 100 characters: {$long}");
            }
        });
    }

    /** Trimmed, non-empty names without repeats (any case), in the order typed. */
    public function names(): array
    {
        $names = [];

        foreach (preg_split('/\R/u', (string) $this->input('names')) as $line) {
            $line = trim($line);
            if ($line !== '' && ! in_array(mb_strtolower($line), array_map('mb_strtolower', $names), true)) {
                $names[] = $line;
            }
        }

        return $names;
    }
}

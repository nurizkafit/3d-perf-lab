<?php

namespace App\Http\Requests\Reports;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Save a report draft (PRD.md §10.3–§10.4): owner of the test or admin. Approved assets must belong to the test. */
class UpdateReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $test = $this->route('test');
        $report = $test->report;

        return $this->user()->can('update', $test) && ($report === null || $this->user()->can('update', $report));
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:150'],
            'question' => ['nullable', 'string', 'max:1000'],
            'limitations' => ['nullable', 'string', 'max:3000'],
            'conclusion' => ['nullable', 'string', 'max:5000'],
            'approved' => ['array'],
            'approved.*' => ['integer', 'distinct', Rule::in($this->route('test')->assets()->pluck('assets.id')->all())],
        ];
    }
}

<?php

namespace App\Http\Requests\Reports;

use App\Services\ReportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/** Publish or unpublish a report: owner or admin (ReportPolicy). Publishing checks the prerequisites (PRD.md §10.4). */
class PublishReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        $report = $this->route('test')->report;

        return $report !== null && $this->user()->can($this->routeIs('results.report.unpublish') ? 'unpublish' : 'publish', $report);
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($this->routeIs('results.report.publish')) {
                foreach (app(ReportService::class)->blockers($this->route('test')->report) as $blocker) {
                    $validator->errors()->add('publish', $blocker);
                }
            }
        }];
    }
}

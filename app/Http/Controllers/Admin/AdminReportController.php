<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewReportRequest;
use App\Models\Report;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminReportController extends Controller
{
    public function index(): View
    {
        $reports = Report::query()
            ->with(['reporter.profile', 'reportedUser.profile', 'reviewer'])
            ->latest('id')
            ->paginate(15);

        return view('admin.reports.index', compact('reports'));
    }

    public function review(ReviewReportRequest $request, Report $report): RedirectResponse
    {
        $report->update([
            'status' => $request->validated('status'),
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => Carbon::now(),
        ]);

        return back()->with('status', 'report-updated');
    }
}

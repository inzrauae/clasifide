<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');
        $reports = Report::with(['listing.user', 'user'])->where('status', $status)->latest()->paginate(30)->withQueryString();

        return view('admin.reports', compact('reports', 'status'));
    }

    public function update(Request $request, Report $report)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['dismiss', 'resolve', 'remove_ad'])],
        ]);

        if ($data['action'] === 'remove_ad' && $report->listing) {
            $report->listing->update(['status' => 'rejected', 'reject_reason' => 'Removed after a user report: '.$report->reasonLabel()]);
            Report::where('listing_id', $report->listing_id)->where('status', 'open')->update(['status' => 'resolved']);
        }

        $report->update(['status' => $data['action'] === 'dismiss' ? 'dismissed' : 'resolved']);

        return back()->with('status', 'Report updated.');
    }
}

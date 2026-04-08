<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use Illuminate\Contracts\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'users_count' => User::count(),
            'pending_profiles_count' => Profile::where('approval_status', ProfileApprovalStatus::Pending->value)->count(),
            'pending_payments_count' => Payment::where('status', PaymentStatus::Pending->value)->count(),
            'open_requests_count' => ConnectionRequest::where('status', ConnectionRequestStatus::PaymentPending->value)->count(),
            'pending_reports_count' => Report::where('status', ReportStatus::Pending->value)->count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}

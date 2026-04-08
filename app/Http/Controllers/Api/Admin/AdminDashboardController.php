<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ConnectionRequestStatus;
use App\Enums\PaymentStatus;
use App\Enums\ProfileApprovalStatus;
use App\Enums\ReportStatus;
use App\Http\Controllers\Api\ApiController;
use App\Models\ConnectionRequest;
use App\Models\Payment;
use App\Models\Profile;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class AdminDashboardController extends ApiController
{
    public function index(): JsonResponse
    {
        $stats = [
            'users_count' => User::count(),
            'active_users_count' => User::where('is_active', true)->where('is_blocked', false)->count(),
            'blocked_users_count' => User::where('is_blocked', true)->count(),
            'inactive_users_count' => User::where('is_active', false)->count(),
            'pending_profiles_count' => Profile::where('approval_status', ProfileApprovalStatus::Pending->value)->count(),
            'pending_payments_count' => Payment::where('status', PaymentStatus::Pending->value)->count(),
            'payment_pending_requests_count' => ConnectionRequest::where('status', ConnectionRequestStatus::PaymentPending->value)->count(),
            'partially_paid_requests_count' => ConnectionRequest::where('status', ConnectionRequestStatus::PartiallyPaid->value)->count(),
            'pending_reports_count' => Report::where('status', ReportStatus::Pending->value)->count(),
        ];

        return $this->success($stats, 'Admin dashboard retrieved.');
    }
}

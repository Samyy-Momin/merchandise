<?php

namespace App\Http\Controllers;

use App\Models\Approval;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApprovalController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('kc_user');
        $approverId = $user['sub'] ?? null;

        $status = $request->query('status');

        $query = Approval::with(['order.items.product', 'order.address'])
            ->where('approver_id', $approverId)
            ->orderByDesc('id');

        if ($status && in_array($status, ['approved','rejected','partial'], true)) {
            $query->where('status', $status);
        }

        $list = $query->paginate(20);
        Log::info('Approvals index', ['approver_id' => $approverId, 'status' => $status, 'count' => $list->count()]);
        return response()->json($list);
    }

    public function show(Request $request, int $id)
    {
        $user = $request->attributes->get('kc_user');
        $approverId = $user['sub'] ?? null;

        $approval = Approval::with(['order.items.product', 'order.address'])
            ->where('approver_id', $approverId)
            ->findOrFail($id);

        return response()->json($approval);
    }

    public function stats(Request $request)
    {
        $user = $request->attributes->get('kc_user');
        $approverId = $user['sub'] ?? null;

        $base = Approval::where('approver_id', $approverId);
        $approved = (clone $base)->where('status','approved')->count();
        $rejected = (clone $base)->where('status','rejected')->count();
        $partial = (clone $base)->where('status','partial')->count();

        $data = compact('approved', 'rejected', 'partial');
        Log::info('Approvals stats', ['approver_id' => $approverId] + $data);
        return response()->json($data);
    }
}


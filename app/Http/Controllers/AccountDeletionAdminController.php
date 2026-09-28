<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountDeletionAdminController extends Controller
{
    public function __construct(private readonly AccountDeletionService $deletions) {}

    public function index(): Response
    {
        $rows = AccountDeletionRequest::query()
            ->with('user:id,account_type,email,is_active')
            ->orderByDesc('requested_at')
            ->limit(200)
            ->get()
            ->map(function (AccountDeletionRequest $deletion): array {
                $plan = $this->deletions->planFor($deletion->user, $deletion->account_type);

                return [
                    'id' => $deletion->id,
                    'email' => $deletion->email,
                    'claimed_role' => $deletion->account_type,
                    'linked_account_type' => $deletion->user?->account_type,
                    'source' => $deletion->source,
                    'status' => $deletion->status,
                    'message' => $deletion->message,
                    'resolution_note' => $deletion->resolution_note,
                    'requested_at' => $deletion->requested_at?->toIso8601String(),
                    'open' => $deletion->isOpen(),
                    'remove' => $plan['remove'],
                    'retain' => $plan['retain'],
                ];
            });

        return Inertia::render('Privacy/DeletionRequests', [
            'requests' => $rows,
        ]);
    }

    public function verify(Request $request, AccountDeletionRequest $deletion): RedirectResponse
    {
        $this->deletions->markVerified($deletion, $request->user(), $request);

        return back();
    }

    public function reject(Request $request, AccountDeletionRequest $deletion): RedirectResponse
    {
        $data = $request->validate([
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->deletions->reject($deletion, $request->user(), $request, $data['resolution_note'] ?? null);

        return back();
    }

    public function complete(Request $request, AccountDeletionRequest $deletion): RedirectResponse
    {
        $data = $request->validate([
            'confirm' => ['accepted'],
            'resolution_note' => ['nullable', 'string', 'max:2000'],
        ]);
        $this->deletions->complete($deletion, $request->user(), $request, $data['resolution_note'] ?? null);

        return back();
    }
}

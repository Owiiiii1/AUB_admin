<?php

namespace App\Http\Controllers;

use App\Models\AccountDeletionRequest;
use App\Services\AccountDeletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PrivacyPageController extends Controller
{
    public function __construct(private readonly AccountDeletionService $deletions) {}

    public function policy(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->rememberLocale($request)) {
            return $redirect;
        }

        return view('privacy.policy', $this->shared());
    }

    public function deletion(Request $request): View|RedirectResponse
    {
        if ($redirect = $this->rememberLocale($request)) {
            return $redirect;
        }

        return view('privacy.deletion', $this->shared());
    }

    public function storeDeletion(Request $request): RedirectResponse
    {
        if (trim((string) $request->input('website')) !== '') {
            return redirect()
                ->route('privacy.deletion')
                ->with('deletion_received', true);
        }

        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'in:'.implode(',', AccountDeletionRequest::ROLES)],
            'message' => ['nullable', 'string', 'max:2000'],
            'confirm' => ['accepted'],
            'privacy' => ['accepted'],
        ]);

        $this->deletions->submitPublic($data['email'], $data['role'], $data['message'] ?? null);

        return redirect()
            ->route('privacy.deletion')
            ->with('deletion_received', true);
    }

    private function rememberLocale(Request $request): ?RedirectResponse
    {
        if (! $request->has('lang')) {
            return null;
        }

        $lang = (string) $request->query('lang');
        if (in_array($lang, config('privacy.locales'), true)) {
            $request->session()->put('locale', $lang);
        }

        return redirect()->to($request->url());
    }

    /**
     * @return array<string, mixed>
     */
    private function shared(): array
    {
        return [
            'controllerName' => (string) config('privacy.controller_name'),
            'controllerAddress' => config('privacy.controller_address') ?: null,
            'contactEmail' => config('privacy.contact_email') ?: null,
            'lastUpdated' => (string) config('privacy.last_updated'),
            'locales' => config('privacy.locales'),
        ];
    }
}

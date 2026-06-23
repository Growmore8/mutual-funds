<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Concerns\FiltersClients;
use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycReviewController extends Controller
{
    use FiltersClients;

    public function index(Request $request)
    {
        $status = $request->get('status', 'submitted');
        $search = trim((string) $request->get('q'));

        $documents = KycDocument::with('user')
            ->when(in_array($status, ['submitted', 'approved', 'rejected']), fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $this->matchClient($u, $search)))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = [
            'submitted' => KycDocument::where('status', 'submitted')->count(),
            'approved' => KycDocument::where('status', 'approved')->count(),
            'rejected' => KycDocument::where('status', 'rejected')->count(),
            'all' => KycDocument::count(),
        ];

        return view('admin.kyc.index', compact('documents', 'status', 'counts', 'search'));
    }

    public function file(KycDocument $document, string $side = 'front')
    {
        $path = $side === 'back'
            ? $document->back_path
            : ($document->front_path ?? $document->file_path);

        abort_if(! $path || ! Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path);
    }

    public function approve(Request $request, KycDocument $document)
    {
        $document->update([
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        // Approving any document grants full access.
        $document->user->update(['kyc_status' => 'approved', 'status' => 'active']);

        \App\Models\AppNotification::notify($document->user_id, 'kyc', 'KYC approved', 'Your identity is verified — full access unlocked.', route('client.dashboard'));

        return back()->with('status', "KYC approved for {$document->user->name}.");
    }

    public function reject(Request $request, KycDocument $document)
    {
        $data = $request->validate(['review_note' => ['nullable', 'string', 'max:500']]);

        $document->update([
            'status' => 'rejected',
            'review_note' => $data['review_note'] ?? null,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $document->user->update(['kyc_status' => 'rejected']);

        \App\Models\AppNotification::notify($document->user_id, 'kyc', 'KYC not approved', ($data['review_note'] ?? 'Please re-upload your documents.'), route('client.dashboard'));

        return back()->with('status', "KYC rejected for {$document->user->name}.");
    }
}

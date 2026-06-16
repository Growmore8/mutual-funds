<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KycReviewController extends Controller
{
    public function index()
    {
        $documents = KycDocument::with('user')
            ->where('status', 'submitted')
            ->latest()
            ->paginate(20);

        return view('admin.kyc.index', compact('documents'));
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

        return back()->with('status', "KYC rejected for {$document->user->name}.");
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user();

        // Approved clients have full access — never show the KYC gate.
        if ($user->kyc_status === 'approved') {
            return redirect()->route('client.dashboard');
        }

        return view('kyc.show', [
            'user' => $user,
            'documents' => $user->kycDocuments()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'document_number' => ['nullable', 'string', 'max:100'],
            'front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'back' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $front = $request->file('front')->store('kyc', 'local'); // private storage
        $back = $request->file('back')->store('kyc', 'local');

        KycDocument::create([
            'user_id' => $request->user()->id,
            'doc_type' => 'identity',
            'document_number' => $data['document_number'] ?? null,
            'front_path' => $front,
            'back_path' => $back,
            'file_path' => $front,
            'status' => 'submitted',
        ]);

        $request->user()->update(['kyc_status' => 'submitted']);

        \App\Models\AppNotification::notifyAdmins('kyc', 'New KYC submission', $request->user()->name . ' uploaded their ID for review.', route('admin.kyc.index'));

        return redirect()->route('kyc.show')->with('status', 'Document uploaded. Your KYC is under review.');
    }
}

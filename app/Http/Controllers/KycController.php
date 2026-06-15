<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use Illuminate\Http\Request;

class KycController extends Controller
{
    public function show(Request $request)
    {
        return view('kyc.show', [
            'user' => $request->user(),
            'documents' => $request->user()->kycDocuments()->latest()->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'doc_type' => ['required', 'in:national_id,passport,proof_of_address'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $path = $request->file('file')->store('kyc', 'local'); // private storage

        KycDocument::create([
            'user_id' => $request->user()->id,
            'doc_type' => $data['doc_type'],
            'document_number' => $data['document_number'] ?? null,
            'file_path' => $path,
            'status' => 'submitted',
        ]);

        $request->user()->update(['kyc_status' => 'submitted']);

        return redirect()->route('kyc.show')->with('status', 'Document uploaded. Your KYC is under review.');
    }
}

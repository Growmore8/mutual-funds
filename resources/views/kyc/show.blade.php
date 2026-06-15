<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Identity Verification (KYC)') }}</h2>
    </x-slot>

    <div class="py-10">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Status banner --}}
            <div class="bg-white shadow sm:rounded-lg p-6">
                @php
                    $badge = [
                        'not_submitted' => ['Not submitted', 'bg-gray-100 text-gray-700'],
                        'submitted'     => ['Under review', 'bg-amber-100 text-amber-800'],
                        'approved'      => ['Approved', 'bg-green-100 text-green-800'],
                        'rejected'      => ['Rejected', 'bg-red-100 text-red-800'],
                    ][$user->kyc_status] ?? ['Unknown', 'bg-gray-100'];
                @endphp
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Verification status</h3>
                        <p class="text-sm text-gray-500">Full account access is granted once your KYC is approved.</p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-sm font-medium {{ $badge[1] }}">{{ $badge[0] }}</span>
                </div>
                @if ($user->kyc_status === 'approved')
                    <a href="{{ route('client.dashboard') }}" class="inline-block mt-4 px-4 py-2 bg-green-600 text-white rounded-md text-sm">Go to dashboard</a>
                @endif
            </div>

            @if (session('status'))
                <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg p-3">{{ session('status') }}</div>
            @endif

            {{-- Upload form --}}
            @if (in_array($user->kyc_status, ['not_submitted', 'rejected']))
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload a document</h3>
                <form method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="doc_type" value="Document type" />
                        <select id="doc_type" name="doc_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                            <option value="national_id">National ID</option>
                            <option value="passport">Passport</option>
                            <option value="proof_of_address">Proof of address</option>
                        </select>
                        <x-input-error :messages="$errors->get('doc_type')" class="mt-2" />
                    </div>
                    <div>
                        <x-input-label for="document_number" value="Document number (optional)" />
                        <x-text-input id="document_number" class="block mt-1 w-full" type="text" name="document_number" />
                    </div>
                    <div>
                        <x-input-label for="file" value="File (JPG, PNG or PDF, max 5 MB)" />
                        <input id="file" name="file" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                               class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700" />
                        <x-input-error :messages="$errors->get('file')" class="mt-2" />
                    </div>
                    <x-primary-button>Submit for review</x-primary-button>
                </form>
            </div>
            @endif

            {{-- Submitted documents --}}
            @if ($documents->count())
            <div class="bg-white shadow sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Your documents</h3>
                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($documents as $doc)
                        <li class="py-2 flex items-center justify-between">
                            <span class="text-gray-700">{{ ucwords(str_replace('_',' ', $doc->doc_type)) }}</span>
                            <span class="text-gray-400">{{ $doc->created_at->diffForHumans() }}</span>
                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                {{ ['submitted'=>'bg-amber-100 text-amber-800','approved'=>'bg-green-100 text-green-800','rejected'=>'bg-red-100 text-red-800'][$doc->status] ?? 'bg-gray-100' }}">
                                {{ ucfirst($doc->status) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

        </div>
    </div>
</x-app-layout>

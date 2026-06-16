<x-client-layout title="Identity Verification (KYC)">
    <div>
        <div class="max-w-3xl mx-auto space-y-6">

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
                <h3 class="text-lg font-semibold text-gray-900 mb-1">National ID / Passport</h3>
                <p class="text-sm text-gray-500 mb-4">Upload a clear photo of the <strong>front</strong> and <strong>back</strong> of your National ID or Passport.</p>
                <form method="POST" action="{{ route('kyc.store') }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <x-input-label for="document_number" value="Document number (optional)" />
                        <x-text-input id="document_number" class="block mt-1 w-full" type="text" name="document_number" placeholder="National ID / Passport number" />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="front" value="Front side" />
                            <input id="front" name="front" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                                   class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700" />
                            <x-input-error :messages="$errors->get('front')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="back" value="Back side" />
                            <input id="back" name="back" type="file" accept=".jpg,.jpeg,.png,.pdf" required
                                   class="mt-1 block w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-gray-100 file:text-gray-700" />
                            <x-input-error :messages="$errors->get('back')" class="mt-2" />
                        </div>
                    </div>
                    <p class="text-xs text-gray-400">JPG, PNG or PDF · max 5 MB each.</p>
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
                            <span class="text-gray-700">National ID / Passport{{ $doc->document_number ? ' · '.$doc->document_number : '' }}</span>
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
</x-client-layout>

<x-mail::message>
# Your GrowthCapital statement

Hi {{ $name }},

Your account statement for **{{ $label }}** is attached as a PDF.

@if(empty($pdfBytes ?? null))
*(If no PDF is attached, your statement viewer wasn't available — please download it from your dashboard.)*
@endif

Thank you for investing with GrowthCapital.

<x-mail::subcopy>
This is a no-reply message. Client ID: {{ $code }}.
</x-mail::subcopy>
</x-mail::message>

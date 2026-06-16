@props(['name' => 'country', 'value' => null, 'required' => false])
@php
    $countries = [
        'Afghanistan' => '+93', 'Albania' => '+355', 'Algeria' => '+213', 'Argentina' => '+54',
        'Armenia' => '+374', 'Australia' => '+61', 'Austria' => '+43', 'Azerbaijan' => '+994',
        'Bahrain' => '+973', 'Bangladesh' => '+880', 'Belarus' => '+375', 'Belgium' => '+32',
        'Bhutan' => '+975', 'Bolivia' => '+591', 'Brazil' => '+55', 'Brunei' => '+673',
        'Bulgaria' => '+359', 'Cambodia' => '+855', 'Cameroon' => '+237', 'Canada' => '+1',
        'Chile' => '+56', 'China' => '+86', 'Colombia' => '+57', 'Croatia' => '+385',
        'Cyprus' => '+357', 'Czechia' => '+420', 'Denmark' => '+45', 'Egypt' => '+20',
        'Estonia' => '+372', 'Ethiopia' => '+251', 'Finland' => '+358', 'France' => '+33',
        'Georgia' => '+995', 'Germany' => '+49', 'Ghana' => '+233', 'Greece' => '+30',
        'Hong Kong' => '+852', 'Hungary' => '+36', 'Iceland' => '+354', 'India' => '+91',
        'Indonesia' => '+62', 'Iran' => '+98', 'Iraq' => '+964', 'Ireland' => '+353',
        'Israel' => '+972', 'Italy' => '+39', 'Japan' => '+81', 'Jordan' => '+962',
        'Kazakhstan' => '+7', 'Kenya' => '+254', 'Kuwait' => '+965', 'Kyrgyzstan' => '+996',
        'Laos' => '+856', 'Latvia' => '+371', 'Lebanon' => '+961', 'Lithuania' => '+370',
        'Luxembourg' => '+352', 'Malaysia' => '+60', 'Maldives' => '+960', 'Malta' => '+356',
        'Mauritius' => '+230', 'Mexico' => '+52', 'Moldova' => '+373', 'Mongolia' => '+976',
        'Morocco' => '+212', 'Myanmar' => '+95', 'Nepal' => '+977', 'Netherlands' => '+31',
        'New Zealand' => '+64', 'Nigeria' => '+234', 'Norway' => '+47', 'Oman' => '+968',
        'Pakistan' => '+92', 'Palestine' => '+970', 'Panama' => '+507', 'Peru' => '+51',
        'Philippines' => '+63', 'Poland' => '+48', 'Portugal' => '+351', 'Qatar' => '+974',
        'Romania' => '+40', 'Russia' => '+7', 'Rwanda' => '+250', 'Saudi Arabia' => '+966',
        'Serbia' => '+381', 'Singapore' => '+65', 'Slovakia' => '+421', 'Slovenia' => '+386',
        'South Africa' => '+27', 'South Korea' => '+82', 'Spain' => '+34', 'Sri Lanka' => '+94',
        'Sweden' => '+46', 'Switzerland' => '+41', 'Taiwan' => '+886', 'Tanzania' => '+255',
        'Thailand' => '+66', 'Tunisia' => '+216', 'Turkey' => '+90', 'Uganda' => '+256',
        'Ukraine' => '+380', 'United Arab Emirates' => '+971', 'United Kingdom' => '+44',
        'United States' => '+1', 'Uzbekistan' => '+998', 'Vietnam' => '+84', 'Yemen' => '+967',
        'Zambia' => '+260', 'Zimbabwe' => '+263',
    ];
@endphp
<select name="{{ $name }}" {{ $required ? 'required' : '' }} {{ $attributes->merge(['class' => 'mt-1 w-full border-gray-300 rounded-md']) }}>
    <option value="">Select country…</option>
    @foreach ($countries as $c => $dial)
        <option value="{{ $c }}" @selected($value === $c)>{{ $c }} ({{ $dial }})</option>
    @endforeach
</select>

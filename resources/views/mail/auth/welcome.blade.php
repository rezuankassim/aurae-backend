<x-mail::message>
# Welcome to {{ config('app.name') }}!

Hi {{ $user->name }},

Thank you for creating an account with us. We're excited to have you on board!

You can now log in and explore everything {{ config('app.name') }} has to offer.

<x-mail::button :url="config('app.url')">
Get Started
</x-mail::button>

If you have any questions, feel free to reach out to us at any time.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

<x-mail::message>
# Your Two-Factor Authentication Code

Use the code below to finish signing in. The code expires in 10 minutes.

<x-mail::panel>
{{ $code }}
</x-mail::panel>

If you didn't try to sign in, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

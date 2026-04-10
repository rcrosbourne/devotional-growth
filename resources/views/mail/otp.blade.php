<x-mail::message>
# Your Login Code

Use the following code to sign in to Devotional Growth:

<x-mail::panel>
**{{ $code }}**
</x-mail::panel>

This code expires in 10 minutes. If you did not request this code, you can safely ignore this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

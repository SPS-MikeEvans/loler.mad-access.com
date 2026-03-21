<x-mail::message>
# Welcome to Your Client Portal

Dear {{ $client->contact_name }},

A client portal account has been created for **{{ $client->name }}**. You can use this portal to view your equipment list, flag items for inspection, and download LOLER certificates.

**Your login details are:**

| | |
|---|---|
| **Email** | {{ $user->email }} |
| **Temporary Password** | {{ $temporaryPassword }} |

You will be required to set a new password when you first log in.

<x-mail::button :url="route('login')">
Log In & Set Your Password
</x-mail::button>

<x-mail::panel>
Keep your login details secure. Do not share your password with anyone.
</x-mail::panel>

If you have any questions, please do not hesitate to get in touch.

Thanks,<br>
{{ config('app.name') }}

---

<small>You can view our terms and conditions at {{ route('liabilities.public') }}</small>
</x-mail::message>

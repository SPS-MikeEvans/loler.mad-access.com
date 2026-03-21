<x-mail::message>
# LOLER Inspection Due in the Next 30 Days

Dear {{ $kitItem->client->name }},

This is a reminder that a LOLER Thorough Examination is due within the next 30 days for the following equipment:

| | |
|---|---|
| **Equipment Type** | {{ $kitItem->kitType->name }} |
| **Asset Tag** | {{ $kitItem->asset_tag ?? '—' }} |
| **Serial No.** | {{ $kitItem->serial_no ?? '—' }} |
| **Due Date** | {{ $kitItem->next_inspection_due->format('d F Y') }} |

To remain compliant with the Lifting Operations and Lifting Equipment Regulations 1998, please log into your client portal and flag this item for inspection so our team can schedule a Thorough Examination.

<x-mail::button :url="route('portal.kit.show', $kitItem)">
View in Client Portal
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

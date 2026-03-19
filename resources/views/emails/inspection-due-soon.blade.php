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

Please arrange a LOLER Thorough Examination before the due date to remain compliant with the Lifting Operations and Lifting Equipment Regulations 1998.

<x-mail::button :url="route('clients.kit-items.show', [$kitItem->client, $kitItem])">
View Kit Item
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

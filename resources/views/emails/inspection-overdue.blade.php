<x-mail::message>
# ⚠ LOLER Inspection OVERDUE

The following equipment has passed its LOLER Thorough Examination due date and has been flagged as **Inspection Due**:

| | |
|---|---|
| **Client** | {{ $kitItem->client->name }} |
| **Equipment Type** | {{ $kitItem->kitType->name }} |
| **Asset Tag** | {{ $kitItem->asset_tag ?? '—' }} |
| **Serial No.** | {{ $kitItem->serial_no ?? '—' }} |
| **Due Date** | {{ $kitItem->next_inspection_due->format('d F Y') }} |

**Action required:** This equipment must not be used until a Thorough Examination has been completed by a competent person under LOLER 1998.

<x-mail::button :url="route('clients.kit-items.show', [$kitItem->client, $kitItem])" color="red">
View Kit Item
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>

@props([
    'amount' => 0,
    'cents' => false,
])

{{ \App\Support\Money::format($amount, $cents) }}

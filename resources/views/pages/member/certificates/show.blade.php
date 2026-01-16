<?php

use App\Models\Certificate;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public Certificate $certificate;

    public function mount(Certificate $certificate): void
    {
        // Ensure user can only view their own certificates
        if ($certificate->user_id !== Auth::id()) {
            abort(403);
        }

        $this->certificate = $certificate->load(['certificateType', 'membership.type', 'issuer']);
    }

    #[Computed]
    public function verificationUrl()
    {
        return route('certificates.verify', ['qr_code' => $this->certificate->qr_code]);
    }
}; ?>

<x-layouts::app :title="__('Certificate Details')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 p-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-4">
                <flux:button href="{{ route('certificates.index') }}" wire:navigate variant="ghost" size="sm">
                    <flux:icon name="arrow-left" class="size-4" />
                    Back
                </flux:button>
                <div>
                    <flux:heading size="xl">{{ $this->certificate->certificateType->name }}</flux:heading>
                    <flux:text class="font-mono text-zinc-500">{{ $this->certificate->certificate_number }}</flux:text>
                </div>
            </div>
            @if($this->certificate->isValid())
                <flux:badge color="green" size="lg">
                    <flux:icon name="check-circle" class="size-4" />
                    Valid
                </flux:badge>
            @elseif($this->certificate->isRevoked())
                <flux:badge color="red" size="lg">
                    <flux:icon name="x-circle" class="size-4" />
                    Revoked
                </flux:badge>
            @else
                <flux:badge color="orange" size="lg">
                    <flux:icon name="exclamation-circle" class="size-4" />
                    Expired
                </flux:badge>
            @endif
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            {{-- Certificate Preview --}}
            <div class="lg:col-span-2">
                <flux:card>
                    <flux:card.header>
                        <flux:heading size="lg">Certificate Preview</flux:heading>
                    </flux:card.header>

                    <flux:card.body>
                        {{-- Certificate Preview Card --}}
                        <div class="relative overflow-hidden rounded-xl border-4 border-emerald-600 bg-gradient-to-br from-emerald-50 to-white p-8 dark:from-emerald-950 dark:to-zinc-900">
                            {{-- Header --}}
                            <div class="mb-8 text-center">
                                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-emerald-600 text-white">
                                    <x-app-logo-icon class="size-8" />
                                </div>
                                <flux:heading size="xl" class="text-emerald-800 dark:text-emerald-200">
                                    NATIONAL RIFLE AND PISTOL ASSOCIATION
                                </flux:heading>
                                <flux:text class="text-lg text-emerald-600 dark:text-emerald-400">
                                    {{ $this->certificate->certificateType->name }}
                                </flux:text>
                            </div>

                            {{-- Certificate Body --}}
                            <div class="mb-8 text-center">
                                <flux:text class="text-zinc-600 dark:text-zinc-400">This is to certify that</flux:text>
                                <flux:heading size="2xl" class="my-3">{{ Auth::user()->name }}</flux:heading>
                                <flux:text class="text-zinc-600 dark:text-zinc-400">
                                    is a registered member of NRAPA
                                    @if($this->certificate->membership)
                                        holding a {{ $this->certificate->membership->type->name }}
                                    @endif
                                </flux:text>
                            </div>

                            {{-- Details --}}
                            <div class="mb-8 flex justify-center gap-12">
                                <div class="text-center">
                                    <flux:text class="text-sm text-zinc-500">Member Number</flux:text>
                                    <flux:text class="font-mono font-semibold">
                                        {{ $this->certificate->membership?->membership_number ?? 'N/A' }}
                                    </flux:text>
                                </div>
                                <div class="text-center">
                                    <flux:text class="text-sm text-zinc-500">Certificate Number</flux:text>
                                    <flux:text class="font-mono font-semibold">{{ $this->certificate->certificate_number }}</flux:text>
                                </div>
                            </div>

                            {{-- Validity --}}
                            <div class="flex justify-center gap-12 border-t border-emerald-200 pt-6 dark:border-emerald-800">
                                <div class="text-center">
                                    <flux:text class="text-sm text-zinc-500">Issued</flux:text>
                                    <flux:text class="font-semibold">{{ $this->certificate->issued_at->format('d F Y') }}</flux:text>
                                </div>
                                <div class="text-center">
                                    <flux:text class="text-sm text-zinc-500">Valid Until</flux:text>
                                    <flux:text class="font-semibold">
                                        @if($this->certificate->valid_until)
                                            {{ $this->certificate->valid_until->format('d F Y') }}
                                        @else
                                            Indefinite
                                        @endif
                                    </flux:text>
                                </div>
                            </div>

                            {{-- QR Code Placeholder --}}
                            <div class="absolute bottom-4 right-4">
                                <div class="flex size-20 items-center justify-center rounded-lg border-2 border-dashed border-emerald-300 bg-white dark:border-emerald-700 dark:bg-zinc-800">
                                    <flux:icon name="qr-code" class="size-12 text-emerald-400" />
                                </div>
                            </div>
                        </div>
                    </flux:card.body>

                    <flux:card.footer class="flex gap-3">
                        @if($this->certificate->file_path)
                        <flux:button variant="primary">
                            <flux:icon name="arrow-down-tray" class="size-4" />
                            Download PDF
                        </flux:button>
                        @endif
                        <flux:button variant="outline">
                            <flux:icon name="printer" class="size-4" />
                            Print
                        </flux:button>
                    </flux:card.footer>
                </flux:card>
            </div>

            {{-- Certificate Details --}}
            <div class="space-y-6">
                <flux:card>
                    <flux:card.header>
                        <flux:heading size="lg">Certificate Details</flux:heading>
                    </flux:card.header>

                    <flux:card.body>
                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm text-zinc-500">Type</dt>
                                <dd class="font-medium">{{ $this->certificate->certificateType->name }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Certificate Number</dt>
                                <dd class="font-mono font-medium">{{ $this->certificate->certificate_number }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Issue Date</dt>
                                <dd>{{ $this->certificate->issued_at->format('d F Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Valid From</dt>
                                <dd>{{ $this->certificate->valid_from->format('d F Y') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-zinc-500">Valid Until</dt>
                                <dd>
                                    @if($this->certificate->valid_until)
                                        {{ $this->certificate->valid_until->format('d F Y') }}
                                    @else
                                        <flux:badge color="amber">Indefinite</flux:badge>
                                    @endif
                                </dd>
                            </div>
                            @if($this->certificate->issuer)
                            <div>
                                <dt class="text-sm text-zinc-500">Issued By</dt>
                                <dd>{{ $this->certificate->issuer->name }}</dd>
                            </div>
                            @endif
                        </dl>
                    </flux:card.body>
                </flux:card>

                {{-- QR Verification --}}
                <flux:card>
                    <flux:card.header>
                        <flux:heading size="lg">QR Verification</flux:heading>
                    </flux:card.header>

                    <flux:card.body>
                        <div class="space-y-4">
                            <div class="flex justify-center">
                                <div class="flex size-32 items-center justify-center rounded-xl bg-white p-2 dark:bg-zinc-800">
                                    {{-- QR Code would be generated here --}}
                                    <flux:icon name="qr-code" class="size-24 text-zinc-800 dark:text-zinc-200" />
                                </div>
                            </div>
                            <flux:text class="text-center text-sm text-zinc-500">
                                Scan this QR code to verify the certificate authenticity.
                            </flux:text>
                            <flux:input
                                readonly
                                :value="$this->verificationUrl"
                                class="font-mono text-xs"
                            />
                        </div>
                    </flux:card.body>
                </flux:card>

                {{-- Revocation Info --}}
                @if($this->certificate->isRevoked())
                <flux:card class="border-red-200 dark:border-red-800">
                    <flux:card.header>
                        <div class="flex items-center gap-2 text-red-600 dark:text-red-400">
                            <flux:icon name="exclamation-triangle" class="size-5" />
                            <flux:heading size="lg">Revoked</flux:heading>
                        </div>
                    </flux:card.header>

                    <flux:card.body>
                        <dl class="space-y-3">
                            <div>
                                <dt class="text-sm text-zinc-500">Revoked On</dt>
                                <dd>{{ $this->certificate->revoked_at->format('d F Y') }}</dd>
                            </div>
                            @if($this->certificate->revocation_reason)
                            <div>
                                <dt class="text-sm text-zinc-500">Reason</dt>
                                <dd>{{ $this->certificate->revocation_reason }}</dd>
                            </div>
                            @endif
                        </dl>
                    </flux:card.body>
                </flux:card>
                @endif
            </div>
        </div>
    </div>
</x-layouts::app>

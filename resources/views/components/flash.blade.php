@php
    $messages = collect([
        'success' => session('success'),
        'error' => session('error') ?? ($errors->any() && ! request()->routeIs('login', 'register', 'password.*') ? $errors->first() : null),
        'status' => session('status') && ! request()->routeIs('login', 'password.*', 'verification.*') ? __(session('status')) : null,
    ])->filter();
@endphp

@if ($messages->isNotEmpty())
    <div class="no-print pointer-events-none fixed inset-x-0 top-24 z-[60] flex flex-col items-center gap-3 px-4">
        @foreach ($messages as $type => $message)
            <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 6000)" x-show="show"
                 x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="-translate-y-4 opacity-0"
                 x-transition:leave="transition duration-200" x-transition:leave-end="opacity-0"
                 @class([
                    'pointer-events-auto flex w-full max-w-lg items-start gap-3 rounded-2xl border px-5 py-4 shadow-soft backdrop-blur',
                    'border-emerald-200 bg-emerald-50/95 text-emerald-900' => $type !== 'error',
                    'border-rose-200 bg-rose-50/95 text-rose-900' => $type === 'error',
                 ])>
                @if ($type === 'error')
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-rose-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd"/></svg>
                @else
                    <svg class="mt-0.5 h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd"/></svg>
                @endif
                <p class="text-sm font-medium">{{ $message }}</p>
                <button @click="show = false" class="ml-auto text-current opacity-50 hover:opacity-100" aria-label="Fechar">✕</button>
            </div>
        @endforeach
    </div>
@endif

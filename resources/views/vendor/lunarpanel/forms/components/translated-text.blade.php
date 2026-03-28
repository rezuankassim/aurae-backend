<x-dynamic-component 
    :component="$getFieldWrapperView()" 
    :field="$field" 
    x-data="{ showTranslations: {{ $getExpanded() ? 'true' : 'false' }} }"
>   

    <div x-data="{ state: $wire.entangle('{{ $getStatePath() }}') }" @if($getOptionRichtext()) data-lunar-richtext @endif>
        <div class="flex items-center gap-2">
            @if ($getMoreLanguages()->count())
                <span x-show="showTranslations"
                    class="items-center w-8 place-content-center text-xs font-normal p-2 rounded shadow-sm bg-gray-200 text-gray-400 dark:bg-white/5 dark:text-white uppercase">
                    {{ Str::upper($getDefaultLanguage()->code) }}
                </span>
            @endif
            <x-filament::input.wrapper class="w-full">
                {{ $getComponentByLanguage($getDefaultLanguage()) }}
            </x-filament::input.wrapper>
        </div>

        @if ($getMoreLanguages()->count())
            @foreach ($getMoreLanguages() as $language)
                <div x-show="showTranslations" class="flex items-center gap-2 mt-4">
                    <span x-show="showTranslations"
                        class="w-8 text-xs font-normal p-2 rounded shadow-sm bg-gray-200 text-gray-400 dark:bg-white/5 dark:text-white uppercase">
                        {{ Str::upper($language->code) }}
                    </span>
                    <x-filament::input.wrapper class="w-full">
                        {{ $getComponentByLanguage($language) }}
                    </x-filament::input.wrapper>    
                </div>        
            @endforeach
        @endif
    </div>

    @if ($getMoreLanguages()->count())
        <div class="mt-2">
            <x-filament::button 
                x-on:click.prevent="showTranslations = !showTranslations" 
                size="xs" 
                color="gray"
                >
                <x-filament::icon 
                    alias="lunar::languages" 
                    @class(['w-3.5 h-3.5 inline-flex'])
                />
                <span class="ml-2">
                    {{ __('lunarpanel::fieldtypes.translatedtext.form.locales') }}
                </span>
            </x-filament::button>
            </button>
        </div>
    @endif

    {{-- Direct image upload for rich text editors --}}
    {{-- Lunar's TranslatedRichEditor is not in Filament's component tree, --}}
    {{-- so Filament's built-in file attachment lookup always returns null. --}}
    {{-- This script intercepts Trix attachments in capture phase (before --}}
    {{-- Filament's Alpine handler) and uploads them directly to a permanent endpoint. --}}
    @if ($getOptionRichtext())
        @once
            <script>
                if (!window.__lunarRichtextUploadRegistered) {
                    window.__lunarRichtextUploadRegistered = true;

                    // Use capture phase so this fires BEFORE Filament's Alpine handler.
                    // Stop propagation to prevent Filament's handler from overwriting
                    // the URL with null (since TranslatedRichEditor is not in
                    // Filament's component tree).
                    document.addEventListener('trix-attachment-add', function (event) {
                        const attachment = event.attachment;
                        if (!attachment.file) return;

                        // Only intercept events from Trix editors inside Lunar's
                        // translated-text component (marked with data attribute).
                        const trixEditor = event.target;
                        if (!trixEditor.closest('[data-lunar-richtext]')) return;

                        // Stop the event from reaching Filament's Alpine handler
                        event.stopPropagation();

                        const formData = new FormData();
                        formData.append('image', attachment.file);

                        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
                            || document.querySelector('input[name="_token"]')?.value
                            || '';

                        fetch('/admin/editor-upload', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        })
                        .then(response => {
                            if (!response.ok) throw new Error('Upload failed');
                            return response.json();
                        })
                        .then(data => {
                            attachment.setAttributes({
                                url: data.url,
                                href: data.url,
                            });
                        })
                        .catch(error => {
                            console.error('Editor image upload failed:', error);
                            attachment.remove();
                        });
                    }, true); // capture phase
                }
            </script>
        @endonce
    @endif

</x-dynamic-component>

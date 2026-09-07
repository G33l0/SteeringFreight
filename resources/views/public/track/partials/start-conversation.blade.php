<div x-data="{ open: {{ $errors->any() ? 'true' : 'false' }} }" class="mt-5">
    <button type="button" @click="open = ! open" class="btn btn-dark btn-sm" x-show="! open">
        <x-icon name="chat" class="h-4 w-4" />
        Contact shipping team
    </button>

    <form x-show="open" x-cloak method="POST"
          action="{{ route('track.chat.store', ['tracking_number' => $shipment->tracking_number]) }}"
          class="space-y-4 border border-ink-100 bg-white p-5">
        @csrf
        <x-form.honeypot />

        <div class="grid gap-4 sm:grid-cols-2">
            <x-form.field name="contact_name" label="Your name" :required="true">
                <input type="text" id="contact_name" name="contact_name" value="{{ old('contact_name') }}" required
                       maxlength="120" class="input" aria-invalid="{{ $errors->has('contact_name') ? 'true' : 'false' }}">
            </x-form.field>

            <x-form.field name="contact_email" label="Email address" :required="true"
                          help="We use this to let you know when the team replies.">
                <input type="email" id="contact_email" name="contact_email" value="{{ old('contact_email') }}" required
                       maxlength="180" class="input" aria-invalid="{{ $errors->has('contact_email') ? 'true' : 'false' }}">
            </x-form.field>
        </div>

        <x-form.field name="subject" label="Subject">
            <input type="text" id="subject" name="subject" value="{{ old('subject') }}" maxlength="180" class="input">
        </x-form.field>

        <x-form.field name="body" label="Message" :required="true">
            <textarea id="body" name="body" rows="4" required maxlength="{{ config('portlane.chat.message_max_length') }}"
                      class="textarea" aria-invalid="{{ $errors->has('body') ? 'true' : 'false' }}">{{ old('body') }}</textarea>
        </x-form.field>

        <div class="flex items-center gap-3">
            <button type="submit" class="btn btn-primary btn-sm">Send message</button>
            <button type="button" @click="open = false" class="text-sm text-ink-600 hover:text-ink-900">Cancel</button>
        </div>
    </form>
</div>

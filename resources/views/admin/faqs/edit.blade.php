<x-layouts.admin title="Edit question">
    <form method="POST" action="{{ route('admin.faqs.update', $faq) }}" class="max-w-3xl">
        @csrf
        @method('PUT')
        @include('admin.faqs.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Save question</button>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>

    <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" class="mt-4" onsubmit="return confirm('Delete this question?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="text-sm text-alert-700 hover:underline">Delete this question</button>
    </form>
</x-layouts.admin>

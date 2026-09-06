<x-layouts.admin title="New question">
    <form method="POST" action="{{ route('admin.faqs.store') }}" class="max-w-3xl">
        @csrf
        @include('admin.faqs.form')
        <div class="mt-6 flex gap-3">
            <button type="submit" class="btn btn-primary">Add question</button>
            <a href="{{ route('admin.faqs.index') }}" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</x-layouts.admin>

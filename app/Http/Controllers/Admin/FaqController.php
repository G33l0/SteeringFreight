<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class FaqController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(): View
    {
        $this->authorize('faqs.view');

        return view('admin.faqs.index', ['faqs' => Faq::ordered()->get()]);
    }

    public function create(): View
    {
        $this->authorize('faqs.manage');

        return view('admin.faqs.create', ['faq' => new Faq(['is_published' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('faqs.manage');

        $faq = Faq::create($this->validated($request));

        $this->audit->record('faq.created', $faq, 'Created FAQ entry');

        return redirect()->route('admin.faqs.index')->with('status', 'Question added.');
    }

    public function show(Faq $faq): RedirectResponse
    {
        return redirect()->route('admin.faqs.edit', $faq);
    }

    public function edit(Faq $faq): View
    {
        $this->authorize('faqs.manage');

        return view('admin.faqs.edit', ['faq' => $faq]);
    }

    public function update(Request $request, Faq $faq): RedirectResponse
    {
        $this->authorize('faqs.manage');

        $faq->update($this->validated($request));

        $this->audit->record('faq.updated', $faq, 'Updated FAQ entry', AuditLogger::changes($faq));

        return redirect()->route('admin.faqs.index')->with('status', 'Question saved.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->authorize('faqs.manage');

        $faq->delete();

        $this->audit->record('faq.deleted', null, 'Deleted FAQ entry');

        return redirect()->route('admin.faqs.index')->with('status', 'Question deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:300'],
            'answer' => ['required', 'string', 'max:5000'],
            'category' => ['nullable', 'string', 'max:80'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_published' => ['boolean'],
            'show_on_home' => ['boolean'],
        ]);

        $data['sort_order'] ??= 0;
        $data['is_published'] = $request->boolean('is_published');
        $data['show_on_home'] = $request->boolean('show_on_home');

        return $data;
    }
}

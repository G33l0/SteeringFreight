<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\AuditLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function index(Request $request): View
    {
        $this->authorize('customers.view');

        return view('admin.customers.index', [
            'customers' => Customer::withCount('shipments')
                ->search($request->string('q')->trim()->value())
                ->orderBy('name')
                ->paginate((int) config('portlane.per_page.admin'))
                ->withQueryString(),
            'search' => $request->string('q')->value(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('customers.manage');

        return view('admin.customers.create', ['customer' => new Customer]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('customers.manage');

        $customer = Customer::create($this->validated($request) + ['created_by' => $request->user()->getKey()]);

        $this->audit->record('customer.created', $customer, "Created customer {$customer->name}");

        return redirect()->route('admin.customers.show', $customer)->with('status', 'Customer created.');
    }

    public function show(Customer $customer): View
    {
        $this->authorize('customers.view');

        $customer->load(['shipments.status']);

        return view('admin.customers.show', ['customer' => $customer]);
    }

    public function edit(Customer $customer): View
    {
        $this->authorize('customers.manage');

        return view('admin.customers.edit', ['customer' => $customer]);
    }

    public function update(Request $request, Customer $customer): RedirectResponse
    {
        $this->authorize('customers.manage');

        $customer->update($this->validated($request, $customer));

        $this->audit->record('customer.updated', $customer, "Updated customer {$customer->name}", AuditLogger::changes($customer));

        return redirect()->route('admin.customers.show', $customer)->with('status', 'Customer updated.');
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('customers.manage');

        if ($customer->shipments()->exists()) {
            return back()->withErrors(['customer' => 'This customer still has shipments and cannot be deleted.']);
        }

        $name = $customer->name;
        $customer->delete();

        $this->audit->record('customer.deleted', null, "Deleted customer {$name}");

        return redirect()->route('admin.customers.index')->with('status', 'Customer deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Customer $customer = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'company' => ['nullable', 'string', 'max:160'],
            'email' => ['nullable', 'email:filter', 'max:180', Rule::unique('customers', 'email')->ignore($customer?->getKey())],
            'phone' => ['nullable', 'string', 'max:40'],
            'address_line_1' => ['nullable', 'string', 'max:200'],
            'address_line_2' => ['nullable', 'string', 'max:200'],
            'city' => ['nullable', 'string', 'max:120'],
            'region' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:40'],
            'country' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'notifications_enabled' => ['boolean'],
        ]);

        $data['notifications_enabled'] = $request->boolean('notifications_enabled');

        return $data;
    }
}

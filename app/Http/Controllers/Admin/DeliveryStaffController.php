<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeliveryAssignment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DeliveryStaffController extends Controller
{
    public function index(): View
    {
        $staff = User::where('role', 'delivery')->orderBy('name')->get();

        $counts = DeliveryAssignment::query()
            ->selectRaw('delivery_user_id, status, count(*) as aggregate')
            ->groupBy('delivery_user_id', 'status')
            ->get()
            ->groupBy('delivery_user_id')
            ->map(fn ($rows) => $rows->pluck('aggregate', 'status'));

        return view('delivery.admin.staff.index', compact('staff', 'counts'));
    }

    public function create(): View
    {
        return view('delivery.admin.staff.form', [
            'staff' => new User(['role' => 'delivery', 'active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'delivery',
            'active' => true,
        ]);

        return redirect()
            ->route('delivery.admin.staff.index')
            ->with('status', 'Delivery account created.');
    }

    public function edit(User $user): View
    {
        abort_unless($user->role === 'delivery', 404);

        return view('delivery.admin.staff.form', ['staff' => $user]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        abort_unless($user->role === 'delivery', 404);

        $data = $this->validated($request, $user);

        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->phone = $data['phone'];

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return redirect()
            ->route('delivery.admin.staff.index')
            ->with('status', 'Delivery account updated.');
    }

    public function toggle(User $user): RedirectResponse
    {
        abort_unless($user->role === 'delivery', 404);

        $user->active = !$user->active;
        $user->save();

        return redirect()
            ->route('delivery.admin.staff.index')
            ->with('status', $user->active ? 'Delivery account enabled.' : 'Delivery account disabled.');
    }

    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === 'delivery', 404);

        if (DeliveryAssignment::where('delivery_user_id', $user->id)->exists()) {
            return redirect()
                ->route('delivery.admin.staff.index')
                ->with('error', 'This delivery account has delivery history. Disable it instead of removing it.');
        }

        $user->delete();

        return redirect()
            ->route('delivery.admin.staff.index')
            ->with('status', 'Delivery account removed.');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user?->id),
            ],
            'phone' => ['required', 'string', 'max:50'],
            'password' => [
                $user === null ? 'required' : 'nullable',
                'string',
                'min:8',
                'confirmed',
            ],
        ], [
            'name.required' => 'Enter the delivery person\'s name.',
            'email.required' => 'Enter the delivery person\'s email.',
            'email.email' => 'Enter a valid email address.',
            'email.unique' => 'This email is already used by another account.',
            'phone.required' => 'Enter the delivery person\'s phone number.',
            'password.required' => 'Set a password for the delivery person.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The two passwords do not match.',
        ]);
    }
}
<?php

namespace App\Http\Controllers\Lube;

use App\Http\Controllers\Controller;
use App\Models\Lube\LubeServiceRecord;
use App\Models\Shop;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LubeReminderController extends Controller
{
    protected function getActiveShopId(): ?int
    {
        $user = auth()->user();
        if ($user->shop_id) {
            return $user->shop_id;
        }

        $sessionShopId = session('dashboard_shop_id');
        if ($sessionShopId) {
            return (int) $sessionShopId;
        }

        return Shop::where('status', 'active')->first()?->id;
    }

    public function index(Request $request): View
    {
        $shopId = $this->getActiveShopId();
        $shop = Shop::find($shopId);

        $filter = $request->query('tab', 'due_soon');

        $baseQuery = LubeServiceRecord::forShop($shopId)
            ->with(['vehicle', 'technician']);

        if ($filter === 'overdue') {
            $baseQuery->where('next_due_date', '<', now()->toDateString());
        } elseif ($filter === 'contacted') {
            $baseQuery->where('notes', 'like', '%[Contacted%');
        } else {
            // Due soon: within next 14 days or recent
            $baseQuery->whereBetween('next_due_date', [now()->toDateString(), now()->addDays(14)->toDateString()]);
        }

        $reminders = $baseQuery->orderBy('next_due_date')->paginate(20)->withQueryString();

        // Counts for tabs
        $dueSoonCount = LubeServiceRecord::forShop($shopId)
            ->whereBetween('next_due_date', [now()->toDateString(), now()->addDays(14)->toDateString()])
            ->count();

        $overdueCount = LubeServiceRecord::forShop($shopId)
            ->where('next_due_date', '<', now()->toDateString())
            ->count();

        $contactedCount = LubeServiceRecord::forShop($shopId)
            ->where('notes', 'like', '%[Contacted%')
            ->count();

        return view('lube.reminders.index', compact(
            'shop',
            'reminders',
            'filter',
            'dueSoonCount',
            'overdueCount',
            'contactedCount'
        ));
    }

    public function markContacted(Request $request, LubeServiceRecord $serviceRecord): RedirectResponse
    {
        $currentNotes = $serviceRecord->notes ?? '';
        $stamp = "[Contacted on " . now()->format('d-M-Y H:i') . " by " . auth()->user()->name . "]";
        $serviceRecord->update([
            'notes' => trim($currentNotes . ' ' . $stamp),
        ]);

        return back()->with('success', 'Customer marked as contacted!');
    }
}

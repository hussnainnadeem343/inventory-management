<?php

namespace App\Http\Controllers;

use App\Models\DemoRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    /**
     * Show the SaaS public landing page.
     */
    public function index(): View
    {
        return view('landing.index');
    }

    /**
     * Store an inquiry or demo booking request.
     */
    public function storeInquiry(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'business_name' => ['required', 'string', 'max:150'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'phone' => ['required', 'string', 'min:7', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'city' => ['nullable', 'string', 'max:100'],
            'branches_count' => ['nullable', 'string', 'max:50'],
            'preferred_plan' => ['nullable', 'string', 'max:50'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $validated['ip_address'] = $request->ip();
        $validated['status'] = 'pending';

        $demoRequest = DemoRequest::create($validated);

        // Pre-composed WhatsApp URL for fast instant conversation
        $waPhone = config('app.whatsapp_number', '923114670195');
        $waMessage = "Hello DenFin Team! I just submitted a demo request.\n"
            . "• Name: {$demoRequest->name}\n"
            . "• Business: {$demoRequest->business_name}\n"
            . "• Category: " . ($demoRequest->business_type ?? 'General Retail') . "\n"
            . "• City: " . ($demoRequest->city ?? 'N/A') . "\n"
            . "• Phone: {$demoRequest->phone}";

        $whatsappUrl = 'https://wa.me/' . preg_replace('/[^0-9]/', '', $waPhone) . '?text=' . urlencode($waMessage);

        $successMsg = 'Thank you! Your demo request has been received. Our team will contact you shortly.';

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $successMsg,
                'whatsapp_url' => $whatsappUrl,
                'inquiry_id' => $demoRequest->id,
            ]);
        }

        return redirect()->to(url('/#demo-section'))
            ->with('demo_success', $successMsg)
            ->with('whatsapp_url', $whatsappUrl);
    }
}

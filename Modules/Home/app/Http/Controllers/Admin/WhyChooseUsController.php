<?php

namespace Modules\Home\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Home\Models\HomeWhyChooseUs;

class WhyChooseUsController extends Controller
{
    /**
     * Show the Why Choose Us section management page.
     */
    public function index(): View
    {
        $whyChooseUs = HomeWhyChooseUs::query()->latest('id')->first();

        return view('home::admin.why-choose-us.index', [
            'whyChooseUs' => $whyChooseUs,
            'defaults' => HomeWhyChooseUs::DEFAULTS,
        ]);
    }

    /**
     * Update (or create) the Why Choose Us section content.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'small_title' => ['nullable', 'string', 'max:255'],
            'title' => ['nullable', 'string', 'max:255'],
            'left_paragraph_1' => ['nullable', 'string'],
            'left_paragraph_2' => ['nullable', 'string'],
            'right_paragraph_1' => ['nullable', 'string'],
            'right_paragraph_2' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $whyChooseUs = HomeWhyChooseUs::query()->latest('id')->first() ?? new HomeWhyChooseUs;

        $whyChooseUs->fill($validated)->save();

        return redirect()
            ->route('admin.home.why_choose_us.index')
            ->with('success', 'Why Choose Us section updated successfully.');
    }
}

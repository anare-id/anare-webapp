<?php

namespace App\Http\Controllers;

use App\Support\LocalizedContent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    protected string $catalog = 'skin';

    protected function setting(string $key, mixed $default = null): mixed
    {
        return config($this->catalog.'.'.$key, $default);
    }

    protected function pageData(): array
    {
        return [
            'serviceKey' => $this->catalog,
            'serviceName' => $this->setting('name'),
            'heroTitle' => LocalizedContent::text('catalog.'.$this->catalog.'.title', $this->setting('hero_title')),
            'heroDescription' => $this->setting('hero_description'),
        ];
    }

    public function index(Request $request): View
    {
        $categories = $this->setting('categories');
        $categories = array_map(fn (string $label, string $key) => LocalizedContent::text('categories.'.$key, $label), $categories, array_keys($categories));
        $categories = array_combine(array_keys($this->setting('categories')), $categories);
        $default = $this->setting('default_category', array_key_first($categories));
        $category = $request->query('category', $default);
        if (is_string($category)) $category = $this->setting('category_aliases', [])[$category] ?? $category;
        abort_unless(is_string($category) && isset($categories[$category]), 404);

        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($page === false, 404);

        $items = collect($this->setting('treatments'))
            ->map(fn (array $item, string $slug) => LocalizedContent::treatment($this->catalog, ['slug' => $slug, ...$item]));
        if ($category !== 'all') $items = $items->where('category', $category);
        $items = $items->values();

        $perPage = max(1, (int) $this->setting('per_page', 6));
        $lastPage = max(1, (int) ceil($items->count() / $perPage));
        abort_if($page > $lastPage, 404);

        $treatments = new LengthAwarePaginator(
            $items->forPage($page, $perPage)->values(), $items->count(), $perPage, $page,
            ['path' => route($this->catalog), 'query' => ['category' => $category], 'fragment' => 'treatments'],
        );
        $slots = max(1, (int) $this->setting('pagination_slots', 5));
        $start = max(1, min($page - intdiv($slots, 2), max(1, $lastPage - $slots + 1)));

        return view('pages.treatments.catalog', [
            ...$this->pageData(),
            'categories' => $categories,
            'activeCategory' => $category,
            'defaultCategory' => $default,
            'treatments' => $treatments,
            'pageNumbers' => range($start, $start + $slots - 1),
            'showPagination' => $this->setting('show_pagination', true) || $treatments->hasPages(),
        ]);
    }

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $aliases = $this->setting('aliases', []);
        if (isset($aliases[$slug])) {
            return redirect()->route($this->catalog.'.detail', ['slug' => $aliases[$slug]], 301);
        }

        $items = [...$this->setting('legacy_treatments', []), ...$this->setting('treatments')];
        abort_unless(isset($items[$slug]), 404);
        $treatment = LocalizedContent::treatment($this->catalog, ['slug' => $slug, ...$items[$slug]]);
        $default = $this->setting('default_category', $treatment['category']);
        $category = $request->query('category', $default);
        if (is_string($category)) $category = $this->setting('category_aliases', [])[$category] ?? $category;
        if (!is_string($category) || !array_key_exists($category, $this->setting('categories'))) $category = $default;

        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $listed = $this->setting('treatments');
        $total = $category === 'all' ? count($listed) : collect($listed)->where('category', $category)->count();
        $lastPage = max(1, (int) ceil($total / max(1, (int) $this->setting('per_page', 6))));

        $conditions = $treatment['conditions'] ?? collect(['pre', 'post', 'downtime'])->mapWithKeys(fn (string $key) => [
            __('site.treatment.conditions.'.$key.'.label') => __('site.treatment.conditions.'.$key.'.body', ['service' => $this->setting('name')]),
        ])->all();

        return view('pages.treatments.detail', [
            ...$this->pageData(),
            'treatment' => $treatment,
            'backUrl' => route($this->catalog, ['category' => $category, 'page' => min($page, $lastPage)]).'#treatments',
            'comparison' => $treatment['comparison'] ?? $this->setting('comparison'),
            'conditions' => $conditions,
        ]);
    }
}

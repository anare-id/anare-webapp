<?php

namespace App\Http\Controllers;

use App\Models\Treatment;
use App\Services\WebsiteContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TreatmentController extends Controller
{
    protected string $catalog = 'skin';

    public function __construct(private readonly WebsiteContent $website)
    {
    }

    private function pageData(): array
    {
        return [
            'serviceKey' => $this->catalog,
            'serviceName' => $this->website->text($this->catalog, 'name'),
            'heroTitle' => $this->website->text($this->catalog, 'title'),
            'heroDescription' => $this->website->text($this->catalog, 'description'),
            'heroSlides' => $this->website->heroes($this->catalog),
        ];
    }

    private function treatments(): Builder
    {
        return Treatment::published()->with('category')->where('service', $this->catalog)->where('legacy', false);
    }

    private function item(Treatment $treatment): array
    {
        $item = $this->website->localize($treatment, ['title', 'description', 'subtitle', 'duration', 'pre', 'post', 'downtime']);
        $item['name'] = $item['title'];
        $item['category'] = $treatment->category?->slug;
        $item['catalog_image'] = $item['catalog_image'] ?: $item['image'];
        return $item;
    }

    public function index(Request $request): View
    {
        $categories = $this->website->categories($this->catalog);
        $default = array_key_exists('all', $categories) ? 'all' : array_key_first($categories);
        $category = $request->query('category', $default);
        abort_unless(($category === null && $categories === []) || (is_string($category) && array_key_exists($category, $categories)), 404);
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($page === false, 404);

        $query = $this->treatments();
        if ($category !== null && $category !== 'all') {
            $query->whereHas('category', fn (Builder $query): Builder => $query->where('slug', $category));
        }
        $treatments = $query->orderBy('sort_order')->orderBy('id')
            ->paginate(max(1, (int) $this->website->setting($this->catalog, 'per_page', 6)));
        abort_if($page > $treatments->lastPage(), 404);
        $treatments->appends(['category' => $category])->fragment('treatments');
        $treatments->through(fn (Treatment $treatment): array => $this->item($treatment));
        $slots = min(5, $treatments->lastPage());
        $start = max(1, min($page - intdiv($slots, 2), $treatments->lastPage() - $slots + 1));

        return view('pages.treatments.catalog', [
            ...$this->pageData(), 'categories' => $categories, 'activeCategory' => $category,
            'defaultCategory' => $default, 'treatments' => $treatments,
            'pageNumbers' => range($start, $start + $slots - 1), 'showPagination' => $treatments->hasPages(),
        ]);
    }

    public function show(Request $request, string $slug): View
    {
        $record = Treatment::published()->with('category')->where('service', $this->catalog)->where('slug', $slug)->firstOrFail();
        $treatment = $this->item($record);
        $categories = $this->website->categories($this->catalog);
        $default = array_key_exists('all', $categories) ? 'all' : array_key_first($categories);
        $category = $request->query('category', $record->category?->slug ?? $default);
        if (!is_string($category) || !array_key_exists($category, $categories)) {
            $category = $default;
        }
        $query = $this->treatments();
        if ($category !== null && $category !== 'all') {
            $query->whereHas('category', fn (Builder $query): Builder => $query->where('slug', $category));
        }
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 1;
        $lastPage = max(1, (int) ceil($query->count() / max(1, (int) $this->website->setting($this->catalog, 'per_page', 6))));
        $conditions = [];
        foreach (['pre', 'post', 'downtime'] as $key) {
            $conditions[__('site.treatment.conditions.'.$key.'.label')] = $treatment[$key];
        }

        return view('pages.treatments.detail', [
            ...$this->pageData(), 'treatment' => $treatment,
            'backUrl' => route($this->catalog, ['category' => $category, 'page' => min($page, $lastPage)]).'#treatments',
            'comparison' => [
                'before' => \App\Support\OptimizedImages::path($record->comparison_before), 'after' => \App\Support\OptimizedImages::path($record->comparison_after),
                'illustrative' => $record->comparison_illustrative, 'image_position' => $record->comparison_position,
            ],
            'conditions' => $conditions,
        ]);
    }
}

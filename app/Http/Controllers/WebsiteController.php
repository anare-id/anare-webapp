<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Doctor;
use App\Services\WebsiteContent;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WebsiteController extends Controller
{
    public function __construct(private readonly WebsiteContent $website)
    {
    }

    public function doctors(Request $request): View
    {
        return view('pages.doctors', [
            'doctors' => Doctor::published()->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (Doctor $doctor): array => $this->website->doctor($doctor))->all(),
            'heroSlides' => $this->website->heroes('doctors'),
        ]);
    }

    public function doctor(string $slug): View
    {
        $doctor = Doctor::published()->where('slug', $slug)->firstOrFail();
        return view('pages.doctor-detail', [
            'doctor' => $this->website->doctor($doctor),
            'otherDoctors' => Doctor::published()->whereKeyNot($doctor->id)->orderBy('sort_order')->orderBy('id')->get()
                ->map(fn (Doctor $other): array => $this->website->doctor($other))->all(),
        ]);
    }

    public function articles(Request $request): View
    {
        $categories = $this->website->categories('articles');
        $category = $request->query('category', 'all');
        abort_unless(is_string($category) && ($category === 'all' || array_key_exists($category, $categories)), 404);
        $page = filter_var($request->query('page', 1), FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        abort_if($page === false, 404);
        $query = Article::published()->with('category');
        if ($category !== 'all') {
            $query->whereHas('category', fn (Builder $query): Builder => $query->where('slug', $category));
        }
        $articles = $query->orderBy('sort_order')->orderByDesc('date')->orderBy('id')->paginate(9)->withQueryString();
        abort_if($page > $articles->lastPage(), 404);
        $articles->through(fn (Article $article): array => $this->website->article($article));
        return view('pages.articles', ['articles' => $articles, 'categories' => $categories, 'activeCategory' => $category]);
    }

    public function article(string $slug): View
    {
        $article = Article::published()->with('category')->where('slug', $slug)->firstOrFail();
        return view('pages.article-detail', ['article' => $this->website->article($article)]);
    }
}

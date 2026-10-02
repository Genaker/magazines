<?php

namespace App\Http\Controllers;

use App\Services\Search\SearchService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SearchController extends Controller
{
    public function __construct(private SearchService $search) {}

    public function index(Request $request): View
    {
        $query = trim((string) $request->get('q', ''));

        $posts = collect();
        $authors = collect();
        $magazines = collect();

        if ($query !== '') {
            $posts = $this->search->searchPosts($query);
            $authors = $this->search->searchAuthors($query);
            $magazines = $this->search->searchMagazines($query);
        }

        return view('search.index', compact('query', 'posts', 'authors', 'magazines'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Enums\CategoryRequestStatus;
use App\Models\CategoryRequest;
use App\Notifications\CategoryRequestReceived;
use App\Notifications\CategoryRequestSubmitted;
use App\Services\AdminNotifier;
use App\Support\MailRecipient;
use App\Support\NotificationSender;
use App\Support\Slugger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryRequestController extends Controller
{
    public function create(): View
    {
        return view('category-requests.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $pendingExists = CategoryRequest::query()
            ->where('user_id', $request->user()->id)
            ->where('name', $data['name'])
            ->where('status', CategoryRequestStatus::Pending)
            ->exists();

        abort_if($pendingExists, 422, 'You already have a pending request for this category.');

        $categoryRequest = CategoryRequest::query()->create([
            'user_id' => $request->user()->id,
            'name' => $data['name'],
            'slug' => Slugger::unique($data['name'], new CategoryRequest, 'slug'),
            'reason' => $data['reason'],
            'status' => CategoryRequestStatus::Pending,
        ]);

        AdminNotifier::notify(new CategoryRequestSubmitted($categoryRequest->load('user')));

        if (MailRecipient::canReceiveEmail($request->user())) {
            NotificationSender::send($request->user(), new CategoryRequestReceived($categoryRequest));
        }

        return redirect()->route('category-requests.mine')->with('status', 'Category request submitted.');
    }

    public function mine(Request $request): View
    {
        $requests = $request->user()
            ->categoryRequests()
            ->latest()
            ->paginate(15);

        return view('category-requests.mine', compact('requests'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /** Only buyers who actually chatted with the seller can leave a review (stops fake reviews). */
    public function store(Request $request, User $user)
    {
        $viewer = $request->user();
        abort_if($viewer->id === $user->id, 403);

        $conversation = Conversation::where('buyer_id', $viewer->id)->where('seller_id', $user->id)->latest('last_message_at')->first();
        if (! $conversation) {
            return back()->with('error', 'You can review a seller after you have chatted with them about an ad.');
        }

        $data = $request->validate([
            'rating' => ['required', 'integer', 'between:1,5'],
            'comment' => ['nullable', 'string', 'max:800'],
        ]);

        Review::updateOrCreate(
            ['seller_id' => $user->id, 'reviewer_id' => $viewer->id, 'listing_id' => $conversation->listing_id],
            $data
        );

        // Seller reputation: trust score blends rating, verification and account age
        $avg = $user->reviewsReceived()->where('is_visible', true)->avg('rating') ?: 3;
        $score = 30 + ($avg - 1) * 12 + ($user->isVerified() ? 15 : 0) + min(7, (int) $user->created_at->diffInMonths(now()));
        $user->forceFill(['trust_score' => (int) max(0, min(100, $score))])->save();

        return back()->with('status', 'Thanks for your review!');
    }
}

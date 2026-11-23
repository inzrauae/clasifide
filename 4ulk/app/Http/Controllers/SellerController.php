<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function show(Request $request, User $user)
    {
        abort_if($user->isBanned(), 404);

        if ($store = $user->stores()->where('is_active', true)->first()) {
            if (! $request->has('reviews')) {
                return redirect()->route('stores.show', $store);
            }
        }

        $listings = $user->listings()->live()->with(['images', 'category'])->ranked()->paginate(20);
        $reviews = $user->reviewsReceived()->where('is_visible', true)->with('reviewer')->latest()->limit(20)->get();

        $viewer = $request->user();
        $canReview = $viewer && $viewer->id !== $user->id
            && \App\Models\Conversation::where('buyer_id', $viewer->id)->where('seller_id', $user->id)->exists()
            && ! $user->reviewsReceived()->where('reviewer_id', $viewer->id)->exists();

        return view('sellers.show', [
            'seller' => $user,
            'listings' => $listings,
            'reviews' => $reviews,
            'rating' => $user->ratingAverage(),
            'canReview' => $canReview,
            'store' => $store,
        ]);
    }
}

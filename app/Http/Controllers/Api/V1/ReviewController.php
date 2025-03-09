<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Repository\IReviewRepository;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    protected $reviewRepository;

    public function __construct(IReviewRepository $reviewRepository)
    {
        $this->reviewRepository = $reviewRepository;
    }

    public function store(Request $request)
    {
        // dd($request->all());
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|max:500',
        ]);
        

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
    
        //dd('Validation Passed', $request->all());
    
        $data = [
            'user_id' => Auth::id(),
            'product_id' => $validator->validated()['product_id'],
            'rating' => $validator->validated()['rating'],
            'comment' => $validator->validated()['comment'],
        ];

        //dd($data);

        $review = $this->reviewRepository->createReview($data);

        if (isset($review['error'])) {
            return response()->json([
                'success' => false,
                'message' => $review['error'],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'review' => $review,
        ]);
    }

    public function getReviews($productId)
    {
        $reviews = $this->reviewRepository->getReviewsByProduct($productId);

        return response()->json([
            'success' => true,
            'reviews' => $reviews,
        ]);
    }
    
}

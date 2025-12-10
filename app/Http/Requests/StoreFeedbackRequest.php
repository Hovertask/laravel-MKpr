<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // anyone can leave feedback
    }

    public function rules(): array
    {
        return [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'required|string|min:5',

            // Required if user is NOT authenticated
            'visitor_name'  => 'required_without:user_id|string|max:120',
            'visitor_email' => 'required_without:user_id|email|max:120',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'auth' => auth()->check(),
        ]);
    }

    public function rules(): array
    {
        return [
            'rating'        => 'required|integer|min:1|max:5',
            'comment'       => 'required|string|min:5',

            'visitor_name'  => 'required_unless:auth,true|string|max:120',
            'visitor_email' => 'required_unless:auth,true|email|max:120',
        ];
    }
}

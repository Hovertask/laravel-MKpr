<?php

namespace App\DTOs;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ResellBudgetPaymentDto
{
    public string $email;
    public float $amount;
    public string $type;
    public array $products;

    public function __construct(array $data)
    {
        $this->validate($data);

        $this->email = $data['email'];
        $this->amount = $data['amount'];
        $this->type = $data['type'];
        $this->products = $data['metadata']['products'];
    }

    private function validate(array $data)
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|exists:users,email',
            'amount' => 'required|numeric|min:0',
            'type' => 'required|string|in:resell_budget',
            'metadata' => 'required|array',
            'metadata.products' => 'required|array|min:1',
            'metadata.products.*.id' => 'required|integer|exists:products,id',
            'metadata.products.*.name' => 'required|string',
            'metadata.products.*.budget' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        // Additional validation: check if product names match
        foreach ($data['metadata']['products'] as $product) {
            $existingProduct = \App\Models\Product::find($product['id']);
            if ($existingProduct && $existingProduct->name !== $product['name']) {
                throw ValidationException::withMessages([
                    'metadata.products' => 'Product name does not match for ID ' . $product['id']
                ]);
            }
        }

        // Check total budget matches amount
        $totalBudget = array_sum(array_column($data['metadata']['products'], 'budget'));
        if ($totalBudget != $data['amount']) {
            throw ValidationException::withMessages([
                'amount' => 'Total product budgets do not match the payment amount'
            ]);
        }
    }
}
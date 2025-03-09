<?php

namespace App\Models;

use App\Models\InitializeDeposit;
use Illuminate\Database\Eloquent\Model;

class InitializeDeposit extends Model
{
    protected $fillable = [
        'user_id',
        'amount',
        'reference',
        'currency',
        'method',
        'trx',
        'status',
    ];

    public static function generateTrx($len = 10)
    {
        $chars = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $charLen = strlen($chars);

        do{
            $str = "";
            for ($i = 0; $i < $len; $i++) {
                $str .= $chars[rand(0, $charLen - 1)];
            }
        }while(InitializeDeposit::where('trx', $str)->exists());

        return $str;
    
    }
}

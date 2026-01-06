<?php

namespace App\Repository\Admin;

interface IAdminResellerConversionRepository
{
    public function getAllConversions();
    public function getConversionById($id);
    public function updateConversion($id, array $data);
    public function deleteConversion($id);
    public function getConversionsByReseller($resellerId);
    public function getConversionsByProduct($productId);
}
<?php
namespace App\Dto;

class ProductListViewParams
{
    public int $year;
    public array $yearDates;
    public array $discountDates;
    public array $discountYears;
    public array $activeProductDiscounts;
}
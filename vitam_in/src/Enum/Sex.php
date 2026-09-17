<?php

namespace App\Enum;

enum Sex: string
{
    case Male = 'male';
    case Female = 'female';
    case Other = 'other';
}
<?php

namespace Package\Pivel\Hydro2\Database\Models;

enum Order : string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';
}
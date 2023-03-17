<?php

namespace Pivel\Hydro2\Models\Database;

enum Order : string
{
    case Ascending = 'ASC';
    case Descending = 'DESC';
}
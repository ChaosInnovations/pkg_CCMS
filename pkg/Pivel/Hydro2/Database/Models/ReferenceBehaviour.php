<?php

namespace Package\Pivel\Hydro2\Database\Models;

enum ReferenceBehaviour : string
{
    case CASCADE = 'CASCADE';
    case RESTRICT = 'RESTRICT';
    case SETNULL = 'SETNULL';
}
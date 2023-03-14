<?php

namespace Package\Pivel\Hydro2\Models\Database;

enum ReferenceBehaviour : string
{
    case CASCADE = 'CASCADE';
    case RESTRICT = 'RESTRICT';
    case SETNULL = 'SETNULL';
}
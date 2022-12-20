<?php

namespace Package\Database\Models;

enum ReferenceBehaviour : string
{
    case CASCADE = 'CASCADE';
    case RESTRICT = 'RESTRICT';
    case SETNULL = 'SETNULL';
}
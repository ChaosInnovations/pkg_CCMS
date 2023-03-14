<?php

namespace Package\Pivel\Hydro2\Models\Email;

enum Encoding : int
{
    case ENC_OTHER = 0;
    case ENC_BINARY = 1;
    case ENC_7BIT = 2;
    case ENC_8BIT = 3;
    case ENC_QUOTEDPRINTABLE = 4;
    case ENC_BASE64 = 5;
}
<?php

namespace Pivel\Hydro2\Models;

enum EnvironmentType : string
{
    case Production = 'Production';
    case Staging = 'Staging';
    case Development = 'Development';
}
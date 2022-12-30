<?php

namespace Package\Pivel\Hydro2\Email\Services;

use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;

interface IOutboundEmailProvider
{
    public function __construct(OutboundEmailProfile $profile);
}
<?php

namespace Package\Pivel\Hydro2\Email\Services;

use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;

interface IOutboundEmailProvider
{
    public function __construct(OutboundEmailProfile $profile);
    
    public function SendEmail(EmailMessage $message, bool $throwExceptions=false) : bool;
}
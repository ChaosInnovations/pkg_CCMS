<?php

namespace Pivel\Hydro2\Services\Email;

use Pivel\Hydro2\Models\Email\EmailMessage;
use Pivel\Hydro2\Models\Email\OutboundEmailProfile;

interface IOutboundEmailProvider
{
    public function __construct(OutboundEmailProfile $profile);
    
    public function SendEmail(EmailMessage $message, bool $throwExceptions=false) : bool;
}
<?php

namespace Pivel\Hydro2\Services;

use Pivel\Hydro2\Models\Email\EmailAddress;
use Pivel\Hydro2\Models\Email\EmailMessage;
use Pivel\Hydro2\Models\Identity\User;
use Pivel\Hydro2\Services\Email\EmailService;
use Pivel\Hydro2\Views\EmailViews\BaseEmailView;

class UserNotificationService
{
    private ILoggerService $_logger;
    private EmailService $_emailService;

    public function __construct(
        ILoggerService $logger,
        EmailService $emailService,
    )
    {
        $this->_logger = $logger;
        $this->_emailService = $emailService;
    }

    public function SendEmailToUser(User $user, BaseEmailView $emailView) : bool
    {
        // TODO look up which profile to use in preferences
        // TODO the actual sending code should probably be in EmailService.
        $emailProfileProvider = $this->_emailService->GetOutboundEmailProviderInstance('noreply');
        if ($emailProfileProvider === null) {
            return false;
        }

        $emailMessage = new EmailMessage($emailView, [new EmailAddress($user->Email, $user->Name)]);
        return $emailProfileProvider->SendEmail($emailMessage);
    }
}
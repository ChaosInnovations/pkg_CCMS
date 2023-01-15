<?php

namespace Package\Pivel\Hydro2\Email\Services;

use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;

class SMTPProvider implements IOutboundEmailProvider
{
    private OutboundEmailProfile $profile;

    public function __construct(OutboundEmailProfile $profile) {
        $this->profile = $profile;     
    }

    public function SendEmail(EmailMessage $message) : bool {
        // open socket
        if (!$this->Connect()) {
            return false;
        }
        // wait for greeting (will be stored in $this->greeting after Connect())
        // Try to switch to TLS?
        // send HELO/EHLO
        if (!$this->Hello()) {
            return false;
        }
        // Authenticate
        if (!$this->Authenticate()) {
            return false;
        }
        // send MAIL FROM
        if (!$this->MailFrom()) {
            return false;
        }
        // send n RCPT commands
        $recipients = $message->GetAllRecipients();
        $bad_recipients = [];
        foreach ($recipients as $recipient) {
            // send RCPT command
            // check whether recipient address was accepted
            //  if not, add to $bad_recipients
            // TODO handle BCC
        }
        if (count($recipients) <= count($bad_recipients)) {
            // if all recipients were rejected, don't bother sending the message data.
            return false;
        }
        // send DATA command
        // send RFC821/RFC822 formatted message (headers+body).
        //  $message->GetAsRFC822()?
        //  or $this->CompileMessage()?
        //  use the latter since other providers might have different format requirements.
        // send <CRLF>.<CRLF>
        // TODO handle BCC
        if (!$this->SendData(self::CompileMessage($message))) {
            return false;
        }
        // send QUIT
        $this->Quit();

        return true;
    }

    protected function Connect() : bool {
        return false;
    }

    protected function Hello() : bool {
        return false;
    }

    protected function Authenticate() : bool {
        return false;
    }

    protected function MailFrom() : bool {
        return false;
    }

    protected function SendData(string $data) : bool {
        return false;
    }

    protected function Quit() : void {
        
    }

    protected static function CompileMessage(EmailMessage $message): string {
    }
}
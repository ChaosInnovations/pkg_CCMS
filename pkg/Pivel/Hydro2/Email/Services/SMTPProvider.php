<?php

namespace Package\Pivel\Hydro2\Email\Services;

use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Models\Encoding;
use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;

class SMTPProvider implements IOutboundEmailProvider
{
    private OutboundEmailProfile $profile;

    protected const LINE_ENDING = '\r\n';
    protected const LINE_MAX_LENGTH = 76;

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
        $messageBodyParts = [];

        // generate multipart/alternative section with text/plain and text/html
        // When using EmailMessage->GetHTMLBody, EmailMessage will automatically convert inlined image
        //  tags to attachments.
        $hasHTML = $message->HasHTMLBody();
        $hasPlaintext = $message->HasPlaintextBody();

        $messageTextParts = [];

        if ($hasPlaintext) {
            $messageTextParts[] = self::CompileRFC822Message(
                headers: [
                    'Content-Type' => 'text/plain',
                    'Content-Transfer-Encoding' => 'quoted-printable',
                ],
                body: $message->GetPlaintextBody(Encoding::ENC_QUOTEDPRINTABLE, self::LINE_ENDING),
            );
        }

        if ($hasHTML) {
            $messageTextParts[] = self::CompileRFC822Message(
                headers: [
                    'Content-Type' => 'text/html',
                    'Content-Transfer-Encoding' => 'quoted-printable',
                ],
                body: $message->GetHTMLBody(Encoding::ENC_QUOTEDPRINTABLE, self::LINE_ENDING),
            );
        }

        // if only plaintext or body and not both, then this can just be text/plain or text/html without
        //  being encapsulated inside a multipart/alternative. If neither, skip this section
        if (count($messageTextParts) === 1) {
            $messageBodyParts[] = $messageTextParts[0];
        } else if (count($messageTextParts) > 1) {
            $messageBodyParts[] = self::CompileMultipartMessage([], $messageTextParts, self::MULTIPART_ALTERNATIVE);
        }
        

        // TODO encapsulate the above-generated section and each EmailAttachment from EmailMessage->GetAttachments()
        //  inside a multipart/mixed. If no attachments, don't encapsulate.
        $attachments = [];

        // TODO generate headers.
        // TODO subject line encoding?
        // TODO Date field
        // TODO From field
        // TODO Reply-To field
        // TODO To field
        // TODO Cc field
        // TODO Bcc field, send multiple copies of message when Bcc is present with the Bcc
        //   header of each copy containing that recipient.
        $headers = [
            'MIME-Version' => '1.0',
            'Date' => '',
            'From' => '',
            'Reply-To' => '',
            'Subject' => $message->GetSubject(),
            'Thread-Topic' => $message->GetSubject(),
            'To' => '',
            'Cc' => '',
        ];

        if (count($attachments) > 0) {
            $compiledMessage = self::CompileMultipartMessage($headers, $messageBodyParts);
        } else {
            $compiledHeaders = self::CompileHeaders($headers);
            $compiledMessage = $compiledHeaders . self::LINE_ENDING . $messageBodyParts[0];
        }

        return $compiledMessage;
    }

    /**
     * @param string[] $headers fieldname=>value pairs.
     *   Will ensure each header ends with exactly one LINE_ENDING.
     *   Will make headers multi-line if needed.
     * @param string $body
     */
    protected static function CompileRFC822Message(array $headers, string $body) : string {
        $compiledHeaders = self::CompileHeaders($headers);
        // remove leading/trailing whitespace from body
        $body = trim($body);
        // add an extra LINE_ENDING between headers and body
        return $compiledHeaders . self::LINE_ENDING . $body . self::LINE_ENDING;
    }

    protected const MULTIPART_MIXED = 'multipart/mixed';
    protected const MULTIPART_ALTERNATIVE = 'multipart/alternative';

    /**
     * @param string[] $headers fieldname=>value pairs.
     *   Will ensure each header ends with exactly one LINE_ENDING.
     *   Will make headers multi-line if needed.
     * @param string[] $bodyParts array of already-compiled message parts.
     *   Will add/ensure there are exactly two LINE_ENDINGs between bodyPart and the boundary
     */
    protected static function CompileMultipartMessage(array $headers, array $bodyParts, string $type=self::MULTIPART_MIXED, ?string $boundary=null) : string {
        if (empty($boundary)) {
            // default boundary starts with "=_" since this pattern is impossible in
            //  both quoted-printable and base64 content.
            $boundary = '=_'.bin2hex(random_bytes(8));
        }
        $boundarySeparator = self::LINE_ENDING . '--' . $boundary;

        // add content-type header woth boundary
        $headers['Content-Type'] = "{$type}; boundary=\"{$boundary}\"";

        $compiledMessage = '';
        
        $compiledMessage .= self::CompileHeaders($headers);

        $compiledMessage .= $boundarySeparator;

        foreach ($bodyParts as $bodyPart) {
            $compiledMessage .= self::LINE_ENDING;
            $compiledMessage .= $bodyPart;
            $compiledMessage .= $boundarySeparator;
        }

        // last boundary separator must end with an extra -- at the end.
        $compiledMessage .= '--';
        $compiledMessage .= self::LINE_ENDING;

        return $compiledMessage;
    }

    /**
     * @param string[] $headers fieldname=>value pairs.
     *   Will ensure each header ends with exactly one LINE_ENDING.
     *   Will make headers multi-line if needed.
     */
    protected static function CompileHeaders(array $headers) {
        $compiledHeaders = '';
        foreach ($headers as $fieldName => $fieldValue) {
            $header = $fieldName.': '.$fieldValue;
            // Strip existing \r and \n from each header
            $header = str_replace(['\r', '\n'], '', $header);
            // TODO: limit length/split into multi-line header if necessary.
            // add to complied headers
            $compiledHeaders .= $header . self::LINE_ENDING;
        }
        return $compiledHeaders;
    }
}
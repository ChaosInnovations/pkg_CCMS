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

    protected function CompileMessage(EmailMessage $message): string {
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

        // TODO Bcc field, send multiple copies of message when Bcc is present with the Bcc
        //   header of each copy containing only that recipient.
        $headers = [
            'MIME-Version' => '1.0',
            'Date' => date('D, j M Y H:i:s O'),
            'From' => $this->profile->GetSender()->__toString(),
            'Subject' => $message->GetSubject(),
            'Thread-Topic' => $message->GetSubject(),
            'To' => implode(', ', $message->To),
        ];

        if ($message->ReplyTo !== null) {
            $headers['Reply-To'] = $message->ReplyTo->__toString();
        }

        if (count($message->Cc) > 0) {
            $headers['Cc'] = implode(', ', $message->Cc);
        }

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
            // field name cannot start/end with whitespace
            $fieldName = trim($fieldName);
            $header = $fieldName.': '.$fieldValue;
            // Strip existing \r and \n from each header
            $header = str_replace(['\r', '\n'], '', $header);
            $header = self::FoldHeader($header);
            // add to complied headers
            $compiledHeaders .= $header . self::LINE_ENDING;
        }
        return $compiledHeaders;
    }

    protected static function FoldHeader(string $unfoldedHeader, int $lineMaxLength=self::LINE_MAX_LENGTH) : string {
        // whitespace characters can have a LINE_ENDING inserted before the whitespace.
        // if remaining string is <= linemaxlength, add it to foldedHeader

        $foldedHeader = '';
        $firstChunk = true;
        while (strlen($unfoldedHeader) > $lineMaxLength) {
            // look backwards in first $lineMaxLength chars to find latest occuring ' ' character.
            $pos = strrpos(substr($unfoldedHeader, 0, $lineMaxLength), ' ');
            // intentionally match both false and 0
            if (!$pos) {
                // look forwards instead. Unfortunately will be longer than lineMaxLength,
                //  but is the best we can do in this case.
                $pos = strpos($unfoldedHeader, ' ', $lineMaxLength);
            }
            // if still no match or if only the last char matches,
            //  exit loop and add remaining unfoldedHeader to folderHeader.
            //  It's longer than lineMaxLength, but we couldn't find a place to fold it.
            if (!$pos || $pos == strlen($unfoldedHeader)-1) {
                break;
            }
            // append [' ' if not first chunk] . substr<0, x> . LINE_ENDING to $foldedHeader
            $foldedHeader .= ($firstChunk?'':' ') . substr($unfoldedHeader, 0, $pos) . self::LINE_ENDING;
            // $unfoldedHeader is now substr<x+2 (skip the whitespace), MAX>.
            $unfoldedHeader = substr($unfoldedHeader, $pos+1);
            $firstChunk = false;
        }
        $foldedHeader .= ($firstChunk?'':' ') . $unfoldedHeader;

        return $unfoldedHeader;
    }
}
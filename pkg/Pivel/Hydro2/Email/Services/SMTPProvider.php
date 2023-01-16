<?php

namespace Package\Pivel\Hydro2\Email\Services;

use Exception;
use Package\Pivel\Hydro2\Email\Models\EmailMessage;
use Package\Pivel\Hydro2\Email\Models\Encoding;
use Package\Pivel\Hydro2\Email\Models\OutboundEmailProfile;

class SMTPProvider implements IOutboundEmailProvider
{
    private OutboundEmailProfile $profile;

    protected const LINE_ENDING = "\r\n";
    protected const LINE_MAX_LENGTH = 76;
    /**
     * 5 minutes/300 seconds is the default per RFC 2821 Section 4.5.3.2
     * 
     * @see https://www.rfc-editor.org/rfc/rfc2821#section-4.5.3.2
     */
    protected const TIMEOUT = 300.0;

    /**
     * @var resource|bool
     */
    private $connection = false;
    private ?string $greeting = null;
    private int $lastResponseCode = -1;
    /**
     * @var mixed[]
     */
    private array $extensions = [];

    public function __construct(OutboundEmailProfile $profile) {
        $this->profile = $profile;     
    }

    public function SendEmail(EmailMessage $message) : bool {
        // TODO remove after testing
        // $compiledMessage = $this->CompileMessage($message);
        // echo $compiledMessage;

        // open socket and wait for greeting (will be stored in $this->greeting after Connect())
        if (!$this->Connect()) {
            return false;
        }
        // send EHLO/HELO
        if (!$this->Hello()) {
            $this->Quit();
            return false;
        }
        // Upgrade to TLS if possible, requested, and not already connected with SSL.
        if (
            $this->profile->Secure == OutboundEmailProfile::SECURE_TLS_AUTO ||
            $this->profile->Secure == OutboundEmailProfile::SECURE_TLS_REQUIRE
        ) {
            $tls = $this->StartTLS();
            //  If unable to upgrade to TLS and profile->Secure is set to TLS_REQUIRE
            //  then fail and return false
            if (!$tls && $this->profile->Secure == OutboundEmailProfile::SECURE_TLS_REQUIRE) {
                $this->Quit();
                return false;
            }
        // Upgrade to TLS if possible and not already connected with SSL.
        //  If unable to upgrade to TLS and profile->Secure is set to TLS_REQUIRE
        //  then fail and return false Try to switch to TLS?
        // Authenticate
        if (!$this->Authenticate()) {
            // re-send EHLO/HELO after successful TLS negotiation
            if (!$this->Hello()) {
                $this->Quit();
                return false;
            }
        }
        // Authenticate if required by profile
        if ($this->profile->RequireAuth && !$this->Authenticate()) {
            $this->Quit();
            return false;
        }
        // send MAIL FROM
        if (!$this->MailFrom()) {
            $this->Quit();
            return false;
        }
        // send n RCPT commands
        $recipients = $message->GetAllRecipients();
        $bad_recipients = [];
        foreach ($recipients as $recipient) {
            // send RCPT command
            // check whether recipient address was accepted
            //  if not, add to $bad_recipients
            // TODO handle BCC, sending separate messages?
        }
        if (count($recipients) <= count($bad_recipients)) {
            // if all recipients were rejected, don't bother sending the message data.
            $this->Quit();
            return false;
        }
        // send DATA command
        // send RFC821/RFC822 formatted message (headers+body).
        //  $message->GetAsRFC822()?
        //  or $this->CompileMessage()?
        //  use the latter since other providers might have different format requirements.
        // send <CRLF>.<CRLF>
        // TODO handle BCC, sending separate messages?
        $compiledMessage = $this->CompileMessage($message);
        if (!$this->SendData($compiledMessage)) {
            $this->Quit();
            return false;
        }
        // send QUIT
        $this->Quit();

        return true;
    }

    protected function Connect(float $timeout=self::TIMEOUT, $options=[]) : bool {
        if ($this->IsConnected()) {
            return false;
        }

        $host = $this->profile->Host;
        $port = $this->profile->Port;

        if ($this->profile->Secure == OutboundEmailProfile::SECURE_SSL) {
            $host = 'ssl://' . $host;
        }

        $error_code = 0;
        $error_message = '';

        $socket_context = stream_context_create($options);

        $this->connection = stream_socket_client(
            $host . ':' . $port,
            $error_code,
            $error_message,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $socket_context
        );

        if (!$this->IsConnected()) {
            return false;
        }

        if (substr(PHP_OS, 0, 3) != 'WIN') {
            $scriptTimeout = ini_get('max_execution_time');
            if ($scriptTimeout != 0 && $timeout > $scriptTimeout) {
                set_time_limit($timeout);
            }
            stream_set_timeout($this->connection, $timeout, 0);
        }

        $this->greeting = $this->ReadLines();
        return true;
    }

    protected function IsConnected() : bool {
        return is_resource($this->connection);
    }

    protected function Hello() : bool {
        return $this->SendEHLO() || $this->SendHELO();
    }

    protected function StartTLS() : bool {
        if (!$this->SendCommand('STARTTLS')) {
            return false;
        }

        $reply = $this->ReadLines();

        if ($this->lastResponseCode != 220) {
            return false;
        }

        $crypto_method = STREAM_CRYPTO_METHOD_TLS_CLIENT;

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_1_CLIENT;
        }

        if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
            $crypto_method |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
        }

        try {
            $success = stream_socket_enable_crypto($this->connection, true, $crypto_method) === true;
        } catch (Exception $e) {
            return false;
        }

        return $success;
    }

    // TODO implement
    protected function Authenticate() : bool {
        return false;
    }

    // TODO implement
    protected function MailFrom() : bool {
        return false;
    }

    // TODO implement
    protected function SendData(string $data) : bool {
        return false;
    }

    protected function Quit() : void {
        if ($this->IsConnected()) {
            $this->SendCommand('QUIT');

            $reply = $this->ReadLines();

            fclose($this->connection);
        }

        $this->connection = false;
        $this->extensions = [];
        $this->greeting = null;
        $this->lastResponseCode = -1;
    }

    protected function SendEHLO() : bool {
        // send EHLO command
        if (!$this->SendCommand('EHLO', gethostname())) {
            return false;
        }
        // get response
        $reply = $this->ReadLines();
        // if response code is not 250, return false
        if ($this->lastResponseCode != 250) {
            return false;
        }
        // parse response into $this->extensions
        // skip the first line which contains the server's hostname
        $this->extensions = [];
        $lines = explode("\n", $reply);
        $firstLine = true;
        foreach ($lines as $line) {
            if ($firstLine) {
                $firstLine = false;
                continue;
            }
            // skip first 4 characters and remove whitespace
            $line = trim(substr($line, 4));
            $fields = explode(' ', $line);
            $name = array_shift($fields);
            $value = true;
            if ($name == 'AUTH') {
                $value = [];
                if (is_array($fields)) {
                    $value = $fields;
                }
            } else if ($name == 'SIZE') {
                $value = 0;
                if (is_array($fields)) {
                    $value = $fields[0];
                }
            }
            $this->extensions[$name] = $value;
        }
        return true;
    }

    protected function SendHELO() : bool {
        // send HELO command
        if (!$this->SendCommand('HELO', gethostname())) {
            return false;
        }
        // get response
        $reply = $this->ReadLines();
        // if response code is not 250, return false
        return $this->lastResponseCode == 250;
    }

    protected function SendCommand(string $command, string $parameterString='') : bool {
        if (!$this->IsConnected()) {
            return false;
        }

        $commandString = $command;
        if ($parameterString != '') {
            $commandString .= ' ' . $parameterString;
        }
        $commandString .= self::LINE_ENDING;

        // TODO remove this
        // echo $commandString;
        return fwrite($this->connection, $commandString) !== false;
    }

    protected function ReadLines(float $timeout=self::TIMEOUT) : string {
        if (!$this->IsConnected()) {
            return '';
        }

        $lines = '';
        $end = $timeout > 0 ? time() + $timeout : 0;
        while ($this->IsConnected()) {
            $newLine = fgets($this->connection);
            $lines .= $newLine;

            // From RFC5321 Section 4.2.1:
            // Multi-line SMTP replies are in the format
            //  250-First line
            //  250-Second line
            //  250-234 Text beginning with numbers
            //  250 The last line
            // Where the first three characters form the response code,
            // and the fourth character is a '-' for multi-line replies
            // and a ' ' on the final line.
            // Therefore, when we encounter a ' ' as the 4th character
            // in $newLine, we know this was the last line.
            // Additionally, while not permitted in the RFC, it is valid
            // syntax for a reply to contain only the 3-digit response
            // code with no message/text; therefore, if there is no 4th
            // character or if the 4th character is '\r' or '\n', we can
            // also know that this was the last line.
            if (!isset($newLine[3]) || in_array($newLine[3], [' ', "\r", "\n"])) {
                break;
            }

            // end if timed out or if we exceeded the allowed time.
            if (stream_get_meta_data($this->connection)['timed_out'] || ($end !== 0 && time() > $end)) {
                break;
            }
        }

        // The first three characters are the response code.
        $this->lastResponseCode = intval(substr($lines, 0, 3));

        return $lines;
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
            $compiledMessage = $compiledHeaders . $messageBodyParts[0];
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
            $header = str_replace(["\r", "\n",], '', $header);
            $header = self::FoldHeader($header);
            // add to complied headers
            $compiledHeaders .= $header . self::LINE_ENDING;
        }
        return $compiledHeaders;
    }

    protected static function FoldHeader(string $unfoldedHeader, int $lineMaxLength=self::LINE_MAX_LENGTH) : string {
        // whitespace characters can have a LINE_ENDING inserted before the whitespace.
        // if remaining string is <= linemaxlength, add it to foldedHeader
        // We shouldn't fold inside the field name, but we won't because there is no
        //  whitespace inside a field name so it will not appear as an eligible place
        //  to fold at.

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
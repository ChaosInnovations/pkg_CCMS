<?php

namespace Package\Pivel\Hydro2\Email\Models;

use Exception;
use Package\Pivel\Hydro2\Email\Views\BaseEmailView;

class EmailMessage
{
    protected const LINE_ENDING = '\r\n';
    protected const LINE_MAX_LENGTH = 76;

    private ?string $htmlBody;
    private ?string $plaintextBody;
    private ?string $subject;

    private array $toAddresses;
    private array $ccAddresses;
    private array $bccAddresses;

    public function __construct(BaseEmailView $view, array $to, array $cc=[], array $bcc=[])
    {
        $this->htmlBody = $view->Render();
        $this->plaintextBody = $view->RenderPlaintext();
        $this->subject = $view->GetSubject();

        $this->toAddresses = $to;
        $this->ccAddresses = $cc;
        $this->bccAddresses = $bcc;
    }

    public function SetHTMLBody(string $content) : void {
        $this->htmlBody = $content;
    }

    public function HasHTMLBody() : bool {
        return !empty($this->htmlBody);
    }

    // TODO convert <img> tags with embedded images to embedded image attachments and [cid] tags
    public function GetHTMLBody(Encoding $encoding=Encoding::ENC_BINARY, string $lineEnding=self::LINE_ENDING) : string {
        return self::EncodeString($this->htmlBody, $encoding, $lineEnding);
    }

    public function SetPlaintextBody(string $content) : void {
        $this->plaintextBody = $content;
    }

    public function HasPlaintextBody() : bool {
        return !empty($this->plaintextBody);
    }

    public function GetPlaintextBody(Encoding $encoding=Encoding::ENC_BINARY, string $lineEnding=self::LINE_ENDING) : string {
        return self::EncodeString($this->plaintextBody, $encoding, $lineEnding);
    }

    public function GetSubject() : string {
        return $this->subject??'';
    }

    public function GetAllRecipients() : array {
        return array_merge([$this->toAddresses, $this->ccAddresses, $this->bccAddresses]);
    }

    protected static function EncodeString(string $str, $encoding=Encoding::ENC_BINARY, string $lineEnding=self::LINE_ENDING) {
        switch ($encoding) {
            case Encoding::ENC_OTHER:
            case Encoding::ENC_BINARY:
                return $str;
            case Encoding::ENC_7BIT:
            case Encoding::ENC_8BIT:
                return self::NormalizeLineEndings($str);
            case Encoding::ENC_QUOTEDPRINTABLE:
                return self::NormalizeLineEndings(quoted_printable_encode($str), $lineEnding);
            case Encoding::ENC_BASE64:
                return chunk_split(base64_encode($str), self::LINE_MAX_LENGTH, $lineEnding);
            default:
                throw new Exception('Encoding not recognized.');
                break;
        }
    }

    protected static function NormalizeLineEndings(string $str, string $lineEnding=self::LINE_ENDING) : string {
        // First, replace \r\n and \r with \n so that \r\n is not converted to [$lineEnding][$lineEnding]
        // str_replace processes each element of the search array in order.
        $normalized_str = str_replace(['\r\n', '\r'], '\n', $str);

        // Now, replace instances of \r, \n, and \r\n with $lineEnding.
        // Except, if $lineEnding == '\n', return right away since we already did this.
        if ($lineEnding == '\n') {
            return $normalized_str;
        }

        $normalized_str = str_replace('\n', $lineEnding, $normalized_str);

        return $normalized_str;
    }
}
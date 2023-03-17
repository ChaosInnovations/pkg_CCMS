<?php

namespace Pivel\Hydro2\Models\Email;

use Pivel\Hydro2\Extensions\Database\TableColumn;
use Pivel\Hydro2\Extensions\Database\TableName;
use Pivel\Hydro2\Extensions\Database\TablePrimaryKey;
use Pivel\Hydro2\Extensions\Database\Where;
use Pivel\Hydro2\Models\Database\BaseObject;

#[TableName('hydro2_outbound_email_profiles')]
class OutboundEmailProfile extends BaseObject
{
    #[TableColumn('id', autoIncrement:true)]
    #[TablePrimaryKey]
    public ?int $Id;
    #[TableColumn('key')]
    public string $Key;
    #[TableColumn('label')]
    public string $Label;
    #[TableColumn('type')]
    public string $Type;
    #[TableColumn('sender_address')]
    public string $SenderAddress;
    #[TableColumn('sender_name')]
    public string $SenderName;
    #[TableColumn('require_auth')]
    public bool $RequireAuth;
    #[TableColumn('username')]
    public ?string $Username;
    #[TableColumn('password')]
    public ?string $Password;
    #[TableColumn('host')]
    public string $Host;
    #[TableColumn('port')]
    public int $Port;
    #[TableColumn('secure')]
    public string $Secure;

    public const SECURE_NONE = '';
    public const SECURE_TLS_REQUIRE = 'TLS_REQUIRE';
    public const SECURE_TLS_AUTO = 'TLS_AUTO';
    public const SECURE_SSL = 'SSL';

    public function __construct(
        ?int $id=null,
        string $key='',
        string $label='',
        string $type='smtp',
        ?EmailAddress $sender=null,
        bool $requireAuth=false,
        ?string $username='',
        ?string $password='',
        string $host='',
        int $port=25,
        string $secure=self::SECURE_NONE,
    ) {
        $sender = $sender??new EmailAddress('', '');
        $this->Id = $id;
        $this->Key = $key;
        $this->Label = $label;
        $this->Type = $type;
        $this->SenderAddress = $sender->Address;
        $this->SenderName = $sender->Name;
        $this->RequireAuth = $requireAuth;
        $this->Username = $username;
        $this->Password = $password;
        $this->Host = $host;
        $this->Port = $port;
        $this->Secure = $secure;

        parent::__construct();
    }

    public static function LoadFromKey(string $key) : ?self {
        $table = self::getTable();
        $results = $table->Select(null, (new Where())->Equal('key', $key));
        
        if (count($results) != 1) {
            return null;
        }

        return self::CastFromRow($results[0], className:get_called_class());
    }

    public static function Blank() : self {
        return new self();
    }

    public function Save() : bool {
        return $this->UpdateOrCreateEntry();
    }

    public function Delete() : bool {
        return $this->DeleteEntry();
    }

    public function GetSender() : EmailAddress {
        return new EmailAddress($this->SenderAddress, $this->SenderName);
    }
}
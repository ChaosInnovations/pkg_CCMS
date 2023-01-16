<?php

namespace Package\Pivel\Hydro2\Email\Models;

use Package\Pivel\Hydro2\Database\Extensions\TableColumn;
use Package\Pivel\Hydro2\Database\Extensions\TableName;
use Package\Pivel\Hydro2\Database\Extensions\TablePrimaryKey;
use Package\Pivel\Hydro2\Database\Extensions\Where;
use Package\Pivel\Hydro2\Database\Models\BaseObject;

#[TableName('outbound_email_profiles')]
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
        ?EmailAddress $sender,
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
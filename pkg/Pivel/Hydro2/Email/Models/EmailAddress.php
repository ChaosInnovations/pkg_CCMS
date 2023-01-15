<?php

namespace Package\Pivel\Hydro2\Email\Models;

class EmailAddress
{
    public string $Address;
    public string $Name;

    public function __construct(
        string $address,
        ?string $name,
    )
    {
        $this->Address = $address;
        $this->Name = $name??$address;
    }

    public function __toString()
    {
        return "\"{$this->Name}\" <{$this->Address}>";
    }
}
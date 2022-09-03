<?php

namespace Package\Database\Controllers;

interface IDatabaseProvider
{
    public function OpenConnection() : bool;
}
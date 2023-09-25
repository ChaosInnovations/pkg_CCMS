<?php

namespace Pivel\Hydro2\Views\Components\RichTable;

use Pivel\Hydro2\Extensions\RequireScript;
use Pivel\Hydro2\Extensions\RequireStyle;
use Pivel\Hydro2\Views\BaseWebView;
use Pivel\Hydro2\Views\Components\SortableTable;

#[RequireScript('RichTable.js')]
#[RequireStyle('RichTable.css')]
class RichTable extends SortableTable
{
    protected string $SearchFormId = '';
    protected bool $HasControls = false;
    protected bool $HasContextMenu = false;

    public function __construct(
        protected string $Id,
        protected array $Headers,
        protected bool $IsSearchEnabled = false,
        protected bool $IsFilterEnabled = false,
        protected bool $IsCreateEnabled = false,
        protected bool $IsDetailEnabled = false,
        protected bool $IsEditEnabled = false,
        protected bool $IsDeleteEnabled = false,
        protected BaseWebView|string|null $DetailOverlay = null,
        protected BaseWebView|string|null $EditOverlay = null,
        protected BaseWebView|string|null $CreateOverlay = null,
    ) {
        $this->SearchFormId = $Id . '_search_form';
        $this->HasControls = $IsSearchEnabled || $IsFilterEnabled || $IsCreateEnabled;
        $this->HasContextMenu = $IsDetailEnabled || $IsEditEnabled || $IsDeleteEnabled;
    }
}
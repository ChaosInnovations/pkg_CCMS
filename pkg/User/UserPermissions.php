<?php

namespace Package\User;

class UserPermissions
{
	public $owner = false;
	public $admin_managepages = false;
	public $admin_managesite = false;
	public $page_createsecure = false;
	public $page_editsecure = false;
	public $page_deletesecure = false;
	public $page_viewsecure = false;
	public $page_create = false;
	public $page_edit = false;
	public $page_delete = false;
	public $toolbar = false;
	public $page_viewblacklist = [];
	public $page_editblacklist = [];
}

<?php

namespace Pivel\Hydro2\Models;

enum Permissions : string
{
    case ViewAdminPanel = 'pivel/hydro2/viewadminpanel';
    case ManagePersistenceProfiles = 'pivel/hydro2/managepersistenceprofiles';
    case ManageOutboundEmailProfiles = 'pivel/hydro2/manageoutboundemailprofiles';
    case ViewUsers = 'pivel/hydro2/viewusers';
    case ManageUsers = 'pivel/hydro2/manageusers';
    case CreateUsers = 'pivel/hydro2/createusers';
    case CreateUserRoles = 'pivel/hydro2/createuserroles';
    case ManageUserRoles = 'pivel/hydro2/manageuserroles';
    case ViewUserSessions = 'pivel/hydro2/viewusersessions';
    case EndUserSessions = 'pivel/hydro2/endusersessions';
}
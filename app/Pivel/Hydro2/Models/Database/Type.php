<?php

namespace Pivel\Hydro2\Models\Database;

enum Type : string
{
    case CHAR = 'CHAR';
    case VARCHAR = 'VARCHAR';
    case BINARY = 'BINARY';
    case VARBINARY = 'VARBINARY';
    case TINYBLOB = 'TINYBLOB';
    case TINYTEXT = 'TINYTEXT';
    case TEXT = 'TEXT';
    case BLOB = 'BLOB';
    case MEDIUMTEXT = 'MEDIUMTEXT';
    case MEDIUMBLOB = 'MEDIUMBLOB';
    case LONGTEXT = 'LONGTEXT';
    case LONGBLOB = 'LONGBLOB';
    case ENUM = 'ENUM'; // this requires arguments, how to implement?
    case SET = 'SET'; // this requires arguments, how to implement?

    case BIT = 'BIT';
    case BOOLEAN = 'BOOLEAN';
    case SMALLINT = 'SMALLINT';
    case MEDIUMINT = 'MEDIUMINT';
    case INT = 'INT';
    case BIGINT = 'BIGINT';
    case FLOAT = 'FLOAT';
    case DOUBLE = 'DOUBLE';
    case DECIMAL = 'DECIMAL';

    case DATE = 'DATE';
    case DATETIME = 'DATETIME';
    case TIMESTAMP = 'TIMESTAMP';
    case TIME = 'TIME';
    case YEAR = 'YEAR';
}
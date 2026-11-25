<?php
declare(strict_types=1);

namespace QuickDRY\Tests;

use PHPUnit\Framework\TestCase;
use QuickDRY\Connectors\SQLCodeGen;

/** Tests for the generator's table list and column filters */
final class CodeGenSecurityTest extends TestCase
{
    public function testParseTableList(): void
    {
        $this->assertNull(SQLCodeGen::ParseTableList(null));
        $this->assertNull(SQLCodeGen::ParseTableList('  '));
        $this->assertSame(['invoice', 'project*', 'UserMfaCode'], SQLCodeGen::ParseTableList(' invoice, project*,,UserMfaCode '));
    }

    public function testEveryTableByDefault(): void
    {
        $gen = new SQLCodeGen();
        $this->assertTrue($gen->JSONTableAllowed('user_mfa_code', 'UserMfaCode'));
    }

    public function testIncludeListMatchesTableOrNiceNameWithWildcards(): void
    {
        $gen = new SQLCodeGen();
        $gen->SetJSONTables(['invoice*', 'Project'], null);

        $this->assertTrue($gen->JSONTableAllowed('invoice', 'Invoice'));
        $this->assertTrue($gen->JSONTableAllowed('invoice_payment', 'InvoicePayment'));
        $this->assertTrue($gen->JSONTableAllowed('project', 'Project'));
        $this->assertFalse($gen->JSONTableAllowed('project_task', 'ProjectTask'));
        $this->assertFalse($gen->JSONTableAllowed('user', 'User'));
    }

    public function testExcludeWins(): void
    {
        $gen = new SQLCodeGen();
        $gen->SetJSONTables(['invoice*'], ['InvoiceAdjustment', 'user_*']);

        $this->assertTrue($gen->JSONTableAllowed('invoice', 'Invoice'));
        $this->assertFalse($gen->JSONTableAllowed('invoice_adjustment', 'InvoiceAdjustment'));

        $gen->SetJSONTables(null, ['user_*']);
        $this->assertTrue($gen->JSONTableAllowed('user', 'User'));
        $this->assertFalse($gen->JSONTableAllowed('user_mfa_code', 'UserMfaCode'));
    }

    public function testEditableColumnsSkipKeysOwnerAndSystemColumns(): void
    {
        $cols = ['id', 'user_id', 'client_id', 'name', 'created_at', 'updated_by_id', 'photo_file', 'total'];
        $this->assertSame(['client_id', 'name', 'total'], SQLCodeGen::EditableColumns($cols, ['id']));
    }

    public function testSensitiveColumns(): void
    {
        $cols = ['id', 'password', 'cookie_hash', 'token_hash', 'reset_token', 'client_secret', 'api_key',
                 'email', 'company', 'tokens_used_count', 'hashtag', 'salt', 'description'];
        $this->assertSame(
            ['password', 'cookie_hash', 'token_hash', 'reset_token', 'client_secret', 'api_key', 'salt'],
            SQLCodeGen::SensitiveColumns($cols)
        );
    }
}

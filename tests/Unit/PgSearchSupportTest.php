<?php

namespace Tests\Unit;

use App\Support\PgSearchSupport;
use Tests\TestCase;

class PgSearchSupportTest extends TestCase
{
    public function test_match_operator_defaults_to_disjunction(): void
    {
        config(['search.postgres.pg_search.match' => 'any']);

        $this->assertSame('|||', PgSearchSupport::matchOperator());
    }

    public function test_match_operator_supports_conjunction(): void
    {
        config(['search.postgres.pg_search.match' => 'all']);

        $this->assertSame('&&&', PgSearchSupport::matchOperator());
    }

    public function test_match_expression_without_boost(): void
    {
        config(['search.postgres.pg_search.match' => 'any']);

        $this->assertSame('(title ||| ?)', PgSearchSupport::matchExpression('title'));
    }

    public function test_match_expression_with_boost(): void
    {
        config(['search.postgres.pg_search.match' => 'any']);

        $this->assertSame('(title ||| ?::pdb.boost(5))', PgSearchSupport::matchExpression('title', 5.0));
    }
}

<?php

namespace App\Repositories\Interfaces;

interface SpamRuleStatRepositoryInterface
{
    public function incrementOrCreate(string $ruleName, string $date): void;
    
    public function batchIncrementOrCreate(array $ruleNames, string $date): void;
}
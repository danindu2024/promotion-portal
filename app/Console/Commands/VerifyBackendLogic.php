<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\StagingData;
use App\Services\RegistryValidator;
use App\Helpers\Current;

class VerifyBackendLogic extends Command
{
    protected $signature = 'verify:backend';
    protected $description = 'Verify Models, Validation, and Mock Auth logic';

    public function handle()
    {
        $this->info('Starting Backend Verification...');

        // 1. Verify Mock Auth
        $this->info('1. Testing Mock Auth (Current::user)...');
        $user = Current::user();
        if ($user) {
            $this->info("   ✅ Logged in as: {$user->username} (ID: {$user->user_id})");
        } else {
            $this->error("   ❌ Mock Auth Failed: No user found. Did you seed the DB?");
            return;
        }

        // 2. Test Validation (Self-Employed)
        $this->info('2. Testing Validation (Self-Employed)...');
        $validData = [
            'category' => 'Self-Employed',
            'full_name' => 'John Doe',
            'address' => '123 Farm Rd',
            'district' => 'Gampaha',
            'address' => '123 Farm Rd',
            'province' => 'Western',
            'district' => 'Gampaha',
            'ds_division' => 'Minuwangoda',

            'contact_number' => '0771234567',
            
            'field_of_work' => 'Agriculture and Fisheries Entrepreneurs',
            'employees_count' => 2,
        ];
        
        $validator = RegistryValidator::validate($validData);
        if ($validator->passes()) {
            $this->info("   ✅ Valid data passed.");
        } else {
            $this->error("   ❌ Valid data failed!");
            $this->table(['Error'], array_map(fn($e) => [$e], $validator->errors()->all()));
        }

        // 3. Test Validation Failure (Trade with missing fields)
        $this->info('3. Testing Validation Failure (Invalid Trade)...');
        $invalidData = [
            'category' => 'Trade',
            'full_name' => 'ABC Traders',
            // Missing contact_person
            // Missing members_count
            'age' => 50, // Forbidden field
        ];
        $validator = RegistryValidator::validate($invalidData);
        if ($validator->fails()) {
            $this->info("   ✅ Invalid data correctly failed.");
            $this->line("      Errors: " . implode(', ', $validator->errors()->keys()));
        } else {
            $this->error("   ❌ Invalid data PASSED! (This is bad)");
        }

        // 4. Test Staging Workflow
        $this->info('4. Testing Staging Workflow...');
        $staging = StagingData::create([
            'batch_id' => 'BATCH-001',
            'data_payload' => $validData,
            'submission_type' => 'NEW',
            'uploaded_by' => Current::id(),
        ]);
        
        $this->info("   Created Staging Record ID: {$staging->id}");
        $this->info("   Initial Status: {$staging->validation_status}");

        $staging->markAsValid();
        $this->info("   After markAsValid: {$staging->validation_status}");

        $staging->approve();
        $this->info("   After approve: {$staging->validation_status} (Reviewer: {$staging->reviewed_by})");

        if ($staging->validation_status === StagingData::STATUS_APPROVED && $staging->reviewed_by === Current::id()) {
            $this->info("   ✅ Workflow verified successfully.");
        } else {
            $this->error("   ❌ Workflow failed.");
        }

        // 5. Test Duplicate Entry (Unique Constraint)
        $this->info('5. Testing Duplicate Entry...');
        try {
            // Attempt to create a duplicate record using the Model directly
            $record = array_merge($validData, [
                'approved_by' => Current::id(),
                'approved_at' => now(),
            ]);
            \App\Models\MainRegistry::create($record);
            \App\Models\MainRegistry::create($record); // Should fail here
            $this->error("   ❌ Duplicate entry allowed!");
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->errorInfo[1] == 1062) { // MySQL Duplicate Entry Error Code
                $this->info("   ✅ Duplicate entry correctly rejected (DB Constraint).");
            } else {
                $this->error("   ❌ Unexpected DB Error: " . $e->getMessage());
            }
        }

        $this->info('Backend Verification Complete!');
    }
}

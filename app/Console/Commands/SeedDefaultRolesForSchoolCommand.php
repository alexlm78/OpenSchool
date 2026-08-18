<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Support\DefaultRoleSeeder;
use Illuminate\Console\Command;

final class SeedDefaultRolesForSchoolCommand extends Command
{
    protected $signature = 'openschool:seed-default-roles {schoolId : The school ID to seed roles for}
        {--guard=web : The guard name for the roles}
        {--force : Overwrite existing role definitions (re-save)}';

    protected $description = 'Seed the 4 default roles (admin, teacher, student, guardian) for a specific school using Spatie Teams.';

    public function handle(DefaultRoleSeeder $seeder): int
    {
        $schoolIdRaw = $this->argument('schoolId');
        $schoolId = filter_var($schoolIdRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! is_int($schoolId)) {
            $this->error('Invalid schoolId provided. Must be a positive integer.');

            return self::FAILURE;
        }

        $school = School::query()->find($schoolId);
        if (! $school instanceof School) {
            $this->error("School with ID {$schoolId} not found.");

            return self::FAILURE;
        }

        $guard = is_string($this->option('guard')) ? (string) $this->option('guard') : 'web';
        $force = (bool) $this->option('force');

        try {
            $result = $seeder->seedForSchool($school, $guard, $force);
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        foreach (DefaultRoleSeeder::DEFAULT_ROLES as $roleName) {
            if ($result['created'] > 0) {
                $this->line("  • Role <info>{$roleName}</info> processed for school {$schoolId}.");
            }
        }

        $this->info("Done. Created: {$result['created']}, Updated: {$result['updated']}, Skipped: {$result['skipped']}.");

        return self::SUCCESS;
    }
}

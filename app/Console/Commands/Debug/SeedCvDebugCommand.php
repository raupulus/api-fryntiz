<?php

declare(strict_types=1);

namespace App\Console\Commands\Debug;

use App\Console\Commands\Debug\Concerns\ResolvesDebugDefaults;
use App\Models\CV\Curriculum;
use App\Models\CV\CurriculumAcademicComplementary;
use App\Models\CV\CurriculumAcademicComplementaryOnline;
use App\Models\CV\CurriculumAcademicTraining;
use App\Models\CV\CurriculumAvailableRepositoryType;
use App\Models\CV\CurriculumCollaboration;
use App\Models\CV\CurriculumExperienceAccredited;
use App\Models\CV\CurriculumExperienceAdditional;
use App\Models\CV\CurriculumExperienceNoAccredited;
use App\Models\CV\CurriculumExperienceOther;
use App\Models\CV\CurriculumExperienceSelfEmployed;
use App\Models\CV\CurriculumHobby;
use App\Models\CV\CurriculumJob;
use App\Models\CV\CurriculumProject;
use App\Models\CV\CurriculumRepository;
use App\Models\CV\CurriculumService;
use App\Models\CV\CurriculumSkill;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SeedCvDebugCommand extends Command
{
    use ResolvesDebugDefaults;

    protected $signature = 'debug:seed-cv';

    protected $description = 'Crea curriculum de prueba para el administrador (solo desarrollo)';

    public function handle(): int
    {
        if (! $this->guardEnvironment()) {
            return self::FAILURE;
        }

        // Obtener o crear superadmin
        $user = User::where('role_id', 1)->first() ?? User::create([
            'name' => 'Raúl Caro',
            'email' => 'admin@raupulus.dev',
            'password' => Hash::make('password'),
            'role_id' => 1,
            'email_verified_at' => now(),
        ]);

        $this->info('Creando curriculum base...');

        $title = 'Curriculum de Raúl Caro';

        $cv = Curriculum::firstOrCreate(['user_id' => $user->id], [
            'title' => $title,
            'slug' => Str::slug($title),
            'presentation' => fake()->paragraph(),
            'is_active' => true,
            'is_downloadable' => true,
            'is_default' => true,
            'is_public' => true,
        ]);

        $this->info('Creando formación académica de prueba...');
        for ($i = 0; $i < 3; $i++) {
            CurriculumAcademicTraining::create([
                'curriculum_id' => $cv->id,
                'title' => fake()->sentence(3),
                'description' => fake()->paragraph(),
                'hours' => fake()->numberBetween(100, 2000),
                'expires' => false,
                'start_at' => now()->subYears(5)->format('Y-m-d H:i:s'),
                'end_at' => now()->subYears(3)->format('Y-m-d H:i:s'),
            ]);
            CurriculumAcademicComplementary::create([
                'curriculum_id' => $cv->id,
                'title' => fake()->sentence(3),
                'description' => fake()->paragraph(),
                'hours' => fake()->numberBetween(20, 100),
                'expires' => false,
                'start_at' => now()->subYears(2)->format('Y-m-d H:i:s'),
                'end_at' => now()->subYears(2)->format('Y-m-d H:i:s'),
            ]);
            CurriculumAcademicComplementaryOnline::create([
                'curriculum_id' => $cv->id,
                'title' => fake()->sentence(3),
                'description' => fake()->paragraph(),
                'hours' => fake()->numberBetween(5, 50),
                'expires' => false,
                'start_at' => now()->subYear()->format('Y-m-d H:i:s'),
                'end_at' => now()->subYear()->format('Y-m-d H:i:s'),
            ]);
        }

        $this->info('Creando experiencia laboral de prueba...');
        $experiences = [
            CurriculumExperienceAccredited::class,
            CurriculumExperienceNoAccredited::class,
            CurriculumExperienceSelfEmployed::class,
            CurriculumExperienceAdditional::class,
            CurriculumExperienceOther::class,
        ];

        foreach ($experiences as $expClass) {
            for ($i = 0; $i < 2; $i++) {
                $expClass::create([
                    'curriculum_id' => $cv->id,
                    'title' => fake()->jobTitle(),
                    'company' => fake()->company(),
                    'description' => fake()->paragraph(),
                    'start_at' => now()->subYears(4 - $i)->format('Y-m-d H:i:s'),
                    'end_at' => now()->subYears(3 - $i)->format('Y-m-d H:i:s'),
                ]);
            }
        }

        $this->info('Creando proyectos, repositorios y habilidades...');
        for ($i = 0; $i < 3; $i++) {
            CurriculumProject::create([
                'curriculum_id' => $cv->id,
                'title' => fake()->words(3, true),
                'description' => fake()->sentence(),
                'url' => fake()->url(),
                'role' => fake()->jobTitle(),
            ]);

            $repoType = CurriculumAvailableRepositoryType::query()->first()
                ?? CurriculumAvailableRepositoryType::create(['title' => 'GitHub', 'slug' => 'github', 'name' => 'GitHub', 'url' => 'https://github.com']);

            CurriculumRepository::create([
                'curriculum_id' => $cv->id,
                'repository_type_id' => $repoType->id,
                'title' => fake()->words(2, true),
                'name' => fake()->word(),
                'description' => fake()->sentence(),
                'url' => fake()->url(),
            ]);
        }

        $skills = ['PHP', 'Laravel', 'Vue.js', 'PostgreSQL', 'Docker', 'Git', 'Linux', 'TailwindCSS'];
        foreach ($skills as $index => $skillName) {
            CurriculumSkill::create([
                'curriculum_id' => $cv->id,
                'name' => $skillName,
                'description' => fake()->sentence(),
                'level' => fake()->numberBetween(5, 10),
            ]);
        }

        $this->info('Creando servicios, colaboraciones, hobbies y trabajos...');
        for ($i = 0; $i < 3; $i++) {
            CurriculumService::create([
                'curriculum_id' => $cv->id,
                'name' => fake()->words(3, true),
                'description' => fake()->paragraph(),
            ]);
            CurriculumCollaboration::create([
                'curriculum_id' => $cv->id,
                'title' => fake()->words(3, true),
                'description' => fake()->paragraph(),
            ]);
            CurriculumJob::create([
                'curriculum_id' => $cv->id,
                'title' => fake()->jobTitle(),
                'description' => fake()->paragraph(),
                'start_at' => now()->subYears(2)->format('Y-m-d H:i:s'),
                'end_at' => now()->format('Y-m-d H:i:s'),
            ]);
        }

        for ($i = 0; $i < 4; $i++) {
            CurriculumHobby::create([
                'curriculum_id' => $cv->id,
                'title' => fake()->words(2, true),
                'description' => fake()->sentence(),
            ]);
        }

        $this->info('✅ Curriculum de prueba e items asociados creados.');

        return self::SUCCESS;
    }
}

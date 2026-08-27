<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Media;
use App\Models\Organization;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        $contents = fake()->text(64);

        return [
            'organization_id' => Organization::factory(),
            'disk' => 'local',
            'path' => 'media/'.fake()->uuid().'.jpg',
            'original_name' => fake()->word().'.jpg',
            'mime_type' => 'image/jpeg',
            'size_bytes' => strlen($contents),
            'checksum_sha256' => hash('sha256', $contents),
        ];
    }
}

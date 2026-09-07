<?php

namespace Database\Seeders;

use App\Models\CourseGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseGroupColorsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $groups = CourseGroup::query()
                ->orderBy('course_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            if ($groups->isEmpty()) {
                return;
            }

            $colors = $this->distinctColors($groups->count());

            foreach ($groups as $index => $group) {
                $group->update([
                    'color' => $colors[$index],
                ]);
            }
        });
    }

    /**
     * Evenly spaced hues (golden angle) with alternating saturation/lightness.
     *
     * @return list<string>
     */
    private function distinctColors(int $count): array
    {
        $colors = [];

        for ($index = 0; $index < $count; $index++) {
            $hue = fmod($index * 137.508, 360);
            $saturation = 58 + (($index % 3) * 12);
            $lightness = 38 + (($index % 5) * 7);

            $colors[] = $this->hslToHex($hue, $saturation, $lightness);
        }

        return $colors;
    }

    private function hslToHex(float $hue, float $saturation, float $lightness): string
    {
        $saturation /= 100;
        $lightness /= 100;

        $chroma = (1 - abs((2 * $lightness) - 1)) * $saturation;
        $huePrime = $hue / 60;
        $x = $chroma * (1 - abs(fmod($huePrime, 2) - 1));

        if ($huePrime < 1) {
            [$red, $green, $blue] = [$chroma, $x, 0];
        } elseif ($huePrime < 2) {
            [$red, $green, $blue] = [$x, $chroma, 0];
        } elseif ($huePrime < 3) {
            [$red, $green, $blue] = [0, $chroma, $x];
        } elseif ($huePrime < 4) {
            [$red, $green, $blue] = [0, $x, $chroma];
        } elseif ($huePrime < 5) {
            [$red, $green, $blue] = [$x, 0, $chroma];
        } else {
            [$red, $green, $blue] = [$chroma, 0, $x];
        }

        $match = $lightness - ($chroma / 2);
        $red = (int) round(($red + $match) * 255);
        $green = (int) round(($green + $match) * 255);
        $blue = (int) round(($blue + $match) * 255);

        return sprintf('#%02X%02X%02X', $red, $green, $blue);
    }
}

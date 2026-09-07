<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentsSeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const FEMALE_NAMES = [
        'Sofia', 'Giulia', 'Aurora', 'Emma', 'Ginevra', 'Alice', 'Beatrice', 'Vittoria',
        'Matilde', 'Chiara', 'Francesca', 'Martina', 'Elisa', 'Sara', 'Laura',
    ];

    /**
     * @var list<string>
     */
    private const MALE_NAMES = [
        'Leonardo', 'Francesco', 'Alessandro', 'Lorenzo', 'Mattia', 'Andrea', 'Gabriele',
        'Riccardo', 'Tommaso', 'Edoardo', 'Davide', 'Federico', 'Luca', 'Simone', 'Pietro',
    ];

    /**
     * @var list<string>
     */
    private const LAST_NAMES = [
        'Rossi', 'Russo', 'Ferrari', 'Esposito', 'Bianchi', 'Romano', 'Colombo', 'Ricci',
        'Marino', 'Greco', 'Bruno', 'Gallo', 'Conti', 'De Luca', 'Costa', 'Fontana',
        'Caruso', 'Ferrara', 'Santoro', 'Rizzo', 'Lombardi', 'Moretti', 'Barbieri',
        'Mancini', 'Giordano', 'Rinaldi', 'Leone', 'Longo', 'Gentile', 'Martinelli',
    ];

    /**
     * @var list<string>
     */
    private const CITIES = [
        'Milano', 'Roma', 'Torino', 'Firenze', 'Bologna', 'Napoli', 'Verona', 'Padova',
        'Brescia', 'Parma', 'Modena', 'Perugia', 'Cagliari', 'Trieste', 'Genova',
    ];

    public function run(): void
    {
        $students = $this->buildStudents();

        foreach ($students as $index => $data) {
            $photoUrl = $data['photo_url'];
            unset($data['photo_url']);

            $customer = Customer::query()->updateOrCreate(
                ['student_email' => $data['student_email']],
                $data,
            );

            $this->storePhoto($customer, $photoUrl, $index);
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function buildStudents(): array
    {
        $students = [];

        for ($i = 0; $i < 80; $i++) {
            if ($i < 30) {
                $isFemale = $i < 15;
                $firstName = $isFemale
                    ? self::FEMALE_NAMES[$i]
                    : self::MALE_NAMES[$i - 15];
                $lastName = self::LAST_NAMES[$i];
                $slug = Str::slug($firstName.'-'.$lastName);
                $studentEmail = "fake.student.{$slug}.{$i}@example.it";
            } else {
                $isFemale = $i % 2 === 0;
                $firstName = $isFemale
                    ? self::FEMALE_NAMES[$i % count(self::FEMALE_NAMES)]
                    : self::MALE_NAMES[$i % count(self::MALE_NAMES)];
                $lastName = self::LAST_NAMES[($i * 3 + 2) % count(self::LAST_NAMES)];
                $slug = Str::slug($firstName.'-'.$lastName.'-'.$i);
                $studentEmail = "fake.student.{$slug}@example.it";
            }

            $gender = $isFemale ? 'female' : 'male';
            $portraitGender = $isFemale ? 'women' : 'men';
            $portraitId = ($i % 90) + 1;
            $birthYear = 2008 + ($i % 9);
            $birthMonth = ($i % 12) + 1;
            $birthDay = ($i % 27) + 1;
            $city = self::CITIES[$i % count(self::CITIES)];

            $students[] = [
                'name' => trim($firstName.' '.$lastName),
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'birth_date' => sprintf('%04d-%02d-%02d', $birthYear, $birthMonth, $birthDay),
                'birth_place' => $city,
                'residence_city_province' => $city,
                'student_email' => $studentEmail,
                'student_phone' => sprintf('+39 3%02d %03d %04d', 10 + ($i % 80), 100 + $i, 2000 + $i),
                'email' => $studentEmail,
                'phone' => sprintf('+39 3%02d %03d %04d', 10 + ($i % 80), 100 + $i, 2000 + $i),
                'course_aa_2026_27' => ($i % 3 === 0) ? 'Balletto' : (($i % 3 === 1) ? 'TAM' : 'Balletto + TAM'),
                'is_existing_student' => $i % 4 === 0,
                'status' => 'active',
                'photo_url' => "https://randomuser.me/api/portraits/{$portraitGender}/{$portraitId}.jpg",
            ];
        }

        return $students;
    }

    private function storePhoto(Customer $customer, string $photoUrl, int $index): void
    {
        $response = Http::timeout(20)->get($photoUrl);

        if (! $response->successful()) {
            $fallbackGender = $index < 15 ? 'women' : 'men';
            $response = Http::timeout(20)->get(
                "https://randomuser.me/api/portraits/{$fallbackGender}/".(($index % 50) + 50).'.jpg'
            );
        }

        if (! $response->successful()) {
            $placeholder = ($index % 5) + 1;
            $placeholderPath = "student-placeholder-{$placeholder}.jpg";

            if (Storage::disk('public')->exists($placeholderPath)) {
                $customer->update(['student_photo_path' => $placeholderPath]);

                return;
            }

            return;
        }

        if ($customer->student_photo_path && str_starts_with($customer->student_photo_path, 'seed-students/')) {
            Storage::disk('public')->delete($customer->student_photo_path);
        }

        $path = sprintf('seed-students/%03d.jpg', $index + 1);
        Storage::disk('public')->put($path, $response->body());
        $customer->update(['student_photo_path' => $path]);
    }
}

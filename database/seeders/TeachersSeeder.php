<?php

namespace Database\Seeders;

use App\Models\Teacher;
use App\Services\SecureFiles\SecureFileService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class TeachersSeeder extends Seeder
{
    /**
     * @var list<array<string, mixed>>
     */
    private const TEACHERS = [
        [
            'type' => 'permanent',
            'first_name' => 'Elena',
            'last_name' => 'Rossi',
            'email' => 'elena.rossi@example.it',
            'phone' => '+39 333 120 4501',
            'tax_code' => 'RSSLNE85M41F205X',
            'description' => 'Insegnante di danza classica con oltre 15 anni di esperienza. Responsabile del programma avanzato di balletto.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/44.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Marco',
            'last_name' => 'Bianchi',
            'email' => 'marco.bianchi@example.it',
            'phone' => '+39 347 882 1190',
            'tax_code' => 'BNCMRC78D12H501Z',
            'description' => 'Maestro di tecnica maschile e repertorio classico. Collabora con compagnie italiane e internazionali.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/32.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Giulia',
            'last_name' => 'Ferretti',
            'email' => 'giulia.ferretti@example.it',
            'phone' => '+39 320 554 9033',
            'tax_code' => 'FRRGLI92C55E625K',
            'description' => 'Insegnante temporanea specializzata in preparazione TAM e musical theatre per studenti intermedi.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/68.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Alessandro',
            'last_name' => 'Conti',
            'email' => 'alessandro.conti@example.it',
            'phone' => '+39 366 701 2284',
            'tax_code' => 'CNTLSN81A15L219P',
            'description' => 'Pianista e accompagnatore di sala. Segue lezioni di balletto, saggio e preparazione esami.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/75.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Sofia',
            'last_name' => 'Romano',
            'email' => 'sofia.romano@example.it',
            'phone' => '+39 328 990 4417',
            'tax_code' => 'RMNSFO88T44F839D',
            'description' => 'Insegnante ospite per workshop di contemporaneo e improvvisazione scenica.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/65.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Valentina',
            'last_name' => 'Marini',
            'email' => 'valentina.marini@example.it',
            'phone' => '+39 331 204 5581',
            'tax_code' => 'MRNVNT90E52F205W',
            'description' => 'Insegnante di tecnica classica e sbarra a terra per corsi intermedi e avanzati.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/12.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Davide',
            'last_name' => 'Moretti',
            'email' => 'davide.moretti@example.it',
            'phone' => '+39 348 771 3390',
            'tax_code' => 'MRTDVD83H14H501A',
            'description' => 'Maestro di punte e pas-de-deux. Esperienza in compagnie europee di repertorio classico.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/18.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Chiara',
            'last_name' => 'Lombardi',
            'email' => 'chiara.lombardi@example.it',
            'phone' => '+39 327 660 8821',
            'tax_code' => 'LMBCHR95L63D612T',
            'description' => 'Docente di danza contemporanea e floor work per il programma TAM.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/26.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Luca',
            'last_name' => 'Gallo',
            'email' => 'luca.gallo@example.it',
            'phone' => '+39 340 118 9044',
            'tax_code' => 'GLLLCU79P20F839B',
            'description' => 'Insegnante di modern e tecnica Graham. Coordina laboratori coreografici annuali.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/41.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Francesca',
            'last_name' => 'Ricci',
            'email' => 'francesca.ricci@example.it',
            'phone' => '+39 329 550 2178',
            'tax_code' => 'RCCFNC91D45E625Y',
            'description' => 'Specialista in pilates e preparazione muscolare per danzatori professionisti.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/33.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Andrea',
            'last_name' => 'Fontana',
            'email' => 'andrea.fontana@example.it',
            'phone' => '+39 346 902 6612',
            'tax_code' => 'FNTNDR86B08L219L',
            'description' => 'Insegnante di repertorio classico e danze storiche per i corsi superiori.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/52.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Martina',
            'last_name' => 'Barbieri',
            'email' => 'martina.barbieri@example.it',
            'phone' => '+39 324 880 7735',
            'tax_code' => 'BRBMRT93M71F205C',
            'description' => 'Docente di hip hop e body work. Conduce stage intensivi per studenti TAM.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/47.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Federico',
            'last_name' => 'Caruso',
            'email' => 'federico.caruso@example.it',
            'phone' => '+39 335 441 9088',
            'tax_code' => 'CRSFRC80C17H501N',
            'description' => 'Maestro di partnering e contact improvisation nel curriculum contemporaneo.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/63.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Elisa',
            'last_name' => 'Ferrara',
            'email' => 'elisa.ferrara@example.it',
            'phone' => '+39 338 772 1140',
            'tax_code' => 'FRRLSE89T48D612V',
            'description' => 'Insegnante di improvvisazione e movement research per laboratori avanzati.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/71.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Simone',
            'last_name' => 'Gentile',
            'email' => 'simone.gentile@example.it',
            'phone' => '+39 349 330 5567',
            'tax_code' => 'GNTSMN77E25F839H',
            'description' => 'Docente di anatomia applicata al movimento e pratica scenica.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/29.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Alessia',
            'last_name' => 'Santoro',
            'email' => 'alessia.santoro@example.it',
            'phone' => '+39 321 905 4482',
            'tax_code' => 'SNTLSS94C16E625P',
            'description' => 'Insegnante di danza di carattere e coreografia per saggi e esibizioni.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/54.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Riccardo',
            'last_name' => 'Mancini',
            'email' => 'riccardo.mancini@example.it',
            'phone' => '+39 347 118 9031',
            'tax_code' => 'MNCRCC82A30L219R',
            'description' => 'Maestro di acrobatica e floor work. Segue la preparazione fisica degli allievi TAM.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/84.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Beatrice',
            'last_name' => 'Rizzo',
            'email' => 'beatrice.rizzo@example.it',
            'phone' => '+39 330 667 2290',
            'tax_code' => 'RZZBTR96D55F205Q',
            'description' => 'Docente ospite per laboratorio coreografico e composizione scenica.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/81.jpg',
        ],
        [
            'type' => 'permanent',
            'first_name' => 'Tommaso',
            'last_name' => 'Giordano',
            'email' => 'tommaso.giordano@example.it',
            'phone' => '+39 345 550 7719',
            'tax_code' => 'GRDTMS84H09H501M',
            'description' => 'Insegnante di pratica e tecnica maschile per i primi anni di balletto.',
            'photo_url' => 'https://randomuser.me/api/portraits/men/36.jpg',
        ],
        [
            'type' => 'temporary',
            'first_name' => 'Laura',
            'last_name' => 'Longo',
            'email' => 'laura.longo@example.it',
            'phone' => '+39 328 441 6603',
            'tax_code' => 'LNGLRA90L42D612S',
            'description' => 'Specialista in preparazione esami RAD e coaching individuale per allievi avanzati.',
            'photo_url' => 'https://randomuser.me/api/portraits/women/19.jpg',
        ],
    ];

    public function run(): void
    {
        foreach (self::TEACHERS as $data) {
            $photoUrl = $data['photo_url'];
            unset($data['photo_url']);

            $firstName = $data['first_name'];
            $lastName = $data['last_name'];
            $data['name'] = trim($firstName.' '.$lastName);

            $teacher = Teacher::query()->updateOrCreate(
                ['email' => $data['email']],
                $data,
            );

            $this->storePhoto($teacher, $photoUrl);
        }
    }

    private function storePhoto(Teacher $teacher, string $photoUrl): void
    {
        $response = Http::timeout(20)->get($photoUrl);

        if (! $response->successful()) {
            $fallbackGender = str_contains($photoUrl, '/women/') ? 'women' : 'men';
            $response = Http::timeout(20)->get(
                "https://randomuser.me/api/portraits/{$fallbackGender}/".(($teacher->id % 50) + 10).'.jpg'
            );
        }

        if (! $response->successful()) {
            return;
        }

        if ($teacher->profilePhoto() !== null) {
            return;
        }

        app(SecureFileService::class)->storeBinary(
            $teacher,
            'profile_photo',
            $response->body(),
            'avatar.jpg',
        );
    }
}

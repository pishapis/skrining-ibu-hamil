<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\EducationContent;
use App\Models\EducationTag;
use App\Models\EducationMedia;
use App\Models\EducationRule;

class EducationDemoSeeder extends Seeder
{
    public function run(): void
    {
        // --- pilih author (admin/superadmin kalau ada, otherwise user pertama)
        $author = User::whereIn('role_id', [2,3])->first() ?? User::firstOrFail();

        // pastikan folder ada
        Storage::disk('public')->makeDirectory('edu');

        // helper bikin file png 1x1 (placeholder) -> akan di-stretch di UI (cover/slide)
        $pngTransparent = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMB/axuQH8AAAAASUVORK5CYII=');
        $slide1 = 'edu/demo-slide-1.png';
        $slide2 = 'edu/demo-slide-2.png';
        $slide3 = 'edu/demo-slide-3.png';
        Storage::disk('public')->put($slide1, $pngTransparent);
        Storage::disk('public')->put($slide2, $pngTransparent);
        Storage::disk('public')->put($slide3, $pngTransparent);

        // ==== Konten 1: Galeri gambar ber-slide ====
        $c1 = EducationContent::create([
            'author_id'    => $author->id,
            'title'        => 'Relaksasi Nafas 4–6 — Galeri Langkah (Slide)',
            'slug'         => Str::slug('Relaksasi Nafas 4–6 Galeri').'-'.Str::random(5),
            'summary'      => 'Langkah-langkah singkat teknik nafas 4–6 untuk membantu menenangkan pikiran.',
            'body'         => <<<MD
### Langkah Singkat
1. **Tarik napas 4 hitungan** lewat hidung.
2. **Hembuskan 6 hitungan** lewat mulut.
3. Ulang **10 siklus** tiap sesi (pagi & malam).

> Tips: lakukan di tempat nyaman, punggung tegak, bahu rileks.
MD,
            'visibility'   => 'public',
            'status'       => 'published',
            'published_at' => now(),
            'reading_time' => 1,
        ]);

        // tag
        $tagIds = collect(['relaksasi','pernapasan','kesehatan-mental'])->map(function($name){
            $slug = Str::slug($name);
            return EducationTag::firstOrCreate(['slug'=>$slug], ['name'=>$name])->id;
        })->all();
        $c1->tags()->sync($tagIds);

        // media (slide)
        $m1 = EducationMedia::create([
            'content_id' => $c1->id,
            'media_type' => 'image',
            'path'       => $slide1,
            'alt'        => 'Langkah 1',
            'caption'    => 'Duduk nyaman, punggung tegak.',
            'sort_order' => 0,
        ]);
        EducationMedia::create([
            'content_id' => $c1->id,
            'media_type' => 'image',
            'path'       => $slide2,
            'alt'        => 'Langkah 2',
            'caption'    => 'Tarik napas 4 hitungan, hembus 6 hitungan.',
            'sort_order' => 1,
        ]);
        EducationMedia::create([
            'content_id' => $c1->id,
            'media_type' => 'image',
            'path'       => $slide3,
            'alt'        => 'Langkah 3',
            'caption'    => 'Ulangi 10 siklus, 2x sehari.',
            'sort_order' => 2,
        ]);
        // jadikan slide pertama sebagai cover
        $c1->update(['cover_path' => $m1->path]);

        // (opsional) rule targeting example: tampil untuk EPDS 0-9
        EducationRule::create([
            'content_id'     => $c1->id,
            'screening_type' => 'epds',
            'dimension'      => 'epds_total',
            'min_score'      => 0,
            'max_score'      => 9,
            'trimester'      => null,
        ]);

        // ==== Konten 2: Video (embed YouTube) ====
        $c2 = EducationContent::create([
            'author_id'    => $author->id,
            'title'        => 'Teknik Grounding 5–4–3–2–1 (Video Panduan)',
            'slug'         => Str::slug('Teknik Grounding 54321 Video').'-'.Str::random(5),
            'summary'      => 'Latihan fokus ke indera untuk menurunkan cemas secara cepat.',
            'body'         => <<<MD
### Cara Praktis
- Sebut **5 hal yang kamu lihat**  
- **4 hal yang kamu rasakan** (sentuhan)  
- **3 hal yang kamu dengar**  
- **2 hal yang kamu cium**  
- **1 hal yang kamu kecap**

> Lakukan saat sinyal cemas mulai naik. Ulangi perlahan, nafas teratur.
MD,
            'visibility'   => 'public',
            'status'       => 'published',
            'published_at' => now(),
            'reading_time' => 1,
        ]);

        $tagIds2 = collect(['grounding','cemas','dass'])->map(function($name){
            $slug = Str::slug($name);
            return EducationTag::firstOrCreate(['slug'=>$slug], ['name'=>$name])->id;
        })->all();
        $c2->tags()->sync($tagIds2);

        // media: EMBED youtube
        EducationMedia::create([
            'content_id'  => $c2->id,
            'media_type'  => 'embed',
            'external_url'=> 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'alt'         => 'Video Grounding 5-4-3-2-1',
            'sort_order'  => 0,
        ]);

        // (opsional) rule targeting example: DASS-ANX >= 12
        EducationRule::create([
            'content_id'     => $c2->id,
            'screening_type' => 'dass',
            'dimension'      => 'dass_anx',
            'min_score'      => 12,
            'max_score'      => null,
            'trimester'      => null,
        ]);

        // ==== Konten 3: Teks saja ====
        $c3 = EducationContent::create([
            'author_id'    => $author->id,
            'title'        => 'Tidur Nyenyak Selama Kehamilan: Panduan Singkat',
            'slug'         => Str::slug('Tidur Nyenyak Selama Kehamilan').'-'.Str::random(5),
            'summary'      => 'Tips higiene tidur yang aman dan sederhana untuk ibu hamil.',
            'body'         => <<<MD
### Kebiasaan Kecil untuk Kualitas Tidur
- Redupkan lampu **1 jam** sebelum tidur.  
- Hindari layar ponsel/TV mendekati jam tidur.  
- Minum air hangat, lakukan peregangan ringan.  
- Catat pikiran yang mengganggu ke kertas, **tunda** urus esok hari.

> Jika sulit tidur > 3 malam berturut-turut dan mengganggu aktivitas, pertimbangkan konsultasi.

### Catatan
Tidak semua tips cocok untuk semua orang. Amati yang paling membantu untukmu, lalu **konsisten** melakukannya 7–14 hari.
MD,
            'visibility'   => 'public',
            'status'       => 'published',
            'published_at' => now(),
            'reading_time' => 2,
        ]);

        $tagIds3 = collect(['tidur','trimester','self-care'])->map(function($name){
            $slug = Str::slug($name);
            return EducationTag::firstOrCreate(['slug'=>$slug], ['name'=>$name])->id;
        })->all();
        $c3->tags()->sync($tagIds3);

        // tanpa media dan tanpa rules → konten umum
    }
}


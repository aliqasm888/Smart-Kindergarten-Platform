<?php

namespace Database\Seeders;

use App\Models\Activity;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run()
    {
        Activity::updateOrCreate([
            'python_script_name' => 'slct',
        ], [
            'name' => 'اختبار تمييز الحروف المتشابهة (SLCT)',
            'type' => 'attention',
            'level' => 'easy',
            'description' => 'يقوم الطفل بالبحث عن الحروف المستهدفة ضمن شبكة من الحروف المشتتة، مما يقيس مدى تركيزه ودقته.'
        ]);

        Activity::updateOrCreate([
            'python_script_name' => 'slct',
            'level' => 'medium'
        ], [
            'name' => 'اختبار تمييز الحروف المتشابهة (SLCT)',
            'type' => 'attention',
            'description' => 'نسخة أكثر صعوبة من نفس التمرين، تتطلب عدد أكبر من الحروف وتحليل أدق.'
        ]);

        Activity::updateOrCreate([
            'python_script_name' => 'slct',
            'level' => 'hard'
        ], [
            'name' => 'اختبار تمييز الحروف المتشابهة (SLCT)',
            'type' => 'attention',
            'description' => 'نسخة متقدمة للأطفال الأكبر سنًا، تركيز أعلى وعدد أهداف أكبر.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'memory_numbers',
            'level' => 'easy'
        ], [
            'name' => 'اختبار ذاكرة الأرقام',
            'type' => 'memory',
            'description' => 'يطلب من الطفل تذكر سلسلة من الأرقام وترتيبها.'
        ]);

        Activity::updateOrCreate([
            'python_script_name' => 'memory_numbers',
            'level' => 'medium'
        ], [ 'name' => 'اختبار ذاكرة الأرقام',
            'type' => 'memory',
            'description' => 'يطلب من الطفل تذكر سلسلة من الأرقام وترتيبها.']);

        Activity::updateOrCreate([
            'python_script_name' => 'memory_numbers',
            'level' => 'hard'
        ], [ 'name' => 'اختبار ذاكرة الأرقام',
            'type' => 'memory',
            'description' => 'يطلب من الطفل تذكر سلسلة من الأرقام وترتيبها.']);
        Activity::updateOrCreate([
            'python_script_name' => 'voice_sequence',
            'level' => 'easy'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'voice_sequence',
            'level' => 'medium'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'voice_sequence',
            'level' => 'hard'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);

        Activity::updateOrCreate(
            ['python_script_name' => 'order_shapes'],
            [
                'name' => 'إعادة ترتيب الصور',
                'description' => 'يقوم الطفل بإعادة ترتيب مجموعة من الصور بنفس الترتيب المعروض سابقاً.',
                'type' => 'memory',
                'level' => 'easy', // أو medium أو hard حسب المطلوب
                'python_script_name' => 'image_order'
            ]
        );
        Activity::updateOrCreate(
            ['python_script_name' => 'order_shapes'],
            [
                'name' => 'إعادة ترتيب الصور',
                'description' => 'يقوم الطفل بإعادة ترتيب مجموعة من الصور بنفس الترتيب المعروض سابقاً.',
                'type' => 'memory',
                'level' => 'medium', // أو medium أو hard حسب المطلوب
                'python_script_name' => 'image_order'
            ]
        );
        Activity::updateOrCreate(
            ['python_script_name' => 'order_shapes'],
            [
                'name' => 'إعادة ترتيب الصور',
                'description' => 'يقوم الطفل بإعادة ترتيب مجموعة من الصور بنفس الترتيب المعروض سابقاً.',
                'type' => 'memory',
                'level' => 'hard', // أو medium أو hard حسب المطلوب
                'python_script_name' => 'image_order'
            ]
        );
        Activity::updateOrCreate(
            ['python_script_name' => 'difference'],
            [
                'name' => 'find difference between images',
                'type' => 'memory',
                'description' => 'find difference between images',
                'level' => 'easy', // أو medium أو hard حسب المطلوب
                'python_script_name' => 'image'
            ]
        );
        Activity::updateOrCreate(
            ['python_script_name' => 'difference'],
            [
                'name' => 'find difference between images',
                'type' => 'memory',
                'description' => 'find difference between images',
                'level' => 'medium', // أو medium أو hard حسب المطلوب
                'python_script_name' => 'image'
            ]
        );
        Activity::updateOrCreate(
            ['python_script_name' => 'difference'],
            [
                'name' => 'find difference between images',
                'type' => 'memory',
                'description' => 'find difference between images',
                'level' => 'hard', // أو medium أو hard حسب المطلوب
                'python_script_name' => 'image'
            ]
        );
        Activity::updateOrCreate([
            'python_script_name' => 'classify',
            'level' => 'easy'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'classify',
            'level' => 'medium'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'classify',
            'level' => 'hard'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'raven',
            'level' => 'easy'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'raven',
            'level' => 'medium'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'raven',
            'level' => 'hard'
        ], [
            'name' => 'اختبار التسلسل الصوتي',
            'type' => 'memory',
            'description' => 'يقوم الطفل بذكر الكلمات المعروضة عليه بصوت.'
        ]);

        Activity::updateOrCreate([
            'python_script_name' => 'size_order',
            'level' => 'easy'
        ], [
            'name' => 'ترتيب الأحجام',
            'type' => 'attention',
            'description' => 'اختيار ترتيب (صغير-متوسط-كبير) ضمن الزمن المعياري حسب العمر',
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'size_order',
            'level' => 'medium'
        ], [
            'name' => 'ترتيب الأحجام',
            'type' => 'attention',
            'description' => 'اختيار ترتيب (صغير-متوسط-كبير) ضمن الزمن المعياري حسب العمر',
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'size_order',
            'level' => 'hard'
        ], [
            'name' => 'ترتيب الأحجام',
            'type' => 'attention',
            'description' => 'اختيار ترتيب (صغير-متوسط-كبير) ضمن الزمن المعياري حسب العمر',
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'maze',
            'level' => 'easy'
        ], [
            'name' => 'المتاهة',
            'type' => 'attention',
            'description' => 'اختيار المتاهة ضمن الزمن المعياري حسب العمر',
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'maze',
            'level' => 'medium'
        ], [
            'name' => 'المتاهة',
            'type' => 'attention',
            'description' => 'اختيار المتاهة ضمن الزمن المعياري حسب العمر',
        ]);
        Activity::updateOrCreate([
            'python_script_name' => 'maze',
            'level' => 'hard'
        ], [
            'name' => 'المتاهة',
            'type' => 'attention',
            'description' => 'اختيار المتاهة  ضمن الزمن المعياري حسب العمر',
        ]);




    }
}

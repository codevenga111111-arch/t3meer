<?php

namespace Database\Seeders;

use App\Models\Lookup;
use Illuminate\Database\Seeder;

/**
 * قوائم الاختيارات الموحدة (قرار 7.1#13 + مهمة ClickUp #7) — SPEC 3.17.
 *
 * القوائم المزروعة (كلها بـ code إنجليزي ثابت + name_ar):
 *  - governorates:               27 محافظة (القيمة المخزنة = name_ar، 2.0.5).
 *  - exhibition_categories:      23 فئة رئيسي + لون (تظهر في الفلاتر).
 *  - exhibition_sub_categories:  96 بنداً فرعياً مرتبطة بـ parent_code.
 *  - engineering_services:       16 خدمة موحّدة = تخصصات المهندس + خدمات
 *                                العميل (القائمتان نفسهما في الأصل وتختلف
 *                                فقط في الهمزات → قائمة واحدة server-side،
 *                                القرار 13). name_ar مطابق للبيانات القديمة
 *                                في services.name و analytics_counts.
 *  - office_services:            8 خدمات مكتب (قائمة legacy محفوظة للتوثيق).
 *  - exhibition_services:        14 خدمة معرض موحّدة (8 معرض + 6 مورد —
 *                                قرار 0.5؛ site-delivery و wholesale-prices
 *                                تمّ map لهما على delivery-shipping و wholesale).
 *  - engineer_majors:            2 تخصص رئيسي (معماري/مدني).
 *
 * idempotent: upsert على UNIQUE(group_key, code) — إعادة التشغيل تحدّث ولا
 * تكرّر. القيم تُكتب كما هي في القاعدة الحية (مطابقة للبيانات المُستوردة).
 */
class LookupsSeeder extends Seeder
{
    /** أيقونة الكتالوج الموحدة لفئات/خدمات المعرض (SPEC 2.875). */
    private const EXHIBITION_ICON = 'solar:double-alt-arrow-right-bold';

    public function run(): void
    {
        $this->seedGovernorates();
        $this->seedExhibitionCategories();
        $this->seedEngineeringServices();
        $this->seedOfficeServices();
        $this->seedExhibitionServices();
        $this->seedEngineerMajors();

        $this->command?->info('+ '.Lookup::query()->count().' صف lookup (بعد التوحيد)');
    }

    // ------------------------------------------------------------------
    // المحافظات — 27 قيمة (SPEC 2.0.5): القيمة المخزنة = الاسم العربي.
    // ------------------------------------------------------------------
    private function seedGovernorates(): void
    {
        $this->upsert('governorates', [
            ['cairo', 'القاهرة'],
            ['giza', 'الجيزة'],
            ['alexandria', 'الإسكندرية'],
            ['dakahlia', 'الدقهلية'],
            ['sharqia', 'الشرقية'],
            ['qalyubia', 'القليوبية'],
            ['gharbia', 'الغربية'],
            ['monufia', 'المنوفية'],
            ['beheira', 'البحيرة'],
            ['kafr-el-sheikh', 'كفر الشيخ'],
            ['damietta', 'دمياط'],
            ['port-said', 'بورسعيد'],
            ['ismailia', 'الإسماعيلية'],
            ['suez', 'السويس'],
            ['fayoum', 'الفيوم'],
            ['beni-suef', 'بني سويف'],
            ['minya', 'المنيا'],
            ['assiut', 'أسيوط'],
            ['sohaga', 'سوهاج'],
            ['qena', 'قنا'],
            ['luxor', 'الأقصر'],
            ['aswan', 'أسوان'],
            ['red-sea', 'البحر الأحمر'],
            ['new-valley', 'الوادي الجديد'],
            ['matrouh', 'مطروح'],
            ['north-sinai', 'شمال سيناء'],
            ['south-sinai', 'جنوب سيناء'],
        ]);
    }

    // ------------------------------------------------------------------
    // فئات المعرض 23 + الفرعية 96 (SPEC 3.17 showroomSupplierCategories
    // و showroomSupplierSubCategories) — الفرعية ترث لون أمها.
    // ------------------------------------------------------------------
    private function seedExhibitionCategories(): void
    {
        $categories = [
            ['building-materials', 'مواد البناء الأساسية', '#0070d7'],
            ['iron-metals', 'الحديد والمعادن', '#34d399'],
            ['concrete-construction', 'الخرسانة والإنشاءات', '#94a3b8'],
            ['insulation', 'العزل', '#64748b'],
            ['plumbing', 'السباكة والمواسير', '#FC7753'],
            ['electricity', 'الكهرباء', '#f59e0b'],
            ['wood', 'الأخشاب', '#a16207'],
            ['gypsum-ceilings', 'الجبس والأسقف', '#e879f9'],
            ['repair-chemicals', 'مواد الترميم والكيماويات', '#f43f5e'],
            ['tools-equipment', 'العدد والمعدات', '#fb923c'],
            ['furniture', 'الأثاث والفرش', '#22c55e'],
            ['ceramics-porcelain', 'السيراميك والبورسلين', '#0070d7'],
            ['sanitary-fixtures', 'الأدوات الصحية', '#22c55e'],
            ['lighting', 'الإضاءة', '#f59e0b'],
            ['paint-decor', 'الدهانات والديكور', '#e879f9'],
            ['doors-windows', 'الأبواب والشبابيك', '#a78bfa'],
            ['marble-granite', 'الرخام والجرانيت', '#34d399'],
            ['facades', 'الواجهات الخارجية', '#38bdf8'],
            ['landscape', 'اللاندسكيب', '#4ade80'],
            ['kitchens-dressing', 'المطابخ والدريسنج', '#0070d7'],
            ['hvac', 'التكييف والتهوية', '#38bdf8'],
            ['solar-energy', 'الطاقة الشمسية', '#f59e0b'],
            ['security-safety', 'الأمن والسلامة', '#f43f5e'],
        ];

        $rows = [];
        foreach ($categories as [$code, $name, $color]) {
            $rows[] = ['code' => $code, 'name_ar' => $name, 'color' => $color,
                'icon' => self::EXHIBITION_ICON];
        }
        $this->upsert('exhibition_categories', $rows);

        // البنود الفرعية: parent_code = كود الفئة الأم، اللون = لون الأم.
        $colorByCode = [];
        foreach ($categories as [$code, , $color]) {
            $colorByCode[$code] = $color;
        }

        $sub = [
            'building-materials' => [
                ['cement', 'أسمنت'],
                ['bricks', 'طوب'],
                ['sand-aggregate', 'رمل وسن'],
                ['gypsum', 'جبس'],
                ['ready-mix-concrete', 'خرسانة جاهزة'],
            ],
            'iron-metals' => [
                ['rebar', 'حديد تسليح'],
                ['steel-sheets', 'صاج وملفوفات'],
                ['angles-beams', 'زوايا وكمرات'],
                ['wire-mesh', 'أسلاك شبك'],
            ],
            'concrete-construction' => [
                ['concrete-additives', 'إضافات خرسانة'],
                ['concrete-blocks', 'بلوك خرساني'],
                ['cement-products', 'منتجات أسمنتية'],
            ],
            'insulation' => [
                ['waterproofing', 'عزل مائي'],
                ['thermal-insulation', 'عزل حراري'],
                ['acoustic-insulation', 'عزل صوتي'],
                ['bitumen-rolls', 'لفائف بتومين'],
            ],
            'plumbing' => [
                ['water-pipes', 'مواسير مياه'],
                ['drainage-pipes', 'مواسير صرف'],
                ['water-tanks', 'خزانات مياه'],
                ['water-pumps', 'مضخات مياه'],
                ['valves-fittings', 'محابس ووصلات'],
            ],
            'electricity' => [
                ['wires-cables', 'أسلاك وكابلات'],
                ['distribution-panels', 'لوحات توزيع'],
                ['circuit-breakers', 'قواطع كهربائية'],
                ['conduits', 'خراطيم وتمديدات'],
            ],
            'wood' => [
                ['beech-wood', 'خشب زان'],
                ['swedish-wood', 'خشب سويدي'],
                ['abilache-wood', 'أبلاكاش'],
                ['mdf-wood', 'خشب MDF'],
            ],
            'gypsum-ceilings' => [
                ['gypsum-boards', 'ألواح جبس بورد'],
                ['ceiling-accessories', 'إكسسوارات تعليق'],
                ['gypsum-cornices', 'كرانيش جبس'],
            ],
            'repair-chemicals' => [
                ['epoxy-grout', 'أديبوند وجراوت'],
                ['repair-mortar', 'مونة ترميم'],
                ['concrete-treatment', 'مواد معالجة خرسانة'],
            ],
            'tools-equipment' => [
                ['hand-tools', 'عدد يدوية'],
                ['power-tools', 'معدات كهربائية'],
                ['scaffolds', 'سقالات'],
                ['measuring-tools', 'أدوات قياس'],
            ],
            'furniture' => [
                ['bedrooms', 'غرف نوم'],
                ['living-sets', 'غرف معيشة وصالونات'],
                ['carpets-rugs', 'سجاد وموكيت'],
                ['curtains-furnishings', 'ستائر ومفروشات'],
                ['office-furniture', 'أثاث مكتبي'],
                ['outdoor-furniture', 'أثاث خارجي'],
            ],
            'ceramics-porcelain' => [
                ['floor-tiles', 'سيراميك أرضيات'],
                ['wall-tiles', 'سيراميك حوائط'],
                ['porcelain', 'بورسلين'],
                ['skirting-decor', 'وزرات وديكورات'],
            ],
            'sanitary-fixtures' => [
                ['bathroom-sets', 'أطقم حمامات'],
                ['faucets', 'خلاطات مياه'],
                ['kitchen-sinks', 'أحواض مطابخ'],
                ['shower-cabinets', 'كابينات شاور'],
                ['jacuzzi', 'جاكوزي'],
            ],
            'lighting' => [
                ['chandeliers', 'نجف وثريات'],
                ['led-lighting', 'إضاءة ليد (LED)'],
                ['spotlights', 'كشافات وسبوتات'],
                ['outdoor-lighting', 'إضاءة خارجية'],
                ['table-lamps', 'أباجورات وإضاءة طاولة'],
            ],
            'paint-decor' => [
                ['interior-paint', 'دهانات داخلية'],
                ['exterior-paint', 'دهانات خارجية'],
                ['putty-primer', 'معجون وبرايمر'],
                ['wallpaper', 'ورق حائط'],
                ['wood-marble-alternative', 'بديل خشب ورخام'],
            ],
            'doors-windows' => [
                ['wooden-doors', 'أبواب خشبية'],
                ['armored-doors', 'أبواب مصفحة'],
                ['aluminum-windows', 'شبابيك ألومنيوم (ألوتال)'],
                ['pvc-windows', 'شبابيك PVC'],
                ['iron-gates', 'بوابات حديد'],
            ],
            'marble-granite' => [
                ['natural-marble', 'رخام طبيعي'],
                ['natural-granite', 'جرانيت طبيعي'],
                ['engineered-marble', 'رخام صناعي'],
                ['marble-stairs-kitchens', 'درج ومطابخ رخام'],
            ],
            'facades' => [
                ['natural-stone', 'حجر طبيعي'],
                ['engineered-stone', 'حجر صناعي'],
                ['grc', 'جي آر سي (GRC)'],
                ['cladding', 'كلادينج'],
            ],
            'landscape' => [
                ['artificial-grass', 'نجيل صناعي'],
                ['interlock-pavers', 'إنترلوك وبردورات'],
                ['pergolas', 'مظلات وبرجولات'],
                ['ornamental-plants', 'نباتات زينة'],
            ],
            'kitchens-dressing' => [
                ['aluminum-kitchens', 'مطابخ ألومنيوم'],
                ['wooden-kitchens', 'مطابخ خشبية'],
                ['dressing-rooms', 'دريسنج روم'],
                ['kitchen-accessories', 'إكسسوارات مطابخ'],
            ],
            'hvac' => [
                ['split-ac', 'تكييفات إسبليت'],
                ['central-ac', 'تكييفات مركزي'],
                ['ventilation-fans', 'مراوح تهوية'],
                ['copper-pipes-insulation', 'مواسير نحاس وعزل'],
            ],
            'solar-energy' => [
                ['solar-panels', 'ألواح شمسية'],
                ['solar-heaters', 'سخانات شمسية'],
                ['batteries-converters', 'بطاريات ومحولات'],
            ],
            'security-safety' => [
                ['cctv-cameras', 'كاميرات مراقبة'],
                ['fire-alarm', 'أنظمة إنذار حريق'],
                ['attendance-devices', 'أجهزة حضور وانصراف'],
                ['fire-extinguishers', 'طفايات حريق'],
            ],
        ];

        $rows = [];
        foreach ($sub as $parent => $items) {
            foreach ($items as [$code, $name]) {
                $rows[] = ['code' => $code, 'name_ar' => $name,
                    'parent_code' => $parent, 'color' => $colorByCode[$parent] ?? null,
                    'icon' => self::EXHIBITION_ICON];
            }
        }
        $this->upsert('exhibition_sub_categories', $rows);
    }

    // ------------------------------------------------------------------
    // الخدمات الهندسية 16 — قائمة موحّدة (تخصصات المهندس + خدمات العميل).
    // الأيقونات والألوان من engineerSpecializations (SPEC 3.17).
    // ------------------------------------------------------------------
    private function seedEngineeringServices(): void
    {
        $this->upsert('engineering_services', [
            ['architectural-design', 'تصميم معماري', 'material-symbols:architecture', '#38bdf8'],
            ['quantity-surveying', 'حصر كميات', 'mdi:calculator', '#34d399'],
            ['shop-drawings', 'رسومات تنفيذية', 'ph:house-line', '#f59e0b'],
            ['project-management', 'إدارة مشاريع', 'mdi:eye-outline', '#22c55e'],
            ['interior-design', 'تصميم داخلي', 'solar:sofa-2-bold', '#a78bfa'],
            ['finishing', 'تشطيبات', 'solar:paint-roller-bold', '#e879f9'],
            ['engineering-consulting', 'استشارات هندسية', 'solar:chat-round-call-bold', '#fb923c'],
            ['building-permits', 'تراخيص بناء', 'solar:document-bold', '#4ade80'],
            ['surveying', 'رفع مساحة', 'solar:map-point-bold', '#fb923c'],
            ['legalization', 'تصالح للبناء', 'solar:check-read-bold', '#f59e0b'],
            ['structural-design', 'تصميم انشائي', 'mdi:pillar', '#a78bfa'],
            ['facade-design', 'تصميم واجهات', 'solar:window-frame-bold', '#38bdf8'],
            ['insulation-works', 'اعمال عزل', 'solar:shield-bold', '#64748b'],
            ['concrete-works', 'اعمال خرسانات', 'material-symbols:wall-art-rounded', '#94a3b8'],
            ['restoration-works', 'اعمال ترميم', 'solar:restart-bold', '#f43f5e'],
            ['syndicate-services', 'خدمات نقابية', 'solar:users-group-rounded-bold', '#0070d7'],
        ]);
    }

    // ------------------------------------------------------------------
    // خدمات المكتب 8 (SPEC 2.0.x officeServices) — قائمة محفوظة لتوثيق
    // الكود القديم؛ الفورم الفعلي يعرض قائمة المهندس الموحدة (4480).
    // ------------------------------------------------------------------
    private function seedOfficeServices(): void
    {
        $this->upsert('office_services', [
            ['architectural-design', 'تصميم معماري'],
            ['building-permits', 'تراخيص بناء'],
            ['surveying', 'رفع مساحة'],
            ['engineering-drawing', 'رسم هندسي'],
            ['legalization', 'تصالح للبناء'],
            ['structural-design', 'تصميم انشائي'],
            ['supervision', 'اشراف علي التنفيذ'],
            ['syndicate-services', 'خدمات نقابية'],
        ]);
    }

    // ------------------------------------------------------------------
    // خدمات المعرض الموحدة 14 (قرار 0.5): 8 خدمات المعرض + 6 من المورد
    // (site-delivery → delivery-shipping و wholesale-prices → wholesale
    // مُدمجتان وقت النقل فلا تُكرَّر هنا).
    // ------------------------------------------------------------------
    private function seedExhibitionServices(): void
    {
        $services = [
            ['delivery-shipping', 'توصيل وشحن'],
            ['installation', 'تركيب'],
            ['design-consulting', 'استشارات تصميم'],
            ['custom-manufacturing', 'تصنيع حسب الطلب'],
            ['product-warranty', 'ضمان على المنتجات'],
            ['wholesale', 'بيع بالجملة'],
            ['inspection-measurement', 'معاينة وقياسات'],
            ['after-sales-service', 'خدمة ما بعد البيع'],
            ['payment-plans', 'تسهيلات سداد'],
            ['free-samples', 'عينات مجانية'],
            ['technical-support', 'دعم فني'],
            ['quality-certificates', 'شهادات جودة'],
            ['return-policy', 'سياسة إرجاع'],
            ['supply-contracts', 'عقود توريد'],
        ];

        $rows = [];
        foreach ($services as [$code, $name]) {
            $rows[] = ['code' => $code, 'name_ar' => $name,
                'icon' => self::EXHIBITION_ICON, 'color' => '#FC7753'];
        }
        $this->upsert('exhibition_services', $rows);
    }

    // ------------------------------------------------------------------
    // التخصص الرئيسي للمهندس (SPEC 3.17 engineerMajors) — القيمة المخزنة
    // في engineers.major هي الاسم العربي (القاعدة الحية: معمار.ي مدني).
    // ------------------------------------------------------------------
    private function seedEngineerMajors(): void
    {
        $this->upsert('engineer_majors', [
            ['architectural', 'معماري'],
            ['civil', 'مدني'],
        ]);
    }

    // ------------------------------------------------------------------
    /**
     * إدراج/تحديث مجموعة كاملة — idempotent على (group_key, code).
     * الوزن يبدأ من 1 بترتيب القائمة (معروض في الكتالوج والفلاتر).
     *
     * @param  array<int, array{0: string, 1: string, 2?: string, 3?: string}>  $rows
     *                                                                                 [code, name, icon?, color?]
     * @param  array<string, array{code: string, name_ar: string, icon?: ?string, color?: ?string, parent_code?: ?string}>  $assocRows
     */
    private function upsert(string $groupKey, array $rows): void
    {
        $now = now();
        $payload = [];

        foreach (array_values($rows) as $index => $row) {
            // الشكل المختصر [code, name, icon?, color?] أو assoc array.
            $normalized = isset($row['code'])
                ? $row
                : ['code' => $row[0], 'name_ar' => $row[1],
                    'icon' => $row[2] ?? null, 'color' => $row[3] ?? null];

            $payload[] = [
                'group_key' => $groupKey,
                'code' => $normalized['code'],
                'name_ar' => $normalized['name_ar'],
                'icon' => $normalized['icon'] ?? null,
                'color' => $normalized['color'] ?? null,
                'parent_code' => $normalized['parent_code'] ?? null,
                'weight' => $index + 1,
                'active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        Lookup::upsert(
            $payload,
            ['group_key', 'code'],
            ['name_ar', 'icon', 'color', 'parent_code', 'weight', 'active', 'updated_at'],
        );
    }
}

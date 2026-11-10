# تعمير (T3meer) — مرجع الـ Backend لتحويل المشروع إلى Laravel REST API

> **الغرض:** الملف ده بيوصف المشروع بالكامل كما هو في الكود الحالي: الـ structure، جداول Supabase وكل الأعمدة، الـ Views والـ RPCs، الـ Auth، كل الفورمز بكل الـ inputs والـ validation، وكل القراءات. وجنب كل واحدة فيهم REST endpoint مقترح بالـ body والـ response، عشان فريق الـ Backend يبني الـ APIs بنفس السلوك.
>
> **المصدر:** كود المشروع عند commit `4ef3be0` (2026-10-27). الفولدر نظيف ومفيهوش تعديلات غير محفوظة. الملف اتعمل يوم 2026-11-03.
> **مهم:** قاعدة البيانات الحية ماكانتش متاحة وقت التحليل، فالـ schema متجمّع من الكود. شوف 0.3.3 و 7.2 قبل كتابة الـ migrations.
> **الاصطلاحات:** `path:line` = مكان الكود في المشروع · **(inferred)** / **(استنتاج)** = مستنتج من الاستخدام · **(مقترح)** = توصية للـ Laravel مش موجودة في الكود · كل الـ endpoints المكتوبة **مقترحة** ومتعملتش ولا اتجربت.

## الفهرس

- 0. نظرة عامة واتفاقيات الـ API
  - 0.1 نبذة عن المشروع
  - 0.2 المعمارية الحالية (اللي هيتم استبدالها)
  - 0.3 هيكل المشروع (Project Structure)
  - 0.4 اتفاقيات الـ REST API المقترحة (Conventions)
  - 0.5 قرار: المورد مش نوع مستخدم مستقل
- 1. قاعدة البيانات وطبقة البيانات (Database Schema & Data Layer)
  - 1.1 ملخص سريع
  - 1.2 الجداول بالتفصيل
    - 1.2.1 `users` (`public.users`)
    - 1.2.2 `engineers`
    - 1.2.3 `offices`
    - 1.2.4 `exhibitions`
    - 1.2.5 `materials` (legacy
    - 1.2.6 `services`
    - 1.2.7 `projects`
    - 1.2.8 `products`
    - 1.2.9 `user_validation`
    - 1.2.10 `plans`
    - 1.2.11 `subscriptions`
    - 1.2.12 `subscription_payments`
    - 1.2.13 `user_permissions`
    - 1.2.14 `customers` (طلبات خدمة العملاء)
    - 1.2.15 `favorites`
    - 1.2.16 `rating`
    - 1.2.17 `stats`
    - 1.2.18 `analytics_counts`
    - 1.2.19 `marketing`
    - 1.2.20 `materials_prices`
    - 1.2.21 `interaction_logs`
  - 1.3 الـ Views
  - 1.4 دوال RPC والـ Triggers والـ Enums
  - 1.5 نموذج المصادقة (Auth Model)
  - 1.6 الـ Next.js Route Handlers (يجب أن تصبح endpoints في Laravel)
  - 1.7 التكاملات الخارجية
  - 1.8 قواعد العمل الموجودة في طبقة البيانات
  - 1.9 مشاكل أمنية يجب أن يصلحها الـ Rebuild
  - 1.10 ما هو موثَّق وغير مستخدم، والتعارضات، وما لم يمكن تحديده
- 2. المصادقة والـ Onboarding (Auth + Onboarding)
  - 2.0 قواعد مشتركة لكل شاشات القسم
  - إنشاء حساب جديد
  - تسجيل الدخول
  - نسيت كلمة المرور
  - إعادة تعيين كلمة المرور
  - تفعيل البريد الإلكتروني
  - توثيق المهندس
  - توثيق المكتب الهندسي
  - 2.V خطوة التوثيق (`user_validation` step)
    - 2.V.1 مين بيمر بخطوة التوثيق
    - 2.V.2 مكان الخطوة في الـ flow (الـ State Machine بعد الإضافة)
    - 2.V.3 حالات التوثيق وتأثيرها على الحساب
    - 2.V.4 الجدول
    - 2.V.5 الـ Endpoints
    - 2.V.6 تأثير الخطوة على باقي الـ Endpoints
    - 2.V.7 تعديلات الفرونت المطلوبة عشان الخطوة تشتغل (للفريق اللي هيربط الـ API)
    - 2.V.8 قرارات مفتوحة (والقيمة الافتراضية المكتوبة فوق)
  - اختيار الباقة
  - تأكيد الاشتراك والدفع
  - إكمال الملف
  - إكمال الملف
  - إكمال الملف
  - إكمال الملف
  - إعداد حساب العميل
  - 2.A Onboarding State Machine
  - 2.B الجداول المتأثرة (Tables touched)
  - 2.C نقاط غامضة وقرارات مطلوبة من فريق الـ backend
- 3. لوحة تحكم المستخدم (غير الأدمن)
  - 3.0 ملاحظات عامة
  - 3.1 جدول الصفحات (page key → النوع → Component)
  - 3.2 الـ Sidebar والإجراءات العامة
  - 3.2.2 رفع الصورة الشخصية
  - 3.2.3 تسجيل الخروج
  - 3.2.4 حذف الحساب نهائياً
  - 3.3 الرئيسية
  - 3.4 تعديل الحساب
  - 3.4.1 تعديل بيانات المهندس
  - 3.4.2 تعديل بيانات المكتب
  - 3.4.3 تعديل بيانات المعرض
  - 3.4.4 تعديل بيانات المورد
  - 3.4.5 تغيير كلمة المرور
  - 3.5 المشاريع
  - إضافة / تعديل مشروع
  - 3.5.1 حذف مشروع
  - 3.6 المنتجات
  - إضافة / تعديل / حذف منتج
  - 3.7 الخدمات / التخصصات / تصنيفات المنتجات
  - إضافة خدمة من الكتالوج / تصنيف مخصص / حذف
  - 3.8 الباقات والاشتراك
  - طلب ترقية / تجديد / تفعيل باقة مجانية / تحديث إيصال التحويل
  - 3.9 التسويق والظهور
  - حفظ بانر / منشور سوشيال / Facebook Pixel (فورم واحد بثلاث نقاط حفظ)
  - 3.10 طلبات العملاء للمكتب/المهندس
  - تسجيل نقرة اتصال / واتساب
  - (غير مستخدمة في الواجهة) تحديث حالة الطلب من المكتب
  - 3.11 طلبات الخدمات للعميل
  - 3.11.1 طلب مكتب بديل (رفض المكتب المعيّن)
  - 3.11.2 تقييم المكتب المعيّن
  - 3.11.3 حذف طلب الخدمة
  - 3.11.4 إنشاء طلب خدمة جديد
  - 3.12 المفضلة
  - حذف من المفضلة
  - 3.13 تقييماتي
  - 3.14 النشاط الأخير
  - 3.15 مرجع حدود الباقات (نص تسويقي في `features/auth/pricing/api/pricing-static.ts`)
  - 3.16 Tables touched (لهذا القسم)
  - 3.17 القيم الثابتة (Enums / Options)
  - 3.18 أهم الملاحظات للـ Backend (ملخص المخاطر)
- 4. لوحة تحكم الأدمن
  - خريطة الصفحات (Route → Component → Purpose)
  - إزاي صلاحية الأدمن بتتشيك حالياً
  - ملاحظات عامة على الـ data layer
  - الإجراءات (Write Actions)
  - قبول طلب توثيق
  - رفض طلب توثيق
  - تغيير حالة اشتراك
  - تفعيل باقة يدوياً
  - إعادة إرسال رابط تفعيل البريد
  - تعديل ملف مهندس/مكتب وحالته
  - إضافة صنف سعر مادة
  - تعديل صنف سعر مادة
  - حذف صنف سعر مادة
  - ترشيح/تعيين مكتب أو مهندس لطلب عميل (وإعادة الترشيح بعد الرفض)
  - تغيير حالة طلب عميل
  - حذف طلب عميل
  - تسجيل نقرة تواصل (واتساب/اتصال) على طلب عميل
  - تبديل ظهور الباقات في الصفحة الرئيسية
  - القراءات (Data Reads)
  - إحصائيات عامة + Funnel التسجيل
  - إعدادات الظهور في الصفحة الرئيسية
  - دليل المستخدمين
  - طلبات التوثيق
  - سجل الاشتراكات
  - الإحصائيات المالية
  - جدول المهندسين / المكاتب
  - تفاصيل مهندس / مكتب
  - أسعار المواد
  - طلبات العملاء + الإحصائيات
  - قايمة المكاتب/المهندسين القابلة للتعيين
  - Status / enum values اللي ظاهرة في كود الأدمن
  - الجداول اللي نطاق الأدمن بيلمسها
  - Security notes (لازم تتحول server-side)
  - سلوكيات في الكود الحالي محتاجة قرار من الباك إند (Quirks / Bugs)
- 5. الموقع العام (Public Site)
  - 5.1 جدول الصفحات العامة → المكونات → البيانات → الـ Endpoints المقترحة
  - 5.2 القراءات (Data Reads)
    - 5.2.0 قواعد عامة لكل القوائم في الموقع العام
    - 5.2.1 بانرات السلايدر الرئيسي
    - 5.2.2 أفضل المكاتب الهندسية (الرئيسية)
    - 5.2.3 أبرز المهندسين (الرئيسية)
    - 5.2.4 إعدادات عرض الرئيسية (Homepage display settings)
    - 5.2.5 أعمال شركائنا المميزة
    - 5.2.6 أرقام نفخر بها (إحصائيات عامة)
    - 5.2.7 أسعار مواد البناء
    - 5.2.8 ماذا يقول عملاؤنا (تقييمات المنصة)
    - 5.2.9 المقالات (اليومية + صفحة المقال)
    - 5.2.10 بانرات الدليل
    - 5.2.11 قائمة دليل المهندسين
    - 5.2.12 قائمة دليل المكاتب الهندسية
    - 5.2.13 قائمة دليل المعارض والموردين
    - 5.2.14 قيم فلاتر الدليل (Filter options)
    - 5.2.15 حالة المفضلة لكل كارت/ملف
    - 5.2.16 تفاصيل المهندس
    - 5.2.17 تفاصيل المكتب الهندسي
    - 5.2.18 تفاصيل المعرض/المورد
    - 5.2.19 قائمة التقييمات + "قيّمت قبل كده؟"
    - 5.2.20 تفاصيل المشروع
    - 5.2.21 الـ Layout العام (Navbar / Footer / Auth)
    - 5.2.22 الصفحات الثابتة
  - 5.3 النماذج والعمليات (Forms / Mutations)
    - 5.3.1 إضافة تقييم لملف مهني (مهندس / مكتب / معرض)
    - 5.3.2 تقييم المنصة ("قيم تجربتك")
    - 5.3.3 تبديل المفضلة (Favorite toggle)
    - 5.3.4 نموذج "اتصل بنا"
    - 5.3.5 تتبع تفاعلات الملفات (visit / whatsapp / call / …)
    - 5.3.6 تتبع تحليلات المنصة (page visits + فلاتر الدليل)
    - 5.3.7 Meta Conversions API
    - 5.3.8 عمليات الـ Navbar (للمستخدم المسجل)
    - 5.3.9 أزرار مفيهاش Backend
  - 5.4 Tables touched (للدمج في قسم الـ Schema)
- 6. فهرس الـ Endpoints المقترحة (ملخص موحد)
  - 6.1 Auth & Account
  - 6.2 Onboarding (مقدمي الخدمة + العميل)
  - 6.3 الموقع العام (Public)
  - 6.4 لوحة تحكم المستخدم (`/me/...`)
  - 6.5 الأدمن (`/admin/...`)
- 7. قرارات مطلوبة قبل البدء + مشاكل لازم تتصلح في الـ Rebuild
  - 7.1 قرارات بيانات / Business (لازم تتحسم قبل الـ migrations)
  - 7.2 حاجات مش موجودة في الكود (لازم تتجاب من الداتابيز الحالية)
  - 7.3 مشاكل أمنية (لازم تتقفل في Laravel)
  - 7.4 ترتيب تنفيذ مقترح للفريق

---

## 0. نظرة عامة واتفاقيات الـ API

### 0.1 نبذة عن المشروع

**تعمير (T3meer)** منصة/دليل رقمي لقطاع البناء والتشطيب في مصر، بتربط بين:

| الدور | `user_type` | وصف مختصر |
|---|---|---|
| العميل | `customer` | يتصفح الأدلة، يضيف مفضلات وتقييمات، ويقدّم طلب خدمة (`customers`) يتم توجيهه لمكتب هندسي. |
| المهندس | `engineer` | ملف مهني + سابقة أعمال (projects) + خدمات. |
| المكتب الهندسي | `office` | ملف شركة + مشاريع + خدمات + استقبال طلبات العملاء اللي الأدمن بيوزعها عليه. |
| المعارض وموردين مواد البناء | `exhibition` | نوع واحد للمعارض (تشطيب، ديكور، أثاث) وتجار مواد البناء، ليه نفس التسجيل والفورمز والداشبورد والدليل (`showrooms-suppliers`) + منتجات (products). **مفيش نوع `material` مستقل** (القسم 0.5). |
| المدير | `admin` | لوحة تحكم كاملة (مستخدمين، توثيق، اشتراكات، أسعار، طلبات عملاء، إعدادات الصفحة الرئيسية). |

مقدمي الخدمة (engineer / office / exhibition) بيمرّوا بمسار تسجيل (Onboarding) متعدد الخطوات يتحكم فيه `current_step`: تأكيد الإيميل، ثم **التوثيق (للمهندس والمكتب، القسم 2.V)**، ثم اختيار باقة ودفع (InstaPay / محفظة)، ثم إكمال الملف.

---

### 0.2 المعمارية الحالية (اللي هيتم استبدالها)

| الطبقة | التقنية الحالية | ملاحظة للـ Laravel |
|---|---|---|
| Frontend | Next.js 16 (App Router) + React 19 + TypeScript + Tailwind 4 | هيفضل زي ما هو، وهيتغيّر بس طبقة الـ `api/*.api.ts` عشان تنادي الـ REST API. |
| Data fetching | React Query v5 (`*.query.ts`) + Zustand (`store/useUserStore.ts`) للـ session | — |
| Database | Supabase (PostgreSQL) | الجداول موثقة في القسم 1. |
| Auth | Supabase Auth (`auth.users` + `user_metadata`) + trigger `handle_new_user` بينسخ لجدول `public.users` | يتحول لـ Laravel Sanctum (Bearer token) + جدول `users`. |
| Business logic | **في المتصفح مباشرة**: الفرونت بيكلم Supabase بالـ anon key (`lib/supabase.ts`) — حتى عمليات الأدمن | كل اللوجيك ده لازم ينتقل للسيرفر (Controllers/Services + Policies). |
| Files | Cloudinary unsigned upload من المتصفح (`apis/global.api.ts → uploadToCloudinary(file, folder)`)، والـ DB بيخزن الـ URL بس | شوف القسم 0.3.4. |
| Server routes | شوية Next.js route handlers في `app/api/**` | لازم تتعمل Endpoints في Laravel برضه (موثقة في القسم 1.6). |
| Analytics / Ads | Meta Pixel + Meta Conversions API (`src/services/meta.service.ts`)، و RPC `increment_analytics` | — |

> **مهم:** مفيش Supabase Storage مستخدم. ومفيش Edge Functions. كل حاجة هي: جداول + Views + RPC functions + Auth.

---

### 0.3 هيكل المشروع (Project Structure)

```text
t3meer/
├── app/                          # Next.js routes (صفحات + route handlers)
│   ├── (auth)/                   # login, register, verify-email, forgot/reset-password,
│   │                             # validation/{engineer|office}, pricing/[type], payment/[type], complete-profile/[type]
│   ├── api/                      # Next.js route handlers (لازم تتحول لـ Laravel endpoints)
│   │   ├── admin/homepage-display/route.ts
│   │   ├── analytics/track/route.ts
│   │   ├── auth/update-email/route.ts
│   │   ├── homepage/route.ts
│   │   ├── meta/event/route.ts , meta/purchase/route.ts
│   │   └── public/stats/route.ts
│   ├── dashboard/[type]/[page]/  # لوحة تحكم المستخدم حسب النوع والصفحة
│   ├── dashboard/admin/**        # لوحة الأدمن
│   ├── directory/[type]/[id]/    # الأدلة العامة + صفحة التفاصيل
│   ├── customer/setup/           # إعداد العميل بعد التسجيل
│   ├── projects/[id]/, articles/[id]/, about/, contact/, privacy/, terms/
│   └── layout.tsx, page.tsx (الرئيسية), manifest.ts (PWA)
├── features/<feature>/           # Feature modules
│   ├── api/*.api.ts              # ← هنا كل استدعاءات Supabase (المرجع الأساسي للـ APIs)
│   ├── api/*.query.ts            # React Query hooks
│   ├── api/*.server.ts           # استدعاءات من Server Components
│   ├── components/*.tsx          # الفورمز والشاشات
│   ├── static.ts / constants.ts  # قوائم الاختيارات (محافظات، تخصصات، تصنيفات...)
│   └── types.ts
├── apis/global.api.ts            # رفع الصور على Cloudinary
├── lib/                          # supabase client, helpers (slug), step-redirect, track-metric, homepage settings
├── src/                          # Meta CAPI service + types (subscription)
├── store/useUserStore.ts         # Zustand session store (cookie: user-storage)
├── middleware.ts                 # حماية الروتس وتوجيه حسب current_step
├── data/homepage-display-settings.json   # إعدادات عرض الصفحة الرئيسية (ملف JSON على السيرفر)
├── supabase_signup_trigger.sql   # trigger handle_new_user + enums
└── supabase_update_email_rpc.sql # RPC update_unconfirmed_user_email
```

Feature modules الموجودة: `admin`, `auth/{signup,login,forgot-password,verify-email,validation,pricing,payment,profile}`, `customer`, `dashboard/{home,profile,products,projects,services,plans,marketing,office,customer}`, `directory`, `engineers`, `offices`, `exhibitions`, `materials`, `showrooms-suppliers`, `projects`, `prices`, `testimonials`, `analysis`, `articles`, `contact`, `about`, `privacy`, `terms`, `home`.

#### 0.3.1 خريطة الصفحات (Routes)

| Route | Auth | الوصف |
|---|---|---|
| `/` | Public | الصفحة الرئيسية (مكاتب، مهندسين، معارض، مشاريع، أسعار خامات، إحصائيات، آراء) |
| `/directory/[type]` | Public | دليل: `engineers` / `offices` / `showrooms-suppliers` (المعارض والموردين). الكود الحالي فيه كمان `exhibitions` و`materials` بنفس المصدر، وفي Laravel دول بيبقوا redirect لـ `showrooms-suppliers` (0.5) |
| `/directory/[type]/[id]` | Public | صفحة تفاصيل مقدم الخدمة (تقييمات، مفضلة، تواصل) |
| `/projects/[id]` | Public | تفاصيل مشروع |
| `/articles/[id]`, `/about`, `/contact`, `/privacy`, `/terms` | Public | محتوى |
| `/register`, `/login`, `/forgot-password`, `/reset-password`, `/verify-email` | Public | المصادقة |
| `/validation/engineer`, `/validation/office` | Auth (step 1) | خطوة التوثيق للمهندس والمكتب (القسم 2.V) |
| `/pricing/[type]` | Auth | اختيار الباقة |
| `/payment/[type]` | Auth | الدفع ورفع الإيصال |
| `/complete-profile/[type]` | Auth (step 3) | إكمال الملف المهني |
| `/customer/setup` | Auth (customer) | إعداد العميل / طلب خدمة |
| `/dashboard/[type]/[page]` | Auth | لوحة تحكم المستخدم |
| `/dashboard/admin/**` | Admin | لوحة الأدمن |

`middleware.ts` بيحمي `/dashboard`, `/complete-profile`, `/payment`, `/customer/*` (ما عدا `/customer/setup`)، وبيقرأ `user_type` و `current_step` من الـ cookie `user-storage` أو من الـ JWT. في Laravel الحماية دي لازم تبقى على مستوى الـ API نفسه (middleware `auth:sanctum` + role/step checks)، والفرونت هيفضل يعمل redirect بناءً على response الـ `/auth/me`.

#### 0.3.2 متغيرات البيئة (أسماء فقط)

| المتغير | الاستخدام |
|---|---|
| `NEXT_PUBLIC_SUPABASE_URL`, `NEXT_PUBLIC_SUPABASE_ANON_KEY`, `SUPABASE_SERVICE_ROLE_KEY` | Supabase (هيتشالوا بعد التحويل) |
| `NEXT_PUBLIC_APP_URL` | روابط الإيميلات (تأكيد البريد، reset password) |
| `NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME`, `NEXT_PUBLIC_CLOUDINARY_UPLOAD_PRESET` | رفع الصور (preset: `t3meer_images`) |
| `NEXT_PUBLIC_INSTAPAY_LINK`, `NEXT_PUBLIC_WALLET_LINK` | روابط الدفع اليدوي في صفحة الدفع |
| `NEXT_PUBLIC_META_PIXEL_ID`, `META_ACCESS_TOKEN`, `META_TEST_EVENT_CODE` | Meta Pixel / Conversions API |

#### 0.3.3 مصدر البيانات في الملف ده

- قاعدة البيانات الحية **ماكانتش متاحة** وقت التحليل، فالجداول والأعمدة في القسم 1 **مستخرجة من الكود** (كل `select` / `insert` / `update` / `filter`) + ملفات الـ SQL الموجودة. الأنواع المكتوب جنبها `(inferred)` مستنتجة من الاستخدام.
- **قبل ما الفريق يبدأ الـ migrations** يُفضّل تصدير الـ schema الفعلي ومقارنته بالملف ده:
  `supabase db dump --schema-only -f schema.sql` (أو `pg_dump --schema-only` على connection string بتاع Supabase) — ده هيأكد الأنواع والـ constraints والـ RLS والـ views بالظبط.
- ملفات التحليل القديمة في المشروع (`database_schema.md`, `t3meer_full_analysis.md`, `full_project_analysis.md`) فيها أجزاء **مقترحة ولسه ما اتنفذتش** أو قديمة؛ الملف ده هو المرجع لأنه مبني على الكود الحالي.

#### 0.3.4 رفع الملفات (Cloudinary)

حاليًا: المتصفح بيرفع الملف مباشرة لـ `https://api.cloudinary.com/v1_1/{cloud}/{image|raw}/upload` بـ unsigned preset `t3meer_images` و `folder`، وبعدين يبعت الـ `secure_url` في الـ payload كـ string.

خياران للـ Laravel:
1. **(الأسهل والمتوافق مع الفرونت الحالي)**: الـ API يستقبل الـ URLs كـ strings (`'image_url' => 'nullable|url'`)، والفرونت يفضل يرفع على Cloudinary. يُفضّل يتعمل endpoint `POST /api/v1/uploads/signature` يرجّع signed upload params بدل الـ unsigned preset.
2. **(أأمن)**: الـ API يستقبل `multipart/form-data` ويرفع هو على Cloudinary/S3 ويرجّع الـ URL: `POST /api/v1/uploads` → `{ "url": "..." }`.

في الحالتين: أعمدة الصور في الداتابيز `TEXT` (URL) أو `TEXT[]`/`JSONB` للمعارض (gallery). تفاصيل كل حقل ملف (الفولدر، العمود، الحد الأقصى) موجودة جنب كل فورم.

---

### 0.4 اتفاقيات الـ REST API المقترحة (Conventions)

| البند | المقترح |
|---|---|
| Base URL | `/api/v1` |
| Auth | Laravel Sanctum — `Authorization: Bearer {token}` |
| Content-Type | `application/json` (و `multipart/form-data` لو اتختار رفع الملفات عن طريق الـ API) |
| Roles | `user_type` ∈ `customer`, `engineer`, `office`, `exhibition` (المعارض والموردين، 0.5)، `admin` → middleware `role:admin` إلخ + Policies للملكية (owner) |
| Onboarding | `current_step` يتخزن في `users` ويرجع في `/auth/me`؛ الـ API هو اللي يزوّده (مش الفرونت) |
| Pagination | `?page=1&per_page=12` → `{ data: [...], meta: { current_page, per_page, total, last_page } }` |
| Filtering/Sort | `?search=&governorate=&sort=` (موضح لكل endpoint) |
| Response envelope | `{ "data": ..., "message": "..." }` |
| Validation errors | HTTP 422 + `{ "message": "...", "errors": { "field": ["رسالة بالعربي"] } }` (الفرونت حاليًا بيعرض رسايل عربي — يُفضّل الحفاظ عليها) |
| IDs | الجداول المرتبطة بالمستخدم بتستخدم `user_id` (UUID) — يُفضّل الإبقاء على UUID لـ `users.id` عشان الـ slugs/الروابط والبيانات المنقولة |
| Timestamps | `created_at`, `updated_at` بصيغة ISO 8601 |
| Ownership | أي `user_id` / `owner_id` في الـ body **يتجاهل** ويتاخد من `auth()->id()` (الكود الحالي بيبعته من الفرونت) |

### 0.5 قرار: المورد مش نوع مستخدم مستقل — المعارض والموردين نوع واحد `exhibition`

> ✅ **القرار المعتمد للـ Laravel:** مفيش `user_type = material` ولا `supplier`. مورد مواد البناء بيسجّل كـ **`exhibition`**، ويستخدم نفس التسجيل والتوثيق (مش مطلوب) والباقات وفورم إكمال الملف والداشبورد والدليل العام بتوع المعرض بالظبط.
> ده متوافق مع الواجهة الحالية أصلاً: شاشة التسجيل بتعرض `exhibition` باسم "معارض وموردين"، والدليل العام `/directory/showrooms-suppliers` بيقرأ من `public_exhibitions_view` بس (`getPublicMaterials` مش مستخدمة)، و`formatUserType` بيعرض `exhibition` كـ "معرض او مورد". كود `material` الموجود legacy، ومفيش طريقة تسجيل حالية بتطلّع النوع ده (القسم 2).
> أي مكان في الملف بيوصف `material` / `materials` / `/dashboard/material` / `MaterialProfileForm` / `EditMaterialProfile` / `public_materials_view` بيوصف **الكود الحالي** (كمرجع لنقل البيانات)، ومش هيتعمل في Laravel.
> ⚠️ **ماتتلخبطش مع `materials_prices`:** ده جدول أسعار خامات البناء (حديد، أسمنت…) اللي الأدمن بيعدّله وبيظهر في الرئيسية. **مالوش علاقة** بالقرار ده وهيفضل زي ما هو.

**أنواع المستخدمين في Laravel:** `customer` · `engineer` · `office` · `exhibition` (معارض وموردين) · `admin`.

| الجزء | الكود الحالي | في Laravel |
|---|---|---|
| `users.user_type` | `material` (داشبورد/routes) و`supplier` (enum الـ trigger) | **`exhibition`**. الـ enum: `customer, engineer, office, exhibition, admin` |
| جدول البروفايل | `materials` (1.2.5) | **`exhibitions`** بس (1.2.4) — جدول `materials` مايتعملش |
| View | `public_materials_view` | مايتعملش. الدليل من `exhibitions` |
| التسجيل | `RegisterForm` بيعرض 4 أنواع (من غيرها) | زي ما هو: `customer, engineer, office, exhibition` |
| الباقات (`plans`) | صفوف `user_type = material` + نص أسعار المورد في `pricing-static.ts` | باقات `exhibition` بس. صفوف `material` تتحذف |
| إكمال الملف | `/complete-profile/material` → `MaterialProfileForm` | `POST /api/v1/onboarding/profile/exhibition` (نفس فورم المعرض) |
| الداشبورد | `/dashboard/material/*` → `EditMaterialProfile` | نفس صفحات `exhibition`: الملف، المنتجات، تصنيفات المنتجات، الباقات، التسويق |
| الدليل العام | `/directory/showrooms-suppliers` + `/directory/exhibitions` + `/directory/materials` | دليل واحد `GET /api/v1/exhibitions` (`showrooms-suppliers`). الروابط القديمة `exhibitions` و`materials` تعمل 301 redirect في الفرونت |
| التصنيف الرئيسي | `exhibitionCategories` = `materialCategories` = `showroomSupplierCategories` (نفس القائمة، 23 فئة) | قائمة واحدة `showroomSupplierCategories` في `exhibitions.main_category`، وهي اللي بتفرّق "مواد بناء" عن "أثاث" مثلاً، زي فلتر الدليل الحالي |
| الخدمات | `exhibitionServices` (8) و`materialServices` (8) قايمتين منفصلتين | **(مقترح)** قائمة واحدة للمعارض والموردين (تحت) |
| Cloudinary | `materials/profiles` | `exhibitions/profiles` |
| قيم `type` في الجداول التانية | `favorites.type`, `rating.type`, `stats.type`, `user_validation.account_type`, `plans.user_type`, `subscriptions.user_type`, `marketing.user_type`، و`analytics_counts` (`page = materials`) فيها `material` | تتحول `exhibition`، و`analytics_counts.page = materials` تتجمع على `showrooms-suppliers` |

**قائمة خدمات `exhibition` الموحدة (مقترح):** الـ 8 بتوع المعرض زي ما هم: `delivery-shipping` توصيل وشحن، `installation` تركيب، `design-consulting` استشارات تصميم، `custom-manufacturing` تصنيع حسب الطلب، `product-warranty` ضمان على المنتجات، `wholesale` بيع بالجملة، `inspection-measurement` معاينة وقياسات، `after-sales-service` خدمة ما بعد البيع.
يتضاف عليهم 6 من خدمات المورد: `payment-plans` تسهيلات سداد، `free-samples` عينات مجانية، `technical-support` دعم فني، `quality-certificates` شهادات جودة، `return-policy` سياسة إرجاع، `supply-contracts` عقود توريد.
والقيمتين المكررتين يتعملهم map وقت نقل البيانات: `site-delivery` → `delivery-shipping`، و`wholesale-prices` → `wholesale`.
لو عايزين قائمة المعرض الحالية بس (8)، الـ 6 الزيادة يتعملهم map لأقرب قيمة أو يتشالوا.

**نقل البيانات (Data migration):**
1. `users`: `UPDATE users SET user_type = 'exhibition' WHERE user_type IN ('material','supplier')` (الـ `slug` مايتغيرش، فالروابط تفضل شغالة بعد الـ redirect).
2. `materials` → `exhibitions` (insert لو مفيش صف لنفس `user_id`):
   - أعمدة بنفس الاسم: `name`, `governorate`, `city`, `bio`, `whats_app`, `open_hours`, `main_category`, `experience_years`, `count_of_categories`, `latitude`, `longitude`.
   - `profile_image` → `profile_image` و`logo`.
   - `specializations` (values) → صفوف `services` بالـ labels، بعد الـ map اللي فوق.
3. الحقول الخاصة بالمورد في `EditMaterialProfile` (`price_list`, `quantities`, `certificates`, `delivery_areas`, `truck_types`, `delivery_cost`, `address`, `website`) **مش موجودة في المعرض**، ومش مؤكد إنها موجودة أصلاً في الـ DB (1.2.5). الافتراضي: **تتشال**. ولو اتلقى فيها بيانات حقيقية وقت الـ dump، يتقرر يا إما تتضاف كأعمدة اختيارية (`jsonb`) في `exhibitions` لكل المعارض، يا إما تتساب.
4. أعمدة الـ `type` في الجداول التانية (الجدول فوق): `material` → `exhibition`.
5. `plans` و`user_permissions`: صفوف `material` تتحذف. المستخدمين المنقولين بياخدوا حدود باقة `exhibition` المماثلة (`basic` / `professional` / `premium`).
6. جدول `materials` و`public_materials_view` يتشالوا بعد التأكد من النقل.

---

## 1. قاعدة البيانات وطبقة البيانات (Database Schema & Data Layer)

> **منهجية هذا القسم:** قاعدة البيانات الحية غير متاحة، فكل ما هنا مُعاد بناؤه من استخدام الكود فقط (`.from()` / `.rpc()` / `auth.*` / ملفي الـ SQL). الكود هو المرجع، والملفات القديمة (`database_schema.md`، `t3meer_full_analysis.md`، `full_project_analysis.md`) استُخدمت كتلميحات فقط.
> - **(inferred)** = استنتاج من طريقة الاستخدام وليس من تعريف صريح.
> - **"only-if-provided"** = عمود يُرسل في payload فقط لو القيمة موجودة. `supabase-js` يحذف المفاتيح التي قيمتها `undefined` من الـ JSON، فوجود العمود فعلاً في الـ DB **غير مؤكد**.
> - كل الوصول للبيانات يتم **من المتصفح مباشرة** بالـ anon key (`lib/supabase.ts:1-11`)، فأي "قاعدة عمل" تجدها هنا كانت تُنفَّذ في الواجهة ويجب نقلها للـ Laravel API.

### 1.1 ملخص سريع

- **21 جدول**، **6 Views**، **6 دوال RPC** (منها `get_all_stats` غير الموجودة في القائمة المبدئية: `features/dashboard/home/api/home.api.ts:311`)، و**trigger واحد** على `auth.users`.
- تحققتُ من قائمة الجداول بالبحث عن كل `.from(` بما فيها الاستدعاءات الديناميكية `.from(table)` (`admin.api.ts:577,1008`، `dashboard/profile/api/profile.api.ts:44,218`، `home.api.ts:52,76`). الاستدعاءات الديناميكية لا تلمس إلا `engineers`/`offices`/`exhibitions`/`materials`.
- أربعة components تستدعي `supabase.from("users")` مباشرة خارج ملفات الـ api: `PricingLayout.tsx:42`، `CustomerSetup.tsx:151`، `TestimonialsSection.tsx:151`، و`lib/helper-functions.ts:231`.

| الجدول | PK (inferred) | العلاقة بالمستخدم | الغرض | العمليات من الكود |
|---|---|---|---|---|
| `users` | `uuid` = `auth.users.id` | — | الحساب المركزي ونوعه وحالته وخطوة التسجيل | select, update (الـ insert يتم من الـ trigger فقط) |
| `engineers` | int/bigint | `user_id` UNIQUE → `users.id` | ملف المهندس | select, upsert |
| `offices` | int/bigint | `user_id` UNIQUE → `users.id` | ملف المكتب | select, upsert |
| `exhibitions` | ? | `user_id` UNIQUE → `users.id` | ملف المعرض/المورد | upsert (والقراءة من الـ view) |
| `materials` | ? | `user_id` UNIQUE → `users.id` | ملف مورد مواد (legacy) | upsert (والقراءة من الـ view أو dynamic) |
| `services` | int/bigint | `user_id` → `users.id` (بدون unique) | التخصصات/الخدمات/التصنيفات الفرعية | select, insert, delete |
| `projects` | uuid (inferred، string في TS) | `user_id` → `users.id` | سابقة الأعمال | select, insert, update, delete |
| `products` | int/bigint | `user_id` → `users.id` | منتجات المعارض/الموردين | select, insert, update, delete |
| `user_validation` | ? | `user_id` UNIQUE → `users.id` | طلبات التوثيق ومستنداته | select, upsert, update |
| `plans` | ? | — | أسعار الباقات لكل نوع ودورة سداد | select |
| `subscriptions` | uuid (inferred) | `user_id` UNIQUE → `users.id` | الاشتراك الحالي (صف واحد لكل مستخدم) | select, upsert, update |
| `subscription_payments` | ? | `user_id`، `subscription_id` | سجل المدفوعات (للمالية) | select فقط |
| `user_permissions` | int أو uuid (متعارض) | `user_id` | حدود وصلاحيات الباقة لكل مستخدم | select فقط |
| `customers` | uuid (inferred) | `user_id` nullable، `assigned_provider_id` uuid | طلبات خدمة العملاء | select, insert, update, delete |
| `favorites` | int/bigint | `user_id` → `users.id` | المفضلة | select, insert, delete |
| `rating` | int/bigint | `user_id` (الكاتب) → `users.id` | التقييمات وآراء المنصة | select, insert, update |
| `stats` | int/bigint | `user_id` nullable (الزائر) → `users.id` | عدادات تفاعل الملفات | select, insert, update |
| `analytics_counts` | ? | — | عدادات المنصة العامة | select (والكتابة عبر RPC فقط) |
| `marketing` | ? | `user_id` UNIQUE | بانر الإعلان والسوشيال والـ pixel | select, upsert |
| `materials_prices` | int (inferred) | — | أسعار خامات البناء (إداري) | select, upsert, delete |
| `interaction_logs` | ? | `user_id` | سجل نشاط | insert فقط (لا يُقرأ أبداً) |

---

### 1.2 الجداول بالتفصيل

#### 1.2.1 `users` (`public.users`)

صف واحد لكل حساب. الواجهة **لا تعمل insert أبداً** في هذا الجدول. الصف يُنشأ بالـ trigger `on_auth_user_created` بعد `INSERT` على `auth.users` (`supabase_signup_trigger.sql:18-113`) من الـ `raw_user_meta_data` المُرسلة وقت `signUp`.

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | `uuid` PK | NOT NULL | — | = `auth.users.id` (الـ trigger يُدخل `NEW.id`، sql:83). كل `user_id` في الجداول الأخرى يشير له. |
| `email` | text | NOT NULL، UNIQUE (inferred) | lowercase بعد تغيير البريد | يكتبه الـ trigger و`update_unconfirmed_user_email` و`verify-email.api.ts:84-96`. بحث `ilike` في `admin.api.ts:275,379`. |
| `name` | text | default `'مستخدم'` من الـ trigger (sql:30-35) | — | يُحدَّث من `updateUserBasicInfo` (`features/dashboard/profile/api/profile.api.ts:6-37`)، و`saveEngineerProfile`/`saveOfficeProfile` فقط (`features/auth/profile/api/profile.api.ts:54-67,183-196`). المعرض والمورد لا يحدّثانه. والأدمن أيضاً (`admin.api.ts:999-1005`). |
| `phone` | text | nullable | رقم مصري (تحقق client-side في `lib/utils.ts:25-30`) | بحث `ilike`. ربما UNIQUE (inferred من ترجمة خطأ "phone" في `signup.api.ts:67-69`). |
| `user_type` | enum `account_type` | الـ trigger يضع `'engineer'` كـ fallback | التسجيل يعرض: `customer`، `engineer`، `office`، `exhibition` (`RegisterForm.tsx:29-55`). legacy: `material`. يدوي: `admin`. في whitelist الـ SQL فقط: `supplier` | ⚠ whitelist الـ trigger لا يحوي `material` ولا `admin` (sql:38-42)، فأي تسجيل بـ `material` يتخزن `engineer` في `users.user_type` بينما الـ metadata تقول `material`. الفلاتر `.eq/.neq("user_type")` كثيرة في admin. |
| `slug` | text | UNIQUE (inferred) | `[a-z0-9-]` | يُولَّد في الواجهة (`generateUniqueSlugFromEmail`) ويُمرَّر في الـ metadata. الـ trigger يولّده لو فارغ. يتغير عند تغيير البريد (RPC). |
| `current_step` | int | default 1 (`COALESCE(...,1)`، sql:89) | 0–4 (انظر 1.5.6) | محرك الـ onboarding. |
| `status` | enum `status` | default `'pending'` | الكود يكتب: `pending`، `active`، `reject`. ويقرأ أيضاً `rejected`. whitelist الـ SQL: `pending`، `active`، `rejected`، `suspended` | ⚠ الأدمن يكتب `'reject'` (`admin.api.ts:203,214-217`؛ `ProfessionalDetails.tsx:141-143`) بينما الـ enum في SQL فيه `rejected`. إحصائية الرفض تبحث عن الاثنين (`admin.api.ts:120`). |
| `selected_plan` | enum `plan_type` | default `'free'` | الكود يكتب: `free`، `basic`، `professional`، `premium`. ويقرأ `pro` كمرادف. whitelist الـ SQL: `free`، `basic`، `pro`، `premium` | ⚠ تعارض `professional` مقابل `pro`. ترتيب `.order("selected_plan", desc)` يعتمد على ترتيب الـ enum (انظر 1.7.7). |
| `rejection_reason` | text | nullable | — | يكتبه الأدمن عبر `adminUpdateProfessionalProfile` (`ProfessionalDetails.tsx:142-143`) ويُقرأ في `dashboard.api.ts:33-51`. |
| `created_at` | timestamptz | default now() (inferred) | — | ترتيب في قوائم الأدمن. |

**قيم موجودة في `user_metadata` فقط وليست في `users`** ويجب أن تصبح أعمدة في Laravel: `selected_billing_cycle`، `selected_user_type`، `profile_image`/`image` (انظر 1.5.4).

**Used by:** `handle_new_user` trigger، `update_unconfirmed_user_email`، `generateUniqueSlugFromEmail` (`lib/helper-functions.ts:220-245`)، `updateUserEmail` (`verify-email.api.ts`)، `saveEngineerProfile`/`saveOfficeProfile`/`saveExhibitionProfile`/`saveMaterialProfile`/`updateUserStep` (`features/auth/profile/api/profile.api.ts`)، `updateUserValidation` (`validation.api.ts:28-31`)، `updateUserPlan` (`pricing.api.ts:55`)، `uploadPaymentConfirmation` (`payment.api.ts:72`)، `getUserStatus` (`dashboard.api.ts:33`)، `updateUserBasicInfo`، `PricingLayout.tsx:42-46`، `CustomerSetup.tsx:150-153`، `TestimonialsSection.tsx:151-155`، admin (`getAdminOverviewStats`، `getAdminUsers`، `updateVerificationStatus`، `manuallyOverrideSubscription`، `getAdminEngineersData`/`OfficesData`، `getAdminUserDetails`، `adminUpdateProfessionalProfile`، `getAdminCustomerRequests`، `getAssignableProviders`، `assignProviderToCustomerRequest`)، `app/api/meta/purchase/route.ts:51-55`.

---

#### 1.2.2 `engineers`

ملف المهندس، علاقة 1:1 مع `users`. الـ upsert يستخدم `onConflict: "user_id"` إذن هناك UNIQUE(`user_id`). الـ join `users!inner(...)` (`admin.api.ts:371`) يثبت وجود FK `engineers.user_id → users.id`.

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int/bigint PK (inferred: `IEngineerDetails.id: number`، `engineers.api.ts:26`) | | | |
| `user_id` | uuid | NOT NULL، UNIQUE | | FK → `users.id` |
| `name` | text | | | |
| `slug` | text | (unique inferred) | = `users.slug` | ⚠ **لا يكتبه أي upsert في الواجهة**، لكنه يُقرأ (`home.api.ts:288` يعتمد على `profile.slug`) ويُفلتر (`admin.api.ts:385,405`). يبدو أنه يُملأ بـ trigger أو default غير موجود في الريبو. في Laravel: املأه = `users.slug` عند الإنشاء. |
| `phone` | text | nullable | | only-if-provided (`features/auth/profile/api/profile.api.ts:29`)؛ الفورم الحالي لا يرسله. |
| `major` | text | | تخصص حر | بحث `ilike` (`admin.api.ts:386`، `engineers.api.ts:113`). |
| `experience_years` | int | | | `parseInt` في الواجهة. |
| `governorate` | text | | اسم محافظة. قد يحوي أكثر من محافظة مفصولة بـ `,` | الأدمن يقسمه بالفاصلة (`admin.api.ts:426`). |
| `city` | text | nullable | | only-if-provided. |
| `bio` | text | | | |
| `whats_app` | text | | | بحث `ilike` (`admin.api.ts:387`). |
| `website` | text | nullable | | only-if-provided. |
| `workingHours` | text (اسم camelCase بين علامتي تنصيص) | nullable | | only-if-provided. |
| `social` | jsonb | nullable | `{facebook,instagram,...}` | only-if-provided. |
| `skills` | text[] أو jsonb (inferred array) | | `string[]` | |
| `latitude`, `longitude` | numeric/float | nullable | | `parseFloat`. |
| `profile_image` | text (URL) | nullable | Cloudinary | من فولدر `engineers/profiles` أو `profiles`. |
| `coverImage` | text | nullable | | only-if-provided. |
| `videoUrl` | text | nullable | | only-if-provided. |
| `video` | text | nullable | | الأدمن يكتبه دائماً (`ProfessionalDetails.tsx:155`)، إذن العمود موجود. |
| `facebook`, `instagram`, `youtube` | text | nullable | | `updateEngineerProfile` (`features/dashboard/profile/api/profile.api.ts:57-110`) والأدمن. |
| `created_at` | timestamptz | default now() (inferred) | | ترتيب في admin. |
| `updated_at` | timestamptz | | | تكتبه الواجهة `new Date().toISOString()`. |

**Used by:** `saveEngineerProfile` (`features/auth/profile/api/profile.api.ts:3-122`)، `updateEngineerProfile`، `fetchProfileData`/`upsertProfileData`، `getUserProfile`/`updateProfileImage` (`home.api.ts:38-93`)، admin (`getAdminEngineersData`، `getAdminUserDetails`، `adminUpdateProfessionalProfile`، `getAssignableProviders`، `assignProviderToCustomerRequest`).

---

#### 1.2.3 `offices`

نفس نمط `engineers`: UNIQUE(`user_id`) و FK إلى `users` (`admin.api.ts:475`).

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int/bigint PK (inferred: `IOfficeDetails.id: number`) | | | |
| `user_id` | uuid | NOT NULL، UNIQUE | | FK → `users.id` |
| `name` | text | | | |
| `slug` | text | | = `users.slug` | نفس ملاحظة `engineers.slug`. |
| `governorate` | text | | قد تكون مفصولة بفاصلة | |
| `bio` | text | | | فورم التعديل يحوّل `designPhilosophy` إلى `bio` (`EditOfficeProfile.tsx:168`). |
| `whats_app` | text | | | بحث `ilike`. |
| `experience_years` | int | | | |
| `count_of_projects` | int | | | عدد يدخله المستخدم يدوياً، وليس count حقيقي. |
| `open_hours` | text | | | |
| `latitude`, `longitude` | numeric | nullable | | |
| `profile_image` | text | nullable | Cloudinary `offices/profiles` أو `profiles` | |
| `video` | text | nullable | Cloudinary `videos` | `EditOfficeProfile.tsx:173`، والأدمن. |
| `facebook`, `instagram`, `youtube` | text | default `""` من فورم التعديل | | |
| `created_at`, `updated_at` | timestamptz | | | |

لا تُكتب أبداً في `offices`: `phone`، `email`، `city`، `website`، `team_size`، `coverImage`. الدالة `saveOfficeProfile` تستقبل `city`/`phone`/`website`/`team_size` لكنها لا تضعها في `profileData` (`features/auth/profile/api/profile.api.ts:163-175`)، والـ `phone` يذهب إلى `users`. القيم `phone`/`email`/`website` التي تُقرأ في صفحة المكتب العامة تأتي من الـ view.

**Used by:** `saveOfficeProfile`، `updateOfficeProfile` (payload في `EditOfficeProfile.tsx:161-184`)، `getUserProfile`/`updateProfileImage`، و admin (نفس دوال المهندسين).

---

#### 1.2.4 `exhibitions`

ملف المعرض أو مورد المواد (النوع الموحّد `exhibition`). UNIQUE(`user_id`). **في Laravel ده الجدول الوحيد للمعارض والموردين**، وبيانات `materials` بتتنقل له (0.5).

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | ? PK | | | |
| `user_id` | uuid | NOT NULL، UNIQUE | | FK → `users.id` |
| `name` | text | | | |
| `slug` | text | nullable | = `users.slug` | **يُكتب هنا** من `updateExhibitionProfile` (`features/dashboard/profile/api/profile.api.ts:158`). |
| `main_category` | text | | label عربي من `exhibitionCategories` | يتحول لعربي قبل الحفظ (`EditExhibitionProfile.tsx:195-198`). |
| `governorate`, `city` | text | | | |
| `bio` | text | nullable | | |
| `whats_app` | text | nullable | | |
| `experience_years` | int | default 0 | | |
| `latitude`, `longitude` | numeric | nullable | | |
| `logo` | text | nullable | Cloudinary `exhibitions/profiles` أو `exhibitions/logos` | |
| `banner` | text | nullable | Cloudinary `exhibitions/banners` | |
| `profile_image` | text | nullable | Cloudinary `profiles` | يكتبه `updateProfileImage` عند تغيير الصورة من الـ Navbar/Sidebar (`home.api.ts:66-78`). |
| `count_of_categories` | int | nullable | | |
| `open_hours` | text | nullable | | |
| `video` | text | nullable | Cloudinary `videos` | |
| `brands` | text[] أو jsonb | default `[]` | `string[]` | |
| `facebook`, `instagram`, `youtube` | text | nullable | | |
| `created_at`, `updated_at` | timestamptz | | | |

**Used by:** `saveExhibitionProfile` (`features/auth/profile/api/profile.api.ts:246-329`)، `updateExhibitionProfile` (`features/dashboard/profile/api/profile.api.ts:135-195`)، `fetchProfileData`، `getUserProfile`/`updateProfileImage`.

---

#### 1.2.5 `materials` (legacy — ما زال مستخدماً في الكود)

> ⛔ **مش هيتعمل في Laravel:** المورد = `exhibition` (القسم 0.5). الوصف تحت للكود الحالي بس (مرجع لنقل البيانات).

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `user_id` | uuid | UNIQUE | | FK → `users.id` |
| `name`, `governorate`, `city`, `bio`, `whats_app`, `open_hours`, `main_category` | text | | | من `saveMaterialProfile` (`features/auth/profile/api/profile.api.ts:348-362`). |
| `experience_years`, `count_of_categories` | int | | | |
| `latitude`, `longitude` | numeric | | | |
| `profile_image` | text | | Cloudinary `materials/profiles` | |

⚠ `EditMaterialProfile.tsx:127-139` يعمل upsert لكائن الـ state **كاملاً** (`IMaterialProfile`: `id`، `phone`، `email`، `address`، `website`، `coverImage`، `social`، `skills`، `videoUrl`، `mainCategories`، `specializations`، `deliveryAvailable`، `materials`، `priceList`، `quantities`، `deliveryAreas`، `truckTypes`، `deliveryCost`، `certificates`… من `features/dashboard/profile/api/profile.type.ts:91-103`). الـ initial state فيه `id: "mat-101"` (`profile.static.ts:135`). لو هذه الأعمدة غير موجودة فـ PostgREST يرفض الطلب، يعني هذا الـ flow غالباً **معطّل**. شاشة التسجيل لم تعد تعرض نوع `material`، لكن المسارات `/pricing/material` و`/complete-profile/material` و`/dashboard/material` ما زالت موجودة.

**Used by:** `saveMaterialProfile`، `upsertProfileData("material")`، `fetchProfileData`، `getUserProfile`/`updateProfileImage`.

---

#### 1.2.6 `services`

جدول واحد يُستخدم لثلاثة أغراض: تخصصات المهندس، تخصصات المكتب، وتصنيفات المنتجات الفرعية للمعرض/المورد.

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int/bigint PK (`IService.id?: number`) | | | |
| `user_id` | uuid | NOT NULL | | FK → `users.id`. **ليس unique**. |
| `name` | text | | اسم عربي (من قوائم ثابتة أو اسم مخصص) | الحذف يتم بـ `match({name, slug})` أو `match({name, user_id})` (`services.api.ts:44-59`). |
| `icon` | text | | Iconify id | المخصص: `"solar:double-alt-arrow-right-bold"` |
| `color` | text | default في الواجهة `"#0070d7"` | hex | المخصص: `"#FC7753"` (`DashboardServices.tsx:146-171`). |
| `slug` | text | | slug المالك (نسخة مكررة denormalized) | يُستعلم به (`exhibitions.api.ts:81-84`). |
| `created_at` | timestamptz | | | ترتيب تصاعدي. |

في إكمال الملف: يُحذف كل ما يخص `user_id` ثم bulk insert (`features/auth/profile/api/profile.api.ts:69-90`). الـ views تجمع الخدمات كـ JSON array من الكائنات وتُفلتر بـ `.contains("services", [{name}])`.

**Used by:** `save*Profile`، `getUserServices`/`addUserService`/`deleteUserService`/`getAllUniqueDbServices` (`features/dashboard/services/api/services.api.ts`)، `getExhibitionDetailsBySlug`، `getAdminUserDetails`.

---

#### 1.2.7 `projects`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | uuid (inferred؛ string في `IProject` و`editingId`) PK | | | |
| `user_id` | uuid | NOT NULL | | FK → `users.id` |
| `name` | text | | | |
| `location` | text | | | |
| `area` | numeric | | | `Number()`. |
| `type` | text | | نوع العقار/المشروع | |
| `image` | text | | Cloudinary `projects` | |
| `slug` | text | | slug **المالك** | يُستخدم للتتبع: زيارة المشروع تُسجَّل على slug المالك (`ProjectDetailsView.tsx:103,127`). |
| `user_type` | text | | نوع المالك | |
| `user_name` | text | | اسم المالك (denormalized) | |
| `views` | int | | | موجود في TS فقط، ولا يُكتب ولا يُزاد في أي مكان. |
| `details` | jsonb | | `{video, brief, categories: string[], photos: string[]}` | الصور والفيديو من Cloudinary `projects` (`DashboardProjects.tsx:207-275`). |
| `created_at` | timestamptz | | | ترتيب تنازلي. |

**Used by:** `features/dashboard/projects/api/projects.api.ts` (get by id/user، add، update، delete)، `getAdminUserDetails`.

---

#### 1.2.8 `products`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int/bigint PK (`IProduct.id?: number`) | | | |
| `user_id` | uuid | NOT NULL | | FK → `users.id` |
| `name`, `category`, `sub_category` | text | | | |
| `image` | text | | Cloudinary `products` | |
| `price` | numeric | | | `Number()`. |
| `slug` | text | | slug المالك | |
| `details` | jsonb | nullable | `{description, photos: string[]}` | |
| `created_at` | timestamptz | | | |

الحد الأقصى للمنتجات يستخدم `max_projects` وليس عموداً خاصاً بالمنتجات (`DashboardProducts.tsx:50-59`).

**Used by:** `features/dashboard/products/api/products.api.ts`. المنتجات تظهر أيضاً كـ `products` داخل `public_exhibitions_view`.

---

#### 1.2.9 `user_validation`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | ? PK | | | يُمرَّر كـ string في `updateVerificationStatus` (`admin.api.ts:200-210`). |
| `user_id` | uuid | NOT NULL، UNIQUE (`onConflict: "user_id"`) | | FK → `users.id` (nested `users(email, phone)` في `admin.api.ts:152`). |
| `name` | text | | | الاسم القانوني أو اسم المكتب. |
| `account_type` | text | | `engineer`، `office`، `exhibition`، `material` (← `exhibition` في Laravel) | |
| `professional_id` | text | | رقم العضوية النقابية أو السجل التجاري | الأدمن يستدعي `.toLowerCase()` عليه، إذن text. `save*Profile` يكتب رقماً عشوائياً من 6 أرقام أو `123456`. |
| `tax_number` | text | nullable | | للمكتب. |
| `documents` | jsonb أو text[] | | مصفوفة URLs (Cloudinary `verifications` أو placeholder `https://placehold.co/600x400`) | |
| `details` | jsonb | nullable | مهندس: `{graduation_year, specialization}`. تعديل المكتب: `{tax_id}` | `graduation_year` أحياناً string وأحياناً int. |
| `status` | text | | `pending`، `active`، `reject` | الإحصاءات تعد الثلاث قيم (`admin.api.ts:184-195`). |
| `rejection_reason` | text | nullable | | `admin.api.ts:209`. |
| `created_at` | timestamptz | | | ترتيب. |

**Used by:** `updateUserValidation`/`getUserValidation` (`validation.api.ts`)، `save*Profile` (upsert بقيم placeholder، انظر 1.7.3)، admin (`getAdminOverviewStats`، `getPendingVerifications`، `updateVerificationStatus`، `updateSubscriptionStatus`).

---

#### 1.2.10 `plans`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `user_type` | text | | `engineer`، `office`، `exhibition`، `material` (← `exhibition` في Laravel) | |
| `plan_type` | text | | `basic`، `professional`، `premium` (وربما `free`) | يُطابَق مع `PRICING_DATA[].plans[].id`. الـ select يعمل alias `plan_id:plan_type` (`pricing.api.ts:69`). |
| `billing_cycle` | text | | `monthly`، `quarter`، `half`، `yearly` | |
| `price` | numeric | | | لو السعر = 0 تمشي الواجهة في مسار الباقة المجانية (`PricingCard.tsx:158-173`). |
| `original_price` | numeric | nullable | | السعر قبل الخصم (يُعرض مشطوباً). |
| (أعمدة أخرى) | ? | | | `getPlanFromDb` يعمل `select("*")`. |

UNIQUE(`user_type`, `plan_type`, `billing_cycle`) (inferred من `.maybeSingle()` في `pricing.api.ts:77-91`). الكود لا يكتب في هذا الجدول، فالبيانات seeded يدوياً.

**Used by:** `getPlansByConfig`، `getPlanFromDb` (`pricing.api.ts`)، `PricingCard.tsx`، `PaymentPage.tsx`، `DashboardPlans.tsx`.

---

#### 1.2.11 `subscriptions`

صف واحد لكل مستخدم: كل الـ upserts من الواجهة تستخدم `onConflict: "user_id"` (`pricing.api.ts:33-45`، `payment.api.ts:34-46`).

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | uuid (inferred) PK | | | يُستخدم كـ `event_id` لحدث Meta Purchase. |
| `user_id` | uuid | NOT NULL، UNIQUE | | FK → `users.id` (nested `users(...)` في `admin.api.ts:705`). |
| `plan_type` | text | | `free`، `basic`، `professional`، `premium` | العمود الذي تكتبه الواجهة. |
| `plan_id` | text | nullable | نفس القيم | ⚠ يكتبه فقط override الأدمن (`admin.api.ts:312-318`). يقرأه `dashboard.api.ts:12` والأدمن، بينما `plans.api.ts:12` يقرأ `plan_type` ويسميه `plan_id`. |
| `plan_slug` | text | ? | | يُقرأ فقط (`admin.api.ts:838`). |
| `plan_price` | numeric | ? | | يُقرأ فقط ولا تكتبه الواجهة أبداً، إذن غالباً يملؤه trigger من `plans`. |
| `amount` | numeric | ? | | يُقرأ كـ fallback فقط (`app/api/meta/purchase/route.ts:38`). |
| `billing_cycle` | text | | `monthly`، `quarter`، `half`، `yearly` | |
| `user_type` | text | | نوع المشترك | |
| `confirmation` | text | | URL إيصال (Cloudinary `payments`) أو النص الحرفي `"free_plan"` | |
| `status` | text أو enum | | `pending`، `active`، `expired` (والـ TS يذكر أيضاً `approved`، `rejected` في `src/types/subscription.ts:14`) | |
| `starts_at` | timestamptz | nullable | | يكتبه override الأدمن فقط. |
| `start_date` | ? | nullable | | يُقرأ كبديل لـ `starts_at` (`plans.api.ts:16`، `SubscriptionsTable.tsx:357-361`). |
| `expires_at` | timestamptz | nullable | | يكتبه override الأدمن فقط. |
| `end_date` | ? | nullable | | يُقرأ كبديل لـ `expires_at` (`admin.api.ts:761-765`). |
| `is_renewal` | boolean | ? | | يُقرأ فقط (`app/api/meta/purchase/route.ts:39`). |
| `created_at` | timestamptz | | | ترتيب. |
| `updated_at` | timestamptz | ? | | في TS فقط. |

⚠ الأعمدة مكررة المعنى: `plan_id`/`plan_type`، و`starts_at`/`start_date`، و`expires_at`/`end_date`، و`plan_price`/`amount`. الكود يقرأها بـ fallbacks، فوحّدها في Laravel.

⚠ `manuallyOverrideSubscription` يعمل `upsert` **بدون** `onConflict` (`admin.api.ts:312`)، فيتعارض على الـ PK فقط. لو المستخدم عنده صف بالفعل سيحاول insert جديد ويخالف UNIQUE(`user_id`).

**Used by:** `updateUserPlan`، `uploadPaymentConfirmation`، `getUserSubscription` (ثلاث نسخ: `payment.api.ts:77`، `dashboard.api.ts:3`، `plans.api.ts:3`)، admin (`manuallyOverrideSubscription`، `getAdminSubscriptions`، `updateSubscriptionStatus`، `getAdminFinanceStats`)، `app/api/meta/purchase/route.ts:27-31`.

---

#### 1.2.12 `subscription_payments`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `subscription_id` | uuid (inferred) | | | FK → `subscriptions.id` (inferred) |
| `user_id` | uuid | | | FK → `users.id` |
| `amount` | numeric | | | `parseFloat`. |
| `plan_type` | text | | | |
| `billing_cycle` | text | | | |
| `status` | text | | ? | المالية تجمع **كل** الصفوف بغض النظر عن الحالة (`admin.api.ts:946-947`). |
| `created_at` | timestamptz | | | يُبنى عليه التقسيم الشهري/السنوي. |

لا يكتب الكود في هذا الجدول أبداً، فغالباً يملؤه trigger في الـ DB عند تفعيل الاشتراك (غير موجود في الريبو). في Laravel: أنشئ صف payment عند موافقة الأدمن على الاشتراك.

**Used by:** `getAdminFinanceStats` فقط (`admin.api.ts:827-987`).

---

#### 1.2.13 `user_permissions`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int أو uuid (متعارض: `offices.api.ts:7` يقول number و`features/dashboard/types.ts:68` يقول string) | | | |
| `user_id` | uuid | nullable حسب TS (`features/dashboard/types.ts:69`) | | يُقرأ بـ `.eq("user_id").maybeSingle()`، إذن صف لكل مستخدم. احتمال وجود صفوف template بـ `user_id = null` لكل (`user_type`, `plan_type`). |
| `user_type` | text | | | |
| `plan_type` | text | | `free`، `basic`، `professional`، `premium` | الواجهة تستخدمه كمصدر أول لـ "الباقة الحالية". |
| `max_visits` | int | nullable | `-1` أو `>= 99999` = غير محدود، `null` = غير محدود | للعرض فقط (لا يُطبَّق). |
| `max_projects` | int | nullable | نفس القاعدة | يُطبَّق client-side على المشاريع **والمنتجات**. |
| `max_services` | int | nullable | نفس القاعدة | |
| `whatsapp_tracking`, `call_tracking`, `detailed_analytics`, `video_projects`, `google_maps_location`, `directory_ad_banner`, `distinguished_badge`, `homepage_ad_banner`, `social_media_promo`, `facebook_pixel` | boolean | | | feature flags (انظر 1.7.5). |
| `priority_search` | text | | الـ TS يقول `"Normal"/"Medium"/"High"`، والكود يقارن بـ `"normal"`/`"high"` بحروف صغيرة (`DashboardMarketing.tsx:424-430`) | ⚠ حالة الأحرف غير متسقة. |

الكود لا يكتب هنا أبداً، إذن تُملأ في الـ DB (trigger أو يدوياً). كذلك تُدمج كـ `permissions` داخل الـ views العامة.

**Used by:** `getUserPermissionsByUserId` (`dashboard.api.ts:53-62`، ويستدعيه hook `useUserPermissions` في Projects/Products/Services/Home/Marketing/Edit-profile/forms)، `getAdminUserDetails`.

---

#### 1.2.14 `customers` (طلبات خدمة العملاء)

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | uuid (inferred) PK | | | |
| `user_id` | uuid | nullable | | العميل صاحب الطلب (القيمة تأتي من الواجهة). |
| `full_name`, `phone`, `email` | text | nullable | | نسخة وقت الإنشاء. |
| `customer_type` | text | NOT NULL | `guest`، `need_service` | قوائم الأدمن تستبعد `guest` (`admin.api.ts:1081,1253`). |
| `governorate` | text | nullable | محافظة | `null` لـ guest. |
| `service` | text | nullable | من `ENGINEERING_SERVICES_15` (`features/customer/constants.ts:11`) | `null` لـ guest. |
| `notes` | text | nullable | | ⚠ يُستخدم أيضاً كـ **سجل append-only** بعلامات نصية: `[رفض سابق: <اسم> - السبب: <سبب>]` (`admin.api.ts:1933-1937`) و`[ملاحظة المكتب: ...]` (`office-requests.api.ts:181-191`)، ويُحلَّل بـ regex لاحقاً (`office-requests.api.ts:109`، `admin.api.ts:1120`). |
| `assigned_provider_id` | uuid | nullable | | `users.id` لمقدم الخدمة. الكود يتحقق `isUUID` قبل الكتابة "لتجنب invalid input syntax for type uuid" (`admin.api.ts:1942-1945`)، إذن العمود uuid. |
| `assigned_provider_name` | text | nullable | | الاسم وقت التعيين، ويُستخدم للمطابقة التقريبية. |
| `assigned_at` | timestamptz | nullable | | |
| `status` | text | | `pending`، `active` (guest)، `assigned`، `approved`، `in_progress`، `completed`، `cancelled`، `deleted` (soft-delete). وقيمة `refused` افتراضية تُصنع في الواجهة فقط (`office-requests.api.ts:122-128`) | `features/customer/types.ts:3-11`. |
| `customer_response` | text | nullable | `approved`، `refused` | |
| `customer_reason` | text | nullable | | سبب الرفض. |
| `whats_count`, `calls_count` | int (smallint حسب الـ doc) | nullable | | عدادات نقرات التواصل. |
| `created_at`, `updated_at` | timestamptz | | | `updated_at` تكتبه الواجهة. |

**Used by:** `features/customer/api/customer.api.ts` (create، list by user، respond، delete، trackCustomerContactClick)، `features/dashboard/office/api/office-requests.api.ts`، admin (`getAdminCustomerRequests`، `getAssignableProviders`، `assignProviderToCustomerRequest`، `updateCustomerRequestStatus`، `deleteCustomerRequest`)، `CustomerSetup.tsx:129-138`.

---

#### 1.2.15 `favorites`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int/bigint PK | | | |
| `user_id` | uuid | NOT NULL | | العميل. FK → `users.id` (nested `users(name,email,phone)` في `admin.api.ts:616`). |
| `name` | text | | | اسم الهدف (نسخة cached). |
| `image` | text | nullable | | صورة الهدف. |
| `type` | text | | `engineer`، `office`، `exhibition`، `material` (← `exhibition` في Laravel) | |
| `slug` | text | | slug الهدف | |
| `phone` | text | nullable | | |
| `created_at` | timestamptz | | | |

UNIQUE(`user_id`, `slug`) (inferred: `isFavorited` يعمل `.maybeSingle()` في `favorites.api.ts:28-41`، والـ toggle يحذف أو يضيف).

**Used by:** `features/dashboard/customer/api/favorites.api.ts`، `home.api.ts` (activities وstats)، `getAdminUserDetails`.

---

#### 1.2.16 `rating`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int/bigint PK | | | |
| `slug` | text | | slug الهدف، أو `"t3meer"` لتقييمات المنصة | `TestimonialsSection.tsx:158-166`، `homepage-data.server.ts:22`. |
| `type` | text | | في تقييم الملف: نوع **الهدف** (`engineer`/`office`/`exhibition`). في تقييم المنصة: `user_type` **الكاتب** | ⚠ دلالة مزدوجة. |
| `user_id` | uuid | | | الكاتب. FK → `users.id`. |
| `name` | text | | | اسم الكاتب كما يكتبه في نافذة التقييم. |
| `rating` | int | | 1–5 | |
| `message` | text | | | |
| `image` | text | nullable | | صورة الكاتب (من metadata) في تقييم المنصة فقط. |
| `created_at` | timestamptz | | | |

قاعدة "تقييم واحد لكل (`user_id`, `slug`)" مطبقة client-side فقط (`reviews.api.ts:29-35`). في Laravel اجعلها UNIQUE constraint.

**Used by:** `features/dashboard/customer/api/reviews.api.ts`، `home.api.ts:138-143,270-273,368-377`، `customer.api.ts:76-78`، `admin.api.ts:1325-1327`.

---

#### 1.2.17 `stats`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int/bigint PK (`IStatWithUser.id: number`، `home.api.ts:17`) | | | |
| `slug` | text | | slug الملف المستهدف | |
| `metric_type` | enum `metric_type` (حسب التعليق في `lib/track-metric.ts:3`) | | `visit`، `whatsapp`، `call`، `email`، `map`، `facebook`، `instagram`، `youtube`. والأدمن يقبل أيضاً `phone` كمرادف لـ `call` (`admin.api.ts:655`) | |
| `count` | int | | `>= 1` | عداد تراكمي. القراءة تعامل `count <= 0` أو null على أنه 1. |
| `date` | date | | `YYYY-MM-DD` | **آخر** يوم تم فيه زيادة العداد. |
| `user_id` | uuid | nullable | | ⚠ **الزائر** وليس صاحب الملف، وهو آخر زائر فقط لأن الـ update يكتب فوقه (`track-metric.ts:57`). FK → `users.id` (nested `users(...)` في `admin.api.ts:604`). |
| `type` | text | nullable | نوع الهدف: `engineer`، `office`، `exhibition`، `material` (← `exhibition` في Laravel)، أو `user_type` مالك المشروع | |
| `created_at` | timestamptz | | | ⚠ يُستبدل بالوقت الحالي في كل زيادة (`track-metric.ts:47`). |

**Used by:** `trackMetricClick` (`lib/track-metric.ts:14-96`)، `home.api.ts` (activities، global stats)، `marketing.api.ts:74-77,119-122`، admin (`getAdminOverviewStats`، `getAdminUserDetails`). والقراءة المجمّعة عبر RPC `get_limited_stats` و`get_all_stats`.

---

#### 1.2.18 `analytics_counts`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `stat_type` | text | | `page`، `service`، `governorate`، `sort` | |
| `stat_key` | text (inferred من بارامتر الـ RPC `p_stat_key`) | | للـ `page`: `home` أو نوع الدليل (`engineers`، `offices`، …). للباقي: القيمة المختارة في الفلتر | |
| `total_count` | int | | | |

الكتابة تتم فقط عبر RPC `increment_analytics`.

**Used by:** `getAdminOverviewStats` (`admin.api.ts:56-65`)، `getGlobalStats` (`home.api.ts:415-422`).

---

#### 1.2.19 `marketing`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `user_id` | uuid | UNIQUE (`onConflict`) | | FK → `users.id` |
| `banner` | jsonb | nullable | `{title, description, image}` | الصورة من Cloudinary `banners`. |
| `meta_pixel` | text | | Pixel ID الخاص بالمشترك | |
| `social` | jsonb | | `{facebook, instagram, youtube, meta_id, caption, image}` | الصورة من Cloudinary `social_posts`. |
| `user_type` | text | | من metadata | |
| `slug` | text | | من metadata | |

**Used by:** `updateMarketingMetadata`/`getMarketingData` (`features/dashboard/marketing/api/marketing.api.ts:10-45`)، وبشكل غير مباشر view `marketing_active_users`.

---

#### 1.2.20 `materials_prices`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `id` | int (inferred: `deleteMaterialPrice(id: number or string)`) PK | | | الصف الجديد يُرسل بدون `id` (`PriceEditor.tsx:48-49`). |
| `category` | text | | مثل `حديد` | ترتيب في الأدمن. |
| `item_name` | text | | | |
| `unit` | text | | مثل `طن` | |
| `price` | numeric | | | |
| `change_percent` | numeric | default 0 | | يُعرض بإشارة +/−. |

**Used by:** `getLatestPrices` (`features/prices/api/prices.api.ts:19-37`)، و admin `getAdminMaterialPrices`/`upsertMaterialPrice`/`deleteMaterialPrice` (`admin.api.ts:332-358`).

---

#### 1.2.21 `interaction_logs`

| Column | Inferred type | Nullable/Default | Allowed values / enum | Notes |
|---|---|---|---|---|
| `user_id` | uuid | | | FK → `users.id` |
| `activity_type` | text | | `update` (القيمة الوحيدة المكتوبة) | |
| `activity_text` | text | | نص عربي | |

يُكتب فقط عند قبول أو رفض التوثيق (`admin.api.ts:221-228`) ولا يُقرأ في أي مكان. الـ "Recent activities" في الداشبورد تُبنى من `favorites`/`rating`/`stats`.

---

### 1.3 الـ Views

الـ views نفسها غير موجودة في الريبو. المكتوب هنا هو ما يقرؤه الكود منها. في Laravel يُعاد بناؤها كـ query scopes و API Resources تعمل joins وتجميعات على الجداول الأصلية.

| View | الأعمدة التي يقرؤها الكود | الفلاتر/الترتيب المطبقة | ماذا تمثل (inferred) |
|---|---|---|---|
| `public_offices_view` | `office_name`، `name`، `slug`، `phone`، `email`، `whats_app`، `website`، `governorate`، `experience_years`، `profile_image`، `average_rating`، `reviews_count`، `total_visits`، `views_count`، `selected_plan`، `services` (JSON array من كائنات فيها `name`)، `projects` (array)، `permissions` (object)، `count_of_projects`، `latitude`، `longitude`، `bio`، `video`، `facebook`، `instagram`، `youtube` | `.eq("slug")`، `.contains("services", '[{"name":…}]')`، `.ilike("governorate")`، `.ilike("office_name")`، `.or(slug.eq,name.eq,office_name.eq)`، order حسب `experience_years`/`total_visits`/`average_rating` ثم `selected_plan` desc، order `name`، و`count` بـ head | `offices` + `users` (phone/email/selected_plan) + تجميع `services`/`projects`/`user_permissions` + `avg`/`count` من `rating` بالـ slug + مجموع الزيارات من `stats`. **مقتصر على الحسابات النشطة**: الأدمن يسميها "Active Counts (Public Views)" ويقارنها بالإجمالي من `users` (`admin.api.ts:36-42`). |
| `public_engineers_view` | `name`، `user_name`، `slug`، `phone`، `email`، `major`، `whats_app`، `governorate`، `experience_years`، `profile_image`، `average_rating`، `reviews_count`، `total_visits`، `selected_plan`، `services`، `projects`، `permissions`، `skills`، `bio`، `facebook`، `instagram`، `youtube`، `latitude`، `longitude` | `.eq("slug")`، `.contains("services")`، `.ilike("governorate")`، `.or(name.ilike,major.ilike)`، `.or(slug.eq,name.eq,user_name.eq)`، `select("slug")` لقائمة الأدمن "public only"، ونفس الترتيب | نفس فكرة المكاتب للمهندسين. `user_name` = غالباً `users.name`. |
| `public_exhibitions_view` | `select("*")`، ومنه يُقرأ: `exhibition_name`، `name`، `slug`، `main_category`، `governorate`، `city`، `phone`، `whats_app`، `email`، `website`، `logo`، `profile_image`، `banner`، `coverImage`، `average_rating`، `reviews_count`، `total_visits`، `views_count`، `experience_years`، `selected_plan`، `latitude`، `longitude`، `services`، `products` (array فيها `id`، `name`/`title`، `price`/`cost`، `category`/`type`، `sub_category`، `image`/`image_url`)، `brands`، `video`، `open_hours`، `bio`، `count_of_projects`، `projects`، `permissions`، `status`، `facebook`، `instagram`، `youtube` | `.eq("governorate")`، `.or(exhibition_name.ilike,name.ilike)`، `.eq("slug")`، ونفس الترتيب. فلتر التصنيف يتم **client-side** على `main_category` (`exhibitions.api.ts:163-169`) | المعارض مع المنتجات والتقييمات والزيارات. تخدم دليل `showrooms-suppliers` وأيضاً مسارات `/directory/exhibitions` و`/directory/materials` (`app/directory/[type]/page.tsx:36-39`)، وصفحة تفاصيل الأنواع الثلاثة (`app/directory/[type]/[id]/page.tsx:34-47`). |
| `public_materials_view` ⛔ (مش هتتعمل في Laravel، 0.5) | `material_name`، `slug`، `phone`، `governorate`، `experience_years`، `profile_image`، `average_rating`، `total_visits`، `selected_plan`، `services`، `latitude`، `longitude`، `projects`، `permissions` | `.contains("services")`، `.eq("governorate")`، `.ilike("material_name")`، `.eq("slug")` | legacy. الاستخدام الفعلي الوحيد هو عدّاد الأدمن (`admin.api.ts:47-49`). `getPublicMaterials`/`getMaterialDetailsBySlug` لها hooks (`materials.query.ts`) لكن لا تستخدمها أي صفحة. |
| `premium_active_view` | صفوف بشكل `projects`: `id`، `user_id`، `name`، `location`، `area`، `type`، `image`، `slug`، `user_type`، `user_name`، `details`، `created_at` | `.order("created_at", desc).limit(n)` | مشاريع أصحاب الباقات المميزة النشطة (inferred من الاسم والـ doc). الرئيسية تأخذ آخر 100، ثم مشروعاً واحداً لكل مستخدم، بحد أقصى 15 (`lib/homepage-data.server.ts:37-47`). |
| `marketing_active_users` | `banner`، `slug`، `user_type`، `plan_type` | `.eq("user_type")`، `.eq("plan_type")`، `.not("banner","is",null)` | صفوف `marketing` لأصحاب الاشتراكات النشطة مع `plan_type` (inferred). |

**ملاحظات لإعادة البناء في Laravel:**
- `average_rating` = متوسط `rating.rating` حيث `rating.slug = profile.slug`. و`reviews_count` = عددها.
- `total_visits`/`views_count` = مجموع `stats.count` حيث `metric_type='visit'` (inferred).
- `services` = مصفوفة `{name, icon, color}` من جدول `services` للمالك. و`projects`/`products` = مصفوفات من جداولها. و`permissions` = صف `user_permissions`.

---

### 1.4 دوال RPC والـ Triggers والـ Enums

| الاسم | البارامترات | الشكل الراجع كما تستهلكه الواجهة | ماذا تفعل | المستدعون |
|---|---|---|---|---|
| `check_user_exists` | `p_email text` | object: `{exists: boolean, message?: string, user_id?: uuid}` | تتحقق من وجود حساب بالبريد (التعريف غير موجود في الريبو). تُستدعى **بدون تسجيل دخول**. | `signup.api.ts:24-34` (يستخدم `exists` و`message`)، `verify-email.api.ts:30-40`، `admin.api.ts:1652,1909` (يستخدم `user_id`) |
| `increment_analytics` | `p_stat_type text`، `p_stat_key text` | لا شيء (error فقط) | تزيد `analytics_counts.total_count` للزوج (`stat_type`, `stat_key`)، atomic (inferred) | `lib/track-metric.ts:98-110`، `app/api/analytics/track/route.ts:13-16` |
| `get_limited_stats` | `p_slug text` | array، والعنصر `[0]` = `{visit, whatsapp, call, email, map, facebook, instagram, youtube}` أرقام | عدادات الفترة الحالية لكل metric. الأدمن يعامل `.visit` على أنه "زيارات الشهر الحالي" (`admin.api.ts:631-632`). تعريف الفترة غير معروف. | `home.api.ts:306-309`، `admin.api.ts:626` |
| `get_all_stats` | `p_slug text` | array من `{metric_type, created_at}` | كل صفوف `stats` للـ slug، ويُستخدم لرسم بياني 12 شهراً | `home.api.ts:311-314` (**غير موجودة في القائمة المبدئية**) |
| `delete_user_account` | بدون | لا شيء | تحذف حساب المستخدم الحالي (`auth.uid()` inferred)، ثم الواجهة تعمل `signOut`. ما يُحذف بالـ cascade غير معروف. | `dashboard.api.ts:65-71` |
| `update_unconfirmed_user_email` | `p_old_email text`، `p_new_email text`، `p_new_slug text DEFAULT NULL` | `jsonb`: `{success, error?, user_id?, new_email?, new_slug?}` | من الـ SQL (`supabase_update_email_rpc.sql:7-141`)، بالترتيب: (1) trim/lower للبريدين ورفض الفارغ أو المتطابق. (2) رفض لو البريد الجديد موجود في `auth.users`. (3) البحث عن حساب بالبريد القديم و`email_confirmed_at IS NULL`، ولو الحساب مؤكَّد يرجع خطأ "سجّل دخول لتغيير البريد". (4) slug = المُمرَّر أو مشتق من البريد الجديد مع ضمان التفرد. (5) تحديث `public.users.email/slug` و`auth.users.email` و`confirmation_sent_at=NULL` و`raw_user_meta_data.email/slug`. `SECURITY DEFINER`، و**GRANT لـ `anon` و`authenticated` و`service_role`** (sql:144-146). | `app/api/auth/update-email/route.ts:74-79,111-118` |

**Trigger: `on_auth_user_created`** (`AFTER INSERT ON auth.users`، ينفذ `public.handle_new_user()`، `SECURITY DEFINER`، `supabase_signup_trigger.sql:18-113`):
1. `name` = أول قيمة موجودة من `raw_user_meta_data->>'name'`، `'fullName'`، `'full_name'`، وإلا `'مستخدم'`.
2. `user_type` = cast آمن لو القيمة ضمن (`customer`, `engineer`, `office`, `supplier`, `exhibition`)، وإلا `'engineer'`.
3. `status` = لو ضمن (`pending`, `active`, `rejected`, `suspended`)، وإلا `'pending'`.
4. `selected_plan` = لو ضمن (`free`, `basic`, `pro`, `premium`)، وإلا `'free'`. ⚠ لو جاءت `professional` تصبح `free`.
5. `slug` = من الـ metadata (lower/trim). لو فارغ: الجزء قبل `@` بعد استبدال `[^a-zA-Z0-9]+` بـ `-` وحذف `-` من الطرفين، أو `'user'`، ثم إضافة `-1`، `-2`… حتى يصبح فريداً.
6. `phone` من الـ metadata، و`current_step` = `COALESCE(NULLIF(meta.current_step,'')::int, 1)`.
7. `INSERT ... ON CONFLICT (id) DO UPDATE` (يحدّث email/name/slug/phone/user_type/status/selected_plan).
8. `EXCEPTION WHEN OTHERS → RAISE WARNING; RETURN NEW`. ⚠ لو فشل الـ insert يُنشأ مستخدم auth **بدون** صف في `public.users` بصمت.
- السكربت يحتوي أيضاً backfill لكل `auth.users` الموجودين (sql:120-161).
- السكربت **يحذف** triggers قديمة: `trigger_sync_user_slug` و`user_slug_update` و`trigger_update_office_name` على `public.users`، والدوال `sync_user_slug()` و`update_office_name()` (sql:7-12). هذه كانت تزامن `slug`/`name` من `users` إلى `offices`/`engineers`، وهذا يفسر عدم وضوح طريقة ملء `slug` في جداول الملفات حالياً.

**Enums** (ملفات الـ SQL لا تحتوي `CREATE TYPE`. القيم مأخوذة من الـ whitelists في الـ trigger):

| Enum | القيم في الـ SQL | ما يستخدمه الكود فعلاً |
|---|---|---|
| `account_type` | `customer`، `engineer`، `office`، `supplier`، `exhibition` | + `material`، `admin`. و`supplier` غير مستخدم في الكود. **في Laravel:** `customer, engineer, office, exhibition, admin` (0.5) |
| `status` | `pending`، `active`، `rejected`، `suspended` | `pending`، `active`، `reject` (يكتب)، `rejected` (يقرأ). و`suspended` غير مستخدم |
| `plan_type` | `free`، `basic`، `pro`، `premium` | `free`، `basic`، **`professional`**، `premium` (يقرأ `pro` كمرادف) |
| `metric_type` | غير موجود في الـ SQL. مذكور في تعليق `lib/track-metric.ts:3` وفي `full_project_analysis.md` §8.2 | `visit`، `whatsapp`، `call`، `email`، `map`، `facebook`، `instagram`، `youtube` |

---

### 1.5 نموذج المصادقة (Auth Model)

#### 1.5.1 العميل والجلسة
- `supabase-js` بخيارَي `persistSession: true` و`autoRefreshToken: true` (`lib/supabase.ts:6-11`)، فالجلسة محفوظة في localStorage.
- الواجهة **تكتب أيضاً يدوياً** cookies باسم `sb-access-token` و`sb-refresh-token` (مدة 7 أيام، `SameSite=Lax`، ليست HttpOnly) في ثلاثة أماكن: `login.api.ts:15-21`، `login.query.ts:26-38` (عند كل `onAuthStateChange`)، و`signup.query.ts:36-42`.
- Zustand يحفظ **كائن `User` الكامل** (بما فيه `user_metadata`) في cookie باسم `user-storage` لمدة 7 أيام (`store/useUserStore.ts:13-43`). و`clearUser` يمسح الـ cookies عند الخروج.
- `AuthInitializer` يستدعي `supabase.auth.getUser()` عند التحميل ويزامن الـ store (`components/providers/AuthInitializer.tsx`، `login.query.ts:8-46`).

#### 1.5.2 التسجيل (`signUpUser`، `features/auth/signup/api/signup.api.ts:6-74`)
1. استدعاء `check_user_exists(p_email)`. لو `exists` يرجع `{alreadyExists, message}` وتحوّل الواجهة إلى `/login`.
2. `slug = generateUniqueSlugFromEmail(email)` (انظر 1.7.8).
3. `supabase.auth.signUp({email, password, options: {emailRedirectTo, data}})`:
   - `emailRedirectTo` = `{origin}/customer/setup` للعميل، أو `{origin}/pricing/{type}` لغيره.
   - `data` (user_metadata) = `{name, phone, user_type, slug, current_step: 1, status: "pending", selected_plan: "free"}` (`RegisterForm.tsx:122-130`).
4. لو رجع session (تأكيد البريد معطّل) تذهب الواجهة مباشرة إلى `/customer/setup` أو `/pricing/{type}`. وإلا تحفظ `pending_verification_email` في localStorage وتحوّل إلى `/verify-email?email=&accountType=` (`signup.query.ts:31-60`).
5. أخطاء "already registered" تُعامل كحساب موجود، وأي خطأ يحتوي "phone" يُترجم إلى "رقم الهاتف مسجل لمستخدم آخر".

#### 1.5.3 البريد، الدخول، كلمة المرور، الحذف
- **صفحة تأكيد البريد:** تعمل polling على `getUser()` كل 10 ثوانٍ وعند focus، حتى يظهر `email_confirmed_at` (`VerifyEmail.tsx:125-150`). وإعادة الإرسال تتم بـ `auth.resend({type:"signup", email, options:{emailRedirectTo}})` (`verify-email.api.ts:5-28`)، ويستطيع الأدمن عمل نفس الشيء (`admin.api.ts:233-249`).
- **تغيير البريد قبل التأكيد** (`updateUserEmail`، `verify-email.api.ts:42-133`): لو هناك session والبريد مطابق يعمل `auth.updateUser({email, data:{slug, email}})` ثم `users.update({email, name, slug})`. وإلا يعمل `POST /api/auth/update-email` (انظر 1.6).
- **الدخول:** `signInWithPassword` ثم redirect حسب `?redirect=` أو `getStepRoute(user_type, current_step)` (`LoginForm.tsx:48-56`). خطأ "email not confirmed" يعرض الانتقال لصفحة التفعيل.
- **Session من الـ URL hash:** صفحة `EngineerValidation` تقرأ `access_token`/`refresh_token` من `location.hash` وتستدعي `setSession` (`EngineerValidation.tsx:52-63`).
- **نسيت كلمة المرور:** `resetPasswordForEmail(email, {redirectTo: {origin}/reset-password})`. صفحة الـ reset تنتظر حدث `PASSWORD_RECOVERY` أو session ثم تعمل `auth.updateUser({password})` بحد أدنى 6 أحرف (`forgot-password.api.ts`، `ResetPasswordForm.tsx:31-60`).
- **تغيير كلمة المرور من الداشبورد:** `auth.updateUser({password})` (`features/dashboard/profile/api/profile.api.ts:197-208`).
- **حذف الحساب:** `rpc("delete_user_account")` ثم `signOut` ثم `clearUser` (`dashboard.api.ts:65-71`، `dashboard.query.ts`).
- **الخروج:** `signOut` ثم مسح الـ cookies (`dashboard.query.ts:42-58`).

#### 1.5.4 مفاتيح `user_metadata` المكتوبة عبر `auth.updateUser({data})` أو `signUp`

| المفتاح | أين يُكتب | هل يُكتب أيضاً في `users`؟ |
|---|---|---|
| `name` | `signUp` (`signup.api.ts:47`)، `updateUserBasicInfo` (`features/dashboard/profile/api/profile.api.ts:20-28`) | نعم |
| `phone` | `signUp`، `updateUserBasicInfo` | نعم |
| `user_type` | `signUp` فقط | نعم (عبر الـ trigger) |
| `slug` | `signUp`، `updateUserEmail` (`verify-email.api.ts:71-79`)، والـ RPC يعدّل `raw_user_meta_data.slug` | نعم |
| `email` | `updateUserEmail`، والـ RPC | نعم |
| `current_step` | `signUp`، `updateUserStep` (`features/auth/profile/api/profile.api.ts:135-137`)، `updateUserValidation` (`validation.api.ts:33-36`)، `updateUserPlan` (`pricing.api.ts:48-51,58-60`)، `uploadPaymentConfirmation` (`payment.api.ts:54-67`)، `save*Profile` (`features/auth/profile/api/profile.api.ts:103,228,313,398`) | نعم |
| `status` | `signUp`، `updateUserValidation`، `save*Profile` | نعم |
| `selected_plan` | `signUp` (`"free"`)، `updateUserPlan`، `uploadPaymentConfirmation` | نعم |
| `selected_billing_cycle` | `updateUserPlan` (`pricing.api.ts:23`)، `uploadPaymentConfirmation` | **لا، metadata فقط** |
| `selected_user_type` | `updateUserPlan` (`pricing.api.ts:24`) | **لا، metadata فقط** |
| `profile_image`، `image` | `updateProfileImage` (`home.api.ts:83-88`) | يُكتب في جدول الملف (`profile_image`)، وليس `users` |

**مفاتيح تُقرأ ولا تُكتب أبداً** (أي تُضبط يدوياً): `account_type` و`type` (يقرؤهما فحص الأدمن في `app/dashboard/admin/layout.tsx:40-43`)، `full_name`، `fullName`، `avatar_url`، `user_name`، `office_name`.

⚠ في `CustomerSetup` القيمتان `status: "active"` و`user_type` تُحدَّثان في جدول `users` وفي الـ Zustand store فقط، وليس في الـ metadata (`CustomerSetup.tsx:142-171`).

#### 1.5.5 الأدوار
- الدور = `user_type`. القيمة `admin` لا يُنشئها أي كود (تُضبط يدوياً).
- فحص الأدمن: `app/dashboard/admin/layout.tsx:12-47` (server component) يقرأ cookie `user-storage` ويقارن `user_metadata.account_type || type || user_type` بـ `"admin"`. لا يوجد أي فحص آخر، وكل عمليات الأدمن تُنفذ من المتصفح بالـ anon key.

#### 1.5.6 `current_step` ومسار الـ Onboarding

`getStepRoute(userType, step)` (`lib/step-redirect.ts:12-33`، والتعليق في رأس الملف قديم ولا يطابق الكود):

| النوع | الخطوة | المسار |
|---|---|---|
| `customer` | `<= 1` | `/customer/setup` |
| `customer` | `>= 2` | `/dashboard/customer` |
| `admin` | أي خطوة | `/dashboard/admin` |
| مهني (engineer/office/exhibition/material) | 0، 1، 2 | `/pricing/{type}` |
| مهني | 3 | `/complete-profile/{type}` |
| مهني | `>= 4` | `/dashboard/{type}` |

**معنى الخطوات ومن يكتبها:**

| Step | المعنى | يكتبها |
|---|---|---|
| 0 | غير مستخدمة فعلياً (لا شيء يكتبها). الـ middleware يحوّل إلى `/verify-email` لو `current_step === 0` | — |
| 1 | تم التسجيل | `signUp` |
| 2 | مهني: يتصفح الأسعار. عميل: أنهى الإعداد | `PricingLayout.tsx:40-62` (لو `users.current_step < 2`)، `updateUserValidation` (لو `< 2`)، `CustomerSetup` (`updateUserStep(2)` و`users.current_step=2, status='active'`) |
| 3 | اختار باقة بتأكيد (`free_plan` أو إيصال) | `updateUserPlan` (فقط لو `confirmation` غير فارغ و`step < 3`)، `uploadPaymentConfirmation` |
| 4 | أكمل الملف المهني | `save*Profile` (لو `< 4`)، ثم `updateUserStep(4)` في `profile.query.ts` |

**الحراسة:**
- `middleware.ts:8-128` يقرأ `user_type`/`current_step` من cookie `user-storage`. لو غير موجود يفك الـ JWT من `sb-access-token` أو `sb-*-auth-token` **بدون تحقق من التوقيع** (`middleware.ts:44-78`). القواعد:
  - غير مسجل ويطلب `/dashboard`، `/complete-profile`، `/payment`، `/customer/*` (عدا `/customer/setup`): يحوَّل إلى `/login?redirect=`.
  - `current_step === 0` (لغير الأدمن) ويطلب `/complete-profile` أو `/dashboard`: يحوَّل إلى `/verify-email`.
  - `/complete-profile` مع `step >= 4`: يحوَّل إلى `/dashboard/{type}`.
  - `/login` و`/register` لمن أنهى التسجيل (أدمن، أو عميل `step >= 2`، أو مهني `step >= 4`): يحوَّل إلى الداشبورد.
- `app/dashboard/layout.tsx:22-50`: المهني بـ `step < 4` يُحوَّل لـ `getStepRoute`.
- صفحة `/pricing` تقبل فقط `step` 1 أو 2، وغير ذلك تعمل redirect (`PricingLayout.tsx:28-38`). صفحة `EngineerValidation` تعمل redirect لو `step > 1`.
- ⚠ صفحتا `/validation/engineer` و`/validation/office` **لم يعد أي مسار يوجّه لهما** (step 1 يذهب للأسعار)، لكنهما ما زالتا موجودتين.
- ⚠ يوجد `uncommitted_changes.patch` يغيّر هذا المسار (إلغاء الأسعار والتوثيق: `step < 4` يذهب إلى `/complete-profile`، وإنشاء اشتراك `free_plan` نشط، وجعل `status='active'` عند إكمال الملف). هذا **غير مطبق** في الكود الحالي، وعلى الفريق تأكيد المسار المقصود.

> ⬅ **خطوة التوثيق:** المسار المعتمد للـ Laravel بيرجّع خطوة `/validation/{type}` عند step 1 للمهندس والمكتب. التفاصيل في **2.V**.

#### 1.5.7 ما يحتاجه Laravel
- جدول `users`: `id` (uuid، للحفاظ على الروابط والـ FKs)، `email` (unique)، `password` (bcrypt؛ ترحيل كلمات المرور من `auth.users.encrypted_password` ممكن لأنها bcrypt)، `email_verified_at`، `name`، `phone` (unique؟)، `user_type` (+`admin`)، `slug` (unique)، `current_step`، `status`، `selected_plan`، `selected_billing_cycle`، `selected_user_type`، `profile_image`، `rejection_reason`، timestamps.
- Token auth (Sanctum personal tokens أو SPA cookie HttpOnly) بدل cookies الـ JS. Middleware يتحقق من التوكن فعلياً.
- Endpoint `GET /me` يرجع ما كانت الواجهة تقرؤه من `user_metadata`: `user_type`، `current_step`، `slug`، `name`، `phone`، `selected_plan`، `selected_billing_cycle`، `status`، `profile_image`، `email_confirmed_at`.
- Endpoints لـ: register (مع منطق `check_user_exists` + توليد slug)، login، logout، resend verification، verify (redirect حسب النوع)، forgot/reset password، change password، change unconfirmed email، delete account.
- انتقالات `current_step` و`status` يجب أن تحدث **server-side** كأثر جانبي لعمليات محددة (اختيار باقة، رفع إيصال، إكمال ملف، موافقة أدمن)، وليس endpoint عام يقبل أي قيمة.
- Policies/Gates لكل دور، وrole `admin` مخزّن في DB وغير قابل للتعديل من المستخدم.

---

### 1.6 الـ Next.js Route Handlers (يجب أن تصبح endpoints في Laravel)

| Method + Path | Request | المنطق | Response |
|---|---|---|---|
| `GET /api/admin/homepage-display` | — | يقرأ `data/homepage-display-settings.json` مع دمج الـ defaults (`lib/homepage-display-settings.server.ts:10-24`) | `HomepageDisplaySettings` JSON، أو 500 `{error}` |
| `POST /api/admin/homepage-display` | body: `{offices:{premium,professional,basic,free: bool}, engineers:{...}}` | يرفض بـ 400 لو `offices` أو `engineers` غير موجودين. يكتب الملف على الـ filesystem ثم `revalidatePath("/")` و`revalidatePath("/api/homepage")`. **لا يوجد أي فحص صلاحية.** | `{success: true, settings}`، أو 400/500 |
| `POST /api/analytics/track` | body: `{type, name}` | 400 لو أحدهما ناقص. يستدعي `increment_analytics(p_stat_type=type, p_stat_key=name)`. **لا تستدعيه الواجهة حالياً** (الواجهة تستدعي الـ RPC مباشرة من `lib/track-metric.ts:98`). | `{success: true}`، أو 400/500 `{error}` |
| `GET /api/homepage` | — | `getCachedHomepageData()` (cache 300s): المكاتب والمهندسين المفلترين والمرتبين حسب إعدادات العرض، المشاريع (واحد لكل مستخدم، 15 كحد أقصى)، `stats`، `prices`، تقييمات `slug="t3meer"` (`lib/homepage-data.server.ts:14-63`) | `{offices[], engineers[], projects[], stats:{engineers,offices,visits}, prices[], reviews[]}` + header `Cache-Control: public, s-maxage=300, stale-while-revalidate=60`. شكل عنصر المكتب: `{id: slug, name, category, region, phone, image, rating, countOfProjects, viewsCount, experienceYears, isPremium, selected_plan, lat, lng}` (`offices.api.ts:138-153`)، والمهندس: `{id, name, specialization, region, phone, email, image, rating, viewsCount, experienceYears, isPremium, selected_plan, lat, lng}` (`engineers.api.ts:144-159`)، والسعر: `{category, name, unit, price, change}`، والتقييم: `IReview` |
| `GET /api/public/stats` | — | `getGlobalStats()` مع cache 300s (`home.api.ts:399-429`) | `{engineers, offices, visits}` |
| `POST /api/meta/event` | body: `{eventName, params, eventId, userId, email, phone, testEventCode}` | يستخرج IP من `x-forwarded-for`/`x-real-ip`، والـ UA، والـ referer، ثم `MetaService.sendGenericMetaEvent` (`src/services/meta.service.ts:116-176`). بدون مصادقة. | رد Meta Graph كما هو، أو 500 |
| `POST /api/meta/purchase` | body: `{subscriptionId, userId, email, phone, price, currency="EGP", isRenewal, fbp, fbc, testEventCode}` | لو `subscriptionId` موجود وبيانات ناقصة: يجلب `subscriptions` + `users(id,email,phone)` ليملأ `userId`/`email`/`phone`، و`price = plan_price أو amount`، و`isRenewal = is_renewal`. لو البريد ناقص يجلب `users`. يرجع 400 لو `subId`/`uId`/`uEmail` ناقص. يقرأ `_fbp`/`_fbc` من الـ cookies. ثم `MetaService.sendPurchaseEvent` (`app/api/meta/purchase/route.ts:5-125`). **لا يستدعيه أي كود حالياً**: الاستدعاء في الأدمن معلّق (`admin.api.ts:812-821`). | رد Meta. والـ 400 يرجع `params:{subId,uId,uEmail}`، أي يكشف البريد |
| `POST /api/auth/update-email` | body: `{oldEmail, newEmail, newSlug?, accountType?}` | تحقق regex للبريدين وأنهما مختلفان (400). `emailRedirectTo` = `{origin}/dashboard/customer` للعميل (⚠ يختلف عن التسجيل الذي يستخدم `/customer/setup`)، أو `{origin}/pricing/{type}`. **Strategy 1:** لو `SUPABASE_SERVICE_ROLE_KEY` موجود: admin client، توليد slug لو غير مُمرَّر، ثم RPC. **Strategy 2:** نفس الـ RPC بالـ anon client. لو `rpcData.success=false` يرجع 400. ثم `auth.resend({type:"signup", email:new})` (`route.ts:5-197`) | `{success:true, newEmail, newSlug, emailSent, emailSendError, message}`، أو 400/500 `{error}` |

---

### 1.7 التكاملات الخارجية

#### 1.7.1 Cloudinary
- `uploadToCloudinary(file, folder="t3meer")` (`apis/global.api.ts:16-59`): رفع **unsigned** مباشرة من المتصفح إلى `https://api.cloudinary.com/v1_1/{cloud}/{image|raw}/upload`. الفيديو يُرفع كـ `raw`. يرجع `secure_url`، والـ DB يخزن الـ URL فقط.
- اسم الـ preset مكتوب hard-coded في السطر 23 ويتجاهل الـ env var. وكلا المتغيرين لهما قيمة fallback مكتوبة في الكود.
- حدود client-side: فيديو 100MB (`lib/helper-functions.ts:278`)، إيصال الدفع 5MB (`PaymentPage.tsx:117`).
- env names: `NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME`، `NEXT_PUBLIC_CLOUDINARY_UPLOAD_PRESET`.

| Folder | الملف الذي يرفع | العمود الذي يُخزن فيه الـ URL |
|---|---|---|
| `profiles` | `components/layouts/Navbar.tsx:114`، `features/dashboard/components/DashboardSidebar.tsx:84` (عبر `updateProfileImage`) | `{engineers,offices,exhibitions,materials}.profile_image` + metadata `profile_image`/`image` |
| `profiles` | `features/admin/components/ProfessionalDetails.tsx:127` | `engineers.profile_image` أو `offices.profile_image` |
| `engineers/profiles` | `EngineerProfileForm.tsx:124` | `engineers.profile_image` |
| `offices/profiles` | `OfficeProfileForm.tsx:118` | `offices.profile_image` |
| `exhibitions/profiles` | `ExhibitionProfileForm.tsx:144` | `exhibitions.logo` |
| `materials/profiles` | `MaterialProfileForm.tsx:143` | `materials.profile_image` |
| `exhibitions/logos` | `EditExhibitionProfile.tsx:178` | `exhibitions.logo` |
| `exhibitions/banners` | `EditExhibitionProfile.tsx:188` | `exhibitions.banner` |
| `videos` | `lib/helper-functions.ts:285` (`handleGlobalVideoUpload`) من `EditOfficeProfile.tsx:151` و`EditExhibitionProfile.tsx:154` | `offices.video`، `exhibitions.video` |
| `projects` | `DashboardProjects.tsx:214,221,230` | `projects.image`، `projects.details.photos[]`، `projects.details.video` |
| `products` | `DashboardProducts.tsx:186,193` | `products.image`، `products.details.photos[]` |
| `verifications` | `EngineerValidation.tsx:96`، `OfficeValidation.tsx:107-108`، `EditEngineerProfile.tsx:220`، `EditOfficeProfile.tsx:244,253` | `user_validation.documents[]` (للمكتب: index 0 = السجل التجاري، index 1 = البطاقة الضريبية) |
| `payments` | `payment.api.ts:19`، `DashboardPlans.tsx:283` | `subscriptions.confirmation` |
| `banners` | `DashboardMarketing.tsx:95` | `marketing.banner.image` |
| `social_posts` | `DashboardMarketing.tsx:102` | `marketing.social.image` |

**توصية Laravel:** استخدم signed uploads (يولد الـ backend الـ signature) أو ارفع عبر الـ backend، وتحقق أن الـ URL المُرسل تابع لحساب Cloudinary الخاص بالمنصة.

#### 1.7.2 Meta Pixel و Conversions API
- **Pixel (browser):** `components/analytics/MetaPixel.tsx` و`src/lib/pixel.ts`. كل `trackMetaEvent`/`trackMetaCustomEvent` يستدعي `fbq` ويرسل أيضاً `POST /api/meta/event` بـ `{eventName, params, eventId}` فقط، أي بدون بيانات المستخدم، فيصل الـ CAPI بـ `em`/`external_id` فارغين.
- **الأحداث المستخدمة:** `CompleteRegistration` (`signup.query.ts:22`)، `ProfileCompleted` custom (`profile.query.ts:23,45,66,87`)، `AddPaymentInfo` و`Subscribe` (`PaymentPage.tsx:150-160`).
- **CAPI:** `POST https://graph.facebook.com/v23.0/{pixelId}/events?access_token=…` (`src/lib/meta.ts`). `user_data` فيه SHA-256 لـ email (trim+lower)، والهاتف (أرقام فقط)، و`external_id` (userId)، بينما `fbp`/`fbc` بدون hash (`src/lib/hash.ts`). `event_id` لحدث Purchase = `subscriptionId`، أو `renewal_{subscriptionId}` للتجديد. `custom_data` = `{value, currency="EGP"}`.
- env: `NEXT_PUBLIC_META_PIXEL_ID`، `META_ACCESS_TOKEN`، `META_TEST_EVENT_CODE`.

#### 1.7.3 تتبع التحليلات
- **لكل ملف** (`trackMetricClick(slug, metricType, type?, userId?)`، `lib/track-metric.ts:14-96`): يستدعيه الـ hook `useTrackMetric(slug)` في صفحات التفاصيل (`visit` عند الفتح، وwhatsapp/call/… عند النقر)، و`ProjectDetailsView.tsx:103,127`، و`HeroSlider.tsx:39` (نقرة البانر تُحسب `visit` لصاحبه). التفاصيل في 1.8.6.
- **على مستوى المنصة** (`trackAnalytics(type,key)` → RPC `increment_analytics`): `("page","home")` من `app/page.tsx:75`، `("page", <نوع الدليل>)` من `app/directory/[type]/page.tsx:106`، و`("service"|"governorate"|"sort", القيمة)` من `DirectoryListing.tsx:432-446`. يوجد dedupe يومي لكل جهاز بمفتاح localStorage `track_analytics_{type}_{name}_{YYYY-MM-DD}` (`TrackPageVisit.tsx`).

#### 1.7.4 إعدادات عرض الصفحة الرئيسية
- الملف `data/homepage-display-settings.json` بالشكل `{offices:{premium,professional,basic,free}, engineers:{...}}`. الـ defaults: كله `true` عدا `free: false` (`lib/homepage-display-settings.ts:13-26`).
- `normalizePlanTier`: `pro` و`professional` يعطيان `professional`، والقيمة null أو غير المعروفة تعطي `free` (`:30-37`).
- **توصية Laravel:** خزّنه في جدول `settings` (key/value JSON) بدل الـ filesystem، لأن الـ FS على serverless لا يحتفظ بالكتابة. واحمِ الـ endpoint بصلاحية admin.

#### 1.7.5 بقية متغيرات البيئة (الأسماء فقط)
`NEXT_PUBLIC_SUPABASE_URL`، `NEXT_PUBLIC_SUPABASE_ANON_KEY`، `SUPABASE_SERVICE_ROLE_KEY` (في route تغيير البريد فقط)، `NEXT_PUBLIC_APP_URL`، `NEXT_PUBLIC_INSTAPAY_LINK` و`NEXT_PUBLIC_WALLET_LINK` (روابط الدفع اليدوي في `PaymentPage.tsx:256,309` و`DashboardPlans.tsx:481,529`)، `NODE_ENV`.

#### 1.7.6 الـ Caching الحالي (لتكراره في Laravel)
`unstable_cache` / ISR: بيانات الرئيسية 300s، الإحصاءات العامة 300s، قوائم الأدلة 120s، بانرات الرئيسية 60s، بانرات الأدلة 120s، وكل صفحات الدليل `revalidate = 120`.

---

### 1.8 قواعد العمل الموجودة في طبقة البيانات

#### 1.8.1 دورة حياة `users.status`

| الحدث | القيمة | المصدر |
|---|---|---|
| التسجيل | `pending` | metadata ثم الـ trigger |
| إرسال التوثيق (مهندس) | `active` ⚠ **تفعيل ذاتي فوري** | `EngineerValidation.tsx:109` → `validation.api.ts:21-31` |
| إرسال التوثيق (مكتب) أو تعديل التوثيق من الداشبورد | `pending` | `OfficeValidation.tsx:119`، `EditEngineerProfile.tsx:239`، `EditOfficeProfile.tsx:278` |
| إكمال الملف (كل الأنواع) | `pending` | `features/auth/profile/api/profile.api.ts:97-102` وما يماثلها |
| إعداد حساب العميل | `active` | `CustomerSetup.tsx:150-153` |
| موافقة أو رفض الأدمن على التوثيق | `active` أو `reject` | `admin.api.ts:214-217` |
| تفعيل الأدمن للاشتراك | `active` | `admin.api.ts:800-805` |
| تعديل الأدمن للملف | أي قيمة من القائمة (`active`/`pending`/`reject`) | `ProfessionalDetails.tsx:141` |

الحسابات غير النشطة لا تظهر في الـ views العامة (inferred). في Laravel يجب أن تكون الانتقالات server-side فقط.

#### 1.8.2 `user_validation.status`
- `pending` عند الإرسال أو التعديل، و`active`/`reject` من الأدمن (`admin.api.ts:207-210`). تفعيل الاشتراك يجعلها `active` أيضاً (`admin.api.ts:806-809`).
- ⚠ **`save*Profile` يعمل upsert على `user_validation` بقيم placeholder** (`professional_id` عشوائي أو `123456`، و`documents` = صور `placehold.co`، و`details` ثابتة، و`status: "pending"`) مع `onConflict: "user_id"` (`features/auth/profile/api/profile.api.ts:105-119,230-241,315-326,400-411`). هذا **يمسح** المستندات الحقيقية لو كان المستخدم رفعها قبلها. في Laravel: لا تنشئ placeholder، وأنشئ صف validation فقط عند رفع مستندات فعلية.

- ⬅ الجدول المقترح `user_validations` والحالات (`pending/approved/rejected`) وتأثيرها على `users.status` موجودين في **2.V.3–2.V.4**.

#### 1.8.3 الباقات والأسعار
- الأسعار من `plans` حسب (`user_type`, `plan_type`, `billing_cycle`). الأسماء والمزايا ثابتة في الواجهة (`features/auth/pricing/api/pricing-static.ts`): ثلاث باقات `basic`/`professional`/`premium` لكل نوع.
- **لو السعر غير موجود في DB** وكان للباقة سعر ثابت: سنوي = `price*12*0.8`، نصف سنوي = `price*6*0.9`، ربع سنوي = `price*3` (`PricingCard.tsx:33-55`). ملاحظة: الـ static الحالي لا يحتوي أسعاراً، فيُعرض `"..."`.
- دورة السداد الافتراضية: المهندس `quarter` (ربع/نصف/سنوي)، وغيره `monthly` (`PricingLayout.tsx:67-71`، `payment.api.ts:23-33`). ⚠ `DashboardPlans.tsx:87` يعتبر المكتب أيضاً quarter-based، وهذا لا يطابق صفحة الأسعار.
- **مسار الباقة المجانية** (السعر = 0): `updateUserPlan({confirmation:"free_plan"})`، ثم upsert `subscriptions` بحالة `pending` + `current_step = 3`، ثم `/complete-profile`.
- **مسار الباقة المدفوعة:** `/payment/{type}?plan=&billing=`، ثم رفع الإيصال إلى Cloudinary `payments`، ثم upsert `subscriptions{plan_type, billing_cycle, user_type, confirmation: url, status: "pending"}` + `users.selected_plan` + `current_step = 3` (`payment.api.ts:4-75`). الدفع **يدوي** (InstaPay أو محفظة)، والأدمن يراجع.
- **الترقية والتجديد من الداشبورد:** نفس `updateUserPlan` بإيصال جديد، وتصبح الحالة `pending` مرة أخرى (`DashboardPlans.tsx:283-305`). ⚠ يكتب فوق الاشتراك النشط الحالي (صف واحد لكل مستخدم).

#### 1.8.4 الاشتراكات وانتهاؤها
- **موافقة الأدمن** (`updateSubscriptionStatus`، `admin.api.ts:780-825`): تغيير `status` (`active`/`pending`/`expired`). عند `active`: `users.status='active'` و`user_validation.status='active'`. الواجهة **لا تضبط** `starts_at`/`expires_at`/`plan_price` ولا تنشئ `subscription_payments`، فغالباً هذا يتم بـ trigger في الـ DB. Laravel يجب أن يحسب: `starts_at = now`، و`expires_at = now + مدة الدورة` (شهر/3/6/12)، و`plan_price` من `plans`، وصف في `subscription_payments`.
- **Override يدوي** (`admin.api.ts:303-330`): `{user_id, plan_id, status:"active", starts_at: now, expires_at: now + days}` (افتراضياً 365 يوم، `ManualSubscription.tsx:11`) + `users.selected_plan = planId`.
- **الانتهاء:** يُعتبر منتهياً لو `status === 'expired'` أو (`end_date`، وإلا `expires_at`) أقل من الآن (`admin.api.ts:759-765`). **لا يوجد أي job** يحوّل الحالة إلى `expired` أو يرجع المستخدم لباقة أقل. Laravel يحتاج scheduled job.
- الإحصاءات في قائمة الأدمن (pending/active/expired وتوزيع الباقات) تُحسب client-side على **كل** الصفوف.

#### 1.8.5 الحدود والصلاحيات (`user_permissions`)
- **قاعدة "غير محدود":** `null`/`undefined`، أو `-1`، أو `>= 99999` (`DashboardProjects.tsx:49-57` وكل الأماكن المشابهة).
- `max_projects`: يمنع إضافة مشروع (`DashboardProjects.tsx:57,259`) **و** منتج (`DashboardProducts.tsx:50-59,221`). client-side فقط.
- `max_services`: يمنع إضافة خدمة (`DashboardServices.tsx:126-138`). وفي فورمات إكمال الملف، لو `max_services` = null تُستخدم fallbacks ثابتة:

| فورم | free | basic | professional | premium |
|---|---|---|---|---|
| مهندس (`EngineerProfileForm.tsx:61-71`) | ∞ | 1 | 5 | ∞ |
| مكتب (`OfficeProfileForm.tsx:59-69`) | ∞ | 3 | 7 | ∞ |
| معرض/مورد (`ExhibitionProfileForm.tsx:67-72`، `MaterialProfileForm.tsx:66-71`) | ∞ | 3 | 7 | ∞ |
| تعديل المورد (`EditMaterialProfile.tsx:53-63`) | ∞ | 1 | 3 | ∞ |

  المزايا المعلنة في `pricing-static.ts` للمهندس: basic = 3 مشاريع وخدمة واحدة و150 زائر/شهر، professional = 7 مشاريع و5 خدمات و300 زائر، premium = غير محدود.
- `max_visits`: **للعرض فقط**. يظهر شريط "وصلت للحد" في الداشبورد (`DashboardHome.tsx:385-399`) ومقارنة "زيارات الشهر" في الأدمن (`ProfessionalDetails.tsx:72`). لا يوجد أي إخفاء للملف عند تجاوز الحد.
- Flags تتحكم في الواجهة:
  - `whatsapp_tracking`، `call_tracking`، `detailed_analytics`، `google_maps_location`: إظهار الإحصاءات (`DashboardHome.tsx:593-606`، `ClicksChartJS.tsx:38-53`، `DashboardLeads.tsx:18`).
  - `google_maps_location === false`: يخفي خريطة الملف العام (`OfficeDetailsView.tsx:272-279,473`).
  - `video_projects === false`: يخفي فيديو المكتب العام (`OfficeDetailsView.tsx:231`). و`video_projects` = true يظهر رفع فيديو المشروع (`DashboardProjects.tsx:660`).
  - `directory_ad_banner`، `homepage_ad_banner` (غير متاح للمهندس)، `social_media_promo`، `facebook_pixel`، `distinguished_badge`، `priority_search`: تتحكم في قسم التسويق (`DashboardMarketing.tsx:163-186,424-430,518,548,628`).
- **مصدر "الباقة الحالية" في الواجهة:** `user_permissions.plan_type`، وإلا `subscriptions.plan_id`، وإلا `user_metadata.selected_plan`، وإلا `"basic"`.
- **في Laravel:** عرّف الحدود في جدول `plan_features` (لكل `user_type` × `plan_type`) وطبّقها **server-side** في endpoints الإضافة.

#### 1.8.6 العدادات (views/clicks)
- **`trackMetricClick`** (`lib/track-metric.ts:14-96`):
  1. لو `visit`: dedupe يومي لكل جهاز بمفتاح localStorage `track_visit_{slug}_{YYYY-MM-DD}`.
  2. يجلب آخر صف `stats` لـ (`slug`, `metric_type`). لو موجود: `count = count + 1` (أو 2 لو `count <= 0`)، ويكتب فوق `date=today` و`created_at=now`، و`type`/`user_id` لو مُمرَّرين.
  3. لو غير موجود (أو فشل الـ update): insert `{metric_type, count:1, date, slug, user_id?, type?}`.
  - ⚠ read-modify-write بدون atomic increment، أي race condition. في Laravel: `INSERT ... ON CONFLICT (slug, metric_type) DO UPDATE SET count = count + 1`، أو جدول events مع تجميع.
  - ⚠ بما أن الصف واحد لكل (`slug`, `metric_type`) يتحدث `created_at` الخاص به باستمرار، فإن الرسم البياني الشهري الذي **يعدّ الصفوف** من `get_all_stats` (`home.api.ts:340-357`) يظهر غالباً 0 أو 1 لكل شهر، ولا يعكس العدد الحقيقي.
- **`trackCustomerContactClick`** (`customer.api.ts:300-339`): read-modify-write على `customers.whats_count`/`calls_count`. يستدعيه العميل والمكتب والأدمن.
- **`analytics_counts`:** atomic (inferred) عبر RPC.

#### 1.8.7 التجميعات المحسوبة client-side (يجب أن يحسبها الـ API)
- **إحصاءات داشبورد المهني** (`getDashboardStats`، `home.api.ts:252-398`): من `get_limited_stats` تأتي العدادات الثمانية. الرسم البياني = عدد صفوف `get_all_stats` بحالة `metric_type='visit'` لكل شهر (مع تجاهل السنة). `favorites_count` = عدد `favorites` بالـ slug. `average_rating` = متوسط `rating.rating` بالـ slug مقرباً لرقم عشري واحد. `reviews_count`. `projects` = `profile.count_of_projects`. `specializations_count` = طول `profile.specializations || profile.services`، وهو غالباً 0 لأن الخدمات في جدول منفصل.
- **داشبورد العميل:** `fav_engineers`/`fav_offices` = عدد `favorites` حسب `type`، و`my_ratings` = عدد تقييماته، بالإضافة لآخر 10 أنشطة مدمجة من `favorites`/`rating`/`stats` (`home.api.ts:106-250`).
- **الإحصاءات العامة:** `engineers`/`offices` = count من الـ views. `visits` = **عدد صفوف** `stats` بـ `metric_type='visit'` (وليس مجموع `count`) + مجموع `analytics_counts.total_count` حيث `stat_type='page'` (`home.api.ts:399-429`). نفس المعادلة في الأدمن (`admin.api.ts:52-65`).
- **ملخص الأدمن** (`admin.api.ts:25-139`): الإجماليات من `users` حسب النوع. الحسابات "النشطة" = count من الـ views. التوثيقات المعلقة. وتوزيع الـ onboarding: `current_step` = 1 أو 2 أو 3، و`>= 4` مع `status='active'`، والمرفوضون (`reject`/`rejected`)، وكلها تستبعد `customer`/`admin`.
- **قوائم المهندسين والمكاتب للأدمن:** أعلى 3 محافظات (بعد تقسيم `governorate` بالفاصلة) وعدد كل باقة (`pro`/`professional` = professional، والقيم غير المعروفة = free) (`admin.api.ts:416-447`).
- **تفاصيل المهني للأدمن** (`admin.api.ts:567-692`): يجمع `stats.count` لكل `metric_type` حيث `slug = profile.slug` **أو** `user_id = id`. ⚠ `stats.user_id` هو الزائر، فهذا الفلتر يخلط نشاط المهني كزائر مع زيارات ملفه. `totalClicks` = whatsapp + phone + fb + ig + yt + email + map.
- **المالية** (`admin.api.ts:827-987`): من `subscription_payments` (كل الحالات): `totalMonthly`/`totalYearly` للسنة الحالية، و`chartData` لـ 12 شهراً (مجمّعة عبر كل السنوات)، و`totalsPerPlan`. التصنيف "جديد" مقابل "تجديد" = أول دفعة لكل `user_id` بالترتيب الزمني جديدة والباقي تجديد. `activeTotalRevenue` = مجموع `plan_price` للاشتراكات `active`.
- **إثراء طلبات العملاء** (`customer.api.ts:40-181`، `admin.api.ts:1302-1463`): مطابقة مقدم الخدمة من الـ views بالـ slug أو الاسم، بمطابقة **تقريبية** (`includes` لو طول الاسم > 3). التقييم = `average_rating` من الـ view، وإلا متوسط `rating` بالـ slug. عدد المشاريع = `count_of_projects`، وإلا طول `projects`. ثم إحصاءات المكتب (assigned/approved/refused) والعميل محسوبة من **كل** صفوف `customers` + regex على `notes`، والباقة من lookup على `users` (حد 1000).
- **مقدمو الخدمة القابلون للتعيين** (`admin.api.ts:1501-1835`): دمج الـ views + resolving الـ UUID (من `users`/`offices`/`engineers`، وإلا `check_user_exists`) + الترتيب بوزن الباقة premium 4 / professional 3 / basic 2 / غير ذلك 1.

#### 1.8.8 توليد الـ slug
- **في الواجهة** (`lib/helper-functions.ts:220-245`): الجزء قبل `@`، ثم `slugify(..., {lower:true, strict:true})` أو `"user"`. لو `users.slug` موجود يضيف `-1`، `-2`… حتى يصبح فريداً. يُستدعى قبل التسجيل (بدون session، إذن `users` مقروءة لـ anon) وعند تغيير البريد.
- **في الـ SQL** (الـ trigger والـ RPC): `[^a-zA-Z0-9]+` يتحول إلى `-`، مع trim للـ `-` من الطرفين، و`'user'` لو فارغ، ثم حلقة الـ suffix.
- ⚠ الـ slug **منسوخ (denormalized)** في: `engineers`/`offices`/`exhibitions`، `services.slug`، `projects.slug`، `products.slug`، `marketing.slug`، `favorites.slug`، `rating.slug`، `stats.slug`. تغيير البريد (والـ slug معه) يحدّث `users` و`auth.users` فقط، فتنكسر الروابط. في Laravel: اربط بـ `user_id`/`profile_id` واجعل الـ slug للعرض فقط، أو حدّث كل النسخ في transaction.

#### 1.8.9 دورة حياة طلب العميل (`customers`)
1. **الإنشاء** (`createCustomerRequest`، `customer.api.ts:8-34`): `guest` يعطي `status='active'` بدون محافظة أو خدمة. `need_service` يعطي `status='pending'`.
2. **تعيين الأدمن** (`admin.api.ts:1842-1967`): resolve الـ UUID، ثم لو الرد السابق `refused` يضيف `[رفض سابق: اسم - السبب: …]` إلى `notes`، ثم `{assigned_provider_id (uuid أو null), assigned_provider_name, assigned_at: now, customer_response: null, customer_reason: null, status: 'pending', updated_at}`.
3. **رد العميل** (`customer.api.ts:187-218`): `approved` يعطي `status='approved'`. `refused` يعطي `status='pending'` مع الإبقاء على المزوِّد حتى يعيد الأدمن التعيين.
4. **تحديث المكتب** (`office-requests.api.ts:170-205`): أي `CustomerStatus` (مثل `in_progress`/`completed`) مع إضافة `[ملاحظة المكتب: …]`.
5. **تحديث الأدمن** (`admin.api.ts:1972-1995`): `pending`/`assigned`/`approved`/`completed`/`cancelled` (`AdminCustomerRequestsTable.tsx:1032-1036`) مع استبدال `notes` اختيارياً.
6. **الحذف:** العميل يجرّب hard delete بـ (`id`, `user_id`)، ثم soft delete `status='deleted'`، ثم soft delete بالـ `id` وحده، ثم hard delete بالـ `id` وحده (`customer.api.ts:224-293`). الأدمن يعمل hard delete مباشرة.
7. **رؤية المكتب** (`office-requests.api.ts:36-164`): يجلب **كل** الطلبات (غير المحذوفة أو الملغاة)، ثم يطابق `assigned_provider_id` مع (`userId`/`slug`) أو الاسم بمطابقة تقريبية، بالإضافة للرفض السابق المستخرج من `notes` ويعرضه كـ `refused`. الإحصاءات: active = غير المرفوض.
8. **قوائم العميل والمكتب** تستبعد `deleted` و`cancelled`. **قوائم الأدمن** تستبعد `customer_type='guest'`. فلاتر الأدمن: `unassigned` = `assigned_provider_name IS NULL`. `assigned`/`pending_decision`/`approved` = معيَّن والرد ليس `refused`. `refused` و`completed` و`cancelled` كما هي (`admin.api.ts:1256-1274`).

#### 1.8.10 التقييمات والمفضلة
- تقييم واحد لكل (`user_id`, `slug`). `upsertReview` يحدّث `rating`/`message`/`name`/`type` لو موجود (`reviews.api.ts:55-81`). تقييمات المنصة على `slug="t3meer"`، وتُعرض في الرئيسية مدمجة مع شهادات ثابتة.
- المفضلة toggle على (`user_id`, `slug`) مع نسخة cached من `name`/`image`/`phone`/`type`.

#### 1.8.11 البانرات التسويقية
- **بانرات الدليل** (`marketing.api.ts:47-94`): من `marketing_active_users` حيث `user_type` = النوع بصيغة المفرد، و`plan_type` = **`premium` للمهندسين و`professional` لغيرهم** (السطر 53)، ويجب أن يكون للبانر `image`.
- **بانرات الرئيسية** (`:96-139`): `plan_type='premium'` و`user_type='office'` فقط.
- الترتيب **تصاعدي** حسب مجموع `stats.count` للـ slug، أي الأقل نقرات أولاً (تدوير عادل). الرابط: `/directory/{type}s/{slug}` (والمعرض يذهب لـ `showrooms-suppliers`). لو لا يوجد أي بانر تُستخدم إعلانات ثابتة (`Static/homePage.ts`).

#### 1.8.12 ترتيب وأوزان الباقات
- الأوزان: premium 30، professional/pro 20، basic 10، free 0 (`lib/homepage-display-settings.ts:44-56`، `admin.api.ts:284-291`).
- الأدلة العامة ترتب بـ `.order("selected_plan", desc)` في الـ DB. لو العمود enum بترتيب الـ SQL (free, basic, pro, premium) يكون الناتج premium ثم pro ثم basic ثم free. لكن لو كانت القيمة `professional` نصاً فالترتيب الأبجدي مختلف. **في Laravel: رتّب بالوزن صراحةً.**
- الرئيسية تفلتر حسب إعدادات العرض (`free` مخفي افتراضياً)، ثم ترتب بالوزن (`lib/homepage-data.server.ts:28-35`).

---

### 1.9 مشاكل أمنية يجب أن يصلحها الـ Rebuild

1. **كل عمليات الأدمن تُنفذ من المتصفح بالـ anon key** (`features/admin/api/admin.api.ts` كله: تعديل أي مستخدم، تفعيل اشتراكات، حذف طلبات، تعديل أسعار). الحماية الوحيدة الممكنة هي RLS، وهي غير موجودة في الريبو ولا يمكن التحقق منها.
2. **فحص الأدمن client-side فقط وقابل للتزوير:** `app/dashboard/admin/layout.tsx:12-47` يثق في cookie `user-storage`، وهو JSON يكتبه JS، غير موقّع وليس HttpOnly. وأي مستخدم يستطيع أيضاً كتابة `user_type: "admin"` في `user_metadata` الخاصة به عبر `supabase.auth.updateUser`. و`middleware.ts:44-78` يفك الـ JWT **بدون التحقق من التوقيع**.
3. **المستخدم يتحكم في حالته وخطوته وباقته:** الواجهة تكتب `users.status`/`current_step`/`selected_plan` مباشرة. `EngineerValidation` يرسل `status:"active"` ليفعّل نفسه (`EngineerValidation.tsx:109`).
4. **الثقة في `user_id` المُرسل من الواجهة:** `createCustomerRequest` (`user_id`)، `addFavorite` (`user_id`)، `addReview` (`author_id`)، `addProject`/`addProduct`/`addUserService` (`user_id`)، `updateUserValidation` (`data.user_id`)، `trackMetricClick` (`userId`). والتعديل والحذف بالـ `id` فقط **بدون التحقق من الملكية**: `updateProject`/`deleteProject`، `updateProduct`/`deleteProduct`، `updateCustomerResponse`، وfallbacks الحذف في `customer.api.ts:259-281`، و`updateOfficeRequestStatus`.
5. **RPC `update_unconfirmed_user_email` ممنوحة لـ `anon`** (`supabase_update_email_rpc.sql:144`)، و`POST /api/auth/update-email` بدون مصادقة ويستخدم الـ service-role key لو موجود. أي شخص يعرف بريد حساب غير مؤكد يستطيع تغييره إلى بريده (account takeover/squatting).
6. **`check_user_exists`** متاحة بدون تسجيل وترجع `user_id`، ما يسمح بـ user enumeration.
7. **`POST /api/admin/homepage-display` بدون أي مصادقة.**
8. **`/api/meta/event` و`/api/meta/purchase` مفتوحان:** يمكن إرسال أحداث مزيفة بتوكن الخادم، ويُقبل `testEventCode` من الـ body، و`purchase` يرجع البريد في رد الـ 400 (`route.ts:67-75`).
9. **حدود الباقات client-side فقط** (المشاريع والمنتجات والخدمات)، ويمكن تجاوزها باستدعاء مباشر.
10. **تسريب بيانات شخصية:** `getOfficeCustomerRequests` يجلب **كل** طلبات العملاء (الاسم والهاتف والبريد) للمتصفح ثم يفلتر (`office-requests.api.ts:57-62`). الأدمن يجلب حتى 1000 مستخدم بالبريد (`admin.api.ts:1328-1331,1522-1525`). `users` مقروءة لـ anon (فحص الـ slug قبل التسجيل).
11. **Filter injection في PostgREST:** نصوص البحث تُدمج خاماً في `.or(...)` (`admin.api.ts:275,379,1289,1862`، `engineers.api.ts:113`، `exhibitions.api.ts:112`). في Laravel استخدم parameter binding.
12. **التوكنات في cookies غير HttpOnly** يكتبها JS (`login.api.ts:19-20`)، وكائن المستخدم كاملاً في cookie.
13. **العدادات قابلة للتلاعب وفيها race conditions** (`stats`، `whats_count`/`calls_count`)، والـ dedupe عبر localStorage فقط.
14. **Cloudinary unsigned preset:** أي شخص يستطيع الرفع، والواجهة ترسل أي URL للحفظ بدون تحقق، وإيصالات الدفع ومستندات التوثيق على روابط عامة.
15. **مسح مستندات التوثيق الحقيقية** بقيم placeholder عند إكمال الملف (1.8.2)، وهي مشكلة سلامة بيانات.
16. الـ trigger يبتلع الأخطاء (`EXCEPTION WHEN OTHERS`)، فقد يوجد مستخدم auth بدون صف `users`.

---

### 1.10 ما هو موثَّق وغير مستخدم، والتعارضات، وما لم يمكن تحديده

#### 1.10.1 Documented-but-unused / possibly stale (الكود لا يلمسها أبداً)
- `t3meer_full_analysis.md` §5.16 و§5.19–5.21 و§8: الجداول `customer_project_requests` و`project_proposals`، والـ view `public_project_requests_view`، والـ enums `project_status`/`proposal_status`، والعمود `user_permissions.monthly_proposals_quota`، وصفحة `/projects-market`. هذه **ميزات مخططة**.
- `database_schema.md`: الجداول `profiles_engineer`/`profiles_office`/`profiles_exhibition`/`profiles_material`، `analytics_daily`، `reviews`، `profile_verifications`. أعمدة `users`: `full_name`، `password_hash`، `account_type`، `is_verified`، `updated_at`. `products.owner_id`/`image_url`/`description`/`in_stock`. `projects.owner_id`/`description`/`image_url`. `interaction_logs.id`/`actor_id`/`created_at` (لا تُكتب ولا تُقرأ). `reviews.target_user_id`/`author_name`/`comment`.
- `full_project_analysis.md` §8.1: الجداول `reviews` و`analytics` و`banners` (الكود يستخدم `rating` و`analytics_counts` و`marketing`).
- `t3meer_full_analysis.md` §5.3: أعمدة في `offices` مثل `phone`، `email`، `city`، `coverImage`، `average_rating`، `reviews_count`، `views_count`. الكود لا يكتبها في `offices`، وتأتي من الـ view فقط. ونفس الشيء لـ `engineers.workingHours`/`coverImage`/`videoUrl`/`website` (only-if-provided ولا يرسلها أي فورم حالي).

#### 1.10.2 تعارضات بين الكود والـ docs أو داخل الكود
- **`materials`:** ✅ **اتحسم: يتدمج في `exhibitions` (0.5).** الـ doc يقول "تم إزالته ومدمج" (§5.5، §5.21)، لكن الكود ما زال يكتب في `materials` ويقرأ `public_materials_view` (عدّاد الأدمن). نوع `material` لم يعد في شاشة التسجيل، لكن مساراته موجودة.
- **`user_validation`:** الـ doc يقول "تم تخطيه وإلغاؤه" (§5.15)، لكن الكود يستخدمه بنشاط (صفحات التوثيق، تعديله من الداشبورد، شاشة توثيقات الأدمن، وupsert تلقائي عند إكمال الملف).
- **مسار الـ onboarding:** الـ doc (§3) يقول: step 2 = اختيار الباقة، و3 = الدفع، و4 = إكمال الملف، و5+ = الداشبورد. الكود: 1–2 = الأسعار، و3 = إكمال الملف، و`>= 4` = الداشبورد. وتعليق `lib/step-redirect.ts:1-11` قديم أيضاً. وملف `uncommitted_changes.patch` يقترح مساراً ثالثاً.
- **`plan_type`:** في الـ SQL والـ doc = `pro`، والكود يكتب `professional`.
- **`status`:** في الـ SQL = `rejected`، والأدمن يكتب `reject`.
- **`account_type`:** في الـ SQL: `supplier`، وبدون `material`/`admin`. الكود عكس ذلك.
- **`subscriptions`:** الـ doc يذكر `plan_type` + `starts_at`/`expires_at`. الكود يقرأ أيضاً `plan_id`، `plan_slug`، `start_date`، `end_date`، `amount`، `is_renewal`.
- **`user_permissions.priority_search`:** في TS `"Normal"/"High"`، والمقارنة بأحرف صغيرة.
- **ثلاث نسخ من `getUserSubscription`** بأشكال مختلفة للرد: `payment.api.ts:77` يرجع الصف خام، و`dashboard.api.ts:3` يقرأ `plan_id`، و`plans.api.ts:3` يقرأ `plan_type` ويسميه `plan_id`.

#### 1.10.3 ما لم يمكن تحديده من الكود
- سياسات RLS وتعريفات الـ enums الفعلية (المعروف هو الـ whitelists فقط).
- أنواع الـ PK لمعظم الجداول (المذكور inferred من TS).
- تعريفات الـ 6 views، وتعريف الفترة في `get_limited_stats`، ومنطق `check_user_exists` و`increment_analytics` و`delete_user_account` (ماذا يُحذف بالـ cascade).
- **من يملأ:** `slug` في جداول الملفات، صفوف `user_permissions`، صفوف `subscription_payments`، و`subscriptions.plan_price`/`starts_at`/`expires_at` عند موافقة الأدمن. لا يوجد أي كود يكتبها، فغالباً triggers غير موجودة في الريبو، ويجب على الفريق تصدير الـ schema الحقيقي (`pg_dump --schema-only`) قبل الترحيل.
- هل أعمدة `materials` التي يرسلها `EditMaterialProfile` موجودة (وإلا هذا الـ flow معطّل).
- هل `users.phone` و`favorites(user_id,slug)` و`rating(user_id,slug)` عليها UNIQUE constraints فعلية.

---

## 2. المصادقة والـ Onboarding (Auth + Onboarding)

> **النطاق:** كل شاشات التسجيل والدخول واستعادة كلمة المرور وتفعيل البريد والتوثيق واختيار الباقة والدفع وإكمال الملف (4 أنواع) وإعداد حساب العميل.
> **المصدر:** الكود عند commit `4ef3be0` (2026-10-27). كل الأرقام بصيغة `path:line`.
> **الوضع الحالي:** الواجهة بتكلم Supabase مباشرةً من المتصفح (`lib/supabase.ts`، anon key + RLS). مفيش backend وسيط غير Route واحد: `app/api/auth/update-email/route.ts`. الـ RPCs والـ triggers المذكورة تحت جزء من الـ business logic اللي لازم تتنقل لـ Laravel.

---

### 2.0 قواعد مشتركة لكل شاشات القسم

#### 2.0.1 كائن المستخدم (User) اللي الواجهة بتعتمد عليه

الواجهة بتقرا الـ Supabase `User` object (من `auth.getUser()` / `signInWithPassword` / `signUp`) وبتخزّنه في Zustand store `user-storage` (cookie، `store/useUserStore.ts:25-44`). الـ middleware بيقرا نفس الـ cookie عشان يعرف `user_type` و`current_step` (`middleware.ts:22-42`).

الحقول المقروءة فعلياً في نطاق القسم:

| الحقل في Supabase | يُقرأ في | ملاحظة |
|---|---|---|
| `id` | كل الشاشات | UUID |
| `email` | VerifyEmail، CustomerSetup | |
| `email_confirmed_at` | RegisterForm:77، signup.query:32، VerifyEmail:95,131 | Laravel: `email_verified_at` |
| `phone` | CustomerSetup:126 (fallback) | |
| `user_metadata.name` (fallbacks: `fullName`, `full_name`) | Profile forms، CustomerSetup:53-58,120-125، verify-email.api:86-89 | |
| `user_metadata.phone` | CustomerSetup:126 | |
| `user_metadata.user_type` | Login، Register، Pricing، Payment، Validation، VerifyEmail، middleware | القيمة الافتراضية لو مش موجود: `"engineer"` |
| `user_metadata.slug` | Profile forms (تُرسل كـ `slug` لصفوف `services`) | |
| `user_metadata.current_step` | كل الـ guards | الافتراضي `1` (`\|\| 1`) |
| `user_metadata.status` | (يُكتب فقط في هذا القسم) | |
| `user_metadata.selected_plan` | Profile forms (حدود الخدمات)، Payment | |
| `user_metadata.selected_billing_cycle` | Payment | |
| `user_metadata.selected_user_type` | payment.api:22-25 | |

**مقترح Laravel — `UserResource`** (يُرجع من `register` و`login` و`GET /auth/me` وكل endpoint بيغيّر الـ onboarding state):

```json
{
  "id": "9b1c…uuid",
  "email": "eng.ahmed@example.com",
  "email_verified_at": "2026-11-03T10:00:00Z",
  "name": "أحمد محمد",
  "phone": "01012345678",
  "user_type": "engineer",
  "slug": "eng-ahmed",
  "current_step": 2,
  "status": "pending",
  "selected_plan": "free",
  "selected_billing_cycle": null,
  "selected_user_type": null
}
```

> الواجهة حالياً بتقرا الحقول دي من داخل `user_metadata.*`. فريق الـ front هيحتاج إما يعدّل القراءة، أو الـ API يرجّع نسخة إضافية تحت `user_metadata` للتوافق.

#### 2.0.2 التخزين المزدوج لحالة الـ onboarding

كل تغيير في `current_step` و`status` و`selected_plan` بيتكتب **مرتين**: مرة في جدول `public.users` ومرة في `auth.users.raw_user_meta_data` عن طريق `supabase.auth.updateUser({ data })`. مثال: `updateUserStep()` في `features/auth/profile/api/profile.api.ts:124-141`. أخطاء تحديث `users` غالباً **مش بتتفحص** (`pricing.api.ts:55`، `payment.api.ts:72`، `profile.api.ts:102,227,312,397`).
الحقول `selected_billing_cycle` و`selected_user_type` موجودة في الـ metadata بس ومش في جدول `users`.
**في Laravel:** مصدر واحد، وهو أعمدة جدول `users`. يُقترح إضافة `selected_billing_cycle` و`selected_user_type` كأعمدة، أو الاكتفاء بجدول `subscriptions`.

#### 2.0.3 رفع الملفات (Cloudinary)

`apis/global.api.ts:16-59` → `uploadToCloudinary(file, folder)`:
- Unsigned upload preset `t3meer_images`، cloud `NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME` (default `drk3bhviw`).
- Endpoint: `https://api.cloudinary.com/v1_1/{cloud}/{resourceType}/upload`. الـ `resourceType` = `raw` لو `file.type` بيبدأ بـ `video/`، وإلا `image`، يعني الـ PDF بيترفع عبر الـ image API.
- بيرجّع `secure_url`، والـ DB بيخزن الـ URL بس.
- **مفيش حد للحجم في الكود** إلا في إيصال الدفع (5MB، `PaymentPage.tsx:117`).

الـ folders المستخدمة في القسم:

| Folder | الشاشة | العمود |
|---|---|---|
| `verifications` | Validation (مهندس/مكتب) | `user_validation.documents[]` |
| `payments` | Payment | `subscriptions.confirmation` |
| `engineers/profiles` | إكمال ملف المهندس | `engineers.profile_image` |
| `offices/profiles` | إكمال ملف المكتب | `offices.profile_image` |
| `exhibitions/profiles` | إكمال ملف المعرض | `exhibitions.logo` |
| `materials/profiles` | إكمال ملف المورد | `materials.profile_image` |

**خيارين لـ Laravel:** (أ) `multipart/form-data` والسيرفر يرفع لـ Cloudinary أو S3، أو (ب) الواجهة تفضل ترفع مباشرة والـ API يستقبل `*_url` كـ string (`'url'` + تحقق إن الـ host هو `res.cloudinary.com`). في الجداول تحت الحقل مكتوب كـ `file`، ومعاه بديل `_url`.

#### 2.0.4 Validators و helpers مشتركة

| Helper | المكان | السلوك |
|---|---|---|
| `toEnglishDigits(str)` | `lib/utils.ts:18-23` | يحوّل الأرقام العربية-الهندية `٠-٩` إلى `0-9`. مستخدم في كل الحقول الرقمية. **Laravel:** طبّقه في `prepareForValidation()`. |
| `isValidEgyptianPhone(phone)` | `lib/utils.ts:25-30` | `toEnglishDigits` ثم حذف المسافات ثم regex: `^(?:\+20\|0020)0?1[0125]\d{8}$\|^01[0125]\d{8}$`. **ملاحظة:** الرقم بيتخزن **زي ما المستخدم كتبه** (من غير normalization). |
| `EMAIL_REGEX` | `ForgotPasswordForm.tsx:24`، `VerifyEmail.tsx:27`، `route.ts:7` | `^[^\s@]+@[^\s@]+\.[^\s@]+$` |
| `generateUniqueSlugFromEmail(email)` | `lib/helper-functions.ts:220-245` | يأخذ الجزء قبل `@` ثم `slugify(..., {lower:true, strict:true})`، ولو النتيجة فاضية يبقى `"user"`. لو الـ slug موجود في `users.slug` يضيف `-1` ثم `-2` وهكذا (loop على `users`). |
| Slug fallback في SQL | `supabase_signup_trigger.sql:58-69`، `supabase_update_email_rpc.sql:84-101` | `LOWER(REGEXP_REPLACE(local_part,'[^a-zA-Z0-9]+','-','g'))` مع trim للـ `-` وfallback `user` ونفس الـ suffix loop. |
| `getCurrentLocation()` | `lib/helper-functions.ts:137-185` | Browser geolocation (`enableHighAccuracy`, timeout 15s). لو الدقة أسوأ من 100m بيعيد مرة واحدة. بيرجّع lat/lng كـ **string**. |
| `selectAndPreviewImage(e)` | `lib/helper-functions.ts:253-265` | ملف واحد، preview محلي. **مفيش تحقق من الحجم أو النوع** غير `accept` في الـ input. |

#### 2.0.5 قائمة المحافظات (Governorates): 27 قيمة

المصدر `Static/directoryConfig.ts:10-38` (`egyptGovernorates`)، ومنها `governorates` في `profile.static.ts:325-327` و`GOVERNORATES_LIST` في `features/customer/constants.ts:110`. القيمة المخزنة = الـ label = الاسم العربي:

`القاهرة, الجيزة, الإسكندرية, الدقهلية, الشرقية, القليوبية, الغربية, المنوفية, البحيرة, كفر الشيخ, دمياط, بورسعيد, الإسماعيلية, السويس, الفيوم, بني سويف, المنيا, أسيوط, سوهاج, قنا, الأقصر, أسوان, البحر الأحمر, الوادي الجديد, مطروح, شمال سيناء, جنوب سيناء`

> في `CustomerSetup` الـ label المعروض هو `محافظة {اسم}` لكن القيمة المرسلة هي الاسم فقط (`CustomerSetup.tsx:71-79`).

#### 2.0.6 ترجمة الأخطاء (يُفضّل الـ API يرجّع نفس الرسائل أو error codes مقابلة)

`translateErrorMessage()` في `lib/helper-functions.ts:5-62`:

| الخطأ الإنجليزي (substring) | الرسالة العربية | Laravel مقترح |
|---|---|---|
| `invalid login credentials` / `invalid user` | البريد الإلكتروني أو كلمة المرور غير صحيحة. | 401 `invalid_credentials` |
| `user already registered` / `already exists` | هذا المستخدم مسجل مسبقاً، يرجى تسجيل الدخول. | 409 `account_exists` |
| `email not confirmed` / `email_not_confirmed` | يرجى تأكيد بريدك الإلكتروني أولاً. | 403 `email_not_confirmed` |
| `network error` / `failed to fetch` | فشل الاتصال، يرجى التحقق من الإنترنت والمحاولة مرة أخرى. | (client-side) |
| `not authenticated` / `jwt expired` | انتهت صلاحية الجلسة، يرجى تسجيل الدخول مجدداً. | 401 `unauthenticated` |
| `permission denied` / `violates row-level security policy` | ليس لديك صلاحية للقيام بهذا الإجراء. | 403 `forbidden` |
| `duplicate key value violates unique constraint` | هذا البيان موجود بالفعل مسبقاً. | 409/422 |
| `rate limit` / `too many requests` | لقد قمت بالعديد من المحاولات، يرجى الانتظار قليلاً. | 429 |
| أي رسالة فيها حروف عربية | تُعرض كما هي | |
| غير ذلك | حدث خطأ غير متوقع، يرجى المحاولة لاحقاً. | |

#### 2.0.7 الحماية والتوجيه

- **Middleware** (`middleware.ts:83-139`)، والـ matcher: `/dashboard/:path*`, `/complete-profile/:path*`, `/payment/:path*`, `/customer/:path*`, `/login`, `/register`.
  - بيطلب تسجيل دخول لـ `/dashboard*` و`/complete-profile*` و`/payment*` و`/customer/*` **ما عدا** `/customer/setup`. غير المسجّل بيتحوّل لـ `/login?redirect={pathname}`.
  - لو `current_step === 0` (وليس admin) وفتح `/complete-profile` أو `/dashboard` → `/verify-email`. **مفيش كود بيكتب step 0**.
  - `/complete-profile*` مع `current_step >= 4` → `/dashboard/{user_type}`.
  - `/login` و`/register` لمستخدم مسجّل: admin → `/dashboard/admin`، customer بـ step ≥ 2 → `/dashboard/customer`، غيره بـ step ≥ 4 → `/dashboard/{type}`.
  - **غير محمية:** `/pricing/*`، `/validation/*`، `/verify-email`، `/forgot-password`، `/reset-password`، `/customer/setup`.
- **`getStepRoute(userType, step)`** (`lib/step-redirect.ts:12-33`):
  - customer: step ≤ 1 → `/customer/setup`، وإلا `/dashboard/customer`.
  - admin → `/dashboard/admin`.
  - غير ذلك: step 0/1/2 → `/pricing/{type}`، step 3 → `/complete-profile/{type}`، step ≥ 4 → `/dashboard/{type}`.
  - **التعليق في أعلى الملف (سطر 6) بيقول إن step 1 للمهندس والمكتب يروح `/validation/{type}`، لكن الكود مش بيعمل كده.**

---

### إنشاء حساب جديد — `/register` — `features/auth/signup/components/RegisterForm.tsx`

- **المستخدم:** زائر غير مسجّل (public). لو مسجّل وبريده مؤكد: customer بـ step ≥ 2 بيتحوّل لـ `/dashboard/customer`، وغيره بـ step ≥ 4 بيتحوّل لـ `getStepRoute()` (`RegisterForm.tsx:75-86`)، بالإضافة لقاعدة الـ middleware.
- **الإجراء عند الإرسال:**
  `RegisterForm.handleSubmit` (`:88-138`) → `useSignUp().mutate` (`signup.query.ts:8-69`) → `signUpUser()` (`signup.api.ts:6-74`):
  1. `supabase.rpc("check_user_exists", { p_email: email })` (`:24-34`). لو `data.exists` → يرجّع `{ alreadyExists: true, message: data.message }`، والواجهة تعرض SweetAlert (info) بالرسالة وتحوّل لـ `/login` (`signup.query.ts:15-19`). **تعريف الـ RPC مش موجود في الريبو.** الحقول المقروءة منه: `exists`، `message`، `user_id`.
  2. `generateUniqueSlugFromEmail(email)` (`:36`).
  3. `supabase.auth.signUp({ email, password, options: { emailRedirectTo, data: {...} } })` (`:38-56`):
     - `emailRedirectTo`: customer → `{origin}/customer/setup`، وغيره → `{origin}/pricing/{accountType}`.
     - `data` (→ `auth.users.raw_user_meta_data`): `name`, `phone`, `user_type`, `slug`, `current_step: 1`, `status: "pending"`, `selected_plan: "free"`.
  4. **Trigger** `on_auth_user_created` → `public.handle_new_user()` (`supabase_signup_trigger.sql:18-113`) يعمل INSERT في `public.users` بالأعمدة `(id, email, name, user_type, slug, phone, current_step, status, selected_plan)`:
     - `name` = metadata `name` ثم `fullName` ثم `full_name` ثم `'مستخدم'`.
     - `user_type` يُقبل فقط لو في `customer|engineer|office|supplier|exhibition`، وإلا **`engineer`**.
     - `status` ∈ `pending|active|rejected|suspended`، وإلا `pending`.
     - `selected_plan` ∈ `free|basic|pro|premium`، وإلا `free`.
     - `slug` من الـ metadata، أو يتولّد في SQL.
     - `ON CONFLICT (id) DO UPDATE`. أي exception بيتسجّل كـ WARNING والتسجيل بيكمل عادي.
  5. نتيجة الـ signUp (`signup.query.ts:31-60`):
     - لو رجعت `session` أو `user.email_confirmed_at` (auto-confirm): يخزّن الـ user في store ويكتب cookies `sb-access-token` و`sb-refresh-token` (7 أيام) ثم يروح لـ customer → `/customer/setup` أو غيره → `/pricing/{accountType}`.
     - غير كده: `localStorage.pending_verification_email` و`pending_verification_account_type` ثم `/verify-email?email={email}&accountType={accountType}`.
  6. Meta event `CompleteRegistration` (`signup.query.ts:22-24`).
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `accountType` → metadata `user_type` | نوع الحساب | radio-cards (single select) | نعم | لازم يتختار: "يرجى اختيار نوع الحساب" (`:102-105`) | `customer` (عميل)، `engineer` (مهندس)، `office` (مكتب هندسي)، `exhibition` (معارض وموردين، badge "جديد"). المصدر `RegisterForm.tsx:36-55` | `auth.users.raw_user_meta_data.user_type` → `users.user_type` (trigger) |
| `fullName` → metadata `name` | الاسم الكامل | text | نعم | non-empty بس (`:92`)، مفيش min أو max | — | metadata `name` → `users.name` |
| `email` | البريد الإلكتروني | email | نعم | HTML `type=email` + `required`، والـ pre-check بـ RPC `check_user_exists` | — | `auth.users.email` → `users.email` |
| `phone` | رقم الهاتف | tel | نعم | `isValidEgyptianPhone` (§2.0.4). الرسالة: "رقم الهاتف غير صالح، يجب أن يكون رقم مصري صحيح (مثال: 01xxxxxxxxx أو +201xxxxxxxxx)" (`:111-117`). بيتخزن كما كُتب | — | metadata `phone` → `users.phone` |
| `password` | كلمة المرور | password | نعم | `length >= 6`: "كلمة المرور يجب أن تكون 6 أحرف على الأقل" (`:106-109`) | — | `auth.users.encrypted_password` |
| `confirmPassword` | تأكيد كلمة المرور | password | نعم | `=== password`: "كلمات المرور غير متوافقة" (`:97-100`) | — | **مش بيتخزن** |
| *(derived)* `slug` | — | hidden | — | `generateUniqueSlugFromEmail(email)` | — | metadata `slug` → `users.slug` |
| *(derived)* `currentStep` = `1` | — | hidden | — | ثابت (`:128`) | — | metadata `current_step` → `users.current_step` |
| *(derived)* `status` = `"pending"` | — | hidden | — | ثابت (`:129`) | — | metadata `status` → `users.status` |
| *(derived)* `selected_plan` = `"free"` | — | hidden | — | ثابت (`signup.api.ts:53`) | — | metadata `selected_plan` → `users.selected_plan` |

ترتيب التحقق في الكود: الحقول الفاضية (`fullName, email, phone, password`) ثم: "يرجى ملء جميع الحقول المطلوبة"، ثم تطابق كلمة المرور، ثم نوع الحساب، ثم طول كلمة المرور، ثم الهاتف.

- **قوائم الاختيارات:** `accountType` ∈ `customer | engineer | office | exhibition`. النوع `supplier` موجود في الـ TS union (`:29-34`) لكنه **مش معروض**. وملف `features/auth/signup/api/signup-static.ts` (فيه `material`) **مش مستخدم في أي مكان**.
- **Endpoint مقترح (REST):** `POST /api/v1/auth/register`، public، `throttle:6,1`.

```json
{
  "account_type": "engineer",
  "name": "أحمد محمد",
  "email": "eng.ahmed@example.com",
  "phone": "01012345678",
  "password": "secret1",
  "password_confirmation": "secret1"
}
```

```php
// prepareForValidation: phone = toEnglishDigits + remove whitespace
'account_type' => 'required|in:customer,engineer,office,exhibition',
'name'         => 'required|string|max:255',            // max: مقترح (الكود بلا حد)
'email'        => 'required|email|max:255|unique:users,email',
'phone'        => ['required','string','regex:/^(?:\+20|0020)0?1[0125]\d{8}$|^01[0125]\d{8}$/', 'unique:users,phone'], // unique: مستنتج من رسالة الخطأ (انظر الملاحظات)
'password'     => 'required|string|min:6|confirmed',
```

  السيرفر يولّد `slug` و`current_step=1` و`status=pending` و`selected_plan=free`، ويبعت إيميل التفعيل. رابط الرجوع بعد التفعيل: customer → `{APP_URL}/customer/setup`، وغيره → `{APP_URL}/pricing/{account_type}`.
  **Response 201:** `{ "user": UserResource, "token": "…|null", "requires_email_verification": true }`.
  الواجهة بتستهلك: `alreadyExists` و`message` (409)، `session.access_token` و`session.refresh_token` (token)، `user.email_confirmed_at`.
  **أخطاء:** 409 `account_exists`: "هذا الحساب مسجل لدينا بالفعل، يرجى تسجيل الدخول بدلاً من ذلك." (`signup.api.ts:65`). 422 `phone`: "رقم الهاتف هذا مسجل لمستخدم آخر بالفعل." (`signup.api.ts:67-69`).
- **ملاحظات / Business rules:**
  - رسالة الهاتف المكرر بتطلع لما خطأ Supabase Auth يحتوي كلمة `phone`. ده معناه وجود unique constraint أو trigger على الهاتف في الـ DB، لكنه **مش ظاهر في الريبو**. محتاج تأكيد.
  - الـ trigger بيحوّل أي `user_type` غير معروف لـ `engineer` (`supabase_signup_trigger.sql:38-42`).
  - **اقتراح تصميم:** Supabase مش بيرجّع session قبل تأكيد الإيميل. في Laravel يُفضّل إصدار Sanctum token وقت التسجيل مع `email_verified_at = null`، وحماية باقي الـ onboarding endpoints بـ middleware `verified`. ده هيسهّل شاشة `/verify-email` (polling على `/auth/me`) وتعديل الإيميل بشكل آمن.

---

### تسجيل الدخول — `/login` — `features/auth/login/components/LoginForm.tsx`

- **المستخدم:** زائر (public). يدعم query param `?redirect=/path` (`LoginForm.tsx:19`). لو مسجّل بالفعل يتحوّل فوراً لـ `redirect` أو `getStepRoute()` (`:24-35`).
- **الإجراء عند الإرسال:** `handleSubmit` (`:37-84`) → `useSignIn` (`login.query.ts:62-74`) → `loginWithPassword()` → `supabase.auth.signInWithPassword({ email, password })` (`login.api.ts:33-41`):
  - **نجاح:** `setUser(user)`، invalidate `["authenticated_user"]`، ثم `router.push(redirect ?? getStepRoute(user_type || "engineer", current_step || 1))`. الـ cookies `sb-access-token` و`sb-refresh-token` بتتكتب من `onAuthStateChange` (`login.query.ts:25-43`).
  - **خطأ `email not confirmed`:** يحفظ `localStorage.pending_verification_email` ويعرض confirm: "البريد الإلكتروني غير مفعّل…". لو وافق → `/verify-email?email={email}` (`:57-75`).
  - **أي خطأ آخر:** "خطأ في تسجيل الدخول" + نص الخطأ.
- **حقول الفورم:**

| key | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `email` | البريد الإلكتروني | email | نعم | non-empty (`:40-43`)، الرسالة: "يرجى إدخال البريد الإلكتروني وكلمة المرور" + HTML `type=email` | — | read: `auth.users.email` |
| `password` | كلمة المرور | password | نعم | non-empty | — | read |
| *(query)* `redirect` | — | URL param | لا | — | — | — |

- **قوائم الاختيارات:** لا يوجد.
- **Endpoint مقترح (REST):**
  - `POST /api/v1/auth/login`، public، `throttle:10,1`، body `{ "email": "…", "password": "…" }`، rules `'email'=>'required|email','password'=>'required|string'`.
    **Response 200:** `{ "token": "…", "user": UserResource }`. **401** `invalid_credentials`. **403** `email_not_confirmed` (الواجهة بتعتمد على الحالة دي لعرض خيار الذهاب لصفحة التفعيل).
  - `GET /api/v1/auth/me` (Bearer) → `UserResource`. بديل `getCurrentUser()` (`login.api.ts:26-31`) المستدعى في كل الشاشات عبر `useCurrentUser()`.
  - `POST /api/v1/auth/logout` (Bearer) → 204. الواجهة بتمسح الـ cookies في `clearUser()` (`useUserStore.ts:31-37`).
  - بديل `setAuthSession(access, refresh)` (`login.api.ts:3-24`)، اللي بيستخدمه Validation لقراءة `#access_token` من الـ URL hash بعد رابط التفعيل: في Laravel مش هنحتاجه لو رابط التفعيل وجّه للواجهة ومعاه token أو طلب تسجيل الدخول.
- **ملاحظات:** أزرار Google وFacebook موجودة لكن **commented out** (`:198-241`)، فمفيش OAuth حالياً.

---

### نسيت كلمة المرور — `/forgot-password` — `features/auth/forgot-password/components/ForgotPasswordForm.tsx`

- **المستخدم:** زائر (public).
- **الإجراء عند الإرسال:** `handleSubmit` (`:15-35`) → `useSendPasswordReset` (`forgot-password.query.ts:5-15`) → `sendPasswordResetEmail(email)` → `supabase.auth.resetPasswordForEmail(email, { redirectTo: "{origin}/reset-password" })` (`forgot-password.api.ts:4-11`). عند النجاح: SweetAlert "تم الإرسال"، وتتغير الشاشة لحالة success تعرض الإيميل، وفيها زر "إعادة الإرسال بعنوان آخر" (reset للفورم).
- **حقول الفورم:**

| key | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `email` | البريد الإلكتروني | email | نعم | فاضي: "يرجى إدخال البريد الإلكتروني"، و regex `^[^\s@]+@[^\s@]+\.[^\s@]+$`: "يرجى إدخال بريد إلكتروني صحيح" (`:19-28`) | — | read: `auth.users.email` |

- **Endpoint مقترح (REST):** `POST /api/v1/auth/password/forgot`، public، `throttle:3,1`. Body `{ "email": "…" }`، rule `'email'=>'required|email'`. **Response 200** دائماً `{ "message": "…" }` (عشان ما نكشفش وجود الحساب، زي Supabase). الرابط في الإيميل: `{APP_URL}/reset-password?token={token}&email={email}`.
- **ملاحظات:** الواجهة مش بتستهلك أي حقل من الـ response.

---

### إعادة تعيين كلمة المرور — `/reset-password` — `features/auth/forgot-password/components/ResetPasswordForm.tsx`

- **المستخدم:** زائر جاي من رابط الإيميل. حالياً الشاشة بتستنى event `PASSWORD_RECOVERY` أو session موجودة (`:31-48`)، وقبلها بتعرض "جاري التحقق من الرابط...".
- **الإجراء عند الإرسال:** `handleSubmit` (`:50-74`) → `useUpdatePassword` → `updatePassword(newPassword)` → `supabase.auth.updateUser({ password })` (`forgot-password.api.ts:13-20`). عند النجاح تظهر شاشة "تم تغيير كلمة المرور" مع رابط `/login`.
- **حقول الفورم:**

| key | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `password` | كلمة المرور الجديدة | password | نعم | فاضي: "يرجى إدخال كلمة المرور الجديدة"، و `length >= 6`: "كلمة المرور يجب أن تكون 6 أحرف على الأقل" | — | `auth.users.encrypted_password` |
| `confirmPassword` | تأكيد كلمة المرور | password | نعم | `=== password`: "كلمات المرور غير متوافقة" | — | مش بيتخزن |
| *(hidden, Laravel)* `token`, `email` | — | URL query | نعم | — | — | `password_reset_tokens` |

- **Endpoint مقترح (REST):** `POST /api/v1/auth/password/reset`، public.

```json
{ "token": "…", "email": "eng.ahmed@example.com", "password": "newpass1", "password_confirmation": "newpass1" }
```

```php
'token' => 'required|string', 'email' => 'required|email', 'password' => 'required|string|min:6|confirmed',
```

  **Response 200** `{ "message": "…" }`. **422** لو الـ token غير صالح أو منتهي.
- **ملاحظات:** الواجهة محتاجة تعديل عشان تقرا `token` و`email` من الـ query بدل الـ Supabase recovery session.

---

### تفعيل البريد الإلكتروني — `/verify-email` — `features/auth/verify-email/components/VerifyEmail.tsx` (+ `app/api/auth/update-email/route.ts`)

- **المستخدم:** مستخدم سجّل وبريده مش مؤكد (public، مش محمي في الـ middleware). Query params: `email` و`accountType` (camelCase). الـ fallback بالترتيب: الـ user الحالي، ثم `localStorage.pending_verification_email` و`pending_verification_account_type` (`:49-90`). القيمة الافتراضية لـ accountType = `"engineer"`.
- **الإجراء (3 أجزاء):**
  1. **Auto-redirect بعد التأكيد** (`:93-157`): عند التحميل، وكل **10 ثواني**، وعند `focus` أو `visibilitychange`، بيستدعي `supabase.auth.getUser()`. لو `email_confirmed_at` موجود:
     - لو النوع ≠ `customer` و `current_step < 2` → `updateUserStep(2)` (`profile.api.ts:124-141`: `users.update({current_step:2}).eq("id")` + `auth.updateUser({data:{current_step:2}})`).
     - ثم `router.replace`: customer → `/customer/setup`، غيره → `/pricing/{type}`.
  2. **زر "إعادة إرسال رابط التفعيل"** (`:175-217`) → `resendVerificationEmail(email, accountType)` → `supabase.auth.resend({ type: "signup", email, options: { emailRedirectTo } })` (`verify-email.api.ts:5-28`). الـ redirect: customer → `/customer/setup`، وغيره → `/pricing/{type}`. بعد النجاح countdown **60 ثانية**. خطأ rate-limit (`rate limit`، `over_email_send_rate_limit`، `seconds`) بيعرض "يرجى الانتظار دقيقة واحدة…" ويبدأ countdown 60.
  3. **Modal "تعديل البريد"** (`:234-305`):
     a. `checkEmailExists(newEmail)` → `rpc("check_user_exists", { p_email })` (`verify-email.api.ts:30-40`). لو الإيميل موجود → "هذا البريد الإلكتروني مسجل بالفعل، يرجى اختيار بريد آخر".
     b. `updateUserEmail({ oldEmail, newEmail, accountType })` (`verify-email.api.ts:42-133`):
        - `newSlug = generateUniqueSlugFromEmail(newEmail)`.
        - **مسار client:** لو فيه session و`user.email === oldEmail`: `auth.updateUser({ email: newEmail, data: { slug: newSlug, email: newEmail } }, { emailRedirectTo })` ثم `users.update({ email, name, slug }).eq("id", user.id)`. الـ `name` = metadata `name` أو `fullName` أو `"مستخدم"`. ويرجّع `emailSent: true`.
        - **مسار server:** `POST /api/auth/update-email` body `{ oldEmail, newEmail, newSlug, accountType }` (`route.ts:9-197`):
          - يتحقق: الإيميلين صالحين بالـ regex، ومختلفين.
          - RPC `update_unconfirmed_user_email(p_old_email, p_new_email, p_new_slug)`، بالـ service role لو موجود، وإلا anon.
          - الـ RPC (`supabase_update_email_rpc.sql:7-141`): يرفض لو الإيميل الجديد موجود في `auth.users`، أو لو الحساب القديم مؤكد بالفعل، أو لو مش موجود. يعمل UPDATE لـ `public.users.email` و`slug`، و UPDATE لـ `auth.users.email` و`confirmation_sent_at = NULL` و`raw_user_meta_data.email` و`raw_user_meta_data.slug`.
          - ثم `auth.resend({ type: "signup", email: newEmail, options: { emailRedirectTo } })`. الـ redirect هنا: customer → **`/dashboard/customer`**، وغيره → `/pricing/{type}` (`route.ts:44-48`).
          - Response: `{ success, newEmail, newSlug, emailSent, emailSendError, message }`.
     c. يحدّث الـ state و `localStorage.pending_verification_email` و `pending_verification_slug`، والـ URL `?email=`. لو `emailSent` يبدأ countdown 60.
- **حقول الفورم (Modal تعديل البريد):**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `newEmail` | البريد الإلكتروني الجديد | email | نعم | `trim().toLowerCase()`، regex: "يرجى إدخال بريد إلكتروني صحيح"، ≠ الحالي: "البريد الإلكتروني الجديد مطابق للبريد الحالي"، غير مسجّل: "هذا البريد الإلكتروني مسجل بالفعل، يرجى اختيار بريد آخر" (`:238-259`) | — | `auth.users.email`، `users.email`، `raw_user_meta_data.email` |
| *(hidden)* `oldEmail` | — (يُعرض في كارت "البريد الإلكتروني المسجل") | hidden | نعم | regex (server) | — | lookup |
| *(hidden)* `accountType` | — | hidden | لا | default `engineer` | `customer, engineer, office, exhibition` | يحدد `emailRedirectTo` |
| *(derived)* `newSlug` | — | hidden | — | `generateUniqueSlugFromEmail` | — | `users.slug`، `raw_user_meta_data.slug` |

  **زر إعادة الإرسال** مالوش inputs: بيستخدم `email` و`accountType` المحسوبين.
- **Endpoints مقترحة (REST):**
  - `POST /api/v1/auth/email/resend`، public أو Bearer، `throttle:1,1` (الواجهة عندها countdown 60 ثانية). Body `{ "email": "…", "account_type": "engineer" }`، rules `'email'=>'required|email|exists:users,email','account_type'=>'nullable|in:customer,engineer,office,exhibition'`. **429** عند تجاوز المعدل.
  - `GET /api/v1/auth/email/verify/{id}/{hash}` (signed URL): يضبط `email_verified_at`، وللنوع ≠ customer يضبط `current_step = max(current_step, 2)` (ده بينقل منطق `VerifyEmail.tsx:100-107` للسيرفر)، ثم 302 للواجهة: customer → `/customer/setup`، وغيره → `/pricing/{user_type}`.
  - `PATCH /api/v1/auth/email` (Bearer، يُسمح فقط لو `email_verified_at IS NULL`). Body `{ "new_email": "…" }`، rules `'new_email'=>'required|email|different:current_email|unique:users,email'`. السيرفر يعيد توليد الـ `slug` من الإيميل الجديد ويبعت رابط التفعيل. **Response:** `{ "success": true, "new_email": "…", "new_slug": "…", "email_sent": true, "message": "…" }`.
  - (polling) `GET /api/v1/auth/me` → الواجهة بتفحص `email_verified_at`.
- **ملاحظات / Business rules:**
  - **ثغرة أمنية حالية:** الـ RPC `update_unconfirmed_user_email` عليه `GRANT EXECUTE … TO anon` (`supabase_update_email_rpc.sql:144`)، ومعاه `/api/auth/update-email` اللي مش بيطلب auth. ده معناه إن أي حد يعرف إيميل حساب لسه مش مؤكد يقدر يغيّره. **في Laravel لازم يتطلب token.**
  - عدم اتساق: رابط التفعيل للـ customer بعد تعديل الإيميل من السيرفر بيروح `/dashboard/customer` (`route.ts:47`)، لكن في باقي الأماكن بيروح `/customer/setup`.
  - تعديل الإيميل **بيغيّر الـ slug** كمان.

---

### توثيق المهندس — `/validation/engineer` — `features/auth/validation/components/EngineerValidation.tsx`

- **المستخدم:** مهندس مسجّل (`userId` لازم يكون موجود، وإلا "يرجى تسجيل الدخول أولاً"). لو `current_step > 1` بيتحوّل لـ `getStepRoute()` (`:40-50`). بيقرا `#access_token` و`#refresh_token` من الـ URL hash ويعمل `setSession` (`:52-63`). **الصفحة مش محمية في الـ middleware.**
- **الإجراء عند الإرسال:** `handleSubmit` (`:74-127`):
  1. `uploadToCloudinary(file, "verifications")`.
  2. `useUpdateUserValidation().mutate(payload)` → `updateUserValidation()` (`validation.api.ts:3-49`):
     - `user_validation.upsert(payload, { onConflict: "user_id" })`.
     - لأن `status` و`user_id` موجودين: `users.update({ status, current_step: 2 /* لو metadata.current_step < 2 */ }).eq("id", user_id)`، ثم `auth.updateUser({ data: { status, current_step? } })`. خطأ تحديث `users` بيتسجّل بس، وخطأ الـ auth بيعمل throw.
  3. نجاح: "تم إرسال البيانات بنجاح، بانتظار المراجعة" ثم `router.push("/pricing/engineer")`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `name` (UI `fullName`) | الاسم بالكامل (FULL NAME) | text | نعم | non-empty | — | `user_validation.name` |
| `professional_id` (UI `membershipId`) | رقم عضوية النقابة (MEMBERSHIP ID) | text (`inputMode=numeric`) | نعم | `toEnglishDigits` ثم حذف أي حرف غير `[0-9]` (`:182-188`)، مفيش طول محدد | — | `user_validation.professional_id` |
| `details.graduation_year` | سنة التخرج (GRADUATION YEAR) | select | نعم | من القائمة | `getGraduationYearOptions()`: من السنة الحالية نزولاً إلى **1970** كـ strings (`validation.static.ts:6-12`) | `user_validation.details->graduation_year` (string) |
| `details.specialization` | التخصص (SPECIALIZATION) | select | نعم | من القائمة | `مدني`، `معماري` (`validation.static.ts:1-4`) | `user_validation.details->specialization` |
| `documents[0]` | صورة كارنيه النقابة | file (زرين: "التقط بالكاميرا" `capture="environment"`، و"اختر من المعرض") | نعم | `accept="image/*"` (`:266,290`)، ملف واحد، **مفيش حد للحجم** | — | Cloudinary `verifications` → `user_validation.documents` = `[url]` |
| *(hidden)* `user_id` | — | hidden | نعم | — | — | `user_validation.user_id` (unique، onConflict) |
| *(hidden)* `account_type` = `"engineer"` | — | hidden | — | — | — | `user_validation.account_type` |
| *(hidden)* `status` = `"active"` | — | hidden | — | — | — | `user_validation.status`، **`users.status`**، metadata `status` |
| *(derived)* `current_step` = `2` (لو < 2) | — | hidden | — | — | — | `users.current_step`، metadata |

  رسالة التحقق العامة: "يرجى ملء جميع الحقول ورفع صورة الكارنيه" (`:81-90`). زر الإرسال disabled لحد ما الحقول كلها تتملي.
- **Endpoint مقترح (REST):** `POST /api/v1/onboarding/validation` (Bearer، verified)، `multipart/form-data`:

```
account_type=engineer
name=أحمد محمد علي
professional_id=12345678
graduation_year=2018
specialization=مدني
documents[]=<file image/*>          (أو documents_urls[]=https://res.cloudinary.com/…)
```

```php
'account_type'    => 'required|in:engineer,office',
'name'            => 'required|string|max:255',
'professional_id' => 'required|regex:/^[0-9]+$/',
'graduation_year' => 'required_if:account_type,engineer|integer|between:1970,'.date('Y'),
'specialization'  => 'required_if:account_type,engineer|in:مدني,معماري',
'documents'       => 'required|array|size:1',          // engineer
'documents.*'     => 'file|image|max:10240',           // max: مقترح (الكود بلا حد)
```

  **Response 200:** `UserResource` (`status`، `current_step`). الواجهة بتعمل `setQueryData(["authenticated_user"], user)` (`validation.query.ts:17-31`).
- **ملاحظات:** انظر ملاحظات التوثيق المشتركة بعد شاشة المكتب.

---

### توثيق المكتب الهندسي — `/validation/office` — `features/auth/validation/components/OfficeValidation.tsx`

- **المستخدم:** مكتب مسجّل. نفس الـ guards ومعالجة الـ hash زي المهندس (`:41-64`).
- **الإجراء عند الإرسال:** `handleSubmit` (`:84-137`): رفع الملفين بالتوازي `uploadToCloudinary(registryFile, "verifications")` و`uploadToCloudinary(taxFile, "verifications")`، ثم نفس `updateUserValidation()`، ثم `router.push("/pricing/office")`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `name` (UI `officeName`) | اسم المكتب (OFFICE NAME) | text | نعم | non-empty | — | `user_validation.name` |
| `professional_id` (UI `registrationNumber`) | رقم السجل الهندسي (REGISTRATION NUMBER) | text numeric | نعم | `toEnglishDigits` ثم حذف `\D` (`:195-199`) | — | `user_validation.professional_id` |
| `tax_number` (UI `taxCardNumber`) | رقم البطاقة الضريبية (TAX CARD NUMBER) | text numeric | نعم | `toEnglishDigits` ثم حذف `\D` (`:222-226`) | — | `user_validation.tax_number` |
| `documents[0]` | مستند السجل الهندسي | file (كاميرا `image/*` أو "ملف من الجهاز" `.jpg,.jpeg,.png,.pdf`) | نعم | `accept` (`:266,283`)، مفيش حد للحجم | — | Cloudinary `verifications` → `user_validation.documents[0]` |
| `documents[1]` | مستند البطاقة الضريبية | file (نفس الخيارين) | نعم | `accept` (`:359,376`) | — | `user_validation.documents[1]` |
| *(hidden)* `user_id`, `account_type="office"`, `status="pending"` | — | hidden | — | — | — | `user_validation.*`، `users.status`، metadata `status` |
| *(derived)* `current_step=2` (لو < 2) | — | hidden | — | — | — | `users.current_step`، metadata |

  رسالة التحقق: "يرجى ملء جميع الحقول ورفع جميع المستندات المطلوبة".
- **Endpoint مقترح (REST):** نفس `POST /api/v1/onboarding/validation` مع:

```
account_type=office
name=مكتب الهندسة الحديثة
professional_id=000123456
tax_number=987654321
documents[]=<registry: jpg|jpeg|png|pdf>
documents[]=<tax card: jpg|jpeg|png|pdf>
```

```php
'tax_number'  => 'required_if:account_type,office|regex:/^[0-9]+$/',
'documents'   => 'required|array|size:2',                       // office: [registry, tax]
'documents.*' => 'file|mimes:jpg,jpeg,png,pdf|max:10240',      // max: مقترح
```

- **ملاحظات / Business rules (للتوثيق بنوعيه):**
  - **الشاشتين orphaned:** مفيش route ولا link في الكود بيوصّل لـ `/validation/*`، و`getStepRoute()` مش بيرجّعهم. وملف `t3meer_full_analysis.md` (سطر 8 و273 و525-530) بيقول إن خطوة التوثيق وجدول `user_validation` اتلغوا. لكن الكود لسه بيكتب في `user_validation` (انظر إكمال الملف). **القرار:** الخطوة اتعرفت كخطوة رسمية في الـ onboarding للمهندس والمكتب. المواصفات الكاملة في **2.V** تحت.
  - **عدم اتساق في الحالة:** المهندس بيكتب `status: "active"` (`EngineerValidation.tsx:109`)، والمكتب `"pending"` (`OfficeValidation.tsx:119`). والقيمة دي بتتنسخ لـ `users.status`، يعني المهندس بيبقى active من غير مراجعة.
  - **إكمال الملف بعد كده بيعمل overwrite لصف `user_validation` ببيانات placeholder** (انظر شاشات إكمال الملف). يعني البيانات الحقيقية اللي اترفعت هنا بتضيع.
  - `details` نوعه JSON. و`documents` array of URLs (jsonb أو text[]، النوع الفعلي مش ظاهر في الريبو).

---

### 2.V خطوة التوثيق (`user_validation` step) — المواصفات المطلوبة للـ Backend

> **الوضع الحالي:** أجزاء خطوة التوثيق كلها موجودة في الكود: شاشتين (`/validation/engineer` و`/validation/office`)، وجدول `user_validation`، وشاشة مراجعة عند الأدمن (`/dashboard/admin/verifications`). المشكلة إن **مفيش أي مسار بيوصّل للخطوة دي**. الـ `getStepRoute()` بيبعت step 1 للأسعار، وصفحة تأكيد الإيميل بتنقل المستخدم لـ step 2 على طول.
> **المطلوب:** الخطوة دي تبقى **خطوة رسمية في الـ onboarding**، في نفس المكان اللي كان متصمم لها أصلاً حسب التعليق في `lib/step-redirect.ts:6-7`: _"step 1 + (engineer | office) → /validation/{type} · step 1 + other types → /pricing/{type} (no validation needed)"_.
> كل اللي في القسم ده **(مقترح)** للـ Laravel، إلا اللي جنبه `path:line` من الكود الحالي. القيم الافتراضية للقرارات المفتوحة مكتوبة في 2.V.8.

#### 2.V.1 مين بيمر بخطوة التوثيق

| `user_type` | التوثيق | المستندات المطلوبة | البيانات المطلوبة |
|---|---|---|---|
| `engineer` | **إلزامي** | 1: صورة كارنيه النقابة (`syndicate_card`) | الاسم بالكامل، رقم عضوية النقابة، سنة التخرج، التخصص (`مدني` / `معماري`) |
| `office` | **إلزامي** | 2: مستند السجل الهندسي (`engineering_registry`) + البطاقة الضريبية (`tax_card`) | اسم المكتب، رقم السجل الهندسي، رقم البطاقة الضريبية |
| `exhibition` (المعارض والموردين) | مش مطلوب (بيروحوا للأسعار على طول) | — | — |
| `customer`, `admin` | لا | — | — |

يُفضّل إن الأنواع اللي محتاجة توثيق تبقى في config (`config('t3meer.validation_required_types') = ['engineer','office']`)، عشان لو حبيتوا تضيفوا المعارض والموردين (`exhibition`) بعدين.

#### 2.V.2 مكان الخطوة في الـ flow (الـ State Machine بعد الإضافة)

```text
register ──► تأكيد الإيميل ──► [step 1]
                                  │
                                  ├─ engineer / office ──► /validation/{type} ──(submit)──► [step 2]
                                  │                                                           │
                                  └─ exhibition (معرض/مورد) ────────────────────────────────►│
                                                                                              ▼
                                                                    /pricing/{type} ──(plan/payment)──► [step 3]
                                                                                                          │
                                                                    /complete-profile/{type} ──(save)──► [step 4] ──► /dashboard/{type}

بالتوازي (مش blocking):  user_validation.status = pending ──(أدمن)──► approved / rejected ──(إعادة إرسال)──► pending
```

| `current_step` | engineer / office | exhibition (معارض وموردين) | customer | الإجراء اللي بيرفع الخطوة |
|---|---|---|---|---|
| إيميل مش متأكد (`email_verified_at IS NULL`) | `/verify-email` | `/verify-email` | `/verify-email` | تأكيد الإيميل. **مايرفعش `current_step`** للـ engineer/office (حالياً `VerifyEmail.tsx:100-107` بيرفعها لـ 2) |
| `1` | **`/validation/{type}`** ⬅ جديد | `/pricing/{type}` | `/customer/setup` | engineer/office: `POST /onboarding/validation` ← 2. exhibition: فتح الأسعار ← 2 (زي الكود الحالي) |
| `2` | `/pricing/{type}` | `/pricing/{type}` | `/dashboard/customer` | اختيار باقة / دفع ← 3 |
| `3` | `/complete-profile/{type}` | `/complete-profile/{type}` | — | حفظ الملف ← 4 |
| `≥ 4` | `/dashboard/{type}` | `/dashboard/{type}` | — | — |

- **أرقام الخطوات ما اتغيرتش** عن الكود الحالي (القسم 2.A). التغيير الوحيد إن step 1 للـ engineer/office بقى بيروح `/validation/{type}` بدل `/pricing/{type}`، وإن step 2 للنوعين دول معناه **"التوثيق اتبعت"**.
- **الخطوة مش blocking:** أول ما المستخدم يبعت التوثيق بيكمل للأسعار على طول (زي الكود الحالي: `router.push("/pricing/engineer")` مع رسالة "بانتظار المراجعة"). مراجعة الأدمن بتمشي بالتوازي، والحساب **مايبقاش `active`** (ومايظهرش في الأدلة العامة) غير بعد قبول التوثيق (2.V.3).
- السيرفر يرجّع `next_route` في `UserResource` بنفس الجدول ده، والفرونت يمشي عليه بدل ما يكرر المنطق.

#### 2.V.3 حالات التوثيق وتأثيرها على الحساب

**`user_validation.status`** (القيم المقترحة `pending | approved | rejected`، بدل القيم الحالية `pending | active | reject`، شوف 7.1 #4):

| من | إلى | مين / إمتى | Endpoint |
|---|---|---|---|
| — (مفيش صف) | `pending` | المستخدم يبعت التوثيق لأول مرة | `POST /api/v1/onboarding/validation` |
| `pending` | `approved` | الأدمن يقبل | `POST /api/v1/admin/verifications/{id}/approve` |
| `pending` | `rejected` | الأدمن يرفض (السبب إلزامي) | `POST /api/v1/admin/verifications/{id}/reject` |
| `rejected` | `pending` | المستخدم يعدّل ويبعت تاني | `PUT /api/v1/me/validation` |
| `approved` | `pending` | المستخدم يغيّر الاسم أو الرقم أو المستندات (مراجعة جديدة، قرار 2.V.8 #4) | `PUT /api/v1/me/validation` |
| `approved` / `rejected` | `pending` | الأدمن يرجّع الطلب للمراجعة (بديل اختيار "معلق" اللي بيرفض حالياً، القسم 4) | `POST /api/v1/admin/verifications/{id}/reset` |

**تأثيرها على `users.status`** (السيرفر بس هو اللي يحسبها، في service واحد مثلاً `AccountStatusService::recalculate($user)` بعد أي تغيير في التوثيق أو الاشتراك أو الخطوة):

| `users.status` | الشرط (للأنواع اللي محتاجة توثيق) |
|---|---|
| `pending` | الحالة الافتراضية لحد ما الشروط تكتمل (التوثيق `pending`، أو الملف لسه ماكملش) |
| `active` | `user_validation.status = approved` **و** `current_step >= 4` **و** الاشتراك مش في حالة `pending` (حسب قرار 7.1 #5) |
| `rejected` | `user_validation.status = rejected`. المستخدم يقدر يدخل ويشوف السبب ويبعت تاني، بس مايظهرش في الأدلة |
| `suspended` | الأدمن بس، ومش بيتأثر بالتوثيق |

- للأنواع اللي مش محتاجة توثيق (`exhibition`): شرط التوثيق بيتشال من المعادلة.
- **عمود جديد مقترح:** `users.is_verified` (boolean، أو يتحسب من `user_validation.status = approved`). يرجع في `UserResource` وفي ملفات الأدلة العامة، عشان تتعرض علامة "موثّق" (الواجهة فيها أيقونة `solar:verified-check-bold` فعلاً في `EditEngineerProfile.tsx:430` و`EditOfficeProfile.tsx:634`).
- كل تغيير في الحالة يكتب `interaction_logs` بنفس نصوص الكود الحالي: "تم قبول طلب توثيق الحساب بنجاح." / "تم رفض طلب توثيق الحساب، راجع التنبيهات." (`admin.api.ts:219-227`). **(مقترح)** وكمان إيميل للمستخدم فيه النتيجة وسبب الرفض.

#### 2.V.4 الجدول — `user_validations` (Laravel migration مقترحة)

| Column | Type | Constraints | ملاحظات / المقابل في الجدول الحالي `user_validation` |
|---|---|---|---|
| `id` | `bigIncrements` (أو `uuid`) | PK | `id` |
| `user_id` | `uuid` | FK → `users.id` `cascadeOnDelete`، **UNIQUE** (صف واحد لكل مستخدم) | `user_id` (onConflict حالياً) |
| `account_type` | `enum('engineer','office')` | NOT NULL | `account_type`. السيرفر يحطه من `users.user_type`، مش من الـ request |
| `name` | `string(255)` | NOT NULL | `name`: الاسم بالكامل للمهندس، أو اسم المكتب |
| `professional_id` | `string(50)` | NOT NULL | `professional_id`: رقم عضوية النقابة (مهندس) أو رقم السجل الهندسي (مكتب). أرقام بس |
| `tax_number` | `string(50)` | nullable | `tax_number`. للمكتب، إلزامي لما `account_type = office` |
| `graduation_year` | `smallInteger` | nullable | حالياً `details.graduation_year` (مرة string ومرة int). للمهندس، إلزامي |
| `specialization` | `string(50)` | nullable | حالياً `details.specialization` (`مدني` / `معماري`). للمهندس، إلزامي |
| `documents` | `json` | NOT NULL | حالياً array URLs بالترتيب. المقترح: `[{ "type": "syndicate_card", "url": "..." }]`. أنواع المستندات: `syndicate_card` · `engineering_registry` · `tax_card` |
| `status` | `enum('pending','approved','rejected')` | default `pending`، index | `status` (`pending`/`active`/`reject`) |
| `rejection_reason` | `text` | nullable | `rejection_reason`. يتمسح (null) لما يتقبل أو يتبعت تاني |
| `submitted_at` | `timestamp` | NOT NULL | جديد: آخر إرسال |
| `reviewed_at` | `timestamp` | nullable | جديد |
| `reviewed_by` | `uuid` | nullable، FK → `users.id` (الأدمن) | جديد |
| `created_at`, `updated_at` | `timestamps` | | `created_at` |

- **(اختياري)** جدول تاريخ المراجعات `user_validation_reviews` (`validation_id`, `status`, `reason`, `admin_id`, `created_at`)، عشان تاريخ الرفض والقبول مايضيعش مع كل إرسال جديد.
- **نقل البيانات الحالية:**
  - `details.*` يتحول للأعمدة الجديدة.
  - الـ `documents`: المهندس `[0] → syndicate_card`، والمكتب `[0] → engineering_registry` و`[1] → tax_card`.
  - الحالات: `active → approved` و`reject → rejected`.
  - ⚠ الصفوف اللي فيها بيانات placeholder (اللي `profile.api.ts:105-119,230-241,315-326,400-411` بيعملها: `documents` فيها `placehold.co`، أو `professional_id` عشوائي أو `123456`) **مش توثيق حقيقي**. يُفضّل تتحذف، أو تتعلم إن المستخدم محتاج يبعت التوثيق (قرار 2.V.8 #5).

#### 2.V.5 الـ Endpoints

##### (1) `GET /api/v1/onboarding/validation` — حالة وبيانات التوثيق
- **Auth:** Bearer، `user_type ∈ {engineer, office}`. بيستخدمه الفرونت في صفحة `/validation/{type}` وفي قسم التوثيق في الداشبورد (بديل `getUserValidation()` في `validation.api.ts:51-60`).
- **Response 200:**

```json
{
  "data": {
    "status": "rejected",
    "rejection_reason": "صورة الكارنيه غير واضحة",
    "account_type": "engineer",
    "name": "أحمد محمد علي",
    "professional_id": "12345678",
    "tax_number": null,
    "graduation_year": 2018,
    "specialization": "مدني",
    "documents": [{ "type": "syndicate_card", "url": "https://res.cloudinary.com/.../verifications/x.jpg" }],
    "submitted_at": "2026-11-03T08:00:00Z",
    "reviewed_at": "2026-11-03T10:00:00Z"
  },
  "required": true,
  "required_documents": ["syndicate_card"]
}
```

لو مفيش توثيق اتبعت قبل كده: `"data": null`.

##### (2) `POST /api/v1/onboarding/validation` — إرسال التوثيق (خطوة الـ onboarding)
- **Auth:** Bearer + إيميل متأكد + `user_type ∈ {engineer, office}`.
- **مسموح لما:** `current_step == 1`، أو `status = rejected` (إعادة إرسال). غير كده: **409** `{ "code": "validation_already_submitted", "next_route": "..." }`. ولو النوع مش محتاج توثيق: **403** `validation_not_required`.
- **Content-Type:** `multipart/form-data` (أو JSON بالـ URLs لو الرفع هيفضل على Cloudinary من الفرونت، القسم 0.3.4).

| key | الاسم في الواجهة | type | required | validation | → column |
|---|---|---|---|---|---|
| `name` | الاسم بالكامل (FULL NAME) / اسم المكتب (OFFICE NAME) | text | ✔ | `string\|max:255` | `name` |
| `professional_id` | رقم عضوية النقابة (MEMBERSHIP ID) / رقم السجل الهندسي (REGISTRATION NUMBER) | text numeric | ✔ | أرقام بس بعد تحويل الأرقام العربية (`toEnglishDigits`، زي الفرونت) `regex:/^[0-9]+$/`، **(مقترح)** `max:50` | `professional_id` |
| `graduation_year` | سنة التخرج (GRADUATION YEAR) | select | ✔ للمهندس | `integer\|between:1970,{السنة الحالية}` (`validation.static.ts:6-12`) | `graduation_year` |
| `specialization` | التخصص (SPECIALIZATION) | select | ✔ للمهندس | `in:مدني,معماري` (`validation.static.ts:1-4`) | `specialization` |
| `tax_number` | رقم البطاقة الضريبية (TAX CARD NUMBER) | text numeric | ✔ للمكتب | `regex:/^[0-9]+$/`، `max:50` | `tax_number` |
| `syndicate_card` | صورة كارنيه النقابة (كاميرا أو معرض) | file | ✔ للمهندس | `image` (الفرونت `accept="image/*"`)، **(مقترح)** `max:10240` KB | `documents[type=syndicate_card]` |
| `engineering_registry` | مستند السجل الهندسي | file | ✔ للمكتب | `mimes:jpg,jpeg,png,pdf` (`OfficeValidation.tsx:266,283`)، `max:10240` | `documents[type=engineering_registry]` |
| `tax_card` | مستند البطاقة الضريبية | file | ✔ للمكتب | `mimes:jpg,jpeg,png,pdf` (`OfficeValidation.tsx:359,376`)، `max:10240` | `documents[type=tax_card]` |

- **حقول بيتجاهلها السيرفر لو اتبعتت** (الفرونت الحالي بيبعتها): `user_id`، `account_type`، `status`، `current_step`. كلها بتتحدد من التوكن. **مهم:** الكود الحالي بيبعت `status: "active"` من شاشة المهندس (`EngineerValidation.tsx:109`)، وده بيفعّل الحساب من غير مراجعة.

```php
// FormRequest: StoreUserValidationRequest
$type = $this->user()->user_type; // engineer | office
return [
  'name'                 => 'required|string|max:255',
  'professional_id'      => 'required|regex:/^[0-9]+$/|max:50',
  'graduation_year'      => [Rule::requiredIf($type === 'engineer'), 'nullable', 'integer', 'between:1970,'.date('Y')],
  'specialization'       => [Rule::requiredIf($type === 'engineer'), 'nullable', Rule::in(['مدني', 'معماري'])],
  'tax_number'           => [Rule::requiredIf($type === 'office'), 'nullable', 'regex:/^[0-9]+$/', 'max:50'],
  'syndicate_card'       => [Rule::requiredIf($type === 'engineer'), 'nullable', 'file', 'image', 'max:10240'],
  'engineering_registry' => [Rule::requiredIf($type === 'office'), 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
  'tax_card'             => [Rule::requiredIf($type === 'office'), 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
];
```

- **منطق السيرفر (جوه DB transaction):**
  1. رفع الملفات (فولدر `verifications`، زي الكود الحالي) وبناء `documents`.
  2. `updateOrCreate(['user_id' => auth()->id()], [...الحقول, 'account_type' => $type, 'status' => 'pending', 'rejection_reason' => null, 'submitted_at' => now()])`.
  3. `users.current_step = max(current_step, 2)`، و`users.status` يفضل `pending` (أو يرجع `pending` لو كان `rejected`).
  4. `interaction_logs`: "تم إرسال بيانات التوثيق وبانتظار المراجعة" **(مقترح)**.
- **Response 201:**

```json
{
  "message": "تم إرسال البيانات بنجاح، بانتظار المراجعة",
  "user": { "id": "…", "user_type": "engineer", "current_step": 2, "status": "pending", "validation_status": "pending", "is_verified": false, "next_route": "/pricing/engineer" },
  "validation": { "status": "pending", "submitted_at": "2026-11-03T08:00:00Z" }
}
```

- **رسائل الأخطاء (422)** بنفس نصوص الفرونت: "يرجى ملء جميع الحقول ورفع صورة الكارنيه" (مهندس، `EngineerValidation.tsx:81-90`)، و"يرجى ملء جميع الحقول ورفع جميع المستندات المطلوبة" (مكتب).

##### (3) `PUT /api/v1/me/validation` — تعديل أو إعادة إرسال من الداشبورد
- **Auth:** Bearer، `user_type ∈ {engineer, office}`، ولازم يكون فيه صف توثيق موجود.
- **الحقول:** نفس جدول (2)، بس **كلها `sometimes`**. أي ملف ماتبعتش بيفضل المستند القديم من نفس النوع (زي سلوك `EditEngineerProfile.tsx:216-221` اللي بيحتفظ بالمستندات القديمة لو مفيش ملف جديد).
- **المنطق:** `status → pending`، و`rejection_reason → null`، و`submitted_at → now()`، و`users.status` يتعاد حسابه (2.V.3). **مايغيّرش `current_step`.**
- الشاشة الحالية في الداشبورد بتبعت `status: "pending"` من الفرونت (`EditEngineerProfile.tsx:228-242`). قسم التوثيق في صفحات التعديل **معلّق (commented out)** حالياً (القسم 3.4.1 و3.4.2)، ومحتاج يرجع عشان المستخدم المرفوض يقدر يبعت تاني.

##### (4) الأدمن (مكملين للقسم 4)
| Method | Path | Body | التأثير |
|---|---|---|---|
| GET | `/api/v1/admin/verifications` | query: `status=pending\|approved\|rejected\|all`، `search`، `page`، `per_page` | القائمة + `stats: { pending, approved, rejected }` (زي `getPendingVerifications`، `admin.api.ts:141-196`) |
| POST | `/api/v1/admin/verifications/{id}/approve` | `{}` | `status=approved`، `reviewed_at/by`، `rejection_reason=null`، `users.is_verified=true`، إعادة حساب `users.status`، `interaction_logs`، إيميل |
| POST | `/api/v1/admin/verifications/{id}/reject` | `{ "reason": "صورة البطاقة غير واضحة" }`، `reason: required\|string\|max:1000` | `status=rejected` + السبب، `users.status=rejected`، `is_verified=false`، `interaction_logs`، إيميل بالسبب |
| POST | `/api/v1/admin/verifications/{id}/reset` | `{}` | يرجّع `pending` (بديل اختيار "معلق" اللي بيرفض حالياً، `VerificationsTable.tsx:54-88`) |

- **تفعيل الاشتراك مايلمسش التوثيق:** حالياً `updateSubscriptionStatus` بيحط `user_validation.status = active` (`admin.api.ts:806-809`)، يعني تفعيل الاشتراك بيوثّق المستخدم كمان. في Laravel: الاشتراك والتوثيق كل واحد مستقل، و`users.status` يتعاد حسابه من الاتنين (2.V.3).

#### 2.V.6 تأثير الخطوة على باقي الـ Endpoints

| Endpoint | التعديل المطلوب |
|---|---|
| `GET /api/v1/auth/me` | يضيف `validation_required`، `validation_status` (`null` لو ماتبعتش)، `validation_rejection_reason`، `is_verified`، `next_route` |
| `POST /api/v1/auth/register` + رابط تأكيد الإيميل | بعد التأكيد يرجّع `next_route` = `/validation/{type}` للـ engineer/office. حالياً `emailRedirectTo` = `/pricing/{type}` (`signup.api.ts:42-45`) |
| `POST /api/v1/onboarding/plan`، `POST /api/v1/onboarding/payment`، `GET /pricing` | للـ engineer/office: لازم يكون فيه توثيق اتبعت (`current_step >= 2` وصف `user_validations` موجود). غير كده **409** `{ "code": "validation_required", "next_route": "/validation/{type}" }`. وفتح صفحة الأسعار **مايرفعش** الخطوة للنوعين دول |
| `POST /api/v1/onboarding/profile/*` | **مايلمسش `user_validations` خالص**. يتشال الـ upsert بتاع الـ placeholder (`profile.api.ts:105-119,230-241,315-326,400-411`). و`users.status` يتعاد حسابه بدل ما يتكتب `pending` |
| `PATCH /api/v1/admin/subscriptions/{id}/status` | مايغيّرش حالة التوثيق (2.V.5 (4)) |
| `GET /api/v1/engineers`، `GET /api/v1/offices` (+ `{slug}`) | يظهر بس `users.status = active` (يعني موثّق). ويرجع `is_verified` للكارت وصفحة التفاصيل |
| `GET /api/v1/admin/stats/overview` | عداد التوثيقات بالحالات الجديدة (`pending/approved/rejected`) |

#### 2.V.7 تعديلات الفرونت المطلوبة عشان الخطوة تشتغل (للفريق اللي هيربط الـ API)

1. `lib/step-redirect.ts:23-27`: `case 1` للـ `engineer`/`office` يرجّع `/validation/${userType}` (أو الأحسن الفرونت يستخدم `next_route` من `/auth/me`).
2. `features/auth/verify-email/components/VerifyEmail.tsx:100-110`: مايرفعش الخطوة لـ 2، ويوجّه حسب `next_route`. حالياً بيروح `/pricing/{type}` على طول، وده بيخلّي صفحة التوثيق تعمل redirect بسبب `step > 1` (`EngineerValidation.tsx:40-50`).
3. `features/auth/pricing/components/PricingLayout.tsx:33-60`: للـ engineer/office تقبل `step == 2` بس، ومتعملش `updateUserStep(2)`.
4. `middleware.ts`: يضيف `/validation/:path*` للـ matcher ويحميها (auth مطلوب). حالياً الصفحة مش محمية.
5. `EngineerValidation.tsx:109` و`OfficeValidation.tsx:119`: يشيلوا `status` و`user_id` من الـ payload.
6. `features/auth/profile/api/profile.api.ts`: يتشال الـ upsert بتاع الـ placeholder على `user_validation` في الدوال الأربعة.
7. `VerificationsTable.tsx:252-267`: الاختيار "معلق" ينادي `reset` بدل ما يفتح نافذة الرفض.
8. `EditEngineerProfile.tsx:428-584` و`EditOfficeProfile.tsx:632-…`: يرجع قسم التوثيق (معلّق حالياً) عشان التعديل وإعادة الإرسال بعد الرفض. ويظهر في الداشبورد banner بالحالة وسبب الرفض.

#### 2.V.8 قرارات مفتوحة (والقيمة الافتراضية المكتوبة فوق)

| # | القرار | الافتراضي في المواصفات دي | البديل |
|---|---|---|---|
| 1 | المستخدم يستنى موافقة الأدمن قبل ما يكمل للأسعار؟ | **لا** (non-blocking): يكمل، والحساب مايبقاش `active` غير بعد القبول | blocking: يفضل في صفحة "بانتظار المراجعة" لحد القبول |
| 2 | المعارض والموردين محتاجين توثيق؟ | **لا** | يتضافوا في config بمستندات (سجل تجاري مثلاً) |
| 3 | الرفض يخلّي `users.status = rejected`؟ | **أيوه** (زي الكود الحالي) | يفضل `pending` والرفض يبان في التوثيق بس |
| 4 | تعديل بيانات التوثيق بعد القبول يرجّعها للمراجعة؟ | **أيوه** للاسم والرقم والمستندات | التعديل يتقفل بعد القبول إلا عن طريق الأدمن |
| 5 | الصفوف الحالية اللي فيها placeholder | **تتحذف**، والمستخدمين دول يتطلب منهم التوثيق (banner في الداشبورد) | تتعلّم `approved` يدوياً من الأدمن |
| 6 | الحسابات الموجودة اللي `users.status = active` من غير توثيق حقيقي | تفضل `active` لحد قرار الأدمن، مع `is_verified = false` | تتحول `pending` لحد ما يبعتوا التوثيق |

---

### اختيار الباقة — `/pricing/[type]` — `features/auth/pricing/components/PricingLayout.tsx` + `PricingCard.tsx`

- **المستخدم:** أي حد (الصفحة **مش محمية** في الـ middleware). الـ route param `type` ∈ `engineer | office | exhibition | material` (`app/(auth)/pricing/[type]/page.tsx:11-18`)، وغير كده 404.
  - **Guard عند التحميل** (`PricingLayout.tsx:28-65`): لو فيه user و `current_step ∉ {1,2}` → `getStepRoute()`. وإلا يقرا `users.current_step` (`select("current_step").eq("id").maybeSingle()`)، ولو < 2 → `updateUserStep(2)`.
- **الإجراء عند الإرسال (زر الكارت):** `PricingCard` onClick (`:347-375`)، والزر disabled لحد ما الـ checkbox يتعلّم:
  - **لو السعر المعروض `=== 0` (باقة مجانية):** `useUpdateUserPlan().mutate({ plan_type, billing_cycle, user_type, confirmation: "free_plan" })` → `updateUserPlan()` (`pricing.api.ts:3-64`):
    1. `subscriptions.upsert({ user_id, plan_type, billing_cycle, user_type, confirmation: "free_plan", status: "pending" }, { onConflict: "user_id" })`.
    2. لو `current_step < 3` → `current_step = 3`.
    3. `users.update({ selected_plan, current_step? }).eq("id")` (الخطأ مش بيتفحص).
    4. `auth.updateUser({ data: { selected_plan, selected_billing_cycle, selected_user_type, current_step? } })`.
    5. بعدها `router.push("/complete-profile/{type}")`.
  - **غير كده (باقة مدفوعة):** مفيش API call، بس navigation لـ `/payment/{type}?plan={plan.id}&billing={billingCycle}`.
  - **جلب الأسعار:** `usePlanPrices(userType, billingCycle)` → `plans.select("plan_id:plan_type, price, original_price").eq("user_type", userType).eq("billing_cycle", billingCycle)` (`pricing.api.ts:66-75`). الكارت بيطابق `plan_id === plan.id`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `billing_cycle` | ربع سنوي / شهري / نصف سنوي / سنوي | segmented toggle | نعم (له default) | — | engineer: `quarter` (default)، `half`، `yearly`. باقي الأنواع: `monthly` (default)، `half`، `yearly` (`PricingLayout.tsx:67-71,101-149`) | `subscriptions.billing_cycle`، metadata `selected_billing_cycle` |
| `plan_type` (UI `plan.id`) | زر الكارت "اشترك الآن بالعرض" | button (اختيار الباقة) | نعم | — | `basic`، `professional`، `premium` | `subscriptions.plan_type`، `users.selected_plan`، metadata `selected_plan` |
| `user_type` | — (من route param `[type]`) | hidden | نعم | — | `engineer, office, exhibition, material` | `subscriptions.user_type`، metadata `selected_user_type` |
| `confirmation` = `"free_plan"` | — | hidden | للمجاني فقط | — | — | `subscriptions.confirmation` |
| *(derived)* `status` = `"pending"` | — | hidden | — | — | — | `subscriptions.status` |
| *(derived)* `current_step` = `3` | — | hidden | — | لو < 3 | — | `users.current_step`، metadata |
| `isAgreed` | أوافق على الشروط والأحكام وسياسة الاستخدام | checkbox | نعم (UI gate) | لازم يتعلّم عشان الزر يشتغل (`:329-345,372-375`) | — | **مش بيتبعت ومش بيتخزن** |

- **قوائم الاختيارات:** `PRICING_DATA` في `features/auth/pricing/api/pricing-static.ts:3-317`. **مفيش أسعار في الكود**: الحقول `price` و`originalPrice` مش متعرّفة لأي باقة. الأسعار كلها من جدول `plans` (`user_type`, `plan_type`, `billing_cycle`, `price`, `original_price`). معادلات الـ fallback في `PricingCard.tsx:224-246` (`yearly = price×12×0.8`، `half = price×6×0.9`، `quarter = price×3`) **dead code**. العملة "ج" (EGP). الـ suffix: `/3 أشهر`، `/شهر`، `/6 أشهر`، `/سنوياً`.

  كل الباقات الـ 12 ليها نفس الـ `badge`: "احصل على خصم 50% مدى الحياة — العرض حصري لأول 100 مشترك فقط!"، ونفس `buttonText`: "اشترك الآن بالعرض". الـ `professional` عليها `isFeatured` وبتظهر بـ label "موصى به". الأسماء: `basic` = الباقة الأساسية (green)، `professional` = الباقة الاحترافية (blue)، `premium` = الباقة المميزة (orange).

  **المميزات والحدود حسب نوع الحساب** (نص تسويقي معروض. التطبيق الفعلي للحدود عن طريق `user_permissions`):

  | النوع | `basic` | `professional` | `premium` |
  |---|---|---|---|
  | **engineer** (`:4-83`) | بروفايل مخصص؛ حتى **150** زائر/شهر؛ نتائج البحث العامة؛ عرض معلومات التواصل؛ **3** مشاريع؛ **خدمة واحدة**؛ الظهور في قائمة المهندسين؛ مهارات لا نهائية؛ روابط السوشيال | كل مميزات الأساسية + حتى **300** زائر/شهر؛ **7** مشاريع؛ **5** خدمات؛ البحث المتقدم؛ إحصائيات المكالمات؛ إحصائيات الواتساب؛ إحصائيات نقرات السوشيال؛ عرض عدد المفضلة | كل مميزات الاحترافية + ترشيح لعميل مميز شهرياً؛ زيارات غير محدودة؛ خدمات غير محدودة؛ مشاريع غير محدودة؛ أول نتائج البحث؛ أفضل المهندسين في الرئيسية؛ بانر في صفحة المهندسين؛ شارة مهندس مميز؛ فرصة ظهور على السوشيال |
  | **office** (`:84-164`) | بروفايل مكتب؛ حتى **500** زائر/شهر؛ البحث العام؛ معلومات التواصل؛ **5** مشروعات سابقة؛ **3** خدمات؛ قائمة المكاتب؛ روابط السوشيال | كل الأساسية + ترشيح لعميل مميز شهرياً؛ حتى **1000** زائر/شهر؛ **7** خدمات؛ **10** مشاريع؛ فيديو للأعمال؛ البحث المتقدم؛ إحصائيات المكالمات؛ إحصائيات الواتساب؛ إعلان مميز في صفحة المكاتب | كل الاحترافية + ترشيح لعميلين مميزين شهرياً؛ زيارات غير محدودة؛ مشروعات وخدمات غير محدودة؛ أعلى نتائج البحث؛ أفضل المكاتب في الرئيسية؛ ظهور المشاريع في الرئيسية؛ شارة مكتب مميز؛ الموقع على Google Maps؛ فرصة ظهور على السوشيال؛ إضافة pixel |
  | **exhibition** (`:165-240`) | بروفايل معرض؛ حتى **500** زائر/شهر؛ البحث العام؛ معلومات التواصل؛ حتى **10** منتجات بالصور؛ **3** فئات منتجات؛ قائمة المعارض؛ روابط السوشيال | كل الأساسية + حتى **20** منتج؛ فيديوهات ترويجية؛ البحث المتقدم؛ حتى **1000** زائر/شهر؛ إحصائيات الزيارات؛ إحصائيات المكالمات؛ إحصائيات الواتساب؛ إعلان مميز في قسم المعارض؛ حتى **7** فئات | كل الاحترافية + منتجات غير محدودة؛ أول نتائج البحث؛ زيارات غير محدودة؛ الظهور في الرئيسية؛ شارة معرض مميز؛ Google Maps؛ فئات غير محدودة؛ فرصة ظهور على السوشيال؛ Facebook pixel |
  | **material** (`:241-316`) | صفحة تاجر؛ حتى **100** زائر/شهر؛ البحث العام؛ معلومات التواصل؛ حتى **5** منتجات؛ **2** فئات؛ قائمة الموردين؛ روابط السوشيال | كل الأساسية + حتى **10** منتجات؛ فيديوهات ترويجية؛ البحث المتقدم؛ حتى **500** زائر/شهر؛ إحصائيات الزيارات؛ إحصائيات المكالمات؛ إحصائيات الواتساب؛ إعلان مميز في قسم مواد البناء؛ حتى **5** فئات | كل الاحترافية + منتجات غير محدودة؛ أول نتائج البحث؛ زيارات غير محدودة؛ الظهور في الرئيسية؛ شارة تاجر مميز؛ Google Maps؛ فئات غير محدودة؛ فرصة ظهور على السوشيال؛ Facebook pixel |

  الـ FAQs ثابتة في نفس الملف (3 للمهندس، 3 للمكتب، 2 للمعرض، 2 للمورد) وبيعرضها `FAQAccordion.tsx` من غير API.
- **Endpoints مقترحة (REST):**
  - `GET /api/v1/plans?user_type={engineer|office|exhibition}&billing_cycle={monthly|quarter|half|yearly}`، public → `[{ "plan_type": "basic", "price": 0, "original_price": 0 }, …]`. الواجهة بتستهلك `plan_id` (alias لـ `plan_type`) و`price` و`original_price`.
  - `POST /api/v1/onboarding/plan` (Bearer، verified). Body `{ "plan_type": "basic", "billing_cycle": "quarter", "user_type": "engineer" }`:

```php
'plan_type'     => 'required|in:basic,professional,premium',
'billing_cycle' => 'required|in:monthly,quarter,half,yearly',
'user_type'     => 'required|in:engineer,office,exhibition',   // material اتدمج في exhibition (0.5)
// السيرفر يحدد إن الباقة مجانية من plans.price == 0 بدل ما يعتمد على confirmation="free_plan" من الـ client
```

    **Response 200:** `UserResource` (`current_step = 3`، `selected_plan`، `selected_billing_cycle`، `selected_user_type`). لو الباقة مدفوعة → 422 `payment_required` (الواجهة تروح لصفحة الدفع).
  - (اختياري) `PATCH /api/v1/onboarding/step` بـ body `{ "step": 2 }` كبديل لـ `updateUserStep(2)` عند فتح الصفحة. **الأفضل** إن السيرفر يضبط step 2 عند التحقق من الإيميل ونلغي ده.
- **ملاحظات / Business rules:**
  - الباقة المجانية بتتسجل في `subscriptions` بـ `status = "pending"` و`confirmation = "free_plan"` (نفس شكل المدفوعة)، والتفعيل يتم يدوياً من الأدمن.
  - **تعارض قيم الباقات:** الواجهة و`plans` بيستخدموا `professional`، لكن enum `plan_type` في `users.selected_plan` حسب `supabase_signup_trigger.sql:53` و`t3meer_full_analysis.md:329` هو `free|basic|pro|premium`. يعني `users.update({selected_plan:"professional"})` ممكن يفشل بصمت. كود الأدمن بيتعامل مع القيمتين (`features/admin/api/admin.api.ts:288,435,538`). **لازم توحيد.**
  - الـ material بيستعلم `plans` بـ `user_type = "material"`. ⛔ في Laravel الصفوف دي بتتحذف والمورد بياخد باقات `exhibition` (0.5).

---

### تأكيد الاشتراك والدفع — `/payment/[type]` — `features/auth/payment/components/PaymentPage.tsx`

- **المستخدم:** مسجّل (محمي بالـ middleware). الـ `[type]` ∈ `engineer | exhibition | office | material` (`validProfileTypes`)، وغير كده 404 (`app/(auth)/payment/[type]/page.tsx:22-29`). مفيش guard على `current_step`.
  - **تحديد الباقة** (`:49-59`): `plan` = query `?plan=` أو metadata `selected_plan` أو `"basic"`. `billing` = query `?billing=` أو metadata `selected_billing_cycle` أو (engineer → `quarter`، غيره `monthly`). `effectiveUserType` = metadata `user_type` أو route `type`.
  - **السعر:** `getPlanFromDb(effectiveUserType, planId, billingCycle)` → `plans.select("*").eq("user_type").eq("plan_type").eq("billing_cycle").maybeSingle()` (`pricing.api.ts:77-91`). اسم الباقة من `PRICING_DATA`.
- **الإجراء عند الإرسال:** `handleSubmit` (`:135-181`) → `useUploadPayment().mutate({ file, plan_type, billing_cycle })` → `uploadPaymentConfirmation()` (`payment.api.ts:4-75`):
  1. `uploadToCloudinary(file, "payments")` → `secureUrl`.
  2. `userType` = metadata `selected_user_type` أو `user_type` أو `"engineer"`.
  3. `subscriptions.upsert({ user_id, plan_type, billing_cycle, user_type, confirmation: secureUrl, status: "pending" }, { onConflict: "user_id" })`.
  4. `auth.updateUser({ data: { selected_plan, selected_billing_cycle, current_step: max(current_step, 3) } })`.
  5. `users.update({ selected_plan, current_step: max(current_step, 3) }).eq("id")` (الخطأ مش بيتفحص).
  6. Meta events `AddPaymentInfo` و`Subscribe` بـ `{ content_name, value: price, currency: "EGP" }`، ثم SweetAlert "تم إرسال الطلب بنجاح / سيتم مراجعة التحويل وتفعيل الباقة خلال 24 ساعة"، ثم `router.push("/complete-profile/{type}")`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `file` (receipt) | رفع إيصال التحويل ("اضغط هنا لرفع الصورة", "JPG, PNG (Max 5MB)") | file | نعم | `accept="image/*"` (`:404`)؛ `size <= 5*1024*1024`: "حجم الصورة يجب أن لا يتجاوز 5 ميجابايت" (`:117-125`)؛ لو مفيش ملف: "يرجى رفع صورة التحويل للتأكيد" | — | Cloudinary `payments` → `subscriptions.confirmation` |
| `plan_type` | (من `?plan=`) | hidden | نعم | — | `basic, professional, premium` | `subscriptions.plan_type`، `users.selected_plan`، metadata |
| `billing_cycle` | (من `?billing=`) | hidden | نعم | — | `monthly, quarter, half, yearly` | `subscriptions.billing_cycle`، metadata `selected_billing_cycle` |
| *(derived)* `user_type` | — | hidden | — | metadata `selected_user_type` أو `user_type` | — | `subscriptions.user_type` |
| *(derived)* `status="pending"` | — | — | — | — | — | `subscriptions.status` |
| *(derived)* `current_step=max(step,3)` | — | — | — | — | — | `users.current_step`، metadata |

- **وسائل الدفع المعروضة (hardcoded، مفيش API):**
  - **InstaPay:** الرابط `process.env.NEXT_PUBLIC_INSTAPAY_LINK`، والافتراضي `https://www.instapay.com.eg` (`:254-258`). رقم الهاتف **`01034865483`** وعليه زر نسخ (`:294-297`).
  - **المحافظ الإلكترونية** (فودافون كاش / أورانج / اتصالات): الرابط `process.env.NEXT_PUBLIC_WALLET_LINK`، والافتراضي `https://www.vodafone.com.eg/ar/vodafonecash` (`:307-311`). رقم المحفظة **`01034865483`** (`:367-370`).
  - الإجمالي المعروض = `plans.price` (من الـ DB فقط).
- **Endpoint مقترح (REST):** `POST /api/v1/onboarding/payment` (Bearer، verified)، `multipart/form-data`:

```
plan_type=professional
billing_cycle=quarter
receipt=<file image/*, ≤ 5MB>         (أو receipt_url=https://res.cloudinary.com/…/payments/…)
```

```php
'plan_type'     => 'required|in:basic,professional,premium',
'billing_cycle' => 'required|in:monthly,quarter,half,yearly',
'receipt'       => 'required|file|image|max:5120',
```

  **Response 200:** `UserResource` (`current_step = 3`). الواجهة بتعمل `setQueryData(["authenticated_user"], user)` وبتعمل invalidate لـ `user-subscription` و`user_subscription` و`user_status` (`payment.query.ts:16-25`).
  - (اختياري) `GET /api/v1/plans/show?user_type=&plan_type=&billing_cycle=` → صف `plans` كامل (بديل `getPlanFromDb`).
  - (اختياري) `GET /api/v1/payment-methods` → `[{ "method": "instapay", "link": "…", "number": "01034865483" }, { "method": "wallet", … }]` بدل الـ hardcode.
  - `GET /api/v1/me/subscription` (Bearer) → صف `subscriptions` للمستخدم (بديل `getUserSubscription`، `payment.api.ts:77-94`).
- **ملاحظات / Business rules:**
  - **صف اشتراك واحد لكل مستخدم** (`onConflict: "user_id"`): أي رفع جديد بيعمل overwrite للصف القديم، حتى لو كان `active`.
  - السعر **مش بيتخزن** في `subscriptions` وقت الطلب (العمود `plan_price` موجود حسب `t3meer_full_analysis.md:510-521` ومش بيتكتب هنا). ومفيش كتابة في `subscription_payments` خالص من الـ onboarding. الجدول ده بيتقري بس في `features/admin/api/admin.api.ts:828-831` (`subscription_id, user_id, amount, plan_type, billing_cycle, status, created_at`)، وطريقة ملئه مش ظاهرة في الريبو (غالباً trigger أو موافقة الأدمن). **مقترح Laravel:** عند الطلب خزّن snapshot لـ `plan_price` من `plans` وأنشئ صف `subscription_payments` بـ `status=pending` و`amount` و`receipt_url`. الأدمن يعتمد فتتحول `subscriptions.status` لـ `active` ويتضبط `starts_at` و`expires_at`.
  - الحالة `pending` لحد موافقة الأدمن، لكن **`current_step` بيتحرك لـ 3 فوراً**، يعني المستخدم يكمل ملفه قبل الاعتماد.
  - `selected_user_type` بيتكتب في الـ metadata بس في مسار الباقة المجانية، فهنا غالباً بيتاخد `user_type`.

---

### إكمال الملف — مهندس — `/complete-profile/engineer` — `features/auth/profile/components/EngineerProfileForm.tsx`

- **المستخدم:** مهندس مسجّل (middleware: لازم مسجّل، ولو step ≥ 4 → dashboard). الصفحة نفسها (`CompleteProfilePage.tsx`) مفيهاش guard إضافي. `/complete-profile/customer` بيعمل redirect لـ `/customer/setup` (`app/(auth)/complete-profile/[type]/page.tsx:36-38`).
- **الإجراء عند الإرسال:** `handleSubmit` (`:109-158`):
  1. لو فيه صورة: `uploadToCloudinary(profileFile, "engineers/profiles")`.
  2. `useSaveEngineerProfile().mutateAsync(data)` (`profile.query.ts:12-32`) → `saveEngineerProfile()` (`profile.api.ts:3-122`):
     - `engineers.upsert({ user_id, name, phone, major, experience_years, governorate, city, bio, whats_app, website, workingHours, social, skills, latitude, longitude, profile_image, coverImage, videoUrl }, { onConflict: "user_id" })`. الحقول `phone, city, website, workingHours, social, coverImage, videoUrl` = `undefined` من الفورم، فبتتشال من الـ JSON ومش بتتكتب.
     - `users.update({ name, phone? }).eq("id")`، و`phone` مش بيتبعت فمش بيتحدث.
     - `services.delete().eq("user_id")` ثم `services.insert([{ user_id, name, icon, color, slug }])` لكل تخصص.
     - `users.update({ status: "pending", current_step: 4 /* لو < 4 */ })` + `auth.updateUser({ data: same })`.
     - `user_validation.upsert({ user_id, name, account_type: "engineer", professional_id: <random 100000–999999>, documents: ["https://placehold.co/600x400"], details: { graduation_year: "2020", specialization: "هندسة معمارية" }, status: "pending" }, { onConflict: "user_id" })` (`:105-119`).
  3. ثم `updateUserStep(4)` (`profile.query.ts:20`): `users.current_step = 4` + metadata.
  4. Meta custom event `ProfileCompleted`، ثم "تم إكمال الملف الشخصي بنجاح!"، ثم `/dashboard/engineer`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `profile_image` | أضف صورة شخصية احترافية | file | لا | `accept=".jpg,.jpeg,.png,.webp"` (`:190-196`)، ملف واحد، مفيش حد للحجم | — | Cloudinary `engineers/profiles` → `engineers.profile_image` (null لو مفيش) |
| `major` | MAJOR \| التخصص الأساسي * | select | نعم | من القائمة | `engineerMajors`: `معماري` (label "عمارة")، `مدني` (label "مدني") (`profile.static.ts:49-52`) | `engineers.major` |
| `experience_years` (UI `experience`) | EXPERIENCE \| سنوات الخبرة * | text numeric | نعم | `toEnglishDigits` + حذف أي حرف غير رقم (`:243-248`)، بعدها `parseInt(...) \|\| 0`، مفيش range | — | `engineers.experience_years` (int) |
| `governorate` | GOVERNORATE \| المحافظة * | select | نعم | من القائمة | 27 محافظة (§2.0.5) | `engineers.governorate` |
| `whats_app` (UI `whatsApp`) | WHATSAPP \| رقم الواتساب | tel numeric | لا | أرقام فقط بعد `toEnglishDigits` (`:294-299`)، **مفيش تحقق طول أو صيغة** (placeholder `01XXXXXXXXX`) | — | `engineers.whats_app` ("" لو فاضي) |
| `bio` | BIO \| نبذة تعريفية | textarea | لا | `maxLength={500}` (`:324`) + `trim()` | — | `engineers.bio` |
| `specializations` | SERVICES \| التخصصات والخدمات * | multiselect | نعم (≥ 1) | `max = maxServices` حسب الباقة (انظر الملاحظات). الرسالة: "باقتك {X} تسمح بـ {N} تخصص فقط.قم بالترقية لإضافة المزيد." (`:77-84`) | `engineerSpecializations` (16، تحت) | جدول **`services`**: صف لكل اختيار `{ user_id, name: <label عربي>, icon, color, slug: users.slug }`. الـ `value` الإنجليزي **مش بيتخزن** |
| `skills` | SKILLS \| المهارات والبرامج | tag editor (`TagEditor`) | لا | `trim()`، مش فاضي، من غير تكرار (`ProfileFormHelpers.tsx:168-174`)، مفيش حد أقصى | free text (مثال AutoCAD, Revit) | `engineers.skills` (text[]) |
| `latitude` | LOCATION \| الموقع الجغرافي: خط العرض (Lat) | map-picker (زر "تحديد موقعي تلقائياً" = geolocation) + text `inputMode=decimal` | لا | `toEnglishDigits` ثم `parseFloat`، ولو فاضي `null` | — | `engineers.latitude` |
| `longitude` | … خط الطول (Lng) | نفس الشيء | لا | نفس الشيء | — | `engineers.longitude` |
| *(derived)* `user_id` | — | hidden | — | — | — | `engineers.user_id` (unique) |
| *(derived)* `name` | — | hidden | — | metadata `name` أو `"بدون اسم"` | — | `engineers.name`، `users.name` |
| *(derived)* `slug` | — | hidden | — | metadata `slug` | — | `services.slug` فقط (**مش** `engineers.slug`) |
| *(derived)* `status="pending"`, `current_step=4` | — | — | — | — | — | `users.*`، metadata |

  زر الحفظ disabled لحد `major && experience && governorate && specializations.length > 0` (`:98-99`).
- **قوائم الاختيارات:** `engineerSpecializations` (`profile.static.ts:54-116`)، بصيغة `value | label (المخزّن في services.name) | icon | color`:
  `architectural-design | تصميم معماري | material-symbols:architecture | #38bdf8`؛ `quantity-surveying | حصر كميات | mdi:calculator | #34d399`؛ `shop-drawings | رسومات تنفيذية | ph:house-line | #f59e0b`؛ `project-management | إدارة مشاريع | mdi:eye-outline | #22c55e`؛ `interior-design | تصميم داخلي | solar:sofa-2-bold | #a78bfa`؛ `finishing | تشطيبات | solar:paint-roller-bold | #e879f9`؛ `engineering-consulting | استشارات هندسية | solar:chat-round-call-bold | #fb923c`؛ `building-permits | تراخيص بناء | solar:document-bold | #4ade80`؛ `surveying | رفع مساحة | solar:map-point-bold | #fb923c`؛ `legalization | تصالح للبناء | solar:check-read-bold | #f59e0b`؛ `structural-design | تصميم انشائي | mdi:pillar | #a78bfa`؛ `facade-design | تصميم واجهات | solar:window-frame-bold | #38bdf8`؛ `insulation-works | اعمال عزل | solar:shield-bold | #64748b`؛ `concrete-works | اعمال خرسانات | material-symbols:wall-art-rounded | #94a3b8`؛ `restoration-works | اعمال ترميم | solar:restart-bold | #f43f5e`؛ `syndicate-services | خدمات نقابية | solar:users-group-rounded-bold | #0070d7`.
  أي value مش موجود في القائمة: icon `solar:star-bold` وcolor `#0070d7` (`:86-94`).
- **Endpoint مقترح (REST):** `POST /api/v1/onboarding/profile/engineer` (Bearer، verified، `current_step == 3`)، `multipart/form-data`:

```
profile_image=<file jpg|jpeg|png|webp>      (أو profile_image_url=…)
major=مدني
experience_years=7
governorate=القاهرة
whats_app=01012345678
bio=مهندس مدني بخبرة 7 سنوات…
specializations[]=structural-design
specializations[]=shop-drawings
skills[]=AutoCAD
skills[]=Revit
latitude=30.0444
longitude=31.2357
```

```php
'profile_image'     => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',   // max: مقترح
'major'             => 'required|in:معماري,مدني',
'experience_years'  => 'required|integer|min:0',
'governorate'       => ['required', Rule::in(config('t3meer.governorates'))],
'whats_app'         => 'nullable|regex:/^[0-9]+$/',                      // مقترح أقوى: /^01[0125][0-9]{8}$/
'bio'               => 'nullable|string|max:500',
'specializations'   => 'required|array|min:1|max:'.$maxServices,
'specializations.*' => ['distinct', Rule::in(array_keys(config('t3meer.engineer_specializations')))],
'skills'            => 'nullable|array',
'skills.*'          => 'string|distinct|max:100',
'latitude'          => 'nullable|numeric|between:-90,90',
'longitude'         => 'nullable|numeric|between:-180,180',
```

  السيرفر يحوّل `specializations[]` لصفوف `services` (`name`, `icon`, `color`) من config، بنفس منطق delete ثم insert. **Response 200:** `UserResource` (`current_step = 4`، `status = pending`).
- **ملاحظات / Business rules:**
  - **حد الخدمات** (`:59-71`): المصدر `user_permissions.max_services` لـ `user_id` (`features/dashboard/dashboard.api.ts:53-61`)، و`-1` أو `≥ 99999` = غير محدود. لو مش موجود: fallback حسب `user_permissions.plan_type` أو metadata `selected_plan` (الافتراضي `basic`): `premium` و`free` → ∞، `professional` → 5، `basic` → 1.
  - **الـ placeholder في `user_validation`** بيعمل overwrite لأي بيانات توثيق حقيقية، ورقم القيد عشوائي. ده واضح إنه workaround، ولازم قرار من الـ backend.
  - `users.status` بيرجع `pending` حتى لو التوثيق كان حطّه `active`.
  - أخطاء `services.delete` وتحديثات `users` مش بتتفحص.

---

### إكمال الملف — مكتب هندسي — `/complete-profile/office` — `features/auth/profile/components/OfficeProfileForm.tsx`

- **المستخدم:** مكتب مسجّل، بنفس guards المهندس.
- **الإجراء عند الإرسال:** `handleSubmit` (`:104-155`): `uploadToCloudinary(profileFile, "offices/profiles")`، ثم `useSaveOfficeProfile` → `saveOfficeProfile()` (`profile.api.ts:143-244`):
  - `offices.upsert({ user_id, name, governorate, bio, whats_app, experience_years, count_of_projects, open_hours, latitude, longitude, profile_image }, { onConflict: "user_id" })`.
  - `users.update({ name }).eq("id")`.
  - `services` delete ثم insert (`{ user_id, name, icon, color, slug }`).
  - `users` و metadata: `{ status: "pending", current_step: 4 }`.
  - `user_validation.upsert({ user_id, name, account_type: "office", professional_id: <random 6 digits>, tax_number: <random 6 digits>, documents: ["https://placehold.co/600x400","https://placehold.co/600x400"], status: "pending" })` (`:230-241`).
  - ثم `updateUserStep(4)`، ثم `/dashboard/office`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `profile_image` (UI logo) | أضف لوجو المكتب | file | لا | `accept=".jpg,.jpeg,.png,.webp"` (`:186-192`) | — | Cloudinary `offices/profiles` → `offices.profile_image` |
| `whats_app` | WHATSAPP \| رقم الواتساب | tel numeric | لا | أرقام فقط (`:215-220`)، مفيش طول | — | `offices.whats_app` |
| `bio` | BIO \| نبذة عن المكتب | textarea | لا | `maxLength=500` + `trim()` | — | `offices.bio` |
| `governorate` | GOVERNORATE \| المحافظة * | select | نعم | من القائمة | 27 محافظة | `offices.governorate` |
| `experience_years` | EXPERIENCE \| سنوات الخبرة * | text numeric | نعم | أرقام فقط، `parseInt`، ولو فاضي 0 | — | `offices.experience_years` |
| `count_of_projects` (UI `projectsCount`) | PROJECTS \| عدد المشاريع المنجزة | text numeric | لا | أرقام فقط، ولو فاضي 0 (`:135-137`) | — | `offices.count_of_projects` |
| `open_hours` (UI `openHours`) | HOURS \| مواعيد العمل | text | لا | مفيش (placeholder "مثال: 9 صباحاً - 5 مساءً") | — | `offices.open_hours` |
| `services` | SERVICES \| الخدمات المقدمة * | multiselect | نعم (≥ 1) | `max = maxServices` ("باقتك {X} تسمح بـ {N} خدمة فقط. قم بالترقية لإضافة المزيد.") | **`engineerSpecializations`** (نفس الـ 16 بتاعة المهندس، `:375,85`) | جدول `services` (`name`=label، `icon`، `color`، `slug`) |
| `latitude` / `longitude` | LOCATION \| الموقع الجغرافي (زر "تحديد موقع المكتب تلقائياً" + Lat/Lng) | map-picker + text | لا | `toEnglishDigits` ثم `parseFloat`، ولو فاضي `null` | — | `offices.latitude`، `offices.longitude` |
| *(derived)* `user_id`, `name` (metadata `name` أو "بدون اسم") | — | hidden | — | — | — | `offices.user_id`، `offices.name`، `users.name` |
| *(derived)* `slug` | — | hidden | — | — | — | `services.slug` فقط |

  زر الحفظ disabled لحد `experience && governorate && services.length > 0` (`:71`).
- **قوائم الاختيارات:** الخدمات من `engineerSpecializations` (انظر شاشة المهندس). `officeServices` (8 قيم، `profile.static.ts:259-308`) **imported لكن مش مستخدم**. للعلم قيمه: `architectural-design|تصميم معماري`، `building-permits|تراخيص بناء`، `surveying|رفع مساحة`، `engineering-drawing|رسم هندسي`، `legalization|تصالح للبناء`، `structural-design|تصميم انشائي`، `supervision|اشراف علي التنفيذ`، `syndicate-services|خدمات نقابية`.
- **Endpoint مقترح (REST):** `POST /api/v1/onboarding/profile/office` (Bearer، verified)، `multipart/form-data`:

```
profile_image=<file>     whats_app=01112345678     bio=…
governorate=الجيزة       experience_years=12        count_of_projects=50
open_hours=9 صباحاً - 5 مساءً
services[]=architectural-design   services[]=building-permits
latitude=30.01   longitude=31.21
```

```php
'profile_image'     => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
'whats_app'         => 'nullable|regex:/^[0-9]+$/',
'bio'               => 'nullable|string|max:500',
'governorate'       => ['required', Rule::in(config('t3meer.governorates'))],
'experience_years'  => 'required|integer|min:0',
'count_of_projects' => 'nullable|integer|min:0',
'open_hours'        => 'nullable|string|max:255',
'services'          => 'required|array|min:1|max:'.$maxServices,
'services.*'        => ['distinct', Rule::in(array_keys(config('t3meer.engineer_specializations')))],
'latitude'          => 'nullable|numeric|between:-90,90',
'longitude'         => 'nullable|numeric|between:-180,180',
```

  **Response 200:** `UserResource`.
- **ملاحظات:**
  - حد الخدمات الـ fallback (`:57-69`): `premium` و`free` → ∞، `professional` → 7، `basic` → 3.
  - `saveOfficeProfile` بيقبل `city`، `phone`، `website`، `team_size`، `specializations` في الـ signature، لكن الفورم **مش بيجمعهم** والدالة **مش بتكتبهم** في `offices`.
  - placeholder `user_validation` زي المهندس.

---

### إكمال الملف — معرض — `/complete-profile/exhibition` — `features/auth/profile/components/ExhibitionProfileForm.tsx`

- **المستخدم:** حساب `exhibition` ("معارض وموردين" في التسجيل). **الفورم ده هو نفسه بتاع المعارض والموردين في Laravel** (0.5)، وقائمة الخدمات الموحدة المقترحة موجودة في 0.5.
- **الإجراء عند الإرسال:** `handleSubmit` (`:134-196`):
  1. `uploadToCloudinary(profileFile, "exhibitions/profiles")`.
  2. يبني `services` من التصنيفات الفرعية المختارة: `{ value: "sub-{name}", name, icon: "solar:double-alt-arrow-right-bold", color: "#FC7753" }` (`:150-159`).
  3. `main_category` = الـ label العربي للتصنيف (`:161-164`).
  4. `saveExhibitionProfile()` (`profile.api.ts:246-329`):
     - `exhibitions.upsert({ user_id, name, governorate, city, bio, whats_app, experience_years, latitude, longitude, logo, count_of_categories, open_hours, main_category }, { onConflict: "user_id" })`.
     - `services` delete ثم insert.
     - `users` و metadata: `{ status: "pending", current_step: 4 }`.
     - `user_validation.upsert({ …, account_type: "exhibition", professional_id: 123456, tax_number: 123456, documents: [placeholder×2], status: "pending" })`.
     - **مفيش** تحديث لـ `users.name`.
  5. `updateUserStep(4)`، ثم `/dashboard/exhibition`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `logo` | أضف لوجو المعرض | file | لا | `accept=".jpg,.jpeg,.png,.webp"` (`:238-244`) | — | Cloudinary `exhibitions/profiles` → **`exhibitions.logo`** |
| `bio` | BIO \| وصف المعرض | textarea | لا | `maxLength=500` + `trim()` | — | `exhibitions.bio` |
| `whats_app` | WHATSAPP \| رقم الواتساب | tel numeric | **لا** | أرقام فقط (`:293-298`) | — | `exhibitions.whats_app` |
| `governorate` | GOVERNORATE \| المحافظة * | select | نعم | من القائمة | 27 محافظة | `exhibitions.governorate` |
| `experience_years` | EXPERIENCE \| سنوات الخبرة | text numeric | لا | أرقام فقط، ولو فاضي 0 | — | `exhibitions.experience_years` |
| `main_category` | MAIN CATEGORY \| التصنيف الرئيسي * | select | نعم | من القائمة. تغييره بيمسح التصنيفات الفرعية (`:55-59`) | `exhibitionCategories` = `showroomSupplierCategories` (23 قيمة، تحت) | `exhibitions.main_category` (النص العربي) |
| `sub_categories` (UI `selectedSubCategories`) | SUB CATEGORIES \| التصنيفات الفرعية | multiselect (يظهر بعد اختيار الرئيسي) | لا | `max = maxServices`: "باقتك {X} تسمح بـ {N} تصنيفات فرعية فقط. قم بالترقية لإضافة المزيد." (`:91-100`) | `showroomSupplierSubCategories[main_category]` + أي custom مضاف | جدول `services`: `{ name: <sub>, icon: "solar:double-alt-arrow-right-bold", color: "#FC7753", slug }` |
| `customSubCategory` | أضف تصنيف فرعي مخصص (إذا لم يكن في القائمة) + زر "إضافة" | text + button | لا | `trim()`، مش فاضي، ونفس حد الباقة (`:102-122`) | free text | يتضاف لـ `sub_categories` ← `services.name` |
| `count_of_categories` | CATEGORIES COUNT \| عدد الأقسام | text numeric | لا | أرقام فقط، ولو فاضي 0 | — | `exhibitions.count_of_categories` |
| `open_hours` | OPENING HOURS \| مواعيد العمل | text | لا | مفيش (placeholder "مثال: 10 ص - 10 م") | — | `exhibitions.open_hours` |
| `latitude` / `longitude` | LOCATION \| الموقع الجغرافي ("تحديد موقع المعرض تلقائياً") | map-picker + text | لا | `parseFloat`، ولو فاضي `null` | — | `exhibitions.latitude`، `exhibitions.longitude` |
| *(derived)* `user_id`, `name` (metadata `name` أو "بدون اسم") | — | hidden | — | — | — | `exhibitions.user_id`، `exhibitions.name` |
| *(derived)* `slug` | — | hidden | — | — | — | `services.slug` فقط |

  زر الحفظ disabled لحد `governorate && mainCategory` (`:124`). يعني التصنيفات الفرعية اختيارية وممكن ما يتسجلش أي `services`.
- **قوائم الاختيارات:**
  - **`showroomSupplierCategories`** (`profile.static.ts:123-149`): التعليق بيقول "22" لكن القائمة فيها **23**. الـ value = الـ label:
    - موردو مواد البناء: `مواد البناء الأساسية`، `الحديد والمعادن`، `الخرسانة والإنشاءات`، `العزل`، `السباكة والمواسير`، `الكهرباء`، `الأخشاب`، `الجبس والأسقف`، `مواد الترميم والكيماويات`، `العدد والمعدات`.
    - معارض التشطيبات: `الأثاث والفرش`، `السيراميك والبورسلين`، `الأدوات الصحية`، `الإضاءة`، `الدهانات والديكور`، `الأبواب والشبابيك`، `الرخام والجرانيت`، `الواجهات الخارجية`، `اللاندسكيب`، `المطابخ والدريسنج`، `التكييف والتهوية`، `الطاقة الشمسية`، `الأمن والسلامة`.
  - **`showroomSupplierSubCategories`** (`profile.static.ts:151-200`). الـ key = التصنيف الرئيسي:
    - مواد البناء الأساسية: أسمنت، طوب، رمل وسن، جبس، خرسانة جاهزة
    - الحديد والمعادن: حديد تسليح، صاج وملفوفات، زوايا وكمرات، أسلاك شبك
    - الخرسانة والإنشاءات: إضافات خرسانة، بلوك خرساني، منتجات أسمنتية
    - العزل: عزل مائي، عزل حراري، عزل صوتي، لفائف بتومين
    - السباكة والمواسير: مواسير مياه، مواسير صرف، خزانات مياه، مضخات مياه، محابس ووصلات
    - الكهرباء: أسلاك وكابلات، لوحات توزيع، قواطع كهربائية، خراطيم وتمديدات
    - الأخشاب: خشب زان، خشب سويدي، أبلاكاش، خشب MDF
    - الجبس والأسقف: ألواح جبس بورد، إكسسوارات تعليق، كرانيش جبس
    - مواد الترميم والكيماويات: أديبوند وجراوت، مونة ترميم، مواد معالجة خرسانة
    - العدد والمعدات: عدد يدوية، معدات كهربائية، سقالات، أدوات قياس
    - الأثاث والفرش: غرف نوم، غرف معيشة وصالونات، سجاد وموكيت، ستائر ومفروشات، أثاث مكتبي، أثاث خارجي
    - السيراميك والبورسلين: سيراميك أرضيات، سيراميك حوائط، بورسلين، وزرات وديكورات
    - الأدوات الصحية: أطقم حمامات، خلاطات مياه، أحواض مطابخ، كابينات شاور، جاكوزي
    - الإضاءة: نجف وثريات، إضاءة ليد (LED)، كشافات وسبوتات، إضاءة خارجية، أباجورات وإضاءة طاولة
    - الدهانات والديكور: دهانات داخلية، دهانات خارجية، معجون وبرايمر، ورق حائط، بديل خشب ورخام
    - الأبواب والشبابيك: أبواب خشبية، أبواب مصفحة، شبابيك ألومنيوم (ألوتال)، شبابيك PVC، بوابات حديد
    - الرخام والجرانيت: رخام طبيعي، جرانيت طبيعي، رخام صناعي، درج ومطابخ رخام
    - الواجهات الخارجية: حجر طبيعي، حجر صناعي، جي آر سي (GRC)، كلادينج
    - اللاندسكيب: نجيل صناعي، إنترلوك وبردورات، مظلات وبرجولات، نباتات زينة
    - المطابخ والدريسنج: مطابخ ألومنيوم، مطابخ خشبية، دريسنج روم، إكسسوارات مطابخ
    - التكييف والتهوية: تكييفات إسبليت، تكييفات مركزي، مراوح تهوية، مواسير نحاس وعزل
    - الطاقة الشمسية: ألواح شمسية، سخانات شمسية، بطاريات ومحولات
    - الأمن والسلامة: كاميرات مراقبة، أنظمة إنذار حريق، أجهزة حضور وانصراف، طفايات حريق
    - فيه كمان keys إنجليزية legacy لنفس القوائم (`basic-construction`، `iron-metals`، `concrete-construction`، `insulation`، `plumbing-pipes`، `electrical`، `wood`، `gypsum-ceilings`، `restoration-chemicals`، `tools-equipment`، `furniture-furnishings`، `ceramics-porcelain`، `sanitary-ware`، `lighting`، `paints-decor`، `doors-windows`، `marble-granite`، `exterior-facades`، `landscape`، `kitchens-dressing`، `hvac`، `solar-energy`، `security-safety`) (`:176-199`).
  - `exhibitionServices` (8 قيم: `delivery-shipping|توصيل وشحن`، `installation|تركيب`، `design-consulting|استشارات تصميم`، `custom-manufacturing|تصنيع حسب الطلب`، `product-warranty|ضمان على المنتجات`، `wholesale|بيع بالجملة`، `inspection-measurement|معاينة وقياسات`، `after-sales-service|خدمة ما بعد البيع`) **imported ومش مستخدم في الفورم**. مستخدم في الـ dashboard بس.
- **Endpoint مقترح (REST):** `POST /api/v1/onboarding/profile/exhibition` (Bearer، verified)، `multipart/form-data`:

```
logo=<file>      bio=…      whats_app=01212345678
governorate=القاهرة      experience_years=10
main_category=السيراميك والبورسلين
sub_categories[]=سيراميك أرضيات   sub_categories[]=بورسلين   sub_categories[]=قسم مخصص جديد
count_of_categories=5      open_hours=10 ص - 10 م
latitude=30.05   longitude=31.24
```

```php
'logo'                => 'nullable|file|mimes:jpg,jpeg,png,webp|max:5120',
'bio'                 => 'nullable|string|max:500',
'whats_app'           => 'nullable|regex:/^[0-9]+$/',
'governorate'         => ['required', Rule::in(config('t3meer.governorates'))],
'experience_years'    => 'nullable|integer|min:0',
'main_category'       => ['required', Rule::in(config('t3meer.showroom_supplier_categories'))],
'sub_categories'      => 'nullable|array|max:'.$maxServices,
'sub_categories.*'    => 'string|distinct|max:255',     // free text مسموح (custom)
'count_of_categories' => 'nullable|integer|min:0',
'open_hours'          => 'nullable|string|max:255',
'latitude'            => 'nullable|numeric|between:-90,90',
'longitude'           => 'nullable|numeric|between:-180,180',
```

  **Response 200:** `UserResource`.
- **ملاحظات:**
  - حد التصنيفات الفرعية (`:67-89`): `user_permissions.max_services`، أو fallback `SERVICE_LIMITS = { free: ∞, basic: 3, professional: 7, premium: ∞ }`، والافتراضي 3.
  - عمود الصورة هنا اسمه `logo`، مش `profile_image` زي باقي الأنواع.
  - `city` بيتبعت `undefined` (مش متجمّع).

---

### إكمال الملف — مورد مواد بناء — `/complete-profile/material` — `features/auth/profile/components/MaterialProfileForm.tsx`

> ⛔ **مش هيتعمل في Laravel:** المورد = `exhibition` (القسم 0.5). الوصف تحت للكود الحالي بس (مرجع لنقل البيانات).

- **المستخدم:** نظرياً `user_type = material`. **فعلياً مفيش طريقة تسجيل بتنتج النوع ده:** RegisterForm مش بيعرضه، والـ trigger بيحوّل أي قيمة غير معروفة لـ `engineer`. الوصول ليه بالـ URL المباشر بس. انظر الأسئلة المفتوحة.
- **الإجراء عند الإرسال:** `handleSubmit` (`:133-189`): `uploadToCloudinary(profileFile, "materials/profiles")`، ثم نفس بناء `services` والـ `main_category` بتوع المعرض، ثم `saveMaterialProfile()` (`profile.api.ts:331-414`):
  - `materials.upsert({ user_id, name, governorate, city, bio, whats_app, experience_years, latitude, longitude, profile_image, count_of_categories, open_hours, main_category }, { onConflict: "user_id" })`. الفورم **مش بيبعت** `count_of_categories` ولا `open_hours` ولا `city`.
  - `services` delete ثم insert، و`users` + metadata `{ status: "pending", current_step: 4 }`.
  - `user_validation` placeholder بـ `account_type: "material"`.
  - `updateUserStep(4)`، ثم `/dashboard/material`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `profile_image` (UI logo) | أضف لوجو المتجر | file | لا | `accept=".jpg,.jpeg,.png,.webp"` (`:237`) | — | Cloudinary `materials/profiles` → `materials.profile_image` |
| `bio` | BIO \| وصف المتجر | textarea | لا | `maxLength=500` (`:263`) + `trim()` | — | `materials.bio` |
| `whats_app` | WHATSAPP \| رقم الواتساب * | tel numeric | **نعم** | أرقام فقط (`:289`)، ومطلوب في `isFormValid` (`:123`) | — | `materials.whats_app` |
| `governorate` | GOVERNORATE \| المحافظة * | select | نعم | — | 27 محافظة (`:319`) | `materials.governorate` |
| `experience_years` | EXPERIENCE \| سنوات الخبرة | text numeric | لا | أرقام فقط، ولو فاضي 0 (`:339`) | — | `materials.experience_years` |
| `main_category` | MAIN CATEGORY \| التصنيف الرئيسي للمنتجات * | select | نعم | — | `materialCategories` = نفس الـ 23 (`:369`) | `materials.main_category` |
| `sub_categories` | SUB CATEGORIES \| التصنيفات الفرعية للمنتجات | multiselect | لا | `max = maxServices` (نفس `SERVICE_LIMITS`: basic 3، professional 7) | `showroomSupplierSubCategories[main]` + custom (`:391`) | `services` (icon `solar:double-alt-arrow-right-bold`، color `#FC7753`) |
| `customSubCategory` | أضف تصنيف فرعي مخصص + "إضافة" | text + button | لا | `trim()`، وحد الباقة (`:424`) | free text | `services.name` |
| `latitude` / `longitude` | LOCATION \| موقع المخزن / المعرض | map-picker + text | لا | `parseFloat`، ولو فاضي `null` (`:473,481`) | — | `materials.latitude`، `materials.longitude` |
| *(derived)* `user_id`, `name`, `slug` | — | hidden | — | — | — | `materials.user_id`، `materials.name`، `services.slug` |

  زر الحفظ disabled لحد `governorate && whatsApp && mainCategory` (`:123`).
- **قوائم الاختيارات:** نفس قوائم المعرض. `materialServices` (8 قيم: `site-delivery|توصيل للموقع`، `wholesale-prices|أسعار جملة`، `payment-plans|تسهيلات سداد`، `free-samples|عينات مجانية`، `technical-support|دعم فني`، `quality-certificates|شهادات جودة`، `return-policy|سياسة إرجاع`، `supply-contracts|عقود توريد`) و`deliveryRanges` (`local|داخل المدينة فقط`، `governorate|داخل المحافظة`، `nearby|المحافظات المجاورة`، `national|جميع أنحاء الجمهورية`، `none|لا يوجد توصيل (استلام من المقر)`) **imported ومش معروضين ومش بيتخزنوا** (`MaterialProfileForm.tsx:9,11`).
- **Endpoint مقترح (REST):** ⛔ مفيش endpoint منفصل. المورد بيستخدم `POST /api/v1/onboarding/profile/exhibition` (فورم المعرض فوق). الفروق القديمة (`whats_app` إلزامي، `profile_image` بدل `logo`) مرجع بس لنقل البيانات (0.5).
- **ملاحظات:** `t3meer_full_analysis.md:407-410` بيقول إن جدول `materials` اتدمج في `exhibitions`، لكن الكود لسه بيكتب في `materials`. محتاج قرار.

---

### إعداد حساب العميل — `/customer/setup` — `features/customer/components/CustomerSetup.tsx`

- **المستخدم:** customer بعد التسجيل أو التفعيل (step ≤ 1). **الصفحة مستثناة من حماية الـ middleware** (`middleware.ts:87`) ومفيهاش guard على النوع أو الـ step. يعني زائر غير مسجّل يقدر يبعت (`user_id = null`)، وعميل مكمّل يقدر يبعت تاني.
- **الإجراء عند الإرسال:** `handleSubmit` (`:99-199`):
  1. Validation لو `need_service` (`:82-96`).
  2. `createCustomerRequest()` (`customer.api.ts:8-34`) → `customers.insert({ user_id, full_name, phone, email, customer_type, governorate, service, notes, status }).select().single()`. للـ guest: `governorate`, `service` = null (بيتفرض في الـ API)، و`status = "active"`. للـ `need_service`: `status = "pending"`.
  3. `updateUserStep(2)` (`users.current_step = 2` + metadata)، والخطأ بيتسجل بس.
  4. `users.update({ current_step: 2, status: "active" }).eq("id", userId)`. الـ **metadata `status` مش بيتحدث** في Supabase Auth، بيتحدث في Zustand store محلياً بس (`:160-171`).
  5. SweetAlert (نجاح الطلب أو الترحيب بالـ guest)، ثم `router.replace("/dashboard/customer")`.
- **حقول الفورم:**

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `customer_type` (UI `selectedType`) | كارتين: "طلب خدمة هندسية" (موصى به • مجاني) / "الاستمرار بدون طلب خدمة" | radio-cards | نعم (default `need_service`) | — | `need_service`، `guest` (`features/customer/types.ts:1`) | `customers.customer_type` |
| `service` (UI `selectedService`) | 1. نوع الخدمة الهندسية * | select (`ResponsiveSelect` مع بحث) | نعم لو `need_service` | "يرجى اختيار نوع الخدمة الهندسية المطلوبة" | `ENGINEERING_SERVICES_15`، والـ **value = الاسم العربي** (`:60-69`) | `customers.service` (null للـ guest) |
| `governorate` (UI `selectedGovernorate`) | 2. المحافظة (موقع المشروع) * | select (`ResponsiveSelect`) | نعم لو `need_service` | "يرجى تحديد المحافظة التي يقع بها مشروعك" | 27 محافظة. الـ label "محافظة X" والـ value "X" | `customers.governorate` (null للـ guest) |
| `notes` | 3. تفاصيل المشروع أو استفسارك (اختياري) | textarea | لا | `maxLength=500` (`:425`)، `trim() \|\| null` | — | `customers.notes` (null للـ guest) |
| *(derived)* `user_id` | — | hidden | — | `currentUser?.id \|\| null` | — | `customers.user_id` |
| *(derived)* `full_name` | — | hidden | — | metadata `name`، أو `fullName`، أو `full_name`، أو الجزء قبل `@` في الإيميل، أو `"عميل"` (`:120-125`) | — | `customers.full_name` |
| *(derived)* `phone` | — | hidden | — | metadata `phone` أو `user.phone` أو `null` | — | `customers.phone` |
| *(derived)* `email` | — | hidden | — | `user.email` | — | `customers.email` |
| *(derived)* `status` | — | hidden | — | guest → `active`، need_service → `pending` | — | `customers.status` |
| *(derived)* step/status | — | — | — | — | — | `users.current_step = 2`، `users.status = active`، metadata `current_step = 2` |

- **قوائم الاختيارات:** `ENGINEERING_SERVICES_15` (`features/customer/constants.ts:11-108`). الاسم بيقول 15 لكن القائمة فيها **16**. القيمة المخزنة = `name`:
  `تصميم معماري`، `حصر كميات`، `رسومات تنفيذية`، `إدارة مشاريع`، `تصميم داخلي`، `تشطيبات`، `استشارات هندسية`، `تراخيص بناء`، `رفع مساحة`، `تصالح للبناء`، `تصميم إنشائي`، `تصميم واجهات`، `أعمال عزل`، `أعمال خرسانات`، `أعمال ترميم`، `خدمات نقابية`.
  الـ ids المقابلة: `architectural-design, quantity-surveying, shop-drawings, project-management, interior-design, finishing, engineering-consulting, building-permits, surveying, legalization, structural-design, facade-design, insulation-works, concrete-works, restoration-works, syndicate-services`.
  **تنبيه إملائي:** 3 أسماء مكتوبة بالهمزة هنا (`تصميم إنشائي`، `أعمال عزل`، `أعمال خرسانات`) ومن غير همزة في `engineerSpecializations` (`تصميم انشائي`، `اعمال عزل`، `اعمال خرسانات`). المقارنة النصية بين طلب العميل وخدمات المزوّد هتفشل في الحالات دي. يُفضّل التخزين بالـ id.
- **Endpoint مقترح (REST):** `POST /api/v1/customer/setup` (Bearer، verified، `user_type = customer`):

```json
{ "customer_type": "need_service", "service": "تصميم معماري", "governorate": "الجيزة", "notes": "شقة 150م في الشيخ زايد، البدء خلال شهر" }
```

```json
{ "customer_type": "guest" }
```

```php
'customer_type' => 'required|in:need_service,guest',
'service'       => ['required_if:customer_type,need_service','nullable', Rule::in(config('t3meer.engineering_services'))], // مقترح: قبول الـ id بدل الاسم
'governorate'   => ['required_if:customer_type,need_service','nullable', Rule::in(config('t3meer.governorates'))],
'notes'         => 'nullable|string|max:500',
```

  السيرفر: `full_name`, `phone`, `email` من `users`. للـ guest يفرض `service`, `governorate`, `notes` = null. `status` = `active` للـ guest و`pending` للـ `need_service`. يحدّث `users.current_step = 2` و`users.status = 'active'`.
  **Response 201:** `{ "customer_request": { "id", "user_id", "full_name", "phone", "email", "customer_type", "governorate", "service", "notes", "status", "created_at", "updated_at" }, "user": UserResource }`. الواجهة مش بتستهلك غير النجاح.
- **ملاحظات / Business rules:**
  - الـ guest بيتسجل كصف في جدول `customers` (يعني الجدول فيه سجلات "تفضيل" وسجلات "طلبات خدمة" مع بعض).
  - `notes` بتتبعت بس مع `need_service`.
  - **مقترح:** امنع إعادة الإرسال لو `current_step >= 2`، أو حوّله لـ "طلب خدمة جديد" من الـ dashboard (endpoint مستقل خارج نطاق القسم).

---

### 2.A Onboarding State Machine

> ⬅ الجدول ده بيوصف **الكود الحالي**. الـ state machine المعتمد للـ Laravel بعد إضافة خطوة التوثيق موجود في **2.V.2**: step 1 للمهندس والمكتب بيروح `/validation/{type}` بدل `/pricing/{type}`.

| `current_step` | customer | engineer / office / exhibition / material | admin | الإجراء اللي بيرفع الخطوة | مكان الكود |
|---|---|---|---|---|---|
| `0` | — | middleware بيحوّل `/complete-profile*` و`/dashboard*` لـ `/verify-email`. **مفيش كود بيكتب 0** | — | — | `middleware.ts:95-101` |
| `1` (بعد التسجيل، `status=pending`) | `/customer/setup` | `/pricing/{type}`. قبل تأكيد الإيميل: `/verify-email` (من `signup.query.ts:57-60`، مش من `getStepRoute`) | `/dashboard/admin` | **→ 2:** (أ) تأكيد الإيميل (`VerifyEmail.tsx:100-107`)، (ب) فتح `/pricing` (`PricingLayout.tsx:39-61`)، (ج) إرسال التوثيق (`validation.api.ts:24-26`، orphan). **Customer → 2:** إرسال `/customer/setup` (+ `users.status = active`) | `step-redirect.ts:14-27` |
| `2` | `/dashboard/customer` (**نهائي**) | `/pricing/{type}` | — | **→ 3:** (أ) باقة مجانية `updateUserPlan(confirmation="free_plan")`، أو (ب) رفع إيصال `uploadPaymentConfirmation()`. الاتنين بيعملوا upsert لـ `subscriptions.status = pending` | `pricing.api.ts:32-52`، `payment.api.ts:34-72` |
| `3` | `/dashboard/customer` | `/complete-profile/{type}` | — | **→ 4:** `save{Engineer\|Office\|Exhibition\|Material}Profile()` + `updateUserStep(4)` (+ `users.status = pending`) | `profile.api.ts:92-103`، `profile.query.ts:20,42,63,84` |
| `≥ 4` | `/dashboard/customer` | `/dashboard/{type}` | — | — (اعتماد الحساب أو الاشتراك من الأدمن خارج النطاق) | `step-redirect.ts:30-31` |

**تطور `users.status` في الـ onboarding:** التسجيل `pending`. توثيق المهندس (orphan) `active`. توثيق المكتب (orphan) `pending`. إكمال أي ملف مهني `pending`. إعداد العميل `active` (في `users` بس). الاعتماد بعد كده من الأدمن (`active`، `rejected`، `suspended`).

**Guards على مستوى الشاشات:**

| الشاشة | Guard |
|---|---|
| `/login`، `/register` | middleware + effect في الكومبوننت (أي مستخدم مسجّل في Login بيتحوّل، وفي Register المؤكد فقط) |
| `/verify-email` | لا يوجد. بيحوّل تلقائياً بعد التأكيد |
| `/validation/*` | بيحوّل لو `current_step > 1` |
| `/pricing/[type]` | بيحوّل لو `current_step ∉ {1,2}` |
| `/payment/[type]` | middleware (auth) فقط |
| `/complete-profile/[type]` | middleware (auth + step ≥ 4 → dashboard) |
| `/customer/setup` | لا يوجد |

**مقترح Laravel:** ضع الـ state machine في السيرفر (`OnboardingService`). كل endpoint يتحقق إن `current_step` الحالي مناسب (مثلاً `POST /onboarding/payment` يتطلب step ∈ {1,2}، و`POST /onboarding/profile/*` يتطلب step == 3) ويرجّع 409 `invalid_onboarding_step` مع `next_route`. وأضف `next_route` في `UserResource` (نفس منطق `getStepRoute`) عشان الواجهة ما تكررش المنطق.

---

### 2.B الجداول المتأثرة (Tables touched)

| الجدول | Write | Read | ملاحظات |
|---|---|---|---|
| `auth.users` (Supabase Auth) | `email`، password، `email_confirmed_at` (عبر الإيميل)، `confirmation_sent_at` (RPC)، `raw_user_meta_data`: `name, phone, user_type, slug, current_step, status, selected_plan, selected_billing_cycle, selected_user_type, email` | كل الحقول السابقة + `id`, `email_confirmed_at`, `phone` | في Laravel تبقى جدول `users` + `email_verified_at` + `password` |
| `users` (public) | INSERT بالـ trigger: `id, email, name, user_type, slug, phone, current_step, status, selected_plan`. UPDATE: `current_step` (Verify, Pricing, Validation, Payment, Profile, Customer)، `status` (Validation, Profile, Customer)، `selected_plan` (Pricing, Payment)، `name` (Engineer/Office profile، تعديل الإيميل)، `email`، `slug` (تعديل الإيميل) | `slug` (فحص التفرد، `helper-functions.ts:230-234`)، `current_step` (`PricingLayout.tsx:42-46`) | enums: `account_type` (`customer, engineer, office, supplier, exhibition`)، `status` (`pending, active, rejected, suspended`)، `plan_type` (`free, basic, pro, premium`) |
| `user_validation` | UPSERT (`onConflict user_id`): `user_id, name, account_type, professional_id, tax_number, documents, details{graduation_year, specialization}, status` | `select *` by `user_id` (`validation.api.ts:51-60`) | بتتكتب من التوثيق (orphan) ومن كل profile save (placeholder). `account_type` فيها `material` كمان |
| `plans` | — | `plan_type (alias plan_id), price, original_price` filtered by `user_type, billing_cycle`. و`select *` by `user_type, plan_type, billing_cycle` | `user_type` ∈ `engineer, office, exhibition, material`. `plan_type` ∈ `basic, professional, premium` (وربما `free`). `billing_cycle` ∈ `monthly, quarter, half, yearly` |
| `subscriptions` | UPSERT (`onConflict user_id`): `user_id, plan_type, billing_cycle, user_type, confirmation (free_plan \| receipt URL), status='pending'` | `select *` by `user_id` (`payment.api.ts:77-94`) | صف واحد لكل مستخدم. أعمدة `plan_price, starts_at, expires_at` مش بتتكتب هنا |
| `subscription_payments` | — (**مش بيتكتب من الـ onboarding**) | — (في الأدمن بس) | انظر شاشة الدفع |
| `user_permissions` | — | `plan_type, max_services` by `user_id` | لحدود الخدمات والتصنيفات في فورمات الملف |
| `engineers` | UPSERT (`onConflict user_id`): `user_id, name, major, experience_years, governorate, bio, whats_app, skills, latitude, longitude, profile_image` (+ `phone, city, website, workingHours, social, coverImage, videoUrl` في الـ signature لكن undefined) | — | `slug` مش بيتكتب هنا |
| `offices` | UPSERT: `user_id, name, governorate, bio, whats_app, experience_years, count_of_projects, open_hours, latitude, longitude, profile_image` | — | |
| `exhibitions` | UPSERT: `user_id, name, governorate, city(undef), bio, whats_app, experience_years, latitude, longitude, logo, count_of_categories, open_hours, main_category` | — | الصورة في `logo` |
| `materials` | UPSERT: `user_id, name, governorate, city(undef), bio, whats_app, experience_years, latitude, longitude, profile_image, count_of_categories(undef), open_hours(undef), main_category` | — | الوثائق بتقول الجدول اتلغى |
| `services` | DELETE `where user_id`، ثم INSERT `[{ user_id, name, icon, color, slug }]` | — | `name` = label عربي أو اسم التصنيف الفرعي |
| `customers` | INSERT: `user_id, full_name, phone, email, customer_type, governorate, service, notes, status` (+ `.select().single()`) | — | `status`: `active` (guest) أو `pending` (need_service) |
| RPC `check_user_exists(p_email)` | — | → `{ exists, message, user_id }` | **التعريف مش في الريبو** |
| RPC `update_unconfirmed_user_email(p_old_email, p_new_email, p_new_slug)` | `users.email, slug`. `auth.users.email, confirmation_sent_at, raw_user_meta_data.email/slug` | → `{ success, error?, user_id, new_email, new_slug }` | `supabase_update_email_rpc.sql`. **GRANT لـ anon** |
| Trigger `on_auth_user_created` → `handle_new_user()` | INSERT/UPSERT في `users` | — | `supabase_signup_trigger.sql` |
| Cloudinary | uploads: `verifications`, `payments`, `engineers/profiles`, `offices/profiles`, `exhibitions/profiles`, `materials/profiles` | — | preset `t3meer_images` |
| `/api/meta/event` (Next route، خارج النطاق) | Meta events: `CompleteRegistration`, `AddPaymentInfo`, `Subscribe`, `ProfileCompleted` (custom) — body `{ eventName, params, eventId }` | — | `src/lib/pixel.ts:6-80` |

---

### 2.C نقاط غامضة وقرارات مطلوبة من فريق الـ backend

1. **`professional` مقابل `pro`:** الواجهة و`plans` و`subscriptions` بيستخدموا `professional`، والـ enum `plan_type` لـ `users.selected_plan` = `free|basic|pro|premium`. لازم توحيد القيم.
2. **نوع حساب المورّد:** شاشات `pricing` و`payment` و`complete-profile` لـ `material` موجودة، لكن التسجيل مش بيعرض `material` ولا `supplier`، والـ trigger بيحوّل `material` لـ `engineer`. والمعارض والموردين بيتسجلوا كـ `exhibition`. **القرار:** هل `material` نوع حساب مستقل (ومعاه جدول `materials`)، ولا يتلغى ويتدمج في `exhibition`؟ ✅ **اتحسم:** مفيش نوع `material`، المورد = `exhibition` (0.5).
3. **التوثيق (`/validation/*`) orphan** وكمان بيتعمله overwrite بـ placeholder (`professional_id` عشوائي، صور `placehold.co`) في كل profile save. **القرار:** إلغاء `user_validation`، أو تفعيل خطوة التوثيق فعلياً وإزالة الـ placeholder.
4. **الهاتف المكرر:** الكود متوقع خطأ uniqueness على الهاتف وقت التسجيل، لكن الـ constraint مش ظاهر في الريبو. والهاتف بيتخزن من غير normalization. **مقترح:** normalize لصيغة `01XXXXXXXXX` + `unique`.
5. **`check_user_exists`:** تعريفه ونص `message` اللي بيرجّعه مش موجودين في الريبو.
6. **الأمان:** تعديل إيميل حساب مش مؤكد متاح لأي حد (RPC لـ anon + route من غير auth). و`/customer/setup` بيسمح بإدخال صف في `customers` من غير تسجيل دخول.
7. **الأسعار مش في الكود:** كلها في جدول `plans`، ولازم seed من الـ DB الحالية. الصفحة بتعتبر الباقة "مجانية" بس لو `price === 0`.
8. **`subscription_payments`:** مش بيتكتب من الـ onboarding، ولا `plan_price` في `subscriptions`. **القرار:** مين ينشئ سجل الدفعة وإمتى.
9. **خدمات المكتب:** فورم المكتب بيعرض قائمة المهندس (`engineerSpecializations`) مش `officeServices`. هل ده مقصود؟
10. **حقول معلنة ومش متجمّعة:** `city`, `phone`, `website`, `workingHours`, `social`, `coverImage`, `videoUrl` (مهندس)، و`city`, `website`, `team_size`, `specializations` (مكتب)، و`city` (معرض ومورد)، و`count_of_categories`, `open_hours` (مورد)، و`deliveryRanges`, `materialServices`, `exhibitionServices` (مش معروضين). ممكن يتجمعوا من شاشات تعديل الملف في الـ dashboard (خارج النطاق).
11. **حقول متجمّعة ومش متخزّنة:** `confirmPassword` (طبيعي)، و checkbox الموافقة على الشروط في الـ pricing (**مش بيتسجل**؛ مقترح: `terms_accepted_at`).
12. **عدم تطابق الحدود:** الحدود التسويقية للمورد (فئات 2 و5) مختلفة عن حد التصنيفات الفرعية في الفورم (3 و7). والـ fallback لحد خدمات المهندس (1 و5 و∞) مختلف عن المكتب (3 و7 و∞).
13. **أسماء الخدمات بنصوص عربية مختلفة الإملاء** بين قائمة العميل وقائمة المهندس (همزات). مقترح: التخزين بالـ ids.
14. **`uncommitted_changes.patch`** (مش مطبّق على الـ snapshot) بيوضح اتجاه بديل اتجرّب: التسجيل بـ `current_step: 3` (تخطي التسعير)، و`getStepRoute` يرجّع `/complete-profile` لأي step < 4، وإنشاء اشتراك `free` بـ `status: active` تلقائياً عند إكمال الملف، وحالة `active` بدل `pending`، وإلغاء حدود الخدمات (`maxServices = Infinity`). **لازم تأكيد مع الـ product** أنهي flow هو المعتمد.
15. **وثائق المشروع مش متطابقة مع الكود:** `t3meer_full_analysis.md` بيقول إن `user_validation` و`materials` اتلغوا، و`database_schema.md` فيه أسماء جداول وأعمدة "مقترحة" (`profiles_engineer`، `full_name`، `account_type`...) مش مستخدمة في الكود. الجداول اللي فوق مأخوذة من الكود الفعلي.

---

## 3. لوحة تحكم المستخدم (غير الأدمن) — `/dashboard/[type]/[page]`

> النطاق: كل شاشات `/dashboard/{type}` و`/dashboard/{type}/{page}` لأنواع الحسابات `customer`, `engineer`, `office`, `exhibition`, `material`. لوحة الأدمن (`/dashboard/admin/**`) خارج هذا القسم.
> كل ما يلي موثّق من الكود فقط. حيث تقول الوثيقة "الكود لا يتحقق من..." فهذا وصف للسلوك الحالي، والـ Laravel validation المقترح يضيف الحد الأدنى المنطقي مع التنبيه.

### 3.0 ملاحظات عامة

**أنواع الحسابات في الداشبورد** (`features/dashboard/types.ts:1`):
`UserType = "engineer" | "office" | "exhibition" | "customer" | "admin"`.

> ✅ **قرار:** في Laravel مفيش `material`. المورد = `exhibition` وبيستخدم نفس صفحات الداشبورد بتاعة المعرض (0.5). أي `exhibition|material` في القسم ده = `exhibition` بس في الـ API، والأجزاء الخاصة بـ `material` (زي 3.4.4) بتوصف الكود الحالي.
- "المورد / supplier" في المتطلبات = `material` في الداشبورد (route segment `material`، جدول `materials`). فورم التسجيل (`features/auth/signup/components/RegisterForm.tsx:29-55`) لا يعرض `supplier` أصلاً؛ يعرض `exhibition` بعنوان "معارض وموردين".
- جدول البروفايل لكل نوع (`features/dashboard/profile/api/profile.static.ts:127-132` و`features/dashboard/home/api/home.api.ts:42-49`):
  `engineer → engineers`, `office → offices`, `exhibition → exhibitions`, `material → materials`. أي نوع آخر (بما فيه `customer`) يقع افتراضياً على `engineers` في `home.api.ts` (انظر 3.2.2).

**الحراسة (Guards) والتوجيه**:
- `app/dashboard/layout.tsx:23-44`: لو غير مسجل → `/login`. لو النوع ليس `customer`/`admin` و`user_metadata.current_step < 4` → رسالة "يرجى إكمال خطوات التسجيل أولاً" ثم توجيه إلى `getStepRoute(type, step)` (`lib/step-redirect.ts:12-33`: step 0–2 → `/pricing/{type}`، step 3 → `/complete-profile/{type}`).
- `app/dashboard/[type]/layout.tsx:23-53`: النوع المسجل = `user_metadata.user_type || .type || .account_type`. لو الـ URL type غير مسموح (`engineer, office, exhibition, material, customer`) → 404. لو مختلف عن النوع المسجل → `router.replace('/dashboard/{registeredType}')`.
- `app/dashboard/page.tsx:9-12`: `/dashboard` يحوّل دائماً إلى `/dashboard/engineer` (ثم الـ layout يعيد التوجيه للنوع الصحيح).
- الـ API الجديد يجب أن يطبق هذه الحراسة server-side: كل endpoint في هذا القسم `auth:sanctum` + middleware يتحقق من `user_type` و(لغير العميل) `current_step >= 4`.

**مفاتيح `auth.user_metadata` المقروءة في هذا القسم**: `user_type`, `type`, `account_type`, `current_step`, `selected_plan`, `selected_billing_cycle`, `slug`, `name`, `full_name`, `phone`, `profile_image`, `image`.
**المفاتيح المكتوبة عبر `auth.updateUser({data})`**: `name`, `phone`, `profile_image`, `image`, `selected_plan`, `selected_billing_cycle`, `selected_user_type`, `current_step` + `auth.updateUser({password})`.
في Laravel: هذه تصبح أعمدة في `users` (أو جدول settings)، والـ API يرجعها في `GET /api/v1/auth/me`.

**قاعدة تطبيع الحدود (limits) المستخدمة في كل الشاشات** (مثال: `DashboardProjects.tsx:51-57`, `DashboardHome.tsx:390-395`):
```
limit = (raw == null) ? ∞ : (raw == -1 || raw >= 99999) ? ∞ : raw
isAtLimit = limit != ∞ && currentCount >= limit
```
المصدر الوحيد للحدود هو صف `user_permissions` (بـ `user_id`). لا توجد أرقام hardcoded إلا fallback واحد في فورم المورد (3.4.4). الأرقام التسويقية لكل باقة موجودة في 3.15 كمرجع فقط.

**الرفع إلى Cloudinary** (`apis/global.api.ts:16-59`): `POST https://api.cloudinary.com/v1_1/{NEXT_PUBLIC_CLOUDINARY_CLOUD_NAME|"drk3bhviw"}/{resource_type}/upload` بـ unsigned preset `t3meer_images` (ثابت في السطر 23)، `resource_type = "raw"` لو `file.type` يبدأ بـ `video/` وإلا `image`، والحقل `folder`. يرجع `secure_url` الذي يُخزن في الـ DB. لا يوجد أي فحص حجم/نوع في الدالة نفسها.

| Cloudinary folder | الشاشة | العمود الهدف | MIME (accept) | حد الحجم المطبق فعلاً | الحد الأقصى للعدد |
|---|---|---|---|---|---|
| `profiles` | Sidebar (صورة المستخدم) | `{profileTable}.profile_image` + metadata `profile_image`,`image` | `image/*` | لا يوجد | 1 |
| `exhibitions/logos` | تعديل المعرض | `exhibitions.logo` | `image/*` | لا يوجد | 1 |
| `exhibitions/banners` | تعديل المعرض | `exhibitions.banner` | `image/*` | لا يوجد | 1 |
| `videos` | تعديل المكتب / المعرض | `offices.video` / `exhibitions.video` | `video/*` (resource_type `raw`) | 100MB (`lib/helper-functions.ts:278`) | 1 |
| `projects` | المشاريع | `projects.image`, `projects.details.photos[]`, `projects.details.video` | `image/*`, `video/*` | لا يوجد (النص يقول 5MB للصور و50MB للفيديو لكنه غير مطبق) | صورة رئيسية 1، صور إضافية بلا حد (`multiple`)، فيديو 1 |
| `products` | المنتجات | `products.image`, `products.details.photos[]` | `image/*` | لا يوجد (النص يقول 5MB) | 1 + صور إضافية بلا حد |
| `payments` | الباقات | `subscriptions.confirmation` | `image/*` | لا يوجد | 1 |
| `banners` | التسويق | `marketing.banner.image` | `image/*` | لا يوجد (المقاس المقترح في الواجهة 1700x900) | 1 |
| `social_posts` | التسويق | `marketing.social.image` | `image/*` | لا يوجد | 1 |
| `verifications` | (قسم التوثيق في تعديل الحساب معلّق/commented) | `user_validation.documents[]` | `image/*` | — | — |

> في Laravel: إما الإبقاء على الرفع المباشر وإرسال الـ URL في الـ JSON (الأبسط ويطابق السلوك الحالي)، أو endpoint `POST /api/v1/uploads` (multipart: `file`, `folder`) يرجع `{url}`. القواعد المقترحة أدناه تفترض الإبقاء على URLs: `url|max:2048`.

---

### 3.1 جدول الصفحات (page key → النوع → Component)

التوجيه في `app/dashboard/[type]/[page]/page.tsx:32-77`، الرئيسية في `app/dashboard/[type]/page.tsx:24`، والـ sidebar في `features/dashboard/components/DashboardSidebar.tsx:124-156` + `features/dashboard/static.ts:15-36`.

| page key (URL) | أنواع الحسابات | Component | الغرض |
|---|---|---|---|
| *(index)* `/dashboard/{type}` | الكل | `features/dashboard/home/components/DashboardHome.tsx` | الرئيسية: إحصائيات، حدود الباقة، نسبة اكتمال الملف، آخر النشاطات، charts |
| `home` | الكل | لا شيء (`return null`، `[page]/page.tsx:72-75`) | غير مستخدم؛ زر "الرئيسية" في الـ sidebar يذهب للـ index |
| `requests` | `office`, `engineer` → `OfficeCustomerRequests`؛ `customer` → `CustomerServiceRequests`؛ `exhibition`/`material` → 404 | `features/dashboard/office/components/OfficeCustomerRequests.tsx` | طلبات العملاء المسندة للمكتب/المهندس + تتبع نقرات الاتصال/واتساب |
| `customers` | نفس `requests` (alias، `[page]/page.tsx:49-57`) | نفس السابق | — |
| `services` | `customer` → `CustomerServiceRequests`؛ باقي الأنواع → `DashboardServices` | `features/dashboard/customer/components/CustomerServiceRequests.tsx` / `features/dashboard/services/components/DashboardServices.tsx` | العميل: طلبات الخدمات الهندسية. المهني: إدارة خدماته/تخصصاته/تصنيفات المنتجات |
| `projects` | `engineer`, `office` (و`customer` متاح بالـ URL فقط، غير موجود في الـ sidebar)؛ `exhibition`/`material` → 404 | `features/dashboard/projects/components/DashboardProjects.tsx` | CRUD معرض الأعمال (المشاريع) |
| `products` | `exhibition`, `material` فقط؛ غيرهم → 404 | `features/dashboard/products/components/DashboardProducts.tsx` | CRUD المنتجات |
| `plans` | كل الأنواع (العميل بالـ URL فقط) | `features/dashboard/plans/components/DashboardPlans.tsx` | عرض الاشتراك الحالي، ترقية/تجديد، رفع إيصال التحويل |
| `edit-profile` | `engineer`, `office`, `exhibition`, `material`؛ (`customer` يقع على `EditEngineerProfile` كـ default — `DashboardEditProfile.tsx:29-30` — وغير موجود في الـ sidebar) | `features/dashboard/profile/components/DashboardEditProfile.tsx` → `EditEngineerProfile` / `EditOfficeProfile` / `EditExhibitionProfile` / `EditMaterialProfile` | تعديل بيانات البروفايل + تغيير كلمة المرور + حذف الحساب |
| `marketing` | كل الأنواع غير العميل (في الـ sidebar) | `features/dashboard/marketing/components/DashboardMarketing.tsx` | بانر الدليل/الرئيسية، منشور سوشيال، Facebook Pixel، عرض مزايا الباقة |
| `favorites` | `customer` (لا يوجد type guard) | `features/dashboard/customer/components/DashboardFavorites.tsx` | قائمة المفضلة + الحذف |
| `rating` | `customer` (لا يوجد type guard) | `features/dashboard/customer/components/DashboardRatings.tsx` | تقييمات العميل (قراءة فقط) |
| `activity` | `customer` (لا يوجد type guard) | `features/dashboard/customer/components/DashboardActivity.tsx` | النشاط الأخير (مفضلة/تقييمات/زيارات) |
| `logout` | الكل | action في الـ sidebar وليس صفحة | تسجيل الخروج |

**عناصر الـ sidebar لكل نوع**:
- `customer`: الرئيسية، `services` ("طلبات الخدمات")، `activity`، `rating`، `favorites`، تسجيل الخروج.
- `engineer` / `office`: الرئيسية، `requests` ("طلبات العملاء" + شارة "جديد")، `projects`، `services`، `marketing`، `plans`، `edit-profile`، تسجيل الخروج.
- `exhibition` / `material`: الرئيسية، `products` (يستبدل `projects`)، `services` (العنوان "تصنيفات المنتجات" للمعرض و"تصنيفات المواد" للمورد)، `marketing`، `plans`، `edit-profile`، تسجيل الخروج.
- `features/dashboard/components/DashboardLeads.tsx` غير مربوط بأي route (بيانات mock)، يتجاهل.

---

### 3.2 الـ Sidebar والإجراءات العامة

#### 3.2.1 قراءات الـ Sidebar
- `useQueryUserProfile(userId, type)` → `getUserProfile` (`home.api.ts:38-59`): `from(profileTable).select("*").eq("user_id", userId).maybeSingle()`. الحقول المستخدمة: `profile_image`, `image`, `name`, `major` (يعرض "معماري" لو `architectural`، "مدني" لو `civil`، وإلا القيمة كما هي — `DashboardSidebar.tsx:226-230`).
- `useUserStatus(userId)` → `getUserStatus` (`features/dashboard/dashboard.api.ts:33-51`): `from("users").select("status, rejection_reason").eq("id", userId).maybeSingle()`. يُعرض لغير العميل: `active` → "حساب مفعل"، `pending` → "قيد المراجعة"، غير ذلك → "غير مفعل"؛ و`rejection_reason` يظهر فقط لو `status === "reject"`.
- **Endpoint مقترح (REST)**: `GET /api/v1/auth/me` (auth) → 
```json
{ "id": "uuid", "email": "...", "name": "...", "phone": "...", "user_type": "office", "slug": "...",
  "current_step": 4, "selected_plan": "basic", "selected_billing_cycle": "quarter",
  "status": "active|pending|reject", "rejection_reason": null,
  "profile": { "name": "...", "profile_image": "...", "major": "...", "slug": "..." } }
```

### 3.2.2 رفع الصورة الشخصية — `/dashboard/{type}/*` (Sidebar) — `features/dashboard/components/DashboardSidebar.tsx`
- **المستخدم**: كل الأنواع (الـ sidebar في الـ layout). لا يوجد gating.
- **الإجراء عند الإرسال** (`DashboardSidebar.tsx:73-105` → `home.query.ts:31-49` → `home.api.ts:61-93`): اختيار ملف → `uploadToCloudinary(file, "profiles")` → `from(table).update({ profile_image: url }).eq("user_id", userId)` حيث `table` = `offices|exhibitions|materials` أو `engineers` لأي نوع آخر (**العميل يكتب في `engineers` ولا يطابق أي صف** — bug) → `auth.updateUser({ data: { profile_image: url, image: url } })` → `setUser(updatedUser)`.
- ملاحظة: للمعرض الواجهة العامة تستخدم `exhibitions.logo`، لكن هذا الإجراء يكتب `exhibitions.profile_image`.
- **حقول الفورم**:

| key | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `file` → `profile_image` | صورة المستخدم (زر الكاميرا) | file (`accept="image/*"`) | نعم (للإجراء) | لا يوجد فحص حجم/نوع | — | `{engineers\|offices\|exhibitions\|materials}.profile_image` + metadata `profile_image`, `image` |
| `user_id` (مشتق) | — | uuid | — | من الجلسة | — | شرط `WHERE user_id` |

- **قوائم الاختيارات**: لا يوجد.
- **Endpoint مقترح (REST)**: `POST /api/v1/me/avatar` (auth) — body `{ "profile_image": "https://res.cloudinary.com/..." }` — rules: `profile_image: required|url|max:2048`. السيرفر يحدد جدول البروفايل من `user_type` (ولـ `customer` يحدّث `users.profile_image` فقط). Response: `{ "profile_image": "..." }`.

### 3.2.3 تسجيل الخروج — `/dashboard/{type}/*` (Sidebar) — `features/dashboard/components/DashboardSidebar.tsx`
- **المستخدم**: كل الأنواع (عنصر `logout` في الـ sidebar).
- **الإجراء عند الإرسال**: `useLogout` (`features/dashboard/dashboard.query.ts:42-58`) → `logout()` (`dashboard.api.ts:73-77`): `supabase.auth.signOut()` ثم `clearUser()`، `queryClient.clear()`، مسح cookies `sb-access-token` و`sb-refresh-token`، توجيه إلى `/`.
- **حقول الفورم**: لا يوجد.
- **قوائم الاختيارات**: لا يوجد.
- **Endpoint مقترح (REST)**: `POST /api/v1/auth/logout` (auth) — بدون body — يلغي الـ token الحالي. Response `204`.

### 3.2.4 حذف الحساب نهائياً — `/dashboard/{engineer|office|exhibition|material}/edit-profile` — `features/dashboard/profile/components/Edit{Engineer|Office|Exhibition|Material}Profile.tsx`
- **المستخدم**: `engineer`, `office`, `exhibition`, `material` (زر "حذف الحساب نهائياً" في `EditEngineerProfile.tsx:722-758`, `EditOfficeProfile.tsx:913-949`, `EditExhibitionProfile.tsx:899-935`, `EditMaterialProfile.tsx:501-537`).
- **الإجراء عند الإرسال**: `confirmSwal("حذف الحساب نهائياً؟", ...)` → `useDeleteUserAccount` (`dashboard.query.ts:28-41`) → `supabase.rpc("delete_user_account")` (بدون parameters، `dashboard.api.ts:65-71`) → `auth.signOut()` → `clearUser()`, `queryClient.clear()`, `router.push("/")`. تعريف الـ RPC غير موجود في الـ repo (الوثائق تقول "Complete account deletion").
- **حقول الفورم**: لا يوجد (تأكيد فقط).
- **قوائم الاختيارات**: لا يوجد.
- **Endpoint مقترح (REST)**: `DELETE /api/v1/me` (auth) — body اختياري `{ "confirm": true }` — rules: `confirm: accepted`. السيرفر يحذف/يلغي: `users`, صف البروفايل، `projects`, `products`, `services`, `marketing`, `subscriptions`, `user_permissions`, `favorites`, `rating` الخاصة به، ويلغي كل الـ tokens. Response `204`.

---

### 3.3 الرئيسية — `/dashboard/{type}` — `features/dashboard/home/components/DashboardHome.tsx`

صفحة قراءة فقط (لا يوجد فورم). عند الـ mount تعمل invalidate لكل الـ queries (`DashboardHome.tsx:53-64`).

#### 3.3.1 القراءات المستخدمة
| Hook / function | المصدر | الأعمدة/الشروط | ما تستخدمه الواجهة |
|---|---|---|---|
| `useDashboardStats(userId, userType)` → `getDashboardStats` (`home.api.ts:252-398`) | انظر 3.3.2 / 3.3.3 | — | stat cards, chart, activities |
| `useQueryUserProfile` → `getUserProfile` | `{profileTable}` `select *` `eq user_id` | — | حساب نسبة اكتمال الملف |
| `useSubscription` → `getUserSubscription` (`features/dashboard/plans/api/plans.api.ts:3-35`) | `subscriptions` `select *` `eq user_id` `maybeSingle` | يعاد تسميتها: `plan_type→plan_id`, `status`, `billing_cycle`, `plan_price`, `start_date→starts_at`, `expires_at`, `confirmation`؛ fallback من metadata: `selected_plan` (أو `"basic"`)، `selected_billing_cycle` (أو `quarter` للمهندس/المكتب و`monthly` لغيرهم) | اسم الباقة، `billing_cycle` لحساب "أيام التفعيل" |
| `useUserPermissions` → `getUserPermissionsByUserId` (`dashboard.api.ts:53-62`) | `user_permissions` `select *` `eq user_id` `maybeSingle` | كل الأعمدة (انظر 3.16) | الأقفال والحدود |
| `useUserStatus` | `users` `status, rejection_reason` | — | شارة الحالة + سبب الرفض |
| `useUserProjects` (`projects.api.ts:38-47`) | `projects` `select *` `eq user_id` `order created_at desc` | — | `length` فقط (عدد المشاريع) |
| `useUserProducts` (`products.api.ts:20-29`) | `products` `select *` `eq user_id` `order created_at desc` | — | `length` فقط |
| `useUserServices(userId, slug?)` (`services.api.ts:14-29`) | `services` `select *` `eq slug` (للمعرض/المورد، `slug = user_metadata.slug`) أو `eq user_id` (لغيرهم) `order created_at asc` | — | `length` فقط |

**تحديد الباقة المعروضة** (`DashboardHome.tsx:80-85`): `userPlan prop (metadata.selected_plan) || user_permissions.plan_type || subscription.plan_id || metadata.selected_plan || "basic"`.

#### 3.3.2 إحصائيات المهني (`engineer`, `office`, `exhibition`, `material`) — `getDashboardStats`
1. `profile = getUserProfile(...)`؛ لو لا يوجد `profile.slug` → كل القيم 0.
2. `rpc("get_limited_stats", { p_slug })` → صف واحد `{visit, whatsapp, call, email, map, facebook, instagram, youtube}` (الأرقام "هذا الشهر" حسب نص الكارت).
3. `rpc("get_all_stats", { p_slug })` → صفوف `{metric_type, created_at}`؛ client-side: لكل شهر 0..11 يعدّ **عدد الصفوف** حيث `metric_type === "visit"` و`getMonth() === i` (يتجاهل السنة، ولا يجمع عمود `count` — `home.api.ts:340-357`). ملاحظة: `lib/track-metric.ts:33-66` يحدّث صفاً واحداً لكل (`slug`,`metric_type`) بزيادة `count`، لذا العد بالصفوف لا يعكس العدد الحقيقي — الـ API يجب أن يجمع `SUM(count)` أو يعدّ events.
4. `getRecentActivities(userId, type, slug)` (`home.api.ts:181-242`): `stats select * eq slug order created_at desc limit 10` + `favorites select * eq slug order created_at desc limit 5`، دمج وترتيب تنازلي وأخذ 10. اسم الفاعل `s.users?.name || "زائر"` — لا يوجد join فعلياً فيظهر دائماً "زائر". نوع النشاط: `visit` → "قام {actor} بزيارة ملفك"، `whatsapp` → "تواصل معك {actor} عبر واتساب"، `call` → "قام {actor} بالنقر على رقم هاتفك"، أي metric آخر يعامل كـ visit text مع `type:"contact"`، favorites → "أضاف {actor} ملفك للمفضلة".
5. `favorites select * count exact head eq slug` → `favorites_count`.
6. `rating select rating eq slug` → `reviews_count = length`، `average_rating = round(avg,1)`.
7. يرجع أيضاً `experience_years`, `specializations_count = (profile.specializations || profile.services || []).length`, `projects = profile.count_of_projects`.

**الـ stat cards** (`features/dashboard/static.ts:51-202`):
- مشترك: `visitors` (=`visit`)، `whatsapp`، `calls` (=`call`)، `rating` (=`average_rating`)، `email`، `maps` (=`map`)، `favorites` (=`favorites_count`).
- `office`, `exhibition`, `material`: المشترك + `last_month_visitors`.
- `engineer`: المشترك بدون `maps` و`rating` + `last_month_visitors`.
- `last_month_visitors` **لا يتم حساب قيمته أبداً** (يبقى 0) — الـ API يجب أن يوفّره.

**منطق القفل** (`DashboardHome.tsx:576-609`): مقفول افتراضياً: `whatsapp, calls, maps, email, favorites, last_month_visitors`. يُفتح: `whatsapp` لو `whatsapp_tracking`، `calls` لو `call_tracking`، `maps` لو `google_maps_location`، `email`/`last_month_visitors`/`favorites` لو `detailed_analytics`. chart الزيارات وchart النقرات مقفولان لو `!detailed_analytics`. أعمدة chart النقرات (`ClicksChartJS.tsx:38-56`): واتساب (`whatsapp_tracking`)، مكالمة (`call_tracking`)، بريد/فيسبوك/انستجرام/يوتيوب (`detailed_analytics`)، الخريطة (`google_maps_location`).

**بانر الحدود ونسبة الاكتمال** (`DashboardHome.tsx:381-536`):
- `visitLimit` من `max_visits` (قاعدة التطبيع). `isLimitReached = visits >= visitLimit` → رسالة "وصلت للحد الأقصى من عدد الزيارات في الباقة {اسم}" + زر ترقية.
- نسبة الاكتمال = عدد الحقول غير الفارغة ÷ العدد الكلي. الحقول المشتركة: `name, bio, profile_image||logo, governorate, facebook, whats_app, instagram, youtube, experience_years`. + `engineer`: `skills` (غير فارغة). + `office`: `open_hours, count_of_projects, video`.
- أشرطة الاستهلاك: الزيارات (`visit` / `max_visits`)، المشاريع أو المنتجات (`count` / `max_projects`)، الخدمات أو التصنيفات (`services.length` / `max_services`)، "أيام التفعيل": `current` ثابت 0 و`max` = 365 (`yearly`) / 180 (`half`) / 90 (`quarter`) / 30 (غير ذلك) — `DashboardHome.tsx:750-760`.

#### 3.3.3 إحصائيات العميل — `getDashboardStats` (`home.api.ts:256-284`)
- `favorites count exact head eq user_id eq type "engineer"` → `fav_engineers`.
- `favorites count ... eq type "office"` → `fav_offices`.
- `rating count exact head eq user_id` → `my_ratings`.
- `getRecentActivities(userId, "customer")` (`home.api.ts:113-180`): `favorites select * eq user_id order created_at desc limit 5` ("لقد أضفت '{name}' للمفضلة"، link `/directory/{type}s/{slug}`)، `rating select * eq user_id order desc limit 5` ("لقد قمت بتقييم أحد المهنيين ({rating} نجوم)")، `stats select * eq user_id eq metric_type "visit" order desc limit 10` ("لقد زرت ملف المهني ذو المعرف '{slug}'")؛ دمج وترتيب وأخذ 10.
- `chartData: []`.
- لو لا يوجد نشاط حقيقي تعرض الواجهة نشاطات ثابتة mock من `static.ts:204-277` — يجب إزالتها.

#### 3.3.4 Endpoints مقترحة للرئيسية
`GET /api/v1/me/dashboard` (auth) — لا params. يحسب كل شيء server-side.
Response (مهني):
```json
{
  "plan": { "plan_type": "professional", "billing_cycle": "quarter", "status": "active", "expires_at": "..." },
  "status": { "status": "active", "rejection_reason": null },
  "permissions": { "max_visits": 1000, "max_projects": 10, "max_services": 7, "whatsapp_tracking": true, "call_tracking": true, "detailed_analytics": true, "google_maps_location": false, "video_projects": true, "...": "..." },
  "stats": { "visit": 0, "whatsapp": 0, "call": 0, "email": 0, "map": 0, "facebook": 0, "instagram": 0, "youtube": 0,
             "last_month_visit": 0, "favorites_count": 0, "average_rating": 4.5, "reviews_count": 3 },
  "usage": { "visits": {"current": 120, "max": 1000}, "items": {"current": 4, "max": 10, "kind": "projects|products"},
             "services": {"current": 2, "max": 7}, "days": {"current": 12, "max": 90} },
  "visits_chart": [ { "month": 1, "label": "يناير", "value": 0 } ],
  "profile_completion": 78,
  "activities": [ { "id": "stat-1", "type": "visit|contact|favorite|review", "actor_name": "زائر", "text": "...", "icon": "...", "created_at": "...", "link": null } ]
}
```
- `null` في أي `max_*` = غير محدود (بدلاً من -1/99999). القيم المقفولة حسب الصلاحيات يُفضّل إرجاعها `null` من السيرفر.
Response (عميل): `{ "stats": { "fav_engineers": 0, "fav_offices": 0, "my_ratings": 0 }, "activities": [...] }`.
- `GET /api/v1/me/activities?limit=10` — نفس `activities` (يستخدمها أيضاً صفحة `activity`، 3.14).

---

### 3.4 تعديل الحساب — `/dashboard/{type}/edit-profile`

الأربعة فورمات تحمّل البيانات بـ `fetchProfileData(userId, type)` (`features/dashboard/profile/api/profile.api.ts:39-55`): `from(TABLE_MAP[type]).select("*").eq("user_id", userId).maybeSingle()`.

> **مهم للـ backend**: كل فورم يبدأ بـ state مليء ببيانات mock (`features/dashboard/profile/api/profile.static.ts:8-167`) ويدمج بيانات الـ DB فوقها بـ fallbacks (`||`)، فالقيم الفارغة في الـ DB تُستبدل أحياناً بقيم mock **وتُحفظ** عند الضغط على حفظ (أمثلة مذكورة تحت كل فورم). الـ API الجديد يجب أن يرجع القيم الفعلية (`null`/فارغ)، والـ frontend يجب ألا يملأ mock.

### 3.4.1 تعديل بيانات المهندس — `/dashboard/engineer/edit-profile` — `features/dashboard/profile/components/EditEngineerProfile.tsx`
- **المستخدم**: `engineer` (و`customer` لو فتح الـ URL، `DashboardEditProfile.tsx:29-30`). لا يوجد gating بالباقة.
- **الإجراء عند الإرسال** (`EditEngineerProfile.tsx:147-186`):
  1. `updateEngineerProfile(userId, {...common, experience_years: parseInt(toEnglishDigits) || 0, latitude: parseFloat || null, longitude: parseFloat || null})` (`profile.api.ts:57-110`) → `from("engineers").upsert({name, major, experience_years, governorate, bio, latitude, longitude, profile_image, whats_app, skills, facebook, instagram, youtube, updated_at: now, user_id}, { onConflict: "user_id" })`. لاحظ: **`phone` لا يُكتب في `engineers`**.
  2. داخلها: لو `name || phone` → `updateUserBasicInfo(userId, {name, phone})` (`profile.api.ts:6-37`): `from("users").update({name, phone}).eq("id", userId)` ثم `auth.updateUser({data: {name?, phone?}})` (فقط القيم truthy).
  3. **بعد** الحفظ: لو `phone` غير فارغ و`!isValidEgyptianPhone(phone)` → خطأ "رقم الهاتف غير صالح، يجب أن يكون رقم مصري صحيح" (البروفايل يكون قد حُفظ بالفعل والـ users قد حُدّث بالرقم غير الصالح في الخطوة 2).
  4. لو `name || phone` → `updateUserBasicInfo` مرة ثانية (تكرار).
  5. invalidate: `user_profile`, `engineer_profile`, `user_status`.
- **Prefill ملاحظات** (`EditEngineerProfile.tsx:97-107`): `skills: data.skills || mock ["Revit Architecture","AutoCAD","SketchUp","3Ds Max"]` → لو الـ DB فارغ تُحفظ قيم mock. `phone: data.phone || metadata.phone || "01012345678"`. `name: data.name || metadata.name || "م. أحمد محمد"`.
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options source | → table.column |
|---|---|---|---|---|---|---|
| `name` | الاسم | text | لا | لا يوجد | — | `engineers.name` + `users.name` + metadata `name` |
| `phone` | رقم الهاتف | tel | لا | input يحوّل الأرقام العربية ويحذف أي غير رقم (`toEnglishDigits(v).replace(/[^0-9]/g,"")`)؛ لو غير فارغ: `isValidEgyptianPhone` = `/^(?:\+20\|0020)0?1[0125]\d{8}$\|^01[0125]\d{8}$/` (بعد التنظيف عملياً `^01[0125]\d{8}$` أو `^00200?1[0125]\d{8}$`) — يُفحص بعد الحفظ | — | **`users.phone`** + metadata `phone` (ليس `engineers`) |
| `governorate` | المحافظة | SingleSelect | لا | — | `governorates` (27 محافظة، value=label، انظر 3.17) | `engineers.governorate` |
| `whats_app` | رقم واتساب | tel | لا | أرقام فقط (نفس التنظيف)، بدون فحص صيغة | — | `engineers.whats_app` |
| `bio` | نبذة مختصرة | textarea | لا | لا حد | — | `engineers.bio` |
| `major` | التخصص | SingleSelect | لا | — | `engineerMajors`: `"معماري"` (label "عمارة")، `"مدني"` (label "مدني") | `engineers.major` |
| `experience_years` | سنوات الخبرة | text numeric | لا | أرقام فقط؛ `parseInt(...)` أو `0` | — | `engineers.experience_years` (int) |
| `skills` | المهارات والبرامج | TagEditor (string[]) | لا | كل tag `trim()`، غير فارغ، بدون تكرار (`ProfileFormHelpers.tsx:168-174`) | حر | `engineers.skills` (array/json) |
| `facebook` | فيسبوك | url input | لا | لا يوجد (لا يوجد form submit) | — | `engineers.facebook` |
| `instagram` | إنستجرام | url input | لا | لا يوجد | — | `engineers.instagram` |
| `youtube` | يوتيوب | url input | لا | لا يوجد | — | `engineers.youtube` |
| `latitude` | خط العرض | text decimal | لا | أرقام عربية→إنجليزية؛ `parseFloat` أو `null`؛ أو زر "جلب الموقع الحالي" (Geolocation، retry لو accuracy > 100m، timeout 15s) | — | `engineers.latitude` |
| `longitude` | خط الطول | text decimal | لا | نفس السابق | — | `engineers.longitude` |
| `profile_image` (مخفي) | — | string | — | يعاد إرسال القيمة المحمّلة من الـ DB كما هي (تُعدّل من الـ sidebar فقط) | — | `engineers.profile_image` |
| `updated_at` (مشتق) | — | timestamp | — | `new Date().toISOString()` | — | `engineers.updated_at` |
| `user_id` (مشتق) | — | uuid | — | من الجلسة؛ مفتاح الـ upsert | — | `engineers.user_id` |

- **قوائم الاختيارات**: `governorate`: 27 محافظة (value = label) من `features/auth/profile/api/profile.static.ts:325` ← `Static/directoryConfig.ts:10-38` (القائمة كاملة في 3.17)؛ `major` (`engineerMajors`, `profile.static.ts:49-52`): `"معماري"` (يُعرض "عمارة") | `"مدني"` (يُعرض "مدني")؛ `skills` حر.
- لا يتم إرسال: `slug`, `city`, `website`, `open_hours`, `video`.
- قسم "التوثيق والتحقق من الشخصية" (`EditEngineerProfile.tsx:428-584`) **معلّق (commented out)**؛ لكن `useUserValidation` ما زال يقرأ `user_validation select * eq user_id` (`features/auth/validation/api/validation.api.ts:51-60`) بدون استخدام في الواجهة.
- **Endpoint مقترح (REST)**: `PUT /api/v1/me/profile` (auth, role `engineer`)
```json
{ "name": "م. أحمد", "phone": "01012345678", "governorate": "القاهرة", "whats_app": "01012345678",
  "bio": "...", "major": "معماري", "experience_years": 10, "skills": ["AutoCAD"],
  "facebook": "https://facebook.com/x", "instagram": null, "youtube": null,
  "latitude": 30.0444, "longitude": 31.2357 }
```
Rules: `name: required|string|max:255` (الكود لا يشترطه)، `phone: nullable|regex:/^01[0125]\d{8}$/` (يُتحقق **قبل** الحفظ)، `governorate: nullable|in:{27 محافظة}`، `whats_app: nullable|digits_between:10,15`، `bio: nullable|string`، `major: nullable|in:معماري,مدني`، `experience_years: nullable|integer|min:0`، `skills: nullable|array`، `skills.*: string|distinct|max:100`، `facebook|instagram|youtube: nullable|url|max:2048`، `latitude: nullable|numeric|between:-90,90`، `longitude: nullable|numeric|between:-180,180`. السيرفر يكتب `phone`/`name` في `users` أيضاً، و`updated_at`. Response: صف البروفايل المحدّث + `user {name, phone}`.

### 3.4.2 تعديل بيانات المكتب — `/dashboard/office/edit-profile` — `features/dashboard/profile/components/EditOfficeProfile.tsx`
- **المستخدم**: `office`. Gating: قسم "فيديو تعريفي للمكتب" مقفول لو `currentPlanId === "basic"` حيث `currentPlanId = user_permissions.plan_type || metadata.selected_plan || "basic"` (`EditOfficeProfile.tsx:48-49, 445`). ملاحظة: `free` **غير مقفول** بهذا الشرط.
- **الإجراء عند الإرسال** (`EditOfficeProfile.tsx:156-210`): يبني `payload` صريح → `updateOfficeProfile(userId, payload)` (`profile.api.ts:112-133`) → `from("offices").upsert({...payload, user_id}, {onConflict:"user_id"})`؛ ثم داخلها `updateUserBasicInfo(userId,{name, phone: undefined})` لأن `payload` لا يحتوي `phone`؛ ثم فحص الهاتف المصري (بعد الحفظ)؛ ثم `updateUserBasicInfo(userId,{name, phone})`. لا يُرسل `updated_at`.
- رفع الفيديو يتم **فوراً عند اختيار الملف** (ليس عند الحفظ): `handleGlobalVideoUpload` (`lib/helper-functions.ts:270-293`): لو `size > 100*1024*1024` → "حجم الفيديو كبير جداً. الحد الأقصى ١٠٠ ميجابايت"، وإلا `uploadToCloudinary(file, "videos")` (resource_type `raw`) → يضع الـ URL في `videoUrl`. زر الحذف يضع `""`.
- **Prefill ملاحظات** (`EditOfficeProfile.tsx:93-110`): `designPhilosophy = data.bio || mock "نؤمن بصنع مساحات..."`، `count_of_projects = data.count_of_projects || "120"`، `open_hours = data.open_hours || "٩ ص - ٥ م"` → قيم mock تُحفظ لو الـ DB فارغ.
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `name` | اسم المكتب | text | لا | لا يوجد | — | `offices.name` + `users.name` + metadata |
| `phone` (غير موجود في payload) | رقم الهاتف | tel | لا | أرقام فقط؛ `isValidEgyptianPhone` لو غير فارغ (بعد الحفظ) | — | `users.phone` + metadata `phone` فقط |
| `governorate` | المحافظة | SingleSelect | لا | — | `governorates` | `offices.governorate` |
| `whats_app` | رقم واتساب | tel | لا | أرقام فقط | — | `offices.whats_app` |
| `experience_years` | سنوات الخبرة | text numeric | لا | أرقام فقط؛ `parseInt` أو `0` | — | `offices.experience_years` |
| `count_of_projects` | عدد المشاريع المنجزة | text numeric | لا | أرقام فقط؛ `parseInt` أو `0` | — | `offices.count_of_projects` |
| `open_hours` | مواعيد العمل | text | لا | لا يوجد (placeholder "مثال: 9 صباحاً - 5 مساءً") | — | `offices.open_hours` |
| UI `designPhilosophy` → `bio` | فلسفة التصميم | textarea (3 rows) | لا | لا يوجد | — | `offices.bio` |
| UI `videoUrl` → `video` | فيديو تعريفي للمكتب | file `video/*` (MP4, WebM حسب النص) | لا | ≤100MB؛ مقفول لباقة `basic` | Cloudinary `videos` | `offices.video` |
| `latitude` | خط العرض | text decimal | لا | `parseFloat` أو `null` | GPS button | `offices.latitude` |
| `longitude` | خط الطول | text decimal | لا | `parseFloat` أو `null` | GPS button | `offices.longitude` |
| `profile_image` (مخفي) | — | string | — | يعاد إرسال قيمة الـ DB | — | `offices.profile_image` |
| `facebook` | فيسبوك | url | لا | لا يوجد؛ يرسل `""` لو فارغ | — | `offices.facebook` |
| `instagram` | إنستجرام | url | لا | نفس | — | `offices.instagram` |
| `youtube` | يوتيوب | url | لا | نفس | — | `offices.youtube` |
| `user_id` (مشتق) | — | uuid | — | مفتاح upsert | — | `offices.user_id` |

- **قوائم الاختيارات**: `governorate`: 27 محافظة (value = label) من `features/auth/profile/api/profile.static.ts:325` ← `Static/directoryConfig.ts:10-38` (القائمة كاملة في 3.17). لا توجد selects أخرى.
- لا يتم إرسال: `slug`, `office_name`, `skills`, `city`, `website`.
- قسم التوثيق (اسم السجل التجاري، رقم السجل التجاري، رقم البطاقة الضريبية، صورتين → `user_validation`) **معلّق** (`EditOfficeProfile.tsx:632-854`).
- **Endpoint مقترح (REST)**: `PUT /api/v1/me/profile` (role `office`)
```json
{ "name": "مكتب البنيان", "phone": "01012345678", "governorate": "القاهرة", "whats_app": "01012345678",
  "experience_years": 15, "count_of_projects": 120, "open_hours": "9 ص - 5 م", "bio": "...",
  "video": "https://res.cloudinary.com/.../raw/upload/....mp4", "latitude": 30.01, "longitude": 31.42,
  "facebook": "", "instagram": "", "youtube": "" }
```
Rules: `name: required|string|max:255`، `phone: nullable|regex:/^01[0125]\d{8}$/`، `governorate: nullable|in:...`، `whats_app: nullable|digits_between:10,15`، `experience_years: nullable|integer|min:0`، `count_of_projects: nullable|integer|min:0`، `open_hours: nullable|string|max:255`، `bio: nullable|string`، `video: nullable|url|max:2048` + **server-side**: يرفض `video` لو plan = `basic` (أو الأفضل: لو `user_permissions.video_projects = false`)، `latitude/longitude: nullable|numeric|between`، `facebook|instagram|youtube: nullable|url`.

### 3.4.3 تعديل بيانات المعرض — `/dashboard/exhibition/edit-profile` — `features/dashboard/profile/components/EditExhibitionProfile.tsx`
- **المستخدم**: `exhibition`. لا يوجد gating بالباقة.
- **الإجراء عند الإرسال** (`EditExhibitionProfile.tsx:159-268`):
  1. validation client-side بالترتيب: `name.trim()` غير فارغ ("يرجى إدخال اسم المعرض")، `main_category.trim()` ("يرجى اختيار التصنيف الرئيسي للمعرض")، `governorate.trim()` ("يرجى اختيار المحافظة").
  2. لو تم اختيار logo جديد → `uploadToCloudinary(logoFile, "exhibitions/logos")`؛ لو banner جديد → `uploadToCloudinary(bannerFile, "exhibitions/banners")` (الرفع عند الحفظ).
  3. `main_category` يُحوّل للـ label العربي: `exhibitionCategories.find(c => c.value === v || c.label === v)?.label || v`.
  4. فحص الهاتف المصري **قبل** الحفظ (هنا فقط).
  5. `updateExhibitionProfile(userId, payload)` (`profile.api.ts:135-195`) → `from("exhibitions").upsert({...updateData, user_id}, {onConflict:"user_id"})` (الأعمدة أدناه، مع إعادة تحويل الأرقام: `experience_years` int أو 0، `count_of_categories` int أو null، `latitude/longitude` float أو null، الـ strings الفارغة → `null`، `brands` array أو `[]`، `updated_at: now`).
  6. داخلها: لو `name || phone` → `updateUserBasicInfo`؛ ثم مرة ثانية من الـ component.
- الفيديو: إما رابط نصي (YouTube/Vimeo/MP4) أو رفع ملف فوري بـ `handleGlobalVideoUpload` (≤100MB، folder `videos`).
- **Prefill ملاحظات** (`EditExhibitionProfile.tsx:76-106`): fallbacks mock تُحفظ: `main_category → "الأثاث والفرش"`، `governorate → "القاهرة"`، `experience_years → 8`، `bio`, `open_hours → "١٠ ص - ٥ م"`، `count_of_categories → 4`، `logo → "/images/exhibition-logo.jpg"`، `banner → "/images/exhibition-cover.jpg"`، `brands → ["Elaraby","Shata","Hub Furniture"]`، `latitude/longitude → 29.97/30.93`، `slug → "al-nour-furniture"` (لو لا DB ولا metadata). `logo` يقرأ أيضاً `data.profile_image` و`banner` يقرأ `data.coverImage` كـ fallback.
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `banner` | صورة الغلاف / البانر | file `image/*` | لا | لا فحص حجم | Cloudinary `exhibitions/banners` (عند الحفظ) | `exhibitions.banner` (URL أو null) |
| `logo` | شعار المعرض | file `image/*` | لا | لا فحص حجم | Cloudinary `exhibitions/logos` | `exhibitions.logo` |
| `name` | اسم المعرض * | text | **نعم** | `trim()` غير فارغ | — | `exhibitions.name` (trimmed) + `users.name` + metadata |
| `main_category` | التصنيف الرئيسي * | SingleSelect | **نعم** | غير فارغ؛ يُخزن الـ **label العربي** | `exhibitionCategories` = `showroomSupplierCategories` (23 قيمة، 3.17) | `exhibitions.main_category` |
| `bio` | نبذة تعريفية | textarea | لا | `maxLength=500` (HTML)؛ `trim()` أو null | — | `exhibitions.bio` |
| `phone` | رقم الهاتف | tel | لا | أرقام فقط؛ `isValidEgyptianPhone` لو غير فارغ (قبل الحفظ) | — | `users.phone` + metadata فقط (ليس `exhibitions`) |
| `whats_app` | رقم الواتساب | tel | لا | أرقام فقط؛ `trim()` أو null | — | `exhibitions.whats_app` |
| `governorate` | المحافظة * | SingleSelect | **نعم** | غير فارغ | `governorates` | `exhibitions.governorate` |
| `open_hours` | ساعات / مواعيد العمل | text | لا | `trim()` أو null | — | `exhibitions.open_hours` |
| `experience_years` | سنوات الخبرة | text numeric | لا | أرقام فقط؛ `parseInt` أو `0` | — | `exhibitions.experience_years` |
| `count_of_categories` | عدد الأقسام / الفئات | text numeric | لا | أرقام فقط؛ `parseInt` أو `null` | — | `exhibitions.count_of_categories` |
| `brands` | العلامات التجارية والتوكيلات | TagEditor string[] | لا | trim، غير فارغ، بدون تكرار | حر | `exhibitions.brands` (jsonb) |
| `video` | رابط الفيديو / رفع ملف | url text أو file `video/*` | لا | رفع ≤100MB؛ `trim()` أو null | Cloudinary `videos` | `exhibitions.video` |
| `facebook` | فيسبوك | url | لا | `trim()` أو null | — | `exhibitions.facebook` |
| `instagram` | انستجرام | url | لا | `trim()` أو null | — | `exhibitions.instagram` |
| `youtube` | يوتيوب | url | لا | `trim()` أو null | — | `exhibitions.youtube` |
| `latitude` | خط العرض | text decimal | لا | `parseFloat` أو null | GPS | `exhibitions.latitude` |
| `longitude` | خط الطول | text decimal | لا | `parseFloat` أو null | GPS | `exhibitions.longitude` |
| `slug` (مخفي) | — | string | — | `data.slug \|\| metadata.slug`؛ `trim()` أو null | — | `exhibitions.slug` |
| `updated_at` (مشتق) | — | timestamp | — | now | — | `exhibitions.updated_at` |
| `user_id` (مشتق) | — | uuid | — | مفتاح upsert | — | `exhibitions.user_id` |

- **قوائم الاختيارات**: `main_category` (`exhibitionCategories` = `showroomSupplierCategories`, `profile.static.ts:123-149, 204`): 23 قيمة value = label — مواد البناء الأساسية، الحديد والمعادن، الخرسانة والإنشاءات، العزل، السباكة والمواسير، الكهرباء، الأخشاب، الجبس والأسقف، مواد الترميم والكيماويات، العدد والمعدات، الأثاث والفرش، السيراميك والبورسلين، الأدوات الصحية، الإضاءة، الدهانات والديكور، الأبواب والشبابيك، الرخام والجرانيت، الواجهات الخارجية، اللاندسكيب، المطابخ والدريسنج، التكييف والتهوية، الطاقة الشمسية، الأمن والسلامة؛ `governorate`: 27 محافظة (value = label) من `features/auth/profile/api/profile.static.ts:325` ← `Static/directoryConfig.ts:10-38` (القائمة كاملة في 3.17)؛ `brands` حر.
- **Endpoint مقترح (REST)**: `PUT /api/v1/me/profile` (role `exhibition`) — JSON (URLs بعد الرفع) أو multipart (`logo_file`, `banner_file`, `video_file` + باقي الحقول):
```json
{ "name": "معرض النور", "main_category": "الأثاث والفرش", "governorate": "الجيزة", "bio": "...",
  "phone": "01012345678", "whats_app": "01012345678", "open_hours": "10 ص - 10 م",
  "experience_years": 8, "count_of_categories": 4, "brands": ["Elaraby"],
  "logo": "https://...", "banner": "https://...", "video": "https://youtube.com/watch?v=...",
  "facebook": null, "instagram": null, "youtube": null, "latitude": 29.97, "longitude": 30.93 }
```
Rules: `name: required|string|max:255`، `main_category: required|in:{23 تصنيف}`، `governorate: required|in:{27}`، `bio: nullable|string|max:500`، `phone: nullable|regex:/^01[0125]\d{8}$/`، `whats_app: nullable|digits_between:10,15`، `open_hours: nullable|string|max:255`، `experience_years: nullable|integer|min:0`، `count_of_categories: nullable|integer|min:0`، `brands: nullable|array`، `brands.*: string|distinct|max:100`، `logo|banner|video: nullable|url|max:2048`، social: `nullable|url`، `latitude/longitude: nullable|numeric|between`. `slug` لا يُقبل من الـ client (يُدار server-side).

### 3.4.4 تعديل بيانات المورد — `/dashboard/material/edit-profile` — `features/dashboard/profile/components/EditMaterialProfile.tsx`

> ⛔ **مش هيتعمل في Laravel:** المورد = `exhibition` (القسم 0.5). الوصف تحت للكود الحالي بس (مرجع لنقل البيانات).
- **المستخدم**: `material`. Gating: `MultiSelect` "المنتجات والخدمات" محدود بـ `maxServices` (`EditMaterialProfile.tsx:52-64`):
  - لو `user_permissions.max_services` = null/undefined → hardcoded fallback: `premium` أو `free` → ∞، `professional` → 3، غير ذلك (`basic`) → 1.
  - غير ذلك: `-1` أو `>= 99999` → ∞، وإلا القيمة.
  - `currentPlanId = user_permissions.plan_type || metadata.selected_plan || "basic"`. لو الاختيار يتجاوز الحد → warning "باقتك {اسم} تسمح بـ {n} خدمة فقط." ولا يُطبق التغيير.
- **الإجراء عند الإرسال** (`EditMaterialProfile.tsx:122-162`): `upsertProfileData("material", {...common, user_id, experience_years: parseInt||0, latitude: parseFloat||null, longitude: parseFloat||null})` (`profile.api.ts:210-227`) → `from("materials").upsert(<كل الـ state>, {onConflict:"user_id"})` → **يرسل الـ object كامل** بما فيه مفاتيح mock وحقول ليست أعمدة على الأرجح (فورم الـ onboarding `features/auth/profile/api/profile.api.ts:330-364` يكتب فقط: `user_id, name, governorate, city, bio, whats_app, experience_years, latitude, longitude, profile_image, count_of_categories, open_hours, main_category`). لو الأعمدة الإضافية غير موجودة فإن PostgREST يرفض الـ upsert بالكامل → هذا الفورم غالباً **لا يحفظ** في الإنتاج (يجب التحقق من الـ schema الحقيقي). لو لا يوجد صف في الـ DB يُرسل `id: "mat-101"` (mock). ثم فحص الهاتف (بعد الحفظ) ثم `updateUserBasicInfo({name, phone})`. لا يوجد رفع صور في هذا الفورم (الـ cover والـ logo للعرض فقط).
- **حقول الفورم** (المرئية):

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `name` | الاسم | text | لا | لا يوجد | — | `materials.name` + `users.name` + metadata |
| `bio` | نبذة تعريفية | text (سطر واحد) | لا | لا يوجد | — | `materials.bio` |
| `phone` | رقم الهاتف | tel | لا | أرقام فقط؛ `isValidEgyptianPhone` بعد الحفظ | — | يُرسل أيضاً داخل الـ upsert كـ `materials.phone` + `users.phone` + metadata |
| `website` | الموقع الإلكتروني | url | لا | لا يوجد | — | `materials.website` (?) |
| `governorate` | المحافظة | SingleSelect | لا | — | `governorates` | `materials.governorate` |
| `city` | المدينة | text | لا | لا يوجد | — | `materials.city` |
| `whats_app` | رقم واتساب | tel | لا | أرقام فقط | — | `materials.whats_app` |
| `specializations` | المنتجات والخدمات | MultiSelect (string[] من الـ values) | لا | `length <= maxServices` | `materialServices` (values: `site-delivery`, `wholesale-prices`, `payment-plans`, `free-samples`, `technical-support`, `quality-certificates`, `return-policy`, `supply-contracts`) | `materials.specializations` (?) |
| `experience_years` | سنوات الخبرة | text numeric | لا | أرقام فقط؛ `parseInt` أو 0 | — | `materials.experience_years` |
| `address` | العنوان المفصل | text | لا | لا يوجد | — | `materials.address` (?) |
| `priceList` | قائمة الأسعار | ListEditor `[{material, price}]` (strings) | لا | لا يوجد (يقبل صفوف فارغة) | — | `materials.priceList` (?) |
| `quantities` | الكميات المتاحة | ListEditor `[{material, quantity}]` | لا | لا يوجد | — | `materials.quantities` (?) |
| `certificates` | شهادات الجودة | TagEditor string[] | لا | trim/unique | — | `materials.certificates` (?) |
| `deliveryAreas` | مناطق التوصيل | TagEditor string[] | لا | trim/unique | — | `materials.deliveryAreas` (?) |
| `truckTypes` | أنواع السيارات | TagEditor string[] | لا | trim/unique | — | `materials.truckTypes` (?) |
| `deliveryCost` | تكلفة التوصيل | text | لا | لا يوجد | — | `materials.deliveryCost` (?) |
| `latitude` / `longitude` | خط العرض / الطول | text decimal | لا | `parseFloat` أو null؛ GPS | — | `materials.latitude/longitude` |
| `user_id` (مشتق) | — | uuid | — | مفتاح upsert | — | `materials.user_id` |

- **قوائم الاختيارات**: `governorate`: 27 محافظة (value = label) من `features/auth/profile/api/profile.static.ts:325` ← `Static/directoryConfig.ts:10-38` (القائمة كاملة في 3.17)؛ `specializations` (`materialServices`, `profile.static.ts:312-321`، المخزّن = value): `site-delivery` (توصيل للموقع)، `wholesale-prices` (أسعار جملة)، `payment-plans` (تسهيلات سداد)، `free-samples` (عينات مجانية)، `technical-support` (دعم فني)، `quality-certificates` (شهادات جودة)، `return-policy` (سياسة إرجاع)، `supply-contracts` (عقود توريد).
- **مفاتيح مخفية تُرسل أيضاً** (من الـ mock state أو من `select *`): `id`, `email` (= auth email), `open_hours`, `profile_image`, `coverImage`, `social` `{facebook, instagram, youtube, linkedin, behance, twitter}`, `skills`, `videoUrl`, `mainCategories`, `deliveryAvailable`, `materials` `[{name, description}]`, + كل أعمدة الصف الحالي (مثلاً `slug`, `main_category`, `count_of_categories`, `created_at`, `updated_at`, `facebook`...). (?) = غير مؤكد وجوده كعمود.
- **Endpoint مقترح (REST)**: ⛔ مش هيتعمل. المورد بيستخدم `PUT /api/v1/me/profile` بحقول المعرض (3.4.3). الـ payload القديم تحت **مرجع بس** للحقول الخاصة بالمورد اللي اتقرر تتشال (0.5، نقل البيانات #3):
```json
{ "name": "شركة السلام", "bio": "...", "phone": "01234567890", "website": "https://...", "governorate": "الإسكندرية",
  "city": "سموحة", "whats_app": "01234567890", "specializations": ["site-delivery"], "experience_years": 20,
  "address": "...", "price_list": [{"material":"طوب طفلي","price":"5000"}],
  "quantities": [{"material":"حديد عز","quantity":"500 طن"}], "certificates": ["ISO 9001"],
  "delivery_areas": ["الإسكندرية"], "truck_types": ["نقل ثقيل"], "delivery_cost": "حسب المسافة",
  "latitude": 31.21, "longitude": 29.94 }
```
Rules: `name: required|string|max:255`، `phone: nullable|regex:/^01[0125]\d{8}$/`، `website: nullable|url`، `governorate: nullable|in:...`، `city|address|delivery_cost: nullable|string|max:255`، `whats_app: nullable|digits_between:10,15`، `specializations: nullable|array|max:{maxServices}` + `specializations.*: in:site-delivery,wholesale-prices,payment-plans,free-samples,technical-support,quality-certificates,return-policy,supply-contracts`، `experience_years: nullable|integer|min:0`، `price_list: nullable|array`، `price_list.*.material: required_with:price_list|string`، `price_list.*.price: nullable|string`، `quantities.*.material|quantity: string`، `certificates|delivery_areas|truck_types: nullable|array`، `*.*: string|distinct`، `latitude/longitude: nullable|numeric|between`. يُنصح بتخزين القوائم كأعمدة `jsonb` بأسماء snake_case.

### 3.4.5 تغيير كلمة المرور — `/dashboard/{engineer|office|exhibition}/edit-profile` — `features/dashboard/profile/components/Edit{Engineer|Office|Exhibition}Profile.tsx`
- **المستخدم**: `engineer`, `office`, `exhibition` (`EditEngineerProfile.tsx:188-207`, `EditOfficeProfile.tsx:212-231`, `EditExhibitionProfile.tsx:270-289`). `EditMaterialProfile` لا يحتوي القسم.
- **الإجراء عند الإرسال**: `updateUserPassword(password)` (`profile.api.ts:197-208`) → `supabase.auth.updateUser({ password })`. لا يُطلب كلمة المرور الحالية.
- **حقول الفورم**:

| key | الاسم في الواجهة | type | required | validation (exact) | → |
|---|---|---|---|---|---|
| `password` (UI `newPassword`) | كلمة المرور الجديدة | password | نعم | "يرجى ملء جميع حقول كلمة المرور" لو أي حقل فارغ؛ `length >= 6` ("يجب أن تكون كلمة المرور 6 أحرف على الأقل") | `auth.users.password` |
| `password_confirmation` (UI `confirmPassword`) — لا يُرسل | تأكيد كلمة المرور | password | نعم | `=== newPassword` ("كلمتا المرور غير متطابقتين") | — |

- **قوائم الاختيارات**: لا يوجد.
- **Endpoint مقترح (REST)**: `PUT /api/v1/me/password` (auth) — `{ "password": "secret123", "password_confirmation": "secret123" }` — rules: `password: required|string|min:6|confirmed` (يُفضّل إضافة `current_password: required|current_password`). Response `204`.

---

### 3.5 المشاريع — `/dashboard/{engineer|office}/projects` — `features/dashboard/projects/components/DashboardProjects.tsx`

#### قراءة القائمة
- `useUserProjects(userId)` → `getUserProjects` (`projects.api.ts:38-47`): `from("projects").select("*").eq("user_id", userId).order("created_at", {ascending:false})`. بدون pagination.
- الأعمدة المستخدمة: `id, name, image, type, created_at, location, area, details{video, brief, categories, photos}`.
- + `useQueryUserProfile` (لـ `slug`, `name`) + `useUserPermissions` (`max_projects`, `plan_type`, `video_projects`).
- **Endpoint مقترح (REST)**: `GET /api/v1/me/projects` → `{ "data": [ {id, name, location, area, type, image, slug, user_type, user_name, views, created_at, details:{video, brief, categories, photos}} ], "meta": { "count": 4, "limit": 10, "remaining": 6, "plan_type": "professional" } }`.

### إضافة / تعديل مشروع — `/dashboard/{engineer|office}/projects` — `features/dashboard/projects/components/DashboardProjects.tsx`
- **المستخدم**: `engineer`, `office` (و`customer` بالـ URL). Gating:
  - حد العدد: `max_projects` (قاعدة التطبيع). زر "إضافة مشروع" disabled لو `projects.length >= limit` (`DashboardProjects.tsx:51-59, 99-100, 258`). التعديل غير محدود.
  - حقل الفيديو يظهر فقط لو `user_permissions.video_projects` (`DashboardProjects.tsx:660`).
- **الإجراء عند الإرسال** (`DashboardProjects.tsx:207-289`):
  1. لو `!isFormValid` (`name && location && area && type`، السطر 96-97) → لا شيء.
  2. صورة رئيسية جديدة → `uploadToCloudinary(file, "projects")`؛ الصور الإضافية الجديدة → `Promise.all(uploadToCloudinary(f, "projects"))` وتُلحق بالموجودة؛ فيديو جديد → `uploadToCloudinary(video, "projects")` (resource_type `raw`).
  3. إضافة: `addProject` (`projects.api.ts:62-71`) → `from("projects").insert({user_id, name, location, area: Number, slug, type, image, user_type, user_name, details}).select().single()`.
  4. تعديل: `updateProject(id, ...)` (`projects.api.ts:74-84`) → `from("projects").update({name, location, area, type, image, slug, user_name, details}).eq("id", id).select().single()` (بدون فحص ملكية client-side؛ يعتمد على RLS).
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `image` | صورة المشروع | file `image/*` | **لا** (ليست ضمن `isFormValid`) | لا فحص حجم (النص "JPG, PNG - بحد أقصى 5 ميجا") | Cloudinary `projects` | `projects.image` (URL أو `""` لو لم تُرفع) |
| `name` | اسم المشروع * | text | نعم | truthy | — | `projects.name` |
| `location` | الموقع * | text | نعم | truthy (حر، مثال "التجمع الخامس") | — | `projects.location` |
| `area` | المساحة * | text numeric | نعم | أرقام فقط (`toEnglishDigits` + حذف غير الأرقام)؛ truthy أي `> 0`؛ يُرسل `Number(area)` | — | `projects.area` (number) |
| `type` | القسم الرئيسي * | أزرار اختيار واحد | نعم | قيمة واحدة | `projectTypes` labels (16 قيمة، 3.17) — يُخزن الـ **label** | `projects.type` |
| `details.categories` | أقسام أخرى تم العمل عليها | أزرار multi-toggle | لا | string[] | `projectTypes` labels | `projects.details->categories` |
| `details.video` | فيديو المشروع (تحميل أو رابط) | file `video/*` أو url text | لا | يظهر فقط لو `video_projects`؛ لا فحص حجم (النص "MP4, WebM - بحد أقصى 50 ميجا") | Cloudinary `projects` (raw) أو رابط YouTube/Cloudinary | `projects.details->video` (`""` لو فارغ) |
| `details.brief` | وصف مختصر للمشروع (نبذة) | textarea | لا | لا يوجد | — | `projects.details->brief` (`""` لو فارغ) |
| `details.photos` | صور إضافية للمشروع | file `image/*` multiple | لا | لا حد للعدد أو الحجم؛ يمكن حذف صور موجودة | Cloudinary `projects` | `projects.details->photos` (string[]) |
| `user_id` (مشتق، إضافة فقط) | — | uuid | — | من الجلسة | — | `projects.user_id` |
| `slug` (مشتق) | — | string | — | `profile.slug \|\| metadata.slug \|\| ""` | — | `projects.slug` |
| `user_type` (مشتق، إضافة فقط) | — | string | — | route type (`engineer`/`office`) | — | `projects.user_type` |
| `user_name` (مشتق) | — | string | — | `profile.name \|\| metadata.name \|\| ""` | — | `projects.user_name` |
| `views` | — | — | — | لا يُرسل (يُقرأ فقط) | — | `projects.views` |

- **قوائم الاختيارات**: `type` و`details.categories` من `projectTypes` (= `engineerSpecializations`, `profile.static.ts:54-118`)، المخزّن = الـ label: تصميم معماري، حصر كميات، رسومات تنفيذية، إدارة مشاريع، تصميم داخلي، تشطيبات، استشارات هندسية، تراخيص بناء، رفع مساحة، تصالح للبناء، تصميم انشائي، تصميم واجهات، اعمال عزل، اعمال خرسانات، اعمال ترميم، خدمات نقابية.
- **Endpoint مقترح (REST)**:
  - `POST /api/v1/me/projects` (auth, role `engineer|office`) — multipart أو JSON:
```json
{ "name": "فيلا سكنية مودرن", "location": "التجمع الخامس", "area": 600, "type": "تصميم معماري",
  "image": "https://...", "details": { "categories": ["تصميم داخلي"], "video": "", "brief": "...", "photos": ["https://..."] } }
```
Rules: `name: required|string|max:255`، `location: required|string|max:255`، `area: required|integer|min:1`، `type: required|in:{16 label}`، `image: nullable|url|max:2048`، `details: nullable|array`، `details.categories: nullable|array`، `details.categories.*: in:{16 label}|distinct`، `details.video: nullable|url` + **prohibited** لو `!video_projects`، `details.brief: nullable|string`، `details.photos: nullable|array`، `details.photos.*: url`. Server: يرفض بـ `403/422` لو `count(projects) >= max_projects`؛ يملأ `user_id, slug, user_type, user_name` من السيرفر. Response `201` بالصف.
  - `PUT /api/v1/me/projects/{id}` — نفس الـ body/rules (ownership check). Response `200`.
  - `DELETE /api/v1/me/projects/{id}` — 3.5.1.

### 3.5.1 حذف مشروع — `/dashboard/{engineer|office}/projects` — `features/dashboard/projects/components/DashboardProjects.tsx`
- **المستخدم**: `engineer`, `office` (أيقونة الحذف على بطاقة المشروع). لا gating.
- **الإجراء عند الإرسال**: `confirmSwal("هل أنت متأكد؟","لن تتمكن من استعادة هذا المشروع بعد الحذف")` → `deleteProject(id)` (`projects.api.ts:87-95`): `from("projects").delete().eq("id", id)` (بدون فحص ملكية client-side؛ لا يحذف صور Cloudinary).
- **حقول الفورم**: `id` (path) فقط.
- **قوائم الاختيارات**: لا يوجد.
- **Endpoint مقترح (REST)**: `DELETE /api/v1/me/projects/{id}` (ownership) → `204`.

---

### 3.6 المنتجات — `/dashboard/{exhibition|material}/products` — `features/dashboard/products/components/DashboardProducts.tsx`

#### قراءة القائمة
- `getUserProducts` (`products.api.ts:20-29`): `from("products").select("*").eq("user_id", userId).order("created_at",{ascending:false})`. الأعمدة المستخدمة: `id, name, image, price, category, sub_category, created_at, details{description, photos}`.
- **Endpoint مقترح (REST)**: `GET /api/v1/me/products` → `{ "data": [...], "meta": { "count", "limit", "remaining", "plan_type" } }`.

### إضافة / تعديل / حذف منتج — `/dashboard/{exhibition|material}/products` — `features/dashboard/products/components/DashboardProducts.tsx`
- **المستخدم**: `exhibition`, `material`. Gating: حد العدد `user_permissions.max_projects` (نفس عمود المشاريع — `DashboardProducts.tsx:50-61`)، زر الإضافة disabled عند الوصول للحد.
- **الإجراء عند الإرسال** (`DashboardProducts.tsx:179-248`): `isFormValid = name && category && sub_category && image && price !== undefined && price >= 0` (السطر 90-96). صورة جديدة → `uploadToCloudinary(file,"products")`؛ صور إضافية → `Promise.all(... "products")`. إضافة: `addProduct` (`products.api.ts:32-41`) → `from("products").insert({user_id, name, category, sub_category, image, price: Number, slug, details}).select().single()`. تعديل: `updateProduct(id, {name, category, sub_category, image, price, slug, details})` → `.update(...).eq("id", id)`.
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `image` | صورة المنتج الأساسية * | file `image/*` | **نعم** | لا فحص حجم (النص "JPG, PNG - بحد أقصى 5 ميجا") | Cloudinary `products` | `products.image` |
| `name` | اسم المنتج * | text | نعم | truthy | — | `products.name` |
| `price` | السعر (بالجنيه المصري) * | text numeric | نعم | أرقام صحيحة فقط (`replace(/[^0-9]/g,"")`)، `>= 0` (0 مقبول)؛ `Number(price)` | — | `products.price` (number) |
| `category` | القسم الرئيسي * | `<select>` | نعم | — | `showroomSupplierCategories` — يُخزن الـ **label** (23 قيمة، 3.17) | `products.category` |
| `sub_category` | القسم الفرعي * | text حر | نعم | truthy (placeholder "مثال: أسمنت بورتلاندي")؛ **ليس** select رغم وجود `showroomSupplierSubCategories` | — | `products.sub_category` |
| `details.description` | وصف المنتج | textarea | لا | — | — | `products.details->description` (`""` لو فارغ) |
| `details.photos` | صور إضافية للمنتج | file `image/*` multiple | لا | لا حد | Cloudinary `products` | `products.details->photos` |
| `user_id` (إضافة فقط) | — | uuid | — | — | — | `products.user_id` |
| `slug` (مشتق) | — | string | — | `profile.slug \|\| metadata.slug \|\| ""` | — | `products.slug` |

- **قوائم الاختيارات**: `category` من `showroomSupplierCategories` (`<option value={c.label}>`، `DashboardProducts.tsx:529-533`) — نفس الـ 23 قيمة (3.17)؛ `sub_category` نص حر (قائمة `showroomSupplierSubCategories` مستوردة لكن غير مستخدمة في هذا الفورم).
- **Endpoint مقترح (REST)**:
  - `POST /api/v1/me/products` (role `exhibition`):
```json
{ "name": "سرير أندلسي", "price": 12000, "category": "الأثاث والفرش", "sub_category": "غرف نوم",
  "image": "https://...", "details": { "description": "...", "photos": ["https://..."] } }
```
Rules: `name: required|string|max:255`، `price: required|integer|min:0` (الكود يقبل أرقاماً صحيحة فقط)، `category: required|in:{23 label}`، `sub_category: required|string|max:255`، `image: required|url|max:2048`، `details.description: nullable|string`، `details.photos: nullable|array`، `details.photos.*: url`. Server: limit `max_projects`؛ يملأ `user_id`, `slug`.
  - `PUT /api/v1/me/products/{id}` — نفس القواعد.
  - `DELETE /api/v1/me/products/{id}` — حذف (`products.api.ts:57-65`: `.delete().eq("id", id)` بعد `confirmSwal("هل أنت متأكد؟","لن تتمكن من استعادة هذا المنتج بعد الحذف")`) → `204`.

---

### 3.7 الخدمات / التخصصات / تصنيفات المنتجات — `/dashboard/{engineer|office|exhibition|material}/services` — `features/dashboard/services/components/DashboardServices.tsx`

العناوين حسب النوع (`features/dashboard/static.ts:375-472`): engineer "خدماتي الهندسية"، office "تخصصات المكتب"، exhibition/material "تصنيفات المنتجات".

#### قراءات
- خدمات المستخدم: `getUserServices(userId, slug?)` (`services.api.ts:14-29`): لو `exhibition|material` → `.eq("slug", user_metadata.slug)`، وإلا `.eq("user_id", userId)`؛ `order created_at asc`. الأعمدة: `id, name, created_at` (الأيقونة واللون يُؤخذان من الكتالوج وليس من الصف؛ أي خدمة اسمها غير موجود في الكتالوج **لا تُعرض** — `DashboardServices.tsx:443-447`).
- الكتالوج من DB: `getAllUniqueDbServices` (`services.api.ts:62-78`): `from("services").select("name, icon, color")` لكل المستخدمين، dedupe بالاسم client-side.
- `useQueryUserProfile` (لـ `main_category` — غير مستخدم فعلياً بعد ذلك) + `useUserPermissions` (`max_services`, `plan_type`).
- **الكتالوج المتاح للإضافة** (`DashboardServices.tsx:53-107`):
  - `engineer` و`office`: `engineerSpecializations` (16 خدمة؛ `office` يستخدم نفس القائمة وليس `officeSpecializations`) → `{name: label, icon, color}`.
  - `exhibition`: كل الأقسام الفرعية الفريدة من `showroomSupplierSubCategories` (icon `solar:double-alt-arrow-right-bold`، color = لون القسم الرئيسي أو `#FC7753`) + `exhibitionServices` (8، icon `solar:double-alt-arrow-right-bold`، color `#FC7753`) + أي أسماء مخصصة موجودة في `services` لأي مستخدم.
  - `material`: الأقسام الفرعية + الأسماء المخصصة من DB (بدون `exhibitionServices`).

### إضافة خدمة من الكتالوج / تصنيف مخصص / حذف — `/dashboard/{engineer|office|exhibition|material}/services` — `features/dashboard/services/components/DashboardServices.tsx`
- **المستخدم**: `engineer`, `office`, `exhibition`, `material`. Gating: `max_services` (قاعدة التطبيع)؛ زر الإضافة disabled عند `userServices.length >= limit`.
- **الإجراء — إضافة من الكتالوج** (`DashboardServices.tsx:137-161`): لو at-limit أو الاسم موجود مسبقاً → no-op. `addUserService` (`services.api.ts:32-41`) → `from("services").insert({user_id, name, icon, color: color || "#0070d7", slug: user_metadata.slug}).select().single()`.
- **الإجراء — تصنيف مخصص** (`exhibition|material` فقط، `DashboardServices.tsx:163-183, 332-366`): `insert({user_id, name: customInput.trim(), icon: "solar:double-alt-arrow-right-bold", color: "#FC7753", slug})`.
- **الإجراء — حذف** (`DashboardServices.tsx:185-205` → `services.api.ts:44-59`): `confirmSwal` → `from("services").delete().match({name, slug})` (exhibition/material) أو `.match({name, user_id})` (غيرهم).
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `name` | اختيار من الشبكة / "إضافة تصنيف مخصص" | button / text | نعم | من الكتالوج: يجب أن يكون في القائمة؛ مخصص: `trim()` غير فارغ؛ لا تكرار لنفس المستخدم | الكتالوج أعلاه (3.17) | `services.name` |
| `icon` (مشتق) | — | string | — | من الكتالوج، أو `solar:double-alt-arrow-right-bold` للمخصص | — | `services.icon` |
| `color` (مشتق) | — | hex | — | من الكتالوج أو `#0070d7`؛ للمخصص `#FC7753` | — | `services.color` |
| `slug` (مشتق) | — | string | — | `user_metadata.slug` (ليس `profile.slug`) | — | `services.slug` |
| `user_id` (مشتق) | — | uuid | — | — | — | `services.user_id` |
| (بحث) | ابحث عن تصنيف فرعي... | text | — | فلترة client-side فقط، لا تُرسل | — | — |

- **قوائم الاختيارات**: الكتالوج حسب النوع — `engineer`/`office`: `engineerSpecializations` (16 label + icon + color، 3.17)؛ `exhibition`: كل أسماء `showroomSupplierSubCategories` الفريدة (3.17) + `exhibitionServices` (توصيل وشحن، تركيب، استشارات تصميم، تصنيع حسب الطلب، ضمان على المنتجات، بيع بالجملة، معاينة وقياسات، خدمة ما بعد البيع) + الأسماء المخصصة في DB؛ `material`: الأقسام الفرعية + الأسماء المخصصة.
- **Endpoint مقترح (REST)**:
  - `GET /api/v1/me/services` → `{ "data": [ {id, name, icon, color, created_at} ], "meta": { "count", "limit", "remaining", "plan_type" } }`.
  - `GET /api/v1/services/catalog?user_type=exhibition` → `[ {name, icon, color, group: "static|exhibition_service|custom"} ]` (الـ static + `SELECT DISTINCT name` من `services`).
  - `POST /api/v1/me/services` — `{ "name": "غرف نوم", "custom": false }` — rules: `name: required|string|max:150`، `custom: boolean`؛ لو `custom=false` → `in:{الكتالوج}` (السيرفر يحدد `icon`/`color`)؛ `custom=true` مسموح فقط لـ `exhibition|material`؛ unique per user (`Rule::unique('services')->where('user_id', ...)`)؛ limit `max_services`. Response `201`.
  - `DELETE /api/v1/me/services/{id}` → `204` (بالـ id بدلاً من الاسم).

---

### 3.8 الباقات والاشتراك — `/dashboard/{type}/plans` — `features/dashboard/plans/components/DashboardPlans.tsx`

#### قراءات
- `useSubscription` → `subscriptions` (3.3.1). الحقول المستخدمة: `plan_id(plan_type)`, `status`, `billing_cycle`, `plan_price`, `expires_at`, `confirmation`.
- `usePlanPrices(userType, cycle)` → `getPlansByConfig` (`features/auth/pricing/api/pricing.api.ts:66-75`): `from("plans").select("plan_id:plan_type, price, original_price").eq("user_type", userType).eq("billing_cycle", cycle)` — يُستدعى مرتين: مرة بـ `currentSubCycle` ومرة بـ `catalogCycle`.
- `PRICING_DATA[userType]` (`features/auth/pricing/api/pricing-static.ts`): أسماء ومزايا الباقات (`basic`, `professional`, `premium`) — **لا يحتوي أسعار** (`price` undefined)، فالـ fallback يعرض "...".
- عرض الحالة: `active` → "نشط"، `expired` → "الاشتراك منتهي"، `pending` → "قيد المراجعة"، غير ذلك → "غير نشط". تاريخ الانتهاء: `expires_at` (ar-EG) أو "مدى الحياة" لو plan `free`، وإلا قيمة default ثابتة `"2024-04-20"` (`DashboardPlans.tsx:38, 45-49`).
- **دورات الدفع**: `engineer`/`office` (`isQuarterBased`): `quarter`, `half`, `yearly` (أي اختيار `monthly` عند الترقية يُحوّل إلى `quarter`، `DashboardPlans.tsx:140-148`)؛ باقي الأنواع: `monthly`, `half`, `yearly`.
- **Fallback الأسعار** (لو لا يوجد صف في `plans`): `yearly = round(price*12*0.8)`, `half = round(price*6*0.9)`, `quarter = round(price*3)`, `monthly = price`.
- ترتيب الباقات: `["basic","professional","premium"]`؛ "ترقية" لو الباقة أعلى أو نفس الباقة بدورة أطول؛ `free` يعامل كأقل من `basic`.
- **Endpoint مقترح (REST)**:
  - `GET /api/v1/me/subscription` → `{ "plan_type": "basic", "status": "active|pending|expired|null", "billing_cycle": "quarter", "plan_price": 300, "start_date": "...", "expires_at": "...", "confirmation": "https://..." }` (fallback من `users.selected_plan`/`selected_billing_cycle`).
  - `GET /api/v1/plans?user_type=office&billing_cycle=quarter` → `[ { "plan_type": "basic", "price": 0, "original_price": 0 } ]` (public أو auth). ويفضل إرجاع المزايا (`features[]`) من الـ DB بدلاً من `PRICING_DATA`.

### طلب ترقية / تجديد / تفعيل باقة مجانية / تحديث إيصال التحويل — `/dashboard/{type}/plans` — `features/dashboard/plans/components/DashboardPlans.tsx`
- **المستخدم**: كل الأنواع (العميل بالـ URL). لا يوجد gating.
- **التدفق** (`DashboardPlans.tsx:120-333`):
  1. **`changePlan(plan, forcedCycle?, forcedPrice?)`**: يحدد `targetCycle` (تجديد = دورة الاشتراك الحالية؛ ترقية = الدورة المختارة مع تحويل `monthly→quarter` للمهندس/المكتب) و`targetPrice` (forcedPrice → `subscriptions.plan_price` لو نفس الباقة والدورة → `plans.price` → fallback).
     - لو `targetPrice === 0` → `updatePlan({plan_type, billing_cycle, user_type, confirmation: "free_plan"})` مباشرة ("تم تفعيل باقة {name} بنجاح!").
     - غير ذلك → شاشة checkout (InstaPay `NEXT_PUBLIC_INSTAPAY_LINK`، محفظة `NEXT_PUBLIC_WALLET_LINK`، رقم ثابت `01034865483`).
  2. **رفع صورة التحويل** (`handlePhotoUpload`, السطر 277-333): `uploadToCloudinary(file, "payments")` ثم **فوراً** `updatePlan({plan_type, billing_cycle, user_type, confirmation: url})` (الطلب يُسجّل عند الرفع، ويخرج من شاشة الـ checkout عند النجاح). لو خارج الـ checkout (قسم "صورة تأكيد التحويل" — يظهر لو السعر > 0 و(الحالة "قيد المراجعة" أو توجد صورة)) → يرسل الباقة/الدورة الحالية بالصورة الجديدة.
  3. **"تأكيد وإرسال الطلب"** (`handleConfirmUpgrade`, السطر 223-271): يتطلب `photoUrl` ("يرجى رفع صورة التحويل للتأكيد")، ويرسل `updatePlan` مرة أخرى بنفس البيانات (إرسال مكرر محتمل).
  - `priceToUpdate` يُحسب في السطر 294-296 لكنه **لا يُرسل** — السعر لا يصل للسيرفر إطلاقاً.
- **`updateUserPlan`** (`features/auth/pricing/api/pricing.api.ts:3-64`):
  - `metaData = {selected_plan: plan_type, selected_billing_cycle: billing_cycle, selected_user_type: user_type}`.
  - لو `confirmation.trim() !== ""`: `from("subscriptions").upsert({user_id, plan_type, billing_cycle, user_type, confirmation, status: "pending"}, {onConflict: "user_id"})` (يعيد الحالة إلى `pending` حتى لو كانت `active`)؛ ولو `current_step < 3` → `current_step = 3` (users + metadata).
  - `from("users").update({selected_plan: plan_type, current_step?}).eq("id", user.id)`.
  - `auth.updateUser({data: metaData})` → `setUser`، invalidate `user_subscription`, `user_permissions`, `user_status`...
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `plan_type` | بطاقة الباقة / زر "ترقية الباقة" / "تجديد الاشتراك" | button | نعم | — | `basic`, `professional`, `premium` (و`free` كقيمة حالية فقط) | `subscriptions.plan_type`, `users.selected_plan`, metadata `selected_plan` |
| `billing_cycle` | مبدّل الدورة (شهري/ربع سنوي/نصف سنوي/سنوي) | toggle | نعم | engineer/office: `quarter\|half\|yearly`؛ غيرهم: `monthly\|half\|yearly` | `monthly`, `quarter`, `half`, `yearly` | `subscriptions.billing_cycle`, metadata `selected_billing_cycle` |
| `user_type` (مشتق) | — | string | — | route type | — | `subscriptions.user_type`, metadata `selected_user_type` |
| `confirmation` | إرفاق صورة التحويل | file `image/*` → URL؛ أو `"free_plan"` | نعم للمدفوع | لا فحص حجم | Cloudinary `payments` | `subscriptions.confirmation` |
| `status` (مشتق) | — | — | — | ثابت `"pending"` | — | `subscriptions.status` |
| `current_step` (مشتق) | — | int | — | `3` لو الحالي `< 3` | — | `users.current_step`, metadata |
| `plan_price` | السعر المعروض | — | — | **لا يُرسل** (يُحسب في الواجهة فقط) | — | (يجب أن يحسبه السيرفر) |

- **قوائم الاختيارات**: `plan_type`: `basic` (الباقة الأساسية) | `professional` (الباقة الاحترافية) | `premium` (الباقة المميزة) — `free` (الباقة المجانية) قيمة حالية فقط؛ `billing_cycle`: `monthly` (شهري) | `quarter` (ربع سنوي) | `half` (نصف سنوي) | `yearly` (سنوي) — المهندس/المكتب يرى `quarter, half, yearly`، باقي الأنواع `monthly, half, yearly`.
- **Endpoint مقترح (REST)**:
  - `POST /api/v1/me/subscription-requests` (auth):
```json
{ "plan_type": "professional", "billing_cycle": "quarter", "confirmation": "https://res.cloudinary.com/.../payments/x.jpg" }
```
Rules: `plan_type: required|in:basic,professional,premium`، `billing_cycle: required|in:monthly,quarter,half,yearly` (+ engineer/office: `not_in:monthly`)، `confirmation: required_unless:price,0|nullable|url|max:2048`. Server: `user_type` من المستخدم؛ `price` من `plans(user_type, plan_type, billing_cycle)`؛ لو `price == 0` → `confirmation = "free_plan"`؛ upsert `subscriptions(status=pending, plan_price=price)`؛ تحديث `users.selected_plan`, `selected_billing_cycle`؛ `current_step = max(current_step, 3)`. يجب منع الإرسال المكرر (idempotency). Response: `{ subscription: {...}, user: {...} }`.
  - `PATCH /api/v1/me/subscription/confirmation` — `{ "confirmation": "https://..." }` — rules `required|url` — لتحديث صورة الإيصال لطلب `pending` فقط (السلوك الحالي يعيد `status` إلى `pending` حتى لو `active`؛ يُنصح بعدم ذلك).

---

### 3.9 التسويق والظهور — `/dashboard/{type}/marketing` — `features/dashboard/marketing/components/DashboardMarketing.tsx`

#### قراءات
- `getMarketingData` (`marketing.api.ts:36-45`): `from("marketing").select("*").eq("user_id", userId).maybeSingle()` → `banner{title, description, image}`, `meta_pixel`, `social{facebook, instagram, youtube, meta_id, caption, image}`.
- `useUserPermissions`: `directory_ad_banner`, `homepage_ad_banner`, `social_media_promo`, `facebook_pixel`, `google_maps_location`, `distinguished_badge`, `priority_search`.
- كروت عرض فقط: "أولوية نتائج البحث" (`priority_search` ≠ `"normal"` → `"high"` = "اول النتائج"، غيره = "أولوية البحث"؛ الـ type يقول `"Normal"|"Medium"|"High"` — حالة الأحرف غير متسقة)، "إعلان بانر بصفحة..."، "إعلان البانر النشط" (غير المهندس)، "الترويج عبر السوشيال"، "Facebook Pixel" (غير المهندس)، "موقع الخريطة" (غير المهندس)، "وسام التميز".
- **Endpoint مقترح (REST)**: `GET /api/v1/me/marketing` → `{ "banner": {...}, "meta_pixel": "...", "social": {...}, "permissions": {...} }`.

### حفظ بانر / منشور سوشيال / Facebook Pixel (فورم واحد بثلاث نقاط حفظ) — `/dashboard/{engineer|office|exhibition|material}/marketing` — `features/dashboard/marketing/components/DashboardMarketing.tsx`
- **المستخدم**: كل غير العملاء. Gating:
  - قسم البانر يظهر لو `directory_ad_banner || (homepage_ad_banner && user_type !== "engineer")` (`DashboardMarketing.tsx:163`).
  - زر "إنشاء منشور ترويجي" (modal) لو `social_media_promo` (السطر 518).
  - حقل Pixel لو `facebook_pixel && user_type !== "engineer"` (السطور 537, 548).
- **الإجراء عند الإرسال** (`DashboardMarketing.tsx:88-138` → `marketing.queries.ts` → `marketing.api.ts:10-34`): كل زر حفظ ("حفظ التغييرات"، "حفظ ونشر المنشور"، أيقونة الحفظ بجانب الـ Pixel) يستدعي نفس `handleSave` ويرسل **كل** الحقول: صورة بانر جديدة → `uploadToCloudinary(file,"banners")`؛ صورة منشور جديدة → `uploadToCloudinary(file,"social_posts")`؛ ثم `supabase.auth.getUser()` → `from("marketing").upsert({user_id, banner, meta_pixel, social, user_type, slug}, {onConflict:"user_id"})`.
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `banner.title` | عنوان البانر | text | لا | لا يوجد | — | `marketing.banner->title` |
| `banner.description` | وصف قصير | textarea | لا | لا يوجد | — | `marketing.banner->description` |
| `banner.image` | صورة البانر (1700x900) | file `image/*` | لا | لا فحص | Cloudinary `banners` | `marketing.banner->image` (URL أو null) |
| `meta_pixel` | أدخل Pixel ID... | text | لا | لا يوجد | — | `marketing.meta_pixel` |
| `social.caption` | وصف المنشور (Caption) | textarea | لا | لا يوجد | — | `marketing.social->caption` |
| `social.image` | صورة المنشور | file `image/*` | لا | لا فحص | Cloudinary `social_posts` | `marketing.social->image` |
| `social.facebook`, `social.instagram`, `social.youtube`, `social.meta_id` (مخفية) | — | string | — | **لا يوجد input**؛ تُعاد قيمة الـ DB أو `""` | — | `marketing.social->...` |
| `user_type` (مشتق) | — | string | — | `user_metadata.user_type \|\| ""` | — | `marketing.user_type` |
| `slug` (مشتق) | — | string | — | `user_metadata.slug \|\| ""` | — | `marketing.slug` |
| `user_id` (مشتق) | — | uuid | — | `auth.getUser().id`؛ مفتاح upsert | — | `marketing.user_id` |

- **قوائم الاختيارات**: لا يوجد.
- **Endpoint مقترح (REST)**: `PUT /api/v1/me/marketing` (auth, غير العميل) — يُفضّل partial (PATCH) بدل إرسال الكل:
```json
{ "banner": { "title": "خصم خاص", "description": "...", "image": "https://..." },
  "meta_pixel": "123456789012345",
  "social": { "caption": "...", "image": "https://..." } }
```
Rules: `banner: sometimes|array` + **prohibited** لو لا `directory_ad_banner` ولا (`homepage_ad_banner` و type ≠ engineer)؛ `banner.title: nullable|string|max:255`؛ `banner.description: nullable|string|max:1000`؛ `banner.image: nullable|url|max:2048`؛ `meta_pixel: sometimes|nullable|string|max:64` (يفضل `digits_between:10,20`) + prohibited لو `!facebook_pixel` أو engineer؛ `social: sometimes|array` + prohibited لو `!social_media_promo`؛ `social.caption: nullable|string|max:2200`؛ `social.image: nullable|url`. السيرفر يملأ `user_id, user_type, slug`. Response: صف `marketing`.
- ملاحظة: البانرات العامة تُقرأ من view `marketing_active_users` (`marketing.api.ts:47-139`) خارج نطاق هذا القسم.

---

### 3.10 طلبات العملاء للمكتب/المهندس — `/dashboard/{office|engineer}/requests` — `features/dashboard/office/components/OfficeCustomerRequests.tsx`

#### قراءة القائمة
- **identifiers** (`OfficeCustomerRequests.tsx:58-66`): `userId`, `slug = profile.slug || metadata.slug`, `name = profile.name || metadata.name`, `officeName = profile.office_name || (engineer ? profile.name : null)`, `email` (غير مستخدم).
- `getOfficeCustomerRequests` (`features/dashboard/office/api/office-requests.api.ts:36-164`):
  1. `from("customers").select("*").neq("status","deleted").neq("status","cancelled").order("created_at",{ascending:false})` — **يقرأ كل طلبات كل العملاء** ثم يفلتر client-side (خطر خصوصية؛ يعتمد على RLS).
  2. مطابقة: `normalize(x) = trim + collapse spaces + lowercase`. تطابق لو `assigned_provider_id` == userId أو == slug؛ أو `assigned_provider_name` == name/officeName/slug؛ أو احتواء متبادل (name/officeName بطول > 3).
  3. الرفض السابق: لو لا تطابق، يبحث في `notes` عن `/\[رفض سابق:\s*([^-\]\n]+)(?:\s*-\s*السبب:\s*([^\]\n]+))?\]/g` (يكتبها الأدمن عند إعادة الترشيح: `[رفض سابق: {assigned_provider_name} - السبب: {customer_reason}]` — `features/admin/api/admin.api.ts:1934`) ولو الاسم يطابق → يضيف نسخة من الطلب بـ `status: "refused"`, `customer_response: "refused"`, `customer_reason: <السبب من الـ notes أو الحالي>`.
  4. إحصائيات: `assigned = total`, `refused = count(customer_response==="refused" || status==="refused")`, `active = approved = total - refused`, `pendingDecision = 0`.
- فلاتر client-side: tabs `all | active | refused`؛ بحث في `full_name, service, governorate, phone, notes, customer_reason`.
- الأعمدة المستخدمة: `id, full_name, phone, governorate, service, notes, customer_reason, customer_response, status, assigned_at, created_at`. الأيقونة من `ENGINEERING_SERVICES_15` بمطابقة `name`.
- واتساب: `wa.me/{phone normalized: يبدأ بـ0 → "2"+digits، يبدأ بـ2 → كما هو، غير ذلك → "20"+digits}?text=...`.
- **Endpoint مقترح (REST)**: `GET /api/v1/me/provider-requests?tab=all|active|refused&search=&page=1&per_page=20` (auth, role `office|engineer`) — السيرفر يطابق `assigned_provider_id = auth()->id()` + جدول تاريخ ترشيح/رفض (بدلاً من parsing الـ notes والمطابقة بالاسم). Response:
```json
{ "data": [ { "id": "uuid", "full_name": "...", "phone": "01...", "governorate": "...", "service": "تصميم معماري",
              "notes": "...", "status": "pending", "customer_response": null, "customer_reason": null,
              "is_refused": false, "assigned_at": "...", "created_at": "..." } ],
  "stats": { "assigned": 10, "active": 8, "refused": 2 } }
```
  يُنصح بإخفاء `phone` للطلبات المرفوضة (الواجهة تخفي أزرار التواصل لها فقط).

### تسجيل نقرة اتصال / واتساب — `/dashboard/{office|engineer}/requests` و`/dashboard/customer/services` — `features/dashboard/office/components/OfficeCustomerRequests.tsx` / `features/dashboard/customer/components/CustomerServiceRequests.tsx`
- **المستخدم**: `office`/`engineer` على طلباتهم المسندة غير المرفوضة (3.10)، و`customer` على طلباته المعيّنة (3.11). عند الضغط على "اتصال" (`tel:`) أو "واتساب".
- **الإجراء عند الإرسال**: `trackCustomerContactClick(requestId, "call"|"whatsapp")` (`features/customer/api/customer.api.ts:300-339`): `from("customers").select("id, whats_count, calls_count").eq("id", requestId).maybeSingle()` → `update({[whats_count|calls_count]: current+1, updated_at: now}).eq("id", requestId)` (read-then-write، غير atomic؛ الأخطاء تُبلع). لا يميّز هل النقرة من المكتب أو العميل.
- **حقول الفورم**: `id` (path)، `type` ∈ `whatsapp|call` → `customers.whats_count` / `customers.calls_count`.
- **قوائم الاختيارات**: `type`: `whatsapp` → `whats_count` | `call` → `calls_count`.
- **Endpoint مقترح (REST)**: `POST /api/v1/service-requests/{id}/contact-clicks` (auth؛ مسموح للعميل صاحب الطلب أو المزوّد المسند) — `{ "type": "whatsapp" }` — rules `type: required|in:whatsapp,call` — `UPDATE ... SET calls_count = calls_count + 1` atomic (ويُفضّل جدول events مع `actor_role`). Response `204`.

### (غير مستخدمة في الواجهة) تحديث حالة الطلب من المكتب — `/dashboard/{office|engineer}/requests` — `features/dashboard/office/api/office-requests.api.ts`
- **المستخدم**: `office`/`engineer` — لكن الدالة `updateOfficeRequestStatus` + `useUpdateOfficeRequestStatus` (`office-requests.api.ts:170-205`, `office-requests.query.ts:32-50`) معرّفة و**لا يستدعيها أي component**.
- **الإجراء عند الإرسال**: لو يوجد `additionalNotes` → `customers.select("notes").eq("id").single()` ثم `notes = existing ? existing + "\n[ملاحظة المكتب: {notes.trim()}]" : "[ملاحظة المكتب: {notes.trim()}]"`؛ ثم `from("customers").update({status, updated_at: now, notes?}).eq("id", requestId).select().single()`.
- **حقول الفورم**: `status` (`CustomerStatus`) → `customers.status`؛ `additionalNotes` (اختياري) → يُلحق بـ `customers.notes`.
- **قوائم الاختيارات**: `CustomerStatus` (`features/customer/types.ts:3-11`): `pending`, `assigned`, `approved`, `refused`, `in_progress`, `completed`, `cancelled`, `active`.
- **Endpoint مقترح (REST)** (لو أُريد تفعيلها): `PATCH /api/v1/me/provider-requests/{id}` — `{ "status": "in_progress", "note": "..." }` — rules: `status: required|in:in_progress,completed`، `note: nullable|string|max:1000` (فقط للطلب المسند للمزوّد وغير المرفوض).

---

### 3.11 طلبات الخدمات للعميل — `/dashboard/customer/services` (و`/requests`, `/customers`) — `features/dashboard/customer/components/CustomerServiceRequests.tsx`

#### قراءة القائمة
- `useCustomerRequests(userId)` → `getCustomerRequestsByUserId` (`features/customer/api/customer.api.ts:40-181`):
  1. `from("customers").select("*").eq("user_id", userId).neq("status","deleted").neq("status","cancelled").order("created_at",{ascending:false})`.
  2. Enrichment (لو يوجد أي `assigned_provider_id/name`): يقرأ **بالكامل** `public_offices_view (name, office_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects, count_of_projects)`، `public_engineers_view (name, user_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects)`، `rating (slug, rating, user_id, message)`. المطابقة: office أولاً ثم engineer؛ `assigned_provider_id === slug` أو تطابق/احتواء الاسم (>3 أحرف).
  3. يضيف لكل طلب: `office_name` (= `assigned_provider_name || provider.name || office_name`)، `experience_years`، `office_rating` (`average_rating` لو > 0 وإلا متوسط `rating` بالـ slug مقرّب لرقم عشري)، `office_slug`، `office_phone`، `office_whatsapp` (`whats_app || phone`)، `office_profile_image`، `office_projects_count` (`count_of_projects` للمكتب أو `projects.length`)، `provider_type` (`offices|engineers`)، `user_rated_stars`/`user_rated_message` (تقييم المستخدم الحالي لهذا الـ slug).
- **فلاتر/إحصائيات client-side** (`CustomerServiceRequests.tsx:171-225`): يستبعد ما ليس `customer_type==="need_service"` ولا يحتوي `service`؛ `total`؛ `assigned = assigned_provider_name && customer_response !== "refused"`؛ `refused = customer_response === "refused"`؛ `underReview = !assigned_provider_name`. Tabs: `all | assigned | refused | review`. بحث في `service, office_name||assigned_provider_name, governorate, notes`. view mode: cards للموبايل (`max-width: 767px`) وجدول للديسكتوب.
- رابط البورتفوليو: `/directory/{offices|engineers}/{office_slug}`؛ أو `/directory/offices/{assigned_provider_id}` لو ليس UUID؛ أو `/directory/offices?search={name}`.
- زر "طلب خدمة هندسية جديدة" → `/customer/setup` (3.11.4).
- **Endpoint مقترح (REST)**: `GET /api/v1/me/service-requests?tab=all|assigned|refused|review&search=` (auth, role `customer`) — السيرفر يعمل join على المزوّد بالـ `assigned_provider_id`. Response:
```json
{ "data": [ { "id": "uuid", "service": "تصميم معماري", "governorate": "القاهرة", "notes": "...", "status": "pending",
  "customer_type": "need_service", "created_at": "...", "assigned_at": "...", "customer_response": null, "customer_reason": null,
  "provider": { "id": "uuid", "type": "office|engineer", "name": "...", "slug": "...", "profile_image": "...",
                "experience_years": 12, "rating": 4.6, "projects_count": 20, "phone": "...", "whatsapp": "..." },
  "my_review": { "rating": 5, "message": "..." } } ],
  "stats": { "total": 3, "assigned": 1, "refused": 1, "under_review": 1 } }
```

### 3.11.1 طلب مكتب بديل (رفض المكتب المعيّن) — `/dashboard/customer/services` — `features/dashboard/customer/components/CustomerServiceRequests.tsx`
- **المستخدم**: `customer`؛ متاح فقط لطلب له `assigned_provider_name` و`customer_response !== "refused"` (والطلب المرفوض يفتح نفس الـ modal لعرض/تعديل السبب).
- **الإجراء عند الإرسال** (`CustomerServiceRequests.tsx:232-260` → `customer.query.ts` → `customer.api.ts:187-218`): لو `!rejectReason.trim()` → "يرجى كتابة سبب طلب تغيير المكتب لتتم معالجة طلبك بدقة."؛ وإلا `from("customers").update({customer_response: "refused", customer_reason: reason.trim(), status: "pending", updated_at: now}).eq("id", id).select().single()` — **يبقي** `assigned_provider_id/name` حتى يعيد الأدمن الترشيح. رسالة النجاح: "تم إرسال طلب التغيير بنجاح".
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | → table.column |
|---|---|---|---|---|---|
| `customer_reason` (UI `rejectReason`) | سبب طلب التغيير والملاحظات * | textarea (3 rows) | **نعم** | `trim()` غير فارغ (HTML `required` + فحص)؛ لا حد أقصى | `customers.customer_reason` |
| `customer_response` (مشتق) | — | — | — | ثابت `"refused"` | `customers.customer_response` |
| `status` (مشتق) | — | — | — | ثابت `"pending"` | `customers.status` |
| `updated_at` (مشتق) | — | — | — | now | `customers.updated_at` |
| `id` (path) | — | uuid | — | — | WHERE |

- **قوائم الاختيارات**: لا يوجد (القيم الثابتة: `customer_response = "refused"`, `status = "pending"`).
- **قبول المكتب**: `updateCustomerResponse(id, "approved")` يدعم `{customer_response:"approved", status:"approved"}` لكن **لا يوجد زر قبول في الواجهة** (لا يُستدعى في أي component).
- **Endpoint مقترح (REST)**: `POST /api/v1/me/service-requests/{id}/response` (auth, owner) — `{ "response": "refused", "reason": "الأسعار لا تناسب الميزانية" }` — rules: `response: required|in:approved,refused`، `reason: required_if:response,refused|nullable|string|max:1000`. السيرفر: refused → `status=pending`, يحتفظ بالمزوّد ويسجّل في جدول history؛ approved → `status=approved`. Response: الطلب المحدّث.

### 3.11.2 تقييم المكتب المعيّن — `/dashboard/customer/services` — `features/dashboard/customer/components/CustomerServiceRequests.tsx`
- **المستخدم**: `customer`؛ للطلب المعيّن وغير المرفوض. إعادة الفتح تعدّل نفس التقييم.
- **الإجراء عند الإرسال** (`CustomerServiceRequests.tsx:269-306` → `useUpsertReview` → `features/dashboard/customer/api/reviews.api.ts:55-81`):
  - `target_slug = office_slug || assigned_provider_id || encodeURIComponent(office_name || assigned_provider_name || request.id)`.
  - `from("rating").select("id").eq("user_id", author_id).eq("slug", target_slug).maybeSingle()` → لو موجود: `update({rating, message, name, type}).eq("id", id)`؛ وإلا `addReview` (السطر 29-52): فحص مكرر ثم `insert({slug, type, user_id, name, rating, message, image: undefined})`.
  - invalidate: `reviews`, `has_rated`, `dashboard_stats`, `customer_reviews`, `customer_requests`.
- **حقول الفورم**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (exact) | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `rating` (UI `ratingScore`) | النجوم | 5 أزرار | نعم (default 5 أو التقييم السابق) | 1..5 | 1 "ضعيف جداً (1/5)"، 2 "مقبول (2/5)"، 3 "جيد (3/5)"، 4 "جيد جداً (4/5)"، 5 "ممتاز وموصى به (5/5)" | `rating.rating` |
| `message` (UI `ratingMessage`) | رأيك وملاحظاتك (اختياري) | textarea | لا | `trim()`؛ يرسل `""` لو فارغ | — | `rating.message` |
| `target_slug` (مشتق) | — | string | — | انظر أعلاه | — | `rating.slug` |
| `target_type` (مشتق) | — | string | — | ثابت `"office"` (حتى لو المزوّد مهندس) | — | `rating.type` |
| `author_id` (مشتق) | — | uuid | — | `userId` | — | `rating.user_id` |
| `name` (مشتق) | — | string | — | `metadata.full_name \|\| metadata.name \|\| "عميل تعمير"` | — | `rating.name` |
| `image` | — | — | — | `undefined` (لا يُرسل) | — | `rating.image` |

- **قوائم الاختيارات**: `rating` 1..5 بالـ labels أعلاه؛ `target_type` ثابت `office`.
- **Endpoint مقترح (REST)**: `PUT /api/v1/me/reviews` (upsert، auth, role `customer`) — `{ "target_slug": "office-slug", "target_type": "office", "rating": 5, "message": "...", "service_request_id": "uuid" }` — rules: `target_slug: required|string|exists:{profiles slug}`، `target_type: required|in:engineer,office,exhibition`، `rating: required|integer|between:1,5`، `message: nullable|string|max:2000`، `service_request_id: nullable|uuid` (للتحقق أن المزوّد مسند للعميل). unique (`user_id`,`slug`). السيرفر يحدد `name` من المستخدم و`target_type` من المزوّد الفعلي. Response: صف التقييم.

### 3.11.3 حذف طلب الخدمة — `/dashboard/customer/services` — `features/dashboard/customer/components/CustomerServiceRequests.tsx`
- **المستخدم**: `customer` (أي طلب في قائمته).
- **الإجراء عند الإرسال** (`CustomerServiceRequests.tsx:86-110` → `useDeleteCustomerRequest` (optimistic remove) → `customer.api.ts:224-293`): سلسلة fallbacks: (1) `delete().eq("id").eq("user_id")` → (2) soft: `update({status:"deleted", updated_at}).eq("id").eq("user_id")` → (3) `update(... "deleted").eq("id")` بدون user_id → (4) `delete().eq("id")`؛ يرمي "فشل حذف الطلب: ..." فقط لو كلها فشلت بأخطاء.
- **حقول الفورم**: `requestId` (path)، `userId = request.user_id || props.userId || user.id`.
- **التأكيد**: modal بنص "هل أنت متأكد من رغبتك في حذف طلب خدمة ...؟ سيتم حذف هذا الطلب نهائياً من حسابك ولا يمكن استرجاعه."
- **قوائم الاختيارات**: لا يوجد.
- **Endpoint مقترح (REST)**: `DELETE /api/v1/me/service-requests/{id}` (owner only) → soft delete (`status = "deleted"` أو `deleted_at`) → `204`.

### 3.11.4 إنشاء طلب خدمة جديد — `/customer/setup` (خارج الداشبورد؛ زر "طلب خدمة هندسية جديدة" يوجّه إليه) — `features/customer/components/CustomerSetup.tsx`
- **المستخدم**: `customer` (قد يغطيه قسم الـ onboarding أيضاً؛ مذكور هنا لأنه مصدر صفوف `customers` التي تعرضها الداشبورد).
- **الإجراء عند الإرسال**: `features/customer/components/CustomerSetup.tsx:99-197` → `createCustomerRequest` (`customer.api.ts:8-34`): `from("customers").insert({user_id, full_name, phone, email, customer_type, governorate, service, notes, status}).select().single()` ثم `updateUserStep(2)` (`features/auth/profile/api/profile.api.ts:124-141`: `users.current_step=2` + metadata `current_step=2`) + `users.update({current_step:2, status:"active"})` → `router.replace("/dashboard/customer")`.
- **حقول الفورم**:

| key | الاسم في الواجهة | type | required | validation | options | → |
|---|---|---|---|---|---|---|
| `customer_type` | اختيار نوع (طلب خدمة / تصفح) | toggle | نعم | — | `need_service` (default), `guest` | `customers.customer_type` |
| `service` | 1. نوع الخدمة الهندسية * | ResponsiveSelect | نعم لـ `need_service` ("يرجى اختيار نوع الخدمة الهندسية المطلوبة") | — | `ENGINEERING_SERVICES_15[].name` (3.17) | `customers.service` (null لـ guest) |
| `governorate` | المحافظة * | select | نعم لـ `need_service` ("يرجى تحديد المحافظة التي يقع بها مشروعك") | — | 27 محافظة (label "محافظة {x}"، value = الاسم) | `customers.governorate` (null لـ guest) |
| `notes` | الملاحظات | textarea | لا | `maxLength=500`؛ `trim() \|\| null` | — | `customers.notes` (null لـ guest) |
| `full_name` (مشتق) | — | — | — | `metadata.name \|\| fullName \|\| full_name \|\| email-prefix \|\| "عميل"` | — | `customers.full_name` |
| `phone` (مشتق) | — | — | — | `metadata.phone \|\| auth phone \|\| null` | — | `customers.phone` |
| `email`, `user_id` (مشتق) | — | — | — | من الجلسة | — | `customers.email`, `customers.user_id` |
| `status` (مشتق) | — | — | — | `guest → "active"`، `need_service → "pending"` | — | `customers.status` |

- **قوائم الاختيارات**: `customer_type`: `need_service` | `guest`؛ `service`: أسماء `ENGINEERING_SERVICES_15` (16، 3.17)؛ `governorate`: 27 محافظة.
- **Endpoint مقترح (REST)**: `POST /api/v1/me/service-requests` — `{ "customer_type": "need_service", "service": "تصميم معماري", "governorate": "القاهرة", "notes": "..." }` — rules: `customer_type: required|in:need_service,guest`، `service: required_if:customer_type,need_service|in:{16 اسم}`، `governorate: required_if:customer_type,need_service|in:{27}`، `notes: nullable|string|max:500`.

---

### 3.12 المفضلة — `/dashboard/customer/favorites` — `features/dashboard/customer/components/DashboardFavorites.tsx`

- **قراءة**: `getUserFavorites` (`favorites.api.ts:16-25`): `from("favorites").select("*").eq("user_id", userId).order("created_at",{ascending:false})`. الأعمدة: `id, name, image, type, slug, phone, created_at`. Tabs client-side: `all, engineer, office, exhibition` (العدّ يشمل `material` لكن لا يوجد tab له). رابط الملف: `/directory/{engineers|offices|exhibitions|materials}/{slug}`.
- **Endpoint مقترح (REST)**: `GET /api/v1/me/favorites?type=engineer|office|exhibition` → `{ "data": [ {id, name, image, type, slug, phone, created_at} ], "counts": { "all": 5, "engineer": 2, "office": 2, "exhibition": 1, "material": 0 } }`. (الإضافة/toggle `favorites.api.ts:44-85` تُستدعى من صفحات الدليل وليس الداشبورد: `insert {user_id, name, image, type, slug, phone}`.)

### حذف من المفضلة — `/dashboard/customer/favorites` — `features/dashboard/customer/components/DashboardFavorites.tsx`
- **المستخدم**: `customer` (زر القلب على البطاقة؛ بدون تأكيد).
- **الإجراء عند الإرسال**: `useRemoveFavorite` → `removeFavorite(userId, slug)` (`favorites.api.ts:58-70`): `from("favorites").delete().eq("user_id", userId).eq("slug", slug)`. invalidate `user_favorites`, `is_favorited`, `dashboard_stats`.
- **حقول الفورم**:

| key | الاسم في الواجهة | type | required | validation | options | → table.column |
|---|---|---|---|---|---|---|
| `slug` | زر القلب على بطاقة العنصر | string (من البطاقة) | نعم | — | — | WHERE `favorites.slug` |
| `user_id` (مشتق) | — | uuid | نعم | من الجلسة | — | WHERE `favorites.user_id` |

- **قوائم الاختيارات**: لا يوجد (tabs العرض: `all`, `engineer` "مهندسين", `office` "مكاتب هندسية", `exhibition` "معارض").
- **Endpoint مقترح (REST)**: `DELETE /api/v1/me/favorites/{slug}` (auth, role `customer`) → `204`.

---

### 3.13 تقييماتي — `/dashboard/customer/rating` — `features/dashboard/customer/components/DashboardRatings.tsx`

- **قراءة فقط**: `getCustomerReviews` (`reviews.api.ts:106-125`): `from("rating").select("*").eq("user_id", userId).order("created_at",{ascending:false})` → mapping `{id, target_slug: slug, target_type: type, author_id: user_id, name, rating, message, image, created_at}`.
- client-side (`DashboardRatings.tsx:78-113`): `targetName = r.professional_name || "مهني"` (الحقل غير موجود → يُعرض "المعرف: {slug}")، نوع الهدف: `type` أو `platform` لو `slug === "t3meer"` وإلا `engineer`؛ متوسط التقييمات (رقم عشري واحد)؛ توزيع 5..1 (count + percent)؛ فلاتر: `all`, `5`, `4`, `3` (= `<= 3`). الرابط `/directory/{engineers|offices|exhibitions}/{slug}` أو `/` للمنصة.
- **Endpoint مقترح (REST)**: `GET /api/v1/me/reviews?stars=5|4|lte3` → 
```json
{ "data": [ { "id": 1, "rating": 5, "message": "...", "created_at": "...",
              "target": { "slug": "...", "type": "office|engineer|exhibition|material|platform", "name": "اسم المزوّد", "profile_image": "..." } } ],
  "summary": { "average": 4.3, "count": 7, "distribution": { "5": 4, "4": 2, "3": 1, "2": 0, "1": 0 } } }
```
(السيرفر يعمل join لاسم المزوّد — مفقود حالياً).

---

### 3.14 النشاط الأخير — `/dashboard/customer/activity` — `features/dashboard/customer/components/DashboardActivity.tsx`

- **قراءة فقط**: `useDashboardStats(userId, metadata.user_type || "customer")` → `activities` من 3.3.3 (أقصى 10 عناصر مدمجة). mapping: `favorite → favorite_add`، `review → rating`، غير ذلك `view`. إحصائيات client-side: إجمالي، إضافات المفضلة، التقييمات، الزيارات؛ فلاتر `all | favorites | ratings | views`؛ تجميع حسب التاريخ (`created_at.split("T")[0]`) مع "اليوم"/"أمس". `mockActivities` معرّفة وغير مستخدمة.
- **Endpoint مقترح (REST)**: `GET /api/v1/me/activities?type=favorite|review|visit&limit=50&page=1` → `{ "data": [ { "id": "fav-1", "type": "favorite|review|visit", "text": "...", "link": "/directory/offices/x", "created_at": "..." } ], "summary": { "total": 10, "favorites": 3, "reviews": 2, "visits": 5 } }`.

---

### 3.15 مرجع حدود الباقات (نص تسويقي في `features/auth/pricing/api/pricing-static.ts`) — **ليست enforcement**

التطبيق الفعلي يقرأ `user_permissions` (`max_visits`, `max_projects`, `max_services` + flags). هذه الأرقام هي ما يُعلن للمستخدم ويُنصح باستخدامها كـ seed لـ `user_permissions`/`plans`:

| النوع | الباقة | زيارات/شهر | مشاريع أو منتجات | خدمات أو فئات | مزايا أخرى معلنة |
|---|---|---|---|---|---|
| engineer | basic | 150 | 3 مشاريع | 1 خدمة | مهارات غير محدودة، روابط سوشيال |
| engineer | professional | 300 | 7 | 5 | إحصائيات مكالمات/واتساب/سوشيال، عدد المفضلة |
| engineer | premium | ∞ | ∞ | ∞ | أول نتائج البحث، الرئيسية، بانر صفحة المهندسين، شارة مميز، سوشيال، ترشيح عميل شهرياً |
| office | basic | 500 | 5 | 3 | — |
| office | professional | 1000 | 10 | 7 | فيديو، إحصائيات، إعلان داخل صفحة المكاتب، ترشيح عميل شهرياً |
| office | premium | ∞ | ∞ | ∞ | شارة، خرائط Google، Pixel، الرئيسية، سوشيال، ترشيح عميلين شهرياً |
| exhibition | basic | 500 | 10 منتجات | 3 فئات | — |
| exhibition | professional | 1000 | 20 | 7 | فيديوهات، إحصائيات، إعلان داخل قسم المعارض |
| exhibition | premium | ∞ | ∞ | ∞ | الرئيسية، شارة، خرائط، Pixel، سوشيال |
| material | basic | 100 | 5 منتجات | 2 فئات | — |
| material | professional | 500 | 10 | 5 | فيديوهات، إحصائيات، إعلان داخل قسم مواد البناء |
| material | premium | ∞ | ∞ | ∞ | الرئيسية، شارة، خرائط، Pixel، سوشيال |

> ⛔ صفوف `material` دي مش هتتعمل في Laravel. المورد بياخد حدود باقات `exhibition` (0.5).

Hardcoded fallback الوحيد: `EditMaterialProfile.tsx:54-64` (`max_services` null → premium/free ∞، professional 3، basic 1) — لا يطابق الجدول أعلاه (material basic = 2).

---

### 3.16 Tables touched (لهذا القسم)

| Table / View / RPC | Read (columns / filters) | Write (columns) |
|---|---|---|
| `auth.users` (metadata) | `user_type`, `type`, `account_type`, `current_step`, `selected_plan`, `selected_billing_cycle`, `slug`, `name`, `full_name`, `phone`, `profile_image`, `image`, `email` | `name`, `phone`, `profile_image`, `image`, `selected_plan`, `selected_billing_cycle`, `selected_user_type`, `current_step`, `password` |
| `users` | `status`, `rejection_reason` (by `id`) | `name`, `phone` (تعديل الحساب)؛ `selected_plan`, `current_step` (الباقات)؛ `current_step`, `status="active"` (customer setup) |
| `engineers` | `*` by `user_id` (يستخدم: `slug, name, phone, major, bio, governorate, whats_app, facebook, instagram, youtube, experience_years, skills, profile_image, image, count_of_projects, specializations, services, latitude, longitude`) | upsert(`user_id`): `name, major, experience_years, governorate, bio, latitude, longitude, profile_image, whats_app, skills, facebook, instagram, youtube, updated_at`؛ update `profile_image` (avatar) |
| `offices` | `*` by `user_id` (+ `office_name, open_hours, video, count_of_projects, slug, phone`) | upsert(`user_id`): `name, governorate, whats_app, experience_years, bio, count_of_projects, open_hours, video, latitude, longitude, profile_image, facebook, instagram, youtube`؛ update `profile_image` |
| `exhibitions` | `*` by `user_id` (+ `logo, banner, brands, main_category, count_of_categories, slug, profile_image, coverImage`) | upsert(`user_id`): `name, main_category, governorate, experience_years, bio, whats_app, open_hours, count_of_categories, slug, video, logo, banner, brands, facebook, instagram, youtube, latitude, longitude, updated_at`؛ update `profile_image` |
| `materials` | `*` by `user_id` | upsert(`user_id`) للـ state كامل: `id, user_id, name, phone, email, governorate, city, address, whats_app, website, open_hours, profile_image, coverImage, social, bio, experience_years, skills, latitude, longitude, videoUrl, mainCategories, specializations, deliveryAvailable, materials, priceList, quantities, deliveryAreas, truckTypes, deliveryCost, certificates` + أعمدة الصف الحالي؛ update `profile_image` |
| `user_permissions` | `*` by `user_id`: `id, user_id, user_type, plan_type, max_visits, max_projects, max_services, whatsapp_tracking, call_tracking, detailed_analytics, video_projects, google_maps_location, directory_ad_banner, distinguished_badge, homepage_ad_banner, social_media_promo, facebook_pixel, priority_search` | — |
| `subscriptions` | `*` by `user_id`: `plan_type, status, billing_cycle, plan_price, start_date, expires_at, confirmation` | upsert(`user_id`): `user_id, plan_type, billing_cycle, user_type, confirmation, status="pending"` |
| `plans` | `plan_type (as plan_id), price, original_price` WHERE `user_type`, `billing_cycle` | — |
| `projects` | `*` by `user_id` order `created_at desc` | insert: `user_id, name, location, area, slug, type, image, user_type, user_name, details{video,brief,categories[],photos[]}`؛ update: `name, location, area, type, image, slug, user_name, details`؛ delete by `id` |
| `products` | `*` by `user_id` order `created_at desc` | insert: `user_id, name, category, sub_category, image, price, slug, details{description,photos[]}`؛ update: نفسها بدون `user_id`؛ delete by `id` |
| `services` | `*` by `user_id` أو `slug` order `created_at asc`؛ `name, icon, color` (الكل) | insert: `user_id, name, icon, color, slug`؛ delete match (`name`+`slug`) أو (`name`+`user_id`) |
| `marketing` | `*` by `user_id` | upsert(`user_id`): `banner{title,description,image}, meta_pixel, social{facebook,instagram,youtube,meta_id,caption,image}, user_type, slug` |
| `stats` | RPC `get_limited_stats(p_slug)` → `{visit, whatsapp, call, email, map, facebook, instagram, youtube}`؛ RPC `get_all_stats(p_slug)` → `[{metric_type, created_at}]`؛ `*` by `slug` order desc limit 10؛ `*` by `user_id` + `metric_type='visit'` limit 10 | — (يُكتب من صفحات الدليل: `metric_type, count, date, slug, user_id, type, created_at`) |
| `favorites` | count by `slug`؛ `*` by `slug` limit 5؛ `*` by `user_id` order desc (limit 5 / الكل)؛ count by `user_id`+`type` | delete by `user_id`+`slug` |
| `rating` | `rating` by `slug`؛ count by `user_id`؛ `*` by `user_id` order desc؛ `slug, rating, user_id, message` (الكل)؛ `id` by `user_id`+`slug` | update: `rating, message, name, type`؛ insert: `slug, type, user_id, name, rating, message, image` |
| `customers` | `*` WHERE `status NOT IN (deleted, cancelled)` (الكل — للمكتب)؛ `*` by `user_id` (للعميل)؛ `id, whats_count, calls_count` by `id` | update: `customer_response, customer_reason, status, updated_at`؛ update: `whats_count\|calls_count, updated_at`؛ delete أو `status='deleted'`؛ (غير مستخدم) `status, notes, updated_at`؛ insert (setup): `user_id, full_name, phone, email, customer_type, governorate, service, notes, status` |
| `public_offices_view` | `name, office_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects, count_of_projects` (الكل) | — |
| `public_engineers_view` | `name, user_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects` (الكل) | — |
| `user_validation` | `*` by `user_id` (القسم معلّق) | (معلّق) |
| RPC `delete_user_account()` | — | حذف الحساب بالكامل |

### 3.17 القيم الثابتة (Enums / Options)

- **`user_type`**: `customer`, `engineer`, `office`, `exhibition`, `material` (+ `admin`). **في Laravel:** من غير `material` (0.5).
- **Plan types**: `free` ("الباقة المجانية" — قيمة التسجيل الافتراضية `selected_plan: "free"`)، `basic` ("الأساسية")، `professional` ("الاحترافية")، `premium` ("المميزة"). ترتيب الترقية: `basic < professional < premium`.
- **`billing_cycle`**: `monthly` ("شهري")، `quarter` ("ربع سنوي")، `half` ("نصف سنوي")، `yearly` ("سنوي").
- **`subscriptions.status`**: `pending` (يكتبه المستخدم)، `active`، `expired` (يقرأها الداشبورد).
- **`users.status`**: `active`، `pending`، `reject` (و`rejected` في `DashboardHome.tsx:190-191`) + `rejection_reason`.
- **`customers.customer_type`**: `need_service`, `guest`.
- **`customers.status`** (`features/customer/types.ts:3-11` + `deleted`): `pending`, `assigned`, `approved`, `refused`, `in_progress`, `completed`, `cancelled`, `active`, `deleted`.
  - التحولات الملاحظة: إنشاء `need_service` → `pending`؛ إنشاء `guest` → `active`؛ الأدمن يرشّح مزوّد → `status=pending` + `assigned_provider_id/name`, `assigned_at`, `customer_response=null`, `customer_reason=null` (ويسجّل `[رفض سابق: ...]` في `notes` لو كان مرفوضاً)؛ العميل يرفض → `customer_response=refused`, `customer_reason`, `status=pending` (المزوّد يبقى)؛ العميل يقبل (دالة بدون UI) → `customer_response=approved`, `status=approved`؛ العميل يحذف → حذف فعلي أو `status=deleted`؛ المكتب (دالة بدون UI) → `in_progress`/`completed` + `[ملاحظة المكتب: ...]`.
  - `customers.status` المستبعدة من القوائم: `deleted`, `cancelled`.
- **`customers.customer_response`**: `null`, `approved`, `refused`.
- **`stats.metric_type`** (`lib/track-metric.ts:4-12`): `whatsapp`, `call`, `email`, `map`, `facebook`, `instagram`, `youtube`, `visit`.
- **`favorites.type`**: `engineer`, `office`, `exhibition`, `material` (← `exhibition` في Laravel).
- **`rating.type`**: من الداشبورد دائماً `office`؛ في القراءة: `engineer`, `office`, `exhibition`؛ الـ slug الخاص `t3meer` = تقييم المنصة.
- **`user_permissions.priority_search`**: الـ type يقول `Normal | Medium | High`، الواجهة تقارن بـ `normal`/`high` (lowercase).
- **`engineers.major`** (`engineerMajors`): `معماري` (label "عمارة")، `مدني`. قيم legacy مقروءة في الـ sidebar: `architectural`, `civil`.
- **`governorates`** (value = label، `Static/directoryConfig.ts:10-38`): القاهرة، الجيزة، الإسكندرية، الدقهلية، الشرقية، القليوبية، الغربية، المنوفية، البحيرة، كفر الشيخ، دمياط، بورسعيد، الإسماعيلية، السويس، الفيوم، بني سويف، المنيا، أسيوط، سوهاج، قنا، الأقصر، أسوان، البحر الأحمر، الوادي الجديد، مطروح، شمال سيناء، جنوب سيناء.
- **`engineerSpecializations` = `projectTypes`** (`features/auth/profile/api/profile.static.ts:54-118`) — `value → label (icon, color)`؛ المخزّن في `projects.type`/`details.categories` و`services.name` هو الـ **label**:
  `architectural-design → تصميم معماري (material-symbols:architecture, #38bdf8)`، `quantity-surveying → حصر كميات (mdi:calculator, #34d399)`، `shop-drawings → رسومات تنفيذية (ph:house-line, #f59e0b)`، `project-management → إدارة مشاريع (mdi:eye-outline, #22c55e)`، `interior-design → تصميم داخلي (solar:sofa-2-bold, #a78bfa)`، `finishing → تشطيبات (solar:paint-roller-bold, #e879f9)`، `engineering-consulting → استشارات هندسية (solar:chat-round-call-bold, #fb923c)`، `building-permits → تراخيص بناء (solar:document-bold, #4ade80)`، `surveying → رفع مساحة (solar:map-point-bold, #fb923c)`، `legalization → تصالح للبناء (solar:check-read-bold, #f59e0b)`، `structural-design → تصميم انشائي (mdi:pillar, #a78bfa)`، `facade-design → تصميم واجهات (solar:window-frame-bold, #38bdf8)`، `insulation-works → اعمال عزل (solar:shield-bold, #64748b)`، `concrete-works → اعمال خرسانات (material-symbols:wall-art-rounded, #94a3b8)`، `restoration-works → اعمال ترميم (solar:restart-bold, #f43f5e)`، `syndicate-services → خدمات نقابية (solar:users-group-rounded-bold, #0070d7)`.
- **`ENGINEERING_SERVICES_15`** (`features/customer/constants.ts:11-108`، 16 عنصر، `customers.service` = `name`): تصميم معماري، حصر كميات، رسومات تنفيذية، إدارة مشاريع، تصميم داخلي، تشطيبات، استشارات هندسية، تراخيص بناء، رفع مساحة، تصالح للبناء، **تصميم إنشائي**، تصميم واجهات، **أعمال عزل**، **أعمال خرسانات**، **أعمال ترميم**، خدمات نقابية. (لاحظ اختلاف الهمزات عن `engineerSpecializations` — يجب توحيد قائمة واحدة server-side.)
- **`showroomSupplierCategories`** (= `exhibitionCategories` = `materialCategories`، value = label، `profile.static.ts:123-149`) مع اللون: مواد البناء الأساسية (#0070d7)، الحديد والمعادن (#34d399)، الخرسانة والإنشاءات (#94a3b8)، العزل (#64748b)، السباكة والمواسير (#FC7753)، الكهرباء (#f59e0b)، الأخشاب (#a16207)، الجبس والأسقف (#e879f9)، مواد الترميم والكيماويات (#f43f5e)، العدد والمعدات (#fb923c)، الأثاث والفرش (#22c55e)، السيراميك والبورسلين (#0070d7)، الأدوات الصحية (#22c55e)، الإضاءة (#f59e0b)، الدهانات والديكور (#e879f9)، الأبواب والشبابيك (#a78bfa)، الرخام والجرانيت (#34d399)، الواجهات الخارجية (#38bdf8)، اللاندسكيب (#4ade80)، المطابخ والدريسنج (#0070d7)، التكييف والتهوية (#38bdf8)، الطاقة الشمسية (#f59e0b)، الأمن والسلامة (#f43f5e).
- **`showroomSupplierSubCategories`** (المفاتيح العربية؛ المفاتيح الإنجليزية legacy مكررة بنفس القيم، `profile.static.ts:151-200`) — كتالوج خدمات المعرض/المورد (icon `solar:double-alt-arrow-right-bold`، color لون القسم):
  - مواد البناء الأساسية: أسمنت، طوب، رمل وسن، جبس، خرسانة جاهزة
  - الحديد والمعادن: حديد تسليح، صاج وملفوفات، زوايا وكمرات، أسلاك شبك
  - الخرسانة والإنشاءات: إضافات خرسانة، بلوك خرساني، منتجات أسمنتية
  - العزل: عزل مائي، عزل حراري، عزل صوتي، لفائف بتومين
  - السباكة والمواسير: مواسير مياه، مواسير صرف، خزانات مياه، مضخات مياه، محابس ووصلات
  - الكهرباء: أسلاك وكابلات، لوحات توزيع، قواطع كهربائية، خراطيم وتمديدات
  - الأخشاب: خشب زان، خشب سويدي، أبلاكاش، خشب MDF
  - الجبس والأسقف: ألواح جبس بورد، إكسسوارات تعليق، كرانيش جبس
  - مواد الترميم والكيماويات: أديبوند وجراوت، مونة ترميم، مواد معالجة خرسانة
  - العدد والمعدات: عدد يدوية، معدات كهربائية، سقالات، أدوات قياس
  - الأثاث والفرش: غرف نوم، غرف معيشة وصالونات، سجاد وموكيت، ستائر ومفروشات، أثاث مكتبي، أثاث خارجي
  - السيراميك والبورسلين: سيراميك أرضيات، سيراميك حوائط، بورسلين، وزرات وديكورات
  - الأدوات الصحية: أطقم حمامات، خلاطات مياه، أحواض مطابخ، كابينات شاور، جاكوزي
  - الإضاءة: نجف وثريات، إضاءة ليد (LED)، كشافات وسبوتات، إضاءة خارجية، أباجورات وإضاءة طاولة
  - الدهانات والديكور: دهانات داخلية، دهانات خارجية، معجون وبرايمر، ورق حائط، بديل خشب ورخام
  - الأبواب والشبابيك: أبواب خشبية، أبواب مصفحة، شبابيك ألومنيوم (ألوتال)، شبابيك PVC، بوابات حديد
  - الرخام والجرانيت: رخام طبيعي، جرانيت طبيعي، رخام صناعي، درج ومطابخ رخام
  - الواجهات الخارجية: حجر طبيعي، حجر صناعي، جي آر سي (GRC)، كلادينج
  - اللاندسكيب: نجيل صناعي، إنترلوك وبردورات، مظلات وبرجولات، نباتات زينة
  - المطابخ والدريسنج: مطابخ ألومنيوم، مطابخ خشبية، دريسنج روم، إكسسوارات مطابخ
  - التكييف والتهوية: تكييفات إسبليت، تكييفات مركزي، مراوح تهوية، مواسير نحاس وعزل
  - الطاقة الشمسية: ألواح شمسية، سخانات شمسية، بطاريات ومحولات
  - الأمن والسلامة: كاميرات مراقبة، أنظمة إنذار حريق، أجهزة حضور وانصراف، طفايات حريق
- **`exhibitionServices`** (للمعرض فقط في كتالوج الخدمات، `services.name` = label): `delivery-shipping` توصيل وشحن، `installation` تركيب، `design-consulting` استشارات تصميم، `custom-manufacturing` تصنيع حسب الطلب، `product-warranty` ضمان على المنتجات، `wholesale` بيع بالجملة، `inspection-measurement` معاينة وقياسات، `after-sales-service` خدمة ما بعد البيع.
- **`materialServices`** (`materials.specializations` يخزن الـ **value**): `site-delivery` توصيل للموقع، `wholesale-prices` أسعار جملة، `payment-plans` تسهيلات سداد، `free-samples` عينات مجانية، `technical-support` دعم فني، `quality-certificates` شهادات جودة، `return-policy` سياسة إرجاع، `supply-contracts` عقود توريد. ← في Laravel بتتدمج في قائمة خدمات `exhibition` الموحدة (0.5).

### 3.18 أهم الملاحظات للـ Backend (ملخص المخاطر)
1. **التفويض client-side فقط**: كل الحدود (`max_*`) والأقفال (`video_projects`, `facebook_pixel`...) تُفحص في الواجهة؛ الـ API يجب أن يفرضها.
2. **مكتب/مهندس يقرأ كل `customers`** ويطابق بالاسم؛ استخدم `assigned_provider_id` + جدول history للرفض.
3. **العميل يقرأ كل `rating` و`public_*_view`** لعمل enrichment؛ اعمل join server-side.
4. **الأسعار لا تُرسل في طلب الاشتراك**؛ يحسبها السيرفر من `plans`. رفع الإيصال يعيد `status` إلى `pending` ويُرسل الطلب مرتين أحياناً.
5. **فورم المورد يرسل الـ state كامل** لجدول `materials` (أعمدة غير موجودة غالباً + `id` mock). (بيتلغي مع دمج المورد في `exhibition`، 0.5)
6. **قيم mock تُحفظ** عند وجود أعمدة فارغة (مهارات المهندس، فلسفة/عدد مشاريع/مواعيد المكتب، شعار/غلاف/تصنيف/علامات المعرض).
7. فحص الهاتف المصري يحدث **بعد** الحفظ في فورم المهندس/المكتب/المورد.
8. chart الزيارات يعد الصفوف وليس `SUM(count)`؛ و`last_month_visitors` و"أيام التفعيل" غير محسوبة.
9. صورة العميل (avatar) تُكتب في `engineers`.
10. لا يوجد فحص حجم ملفات إلا الفيديو (100MB) في تعديل المكتب/المعرض.

---

## 4. لوحة تحكم الأدمن — `/dashboard/admin/**`

> النطاق: كل صفحات `app/dashboard/admin/**` + `features/admin/api/admin.api.ts` (2008 سطر) + `features/admin/api/admin.query.ts` + كل `features/admin/components/*` + `app/api/admin/homepage-display/route.ts` + `lib/homepage-display-settings*.ts` + `data/homepage-display-settings.json`.
> كل اللي تحت ده وصف لِلّي الكود بيعمله فعلاً. أي حاجة مكتوب جنبها **(مقترح)** دي توصية للـ Laravel مش موجودة في الكود.

### خريطة الصفحات (Route → Component → Purpose)

| Admin route | Component(s) | الغرض |
|---|---|---|
| `/dashboard/admin` | `app/dashboard/admin/page.tsx` → `AdminStatCards` + `HomepageDisplayControls` (+ كارتين placeholder ثابتين "سيتم إضافة الرسم البياني لاحقاً" بدون data) | KPIs عامة للمنصة + التحكم في الباقات المسموح ظهورها في الصفحة الرئيسية (مكاتب/مهندسين) |
| `/dashboard/admin/users` | `OnboardingStatsGrid` + `UserManagementTable` | Funnel مراحل التسجيل (`current_step`) + دليل كل المستخدمين (بحث/ترتيب/pagination) + زرار "إرسال الرابط" (resend signup email) |
| `/dashboard/admin/customers` | `AdminCustomerRequestsTable` | طلبات الخدمة الهندسية من العملاء (جدول `customers`): ترشيح/تعيين مكتب أو مهندس، إعادة الترشيح بعد رفض العميل، تغيير الحالة، حذف، إحصائيات نقرات واتساب/اتصال لكل خدمة |
| `/dashboard/admin/offices` | `ProfessionalTable type="office"` | جدول المكاتب + كروت (الإجمالي، المفعّل، توزيع الباقات، أعلى 3 محافظات) + فلتر "العرض العام فقط" |
| `/dashboard/admin/offices/[id]` | `ProfessionalDetails type="office"` (`id` = `users.id`، اللينك بيتبني من `profile.user_id` في `ProfessionalTable.tsx:370`) | تفاصيل مكتب + إحصائيات تفاعل + تعديل البيانات والحالة وسبب الرفض والصورة |
| `/dashboard/admin/engineers` | `ProfessionalTable type="engineer"` | نفس المكاتب للمهندسين |
| `/dashboard/admin/engineers/[id]` | `ProfessionalDetails type="engineer"` | نفس تفاصيل المكتب + التخصص (`major`) والمهارات (`skills`) |
| `/dashboard/admin/subscriptions` | `SubscriptionsTable` (و`ManualSubscription` **متعمله comment** في `app/dashboard/admin/subscriptions/page.tsx:21-23` يعني مش ظاهر في الـ UI حالياً) | سجل الاشتراكات + تغيير الحالة (`active/pending/expired`) + عرض صورة إيصال الدفع |
| `/dashboard/admin/verifications` | `VerificationsTable` | مراجعة طلبات التوثيق (`user_validation`) قبول/رفض بسبب |
| `/dashboard/admin/finance` | `FinanceStats` | إحصائيات الإيرادات من `subscription_payments` + قيمة الاشتراكات النشطة |
| `/dashboard/admin/prices` | `PriceEditor` | CRUD لأسعار مواد البناء (`materials_prices`) اللي بتظهر في الصفحة الرئيسية |
| Next route handler: `GET` / `POST /api/admin/homepage-display` | `app/api/admin/homepage-display/route.ts` | قراءة/حفظ إعدادات الظهور في ملف `data/homepage-display-settings.json` على الـ filesystem |

- الـ Sidebar: `adminSidebarItems` في `features/dashboard/static.ts:38-49` بالترتيب: `admin` (لوحة التحكم)، `users`، `customers`، `offices`، `engineers`، `subscriptions`، `verifications`، `finance`، `prices`، `logout` (بينادي `useLogout()` العام، مش خاص بالأدمن).
- **مش موجود في كود الأدمن** (عشان الباك إند ميدورش عليه): إنشاء مستخدم، حذف مستخدم، Suspend/Ban كإجراء مستقل، تعديل exhibitions/materials (معارض/موردين)، إدارة التقييمات (`flaggedReviews` راجع `0` ثابت — `admin.api.ts:132`)، تعديل خدمات أو مشاريع المهني (بتتعرض read-only بس). أقرب حاجة لـ "إيقاف حساب" هي تغيير `users.status` من صفحة تفاصيل المهندس/المكتب.

### إزاي صلاحية الأدمن بتتشيك حالياً

1. **`app/dashboard/admin/layout.tsx:12-47` (Server Component)**: بيقرا cookie اسمها `user-storage` (ده الـ Zustand persist بتاع `store/useUserStore.ts`، بيتكتب من المتصفح)، يعمل `JSON.parse` (ولو فشل يجرب `decodeURIComponent` الأول)، ياخد `state.user` و`state.isAuthenticated`.
   - لو مش authenticated → `redirect("/login")`.
   - `accountType = user.user_metadata.account_type || user.user_metadata.type || user.user_metadata.user_type`؛ لو `!== "admin"` → `redirect("/dashboard")`.
2. **`middleware.ts:83-101`**: بيشيك بس إن المستخدم authenticated لأي `/dashboard/*` (من نفس الـ cookie أو fallback لـ `sb-*-auth-token` بفك الـ JWT payload **من غير التحقق من التوقيع**). مفيش أي check على `admin` في الـ middleware.
3. **مفيش أي check على مستوى الداتا**: كل functions `admin.api.ts` بتتنفذ من المتصفح بـ `supabase` client (`lib/supabase.ts` بالـ anon key). الحماية الوحيدة المحتملة هي RLS في Supabase (مش موجودة في الريبو).
4. **`/api/admin/homepage-display`**: مفيهوش أي auth check خالص، ومش داخل في `matcher` بتاع الـ middleware (`middleware.ts:130-139`).

### ملاحظات عامة على الـ data layer

- Page size ثابت **20** في كل الجداول (`limit = 20`)، والـ UI بيحسب `totalPages = ceil(total / 20)`.
- React Query keys والـ invalidation (مهمة عشان تعرف أي Endpoint لازم يرجّع إيه بعد كل mutation):

| Mutation hook | بيعمل invalidate لـ |
|---|---|
| `useMutationUpdateVerification` | `admin_pending_verifications`, `admin_overview_stats` |
| `useMutationOverrideSubscription` | `admin_users`, `user_profile`, `admin_finance_stats` |
| `useMutationUpsertPrice` / `useMutationDeletePrice` | `admin_materials_prices` |
| `useMutationUpdateSubscription` | `admin_subscriptions`, `admin_finance_stats` |
| `useMutationAdminUpdateProfile` | `admin_user_details(userId,type)`, `admin_engineers`, `admin_offices` |
| `useMutationAdminResendConfirmation` | (مفيش) — بيعرض Swal نجاح/فشل |
| `useMutationAssignProvider` | `admin_customer_requests`, `customer_requests`, `dashboard_stats` |
| `useMutationUpdateCustomerRequestStatus` / `useMutationDeleteCustomerRequest` | `admin_customer_requests`, `customer_requests` |

- staleTime: الإحصائيات العامة والمالية دقيقتين، القوايم 30 ثانية، طلبات العملاء 15 ثانية، قايمة المكاتب القابلة للتعيين 5 دقايق.
- **(مقترح)** كل الـ endpoints تحت `/api/v1/admin/*` بـ middleware: `auth:sanctum` + `admin` (أو Policy) — تفاصيل في "Security notes" آخر القسم.

---

### الإجراءات (Write Actions)

### قبول طلب توثيق — `/dashboard/admin/verifications` — `VerificationsTable` → `updateVerificationStatus`

> ⬅ السلوك المطلوب في Laravel للقبول والرفض والـ reset (الحالات الجديدة، `is_verified`، إعادة حساب `users.status`، وفصل التوثيق عن تفعيل الاشتراك) موجود في **2.V.3** و**2.V.5 (4)**. اللي تحت ده وصف الكود الحالي.

- **الإجراء** (`admin.api.ts:200-231`)، الـ trigger: اختيار `active` من الـ select في عمود "الإجراءات/الحالة" (`VerificationsTable.tsx:252-267`) — مفيش confirm:
  1. `user_validation` **update** `{ status: "active", rejection_reason: undefined }` where `id = :id`. ملاحظة: `undefined` بيتشال من الـ JSON، يعني **`rejection_reason` القديم بيفضل زي ما هو** في حالة القبول.
  2. `users` **update** `{ status: "active" }` where `id = :userId`.
  3. `interaction_logs` **insert** `{ user_id: userId, activity_type: "update", activity_text: "تم قبول طلب توثيق الحساب بنجاح." }` (الـ error بيتجاهل).
  - مفيش تحديث لـ `auth.user_metadata`، ولا `users.current_step`، ولا `subscriptions`.
  - Swal نجاح: "تم قبول طلب التوثيق بنجاح".
- **حقول الفورم / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `id` | (صف الجدول) | uuid/string | ✔ | — | — | `user_validation.id` (filter) |
| `userId` | (صف الجدول `req.user_id`) | uuid | ✔ | — | — | `users.id` (filter) + `interaction_logs.user_id` |
| `status` | select "الإجراءات/الحالة" | enum | ✔ | — | `active` (مقبول/نشط) | `user_validation.status`, `users.status` |

- **Endpoint مقترح (REST)**: `POST /api/v1/admin/verifications/{verification}/approve`
  - Body: `{}` — الـ `user_id` يتجاب من `user_validation.user_id` على السيرفر (متتبعتش من الكلاينت).
  - Validation: لا شيء.
  - Response: `{ "success": true, "verification": { "id": "...", "status": "active" } }` — الـ UI بيستخدم النجاح بس ويعمل refetch.
  - السيرفر لازم يعمل الـ 3 writes جوه DB transaction (+ **(مقترح)** يحدّث `rejection_reason = null`).

### رفض طلب توثيق — `/dashboard/admin/verifications` — `VerificationsTable` → `updateVerificationStatus`

- **الإجراء**: الـ trigger: اختيار أي قيمة غير `active` من الـ select (يعني `reject` **أو `pending`** — `VerificationsTable.tsx:54-88` بيروح للـ else branch لأي قيمة ≠ `active`). بيفتح SweetAlert فيه textarea؛ لو المستخدم كتب سبب (`if (reason)` سطر 78) → mutate بـ `status: "reject"`. لو ساب السبب فاضي أو cancel → مفيش أي request. **يعني اختيار "معلق" من الـ UI مستحيل يرجّع الطلب `pending`، وبيتعامل كرفض.**
  1. `user_validation` **update** `{ status: "reject", rejection_reason: reason }` where `id`.
  2. `users` **update** `{ status: "reject" }` where `id = userId`.
  3. `interaction_logs` **insert** `{ user_id, activity_type: "update", activity_text: "تم رفض طلب توثيق الحساب، راجع التنبيهات." }`.
  - Swal نجاح: "تم رفض طلب التوثيق وإرسال التنبيه" (مفيش أي إشعار فعلي غير الـ interaction_log).
- **حقول الفورم / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `id` | (صف) | uuid | ✔ | — | — | `user_validation.id` |
| `userId` | (صف) | uuid | ✔ | — | — | `users.id` |
| `status` | select | enum | ✔ | — | `reject` (غير مفعل) — وكمان `pending` (معلق) بيتحول لـ `reject` | `user_validation.status`, `users.status` |
| `reason` | Swal title "رفض الطلب"، inputLabel "يرجى كتابة سبب الرفض لتوجيه المستخدم"، placeholder "مثال: صورة البطاقة غير واضحة..." | string (textarea) | ✔ (الـ UI مبيبعتش لو فاضي) | non-empty بس | — | `user_validation.rejection_reason` (مش بيتكتب في `users.rejection_reason`) |

- **Endpoint مقترح (REST)**: `POST /api/v1/admin/verifications/{verification}/reject`
  - Body: `{ "reason": "صورة البطاقة غير واضحة" }`
  - Laravel rules: `['reason' => ['required','string']]` **(مقترح:** `max:1000`**)**.
  - Response: `{ "success": true, "verification": { "id": "...", "status": "reject", "rejection_reason": "..." } }`.
  - **(مقترح)** endpoint مستقل `POST .../{verification}/reset` لو عايزين حالة `pending` فعلاً.

### تغيير حالة اشتراك — `/dashboard/admin/subscriptions` — `SubscriptionsTable` → `updateSubscriptionStatus`

- **الإجراء** (`admin.api.ts:780-825`)، الـ trigger: `onChange` للـ select في كل صف (`SubscriptionsTable.tsx:371-410`) — مفيش confirm، والـ select بيتقفل وقت الـ pending:
  1. `subscriptions` **update** `{ status }` where `id = :id` ثم `.select("*, users(id, email, phone)").maybeSingle()`.
  2. **لو `status === "active"`** والصف رجع وفيه `user_id`:
     - `users` **update** `{ status: "active" }` where `id = user_id`.
     - `user_validation` **update** `{ status: "active" }` where `user_id = user_id`.
     - (الأخطاء في الخطوتين دول بتتجاهل.)
  3. Meta CAPI `trackMetaEvent("Purchase", …)` **متعمله comment** (`admin.api.ts:812-821`).
  - **مش بيتغير**: `starts_at`/`expires_at`/`start_date`/`end_date`، `users.selected_plan`، `users.current_step`، `auth.user_metadata`، ومفيش insert في `subscription_payments`.
  - لما الحالة تبقى `pending` أو `expired` مفيش أي side effect على `users`.
  - Swal: "تم تحديث الحالة بنجاح".
- **حقول الفورم / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `id` | (صف) | uuid/string | ✔ | — | — | `subscriptions.id` |
| `status` | select "الإجراءات/الحالة" | enum | ✔ | TS type بس | `active` (نشط)، `pending` (معلق)، `expired` (منتهي) | `subscriptions.status` (+ `users.status`/`user_validation.status` = `active` لو active) |

- **Endpoint مقترح (REST)**: `PATCH /api/v1/admin/subscriptions/{subscription}/status`
  - Body: `{ "status": "active" }`
  - Laravel rules: `['status' => ['required', Rule::in(['active','pending','expired'])]]`
  - Response: `{ "success": true, "subscription": { "id", "status", "user_id" } }`.
  - **(مقترح)** عند التفعيل: حساب `starts_at = now()` و`expires_at` حسب `billing_cycle`، وإنشاء صف في `subscription_payments` (لأن صفحة المالية بتقرا منه ومفيش أي كود فرونت بيكتب فيه)، وتحديث `users.current_step`/metadata لو مطلوب — دي قرارات business لازم تتأكد.

### تفعيل باقة يدوياً — `/dashboard/admin/subscriptions` (Component متعمله comment) — `ManualSubscription` → `manuallyOverrideSubscription`

- **الإجراء** (`admin.api.ts:303-330`)، الـ UI (`ManualSubscription.tsx:15-41`): لو `userId.trim()` فاضي → Swal warning "يرجى إدخال معرف المستخدم". بعدين SweetAlert confirm "تأكيد تفعيل الباقة" بنص `سيتم تفعيل باقة ${planId} لهذا المستخدم لمدة ${days} يوم. هل أنت متأكد؟`.
  1. `startsAt = now`, `expiresAt = now + days` (بالأيام).
  2. `subscriptions` **upsert** `{ user_id, plan_id: planId, status: "active", starts_at, expires_at }` — **من غير `onConflict`** (يعني على الـ PK، ومفيش `id` في الـ payload ⇒ عملياً INSERT). باقي الكود بيعمل upsert على `onConflict: "user_id"` (`features/auth/payment/api/payment.api.ts:34-45`)، فلو فيه unique على `user_id` العملية دي هتفشل بـ duplicate key للمستخدم اللي عنده اشتراك. كمان بيكتب في `plan_id` مش `plan_type` (الفلو العادي بيكتب `plan_type`).
  3. `users` **update** `{ selected_plan: planId }` where `id = userId` (الـ error بيتجاهل).
  - Swal "تم تفعيل الباقة بنجاح".
- **حقول الفورم / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `userId` | "معرف المستخدم (User ID)" (input `dir=ltr`، placeholder "أدخل User ID الخاص بالمستخدم...") | string (uuid) | ✔ | `trim()` non-empty | — | `subscriptions.user_id`, `users.id` (filter) |
| `planId` | "نوع الباقة" select | enum | ✔ | — | `basic` (الأساسية)، `professional` (الاحترافية) **default**، `premium` (المميزة) | `subscriptions.plan_id`, `users.selected_plan` |
| `days` | "فترة الصلاحية (بالأيام)" input number | integer | ✔ | `min="1"` HTML بس، `Number(value)` | default `365` | بيتحسب منه `subscriptions.expires_at` |

- **Endpoint مقترح (REST)**: `POST /api/v1/admin/subscriptions/manual`
  - Body: `{ "user_id": "uuid", "plan_id": "professional", "days": 365 }`
  - Laravel rules: `['user_id' => ['required','uuid','exists:users,id'], 'plan_id' => ['required', Rule::in(['basic','professional','premium'])], 'days' => ['required','integer','min:1']]`
  - Response: `{ "success": true, "subscription": { "id", "user_id", "plan_type", "status", "starts_at", "expires_at" } }`.
  - **(مقترح)** `updateOrCreate(['user_id'=>…])` بدل insert، وكتابة `plan_type` (مع `plan_id` لو هيتساب للتوافق).

### إعادة إرسال رابط تفعيل البريد — `/dashboard/admin/users` — `UserManagementTable` → `adminResendUserConfirmation`

- **الإجراء** (`admin.api.ts:233-249`)، الزرار "إرسال الرابط" بيظهر لكل مستخدم `user_type !== "admin"` (`UserManagementTable.tsx:212-222`) بغض النظر عن حالة تأكيد الإيميل. لو `email` فاضي → Swal error "البريد الإلكتروني للمستخدم غير متاح".
  - `supabase.auth.resend({ type: "signup", email, options: { emailRedirectTo } })`
  - `emailRedirectTo = accountType === "customer" ? `${getAppUrl()}/customer/setup` : `${getAppUrl()}/pricing/${accountType}``؛ `getAppUrl()` = `window.location.origin` (`lib/utils.ts:33-43`).
  - مفيش أي DB write.
  - Swal نجاح: "تم إعادة إرسال رابط التفعيل للمستخدم بنجاح!"، فشل: رسالة الخطأ أو "فشل إعادة إرسال الرابط، يرجى المحاولة لاحقاً".
- **حقول / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `email` | (صف `user.email`) | string | ✔ | non-empty | — | `users.email` (قراءة) |
| `accountType` | (صف `user.user_type`) | string | ✔ | — | `engineer`, `office`, `exhibition`, `material`, `customer` | بيحدد الـ redirect URL |

- **Endpoint مقترح (REST)**: `POST /api/v1/admin/users/{user}/resend-verification`
  - Body: `{}` (السيرفر ياخد `email` و`user_type` من `users`).
  - Response: `{ "success": true }`.
  - المنطق: `customer` → رابط `/customer/setup`؛ غير كده → `/pricing/{user_type}`. **(مقترح)** يرجّع 422 لو الإيميل متأكد بالفعل.

### تعديل ملف مهندس/مكتب وحالته — `/dashboard/admin/engineers/[id]` و `/dashboard/admin/offices/[id]` — `ProfessionalDetails` → `adminUpdateProfessionalProfile` (+ `uploadToCloudinary`)

- **الإجراء**: زرار "تعديل البيانات" يفتح edit mode، و"حفظ" ينفّذ `handleSave` (`ProfessionalDetails.tsx:121-177`):
  1. لو اتختارت صورة جديدة (`input type=file accept="image/*"` سطر 413-418، بدون validation حجم/نوع غير الـ accept) → `uploadToCloudinary(file, "profiles")` → URL. غير كده `profile_image` القديم.
  2. `experience_years` و`count_of_projects`: `parseInt(toEnglishDigits(value) || "0")` (بيحوّل الأرقام العربية ٠-٩).
  3. يبني payload:
     - `userData = { name, phone, status, rejection_reason: status === "reject" ? rejection_reason : null }`
     - `profileData = { name, governorate, bio, whats_app, experience_years, profile_image, facebook, instagram, youtube, video, ...(engineer ? { major, skills } : { count_of_projects }) }`
  4. `adminUpdateProfessionalProfile` (`admin.api.ts:989-1021`):
     - لو `userData` فيه keys → `users` **update** `userData` where `id = userId` (error: "فشل تحديث المستخدم: …").
     - `engineers` أو `offices` **upsert** `{ ...profileData, user_id: userId, updated_at: now }` بـ `onConflict: "user_id"` (error: "فشل تحديث الملف الشخصي: …").
  - **مش بيتعمل**: مزامنة `user_validation.status`، تحديث `auth.user_metadata`، `interaction_logs`، تعديل `current_step`. (بعكس فلو التوثيق).
  - Swal "تم حفظ البيانات بنجاح".
  - القيم الابتدائية للفورم (`ProfessionalDetails.tsx:76-96`): `name/phone/status/rejection_reason` من `users`، والباقي من صف `engineers`/`offices`؛ `status` default `"pending"`؛ `experience_years` و`count_of_projects` default `"0"`.
- **حقول الفورم / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation (في الكود) | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `userData.name` | "الاسم بالكامل" | string | ✗ | لا شيء | — | `users.name` |
| `userData.phone` | "الهاتف" (dir ltr) | string | ✗ | لا شيء | — | `users.phone` |
| `userData.status` | select في الهيدر | enum | ✔ (دايماً بيتبعت) | — | `active` (مفعل / نشط)، `pending` (معلق / قيد المراجعة)، `reject` (مرفوض) | `users.status` |
| `userData.rejection_reason` | input "سبب الرفض..." (يظهر بس لو status=`reject`) | string\|null | ✗ | بيتبعت `null` لو status ≠ `reject` | — | `users.rejection_reason` |
| `profileData.name` | نفس "الاسم بالكامل" | string | ✗ | — | — | `engineers.name` / `offices.name` |
| `profileData.governorate` | "المحافظة" `MultiSelect` | string (CSV مفصول بـ `,`) | ✗ | `maxSelected={5}` | الـ 27 محافظة من `egyptGovernorates` (`Static/directoryConfig.ts:10-38`): القاهرة، الجيزة، الإسكندرية، الدقهلية، الشرقية، القليوبية، الغربية، المنوفية، البحيرة، كفر الشيخ، دمياط، بورسعيد، الإسماعيلية، السويس، الفيوم، بني سويف، المنيا، أسيوط، سوهاج، قنا، الأقصر، أسوان، البحر الأحمر، الوادي الجديد، مطروح، شمال سيناء، جنوب سيناء | `{table}.governorate` |
| `profileData.bio` | "النبذة التعريفية (Bio)" / للمكتب "فلسفة التصميم / النبذة" (textarea) | text | ✗ | — | — | `{table}.bio` |
| `profileData.whats_app` | "واتساب" (dir ltr) | string | ✗ | — | — | `{table}.whats_app` |
| `profileData.experience_years` | "سنوات الخبرة" (number) | integer | ✗ | `parseInt(toEnglishDigits)` | — | `{table}.experience_years` |
| `profileData.profile_image` | الصورة الدائرية (file `image/*`) | URL string | ✗ | — | Cloudinary folder `profiles` | `{table}.profile_image` |
| `profileData.facebook` | "فيسبوك" | string (URL) | ✗ | — | — | `{table}.facebook` |
| `profileData.instagram` | "انستقرام" | string (URL) | ✗ | — | — | `{table}.instagram` |
| `profileData.youtube` | "يوتيوب" | string (URL) | ✗ | — | — | `{table}.youtube` |
| `profileData.video` | "فيديو تعريفي" (placeholder "رابط فيديو (Youtube / Drive)...") | string (URL) | ✗ | — | — | `{table}.video` |
| `profileData.major` *(engineer فقط)* | "التخصص" `SingleSelect` | string | ✗ | — | `engineerMajors` (`features/auth/profile/api/profile.static.ts:49-52`): value `معماري` (label عمارة)، value `مدني` (label مدني) | `engineers.major` |
| `profileData.skills` *(engineer فقط)* | "المهارات (Skills)" input نصي "أدخل المهارات مفصولة بفواصل (,)" | string[] | ✗ | `split(",").map(trim).filter(Boolean)` | — | `engineers.skills` (array) |
| `profileData.count_of_projects` *(office فقط)* | "عدد المشاريع" (number) | integer | ✗ | `parseInt(toEnglishDigits)` | — | `offices.count_of_projects` |
| `profileData.updated_at` | (تلقائي) | timestamp | — | — | `now()` | `{table}.updated_at` |

  الإيميل والباقة (`selected_plan`) وتاريخ التسجيل بيتعرضوا read-only.
- **Endpoint مقترح (REST)**: `PUT /api/v1/admin/engineers/{user}` و `PUT /api/v1/admin/offices/{user}` (`{user}` = `users.id`)
  - Body (JSON؛ الصورة إما multipart `profile_image` file أو URL جاهز لو الفرونت هيفضل يرفع على Cloudinary):
    ```json
    {
      "name": "م. أحمد علي",
      "phone": "01012345678",
      "status": "reject",
      "rejection_reason": "بيانات غير مكتملة",
      "governorate": "القاهرة,الجيزة",
      "bio": "…",
      "whats_app": "01012345678",
      "experience_years": 7,
      "profile_image": "https://res.cloudinary.com/…",
      "facebook": "https://facebook.com/…",
      "instagram": "",
      "youtube": "",
      "video": "",
      "major": "معماري",
      "skills": ["AutoCAD", "Revit"]
    }
    ```
    (للمكتب: `count_of_projects` بدل `major`/`skills`.)
  - Laravel rules **(مقترح — الكود مفيهوش validation)**:
    ```php
    [
      'name' => ['required','string','max:255'],
      'phone' => ['nullable','string','max:20'],
      'status' => ['required', Rule::in(['active','pending','reject'])],
      'rejection_reason' => ['nullable','required_if:status,reject','string'],
      'governorate' => ['nullable','string'], // CSV، max 5 عناصر من قائمة المحافظات
      'bio' => ['nullable','string'],
      'whats_app' => ['nullable','string','max:20'],
      'experience_years' => ['nullable','integer','min:0'],
      'profile_image' => ['nullable','string','url'], // أو 'image' لو multipart
      'facebook' => ['nullable','string'], 'instagram' => ['nullable','string'],
      'youtube' => ['nullable','string'], 'video' => ['nullable','string'],
      // engineer:
      'major' => ['nullable', Rule::in(['معماري','مدني'])],
      'skills' => ['nullable','array'], 'skills.*' => ['string'],
      // office:
      'count_of_projects' => ['nullable','integer','min:0'],
    ]
    ```
  - السلوك: `rejection_reason = null` لو `status != reject`؛ update `users` + `updateOrCreate` على `engineers|offices` by `user_id` جوه transaction.
  - Response: نفس shape بتاع `GET /api/v1/admin/{engineers|offices}/{user}` (عشان الـ UI بيعمل invalidate ويعيد التحميل).

### إضافة صنف سعر مادة — `/dashboard/admin/prices` — `PriceEditor` → `upsertMaterialPrice`

- **الإجراء**: زرار "إضافة صنف جديد" (`PriceEditor.tsx:31-41`) يضيف صف input بقيم default `{ category: "حديد", item_name: "", unit: "طن", price: 0, change_percent: 0 }`. "حفظ" (`handleSave` سطر 43-59): لو `!item_name || !price` → Swal warning "يرجى إكمال البيانات الأساسية" (يعني **`price = 0` مرفوض**). بعدين `delete payload.id` لو `id === "new"` (عملياً الـ formData في الإضافة مفيهاش `id` أصلاً).
  - `materials_prices` **upsert** `payload` (من غير onConflict ⇒ PK `id`؛ مفيش id ⇒ INSERT) ثم `.select().single()`.
  - Swal "تم حفظ السعر بنجاح".
- **حقول الفورم / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `category` | "التصنيف" (text) | string | ✗ (default "حديد") | — | نص حر (الـ page description بيذكر حديد، أسمنت، خرسانة، طوب) | `materials_prices.category` |
| `item_name` | "اسم الصنف" (placeholder "اسم الصنف...") | string | ✔ | truthy | — | `materials_prices.item_name` |
| `unit` | "الوحدة" (text) | string | ✗ (default "طن") | — | نص حر | `materials_prices.unit` |
| `price` | "السعر (ج.م)" (number) | number | ✔ | truthy (≠ 0)، `Number(value)` | — | `materials_prices.price` |
| `change_percent` | "التغير (%)" (number step 0.1) | number | ✗ (default 0) | `Number(value)`، ممكن سالب | — | `materials_prices.change_percent` |

- **Endpoint مقترح (REST)**: `POST /api/v1/admin/material-prices`
  - Body: `{ "category": "حديد", "item_name": "حديد عز 16مم", "unit": "طن", "price": 38500, "change_percent": -1.5 }`
  - Laravel rules: `['category' => ['required','string','max:100'], 'item_name' => ['required','string','max:255'], 'unit' => ['required','string','max:50'], 'price' => ['required','numeric','gt:0'], 'change_percent' => ['nullable','numeric']]` (الـ max أطوال مقترحة).
  - Response: الصف المنشأ `{ id, category, item_name, unit, price, change_percent, ... }`.
  - ده نفس الجدول اللي الصفحة الرئيسية بتقراه (`features/prices/api/prices.api.ts:19-38` → `category,item_name,unit,price,change_percent`).

### تعديل صنف سعر مادة — `/dashboard/admin/prices` — `PriceEditor` → `upsertMaterialPrice`

- **الإجراء**: أيقونة القلم (`handleEdit` سطر 25-29) بتحط **الصف كله** (`select("*")`) في `formData` — يعني الـ upsert بيبعت كل أعمدة الصف (بما فيها `id` وأي أعمدة تانية زي `created_at` لو موجودة). نفس validation الإضافة. `materials_prices` **upsert** (على `id` ⇒ UPDATE) ثم `.select().single()`.
- **حقول الفورم / المدخلات**: نفس جدول الإضافة + :

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `id` | (الصف) | number \| string (TS: `number \| string`) | ✔ | — | — | `materials_prices.id` |

- **Endpoint مقترح (REST)**: `PUT /api/v1/admin/material-prices/{id}` — نفس body ونفس rules الإضافة (السيرفر يتجاهل أي أعمدة غير الخمسة). Response: الصف بعد التعديل.

### حذف صنف سعر مادة — `/dashboard/admin/prices` — `PriceEditor` → `deleteMaterialPrice`

- **الإجراء** (`admin.api.ts:351-358`): SweetAlert confirm "هل أنت متأكد؟ / سيتم حذف هذا الصنف من جدول الأسعار نهائياً." → `materials_prices` **delete** where `id`. Hard delete. Swal "تم الحذف بنجاح".
- **المدخلات**: `id` (number|string) → `materials_prices.id`.
- **Endpoint مقترح (REST)**: `DELETE /api/v1/admin/material-prices/{id}` → `204` أو `{ "success": true }`.

### ترشيح/تعيين مكتب أو مهندس لطلب عميل (وإعادة الترشيح بعد الرفض) — `/dashboard/admin/customers` — `AdminCustomerRequestsTable` → `assignProviderToCustomerRequest`

- **الـ Triggers في الـ UI**: زرار "ترشيح وتعيين مكتب" (لو مفيش مكتب)، زرار "تعيين/تغيير" في عمود الإجراءات، "ترشيح مكتب/تغيير المكتب" في الـ cards، "تعيين مكتب/تغيير المكتب" في مودال التفاصيل، و"ترشيح مكتب بديل الآن" في مودال سبب الرفض. كلهم بيفتحوا مودال "ترشيح وتعيين مكتب هندسي للطلب" (`AdminCustomerRequestsTable.tsx:1291-2031`).
  - `handleOpenAssignModal` (سطر 216-224): يصفّر الاختيار والفلاتر، و**يملأ textarea الملاحظات بـ `req.notes` الحالية**.
  - قايمة المرشحين من `getAssignableProviders` (شوف القراءات) وبتتفلتر client-side (سطر 159-213):
    - نوع: `all` | `office` | `engineer` (أزرار "الكل (n)" / "مكاتب (n)" / "مهندسين (n)").
    - الباقة: `all` | `premium` (بريميوم) | `professional` (احترافية، بيقبل كمان `pro`) | `basic` (أساسية) | `free` (مجانية = `free` أو plan فاضي).
    - الخدمة: `all` أو اسم من `ENGINEERING_SERVICES_15` — match لو أي خدمة من خدمات المرشح `includes` الفلتر أو العكس (lowercase).
    - بحث نصي في `name`/`governorate`/`slug`/`services`.
    - Quick buttons: "يقدم خدمة ({service الطلب})" (toggle لفلتر الخدمة)، "محافظة العميل ({governorate})" (بيحط المحافظة في البحث النصي)، "إعادة ضبط الفلاتر".
  - كل مرشح بيعرض: صورة، الاسم، مكتب/مهندس، badge الباقة، "يقدم {service}" لو match، المحافظة، الخبرة، التقييم، وموافقة/رفض/إجمالي الترشيحات + "معدل القبول" = `round(approved / (approved + refused) * 100)` (ألوان ≥70 / ≥40 / أقل؛ و"مكتب جديد" لو مفيش قرارات)، وأول 3 خدمات.
  - كارت "المكتب المختار" بيعرض نفس المقاييس + تقييم نصي: ≥75 "ممتاز"، ≥50 "جيد"، أقل "منخفض".
  - Submit (`handleConfirmAssign` سطر 227-251): لو مفيش مرشح مختار → Swal info "يرجى اختيار مكتب أو مهندس أولاً" (والزرار disabled أصلاً). بيبعت `{ requestId, providerId: selectedProvider.id, providerName: selectedProvider.name, adminNotes: adminNotes.trim() }`. Swal نجاح: "تم التعيين بنجاح!" + "تم ترشيح {name} للطلب. سيظهر للعميل في لوحة تحكمه لاتخاذ القرار."
- **الإجراء** (`admin.api.ts:1842-1967`):
  1. **Resolve الـ UUID بتاع المرشح** (`providerId` ممكن يكون UUID أو slug أو اسم):
     - لو `providerId` UUID صالح (regex `^[0-9a-f]{8}-…-[0-9a-f]{12}$`) → يستخدمه.
     - غير كده بالترتيب، وأول نتيجة UUID صالحة بتكسب: `users.select("id").or(slug.eq.{id},name.eq.{name}).limit(1)` → `offices.select("user_id")` بنفس الشرط → `engineers.select("user_id")` بنفس الشرط → `public_offices_view.select("email").or(slug,name,office_name)` و`public_engineers_view.select("email").or(slug,name,user_name)` → RPC `check_user_exists({ p_email: email.toLowerCase().trim() })` → `data.user_id`.
  2. يقرا الطلب الحالي: `customers.select("assigned_provider_name, customer_response, customer_reason, notes").eq("id")`.
     - لو `customer_response === "refused"` و`assigned_provider_name` موجود → يكوّن سطر تاريخ: `[رفض سابق: {assigned_provider_name}{ - السبب: {customer_reason} لو موجود}]`؛ `base = adminNotes !== undefined ? adminNotes : (notes || "")`؛ `notes = base ? base + "\n" + log : log`.
  3. `customers` **update** where `id = requestId`:
     ```
     assigned_provider_id   = UUID لو اتحل، غير كده null
     assigned_provider_name = providerName
     assigned_at            = now()
     customer_response      = null
     customer_reason        = null
     status                 = "pending"
     updated_at             = now()
     notes                  = updatedNotes   (لو مش undefined)
     ```
     ثم `.select().single()` → يرجّع الصف (error: "فشل تعيين المكتب: …").
  - **مهم**: الـ UI دايماً بيبعت `adminNotes` (حتى لو string فاضي)، يعني **الـ `notes` بتاعة العميل بتتستبدل بمحتوى الـ textarea** (اللي متعبي مبدئياً بنفس الـ notes)؛ لو الأدمن مسحها بتتمسح.
  - **مهم**: نص `[رفض سابق: …]` جوه `notes` هو المصدر الوحيد لتاريخ الرفض — الإحصائيات بتعمله parse بـ regex `/\[رفض سابق:\s*([^-\]\n]+)/g` (شوف قراءة طلبات العملاء).
  - مفيش إشعار للمكتب/المهندس ولا `interaction_logs`.
- **حقول الفورم / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `requestId` | (الطلب المفتوح) | uuid | ✔ | — | — | `customers.id` (filter) |
| `providerId` | الاختيار من قايمة المرشحين | string (UUID أو slug أو اسم) | ✔ | زرار الحفظ disabled لو مفيش اختيار | `IAssignableProvider.id` | `customers.assigned_provider_id` (لو UUID بس، غير كده `null`) |
| `providerName` | اسم المرشح المختار | string | ✔ | — | `IAssignableProvider.name` | `customers.assigned_provider_name` |
| `adminNotes` | "ملاحظات إضافية على الطلب (اختياري)" textarea rows=2، placeholder "ملاحظات توضيحية لمتطلبات المشروع..." | string | ✗ (بس دايماً بيتبعت، `trim()`) | — | default = `req.notes` | `customers.notes` (+ سطر `[رفض سابق: …]`) |

- **Endpoint مقترح (REST)**: `POST /api/v1/admin/customer-requests/{customer}/assign`
  - Body: `{ "provider_user_id": "uuid", "notes": "ملاحظات…" }` — السيرفر يجيب اسم المرشح (`offices.name`/`engineers.name`) ونوعه من الـ DB بدل ما يعتمد على اسم جاي من الكلاينت، ويلغي منطق الـ resolve بالاسم/slug.
  - Laravel rules: `['provider_user_id' => ['required','uuid', Rule::exists('users','id')->whereIn('user_type',['office','engineer'])], 'notes' => ['nullable','string']]`
  - **(مقترح)** جدول تاريخ `customer_request_assignments (customer_id, provider_user_id, provider_name, assigned_at, customer_response, customer_reason, responded_at)` بدل تخزين التاريخ كنص في `notes`، مع الحفاظ على نفس المخرجات (counts) اللي الـ UI بيعرضها.
  - Response: الطلب بعد التحديث (نفس shape العنصر في `GET /api/v1/admin/customer-requests`).

### تغيير حالة طلب عميل — `/dashboard/admin/customers` — `AdminCustomerRequestsTable` → `updateCustomerRequestStatus`

- **الإجراء**: select في عمود "الحالة" (جدول فقط، مش في الـ cards) (`AdminCustomerRequestsTable.tsx:1025-1037`)، `onChange` مباشرة من غير confirm، `value = req.status || "pending"`. `handleQuickStatusChange` (سطر 254-267) بيبعت `{ requestId, status }` بس (من غير notes).
  - `customers` **update** `{ status, updated_at: now(), notes? }` where `id` ثم `.select().single()` (`admin.api.ts:1972-1995`). مفيش validation للقيمة في الـ API.
  - Swal "تم التحديث!" / "تم تغيير حالة الطلب بنجاح."
- **حقول / المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `requestId` | (صف) | uuid | ✔ | — | — | `customers.id` |
| `status` | select "الحالة" | enum string | ✔ | — | `pending` (قيد المراجعة)، `assigned` (تم التعيين)، `approved` (معتمد)، `completed` (مكتمل ومسلّم)، `cancelled` (ملغي) | `customers.status` |
| `notes` | (الـ API بيقبلها بس الـ UI مش بيبعتها) | string | ✗ | — | — | `customers.notes` |

- **Endpoint مقترح (REST)**: `PATCH /api/v1/admin/customer-requests/{customer}/status`
  - Body: `{ "status": "completed" }` (+ `notes` اختياري)
  - Laravel rules: `['status' => ['required', Rule::in(['pending','assigned','approved','completed','cancelled'])], 'notes' => ['sometimes','nullable','string']]`
  - Response: الطلب بعد التحديث.

### حذف طلب عميل — `/dashboard/admin/customers` — `AdminCustomerRequestsTable` → `deleteCustomerRequest`

- **الإجراء**: زرار السلة في عمود الإجراءات (table view) وزرار "حذف الطلب" في مودال التفاصيل (الـ cards view مفيهاش حذف). `window.confirm("هل أنت متأكد من رغبتك في حذف طلب العميل \"{full_name || بدون اسم}\" نهائياً؟")`.
  - `customers` **delete** where `id` (Hard delete — `admin.api.ts:2000-2008`). Swal "تم الحذف!" / "تم حذف الطلب نهائياً." ويقفل مودال التفاصيل لو مفتوح على نفس الطلب.
  - ملاحظة: جانب العميل بيعمل **soft delete** (`status = "deleted"`) كـ fallback (`features/customer/api/customer.api.ts:240-270`)، والأدمن بيستثني `deleted` من الإحصائيات المحسوبة بس مش من الجدول.
- **المدخلات**: `requestId` (uuid) → `customers.id`.
- **Endpoint مقترح (REST)**: `DELETE /api/v1/admin/customer-requests/{customer}` → `{ "success": true }`.

### تسجيل نقرة تواصل (واتساب/اتصال) على طلب عميل — `/dashboard/admin/customers` — `AdminCustomerRequestsTable` → `trackCustomerContactClick` (من `features/customer/api/customer.api.ts:300-339`)

- **الإجراء**: الضغط على badge الواتساب (`https://wa.me/{phone digits only}`) أو الاتصال (`tel:{phone}`) في الجدول (سطر 817-853)، الـ cards (1195-1228)، ومودال التفاصيل (2155-2191). الأدمن بيعدّ النقرات بتاعته هو كمان.
  1. `customers.select("id, whats_count, calls_count").eq("id")`.
  2. `customers` **update** `{ [whats_count|calls_count]: current + 1, updated_at: now() }` where `id` (read-modify-write مش atomic). أي error بيتبلع (console فقط). مفيش invalidation (العداد في الشاشة مبيتحدثش لحد الـ refetch).
- **المدخلات**:

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `requestId` | (صف) | uuid | ✔ | — | — | `customers.id` |
| `type` | أيقونة واتساب / اتصال | enum | ✔ | — | `whatsapp` → `whats_count`، `call` → `calls_count` | `customers.whats_count` / `customers.calls_count` |

- **Endpoint مقترح (REST)**: `POST /api/v1/service-requests/{id}/contact-clicks` (shared مع لوحة العميل/المكتب — نفس الفانكشن) Body `{ "type": "whatsapp" }`، rules `['type' => ['required', Rule::in(['whatsapp','call'])]]`، تنفيذ بـ `increment()` atomic. Response `{ "whats_count": n, "calls_count": m }`.

### تبديل ظهور الباقات في الصفحة الرئيسية — `/dashboard/admin` — `HomepageDisplayControls` → `POST /api/admin/homepage-display`

- **الإجراء** (`HomepageDisplayControls.tsx:107-158`): كل toggle (8 toggles = مجموعتين × 4 باقات) بيبني الـ object الكامل بعد قلب القيمة، يحدّث الـ state optimistically ويخزّنه في `localStorage["homepage_display_settings"]`، ثم `fetch POST /api/admin/homepage-display` بالـ object كله. لو `!res.ok` → Swal error ورجوع للقيمة القديمة. Swal نجاح: `تم تحديث ظهور {المكاتب|المهندسين} ({عنوان الباقة}) بنجاح`.
  - الـ route (`app/api/admin/homepage-display/route.ts:24-53`):
    - لو `!body || !body.offices || !body.engineers` → `400 { error: "Invalid settings format" }`.
    - `saveHomepageDisplaySettings(body)` → `fs.writeFileSync("data/homepage-display-settings.json", JSON.stringify(settings, null, 2))` (`lib/homepage-display-settings.server.ts:26-36`) — **أي error في الكتابة بيتبلع والـ route بيرجّع success برضه**. مفيش validation على أنواع القيم.
    - `revalidatePath("/", "page")` و`revalidatePath("/api/homepage")`.
    - Response: `{ success: true, settings: body }`؛ `500 { error: "Failed to save settings" }` لو exception.
    - **مفيش أي auth check**.
  - الأثر: `lib/homepage-data.server.ts:25-35` بيفلتر المكاتب والمهندسين في الصفحة الرئيسية بـ `isPlanAllowed(selected_plan, settings.offices|engineers)` ويرتبهم بـ `getPlanWeight` (premium 30 > professional/pro 20 > basic 10 > free 0)؛ `normalizePlanTier`: null/فاضي/أي قيمة غير معروفة → `free`، `pro` → `professional`.
- **حقول / المدخلات** (Body كامل):

| key (as sent / DB column) | الاسم في الواجهة | type | required | validation | allowed values / options | → table.column |
|---|---|---|---|---|---|---|
| `offices.premium` | المكاتب الهندسية → "الباقة المميزة" | boolean | ✔ (object `offices` لازم يكون موجود) | وجود `offices` بس | default `true` | ملف JSON (مقترح: `settings`) |
| `offices.professional` | المكاتب → "الباقة الاحترافية" | boolean | ✔ | — | default `true` | نفسه |
| `offices.basic` | المكاتب → "الباقة الأساسية" | boolean | ✔ | — | default `true` | نفسه |
| `offices.free` | المكاتب → "الباقة المجانية" | boolean | ✔ | — | default `false` | نفسه |
| `engineers.premium` | المهندسون → "الباقة المميزة" | boolean | ✔ | وجود `engineers` بس | default `true` | نفسه |
| `engineers.professional` | المهندسون → "الباقة الاحترافية" | boolean | ✔ | — | default `true` | نفسه |
| `engineers.basic` | المهندسون → "الباقة الأساسية" | boolean | ✔ | — | default `true` | نفسه |
| `engineers.free` | المهندسون → "الباقة المجانية" | boolean | ✔ | — | default `false` | نفسه |

  (الـ defaults من `DEFAULT_DISPLAY_SETTINGS` في `lib/homepage-display-settings.ts:13-26`، وهي نفس محتوى `data/homepage-display-settings.json` الحالي.)
- **Endpoint مقترح (REST)**: `PUT /api/v1/admin/settings/homepage-display`
  - Body:
    ```json
    { "offices":   { "premium": true, "professional": true, "basic": true, "free": false },
      "engineers": { "premium": true, "professional": true, "basic": true, "free": false } }
    ```
  - Laravel rules: `['offices' => ['required','array'], 'offices.premium' => ['required','boolean'], 'offices.professional' => ['required','boolean'], 'offices.basic' => ['required','boolean'], 'offices.free' => ['required','boolean'], 'engineers' => ['required','array'], 'engineers.premium' => ['required','boolean'], 'engineers.professional' => ['required','boolean'], 'engineers.basic' => ['required','boolean'], 'engineers.free' => ['required','boolean']]`
  - التخزين **(مقترح)**: جدول `settings (key = 'homepage_display', value JSON)` بدل ملف على الـ filesystem، + cache invalidation لـ endpoint الصفحة الرئيسية العام.
  - Response: `{ "success": true, "settings": { ... } }` (الـ UI بيشيك `res.ok` بس).

---

### القراءات (Data Reads)

### إحصائيات عامة + Funnel التسجيل — `getAdminOverviewStats` → `AdminStatCards` (`/dashboard/admin`) و `OnboardingStatsGrid` (`/dashboard/admin/users`)

- **Function** (`admin.api.ts:25-139`) — كلها `count: "exact", head: true` (عدد بس):

| Key | المصدر والفلتر |
|---|---|
| `engineers` | `users` where `user_type = 'engineer'` |
| `offices` | `users` where `user_type = 'office'` |
| `activeEngineers` | count `public_engineers_view` |
| `activeOffices` | count `public_offices_view` |
| `exhibitions` | count `public_exhibitions_view` |
| `materials` | count `public_materials_view` (⛔ في Laravel بيتشال، والموردين بيتحسبوا ضمن `exhibitions`، 0.5) |
| `visits` | count صفوف `stats` where `metric_type = 'visit'` **+** `SUM(analytics_counts.total_count)` where `stat_type = 'page'` |
| `customers` | `users` where `user_type = 'customer'` |
| `pendingVerifications` | `user_validation` where `status = 'pending'` |
| `flaggedReviews` | ثابت `0` (placeholder) |
| `awaitingVerification` | `users` where `current_step = 1` AND `user_type NOT IN ('customer','admin')` |
| `selectingPlan` | `users` where `current_step = 2` AND نفس استثناء الأنواع |
| `awaitingProfile` | `users` where `current_step = 3` AND نفس الاستثناء |
| `activeOnboarded` | `users` where `current_step >= 4` AND `status = 'active'` AND نفس الاستثناء |
| `rejected` | `users` where `status IN ('reject','rejected')` AND نفس الاستثناء (أي step) |

  ملاحظة: مستخدم `current_step = 0` أو (`>= 4` وحالته مش `active` ولا مرفوض) مش بيتعد في أي bucket، ومستخدم مرفوض في step 1-3 بيتعد مرتين (في الـ step وفي `rejected`).
- **حسابات في الـ UI** (لازم الـ API يرجّع الخام والفرونت يفضل يحسب، أو يرجعها جاهزة):
  - `AdminStatCards.tsx:20-55`: "إجمالي زيارات المنصة" = `visits + 1000` ويتعرض بعدها `"+"` (رقم مضخم يدوياً)؛ "إجمالي المهنيين" = `engineers + offices + exhibitions + materials` (خليط: مهندسين/مكاتب كل المسجلين، معارض/موردين النشطين بس)، والـ subtitle = `activeEngineers + activeOffices` + " حساب نشط"؛ "العملاء المسجلين" = `customers`؛ "طلبات التوثيق المعلقة" = `pendingVerifications` (highlight لو > 0).
  - `OnboardingStatsGrid.tsx:23-91`: `totalProfessionals = awaitingVerification + selectingPlan + awaitingProfile + activeOnboarded + rejected`؛ نسبة كل كارت = `round(value / totalProfessionals * 100)` (0 لو الإجمالي 0). الكروت: الخطوة 1 "بانتظار التوثيق" (سجل ولم يرفع المستندات)، 2 "اختيار الباقة"، 3 "بانتظار إكمال الملف"، 4 "نشط / مفعل"، و"طلبات مرفوضة".
- **Endpoint مقترح**: `GET /api/v1/admin/stats/overview`
  ```json
  { "engineers": 120, "activeEngineers": 80, "offices": 60, "activeOffices": 41,
    "exhibitions": 21, "customers": 340, "visits": 15200,
    "pendingVerifications": 7, "flaggedReviews": 0,
    "awaitingVerification": 15, "selectingPlan": 22, "awaitingProfile": 9,
    "activeOnboarded": 121, "rejected": 4 }
  ```

### إعدادات الظهور في الصفحة الرئيسية — `GET /api/admin/homepage-display` → `HomepageDisplayControls`

- عند الـ mount: يقرا `localStorage["homepage_display_settings"]` الأول (عرض فوري) ثم `fetch GET`؛ لو الرد فيه `offices` و`engineers` يحطه في الـ state والـ localStorage.
- الـ route (`route.ts:11-22`): `getHomepageDisplaySettings()` → يقرا الملف ويعمل merge مع `DEFAULT_DISPLAY_SETTINGS` (per group)؛ لو الملف مش موجود/بايظ يرجع الـ defaults؛ `500 { error: "Failed to load settings" }` لو exception.
- **Endpoint مقترح**: `GET /api/v1/admin/settings/homepage-display` → `{ "offices": {premium,professional,basic,free}, "engineers": {premium,professional,basic,free} }` (+ الـ endpoint العام للصفحة الرئيسية يطبّق نفس الفلترة/الترتيب على السيرفر).

### دليل المستخدمين — `getAdminUsers` → `UserManagementTable` (`/dashboard/admin/users`)

- **Function** (`admin.api.ts:252-301`): `users.select("*", { count: "exact" })`.
  - Search (debounce 500ms في الـ UI، ويرجع لصفحة 1): `or(name.ilike.%q%, email.ilike.%q%, phone.ilike.%q%)` — على **كل** الأنواع بما فيها `admin` و`customer`.
  - Sort `sortBy` (toggle زرار + ضغط على هيدر "الباقة"):
    - `plan` (default): DB `order(selected_plan desc nulls last)` ثم `created_at desc`، **وبعد الـ `range`** بيعيد ترتيب الصفحة الحالية بس بالوزن: `premium=30`، `professional|pro=20`، `basic=10`، غير كده `0`، والتعادل بـ `created_at desc`. يعني الترتيب عبر الصفحات أبجدي (`professional` > `pro` > `premium` > `free` > `basic`) والترتيب جوه الصفحة بالوزن.
    - `date`: `created_at desc`.
  - Pagination: server-side `range(from, to)`، 20 لكل صفحة. `total = count`.
- **الأعمدة المستخدمة في الـ UI**: `id`, `name`, `email`, `phone`, `user_type` (engineer→مهندس، office→مكتب، exhibition→معرض، material→مورد، customer→عميل، غير كده→مدير), `selected_plan` (premium→مميزة، professional/pro→احترافية، basic→أساسية، غير كده→مجانية), `status`, `rejection_reason`, `current_step`, `created_at`, `slug` (لينك "زيارة الملف" → `/directory/{engineers|offices|exhibitions|materials}/{slug}` لغير العميل/الأدمن).
  - منطق عمود الحالة (`UserManagementTable.tsx:164-204`): customer → "-"؛ `reject|rejected` → "مرفوض / غير مفعل" + `rejection_reason`؛ `active` → "نشط"؛ `current_step >= 4` → "بانتظار الموافقة"؛ 1 → "1 (تأكيد البريد)"؛ 2 → "2 (اختيار الباقة)"؛ 3 → "3 (إكمال الملف)"؛ غير كده → قيمة `status` أو "معلق".
- **Endpoint مقترح**: `GET /api/v1/admin/users?page=1&per_page=20&search=&sort=plan|date`
  - الترتيب بالباقة يتعمل في SQL كله: `ORDER BY CASE lower(trim(selected_plan)) WHEN 'premium' THEN 30 WHEN 'professional' THEN 20 WHEN 'pro' THEN 20 WHEN 'basic' THEN 10 ELSE 0 END DESC, created_at DESC`.
  - Response: `{ "users": [ { "id","name","email","phone","user_type","selected_plan","status","rejection_reason","current_step","created_at","slug" } ], "total": 512 }`.

### طلبات التوثيق — `getPendingVerifications` → `VerificationsTable` (`/dashboard/admin/verifications`)

- **Function** (`admin.api.ts:141-198`):
  - `user_validation.select("*, users(email, phone)")` order `created_at desc`، و`eq("status", status)` لو `status !== "all"`. **بيجيب كل الصفوف** (مفيش range).
  - Search **client-side** (lowercase includes) على: `name`, `professional_id`, `tax_number`, `users.email`, و`users.phone` (includes من غير lowercase).
  - Pagination **client-side**: `slice(from, to+1)` بـ 20؛ `total = filteredData.length`.
  - Stats: query تانية `user_validation.select("status")` لكل الصفوف → `{ pending, active, reject }` (أي status تاني مش بيتعد).
- **فلاتر الـ UI**: tabs `all` "الكل (pending+active+reject)"، `pending` "معلق"، `active` "مقبول"، `reject` "غير مفعل"؛ بحث (placeholder "البحث بالاسم، الإيميل، رقم الهاتف...").
- **الأعمدة المستخدمة**: `id`, `user_id`, `name`, `account_type` (`engineer` → "مهندس"، غير كده → "مكتب هندسي"), `professional_id` ("رقم النقابة"), `tax_number` ("البطاقة الضريبية"), `details.graduation_year` ("سنة التخرج"), `documents[]` (URLs صور، thumbnail 48px + مودال تكبير), `created_at`, `status`, `rejection_reason` (tooltip لو `reject`), `users.email`, `users.phone`.
  - كروت الإحصائيات: الإجمالي = `pending + active + reject`، "الحسابات الموثقة" = active، "طلبات معلقة" = pending، "الطلبات غير المفعلة" = reject.
- **Endpoint مقترح**: `GET /api/v1/admin/verifications?page=1&per_page=20&status=all|pending|active|reject&search=`
  - Search server-side على `user_validation.name/professional_id/tax_number` + `users.email/phone` (ILIKE)، pagination server-side.
  - Response: `{ "verifications": [ { "id","user_id","name","account_type","professional_id","tax_number","details":{"graduation_year"},"documents":["url"],"created_at","status","rejection_reason","users":{"email","phone"} } ], "total": 37, "stats": { "pending": 7, "active": 25, "reject": 5 } }`.

### سجل الاشتراكات — `getAdminSubscriptions` → `SubscriptionsTable` (`/dashboard/admin/subscriptions`)

- **Function** (`admin.api.ts:694-778`):
  - `subscriptions.select("*, users(name, email, phone, user_type, slug)", { count: "exact" })` order `created_at desc`.
  - Status filter: `expired` → `eq(status,'expired')`؛ `active` → `eq(status,'active')`؛ `pending` → `eq(status,'pending')`؛ `all` → مفيش.
  - `range(from, to)` (server pagination) **وبعدها** Search **على صفحة الـ 20 الحالية بس** (lowercase includes على `users.name`, `users.email`, `users.phone`, `users.slug`, `plan_type`, `plan_id`؛ الصفوف من غير user بتتشال). `total = count` (مش متأثر بالبحث).
  - Stats: `subscriptions.select("*")` لكل الصفوف:
    - `isExpired = status === 'expired' || (end_date ? end_date < now : (expires_at ? expires_at < now : false))`
    - لو `status === 'pending'` → `pending++`؛ else لو `isExpired` → `expired++`؛ else → `active++`. (يعني الاشتراك `active` اللي تاريخه عدّى بيتعد expired في الكروت لكن بيظهر في فلتر `active`.)
    - `plans[plan_type || plan_id || "غير معروف"]++` (key case-sensitive كما هو).
- **الـ UI**: كروت: الإجمالي = `pending + active + expired`، النشطة، "بانتظار التأكيد (معلق)"، المنتهية؛ وتوزيع الباقات بيقرا `plans["basic"]||plans["Basic"]`، `plans["professional"]||plans["Professional"]`، `plans["premium"]||plans["Premium"]`. Tabs: الكل / نشط / معلق / منتهي. بحث (placeholder "بحث بالاسم، الإيميل، الباقة...").
- **الأعمدة المستخدمة**: `id`, `status`, `plan_type || plan_id`, `plan_price` (يتعرض "{price} ج.م" أو "-"), `confirmation` (URL صورة الإيصال؛ لو `"free_plan"` أو فاضي → "لا يوجد"), `start_date || starts_at` ("تاريخ التفعيل")، `end_date || expires_at` ("تاريخ الانتهاء")، `users.name`, `users.email`, `users.slug`.
- **Endpoint مقترح**: `GET /api/v1/admin/subscriptions?page=1&per_page=20&status=all|active|pending|expired&search=`
  - Search server-side (join users) قبل الـ pagination، و`total` بعد البحث.
  - Response: `{ "subscriptions": [ { "id","status","plan_type","plan_id","plan_price","confirmation","start_date","starts_at","end_date","expires_at","users":{"name","email","phone","user_type","slug"} } ], "total": 88, "stats": { "pending": 5, "active": 70, "expired": 13, "plans": { "basic": 30, "professional": 40, "premium": 18 } } }` (نفس معادلة الـ stats فوق).

### الإحصائيات المالية — `getAdminFinanceStats` → `FinanceStats` (`/dashboard/admin/finance`)

- **Function** (`admin.api.ts:827-987`):
  - `subscription_payments.select("subscription_id, user_id, amount, plan_type, billing_cycle, status, created_at")` — **كل الصفوف بغض النظر عن `status`**.
  - `subscriptions.select("plan_price, plan_type, plan_slug, status").eq("status","active")`.
  - مفيش أي كود في الفرونت بيكتب في `subscription_payments` (غالباً trigger في الـ DB أو مصدر خارجي) — لازم الباك إند يحدد مين بينشئ الصفوف دي.
- **المعادلات (لازم الـ API يحسبها)**، `now` = وقت الطلب:
  - `activeTotalRevenue = Σ parseFloat(subscriptions.plan_price)` للاشتراكات `status='active'` (NaN → 0)؛ `activeTotalCount = count` نفس الصفوف.
  - لكل payment: `price = parseFloat(amount) || 0`، `plan = lower(plan_type || "other")`.
    - `totalYearly = Σ price` للمدفوعات اللي `year(created_at) = سنة now`.
    - `totalMonthly = Σ price` للمدفوعات اللي سنة وشهر `created_at` = سنة وشهر now.
    - `chartData[month(created_at)]` (12 شهر "يناير".."ديسمبر"، index 0-11) **مجمّع عبر كل السنين** (يناير 2025 + يناير 2026 في نفس الخانة): `total += price`، `count += 1`.
    - `totalsPerPlan[plan] += price`؛ `planBreakdown[plan].income += price`.
  - New vs Renewed: ترتيب المدفوعات تصاعدي بـ `created_at`؛ أول payment لكل `user_id` = **new**، أي payment بعده لنفس الـ user = **renewal**:
    - `chartData[month].newCount` / `.renewedCount` (برضه عبر كل السنين).
    - `planBreakdown[plan].newCount|renewedCount += 1`، `planBreakdown[plan].count += 1`.
    - `newTotal`/`renewedTotal` (الكل)، `newYearly`/`renewedYearly` (سنة now)، `newMonthly`/`renewedMonthly` (شهر وسنة now).
  - `totalTransactions = count(payments)` (بيترجع لكن مش بيتعرض).
- **الـ UI**: 4 كروت (إجمالي هذا الشهر، إجمالي هذا العام، قيمة الاشتراكات النشطة، عدد الاشتراكات النشطة "عضوية")؛ كارتين جديد/تجديد (الإجمالي + "هذا الشهر" + "هذا العام")؛ Line chart (الإيرادات `total` + bars `newCount`/`renewedCount`)؛ قايمة "الإيرادات بحسب الباقات" من `totalsPerPlan` مترتبة تنازلي؛ Bar chart لـ `planBreakdown` (income/newCount/renewedCount) مع labels: premium "المميزة (Premium)"، professional "الاحترافية (Professional)"، basic "الأساسية (Basic)"، free "المجانية (Free)"، غير كده `UPPERCASE`. العملة ج.م.
- **Endpoint مقترح**: `GET /api/v1/admin/finance/stats`
  ```json
  { "totalMonthly": 4500, "totalYearly": 61000,
    "activeTotalRevenue": 23000, "activeTotalCount": 70,
    "totalsPerPlan": { "premium": 30000, "professional": 25000, "basic": 6000 },
    "chartData": [ { "month": "يناير", "total": 5000, "count": 6, "newCount": 4, "renewedCount": 2 }, "… 12 عنصر" ],
    "totalTransactions": 140,
    "subscriptionStats": { "newTotal": 90, "renewedTotal": 50, "newMonthly": 3, "renewedMonthly": 1, "newYearly": 40, "renewedYearly": 20 },
    "planBreakdown": { "premium": { "income": 30000, "count": 20, "newCount": 12, "renewedCount": 8 } } }
  ```
  **(مقترح)**: قرار business هل نعدّ المدفوعات كلها ولا `status` معين بس، وهل الـ chart يكون آخر 12 شهر بدل تجميع كل السنين.

### جدول المهندسين / المكاتب — `getAdminEngineersData` / `getAdminOfficesData` → `ProfessionalTable` (`/dashboard/admin/engineers`, `/dashboard/admin/offices`)

- **Function** (`admin.api.ts:360-462` و `464-565`)، `{table}` = `engineers` أو `offices`:
  - Main: `{table}.select("*, users!inner(email, phone, status, selected_plan, rejection_reason, current_step)", { count: "exact" })` order `created_at desc` (inner join ⇒ الصفوف اللي ملهاش `users` بتتشال).
  - Search (debounce 600ms، يرجع صفحة 1):
    1. `users.select("id").or(email.ilike.%q%, phone.ilike.%q%).limit(200)` → `userIds`.
    2. `{table}.or(name.ilike.%q%, slug.ilike.%q%, [major.ilike.%q% — للمهندسين بس], whats_app.ilike.%q%, user_id.in.(userIds))`.
  - `onlyPublic` (toggle "العرض العام فقط"): يجيب `slug` من `public_engineers_view`/`public_offices_view` ويفلتر `slug IN (...)` (لو فاضية يحط slug وهمي عشان النتيجة تبقى صفر).
  - `range(from,to)` → `users` (الصفحة) و`filteredTotal = count`.
  - Stats (على كل الجدول، مش متأثرة بالبحث/الفلتر): `{table}.select("governorate, users!inner(selected_plan)")`:
    - `topGovernorates`: `governorate` نص CSV → `split(",").trim()` وعدّ كل محافظة، ترتيب تنازلي، **أول 3** → `[{ name, value }]`.
    - `planStats`: `lower(trim(users.selected_plan))`: `premium` → premium، `professional|pro` → professional، `basic` → basic، أي حاجة تانية (بما فيها null) → free.
    - `total = عدد صفوف الـ stats query`.
  - `activeTotal = count(public_engineers_view | public_offices_view)`.
- **الـ UI**: `totalPages = ceil((filteredTotal ?? total) / 20)`. كروت: "الإجمالي" (`total`)، "حسابات مفعلة" (`activeTotal`)، "توزيع الباقات" (مجانية/أساسية/احترافية/مميزة)، "أكثر المحافظات تسجيلاً".
  - الأعمدة المستخدمة: `profile_image`, `name`, `status` (من الصف نفسه لو موجود وإلا `users.status`، default `pending`) و`current_step` (من الصف أو `users.current_step`) → "نشط" لو `active`؛ "مرفوض" لو `rejected|reject`؛ غير كده "بانتظار الموافقة" لو step ≥ 4 أو "غير نشط"، `slug`, `users.email`, `users.phone`, `whats_app` (لو مختلف عن الهاتف), `governorate`, `users.selected_plan`, `created_at`, `user_id` (لينك التفاصيل)، و`slug` (لينك `/directory/{engineers|offices}/{slug}`).
- **Endpoint مقترح**: `GET /api/v1/admin/engineers?page=1&per_page=20&search=&only_public=0|1` و `GET /api/v1/admin/offices?…` (نفس الباراميترز؛ البحث في `major` للمهندسين بس)
  - `only_public` = نفس شرط الـ public view (اللي الباك إند لازم يعرّفه كـ scope، شوف قسم الأدلة العامة).
  - Response:
    ```json
    { "users": [ { "id","user_id","name","slug","profile_image","governorate","whats_app","created_at","status",
                   "users": { "email","phone","status","selected_plan","rejection_reason","current_step" } } ],
      "total": 120, "filteredTotal": 14, "activeTotal": 80,
      "topGovernorates": [ { "name": "القاهرة", "value": 40 } ],
      "planStats": { "free": 50, "basic": 30, "professional": 25, "premium": 15 } }
    ```

### تفاصيل مهندس / مكتب — `getAdminUserDetails` → `ProfessionalDetails` (`/dashboard/admin/engineers/[id]`, `/dashboard/admin/offices/[id]`)

- **Function** (`admin.api.ts:567-692`)، `id` = `users.id`، `{table}` = `engineers|offices`. Parallel:
  1. `users.select("*").eq("id", id).single()` (error → throw).
  2. `{table}.select("*").eq("user_id", id).maybeSingle()`.
  3. `services.select("*").eq("user_id", id)`.
  4. `projects.select("*").eq("user_id", id).order("created_at", desc)`.
  5. `user_permissions.select("*").eq("user_id", id).maybeSingle()`.
  - بعدها (`slug = profile.slug`):
    - RPC `get_limited_stats({ p_slug: slug })` (لو فيه slug) → أول عنصر `lStats` (shape: `{ visit, whatsapp, call, email, map, facebook, instagram, youtube }`) → `current_month_visits = lStats.visit || 0`، `monthly_stats = lStats`.
    - `stats.select("*, users(name, email, phone)")` where `slug = :slug OR user_id = :id` (أو `user_id` بس لو مفيش slug) order `created_at desc`.
    - `favorites.select("*, users(name, email, phone)")` بنفس الشرط.
    - ملاحظة: شرط `OR user_id = :id` في `stats`/`favorites` غالباً بيجيب صفوف المستخدم ده كـ "فاعل" مش كـ "هدف" (الـ join على `users` بيوحي إن `user_id` هو الزائر) — لازم يتراجع مع الـ schema.
  - **Aggregation (client-side حالياً)**: لكل صف `stats`: `c = (typeof count === "number" && count > 0) ? count : 1`؛ `metricCounts[metric_type] += c`؛ و:
    - `visitsCount` ← `visit`؛ `whatsappCount` ← `whatsapp`؛ `phoneCount` ← `call` أو `phone`؛ `facebookCount`، `instagramCount`، `youtubeCount`، `emailCount` ← `email`؛ `mapCount` ← `map`.
    - `totalSocialClicks = facebook + instagram + youtube`.
    - `totalClicks = whatsapp + phone + facebook + instagram + youtube + email + map`.
    - `favoritesCount = عدد صفوف favorites`.
  - Return: `{ ...usersRow, [table]: [profile] | [], services, projects, permissions | null, current_month_visits, monthly_stats, analytics: { visitsCount, whatsappCount, phoneCount, facebookCount, instagramCount, youtubeCount, emailCount, mapCount, totalSocialClicks, totalClicks, favoritesCount, metricCounts, allStats, allFavorites } }`.
- **الحقول المستخدمة في الـ UI فعلاً**:
  - `users`: `name`, `phone`, `email`, `status`, `rejection_reason`, `selected_plan` (يتعرض كما هو، default "أساسية")، `created_at`.
  - profile: `slug`, `major`, `experience_years`, `governorate`, `bio`, `whats_app`, `skills[]`, `count_of_projects`, `facebook`, `instagram`, `youtube`, `video`, `profile_image`.
  - `services[]`: `id`, `name`, `icon`, `color`.
  - `projects[]`: `id`, `image`, `name`, `location`, `type`, `area` ("{area}م²").
  - `permissions.max_visits` + `current_month_visits` → "زيارات هذا الشهر {current} / {limit}"؛ `limit = -1` أو `>= 99999` → "∞" و progress 100%؛ غير كده `min(current/limit*100, 100)`.
  - `analytics`: `visitsCount`, `whatsappCount`, `phoneCount`, `mapCount`, `favoritesCount`, `facebookCount`, `instagramCount`, `youtubeCount`, `totalSocialClicks`, `totalClicks`. (`emailCount`, `metricCounts`, `allStats`, `allFavorites`, `monthly_stats` بيترجعوا ومش بيتعرضوا.)
- **Endpoint مقترح**: `GET /api/v1/admin/engineers/{user}` و `GET /api/v1/admin/offices/{user}`
  ```json
  { "user": { "id","name","email","phone","status","rejection_reason","selected_plan","created_at" },
    "profile": { "slug","major","skills","count_of_projects","experience_years","governorate","bio","whats_app",
                 "facebook","instagram","youtube","video","profile_image" },
    "services": [ { "id","name","icon","color" } ],
    "projects": [ { "id","name","image","location","type","area" } ],
    "permissions": { "max_visits": 500 },
    "current_month_visits": 120,
    "analytics": { "visitsCount","whatsappCount","phoneCount","facebookCount","instagramCount","youtubeCount",
                   "emailCount","mapCount","totalSocialClicks","totalClicks","favoritesCount" } }
  ```
  (لو الفرونت هيفضل يقرا `user.engineers[0]`/`user.offices[0]` ممكن الـ mapper في `admin.api.ts` يحوّل الشكل.) الـ analytics تتحسب في SQL بـ `SUM(CASE metric_type …)`، و`current_month_visits` نفس منطق `get_limited_stats` (شوف قسم إحصائيات الداشبورد).

### أسعار المواد — `getAdminMaterialPrices` → `PriceEditor` (`/dashboard/admin/prices`)

- **Function** (`admin.api.ts:332-339`): `materials_prices.select("*").order("category")` (asc)، من غير pagination.
- **الـ UI**: أعمدة "التصنيف" `category`، "اسم الصنف" `item_name`، "الوحدة" `unit`، "السعر (ج.م)" `price`، "التغير (%)" `change_percent` (أخضر وعلامة `+` لو > 0، أحمر لو < 0)، + `id` للتعديل والحذف.
- **Endpoint مقترح**: `GET /api/v1/admin/material-prices` → `[ { "id", "category", "item_name", "unit", "price", "change_percent" } ]` مرتبة بـ `category`.

### طلبات العملاء + الإحصائيات — `getAdminCustomerRequests` → `AdminCustomerRequestsTable` (`/dashboard/admin/customers`)

- **Params**: `page`, `limit` (الـ UI بيبعت 20), `searchQuery` (debounce 400ms), `statusFilter`, `serviceFilter`, `governorateFilter`.
- **(1) Stats query** (`admin.api.ts:1078-1087`): `customers.select("id, user_id, phone, email, full_name, assigned_provider_id, assigned_provider_name, customer_response, status, notes, service, whats_count, calls_count, customer_type").neq("customer_type","guest")` — كل الصفوف (ملحوظة: `neq` في SQL بيستبعد كمان الصفوف اللي `customer_type IS NULL`).
  - `stats` (على كل الصفوف دي، **من غير** استثناء `status='deleted'`، ومش متأثرة بالفلاتر) (`admin.api.ts:1215-1223`):
    - `total = count`
    - `unassigned = count(assigned_provider_name IS NULL/empty)`
    - `assigned = count(assigned_provider_name موجود AND customer_response != 'refused')`
    - `refused = count(customer_response = 'refused')`
    - `pendingDecision = 0`, `approved = 0` (ثابتين)
    - `completed = count(status = 'completed')`
  - `serviceStats` (`1226-1247`) — بتستثني `status='deleted'`: group by `trim(service)` (null → `"خدمات عامة"`): `totalRequests = count`, `whatsCount = Σ whats_count`, `callsCount = Σ calls_count`, `totalClicks = whatsCount + callsCount` (قيم NaN → 0)؛ ترتيب `totalClicks desc` ثم `totalRequests desc`.
  - **Office history map** (`1091-1135`) — بتستثني `deleted`: لكل صف فيه `assigned_provider_id` أو `assigned_provider_name` (normalized lower/trim، والاتنين بيشيروا لنفس الـ entry): `assigned++`، و`approved++` لو `customer_response='approved'`، و`refused++` لو `'refused'`. كمان لكل match لـ regex `/\[رفض سابق:\s*([^-\]\n]+)/g` في `notes`: الاسم المستخرج (normalized) → `assigned++` و`refused++`.
  - **Customer history map** (`1153-1185`) — بتستثني `deleted`: keys = `uid:{user_id}`, `phone:{digits}`, `email:{lower}`, `name:{lower}`؛ لكل key: `total++`، `approved++` لو approved، `refused++` لو refused، و`refused += عدد "[رفض سابق:" في notes`. الـ lookup بياخد أول key موجود بالترتيب ده.
  - `request_refused_count` = `(customer_response='refused' ? 1 : 0) + عدد "[رفض سابق:" في notes`.
- **(2) Main query** (`1250-1294`): `customers.select("*", { count: "exact" }).neq("customer_type","guest").order("created_at", desc)` +:
  - `statusFilter`:
    - `unassigned` → `assigned_provider_name IS NULL`
    - `assigned` → `assigned_provider_name IS NOT NULL AND (customer_response IS NULL OR customer_response != 'refused')`
    - `refused` → `customer_response = 'refused'`
    - `pending_decision` / `approved` (legacy) → نفس `assigned`
    - `completed` → `status = 'completed'`
    - `cancelled` → `status = 'cancelled'` (موجود في الـ API بس مفيش tab ليه في الـ UI)
    - `all` → مفيش
  - `serviceFilter != "all"` → `service = :service`
  - `governorateFilter != "all"` → `governorate = :gov`
  - Search → `or(full_name, phone, email, service, governorate, assigned_provider_name, notes ILIKE %q%)`
  - `range(from, to)` → `total = count`.
  - ملاحظة: صفوف `status='deleted'` (soft delete من جانب العميل) **بتظهر** في الجدول.
- **(3) Enrichment** (`1303-1462`) لو في الصفحة أي طلب متعيّن: queries إضافية: `public_offices_view.select("name, office_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects, count_of_projects")`، `public_engineers_view.select("name, user_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects")`، `rating.select("slug, rating, user_id, message")`، `users.select("id, slug, email, name, selected_plan").limit(1000)`.
  - Matching المرشح: normalize (`\s+` → مسافة، trim، lower). مكتب الأول: `slug == assigned_provider_id` أو الاسم (`name`/`office_name`/`slug`) يساوي `assigned_provider_name`، أو (لو الاسم > 3 حروف) contains في أي اتجاه. لو مفيش مكتب → نفس المنطق على المهندسين (`name`/`user_name`/`slug`).
  - الحقول المضافة لكل طلب:
    - `office_approved_count`, `office_refused_count`, `office_assigned_count` (من office map بالـ keys: `assigned_provider_id`, `assigned_provider_name`, `office_name`, `office_slug`)
    - `customer_approved_count`, `customer_refused_count`, `customer_total_requests` (customer map)
    - `request_refused_count`, `request_approved_count` (= 1 لو approved)
    - `office_name` = `assigned_provider_name || provider.name || provOffice.office_name`
    - `experience_years` = `Number(provider.experience_years)`
    - `office_rating` = `average_rating` لو > 0، غير كده متوسط `rating.rating` للـ slug مقرّب لرقم عشري واحد
    - `office_slug`, `office_phone`, `office_whatsapp` (`whats_app || phone`), `office_profile_image`
    - `office_projects_count` = `count_of_projects` (مكتب) أو `projects.length`
    - `provider_type` = `"offices"` أو `"engineers"`
    - `office_plan` = `provider_plan` = `selected_plan` من `users` بـ lookup على slug ← assigned_provider_id ← اسم المرشح ← `provider.name`
    - `user_rated_stars`, `user_rated_message` = تقييم العميل (`rating` where `slug` و`user_id = request.user_id`)
  - لو مفيش طلبات متعيّنة في الصفحة أو الـ enrichment فشل → fallback بالـ counts بس (`1469-1491`).
- **الـ UI** (`AdminCustomerRequestsTable.tsx`):
  - كروت/tabs: "إجمالي الطلبات"/"الكل" (`stats.total`)، "بانتظار الترشيح" (`unassigned`)، "تم التعيين" (`assigned`)، "يحتاج بديل" (`refused`، بيعمل pulse لو > 0)، "المكتملة"/"مكتمل" (`completed`).
  - لوحة "إحصائيات نقرات التواصل حسب الخدمة": عدد الخدمات، مجاميع `whatsCount`/`callsCount`/`totalClicks` (محسوبة في الـ UI بـ reduce على `serviceStats`)، والضغط على كارت خدمة = `serviceFilter`.
  - فلتر الخدمة: `all` "كل الخدمات الهندسية" + أسماء `ENGINEERING_SERVICES_15` (`features/customer/constants.ts:11-109`): تصميم معماري، حصر كميات، رسومات تنفيذية، إدارة مشاريع، تصميم داخلي، تشطيبات، استشارات هندسية، تراخيص بناء، رفع مساحة، تصالح للبناء، تصميم إنشائي، تصميم واجهات، أعمال عزل، أعمال خرسانات، أعمال ترميم، خدمات نقابية (القيمة المبعوتة = الاسم العربي).
  - فلتر المحافظة: `all` "كل المحافظات" + الـ 27 محافظة (`egyptGovernorates`).
  - الحقول المستخدمة لكل طلب: `id`, `full_name`, `phone`, `email`, `governorate`, `service`, `created_at`, `notes`, `status`, `customer_type`, `assigned_provider_name`, `assigned_at`, `customer_response` (approved → "تمت الموافقة"، غير كده "طلب بديل")، `customer_reason`, `whats_count`, `calls_count`, `experience_years`, `office_slug`, `office_plan`/`provider_plan`, `office_approved_count`, `office_refused_count`, `office_assigned_count`, `customer_total_requests`, `customer_approved_count`, `customer_refused_count`, `request_refused_count` ("تغيير المكتب n مرات" لو > 1).
  - "حالة التعيين" (مشتقة): `customer_response='refused'` → "يحتاج بديل" (يفتح مودال السبب)؛ فيه `assigned_provider_name` → "تم التعيين"؛ غير كده "بانتظار الترشيح".
- **Endpoint مقترح**: `GET /api/v1/admin/customer-requests?page=1&per_page=20&search=&status=all|unassigned|assigned|refused|completed|cancelled&service=&governorate=`
  ```json
  { "requests": [ { "id","user_id","full_name","phone","email","customer_type","governorate","service","notes",
                    "status","created_at","updated_at","assigned_provider_id","assigned_provider_name","assigned_at",
                    "customer_response","customer_reason","whats_count","calls_count",
                    "office_name","office_slug","office_plan","provider_plan","provider_type","experience_years",
                    "office_rating","office_phone","office_whatsapp","office_profile_image","office_projects_count",
                    "office_approved_count","office_refused_count","office_assigned_count",
                    "customer_approved_count","customer_refused_count","customer_total_requests",
                    "request_refused_count","request_approved_count","user_rated_stars","user_rated_message" } ],
    "total": 230,
    "stats": { "total": 230, "unassigned": 40, "assigned": 150, "refused": 12, "pendingDecision": 0, "approved": 0, "completed": 28 },
    "serviceStats": [ { "service": "تصميم معماري", "totalRequests": 50, "whatsCount": 80, "callsCount": 30, "totalClicks": 110 } ] }
  ```
  **(مقترح)**: الربط بالمرشح بـ `assigned_provider_id` (FK على `users.id`) بدل الـ fuzzy matching بالاسم، وتاريخ الترشيحات من جدول مستقل بدل parsing الـ notes.

### قايمة المكاتب/المهندسين القابلة للتعيين — `getAssignableProviders` → مودال الترشيح في `AdminCustomerRequestsTable`

- **Function** (`admin.api.ts:1501-1835`)، Parallel:
  - `public_offices_view.select("office_name, name, governorate, experience_years, slug, profile_image, phone, email, average_rating, services").order("name")`
  - `public_engineers_view.select("name, user_name, governorate, experience_years, slug, profile_image, phone, email, average_rating, services").order("name")`
  - `customers.select("assigned_provider_id, assigned_provider_name, customer_response, status, notes")` (كل الصفوف، **بما فيها guest**)
  - `users.select("id, slug, email, name, selected_plan").limit(1000)`
  - `offices.select("user_id, slug, name").limit(1000)` و `engineers.select("user_id, slug, name").limit(1000)`
- **المنطق**:
  - statsMap (نفس منطق office history فوق): استثناء `deleted`، keys = id/name normalized، `assigned/approved/refused` + parsing `[رفض سابق: …]`.
  - `uuidMap` (slug/email/name → UUID) من `users` ثم `offices` ثم `engineers`؛ `planLookup` (id/slug/email/name → `selected_plan`).
  - أي مرشح في الـ public views عنده email ومش لاقي UUID → RPC `check_user_exists({ p_email })` لكل واحد (N calls) → `data.user_id`.
  - لكل مكتب: `name = trim(office_name || name)`؛ لكل مهندس: `name = trim(name || user_name)`؛ `id = resolvedUuid || slug || name`؛ `services` = array من strings أو `{name}` → strings؛ stats بالـ lookup على (uuid, slug, name)؛ `plan` بالـ lookup على (uuid, slug, email, name).
  - Fallback لو القايمة فاضية: `offices`/`engineers` مباشرة (`user_id, name, governorate, experience_years, slug, profile_image`) بـ `services: []`.
  - ترتيب: premium (4) > professional/pro (3) > basic (2) > غير كده (1). أي exception → `[]`.
- **Shape (`IAssignableProvider`)**: `{ id, name, type: "office"|"engineer", governorate, experience_years, slug, image, phone, rating, services: string[], assigned_count, approved_count, refused_count, plan }` — الـ UI بيستخدم كل الحقول دي ما عدا `phone`.
- **Endpoint مقترح**: `GET /api/v1/admin/customer-requests/assignable-providers?type=all|office|engineer&plan=all|premium|professional|basic|free&service=&search=`
  - الفلاتر دي client-side حالياً — ممكن تفضل في الفرونت (staleTime 5 دقايق) أو تتنقل للسيرفر.
  - Response: `[ { "id": "uuid", "name": "...", "type": "office", "governorate": "القاهرة", "experience_years": 10, "slug": "...", "image": "url", "phone": "...", "rating": 4.6, "services": ["تصميم معماري"], "assigned_count": 12, "approved_count": 8, "refused_count": 3, "plan": "premium" } ]`
  - **(مقترح)**: `id` يكون دايماً `users.id` (UUID)، والمصدر الأساسي `users` + `offices`/`engineers` بشرط "public" بدل الـ resolve بالإيميل.

---

### Status / enum values اللي ظاهرة في كود الأدمن

| الحقل | القيم | المعنى | الانتقالات اللي الأدمن بيعملها |
|---|---|---|---|
| `users.user_type` | `engineer`, `office`, `exhibition`, `material`, `customer`, `admin` (`features/dashboard/types.ts:1`). **في Laravel:** من غير `material` (0.5) | نوع الحساب | — (read only) |
| `users.status` | `active`, `pending`, `reject` (+ `rejected` بيتقرا كمرادف في الإحصائيات والعرض) | حالة اعتماد الحساب المهني | توثيق: → `active` / → `reject`؛ تفعيل اشتراك: → `active`؛ تفاصيل المهني: أي من `active`/`pending`/`reject` |
| `users.current_step` | `0`, `1`, `2`, `3`, `>=4` | 1 = بانتظار التوثيق (في `UserManagementTable` "تأكيد البريد")، 2 = اختيار الباقة، 3 = إكمال الملف، 4+ = مكتمل (نشط لو `status=active`، غير كده "بانتظار الموافقة") | الأدمن **مبيغيرهوش** خالص |
| `users.selected_plan` | `premium`, `professional` (و`pro` كمرادف)، `basic`، null/أي حاجة تانية = `free` | الباقة | الاشتراك اليدوي بيكتب `basic`، `professional`، `premium` |
| `users.rejection_reason` | نص | سبب الرفض | تفاصيل المهني: بيتكتب لو `reject`، `null` غير كده |
| `user_validation.status` | `pending`, `active`, `reject` | حالة طلب التوثيق | `pending` → `active` / `reject` (و`reject` → `active` ممكن)؛ تفعيل الاشتراك بيحطها `active`. مفيش رجوع لـ `pending` من الـ UI |
| `user_validation.account_type` | `engineer` / غير ذلك (مكتب) | — | — |
| `subscriptions.status` | `active`, `pending`, `expired` | حالة الاشتراك | أي انتقال حر من الـ select؛ `→ active` بيفعّل `users.status` و`user_validation.status` |
| `subscriptions.plan_type` / `plan_id` | `basic`, `professional`, `premium` (والـ stats بتقرا كمان `Basic`/`Professional`/`Premium`) | الباقة | الاشتراك اليدوي بيكتب `plan_id` |
| `subscriptions.confirmation` | URL صورة الإيصال، أو `"free_plan"` | إثبات الدفع | — |
| `subscription_payments.status` | (بيتقرا ومش بيتفلتر بيه) | — | — |
| `subscription_payments.plan_type` | أي نص؛ بيتعمل lowercase؛ null → `"other"` | — | — |
| `customers.customer_type` | `guest`, `need_service` | الأدمن بيستبعد `guest` | — |
| `customers.status` | من الـ UI: `pending` (قيد المراجعة)، `assigned` (تم التعيين)، `approved` (معتمد)، `completed` (مكتمل ومسلّم)، `cancelled` (ملغي)؛ وفي النوع `CustomerStatus` كمان `refused`, `in_progress`, `active`؛ و`deleted` (soft delete من جانب العميل) | حالة الطلب العامة | الأدمن: أي قيمة من الخمسة يدوياً؛ التعيين → `pending` دايماً. (جانب العميل: approved → `approved`، refused → `pending`) |
| `customers.customer_response` | `null`, `approved`, `refused` | قرار العميل على المرشح | التعيين بيصفّره `null` (ومعاه `customer_reason`) |
| حالة التعيين (مشتقة، مش عمود) | `unassigned` / `assigned` / `refused` | من `assigned_provider_name` + `customer_response` | — |
| `materials_prices` | — | — | — |
| Homepage display tiers | `premium`, `professional`, `basic`, `free` × `offices`/`engineers` | booleans | toggle |
| `stats.metric_type` | `visit`, `whatsapp`, `call`, `phone`, `facebook`, `instagram`, `youtube`, `email`, `map` | نوع التفاعل | — |
| `analytics_counts.stat_type` | `page` (اللي بيتجمع للزيارات) | — | — |
| `interaction_logs.activity_type` | `update` (اللي الأدمن بيكتبه) | — | insert فقط |

### الجداول اللي نطاق الأدمن بيلمسها

| Table / View / RPC | Read columns | Written columns (العملية) |
|---|---|---|
| `users` | `*` (الجدول/التفاصيل)، `id, slug, email, name, selected_plan` (lookups)، counts بـ `user_type`, `current_step`, `status`؛ embedded: `email, phone, status, selected_plan, rejection_reason, current_step, name, user_type, slug` | `status` (توثيق، تفعيل اشتراك، تفاصيل المهني)، `name, phone, rejection_reason` (تفاصيل المهني)، `selected_plan` (اشتراك يدوي) — update |
| `user_validation` | `*` + `users(email, phone)`؛ `status` (stats) | `status`, `rejection_reason` (update by `id`)؛ `status` (update by `user_id` عند تفعيل اشتراك) |
| `interaction_logs` | — | insert `user_id, activity_type, activity_text` |
| `subscriptions` | `*` + `users(name, email, phone, user_type, slug)`؛ `plan_price, plan_type, plan_slug, status` (مالية)؛ أعمدة مستخدمة في العرض: `id, status, plan_type, plan_id, plan_price, confirmation, start_date, starts_at, end_date, expires_at, created_at, user_id` | `status` (update by id)؛ upsert `user_id, plan_id, status, starts_at, expires_at` (يدوي) |
| `subscription_payments` | `subscription_id, user_id, amount, plan_type, billing_cycle, status, created_at` | — |
| `materials_prices` | `*` (`id, category, item_name, unit, price, change_percent`) | upsert نفس الأعمدة، delete by `id` |
| `engineers` | `*` + `users!inner(...)`؛ `governorate`؛ `user_id, slug, name`؛ fallback: `user_id, name, governorate, experience_years, slug, profile_image` | upsert on `user_id`: `name, governorate, bio, whats_app, experience_years, profile_image, facebook, instagram, youtube, video, major, skills, user_id, updated_at` |
| `offices` | نفس المهندسين (من غير `major` في البحث) | upsert on `user_id`: نفس الأعمدة بس `count_of_projects` بدل `major, skills` |
| `services` | `*` (المستخدم: `id, name, icon, color`) by `user_id` | — |
| `projects` | `*` (المستخدم: `id, name, image, location, type, area`) by `user_id` order `created_at` | — |
| `user_permissions` | `*` (المستخدم: `max_visits`) by `user_id` | — |
| `stats` | count `metric_type='visit'`؛ `* + users(name, email, phone)` by `slug`/`user_id` (أعمدة مستخدمة: `metric_type, count, created_at`) | — |
| `analytics_counts` | `total_count` where `stat_type='page'` | — |
| `favorites` | `* + users(name, email, phone)` by `slug`/`user_id` (عدد بس) | — |
| `rating` | `slug, rating, user_id, message` | — |
| `customers` | `*`؛ stats: `id, user_id, phone, email, full_name, assigned_provider_id, assigned_provider_name, customer_response, status, notes, service, whats_count, calls_count, customer_type`؛ `assigned_provider_name, customer_response, customer_reason, notes` (قبل التعيين)؛ `whats_count, calls_count` | update `assigned_provider_id, assigned_provider_name, assigned_at, customer_response, customer_reason, status, updated_at, notes` (تعيين)؛ update `status, updated_at, notes` (حالة)؛ update `whats_count`/`calls_count`, `updated_at` (نقرات)؛ delete by `id` |
| `public_engineers_view` | count؛ `slug`؛ `name, user_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects`؛ `name, user_name, governorate, experience_years, slug, profile_image, phone, email, average_rating, services`؛ `email` | — |
| `public_offices_view` | count؛ `slug`؛ `name, office_name, slug, experience_years, average_rating, phone, whats_app, profile_image, projects, count_of_projects`؛ `office_name, name, governorate, experience_years, slug, profile_image, phone, email, average_rating, services`؛ `email` | — |
| `public_exhibitions_view`, `public_materials_view` | count فقط (`public_materials_view` ⛔ بيتشال في Laravel) | — |
| RPC `check_user_exists(p_email)` | يرجّع object فيه `user_id` (و`exists`, `message` في الاستخدامات التانية) | — |
| RPC `get_limited_stats(p_slug)` | array أول عنصر `{ visit, whatsapp, call, email, map, facebook, instagram, youtube }` (الشهر الحالي) | — |
| `auth` (Supabase) | — | `auth.resend({ type: "signup" })` فقط. **مفيش أي تعديل على `user_metadata` من الأدمن** |
| ملف `data/homepage-display-settings.json` | `offices.*`, `engineers.*` | overwrite كامل |
| Cloudinary | — | upload صورة بروفايل في folder `profiles` (unsigned preset `t3meer_images`) |

### Security notes (لازم تتحول server-side)

1. **صلاحية الأدمن مبنية على cookie بيكتبها المتصفح**: `layout.tsx` بيصدّق `user-storage` (Zustand persist) و`user_metadata.account_type|type|user_type === "admin"` من غير أي verification. أي حد يقدر يكتب cookie بنفس الشكل يفتح الصفحات. → **Laravel**: middleware `auth:sanctum` + `EnsureUserIsAdmin` (يشيك `users.user_type = 'admin'` من الـ DB) على كل `/api/v1/admin/*`، والفرونت يجيب الـ role من `GET /api/v1/auth/me`.
2. **كل عمليات الأدمن بتتنفذ من المتصفح بالـ anon key**: يعني أي مستخدم (حتى مش أدمن) يقدر يستدعي نفس الـ queries من الـ console (`users.update({status:"active"})`, `subscriptions.update`, `customers.delete`, `materials_prices.upsert`, …) إلا لو RLS مانعها — وRLS مش موثقة في الريبو. → كل write لازم يبقى endpoint محمي، والـ policies (Laravel Policies/Gates) تمنع غير الأدمن.
3. **`/api/admin/homepage-display` مفتوح بالكامل** (GET و POST) ومش في الـ middleware matcher؛ أي حد يقدر يغير إعدادات الصفحة الرئيسية. → endpoint أدمن محمي.
4. **الـ client بيبعت IDs بيعتمد عليها السيرفر**: `updateVerificationStatus` بياخد `userId` من الكلاينت (ممكن يتبعت `userId` مختلف عن صاحب الطلب)؛ `assignProviderToCustomerRequest` بياخد `providerName` حر؛ `adminResendUserConfirmation` بياخد `email` و`accountType` حرين (يقدر يبعت إيميل لأي عنوان). → السيرفر يجيب الـ IDs/الإيميل/الاسم من الـ DB بناءً على الـ route model.
5. **مفيش validation للمدخلات** في الـ API layer (status strings، أرقام، URLs، نص حر) → Form Requests بالـ rules المقترحة فوق.
6. **`upsertMaterialPrice` بيبعت الصف كله** (أي أعمدة) → whitelist (`$fillable`/`validated()`).
7. **Writes متعددة من غير transaction** (توثيق: 3 writes؛ تفعيل اشتراك: 3 writes؛ تعديل مهني: 2 writes؛ نقرات: read-modify-write) → `DB::transaction()` و`increment()`.
8. **الـ middleware بيفك الـ JWT من غير ما يتحقق من التوقيع** (`middleware.ts:63-72`) — ده عام مش أدمن بس، بس بيأثر على حماية `/dashboard/admin`.
9. **Cloudinary unsigned upload** من المتصفح (preset `t3meer_images`) لصور البروفايل — **(مقترح)** signed uploads عن طريق endpoint في Laravel أو رفع عن طريق السيرفر.
10. **Audit**: الأدمن مبيسجلش مين عمل إيه (غير `interaction_logs` في التوثيق بس، ومن غير `actor_id`) — **(مقترح)** audit log لكل admin action.

### سلوكيات في الكود الحالي محتاجة قرار من الباك إند (Quirks / Bugs)

- اختيار "معلق" في select التوثيق بيفتح prompt الرفض وبيحفظ `reject` (`VerificationsTable.tsx:54-88`).
- قبول التوثيق مش بيمسح `user_validation.rejection_reason` القديم.
- تفعيل الاشتراك من الأدمن مش بيحدد `starts_at`/`expires_at` ولا بينشئ `subscription_payments` ولا بيغيّر `current_step` — والصفحة المالية بتعتمد على `subscription_payments`.
- `manuallyOverrideSubscription` بيعمل upsert من غير `onConflict` وبيكتب `plan_id` (الباقي بيكتب `plan_type`)، والكومبوننت متعمله comment.
- البحث في الاشتراكات بيتعمل على الصفحة الحالية بس، و`total` مش بيتأثر بالبحث.
- ترتيب المستخدمين "حسب الباقة" صحيح جوه الصفحة بس.
- إحصائية `active` في الاشتراكات بتعتمد على التواريخ، أما فلتر `active` فبيعتمد على `status` بس.
- الـ chart المالي بيجمع نفس الشهر من كل السنين.
- "إجمالي زيارات المنصة" = الرقم الحقيقي + 1000.
- تعيين مكتب بيستبدل `customers.notes` بمحتوى الـ textarea، وتاريخ الرفض متخزن كنص `[رفض سابق: الاسم - السبب: …]` جوه `notes` وبيتعمل له parse بالـ regex.
- `assigned_provider_id` بيبقى `null` لو الـ UUID متحلش، والربط بالمرشح بعد كده بالاسم (fuzzy).
- `getAssignableProviders` بيعمل RPC call لكل مرشح ناقص UUID (N+1)، والـ lookups محدودة بـ `limit(1000)`.
- `customers.neq("customer_type","guest")` بيستبعد كمان `customer_type IS NULL`، وصفوف `status='deleted'` بتظهر في جدول الأدمن.
- حفظ إعدادات الصفحة الرئيسية بيرجّع success حتى لو الكتابة على الملف فشلت، والتخزين على filesystem مش هيصمد على serverless/multi-instance.

---

## 5. الموقع العام (Public Site) — كل القراءات والنماذج

> ✅ **قرار:** في Laravel فيه دليل واحد للمعارض والموردين (`showrooms-suppliers` ← `GET /api/v1/exhibitions`)، والروابط `/directory/exhibitions` و`/directory/materials` بتبقى redirect له. مفيش `material` كنوع مستخدم ولا `public_materials_view` (0.5). أي ذكر لـ `material` في القسم ده بيوصف الكود الحالي.

> النطاق: الصفحات اللي بيشوفها الزائر (من غير `dashboard` ومن غير صفحات `(auth)`): الرئيسية، الأدلة (`/directory/*`)، صفحات التفاصيل، صفحة المشروع، المقالات، اتصل بنا، الصفحات الثابتة، والـ Layout العام (Navbar/Footer/Tracking).
> كل الكود الحالي بيكلم Supabase مباشرة بالـ anon key (`lib/supabase.ts`) — سواء من المتصفح أو من Server Components (`*.server.ts` + `unstable_cache`).
> الأسماء (أعمدة/قيم/مفاتيح) مكتوبة زي ما هي في الكود بالظبط. أي حاجة مكتوب جنبها **(استنتاج)** معناها إنها مش ظاهرة في الكود ومستنتجة من رسالة UI أو من ملفات التحليل.

---

### 5.1 جدول الصفحات العامة → المكونات → البيانات → الـ Endpoints المقترحة

| Route | Component(s) / Rendering | البيانات المطلوبة | Endpoint(s) مقترح |
|---|---|---|---|
| `/` | `app/page.tsx` (Server Component, ISR `revalidate = 300`) → `HeroSlider`, `OfficesSlider`, `ProjectsSection`, `EngineersSection`, `FreeConsultationSection`, `AnalysisSection`, `PricesTable`, `ArticleSection`, `TestimonialsSection`, `TrackPageVisit` | بانرات تسويقية (premium offices)، مكاتب مميزة، مهندسين مميزين، مشاريع مميزة (15)، إحصائيات عامة، أسعار المواد، تقييمات المنصة (`slug = 't3meer'`)، حالة "قيّمت قبل كده؟"، مقالات (static) | `GET /api/v1/home` (aggregate) **أو** منفصلين: `GET /api/v1/banners?placement=home`، `GET /api/v1/offices?featured=1`، `GET /api/v1/engineers?featured=1`، `GET /api/v1/projects/featured`، `GET /api/v1/stats/public`، `GET /api/v1/material-prices`، `GET /api/v1/reviews?target_slug=t3meer`، `GET /api/v1/reviews/status?target_slug=t3meer`؛ Mutations: `POST /api/v1/reviews`، `POST /api/v1/track/analytics`، `POST /api/v1/track/metric` (ضغطة بانر) |
| `/directory/[type]` — `type ∈ engineers, offices, showrooms-suppliers, exhibitions, materials` | `app/directory/[type]/page.tsx` (ISR `revalidate = 120`) → `HeroSlider` + `DirectoryListing` (`Filter`, كروت، `FavoriteSpan`, `RatingSpan`, `ViewsSpan`, `SpecialSpan`) + `TrackPageVisit` | بانرات الدليل حسب النوع، القائمة (بحث/فئة/محافظة/ترتيب/صفحات)، حالة المفضلة لكل كارت | `GET /api/v1/banners?placement=directory&type={type}`، `GET /api/v1/engineers` / `GET /api/v1/offices` / `GET /api/v1/exhibitions` (الـ 3 أنواع الأخيرة alias لنفس المصدر)، `GET /api/v1/favorites/status?slugs[]=`؛ Mutations: `POST /api/v1/favorites/toggle`، `POST /api/v1/track/analytics` |
| `/directory/engineers/[slug]` | `app/directory/[type]/[id]/page.tsx` (**Client Component**) → `EngineerDetailsView` + `AddRatingModal` + `ReviewRating` | ملف المهندس (+ services + projects + permissions embedded)، التقييمات، `has_rated`، `is_favorited` | `GET /api/v1/engineers/{slug}`، `GET /api/v1/reviews?target_slug={slug}`؛ Mutations: `POST /api/v1/reviews`، `POST /api/v1/favorites/toggle`، `POST /api/v1/track/metric` |
| `/directory/offices/[slug]` | نفس الصفحة → `OfficeDetailsView` + `MainInfoCard` + `HeroVideo` + `AddRatingModal` | ملف المكتب (+ services + projects + permissions)، التقييمات، `has_rated`، `is_favorited` | `GET /api/v1/offices/{slug}`، `GET /api/v1/reviews?target_slug=`؛ نفس الـ Mutations |
| `/directory/{showrooms-suppliers\|exhibitions\|materials}/[id]` (الـ `id` ممكن يبدأ بـ `ex-` أو `mat-` وبيتشال) | نفس الصفحة → `ExhibitionDetailsView` (`app/directory/[type]/[id]/page.tsx:39-46`) | ملف المعرض/المورد + صفوف `services` حسب `slug` + products + brands، التقييمات، `has_rated`، `is_favorited` | `GET /api/v1/exhibitions/{slug}`، `GET /api/v1/reviews?target_slug=`؛ نفس الـ Mutations |
| `/projects/[id]` | `app/projects/[id]/page.tsx` (Client) → `ProjectDetailsView` | المشروع (`projects` by id) + بيانات صاحب المشروع (ملف المهندس/المكتب كامل) + باقي مشاريعه | `GET /api/v1/projects/{id}` (يرجّع `owner` و `other_projects`)؛ Mutation: `POST /api/v1/track/metric` |
| `/articles/[id]` | `app/articles/[id]/page.tsx` (`generateMetadata`) → `ArticleView` | **Static بالكامل** (`features/articles/topics.ts` 100 مقال + `features/articles/static.tsx` 3 مقالات) | لا شيء حاليًا (اختياري: `GET /api/v1/articles`, `GET /api/v1/articles/{id}` — شوف 5.2.17) |
| `/contact` | `ContactUs` | معلومات تواصل ثابتة؛ الفورم **مش بيتبعت لأي مكان** (محاكاة) | جديد: `POST /api/v1/contact-messages` |
| `/about`, `/privacy`, `/terms` | `AboutUs`, `Privacy`, `Terms` | Static بالكامل (لا Supabase ولا hooks) | لا شيء |
| Layout عام (كل الصفحات) | `app/layout.tsx` → `MetaPixel`, `QueryProvider`, `AuthInitializer`, `LayoutWrapper` (`Navbar`, `Footer`, `FloatingInstallButton`) + Google Analytics | لو المستخدم مسجل: `auth.getUser()`، ملفه من جدول نوعه، `users.status/rejection_reason`؛ رفع صورة البروفايل؛ Logout | `GET /api/v1/auth/me` (يرجع user + profile summary + status) ، `POST /api/v1/me/avatar` ، `POST /api/v1/auth/logout` (تفاصيلها في قسم Auth/Dashboard) |
| `/manifest.webmanifest` | `app/manifest.ts` | PWA manifest ثابت (name/short_name/icons/theme `#0070d7`/bg `#101922`/`dir: rtl`) — لا بيانات | لا شيء |
| Next route handlers موجودة حاليًا | `app/api/homepage/route.ts`, `app/api/public/stats/route.ts`, `app/api/analytics/track/route.ts`, `app/api/meta/event/route.ts`, `app/api/meta/purchase/route.ts` | — | تتحول لـ: `GET /api/v1/home`, `GET /api/v1/stats/public`, `POST /api/v1/track/analytics`, `POST /api/v1/meta/events`, `POST /api/v1/meta/purchase` |

> ملاحظة: الـ hooks `useHomepageData()` و `useGlobalStats()` (`features/dashboard/home/api/home.query.ts:50-77`) اللي بتنادي `/api/homepage` و `/api/public/stats` **مش مستخدمة في أي component** حاليًا — الصفحة الرئيسية بتجيب الداتا server-side مباشرة (`lib/homepage-data.server.ts`). والـ route `/api/analytics/track` كمان مالوش أي consumer (الفرونت بينادي الـ RPC مباشرة).

---

### 5.2 القراءات (Data Reads)

#### 5.2.0 قواعد عامة لكل القوائم في الموقع العام

- **ترتيب الباقة (`selected_plan`)**: كل القوائم بترتب بـ `.order("selected_plan", { ascending: false, nullsFirst: false })` كترتيب افتراضي أو ثانوي. الترتيب ده بيعتمد على نوع العمود في Postgres: لو `plan_type` ENUM متعرف بالترتيب `free, basic, pro, premium` يبقى DESC = premium أولًا؛ لو text يبقى أبجدي (`pro` > `premium` > `free` > `basic`). **في Laravel اعمل ترتيب صريح بوزن**: `premium=30`, `pro|professional=20`, `basic=10`, غير ذلك/`null`=0 (نفس `getPlanWeight` في `lib/homepage-display-settings.ts:44-55`).
- `isPremium` في كل الكروت = `selected_plan === "premium"` (بيظهر badge "مميز" + نجمة).
- **مفيش pagination server-side** في أي قائمة عامة: كل الصفوف بتتجاب مرة واحدة (بدون `range`/`limit`) والتقسيم لصفحات بيتم في المتصفح (12 لكل صفحة). المقترح: pagination حقيقي في الـ API.
- الـ Views (`public_engineers_view`, `public_offices_view`, `public_exhibitions_view`, `premium_active_view`, `marketing_active_users`) تعريفها **مش موجود في الريبو**. الأعمدة المذكورة هنا هي اللي الكود بيقراها فعلًا. رسالة الخطأ في صفحات التفاصيل ("يرجى التأكد من تفعيل الحساب أو مراجعة استهلاك الباقة الشهرية" — `EngineerDetailsView.tsx:758`, `OfficeDetailsView.tsx:795`) بتوحي إن الـ View بيخفي الحسابات غير المفعّلة (`users.status != 'active'`) والحسابات اللي عدّت حد الزيارات `user_permissions.max_visits` **(استنتاج)** — لازم الـ Laravel scope يطبّق نفس الشروط بعد مراجعة تعريف الـ View من قاعدة البيانات.
- كل الـ Searches في الكود الحالي بتعمل string interpolation جوه `.or(...)` و `ilike` — في Laravel استخدم bindings (`where('name','ilike',"%{$s}%")`).

---

#### 5.2.1 بانرات السلايدر الرئيسي — `/` — `features/dashboard/marketing/api/marketing.api.ts` (`getHomepageBanners`) + `marketing.server.ts`

- **Function**: `getCachedHomepageBanners()` → `getHomepageBanners()` (`marketing.api.ts:96-139`)، cache 60 ثانية (`marketing.server.ts:4-13`، tags `banners`, `homepage`).
- **Query 1**: `marketing_active_users` → `select("banner, slug, user_type")` + `.eq("plan_type", "premium")` + `.eq("user_type", "office")` + `.not("banner", "is", null)`.
  - بعد كده فلترة في JS: `banner && banner.image` لازم يكون موجود.
- **Query 2 (عدد النقرات)**: `stats` → `select("slug, count")` + `.in("slug", slugs)` → `clicks[slug] = Σ (count > 0 ? count : 1)` لكل صفوف الـ slug (كل الـ `metric_type`s مع بعض، مش الزيارات بس).
- **Mapping**: `{ image: banner.image, title: banner.title, description: banner.description, link: "/directory/{user_type}s/{slug}", clicks }` مترتبة **تصاعديًا** بالـ clicks (الأقل نقرات يظهر الأول)، و `HeroSlider.tsx:28-30` بيرتبهم تاني بنفس المنطق.
- **Fallback**: لو مفيش بانرات → `homeAds` من `Static/homePage.ts` (صورة واحدة محلية `office.png` بلينك `/directory/offices/moemen20223`، `title/description` فاضيين) (`app/page.tsx:71`).
- **UI بيعرض**: الصورة (fill)، و لو فيه `title` أو `description` بيظهر badge "مميز" + العنوان + الوصف؛ الضغط → `Link` لـ `link` + تتبع (شوف 5.3.5).

**Proposed** `GET /api/v1/banners`

| param | type | values | maps to |
|---|---|---|---|
| `placement` | string | `home`, `directory` | `home` ⇒ `plan_type='premium' AND user_type='office'` |
| `type` | string (مطلوب مع `directory`) | `engineers`, `offices`, `showrooms-suppliers` (القيم القديمة `exhibitions`/`materials` = `showrooms-suppliers`) | شوف 5.2.10 |

```json
{
  "data": [
    {
      "slug": "moemen20223",
      "user_type": "office",
      "title": "مكتب ... للاستشارات الهندسية",
      "description": "تصميم وإشراف",
      "image": "https://res.cloudinary.com/.../banner.jpg",
      "link": "/directory/offices/moemen20223",
      "clicks": 37
    }
  ]
}
```
> الـ `link` ممكن الـ API يرجّعه جاهز أو يرجّع `user_type + slug` والفرونت يبنيه. الـ fallback الثابت يفضل في الفرونت.

---

#### 5.2.2 أفضل المكاتب الهندسية (الرئيسية) — `/` — `lib/homepage-data.server.ts` + `features/offices/api/offices.api.ts` (`getPublicOffices`)

- **Function**: `getCachedHomepageData()` (`homepage-data.server.ts:14-63`, cache key `homepage_data_v3`, revalidate 300، tag `homepage`) → `getPublicOffices()` **بدون أي فلاتر** (= ترتيب افتراضي بالـ `selected_plan`).
- **Table/View**: `public_offices_view`
- **Columns**: `office_name, slug, phone, governorate, experience_years, profile_image, average_rating, total_visits, selected_plan, services, latitude, longitude, count_of_projects` (`offices.api.ts:93-97`).
- **فلترة إعدادات الرئيسية** (`homepage-data.server.ts:28-30`): `isPlanAllowed(selected_plan, settings.offices)` ثم ترتيب تنازلي بـ `getPlanWeight` — شوف 5.2.4. **مفيش limit** (كل المكاتب المسموحة).
- **Mapping** (`offices.api.ts:138-153`): `id = slug`, `name = office_name`, `category = services[0].name || "مكتب هندسي"`, `region = governorate`, `phone`, `image = profile_image`, `rating = average_rating`, `countOfProjects = count_of_projects`, `viewsCount = total_visits`, `experienceYears = experience_years`, `isPremium`, `selected_plan`, `lat = latitude`, `lng = longitude`.
- **UI (`OfficeCard.tsx`) بيعرض**: الصورة (أو avatar افتراضي)، `name`، "`countOfProjects` مشاريع"، `region`، `phone`، "`experienceYears` سنين خبرة"؛ اللينك `/directory/offices/{slug}`. السلايدر مايظهرش لو القائمة فاضية.

**Proposed** `GET /api/v1/offices?featured=1` (نفس endpoint الدليل، والـ `featured=1` يطبق إعدادات الرئيسية والترتيب بالوزن ويلغي الـ pagination أو `per_page` كبير)

| param | type | values | maps to |
|---|---|---|---|
| `featured` | boolean | `1` | فلترة `isPlanAllowed(selected_plan, settings.offices)` + `ORDER BY plan_weight DESC` |
| `limit` | int (اختياري) | مثلًا `20` | حاليًا مفيش limit |

Response item: نفس شكل عنصر قائمة المكاتب في 5.2.12.

---

#### 5.2.3 أبرز المهندسين (الرئيسية) — `/` — `lib/homepage-data.server.ts` + `features/engineers/api/engineers.api.ts` (`getPublicEngineers`)

- `getPublicEngineers()` بدون فلاتر → `public_engineers_view`
- **Columns**: `name, slug, phone, major, email, governorate, experience_years, profile_image, average_rating, total_visits, selected_plan, services, latitude, longitude` (`engineers.api.ts:98-102`).
- فلترة `isPlanAllowed(selected_plan, settings.engineers)` + ترتيب بالوزن (`homepage-data.server.ts:33-35`). مفيش limit.
- **Mapping** (`engineers.api.ts:144-159`): `id = slug`, `name`, `specialization = major`, `region = governorate`, `phone`, `email`, `image = profile_image`, `rating = average_rating`, `viewsCount = total_visits`, `experienceYears`, `isPremium`, `selected_plan`, `lat`, `lng`.
- **UI (`EngineerCard.tsx`)**: صورة، `name` (+ نجمة لو premium)، "مهندس `{specialization}`"، `region`، "`experienceYears` سنين خبرة"، `phone`؛ لينك `/directory/engineers/{slug}`.

**Proposed** `GET /api/v1/engineers?featured=1` — نفس الشرح في 5.2.2 (بإعدادات `engineers`).

---

#### 5.2.4 إعدادات عرض الرئيسية (Homepage display settings) — `/` — `lib/homepage-display-settings.ts` + `lib/homepage-display-settings.server.ts`

- **المصدر حاليًا ملف JSON على السيرفر** مش جدول: `data/homepage-display-settings.json` (`homepage-display-settings.server.ts:8`)، بيتقري بـ `fs.readFileSync` ويتدمج مع الـ defaults. الأدمن بيكتبه عن طريق `POST /api/admin/homepage-display` (قسم الأدمن).
- **الشكل** (`HomepageDisplaySettings`):
  ```json
  { "offices":   { "premium": true, "professional": true, "basic": true, "free": false },
    "engineers": { "premium": true, "professional": true, "basic": true, "free": false } }
  ```
  (القيمة الحالية في الملف = الـ defaults بالظبط `homepage-display-settings.ts:13-26`).
- **Normalization** (`normalizePlanTier`, `:30-37`): `trim().toLowerCase()`؛ `premium`→premium، `pro` أو `professional`→professional، `basic`→basic، أي حاجة تانية أو `null`→free.
- **Weights** (`:44-55`): premium 30، professional 20، basic 10، free 0.
- **المقترح في Laravel**: جدول `settings` (key/value JSON) بمفتاح `homepage_display`، والـ endpoints `?featured=1` تطبقه server-side. قراءة عامة اختيارية: `GET /api/v1/settings/homepage-display` → نفس الـ JSON (القراءة/الكتابة للأدمن موثقة في قسم الأدمن).

---

#### 5.2.5 أعمال شركائنا المميزة — `/` — `features/dashboard/projects/api/projects.api.ts` (`getPublicProjects`) + `features/projects/components/ProjectsSection.tsx`

- **Function**: `getPublicProjects(100)` (`projects.api.ts:50-59`) → `premium_active_view` → `select('*')` + `.order('created_at', desc)` + `.limit(100)`.
- **Post-processing** (`homepage-data.server.ts:38-47`): مشروع واحد بس لكل `user_id` (أول ظهور = الأحدث)، واستبعاد أي صف `user_id` فاضي، ثم `slice(0, 15)`.
- **Columns المستخدمة**: `id, user_id, name, location, area, type, image, slug` (slug صاحب المشروع)، `user_type` (`engineer`|`office`)، `user_name`, `details { brief, video, photos[], categories[] }`, `created_at`.
- **UI**: كل كارت فيه badge "مميز" ثابت، الصورة، `type` (badge)، `name`، `user_name`، `location`، "`area` م²".
- **اللينك** (`ProjectsSection.tsx:65-78`): لو `details` فيها أي من (`brief`, `video`, `photos.length>0`, `categories.length>0`) → `/projects/{id}`؛ غير كده → `/directory/{engineers|offices}/{slug}` (حسب `user_type === "engineer"`).
- **Fallback**: لو القائمة فاضية → 4 مشاريع ثابتة من `features/projects/static.ts` (صور محلية، `user_id: "static"`، لينك `/directory/offices`).
- الـ prop `stats` بيتبعت للـ component بس **مش مستخدم**.

**Proposed** `GET /api/v1/projects/featured`

| param | type | values | maps to |
|---|---|---|---|
| `limit` | int | default `15` | `slice(0,15)` بعد dedupe |
| `one_per_owner` | boolean | default `1` | dedupe بالـ `user_id` |

```json
{
  "data": [
    {
      "id": "8f1c...-uuid",
      "user_id": "2b7e...-uuid",
      "name": "تشطيب فيلا سكنية",
      "location": "الزقازيق - الشرقية",
      "area": 450,
      "type": "تصميم داخلي",
      "image": "https://res.cloudinary.com/.../p1.jpg",
      "owner": { "slug": "moemen20223", "user_type": "office", "name": "مؤمن محمد عبدربه" },
      "has_details": true,
      "details": { "brief": "...", "video": "https://youtu.be/xxxx", "photos": ["https://..."], "categories": ["تصميم داخلي"] },
      "created_at": "2026-05-08T12:52:08Z"
    }
  ]
}
```
> شروط `premium_active_view` (باقات نشطة) مش ظاهرة في الكود — حسب `t3meer_full_analysis.md` هو `projects + users` لمستخدمي الباقات النشطة **(استنتاج)**.

---

#### 5.2.6 أرقام نفخر بها (إحصائيات عامة) — `/` — `features/dashboard/home/api/home.api.ts` (`getGlobalStats`) + `features/analysis/components/AnalysisSection.tsx` + `app/api/public/stats/route.ts`

- **Function**: `getGlobalStats()` (`home.api.ts:399-429`):
  1. `public_engineers_view` → `select("*", { count: "exact", head: true })` → `engineers`
  2. `public_offices_view` → نفس الشيء → `offices`
  3. `stats` → `count exact head` + `.eq("metric_type", "visit")` → عدد **الصفوف** (مش مجموع عمود `count`)
  4. `analytics_counts` → `select("total_count")` + `.eq("stat_type", "page")` → مجموع `total_count`
  - `visits = (3) + (4)`
- **UI** (`AnalysisSection.tsx`, بيانات العناوين من `features/analysis/static.ts`): 3 خانات: "مهندس معتمد" = `engineers`، "مكتب هندسي" = `offices`، "زيارات الموقع الشهرية" = `visits + 1000` (**+1000 ثابتة في الفرونت** `AnalysisSection.tsx:31`)، وكل رقم بعده "+". العناصر 2 و 4 (معارض/موردين) متعلّقة (commented).
- **ملاحظة مهمة**: لأن `trackMetricClick` بيعمل update على نفس الصف (شوف 5.3.5)، عدّ الصفوف في (3) = عدد الـ slugs اللي اتزارت، مش عدد الزيارات. لازم يتحدد في Laravel هل المقصود `SUM(count)`.
- `/api/public/stats` بيرجع نفس الكائن مع cache 300s (مش مستخدم من الفرونت).

**Proposed** `GET /api/v1/stats/public` (Cache 5 دقائق)

```json
{ "data": { "engineers": 128, "offices": 46, "visits": 15230 } }
```

---

#### 5.2.7 أسعار مواد البناء — `/` — `features/prices/api/prices.api.ts` (`getLatestPrices`) + `features/prices/components/PricesTable.tsx`

- **Table**: `materials_prices` → `select("*")` — **بدون فلتر وبدون ترتيب** (`prices.api.ts:19-38`).
- **Mapping**: `category`, `name = item_name`, `unit`, `price`, `change = change_percent || 0`.
- **UI**: جدول 3 أعمدة: "المادة (الوحدة)" = `name (unit)`، "السعر (ج.م)" = `price.toLocaleString()`، "التغير" = `|change|%` بلون (سالب = أخضر/نازل، موجب = أحمر/طالع، صفر = رمادي). `category` **مش معروض**.
- **Fallback**: لو مفيش صفوف → `features/prices/static.ts` (`pricesTable`).
- Hook `useMaterialPrices()` (staleTime ساعة) موجود بس الرئيسية بتاخد الداتا من السيرفر.

**Proposed** `GET /api/v1/material-prices`

| param | type | values | maps to |
|---|---|---|---|
| `category` | string (اختياري) | قيم `materials_prices.category` (مثلًا `الحديد`, `الأسمنت`) | `where category = ?` — مش مستخدم حاليًا |

```json
{
  "data": [
    { "id": 1, "category": "الحديد", "item_name": "حديد عز (قياسي)", "unit": "طن", "price": 39800, "change_percent": 1.5, "updated_at": "2026-11-01T09:00:00Z" }
  ]
}
```
> يفضّل ترتيب ثابت (مثلًا `category, id`) لأن الحالي بيعتمد على ترتيب الـ DB.

---

#### 5.2.8 ماذا يقول عملاؤنا (تقييمات المنصة) — `/` — `features/testimonials/components/TestimonialsSection.tsx` + `features/dashboard/customer/api/reviews.api.ts`

- **Server**: `getReviewsBySlug("t3meer")` (`reviews.api.ts:84-103`) جوه `getCachedHomepageData` → `rating` → `select("*")` + `.eq("slug","t3meer")` + `.order("created_at", desc)` — **بدون limit**.
- **Client** (`TestimonialsSection.tsx:48-49`): بيعيد نفس الاستعلام `useProfessionalReviews("t3meer")` + `useHasUserRated(user.id, "t3meer")` (`rating` → `select("id")` `.eq("user_id")` `.eq("slug")` `.maybeSingle()`).
- **Mapping للعرض** (`:55-65`): 3 شهادات ثابتة من `features/testimonials/static.ts` **+** الشهادات الحية: `{ name, role: formatUserType(target_type), text: message, rating, image }`.
  - `formatUserType`: `engineer`→"مهندس"، `office`→"مكتب هندسي"، `exhibition`→"معرض او مورد"، `customer` أو فاضي→"عميل"، غير كده = القيمة نفسها.
  - ⚠️ في تقييمات المنصة، عمود `rating.type` بيتخزن فيه **نوع المُقيِّم** (مش نوع الهدف) — شوف 5.3.2.
- **متوسط وعدد** (`:67-69`, `:111`): المتوسط = متوسط التقييمات الحية `toFixed(1)` أو `"4.9"` لو مفيش؛ العدد المعروض = `liveReviews.length + 12` (**+12 ثابتة**).
- زر "قيم تجربتك" بيختفي لو `has_rated = true`.
- **TestimonialCard** بيعرض: نجوم بعدد `rating`، `text` (3 سطور)، الصورة أو أيقونة، `name`، `role`.

**Proposed** `GET /api/v1/reviews?target_slug=t3meer` (نفس endpoint تقييمات الملفات — 5.2.15) + `GET /api/v1/reviews/status?target_slug=t3meer` (auth).

---

#### 5.2.9 المقالات (اليومية + صفحة المقال) — `/`, `/articles/[id]` — `features/articles/*`

- **Static بالكامل** — مفيش أي قراءة من DB:
  - `features/articles/topics.ts` → `constructionTopics` (100 مقال، `id` 1..100) بالحقول `id, title, description, content, image (Unsplash URL), date (نص زي "تم النشر اليوم"), readTime`.
  - `features/articles/static.tsx` → `articles` (3 مقالات، `id` 1..3، نفس الحقول).
- **ArticleSection** (الرئيسية): بيختار 3 مقالات من `constructionTopics` حسب يوم السنة: `start = (dayOfYear*3) % length` → العناصر `start, start+1, start+2`؛ اللينك `/articles/{id}?type=topic`.
- **ArticleView**: بيدور في `articles` **الأول** ثم `constructionTopics` (`ArticleView.tsx:12-14`) — والـ `?type=topic` **متجاهَل** ⇒ IDs 1..3 بتعرض مقال من `static.tsx` غير اللي في الـ metadata (`app/articles/[id]/page.tsx:13` بيستخدم `constructionTopics`). Bug تضارب IDs.
- المحتوى بيتقسم بالـ `\n`؛ أزرار مشاركة (Facebook sharer, Twitter intent, WhatsApp `api.whatsapp.com/send`, نسخ الرابط) كلها client-side.
- **لو اتقرر تبقى ديناميكية**: جدول `articles` (`id, title, description, content (text/markdown), image, published_at, read_time_minutes, source enum('topic','article'), is_active`) و:
  - `GET /api/v1/articles?daily=1&limit=3` (أو `?page=`) → `[{id, title, description, image, published_at, read_time}]`
  - `GET /api/v1/articles/{id}` → `{id, title, description, content, image, published_at, read_time}` (يُستخدم في `generateMetadata` كمان).

---

#### 5.2.10 بانرات الدليل — `/directory/[type]` — `features/dashboard/marketing/api/marketing.api.ts` (`getDirectoryBanners`)

- **Call**: `getCachedDirectoryBanners(type === 'showrooms-suppliers' ? 'exhibition' : type)` (`app/directory/[type]/page.tsx:92`)، cache 120s (`marketing.server.ts:15-26`، tag `directory_{type}`).
- **Logic** (`marketing.api.ts:47-94`):
  - `singularType` = إزالة `s` الأخيرة: `engineers`→`engineer`، `offices`→`office`، `exhibition`→`exhibition`، `exhibitions`→`exhibition`، `materials`→`material`.
  - `planType = singularType === "engineer" ? "premium" : "professional"` (`:53`). ⚠️ لو عمود `plan_type` بيخزّن `pro` مش `professional` الاستعلام ده مش هيرجع حاجة — لازم يتراجع.
  - `marketing_active_users` → `select("banner, slug")` + `.eq("user_type", singularType)` + `.eq("plan_type", planType)` + `.not("banner","is",null)` → فلترة `banner.image`.
  - clicks من `stats` بنفس طريقة 5.2.1، ترتيب تصاعدي.
  - `link = /directory/{userType === 'exhibition' ? 'showrooms-suppliers' : userType}/{slug}` (`:90`) — مثلًا `/directory/engineers/{slug}`، `/directory/showrooms-suppliers/{slug}` (من غير `ex-`؛ صفحة التفاصيل بتقبله).
- **Fallback** (`page.tsx:93-102`): `engineers` → `engineersAds` (`/directory/engineers/moemen20111`)، غير كده → `homeAds`.

**Proposed** `GET /api/v1/banners?placement=directory&type={type}` (نفس الـ response في 5.2.1).

| `type` | `user_type` | `plan_type` |
|---|---|---|
| `engineers` | `engineer` | `premium` |
| `offices` | `office` | `professional` |
| `showrooms-suppliers` | `exhibition` | `professional` |
| `exhibitions` | `exhibition` | `professional` |
| `materials` | `material` | `professional` |

---

#### 5.2.11 قائمة دليل المهندسين — `/directory/engineers` — `features/directory/components/DirectoryListing.tsx` + `features/engineers/api/engineers.api.ts` (`getPublicEngineers`)

- **Server initial** (`app/directory/[type]/page.tsx:30-43`): `getCachedPublicEngineers()` (بدون فلاتر، cache 120s، tags `engineers`, `directory`) → `items` prop.
- **Client**: `usePublicEngineers({category, region, search, sort, enabled: type==="engineers"})` (queryKey `["public_engineers", category, region, search, sort]`, staleTime 5 دقائق) بيستبدل الـ items أول ما يرجع.
- **View**: `public_engineers_view`، الأعمدة زي 5.2.3.
- **Filters** (`engineers.api.ts:104-114`):
  - `category` (لو مش فاضي): `.contains("services", JSON.stringify([{ name: category }]))` ⇒ JSONB containment على عمود `services` (array of `{name,...}`).
  - `region` (لو مش فاضي): `.ilike("governorate", "%{region}%")`.
  - `search` (لو مش فاضي): `.or("name.ilike.%{search}%,major.ilike.%{search}%")`.
- **Sort** (`:117-135`):
  - `experience` → `experience_years DESC`, ثم `selected_plan DESC NULLS LAST`
  - `bestViewers` → `total_visits DESC`, ثم `selected_plan`
  - `bestRating` → `average_rating DESC`, ثم `selected_plan`
  - غير ذلك (`الكل` / `nearMe`) → `selected_plan DESC NULLS LAST`
  - `nearMe`: بعد الاستعلام، المتصفح بياخد الموقع بـ `navigator.geolocation` (`lib/helper-functions.ts:137`) ويرتب بـ Haversine (`calculateDistance`, `:190-208`، بالمتر)، والعناصر اللي من غير `lat/lng` في الآخر (`DirectoryListing.tsx:396-421`). لو المستخدم رفض → يرجع `الكل` + رسالة "يرجى السماح بالوصول للموقع للحصول على أقرب النتائج".
- **Pagination**: client-side، `ITEMS_PER_PAGE = 12` (`DirectoryListing.tsx:323`)، النص "عرض {from}-{to} من {total} نتيجة"، أزرار صفحات (≤7 صفحات كلها، وإلا 1 … current±1 … last). أي تغيير فلتر يرجّع للصفحة 1.
- **Search بدون debounce**: كل حرف بيعمل query جديدة (`Filter.tsx:96`).
- **UI الكارت** (`EngineerCardItem`, `:160-228`): `FavoriteSpan` (type `engineer`, `targetName`, `targetImage`, `targetSlug = id`, `targetPhone`)، `RatingSpan(rating||0)`، `ViewsSpan(viewsCount||0)`، صورة/أفاتار، badge "مميز" لو `isPremium`، `name`، "مهندس {specialization}"، `region`، `phone`، "{experienceYears} سنين خبرة"؛ لينك `/directory/engineers/{slug}`.
- **Tracking** مع الفلاتر: شوف 5.3.6.

**Proposed** `GET /api/v1/engineers`

| param | type | values | maps to |
|---|---|---|---|
| `search` | string ≤100 | نص حر | `name ILIKE %s% OR major ILIKE %s%` |
| `category` | string | قيم 5.2.14 (تخصصات المهندسين) | `services @> [{"name": category}]` / `whereHas('services', name = ?)` |
| `governorate` | string | المحافظات الـ 27 (5.2.14) | حاليًا `governorate ILIKE %v%` (يُفضّل `=`) |
| `sort` | string | `experience`, `bestViewers`, `bestRating`, `nearMe` (الغياب = افتراضي) | شوف أعلاه؛ الافتراضي `plan_weight DESC` |
| `lat`, `lng` | numeric | مطلوبين لو `sort=nearMe` | `ORDER BY haversine(lat,lng,latitude,longitude) ASC NULLS LAST` |
| `page` | int | ≥1 | client-side حاليًا |
| `per_page` | int | default `12` | `ITEMS_PER_PAGE` |
| `featured` | boolean | `1` | 5.2.3 |

```json
{
  "data": [
    {
      "slug": "ahmed-ali-2031",
      "name": "أحمد علي",
      "major": "مدني",
      "governorate": "القاهرة",
      "phone": "01012345678",
      "email": "ahmed@example.com",
      "profile_image": "https://res.cloudinary.com/.../a.jpg",
      "average_rating": 4.6,
      "total_visits": 340,
      "experience_years": 8,
      "selected_plan": "premium",
      "is_premium": true,
      "latitude": 30.0444,
      "longitude": 31.2357,
      "distance_m": null,
      "is_favorited": false
    }
  ],
  "meta": { "current_page": 1, "per_page": 12, "total": 128, "last_page": 11 }
}
```
> `is_favorited` يرجع بس لو فيه Bearer token (يلغي N query من `FavoriteSpan` — 5.2.13). الفرونت حاليًا بيحوّل الحقول لـ (`id=slug`, `specialization=major`, `region=governorate`, `image`, `rating`, `viewsCount`, `experienceYears`, `isPremium`, `lat`, `lng`).

---

#### 5.2.12 قائمة دليل المكاتب الهندسية — `/directory/offices` — `DirectoryListing.tsx` + `features/offices/api/offices.api.ts` (`getPublicOffices`)

- Server initial: `getCachedPublicOffices()` (cache 120s، tags `offices`, `directory`)؛ Client: `usePublicOffices(...)` (queryKey `["public_offices", ...]`).
- **View**: `public_offices_view`، الأعمدة زي 5.2.2.
- **Filters** (`offices.api.ts:99-109`):
  - `category` → `.contains("services", [{ name: category }])`
  - `region` → `.ilike("governorate", "%{region}%")`
  - `search` → `.ilike("office_name", "%{search}%")` (**الاسم بس**، رغم إن الـ placeholder بيقول "بالاسم أو التخصص").
- **Sort**: نفس منطق 5.2.11 بالظبط (`:112-129`) + `nearMe` client-side.
- **UI الكارت** (`OfficeCardItem`, `:230-298`): `FavoriteSpan` (type `office`)، Rating، Views، صورة، badge "مميز"، `name`، "{countOfProjects || 0} مشروع"، `region`، `phone`، "{experienceYears} سنين خبرة"؛ لينك `/directory/offices/{slug}`.

**Proposed** `GET /api/v1/offices` — نفس جدول params في 5.2.11 مع: `search` → `office_name ILIKE %s%`.

```json
{
  "data": [
    {
      "slug": "moemen20223",
      "name": "مكتب ... للاستشارات الهندسية",
      "primary_service": "تصميم معماري",
      "governorate": "الشرقية",
      "phone": "01012345678",
      "profile_image": "https://res.cloudinary.com/.../o.jpg",
      "average_rating": 4.8,
      "total_visits": 512,
      "experience_years": 12,
      "count_of_projects": 85,
      "selected_plan": "professional",
      "is_premium": false,
      "latitude": 30.5877,
      "longitude": 31.502,
      "is_favorited": false
    }
  ],
  "meta": { "current_page": 1, "per_page": 12, "total": 46, "last_page": 4 }
}
```
> ⚠️ القائمة بتقرا `office_name` لكن صفحة التفاصيل بتعرض `office.name` (`OfficeDetailsView.tsx:244`) — يعني الـ View فيه العمودين. في الـ API رجّع `name` واحد موحّد. `primary_service = services[0].name || "مكتب هندسي"` (مش معروض في كارت الدليل حاليًا).

---

#### 5.2.13 قائمة دليل المعارض والموردين — `/directory/showrooms-suppliers` (+ `/directory/exhibitions`, `/directory/materials` نفس المصدر) — `DirectoryListing.tsx` + `features/showrooms-suppliers/api/showrooms-suppliers.api.ts`

- Server initial: `getCachedPublicShowroomsSuppliers()` (cache 120s)؛ Client: `usePublicShowroomsSuppliers(...)` (enabled للأنواع الـ 3).
- **View**: `public_exhibitions_view` → `select("*")`.
- **Filters** (`showrooms-suppliers.api.ts:17-25`):
  - `region` (لو مش فاضي ومش `"الكل"`) → `.eq("governorate", region)` (**مساواة**، مش ilike زي المهندسين).
  - `search` (trim) → `.or("exhibition_name.ilike.%s%,name.ilike.%s%")`.
  - `category` → **بعد الاستعلام في JS** (`:73-79`): `item.category === category` (و `services.some(...)` اللي بعدها مش بتشتغل فعليًا لأن الـ mapped item مافيهوش `services`) ⇒ عمليًا فلترة على `main_category` (أو `services[0].name`).
- **Sort**: نفس 5.2.11 (`:28-46`) + `nearMe`.
- **Mapping** (`:55-71`): `id = "ex-" + slug`, `slug`, `name = exhibition_name || name || ""`, `category = main_category = main_category || services[0].name || "معرض"`, `region = governorate || "القاهرة"`, `phone = phone || whats_app || ""`, `image = logo || profile_image || banner || ""`, `rating = Number(average_rating)||0`, `viewsCount = Number(total_visits)||0`, `experienceYears`, `isPremium`, `lat/lng` (Number أو undefined), `sourceType: "exhibition"`.
- **UI الكارت** (`ShowroomSupplierCardItem`, `:83-158`): صورة (object-contain + خلفية blur)، `FavoriteSpan` (type = `sourceType === "exhibition" ? "exhibition" : "material"` ⇒ دايمًا `exhibition`)، Rating، Views، `name`، badge "مميز"، أيقونة + `main_category` (أيقونة من map ثابت `getCategoryIcon`, `:52-81`)، `region`، `phone`؛ لينك `/directory/showrooms-suppliers/ex-{slug}`.
- `getPublicExhibitions()` و `getPublicMaterials()` / `public_materials_view` موجودين في `features/exhibitions/api` و `features/materials/api` بس **مش مستخدمين** في الدليل (الأول مستخدم فقط في `ExhibitionSlider` المعلّق في الرئيسية، والتاني dead code — `public_materials_view` اتلغى حسب `t3meer_full_analysis.md`).

**Proposed** `GET /api/v1/exhibitions`

| param | type | values | maps to |
|---|---|---|---|
| `search` | string ≤100 | نص | `exhibition_name ILIKE OR name ILIKE` |
| `category` | string | الـ 23 فئة (5.2.14) | `main_category = ?` (يُفضّل server-side + اختياريًا `OR services.name = ?`) |
| `governorate` | string | الـ 27 محافظة | `governorate = ?` |
| `sort` | string | `experience`, `bestViewers`, `bestRating`, `nearMe` | زي 5.2.11 |
| `lat`, `lng` | numeric | مع `nearMe` | Haversine |
| `page`, `per_page` | int | `per_page` default 12 | — |

```json
{
  "data": [
    {
      "slug": "elnour-ceramics",
      "name": "معرض النور للسيراميك",
      "main_category": "السيراميك والبورسلين",
      "governorate": "القاهرة",
      "phone": "01098765432",
      "image": "https://res.cloudinary.com/.../logo.png",
      "average_rating": 4.3,
      "total_visits": 210,
      "experience_years": 6,
      "selected_plan": "basic",
      "is_premium": false,
      "latitude": 30.05,
      "longitude": 31.24,
      "is_favorited": false
    }
  ],
  "meta": { "current_page": 1, "per_page": 12, "total": 64, "last_page": 6 }
}
```

---

#### 5.2.14 قيم فلاتر الدليل (Filter options) — `Static/directoryConfig.ts` + `features/auth/profile/api/profile.static.ts` + `components/ui/Filter.tsx`

**قائمة "الفئة" الفعلية** بتتحدد في `DirectoryListing.tsx:368-381` (مش من `config.categories`):

| Directory `type` | مصدر قائمة الفئة | القيمة اللي بتتبعت للفلتر | العمود |
|---|---|---|---|
| `engineers` | `engineerSpecializations.map(s => s.label)` | الـ label بالعربي | `services[].name` |
| `offices` | **نفس** `engineerSpecializations` (مش `officeServices`) | الـ label | `services[].name` |
| `showrooms-suppliers`, `exhibitions`, `materials` | `showroomSupplierCategories.map(s => s.label)` | الـ label | `main_category` |

- **تخصصات المهندسين/المكاتب (16)** — `label` (value): تصميم معماري (`architectural-design`)، حصر كميات (`quantity-surveying`)، رسومات تنفيذية (`shop-drawings`)، إدارة مشاريع (`project-management`)، تصميم داخلي (`interior-design`)، تشطيبات (`finishing`)، استشارات هندسية (`engineering-consulting`)، تراخيص بناء (`building-permits`)، رفع مساحة (`surveying`)، تصالح للبناء (`legalization`)، تصميم انشائي (`structural-design`)، تصميم واجهات (`facade-design`)، اعمال عزل (`insulation-works`)، اعمال خرسانات (`concrete-works`)، اعمال ترميم (`restoration-works`)، خدمات نقابية (`syndicate-services`).
  - للعلم: `officeServices` (اللي المكاتب بتختار منها في ملفها) = تصميم معماري، تراخيص بناء، رفع مساحة، رسم هندسي (`engineering-drawing`)، تصالح للبناء، تصميم انشائي، اشراف علي التنفيذ (`supervision`)، خدمات نقابية ⇒ "رسم هندسي" و "اشراف علي التنفيذ" **مش موجودين** في فلتر المكاتب.
- **فئات المعارض والموردين (23)**: مواد البناء الأساسية، الحديد والمعادن، الخرسانة والإنشاءات، العزل، السباكة والمواسير، الكهرباء، الأخشاب، الجبس والأسقف، مواد الترميم والكيماويات، العدد والمعدات، الأثاث والفرش، السيراميك والبورسلين، الأدوات الصحية، الإضاءة، الدهانات والديكور، الأبواب والشبابيك، الرخام والجرانيت، الواجهات الخارجية، اللاندسكيب، المطابخ والدريسنج، التكييف والتهوية، الطاقة الشمسية، الأمن والسلامة. (`value === label` لكل واحدة.)
- **المحافظات (27)** — `egyptGovernorates` (`Static/directoryConfig.ts:10-38`) لكل الأنواع: القاهرة، الجيزة، الإسكندرية، الدقهلية، الشرقية، القليوبية، الغربية، المنوفية، البحيرة، كفر الشيخ، دمياط، بورسعيد، الإسماعيلية، السويس، الفيوم، بني سويف، المنيا، أسيوط، سوهاج، قنا، الأقصر، أسوان، البحر الأحمر، الوادي الجديد، مطروح، شمال سيناء، جنوب سيناء.
- **الترتيب** (`Filter.tsx:34-52`) — `key` → label: `الكل` → الكل (افتراضي)، `nearMe` → قريب مني، `experience` → سنين الخبرة، `bestViewers` → الأكثر مشاهدة، `bestRating` → الأعلى تقييماً.
- **"الكل"** في الفئة/المحافظة بيبعت `""` (string فاضي) = بدون فلتر. زر "مسح الفلاتر" بيرجّع category/region = `""` و sort = `الكل` (البحث مش بيتمسح).
- `directoryConfigs[type].categories` (**مش مستخدمة** إلا كـ default في switch مش بيتنفّذ): engineers = مهندس مدني، مهندس معماري، مهندس كهرباء، مهندس ميكانيكا، مهندس إنشائي، مهندس مساحة، مهندس طرق؛ offices = استشارات معمارية وإنشائية، تصميم معماري، ديكور وتصميم داخلي، إشراف هندسي وتنفيذ، تصميم معماري وواجهات، استشارات إنشائية، تصميم داخلي ومعماري؛ الأنواع الـ 3 الباقية = نفس الـ 23 فئة.
- باقي الـ config لكل نوع (title/titleSingle/description/seoTitle/seoDescription/icon/searchPlaceholder) نصوص ثابتة في الفرونت — مش محتاجة API. `validTypes = engineers, offices, showrooms-suppliers, exhibitions, materials` وأي نوع تاني → 404.
- **اقتراح**: `GET /api/v1/lookups/directory-filters?type=` يرجّع `{categories: [{value,label,icon,color}], governorates: [...], sorts: [{key,label}]}` لو عايزين يتحكموا فيها من الباك.

---

#### 5.2.15 حالة المفضلة لكل كارت/ملف — كل صفحات الدليل والتفاصيل — `components/ui/FavoriteSpan.tsx` + `features/dashboard/customer/api/favorites.api.ts`

- `useIsFavorited(userId, slug)` (enabled لو فيه user و slug) → `isFavorited()` (`favorites.api.ts:28-41`): `favorites` → `select("id")` `.eq("user_id", userId)` `.eq("slug", slug)` `.maybeSingle()` → boolean.
- في الدليل **كل كارت** بيعمل query لوحده (N+1 = حتى 12 query في الصفحة، وفي الحقيقة على كل العناصر المعروضة).
- العرض: قلب أحمر لو `true`، ومع الـ mutation pending بيعكس القيمة (optimistic).

**Proposed**:
- في القوائم/التفاصيل: حقل `is_favorited` في كل عنصر لو الطلب authenticated.
- أو batch: `GET /api/v1/favorites/status?slugs[]=a&slugs[]=b` (auth) → `{ "data": { "a": true, "b": false } }`.

---

#### 5.2.16 تفاصيل المهندس — `/directory/engineers/[slug]` — `features/directory/components/details/EngineerDetailsView.tsx` + `features/engineers/api/engineers.api.ts` (`getEngineerDetailsBySlug`)

- **Query**: `public_engineers_view` → `select("*")` `.eq("slug", slug)` `.single()` (`engineers.api.ts:69-87`) → `{ engineer: row, projects: row.projects || [], services: row.services || [], permissions: row.permissions || null }`. queryKey `["engineer_details", slug]`, staleTime 5 دقائق، retry 1.
- **Embedded JSON في الـ View**: `projects` (array)، `services` (array)، `permissions` (object من `user_permissions`).
- **حقول بتتعرض** (`EngineerDetailsContent`):
  - Sidebar: `profile_image`، `name`، `average_rating` (badge بيعمل scroll للتقييمات)، "مهندس `{major}`"، "`{experience_years}` سنوات خبرة"، `governorate`.
  - زر واتساب: `https://wa.me/+2{(whats_app || phone) digits only}`.
  - معلومات الاتصال: المفضلة (toggle)، `phone` (`tel:`)، واتساب، `email` (`mailto:`)، `facebook`/`instagram`/`youtube` (لينكات).
  - "نبذة عني": `bio` (أو نص افتراضي).
  - "المهارات والبرامج": `skills[]` (strings).
  - "التخصصات و الخدمات": `services[]` → `name`, `icon` (default `solar:check-circle-bold`), `color` (default `#0070d7`), `id`.
  - "سابقة الأعمال": `projects[]` → `id, name, image, type, location, area, details{brief, video, photos[], categories[]}`؛ اللينك `/projects/{id}` لو فيه details، غير كده يفتح الصورة في tab جديد.
  - "تقييمات العملاء": من جدول `rating` (5.2.19) مع `fallbackRating = average_rating`, `fallbackCount = reviews_count`.
  - `permissions` بيتبعت للـ component **بس مش مستخدم** في صفحة المهندس.
- **عند فتح الصفحة**: `track("visit")` (5.3.5).
- **Error state**: "نعتذر، هذا الملف غير متاح للعرض حالياً. يرجى التأكد من تفعيل الحساب أو مراجعة استهلاك الباقة الشهرية عبر لوحة التحكم." + رجوع لـ `/directory/engineers`.
- الصفحة Client Component بالكامل ⇒ مفيش SEO metadata للملف.

**Proposed** `GET /api/v1/engineers/{slug}` (Optional auth لإرجاع `viewer`). 404 لو مش ظاهر.

```json
{
  "data": {
    "slug": "ahmed-ali-2031",
    "name": "أحمد علي",
    "major": "مدني",
    "governorate": "القاهرة",
    "experience_years": 8,
    "bio": "مهندس مدني متخصص في ...",
    "skills": ["AutoCAD", "Revit", "ETABS"],
    "profile_image": "https://res.cloudinary.com/.../a.jpg",
    "phone": "01012345678",
    "whats_app": "01012345678",
    "email": "ahmed@example.com",
    "facebook": "https://facebook.com/...",
    "instagram": null,
    "youtube": null,
    "average_rating": 4.6,
    "reviews_count": 12,
    "total_visits": 340,
    "selected_plan": "premium",
    "services": [
      { "id": 31, "name": "تصميم انشائي", "icon": "mdi:pillar", "color": "#a78bfa" }
    ],
    "projects": [
      {
        "id": "8f1c...-uuid", "name": "عمارة سكنية", "type": "تصميم انشائي", "location": "مدينة نصر",
        "area": 600, "image": "https://res.cloudinary.com/.../p.jpg",
        "details": { "brief": "...", "video": null, "photos": [], "categories": [] }
      }
    ],
    "permissions": { "plan_type": "premium", "google_maps_location": true, "video_projects": true, "whatsapp_tracking": true, "call_tracking": true, "detailed_analytics": true, "max_visits": null, "max_projects": 20, "max_services": 10 },
    "viewer": { "is_favorited": false, "has_rated": false }
  }
}
```

---

#### 5.2.17 تفاصيل المكتب الهندسي — `/directory/offices/[slug]` — `features/directory/components/details/OfficeDetailsView.tsx` + `features/offices/api/offices.api.ts` (`getOfficeDetailsBySlug`)

- **Query**: `public_offices_view` → `select("*")` `.eq("slug", slug)` `.single()` (`offices.api.ts:64-82`) → `{ office, projects, services, permissions }`. queryKey `["office_details", slug]`.
- **حقول بتتعرض**:
  - Hero: `video` (HeroVideo autoplay/muted) — **يظهر بس لو** `video` موجود و `permissions.video_projects !== false` (`OfficeDetailsView.tsx:231`).
  - `MainInfoCard`: `name`, `average_rating`, `governorate`, `profile_image` (logo), `bio` (أو نص افتراضي)، و 4 أزرار:
    - المفضلة (toggle)
    - التواصل: `tel:{phone}` + track `call`
    - الموقع: لو `permissions.google_maps_location === false` → "غير متاح"، لو مفيش إحداثيات صالحة (`latitude/longitude` أرقام ≠ 0) → "لا تتوفر إحداثيات"، غير كده لينك `https://www.google.com/maps?q={lat},{lng}` + track `map`
    - الرسائل: `https://wa.me/+2{whats_app || phone}` (من غير تنظيف أرقام) + track `whatsapp`
  - "عن المكتب": `bio`، "خبرة +`experience_years`"، "+`{count_of_projects || projects.length}` مشروع"، "`views_count` مشاهدة" (لو مش undefined).
  - "الخدمات والتخصصات": `services[]` → `name`, `icon`, `color`.
  - "سابقة الأعمال": `projects[]` (نفس حقول المهندس).
  - "الموقع والتواصل": iframe خريطة `https://maps.google.com/maps?q={lat},{lng}&hl=ar&z=14&output=embed` (بنفس شروط الـ permission/الإحداثيات)، `email` (+ track `email`)، `website` (لينك `https://{website}`، **مش متتبَّع**)، `facebook`/`instagram`/`youtube` (+ track).
  - "آراء وتقييمات العملاء": 5.2.19 (`fallbackRating = average_rating`, `fallbackCount = reviews_count`)، ولو `has_rated` يظهر "تم التقييم".
- ⚠️ القائمة بتستخدم `total_visits` والتفاصيل بتعرض `views_count` — وحّدهم.

**Proposed** `GET /api/v1/offices/{slug}`

```json
{
  "data": {
    "slug": "moemen20223",
    "name": "مكتب ... للاستشارات الهندسية",
    "governorate": "الشرقية",
    "bio": "...",
    "experience_years": 12,
    "count_of_projects": 85,
    "views_count": 512,
    "average_rating": 4.8,
    "reviews_count": 47,
    "profile_image": "https://res.cloudinary.com/.../logo.png",
    "video": "https://res.cloudinary.com/.../intro.mp4",
    "phone": "01012345678",
    "whats_app": "01012345678",
    "email": "info@office.com",
    "website": "www.office.com",
    "facebook": "https://facebook.com/...",
    "instagram": "https://instagram.com/...",
    "youtube": null,
    "latitude": 30.5877,
    "longitude": 31.502,
    "selected_plan": "professional",
    "services": [ { "id": 5, "name": "تصميم معماري", "icon": "material-symbols:architecture", "color": "#38bdf8" } ],
    "projects": [ { "id": "uuid", "name": "...", "type": "...", "location": "...", "area": 450, "image": "https://...", "details": { "brief": "...", "video": null, "photos": [], "categories": [] } } ],
    "permissions": { "google_maps_location": true, "video_projects": true },
    "viewer": { "is_favorited": false, "has_rated": true }
  }
}
```

---

#### 5.2.18 تفاصيل المعرض/المورد — `/directory/showrooms-suppliers/[id]` (و `exhibitions`, `materials`) — `features/directory/components/details/ExhibitionDetailsView.tsx` + `features/exhibitions/api/exhibitions.api.ts` (`getExhibitionDetailsBySlug`)

- **Slug**: لو الـ `id` بيبدأ بـ `ex-` أو `mat-` بيتشال (`app/directory/[type]/[id]/page.tsx:39-43`).
- **Query 1**: `public_exhibitions_view` → `select("*")` `.eq("slug", slug)` `.single()`.
- **Query 2**: `services` → `select("*")` `.eq("slug", slug)` (`exhibitions.api.ts:80-84`).
- النتيجة: `{ exhibition: row, projects: row.projects || [], services: dbServices || [], permissions: row.permissions || null }`.
- **Mapping** (`mapDbExhibitionToCard`, `ExhibitionDetailsView.tsx:991-1041`):
  `name = exhibition_name || name`، `region = governorate || "القاهرة"`، `city`، `phone`، `whatsapp = whats_app || whatsapp || phone`، `email`، `website`، `facebook`، `instagram`، `youtube`، `image = logo || profile_image`، `coverImage = banner || coverImage`، `rating = average_rating`، `viewsCount = total_visits || views_count`، `workingHours = open_hours || "٩ص - ٩م"`، `aboutText = bio`، `experienceYears`، `brands` (array) و `brandsCount`، `projectsCount = products.length || count_of_projects`، `reviewsCount`، `lat/lng` (default القاهرة `30.0444, 31.2357` لو فاضية)، `video`، `category = main_category || category || "معرض"`.
  - `products[]` من `row.products`: `{ id: id||name, name: name||title, category: category||type, sub_category: sub_category||category||type, image: image||image_url, price: (cost||price) + " ج.م" }`. لو فاضية → fallback من `row.projects` بنفس الـ mapping (`:1066-1084`).
  - الفئات المعروضة = `main_category` (بأيقونة/لون من `exhibitionCategories`) + أسماء الـ services (Query 2 + `row.services`، dedupe بالاسم `:1092-1105`)، والأيقونة من map ثابت كبير للفئات الفرعية (`:301-569`).
- **حقول بتتعرض**:
  - Hero: `video` لو موجود، غير كده `banner` (أو الـ logo) كخلفية.
  - `MainInfoCard`: `name`، rating (متوسط محسوب من جدول `rating` أو `average_rating`)، "`governorate` - `city`" (أو "مصر")، logo، `main_category`، وأزرار: المفضلة، `tel:{phone}` (+`call`)، الخريطة `https://www.google.com/maps?q={lat},{lng}` (+`map`، **من غير** فحص `permissions`)، واتساب `https://wa.me/2{whatsapp || phone}` (+`whatsapp`).
  - "عن المعرض": `bio`، "خبرة +`experience_years`"، "+`brandsCount` ماركة".
  - "تصنيفات المنتجات"، "أبرز المنتجات" (صورة/اسم/فئة فرعية/سعر)، "شركاء النجاح (العلامات التجارية)" = `brands[]` strings.
  - "آراء وتقييمات العملاء" (5.2.19).
  - "الموقع والتواصل": iframe الخريطة، اسم + "`city` - `governorate`"، `email` (default `info@t3meer.com`، **مش متتبَّع**)، السوشيال (+ track).
  - `website`, `open_hours`, `viewsCount`, `reviewsCount` بيتعملهم map **ومش معروضين**.
- **Visit**: بيتنده `track("visit")` مرتين (`:1047-1051` و `:188-190`) — الـ dedupe في `localStorage` بيمنع التكرار.

**Proposed** `GET /api/v1/exhibitions/{slug}`

```json
{
  "data": {
    "slug": "elnour-ceramics",
    "name": "معرض النور للسيراميك",
    "main_category": "السيراميك والبورسلين",
    "governorate": "القاهرة",
    "city": "مدينة نصر",
    "bio": "...",
    "experience_years": 6,
    "logo": "https://res.cloudinary.com/.../logo.png",
    "banner": "https://res.cloudinary.com/.../banner.jpg",
    "video": null,
    "phone": "01098765432",
    "whats_app": "01098765432",
    "email": "sales@elnour.com",
    "website": "elnour.com",
    "facebook": "https://facebook.com/...",
    "instagram": null,
    "youtube": null,
    "open_hours": "١٠ص - ١٠م",
    "latitude": 30.05,
    "longitude": 31.24,
    "average_rating": 4.3,
    "reviews_count": 9,
    "total_visits": 210,
    "brands": ["Cleopatra", "Royal"],
    "categories": [
      { "name": "السيراميك والبورسلين", "icon": "solar:layers-bold", "color": "#0070d7", "is_main": true },
      { "name": "بورسلين", "icon": null, "color": null, "is_main": false }
    ],
    "products": [
      { "id": 77, "name": "بورسلين 60×60 مط", "category": "السيراميك والبورسلين", "sub_category": "بورسلين", "image": "https://...", "price": 450 }
    ],
    "permissions": { "google_maps_location": true, "video_projects": true },
    "viewer": { "is_favorited": false, "has_rated": false }
  }
}
```
> `price` يرجع رقم والفرونت يضيف " ج.م". مصدر `products` في الـ View غير معروف (غالبًا جدول `products` بالـ `user_id`/`slug` — **استنتاج**).

---

#### 5.2.19 قائمة التقييمات + "قيّمت قبل كده؟" — كل صفحات التفاصيل + الرئيسية — `features/dashboard/customer/api/reviews.api.ts` + `components/ui/ReviewRating.tsx`

- **List**: `useProfessionalReviews(slug)` → `getReviewsBySlug(slug)`: `rating` → `select("*")` `.eq("slug", slug)` `.order("created_at", desc)` — بدون pagination.
  - Mapping: `{ id, target_slug: slug, target_type: type, author_id: user_id, name, rating, message, image, created_at }`.
- **Has rated**: `useHasUserRated(user.id, slug)` → `rating.select("id").eq("user_id").eq("slug").maybeSingle()` (لو حصل error بيرجع `false`).
- **UI**: `ReviewRating` بيحسب في المتصفح: المتوسط (`toFixed(1)`) أو fallback، العدد، وتوزيع 5→1 نجوم (`Math.round(rating) === n`) بنسب مئوية؛ وكل تعليق: `image` أو أيقونة، `name`، الوقت النسبي بالعربي من `created_at` (`date-fns` `formatDistanceToNow`, locale `ar`)، النجوم، `message`. لو مفيش: "لا توجد تعليقات بعد." (أو "لا توجد تقييمات بعد. كن أول من يقيّم هذا المعرض!" في المعارض).

**Proposed** `GET /api/v1/reviews`

| param | type | values | maps to |
|---|---|---|---|
| `target_slug` | string (required) | slug الملف أو `t3meer` | `rating.slug = ?` |
| `page`, `per_page` | int | اختياري (حاليًا كله) | — |

```json
{
  "data": [
    { "id": 301, "name": "محمد سامي", "rating": 5, "message": "تجربة ممتازة", "image": null, "target_type": "office", "created_at": "2026-10-28T14:12:00Z" }
  ],
  "summary": { "average": 4.7, "count": 12, "distribution": { "5": 9, "4": 2, "3": 1, "2": 0, "1": 0 } }
}
```
**Proposed** `GET /api/v1/reviews/status?target_slug=` (auth:sanctum) → `{ "data": { "has_rated": true } }` (أو ضمن `viewer.has_rated` في endpoints التفاصيل).

---

#### 5.2.20 تفاصيل المشروع — `/projects/[id]` — `features/projects/components/ProjectDetailsView.tsx` + `projects.api.ts` (`getProjectById`)

- **Query 1**: `useProjectDetails(id)` → `projects` → `select('*')` `.eq('id', id)` `.single()` (`projects.api.ts:26-35`) — **من غير أي شرط ظهور** (status/plan).
- **Query 2 (صاحب المشروع)**: لو `user_type === "engineer"` → `useEngineerDetails(project.slug)` (5.2.16 كامل)؛ لو `"office"` → `useOfficeDetails(project.slug)` (5.2.17 كامل). بيستخدم منهم: `whats_app`, `phone`، و `projects` (باقي أعمال المنفذ مع استبعاد المشروع الحالي).
- **حقول المشروع المعروضة**: `details.video` (لو YouTube → embed `https://www.youtube.com/embed/{id}`، غير كده `<video>`/HeroVideo)، `name`، `location`، `image` (logo)، `type` (category + "القسم الرئيسي")، `details.brief` (أو نص مولّد)، `area` ("م²")، `details.categories[]` ("ماتم انجازه في هذا المشروع")، معرض صور = `image` + `details.photos[]` (dedupe) مع lightbox.
- **أزرار**: "المنفذ" → `/directory/{engineers|offices}/{slug}`؛ هاتف `tel:{ownerPhone}` (engineer: `phone || whats_app`، office: `phone`)؛ واتساب `https://wa.me/+2{digits(whats_app || phone)}`.
- **Tracking**: `visit` عند التحميل و `call`/`whatsapp` عند الضغط — على **slug صاحب المشروع** و `type = project.user_type` و `user_id` لو مسجل (`ProjectDetailsView.tsx:101-105`, `:125-129`).
- عمود `projects.views` موجود في الـ interface بس **مش بيتزوّد** في أي مكان عام.

**Proposed** `GET /api/v1/projects/{id}`

```json
{
  "data": {
    "id": "8f1c...-uuid",
    "name": "تشطيب فيلا سكنية",
    "location": "التجمع الخامس",
    "area": 450,
    "type": "تصميم داخلي",
    "image": "https://res.cloudinary.com/.../main.jpg",
    "details": { "brief": "...", "video": "https://youtu.be/abcdefghijk", "photos": ["https://.../1.jpg"], "categories": ["تصميم داخلي", "تشطيبات"] },
    "created_at": "2026-05-08T12:52:08Z",
    "owner": { "user_type": "office", "slug": "moemen20223", "name": "مكتب ...", "phone": "01012345678", "whats_app": "01012345678" },
    "other_projects": [
      { "id": "uuid-2", "name": "...", "type": "...", "location": "...", "area": 200, "image": "https://...", "has_details": false }
    ]
  }
}
```
> يُفضّل تطبيق نفس شروط الظهور بتاعة ملف المالك (لو الملف مخفي → 404).

---

#### 5.2.21 الـ Layout العام (Navbar / Footer / Auth) — كل الصفحات — `components/layouts/Navbar.tsx`, `Footer.tsx`, `components/providers/AuthInitializer.tsx`, `app/layout.tsx`

- **AuthInitializer**: `useCurrentUser()` → `supabase.auth.getUser()` في كل تحميل للموقع → Zustand store (`user-storage`، persisted).
- **Navbar** (لو فيه user):
  - `userType = user_metadata.user_type || .type || .account_type || pathname.split("/")[2] || "engineer"` (`Navbar.tsx:65-71`).
  - `useQueryUserProfile(user.id, userType)` → `getUserProfile` (`home.api.ts:38-59`): `select("*")` من `offices` | `exhibitions` | `materials` | `engineers` (أي نوع تاني بما فيهم `customer` → `engineers`) `.eq("user_id")` `.maybeSingle()` — بيستخدم `name`, `profile_image`, `image`, `major` (`architectural`→"معماري"، `civil`→"مدني").
  - `useUserStatus(user.id)` → `users` → `select("status, rejection_reason")` (`dashboard.api.ts:33-51`) — badge لغير العملاء: `active` "حساب مفعل"، `pending` "قيد المراجعة"، غير كده "غير مفعل"، ولو `reject` + سبب → "السبب: …".
  - تغيير الصورة: `<input accept="image/*">` → `uploadToCloudinary(file, "profiles")` → `updateProfileImage` = `update {profile_image}` على جدول النوع `.eq("user_id")` + `auth.updateUser({ data: { profile_image, image } })`.
  - Logout: `supabase.auth.signOut()` + مسح cookies `sb-access-token`/`sb-refresh-token` + `router.push("/")`.
  - روابط ثابتة: الرئيسية، من نحن، المكاتب الهندسية، مهندسين مستقلين، معارض وموردين (badge "جديد")، دخول، حساب جديد + روابط الداشبورد من `features/dashboard/static.ts`. **مفيش بحث عام ولا إشعارات** في الـ Navbar.
- **Footer**: روابط ثابتة فقط (`/about`, `/terms`, `/privacy`, `/contact`, `/directory/engineers`, `/directory/offices`, `/directory/exhibitions`, `/directory/materials`) + سوشيال/إيميل/واتساب ثابتين — **مفيش fetch**.
- **Analytics scripts**: Meta Pixel (`fbq('init', NEXT_PUBLIC_META_PIXEL_ID)` + `PageView`) و Google Analytics `G-KC95QJL6GG` — browser-only، مش بيكلموا الباك.

**Proposed** (تفاصيلها في قسم Auth/Dashboard): `GET /api/v1/auth/me` → `{ user: {id, email, user_type, current_step, status, rejection_reason, selected_plan}, profile: {name, profile_image, major, slug} }`، `POST /api/v1/me/avatar` (`profile_image` URL أو multipart)، `POST /api/v1/auth/logout`.

---

#### 5.2.22 الصفحات الثابتة — `/about`, `/privacy`, `/terms`, `/contact` (معلومات التواصل)، Static helpers

- `AboutUs`, `Privacy`, `Terms`: محتوى ثابت 100% (لا Supabase، لا hooks بيانات) — مؤكَّد بالـ grep.
- `ContactUs`: بيانات التواصل ثابتة (`t3meer1110@gmail.com`، واتساب `+201034865483`، "القاهرة، جمهورية مصر العربية"، Facebook/Instagram/TikTok).
- `FreeConsultationSection` (الرئيسية): لينك واتساب فقط `https://wa.me/201034865483?text=` + رسالة "مرحباً، أرغب في الحصول على استشارة مجانية بخصوص مشروعي" — **مفيش تتبع ولا تخزين**.
- `app/not-found.tsx`, `app/error.tsx`, `app/loading.tsx`, `app/directory/loading.tsx`: UI فقط.
- **PWA**: `app/manifest.ts` ثابت + `sw.js` + زر التثبيت بيستخدم `localStorage` (`t3meer_pwa_installed`) — مفيش API.
- Static data لسه في الكود بس **مش مستخدمة** (mock): `features/offices/static.ts`, `features/engineers/static.ts`, `features/exhibitions/static.ts`, `features/materials/static.ts`, `features/showrooms-suppliers/static.ts`. و `MaterialDetailsView.tsx` component ميت (مش متركّب في أي route) وبياخد props ثابتة.

---

### 5.3 النماذج والعمليات (Forms / Mutations)

#### 5.3.1 إضافة تقييم لملف مهني (مهندس / مكتب / معرض) — `features/directory/components/details/AddRatingModal.tsx` + `EngineerDetailsView.tsx:683-714` / `OfficeDetailsView.tsx:722-753` / `ExhibitionDetailsView.tsx:909-940`

**شروط الفتح** (قبل الـ modal): لازم `isAuthenticated` وإلا "تسجيل الدخول مطلوب"؛ ولازم `user_metadata.user_type === "customer"` وإلا "عذراً، إضافة التقييمات متاح للعملاء فقط."؛ والزر مش بيظهر لو `has_rated`.

**حقول الفورم**

| key / DB column | الاسم في الواجهة | type | required | validation | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `rating` | "ما هو تقييمك؟" (5 نجوم) | integer | ✅ | لو `0` → "يرجى اختيار التقييم بالنجوم أولاً" | `1`–`5` | `rating.rating` |
| `message` | "رأيك بالتفصيل" (textarea, placeholder "اكتب تجربتك هنا...") | string | ✅ (HTML `required` فقط) | مفيش trim/طول | نص حر | `rating.message` |
| `name` (hidden) | معاينة "سيتم نشر تقييمك بهذا الاسم وصفتك كـ {عميل/مهندس/…}" | string | ✅ | مشتق: `user_metadata.full_name \|\| name \|\| office_name \|\| user_name \|\| email.split("@")[0] \|\| "مستخدم"` (`AddRatingModal.tsx:43-48`) | — | `rating.name` |
| `target_slug` (hidden) | — | string | ✅ | slug الصفحة | — | `rating.slug` |
| `target_type` (hidden) | — | string | ✅ | ثابت حسب الصفحة | `engineer`, `office`, `exhibition` | `rating.type` |
| `author_id` (hidden) | — | uuid | ✅ | `user.id` | — | `rating.user_id` |
| `image` (hidden) | صورة المعاينة | string\|null | ❌ | **مش بيتبعت** في تقييمات الملفات (undefined → null) | — | `rating.image` |
| `photo` | (موجود في `RatingData` بس دايمًا `null`) | File | ❌ | مش بيترفع | — | — |

**Supabase ops** (`reviews.api.ts:29-52`): (1) `rating.select("id").eq("user_id").eq("slug").maybeSingle()` → لو موجود throw "لقد قمت بتقييم هذا الملف مسبقاً. لا يمكنك التقييم أكثر من مرة."؛ (2) `rating.insert({ slug, type, user_id, name, rating, message, image }).select().single()`. بعدها invalidate: `reviews/{slug}`, `has_rated`, `dashboard_stats`, `customer_reviews`. نجاح: "تمت إضافة تقييمك بنجاح. شكراً لك!".

**Proposed** `POST /api/v1/reviews` (auth:sanctum)

```json
{ "target_slug": "moemen20223", "target_type": "office", "rating": 5, "message": "تعامل محترم والتزام بالمواعيد" }
```
```php
$data = $request->validate([
    'target_slug' => ['required', 'string', 'max:255'],
    'target_type' => ['required', Rule::in(['engineer', 'office', 'exhibition', 'platform'])],
    'rating'      => ['required', 'integer', 'between:1,5'],
    'message'     => ['required', 'string', 'max:2000'],
]);
// target_type=platform ⇔ target_slug='t3meer' (5.3.2)
// 1) تأكد إن target_slug موجود في الجدول المناسب (engineers/offices/exhibitions) وظاهر
// 2) Policy: لو target_type != platform ⇒ auth()->user()->user_type === 'customer' وإلا 403 "عذراً، إضافة التقييمات متاح للعملاء فقط."
// 3) Unique(user_id, slug) ⇒ 422 "لقد قمت بتقييم هذا الملف مسبقاً. لا يمكنك التقييم أكثر من مرة."
// 4) user_id = auth()->id(); name = اسم المستخدم من profile/users; image = صورة المستخدم (لو عايزين نوحّد مع تقييم المنصة)
// 5) Recalculate average_rating / reviews_count للهدف (الـ View بيحسبهم حاليًا)
```
Response `201`: `{ "data": { "id": 301, "name": "...", "rating": 5, "message": "...", "image": null, "target_type": "office", "created_at": "..." }, "message": "تمت إضافة تقييمك بنجاح. شكراً لك!" }`

> يفضل unique index على `rating(user_id, slug)` (حاليًا الفحص client-side فقط).

---

#### 5.3.2 تقييم المنصة ("قيم تجربتك") — `/` — `features/testimonials/components/TestimonialsSection.tsx:71-91`, `:136-186`

- **شروط**: لازم يكون مسجل (وإلا Swal بزر "تسجيل الدخول" → `/login`)؛ **أي نوع مستخدم** مسموح له (عكس صفحات الملفات). الزر مخفي لو `has_rated` على `t3meer`.
- نفس `AddRatingModal` (نفس الحقول الظاهرة).

**حقول الفورم**

| key / DB column | الاسم في الواجهة | type | required | validation | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `rating` | النجوم | integer | ✅ | ≠ 0 | 1–5 | `rating.rating` |
| `message` | "رأيك بالتفصيل" | string | ✅ | HTML required | — | `rating.message` |
| `name` (hidden) | — | string | ✅ | نفس اشتقاق 5.3.1 | — | `rating.name` |
| `target_slug` (hidden) | — | string | ✅ | ثابت | `t3meer` | `rating.slug` |
| `target_type` (hidden) | — | string | ✅ | **نوع المُقيِّم**: `user_metadata.user_type`، لو فاضي → `users.select("user_type").eq("id").maybeSingle()`، لو فاضي → `"customer"` | `customer`, `engineer`, `office`, `exhibition`, (`supplier`/`admin` لو موجودين) | `rating.type` |
| `author_id` (hidden) | — | uuid | ✅ | `user.id` | — | `rating.user_id` |
| `image` (hidden) | — | string | ❌ | `user_metadata.profile_image \|\| image \|\| avatar_url` | URL | `rating.image` |

**Ops**: نفس 5.3.1 (+ قراءة `users.user_type` كـ fallback). نجاح: "شكراً لك! تم إرسال تقييمك بنجاح."

**Proposed**: نفس `POST /api/v1/reviews` بـ `{ "target_slug": "t3meer", "target_type": "platform", "rating": 5, "message": "..." }` — والسيرفر يخزّن `type = auth()->user()->user_type` و `image` من بروفايل المستخدم (للحفاظ على عرض `role` في الشهادات). يُفضّل عمود منفصل `reviewer_type` بدل إعادة استخدام `type`.

---

#### 5.3.3 تبديل المفضلة (Favorite toggle) — الدليل + صفحات التفاصيل — `components/ui/FavoriteSpan.tsx` + `features/dashboard/customer/api/favorites.api.ts:73-85`

**شروط** (مختلفة بين المكانين):
- `FavoriteSpan` (كروت الدليل) (`FavoriteSpan.tsx:47-73`): لازم مسجل؛ لو `type ∈ {engineer, office}` لازم `user_type === "customer"` ("عذراً، إضافة المهندسين والمكاتب للمفضلة متاح للعملاء فقط.")؛ **المعارض/المواد مسموحة لأي مستخدم مسجل**. لو أي حقل من `userId/type/targetName/targetSlug` ناقص → مفيش حاجة تحصل.
- صفحات التفاصيل (مهندس/مكتب/معرض): لازم مسجل ("يجب تسجيل الدخول كعميل…") **و** `customer` لكل الأنواع ("ميزة المفضلة متاحة للعملاء فقط").

**حقول الفورم** (كلها hidden — مفيش فورم مرئي)

| key / DB column | الاسم في الواجهة | type | required | validation | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `user_id` | — | uuid | ✅ | `user.id` | — | `favorites.user_id` |
| `slug` | — | string | ✅ | slug الهدف (للمعارض slug خام من غير `ex-`) | — | `favorites.slug` |
| `type` | — | string | ✅ | — | `engineer`, `office`, `exhibition`, `material` (عمليًا الكود مش بيبعت `material` لأن `sourceType` دايمًا `exhibition`) | `favorites.type` |
| `name` | — | string | ✅ | اسم الهدف (snapshot) | — | `favorites.name` |
| `image` | — | string\|null | ❌ | الكارت: `image` لو string وإلا `null`؛ التفاصيل: `profile_image \|\| ""` (معرض: logo أو `""`) | URL | `favorites.image` |
| `phone` | — | string\|null | ❌ | الكارت: `phone \|\| null`؛ التفاصيل: `phone \|\| ""` | — | `favorites.phone` |

**Supabase ops**: `isFavorited` (`select id where user_id, slug maybeSingle`) → لو موجود: `favorites.delete().eq("user_id").eq("slug")` → `{action:"removed"}`؛ غير كده: `favorites.insert(row).select().single()` → `{action:"added"}`. Invalidate: `user_favorites`, `is_favorited/{user}/{slug}`, `dashboard_stats`. Toast: "تمت الإضافة للمفضلة" / "تمت الإزالة من المفضلة" (أو "تم الحذف من المفضلة" في التفاصيل).

**Proposed** `POST /api/v1/favorites/toggle` (auth:sanctum)

```json
{ "target_type": "engineer", "target_slug": "ahmed-ali-2031" }
```
```php
$data = $request->validate([
    'target_type' => ['required', Rule::in(['engineer', 'office', 'exhibition'])],   // material → exhibition (0.5)
    'target_slug' => ['required', 'string', 'max:255'],
]);
// Policy: user_type === 'customer' (توحيد السلوك؛ أو استثناء exhibition زي FavoriteSpan لو مطلوب)
// السيرفر يجيب name/image/phone من جدول الهدف (snapshot) بدل ما ياخدهم من الـ body
// user_id = auth()->id(); unique(user_id, slug)
```
Response `200`: `{ "data": { "action": "added", "is_favorited": true }, "message": "تمت الإضافة للمفضلة" }`
(بدائل REST: `POST /api/v1/favorites` و `DELETE /api/v1/favorites/{slug}` — قائمة المفضلة نفسها في قسم داشبورد العميل.)

---

#### 5.3.4 نموذج "اتصل بنا" — `/contact` — `features/contact/components/ContactUs.tsx:19-53`

- **الوضع الحالي**: **محاكاة فقط** — `setTimeout(1500)` ثم رسالة "تم الإرسال بنجاح!" وتفريغ الفورم. **مفيش أي حفظ ولا إيميل ولا WhatsApp/mailto** للرسالة (لينكات mailto/واتساب موجودة كمعلومات تواصل منفصلة).

**حقول الفورم**

| key / DB column | الاسم في الواجهة | type | required | validation | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `name` | "الاسم الكامل" (placeholder "أدخل اسمك الكامل") | string | ✅ | HTML required | — | `contact_messages.name` (جديد) |
| `email` | "البريد الإلكتروني" (placeholder `example@t3meer.com`) | email | ✅ | HTML `type=email` + required | — | `contact_messages.email` |
| `phone` | "رقم الهاتف (اختياري)" (placeholder `01xxxxxxxxx`) | tel | ❌ | لو مكتوب: `isValidEgyptianPhone` — تحويل الأرقام العربية + إزالة المسافات ثم `/^(?:\+20\|0020)0?1[0125]\d{8}$\|^01[0125]\d{8}$/` ؛ الخطأ: "رقم الهاتف غير صالح، يجب أن يكون رقم مصري صحيح" | — | `contact_messages.phone` |
| `subject` | "الموضوع" (placeholder "سبب التواصل") | string | ✅ | HTML required | — | `contact_messages.subject` |
| `message` | "رسالتك" (textarea 6 rows) | string | ✅ | HTML required | — | `contact_messages.message` |

**Proposed (جديد)** `POST /api/v1/contact-messages` (public + `throttle:5,1` + اختياري captcha)

```json
{ "name": "أحمد محمد", "email": "ahmed@example.com", "phone": "01012345678", "subject": "استفسار عن الباقات", "message": "..." }
```
```php
$request->merge(['phone' => $request->filled('phone') ? preg_replace('/\s+/', '', strtr($request->phone, ['٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9'])) : null]);
$data = $request->validate([
    'name'    => ['required', 'string', 'max:255'],
    'email'   => ['required', 'email', 'max:255'],
    'phone'   => ['nullable', 'regex:/^(?:\+20|0020)0?1[0125]\d{8}$|^01[0125]\d{8}$/'],
    'subject' => ['required', 'string', 'max:255'],
    'message' => ['required', 'string', 'max:5000'],
], ['phone.regex' => 'رقم الهاتف غير صالح، يجب أن يكون رقم مصري صحيح']);
// user_id = auth()->id() لو موجود؛ status = 'new'؛ + إشعار إيميل للإدارة (t3meer1110@gmail.com)
```
Response `201`: `{ "message": "شكراً لتواصلك معنا، سنقوم بالرد عليك في أقرب وقت ممكن." }`

---

#### 5.3.5 تتبع تفاعلات الملفات (visit / whatsapp / call / …) — `lib/track-metric.ts:14-96` (`trackMetricClick`)

**أماكن الاستدعاء**

| المكان | الـ metrics | `type` المبعوت | `user_id` |
|---|---|---|---|
| `EngineerDetailsView` | `visit` (mount)، `whatsapp` (زرين)، `call`، `email`، `facebook`، `instagram`، `youtube` | `engineer` | ✅ لو مسجل |
| `OfficeDetailsView` | `visit`، `call`، `map`، `whatsapp`، `email`، `facebook`، `instagram`، `youtube` (`website` مش متتبَّع) | `office` | ✅ |
| `ExhibitionDetailsView` | `visit` (×2 مع dedupe)، `call`، `map`، `whatsapp`، `facebook`، `instagram`، `youtube` (`email` مش متتبَّع) | `exhibition` | ✅ |
| `ProjectDetailsView` | `visit`، `call`، `whatsapp` — على **slug صاحب المشروع** | `project.user_type` (`engineer`/`office`) | ✅ |
| `HeroSlider` (ضغطة بانر) | `visit` — لو اللينك `/directory/{type}/{slug}` | `type` بعد شيل `s` الأخيرة: `engineer`, `office`, `exhibition`, `material`، و `showrooms-supplier` (⚠️ bug من `showrooms-suppliers`) | ❌ مش بيتبعت |
| `MaterialDetailsView` (ميت) | `visit`, `call`, `map`, `whatsapp`, `facebook`, `instagram`, `youtube` | `material` | ✅ |

**المنطق الحالي**:
1. لو `visit`: dedupe في `localStorage` بالمفتاح `track_visit_{slug}_{YYYY-MM-DD}` (تاريخ UTC) — مرة لكل جهاز/slug/يوم. باقي الـ metrics مش عليها dedupe.
2. `stats.select("id, count").eq("slug").eq("metric_type").order("created_at", desc).limit(1)`.
3. لو فيه صف: `update { count: (count>0?count:1) + 1, date: today, created_at: now(), type?, user_id? }` على الـ `id` ⇒ **صف واحد تراكمي لكل (slug, metric_type)**، والتاريخ/المستخدم بيتكتب عليهم آخر قيمة.
4. لو مفيش (أو الـ update فشل): `insert { metric_type, count: 1, date: today, slug, user_id?, type? }`.
- ⚠️ race condition (read-then-write من المتصفح)، وأي حد بالـ anon key يقدر يكتب أرقام. الـ dashboards بتقرا `stats` عن طريق RPCs `get_limited_stats`/`get_all_stats` (قسم الداشبورد).

**حقول الطلب** (hidden — مفيش فورم)

| key / DB column | الاسم في الواجهة | type | required | validation | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `slug` | — | string | ✅ | slug الملف | — | `stats.slug` |
| `metric_type` | — | enum | ✅ | — | `visit`, `whatsapp`, `call`, `email`, `map`, `facebook`, `instagram`, `youtube` | `stats.metric_type` (enum `metric_type`) |
| `type` | — | string | ❌ | — | `engineer`, `office`, `exhibition`, `material` (+ `showrooms-supplier` الحالي bug) | `stats.type` |
| `user_id` | — | uuid | ❌ | المستخدم الحالي | — | `stats.user_id` |
| (server) | — | date | ✅ | today | — | `stats.date` |
| (server) | — | int | ✅ | +1 | — | `stats.count` |

**Proposed** `POST /api/v1/track/metric` (public، optional auth، `throttle:60,1`) → `204 No Content`

```json
{ "slug": "moemen20223", "metric_type": "whatsapp", "type": "office", "device_id": "c0a8f1e2-..." }
```
```php
$data = $request->validate([
    'slug'        => ['required', 'string', 'max:255'],
    'metric_type' => ['required', Rule::in(['visit','whatsapp','call','email','map','facebook','instagram','youtube'])],
    'type'        => ['nullable', Rule::in(['engineer','office','exhibition'])],   // material → exhibition (0.5)
    'device_id'   => ['nullable', 'string', 'max:64'],   // لعمل dedupe للزيارات server-side
]);
// user_id = auth('sanctum')->id() (اختياري)
// visit: dedupe (slug + device_id|ip + date) ثم increment
// للحفاظ على السلوك الحالي: UPSERT على (slug, metric_type) count = count + 1, date = today
// يُنصح: جدول events (صف لكل حدث) + aggregate يومي بدل الكتابة فوق created_at
```
> لو `type=showrooms-supplier` جاي من الفرونت القديم، اعمله map لـ `exhibition`.

---

#### 5.3.6 تتبع تحليلات المنصة (page visits + فلاتر الدليل) — `lib/track-metric.ts:98-110` (`trackAnalytics`) + `features/analysis/components/TrackPageVisit.tsx` + `DirectoryListing.tsx:428-447` + `app/api/analytics/track/route.ts`

- **Op**: `supabase.rpc("increment_analytics", { p_stat_type, p_stat_key })` → بيزوّد `analytics_counts.total_count` للزوج (`stat_type`, key) (اسم عمود الـ key مش ظاهر في الكود — **استنتاج** `stat_key`).
- **الأحداث**:

| `p_stat_type` | `p_stat_key` | المصدر | dedupe |
|---|---|---|---|
| `page` | `home` | `TrackPageVisit` في `/` (default props) | `localStorage` `track_analytics_page_home_{YYYY-MM-DD}` + deferred (`requestIdleCallback` أو `setTimeout 2000ms`) |
| `page` | `engineers` \| `offices` \| `showrooms-suppliers` \| `exhibitions` \| `materials` | `TrackPageVisit type="page" name={type}` في `/directory/[type]` | نفس المفتاح بالـ name |
| `service` | label الفئة المختارة (مثلًا `تصميم معماري`، `السيراميك والبورسلين`) | تغيير فلتر الفئة (لو مش `""`/`الكل`) | لا |
| `governorate` | اسم المحافظة (مثلًا `القاهرة`) | تغيير فلتر المحافظة | لا |
| `sort` | `nearMe` \| `experience` \| `bestViewers` \| `bestRating` | تغيير الترتيب (لو مش `الكل`) | لا |

- `/api/analytics/track` (POST `{type, name}` → نفس الـ RPC، 400 لو ناقص) موجود **ومش مستخدم**.

**حقول الطلب** (hidden)

| key / DB column | الاسم في الواجهة | type | required | validation | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `stat_type` (`p_stat_type`) | — | string | ✅ | — | `page`, `service`, `governorate`, `sort` | `analytics_counts.stat_type` |
| `stat_key` (`p_stat_key`) | — | string | ✅ | — | الجدول أعلاه | `analytics_counts.<key column>` |

**Proposed** `POST /api/v1/track/analytics` (public، `throttle:120,1`) → `204`

```json
{ "stat_type": "governorate", "stat_key": "القاهرة" }
```
```php
$data = $request->validate([
    'stat_type' => ['required', Rule::in(['page', 'service', 'governorate', 'sort'])],
    'stat_key'  => ['required', 'string', 'max:255'],
]);
// AnalyticsCount::query()->upsert([...], ['stat_type','stat_key'], ['total_count' => DB::raw('total_count + 1')])
// (يُفضّل whitelisting للـ stat_key حسب النوع: الصفحات/الفئات/المحافظات/مفاتيح الترتيب من 5.2.14)
```

---

#### 5.3.7 Meta Conversions API — `app/api/meta/event/route.ts`, `app/api/meta/purchase/route.ts`, `components/analytics/MetaPixel.tsx`, `src/lib/pixel.ts`

- الموقع العام نفسه **مش بيبعت** أي `trackMetaEvent` — بس Pixel `PageView` من المتصفح في الـ root layout. `trackMetaEvent` بيتنده من: signup (`CompleteRegistration`)، payment (`AddPaymentInfo`, `Subscribe`)، admin (قسم Auth/Payment/Admin).
- `POST /api/meta/event` body: `{ eventName, params, eventId, userId, email, phone, testEventCode }` + بياخد IP (`x-forwarded-for`/`x-real-ip`)، `user-agent`، `referer` → `MetaService.sendGenericMetaEvent` (hash لـ email/phone/external_id).
- `POST /api/meta/purchase` body: `{ subscriptionId, userId, email, phone, price, currency (default EGP), isRenewal, fbp, fbc, testEventCode }`؛ لو ناقص بيقرا `subscriptions.select("*, users(id, email, phone)").eq("id")` و/أو `users.select("id, email, phone")`؛ 400 لو `subscriptionId/userId/email` ناقصين؛ cookies `_fbp`/`_fbc`.
- **Proposed**: `POST /api/v1/meta/events` و `POST /api/v1/meta/purchase` (نفس الـ bodies) — أو الأفضل: Laravel يبعت Purchase server-side تلقائيًا عند تفعيل الاشتراك. (تفاصيل في قسم الدفع.)

---

#### 5.3.8 عمليات الـ Navbar (للمستخدم المسجل) — `components/layouts/Navbar.tsx:99-135`

| key / DB column | الاسم في الواجهة | type | required | validation | allowed values | → table.column |
|---|---|---|---|---|---|---|
| `file` | الضغط على الصورة الشخصية | image file | ✅ | `accept="image/*"` فقط (مفيش حد حجم) | — | Cloudinary (folder `profiles`, preset `t3meer_images`) |
| `profile_image` | — | URL | ✅ | ناتج الرفع | — | `{engineers\|offices\|exhibitions\|materials}.profile_image` + `auth.user_metadata.profile_image`, `.image` |

- Logout: `supabase.auth.signOut()`.
- **Proposed**: `POST /api/v1/me/avatar` (`{ "profile_image": "https://res.cloudinary.com/..." }` أو multipart `file` → `image|max:5120`) و `POST /api/v1/auth/logout` — موثقين في قسم Auth/Dashboard.

---

#### 5.3.9 أزرار مفيهاش Backend

- واتساب/هاتف/إيميل/سوشيال في صفحات التفاصيل: لينكات مباشرة (`wa.me`, `tel:`, `mailto:`) + تتبع 5.3.5 فقط.
- "احصل على استشارة مجانية": لينك واتساب ثابت (مفيش تسجيل).
- **مفيش زر "طلب خدمة"** في الصفحات العامة — طلب الخدمة موجود في `/customer/setup` وداشبورد العميل (قسم العميل).
- مشاركة المقال: روابط Facebook/Twitter/WhatsApp + نسخ الرابط.

---

### 5.4 Tables touched (للدمج في قسم الـ Schema)

| table / view | Read (columns) | Write (columns) | المصدر |
|---|---|---|---|
| `public_engineers_view` | list: `name, slug, phone, major, email, governorate, experience_years, profile_image, average_rating, total_visits, selected_plan, services, latitude, longitude`؛ details `*`: + `whats_app, bio, skills, facebook, instagram, youtube, reviews_count, projects (json), services (json), permissions (json)`؛ count (head) | — | 5.2.3, 5.2.6, 5.2.11, 5.2.16, 5.2.20 |
| `public_offices_view` | list: `office_name, slug, phone, governorate, experience_years, profile_image, average_rating, total_visits, selected_plan, services, latitude, longitude, count_of_projects`؛ details `*`: + `name, bio, email, website, whats_app, facebook, instagram, youtube, video, views_count, reviews_count, projects, permissions{video_projects, google_maps_location}`؛ count (head) | — | 5.2.2, 5.2.6, 5.2.12, 5.2.17, 5.2.20 |
| `public_exhibitions_view` | `*`: `slug, exhibition_name, name, main_category, category, services, governorate, city, phone, whats_app, whatsapp, email, website, facebook, instagram, youtube, logo, profile_image, banner, coverImage, average_rating, total_visits, views_count, open_hours, bio, experience_years, brands (json array), products (json array: id,name,title,category,type,sub_category,image,image_url,cost,price), projects, count_of_projects, reviews_count, latitude, longitude, video, selected_plan, permissions` | — | 5.2.13, 5.2.18 |
| `premium_active_view` | `*`: `id, user_id, name, location, area, type, image, slug, user_type, user_name, details{brief,video,photos,categories}, created_at` (order `created_at` desc, limit 100) | — | 5.2.5 |
| `marketing_active_users` | `banner{title, description, image}, slug, user_type` + filters `plan_type, user_type, banner not null` | — | 5.2.1, 5.2.10 |
| `projects` | `*` by `id`: `id, user_id, name, location, area, type, image, slug, user_type, user_name, views, details, created_at` | — | 5.2.20 |
| `services` | `*` by `slug`: `id, user_id, name, icon, color, slug, created_at` | — | 5.2.18 |
| `materials_prices` | `*`: `category, item_name, unit, price, change_percent` | — | 5.2.7 |
| `rating` | `*` by `slug` (order `created_at` desc): `id, slug, type, user_id, name, rating, message, image, created_at`؛ `id` by (`user_id`,`slug`) | insert `slug, type, user_id, name, rating, message, image` | 5.2.8, 5.2.19, 5.3.1, 5.3.2 |
| `favorites` | `id` by (`user_id`,`slug`) | insert `user_id, name, image, type, slug, phone`؛ delete by (`user_id`,`slug`) | 5.2.15, 5.3.3 |
| `stats` | `id, count` by (`slug`,`metric_type`) latest؛ `slug, count` by `slug IN (...)`؛ count(head) where `metric_type='visit'` | insert `metric_type, count, date, slug, user_id, type`؛ update `count, date, created_at, type, user_id` | 5.2.1, 5.2.6, 5.2.10, 5.3.5 |
| `analytics_counts` | `total_count` where `stat_type='page'` | via RPC `increment_analytics(p_stat_type, p_stat_key)` | 5.2.6, 5.3.6 |
| `users` | `user_type` by `id` (تقييم المنصة)؛ `status, rejection_reason` by `id` (Navbar) | — | 5.3.2, 5.2.21 |
| `engineers` / `offices` / `exhibitions` / `materials` | `*` by `user_id` (Navbar: `name, profile_image, image, major`) | update `profile_image` (Navbar avatar) | 5.2.21, 5.3.8 |
| `user_permissions` | embedded في الـ views كـ `permissions` (`plan_type, google_maps_location, whatsapp_tracking, call_tracking, detailed_analytics, max_visits, max_projects, max_services, video_projects`) | — | 5.2.16–5.2.18 |
| `subscriptions` (Meta purchase) | `*, users(id, email, phone)` by `id` (`plan_price`, `amount`, `is_renewal`, `user_id`) | — | 5.3.7 |
| `auth.users` (Supabase) | `getUser()` | `updateUser({data:{profile_image,image}})`، `signOut()` | 5.2.21, 5.3.8 |
| ملف `data/homepage-display-settings.json` | `offices/engineers × premium/professional/basic/free` | (أدمن) | 5.2.4 → جدول `settings` مقترح |
| `contact_messages` (**جديد**) | — | `name, email, phone, subject, message, user_id, status` | 5.3.4 |
| `articles` (**اختياري جديد**) | `id, title, description, content, image, published_at, read_time` | — | 5.2.9 |

**RPCs المستخدمة في الموقع العام**: `increment_analytics(p_stat_type text, p_stat_key text)`.

**أسماء الأحداث المتتبَّعة**

- `stats.metric_type` (enum): `visit`, `whatsapp`, `call`, `email`, `map`, `facebook`, `instagram`, `youtube`.
- `stats.type`: `engineer`, `office`, `exhibition`, `material`، و `showrooms-supplier` (bug من `HeroSlider`).
- `analytics_counts`: (`page`, `home`)، (`page`, `engineers`|`offices`|`showrooms-suppliers`|`exhibitions`|`materials`)، (`service`, <label الفئة>)، (`governorate`, <اسم المحافظة>)، (`sort`, `nearMe`|`experience`|`bestViewers`|`bestRating`).
- Meta Pixel (browser): `PageView` (كل الصفحات). Meta CAPI من خارج الموقع العام: `CompleteRegistration`, `AddPaymentInfo`, `Subscribe`, Purchase.
- Google Analytics: `G-KC95QJL6GG` page views (browser فقط).
- مفاتيح `localStorage` المرتبطة: `track_visit_{slug}_{YYYY-MM-DD}`، `track_analytics_{type}_{name}_{YYYY-MM-DD}`، `t3meer_pwa_installed`، `user-storage` (Zustand session).

**Caching الحالي (للـ Cache-Control في Laravel)**: الرئيسية ISR 300s + `homepage_data_v3` 300s؛ بانرات الرئيسية 60s؛ قوائم الدليل وبانراته 120s؛ React Query staleTime 5 دقائق (الأسعار ساعة).

---

## 6. فهرس الـ Endpoints المقترحة (ملخص موحد)

> ده فهرس لكل الـ endpoints المقترحة في الأقسام 1–5. تفاصيل كل endpoint (الـ body، الـ validation rules، والـ response اللي الواجهة بتستخدمه) موجودة في القسم المذكور جنبه. كلها **مقترحة** ومفيش منها حاجة متنفذة أو متجربة.
> **Auth:** `Public` = من غير توكن · `User` = `auth:sanctum` · `Owner` = صاحب المورد فقط · `Admin` = `role:admin`.

### 6.1 Auth & Account

| Method | Path | Auth | الوظيفة | القسم |
|---|---|---|---|---|
| POST | `/api/v1/auth/register` | Public | تسجيل حساب (name, email, phone, password, user_type) | 2 |
| POST | `/api/v1/auth/login` | Public | تسجيل دخول → token + user | 2 |
| POST | `/api/v1/auth/logout` | User | تسجيل خروج | 2, 3 |
| GET | `/api/v1/auth/me` | User | بيانات المستخدم + `user_type` + `current_step` + `status` + `selected_plan` + `validation_status` + `is_verified` + `next_route` + بيانات البروفايل الأساسية | 2, 2.V, 3, 5 |
| GET | `/api/v1/auth/email/verify/{id}/{hash}` | Public (signed) | تأكيد البريد | 2 |
| POST | `/api/v1/auth/email/resend` | Public/User | إعادة إرسال رسالة التأكيد | 2 |
| PATCH | `/api/v1/auth/email` | User | تغيير بريد حساب لسه ما اتأكدش (بديل `update_unconfirmed_user_email`) | 2, 1.6 |
| POST | `/api/v1/auth/password/forgot` | Public | إرسال رابط إعادة تعيين كلمة المرور | 2 |
| POST | `/api/v1/auth/password/reset` | Public | تعيين كلمة مرور جديدة | 2 |
| PUT | `/api/v1/me/password` | User | تغيير كلمة المرور من الداشبورد | 3 |
| POST | `/api/v1/me/avatar` | User | تغيير الصورة الشخصية | 3, 5 |
| DELETE | `/api/v1/me` | User | حذف الحساب نهائياً (بديل RPC `delete_user_account`) | 3 |

### 6.2 Onboarding (مقدمي الخدمة + العميل)

| Method | Path | Auth | الوظيفة | القسم |
|---|---|---|---|---|
| GET | `/api/v1/onboarding/validation` | User (engineer/office) | حالة وبيانات التوثيق + المستندات المطلوبة | 2.V |
| POST | `/api/v1/onboarding/validation` | User (engineer/office، step 1 أو مرفوض) | **خطوة التوثيق**: إرسال البيانات والمستندات → `user_validations` (pending) + `current_step = 2` | 2, 2.V |
| PUT | `/api/v1/me/validation` | User (engineer/office) | تعديل أو إعادة إرسال التوثيق من الداشبورد → `pending` | 2.V |
| GET | `/api/v1/plans` | Public | أسعار الباقات حسب `user_type` و `billing_cycle` | 2, 3 |
| GET | `/api/v1/plans/show` | Public | باقة واحدة (`user_type`, `plan_type`, `billing_cycle`) | 2 |
| POST | `/api/v1/onboarding/plan` | User | اختيار الباقة (`selected_plan`, `billing_cycle`) | 2 |
| GET | `/api/v1/payment-methods` | Public | روابط InstaPay / المحفظة | 2 |
| POST | `/api/v1/onboarding/payment` | User | تأكيد الاشتراك + إيصال التحويل → `subscriptions` (pending) | 2 |
| GET | `/api/v1/me/subscription` | User | الاشتراك الحالي | 2, 3 |
| POST | `/api/v1/onboarding/profile/engineer` | User (step 3) | إكمال ملف المهندس | 2 |
| POST | `/api/v1/onboarding/profile/office` | User (step 3) | إكمال ملف المكتب | 2 |
| POST | `/api/v1/onboarding/profile/exhibition` | User (step 3) | إكمال ملف المعرض / المورد (نوع واحد، 0.5) | 2 |
| PATCH | `/api/v1/onboarding/step` | User | تحديث `current_step` (يُفضّل السيرفر هو اللي يحدّثه تلقائياً) | 2 |
| POST | `/api/v1/customer/setup` | User (customer) | إعداد العميل / إنشاء أول طلب خدمة → `customers` | 2 |

### 6.3 الموقع العام (Public)

| Method | Path | Auth | الوظيفة | القسم |
|---|---|---|---|---|
| GET | `/api/v1/home` | Public | بيانات الصفحة الرئيسية المجمّعة | 5 |
| GET | `/api/v1/settings/homepage-display` | Public | إعدادات ظهور الباقات في الرئيسية | 5 |
| GET | `/api/v1/stats/public` | Public | عدادات المنصة (`analytics_counts`) | 5 |
| GET | `/api/v1/banners` | Public | بانرات التسويق (`placement` = `home` أو `directory`، `type`) | 5 |
| GET | `/api/v1/lookups/directory-filters` | Public | قوائم الفلاتر لكل دليل | 5 |
| GET | `/api/v1/engineers` | Public | دليل المهندسين (search, governorate, category, sort, pagination, `featured`) | 5 |
| GET | `/api/v1/engineers/{slug}` | Public | تفاصيل مهندس (+ services + projects + permissions) | 5 |
| GET | `/api/v1/offices` | Public | دليل المكاتب | 5 |
| GET | `/api/v1/offices/{slug}` | Public | تفاصيل مكتب | 5 |
| GET | `/api/v1/exhibitions` | Public | دليل المعارض والموردين (`showrooms-suppliers`) | 5 |
| GET | `/api/v1/exhibitions/{slug}` | Public | تفاصيل معرض/مورد (+ products) | 5 |
| GET | `/api/v1/projects/featured` | Public | مشاريع الرئيسية | 5 |
| GET | `/api/v1/projects/{id}` | Public | تفاصيل مشروع | 5 |
| GET | `/api/v1/material-prices` | Public | أسعار خامات البناء | 5 |
| GET | `/api/v1/articles`, `/api/v1/articles/{id}` | Public | المقالات (حالياً static — اختياري) | 5 |
| GET | `/api/v1/reviews` | Public | تقييمات ملف (`target_slug`) أو تقييمات المنصة (`target_slug=t3meer`) | 5 |
| GET | `/api/v1/reviews/status` | User | هل المستخدم قيّم الملف ده قبل كده | 5 |
| POST | `/api/v1/reviews` | User | إضافة تقييم | 5 |
| GET | `/api/v1/favorites/status` | User | حالة المفضلة لمجموعة slugs | 5 |
| POST | `/api/v1/favorites/toggle` | User | إضافة/إزالة من المفضلة | 5 |
| POST | `/api/v1/contact-messages` | Public | فورم اتصل بنا (**جديد** — حالياً مش بيتحفظ) | 5 |
| POST | `/api/v1/track/metric` | Public | تسجيل نقرة/زيارة على ملف (`stats`) | 5 |
| POST | `/api/v1/track/analytics` | Public | عدادات عامة (بديل RPC `increment_analytics`) | 5, 1.6 |
| POST | `/api/v1/meta/events`, `/api/v1/meta/purchase` | Public | Meta Conversions API (بديل `app/api/meta/*`) | 5, 1.6 |
| POST | `/api/v1/uploads` | User | رفع ملف (لو اتختار الرفع عن طريق الـ API) | 0.3.4, 3 |

> بدائل مذكورة في القسم 5: `POST /api/v1/favorites` و `DELETE /api/v1/favorites/{slug}` بدل `toggle`. اختاروا أسلوب واحد بس.

### 6.4 لوحة تحكم المستخدم (`/me/...`)

| Method | Path | Auth | الوظيفة | القسم |
|---|---|---|---|---|
| GET | `/api/v1/me/dashboard` | User | إحصائيات الرئيسية (زيارات، نقرات، تقييمات، charts) — بديل `get_all_stats` | 3 |
| GET | `/api/v1/me/activities` | User | النشاط الأخير (`type`, `limit`) | 3 |
| PUT | `/api/v1/me/profile` | User | تعديل الملف (حقول مختلفة حسب `user_type`) | 3 |
| GET / POST | `/api/v1/me/projects` | User (engineer/office) | قائمة / إضافة مشروع (مع حد الباقة) | 3 |
| PUT / DELETE | `/api/v1/me/projects/{id}` | Owner | تعديل / حذف مشروع | 3 |
| GET / POST | `/api/v1/me/products` | User (exhibition) | قائمة / إضافة منتج (مع حد الباقة) | 3 |
| PUT / DELETE | `/api/v1/me/products/{id}` | Owner | تعديل / حذف منتج | 3 |
| GET | `/api/v1/services/catalog` | User | كتالوج الخدمات/التصنيفات حسب `user_type` | 3 |
| GET / POST | `/api/v1/me/services` | User | خدماتي / إضافة خدمة (مع حد الباقة) | 3 |
| DELETE | `/api/v1/me/services/{id}` | Owner | حذف خدمة | 3 |
| POST | `/api/v1/me/subscription-requests` | User | طلب ترقية/تجديد باقة + إيصال | 3 |
| PATCH | `/api/v1/me/subscription/confirmation` | User | تحديث إيصال التحويل | 3 |
| GET / PUT | `/api/v1/me/marketing` | User | البانر + السوشيال + Facebook Pixel | 3 |
| GET | `/api/v1/me/provider-requests` | User (office/engineer) | طلبات العملاء المسندة ليا | 3 |
| PATCH | `/api/v1/me/provider-requests/{id}` | User (office/engineer) | تحديث حالة الطلب (الدالة موجودة بس مش مستخدمة في الواجهة) | 3 |
| POST | `/api/v1/service-requests/{id}/contact-clicks` | User | تسجيل نقرة واتساب/اتصال على طلب | 3, 4 |
| GET / POST | `/api/v1/me/service-requests` | User (customer) | طلباتي / طلب جديد | 3 |
| POST | `/api/v1/me/service-requests/{id}/response` | Owner (customer) | قبول/رفض المكتب المسند + السبب (طلب مكتب بديل) | 3 |
| DELETE | `/api/v1/me/service-requests/{id}` | Owner (customer) | حذف طلب | 3 |
| GET | `/api/v1/me/favorites` | User (customer) | مفضلاتي (`type`) | 3 |
| DELETE | `/api/v1/me/favorites/{slug}` | User (customer) | حذف من المفضلة | 3 |
| GET / PUT | `/api/v1/me/reviews` | User (customer) | تقييماتي / تعديل تقييم | 3 |

### 6.5 الأدمن (`/admin/...`)

| Method | Path | الوظيفة | القسم |
|---|---|---|---|
| GET | `/api/v1/admin/stats/overview` | إحصائيات عامة + Funnel التسجيل | 4 |
| GET / PUT | `/api/v1/admin/settings/homepage-display` | إعدادات ظهور الباقات في الرئيسية | 4 |
| GET | `/api/v1/admin/users` | دليل المستخدمين (search, sort, pagination) | 4 |
| POST | `/api/v1/admin/users/{user}/resend-verification` | إعادة إرسال رابط تفعيل البريد | 4 |
| GET | `/api/v1/admin/verifications` | طلبات التوثيق | 4 |
| POST | `/api/v1/admin/verifications/{verification}/approve` | قبول توثيق | 4, 2.V |
| POST | `/api/v1/admin/verifications/{verification}/reject` | رفض توثيق + السبب | 4, 2.V |
| POST | `/api/v1/admin/verifications/{verification}/reset` | إرجاع الطلب للمراجعة (`pending`) | 2.V |
| GET | `/api/v1/admin/subscriptions` | سجل الاشتراكات | 4 |
| PATCH | `/api/v1/admin/subscriptions/{subscription}/status` | تغيير حالة اشتراك (تفعيل/رفض…) | 4 |
| POST | `/api/v1/admin/subscriptions/manual` | تفعيل باقة يدوياً | 4 |
| GET | `/api/v1/admin/finance/stats` | الإحصائيات المالية | 4 |
| GET | `/api/v1/admin/engineers`, `/api/v1/admin/offices` | جداول المهندسين/المكاتب | 4 |
| GET / PUT | `/api/v1/admin/engineers/{user}`, `/api/v1/admin/offices/{user}` | تفاصيل وتعديل ملف وحالة | 4 |
| GET / POST | `/api/v1/admin/material-prices` | أسعار المواد / إضافة | 4 |
| PUT / DELETE | `/api/v1/admin/material-prices/{id}` | تعديل / حذف سعر | 4 |
| GET | `/api/v1/admin/customer-requests` | طلبات العملاء + الإحصائيات | 4 |
| GET | `/api/v1/admin/customer-requests/assignable-providers` | المكاتب/المهندسين المتاحين للترشيح | 4 |
| POST | `/api/v1/admin/customer-requests/{customer}/assign` | ترشيح/تعيين مكتب أو مهندس | 4 |
| PATCH | `/api/v1/admin/customer-requests/{customer}/status` | تغيير حالة الطلب | 4 |
| DELETE | `/api/v1/admin/customer-requests/{customer}` | حذف طلب | 4 |

كل endpoints الأدمن: `auth:sanctum` + `role:admin` (server-side).

---

## 7. قرارات مطلوبة قبل البدء + مشاكل لازم تتصلح في الـ Rebuild

> دي أهم النقط اللي ظهرت في الأقسام 1–5، مجمّعة في مكان واحد. التفاصيل والـ `path:line` موجودين في القسم المذكور.

### 7.1 قرارات بيانات / Business (لازم تتحسم قبل الـ migrations)

| # | الموضوع | الوضع الحالي في الكود | المطلوب قرار فيه | القسم |
|---|---|---|---|---|
| 1 | **أسماء الباقات** | الواجهة وجداول `plans`/`subscriptions` بتستخدم `professional`، أما enum `plan_type` في `users.selected_plan` فقيمه `free/basic/pro/premium`. ممكن الحفظ يفشل من غير ما حد يلاحظ لأن الـ error مش بيتشيك | اعتماد مجموعة قيم واحدة (مقترح: `free, basic, professional, premium`) + عمود `weight` للترتيب بدل الاعتماد على ترتيب الـ enum | 1.8.3, 1.8.12, 2 |
| 2 | **نوع المورد** | الداشبورد والـ routes بيستخدموا `material` (وجدول `materials`)، والـ trigger بيقبل `supplier`، والتسجيل مش بيعرض النوع ده أصلاً. وأي `material` بيتحفظ `engineer` | ✅ **اتحسم:** مفيش نوع مستقل، المورد = `exhibition` (نفس التسجيل والفورمز والداشبورد والدليل). خطة نقل البيانات وقائمة الخدمات الموحدة في **0.5** | 0.5, 1.10.2, 2, 3.0 |
| 3 | **التوثيق (`user_validation`)** | مفيش حاجة في الكود بتوجّه لشاشات `/validation/*`. وكل حفظ لإكمال الملف بيكتب فوق `user_validation` بيانات placeholder (رقم عشوائي وصور placehold.co). والمهندس لما يبعت التوثيق بيخلي حالته `active` بنفسه | ✅ **اتحسم:** التوثيق خطوة رسمية للمهندس والمكتب عند step 1، والأدمن بس اللي بيغيّر الحالة. المواصفات في **2.V**، وفيه 6 قرارات فرعية ليها قيم افتراضية في **2.V.8** | 1.9, 2, 2.V, 4 |
| 4 | **قيم الحالات** | الأدمن بيكتب `reject` والـ SQL فيه `rejected`. واختيار "معلق" في جدول التوثيقات عند الأدمن بينفّذ **رفض** | enum موحد لكل جدول + state machine واضحة | 1.8.1, 4 |
| 5 | **الاشتراكات** | أعمدة مكررة (`plan_id`/`plan_type`, `starts_at`/`start_date`, `expires_at`/`end_date`, `plan_price`/`amount`). التفعيل من الأدمن مش بيحط تواريخ. `subscription_payments` مفيش كود بيكتب فيه بس صفحة المالية بتقرأ منه. السعر مش بيتبعت للسيرفر. وإعادة رفع الإيصال بترجّع حتى الاشتراك الـ active لـ `pending` | تصميم `subscriptions` + `subscription_payments` من جديد، وحساب السعر والتواريخ على السيرفر | 1.2.11–1.2.12, 1.8.4, 3.8, 4 |
| 6 | **حدود الباقات (`user_permissions`)** | المصدر الوحيد للحدود (`max_services`, المشاريع، المنتجات…) والتطبيق client-side بس. مفيش كود بيعمل الصفوف دي | seed للحدود لكل باقة (أرقام صفحة الأسعار موجودة في 3.15 كمرجع) + enforcement في الـ API | 1.2.13, 1.8.5, 3.15 |
| 7 | **طلبات العملاء (`customers`)** | الترشيح بيمسح `notes` ويحط نص الأدمن مكانه، وتاريخ الرفض متخزن كنص جوه `notes` (`[رفض سابق: …]`). المكاتب بتقرأ كل جدول `customers` وبتطابق بالاسم | جدول history للترشيحات/الرفض (مقترح `customer_request_assignments`) + فلترة server-side بـ `assigned_provider_id` | 1.8.9, 3.10–3.11, 4 |
| 8 | **الإحصائيات (`stats`)** | صف واحد لكل `(slug, metric_type)` فيه total، و`created_at` بيتكتب عليه كل مرة، فـ"الزيارات" بتعد صفوف مش زيارات. والرئيسية بتزوّد أرقام ثابتة (+1000 زيارة، +12 تقييم) | جدول events يومي (`date`, `count`) + تحديد هل الأرقام الثابتة دي تفضل | 1.8.6, 3.3, 5 |
| 9 | **فورم اتصل بنا** | مش بيحفظ أي حاجة، بيستنى 1.5 ثانية ويعرض رسالة نجاح | جدول `contact_messages` جديد | 5 |
| 10 | **إعدادات الصفحة الرئيسية** | متخزنة في ملف JSON على السيرفر (`data/homepage-display-settings.json`) | جدول `settings` | 1.7.4, 4, 5 |
| 11 | **بيانات mock بتتحفظ** | فورمز تعديل الملف بتبدأ ببيانات تجريبية وأحياناً بتحفظها. وفورم المورد (اللي بيتلغي، 0.5) بيعمل upsert للـ state كلها في `materials` (فيها `id: "mat-101"`)، وده غالباً بيفشل | الـ API يقبل الحقول الموثقة بس (whitelist) | 3.4 |
| 12 | **مسار التسجيل** | الملف `uncommitted_changes.patch` (من يونيو، مش متطبق) فيه flow تاني: تخطي الأسعار، اشتراك مجاني تلقائي، ومفيش حد للخدمات | تأكيد إن الـ flow الحالي (القسم 2.A) هو المعتمد | 2.A, 2.C |
| 13 | **القوائم الثابتة** | فورم المكتب بيعرض قائمة خدمات المهندس بدل `officeServices`، وفلتر المكاتب في الدليل نفس المشكلة. وأسماء خدمات العميل وتخصصات المهندس مكتوبة بإملاء مختلف (الهمزات)، فالمطابقة النصية بينهم بتفشل | جداول lookups موحدة بـ `code` ثابت بدل المطابقة بالنص العربي | 2, 5 |
| 14 | **التليفونات** | أرقام التليفون والواتساب بتتحفظ زي ما اتكتبت. وفي فورمز التعديل التحقق من رقم مصري بيحصل بعد الحفظ | normalization + validation على السيرفر (`/^01[0125][0-9]{8}$/`) | 2, 3 |
| 15 | **الـ Pagination** | كل الأدلة بتحمّل كل الصفوف وبتقسّمها 12 في المتصفح، والبحث بيضرب query مع كل حرف | pagination + search + sort على السيرفر | 5 |

### 7.2 حاجات مش موجودة في الكود (لازم تتجاب من الداتابيز الحالية)

الملفات دي/التعريفات دي **مش موجودة في الـ repo** ولازم تتسحب من Supabase قبل البدء: `supabase db dump --schema-only` أو `pg_dump --schema-only`:

- تعريفات الـ Views: `public_engineers_view`, `public_offices_view`, `public_exhibitions_view`, `public_materials_view` (للنقل بس، 0.5), `premium_active_view`, `marketing_active_users`.
- تعريفات الـ RPCs: `check_user_exists`, `get_limited_stats`, `get_all_stats`, `delete_user_account`, `increment_analytics`.
- أي triggers بتملا `slug` في جداول البروفايل، أو بتعمل صفوف `user_permissions` / `subscription_payments`، أو بتحط تواريخ الاشتراك.
- RLS policies، والـ enums الحقيقية، وأنواع الـ PK لكل جدول.
- البيانات المرجعية اللي لازم تتعمل لها seed: جدول `plans` (الأسعار مش موجودة في الكود خالص)، `user_permissions`، `materials_prices`، `analytics_counts`.

### 7.3 مشاكل أمنية (لازم تتقفل في Laravel)

1. **صلاحية الأدمن** معتمدة على cookie المتصفح بيكتبها بنفسه (`user-storage`)، وكل عمليات الأدمن بتتنفذ من المتصفح بالـ anon key ← `role:admin` middleware + Policies على السيرفر.
2. **RPC `update_unconfirmed_user_email`** متاح للـ `anon`، والـ route بتاعه مش محتاج login، يعني أي حد يعرف بريد حساب لسه ما اتأكدش يقدر يغيّره ← endpoint يتطلب token أو signed link.
3. **`POST /api/admin/homepage-display`** من غير أي authentication.
4. **المهندس بيفعّل حسابه بنفسه** (التوثيق بيكتب `status = active`).
5. **`/customer/setup`** بيقبل إرسال من غير login.
6. **`user_id` بييجي من الواجهة** في كل الـ inserts/updates ← يتاخد من `auth()->id()` دايماً.
7. **حدود الباقات** (مشاريع، منتجات، خدمات) client-side بس.
8. **بيانات العملاء** (تليفون وإيميل) بتتقري كاملة من مكاتب ومهندسين مش مسند لهم الطلب.

باقي القائمة (16 نقطة) في القسم 1.9، وملاحظات الأدمن في القسم 4.

### 7.4 ترتيب تنفيذ مقترح للفريق

1. سحب الـ schema الحقيقي (7.2) ومقارنته بالقسم 1، وبعدين كتابة الـ migrations والـ seeders (plans, permissions, lookups).
2. Auth + Onboarding (القسم 2): register/login/verify/reset + `current_step` + **خطوة التوثيق (2.V)** + plans + payment + complete-profile.
3. القراءات العامة (القسم 5): الأدلة والتفاصيل والتقييمات والمفضلة والتتبع.
4. لوحة المستخدم (القسم 3): profile, projects, products, services, subscription, marketing, requests.
5. لوحة الأدمن (القسم 4).
6. تحويل طبقة `features/**/api/*.api.ts` في الفرونت إنها تنادي الـ endpoints الجديدة بدل Supabase. الـ components والـ React Query hooks تفضل زي ما هي على قد ما يمكن.

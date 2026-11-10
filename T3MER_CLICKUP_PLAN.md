# خطة تحويل تعمير (T3meer) إلى Laravel REST API — تاسكات ClickUp

> المصدر: `T3MEER_BACKEND_API_SPEC_v3.md` — الترتيب مبني على القسم 7.4 (ترتيب التنفيذ المقترح).
> كل يوم = List في سبيس **T3mer**، وآخر مهمة في كل يوم = مهمة اختبار/مراجعة مع المستخدم.
> الاصطلاح: كل وصف تاسكة يحوي الجداول والـ endpoints والقواعد المطلوبة والمراجع لأقسام الملف.

---

## اليوم 1 — إعداد المشروع واتفاقيات API وحسم القرارات

### 1. تأسيس مشروع Laravel واتفاقيات الـ REST API
- إنشاء مشروع Laravel + تثبيت Sanctum، Base URL ‏`/api/v1`، `Authorization: Bearer {token}` (المراجع: 0.4).
- توحيد الـ envelope: `{data, message}`، والأخطاء 422 بصيغة `{message, errors:{field:["رسالة بالعربي"]}}`.
- Pagination: `?page=1&per_page=12` → `{data, meta:{current_page, per_page, total, last_page}}`.
- قواعد عامة: تجاهل `user_id`/`owner_id` في الـ body ويؤخذ من `auth()->id()`، الإبقاء على UUID لـ `users.id`، timestamps ISO 8601.
- Middleware: `auth:sanctum` + `role:admin`/roles (customer/engineer/office/exhibition) + فحص `current_step` — الفرونت يتوجّه بناءً على `GET /api/v1/auth/me` (0.3.1).
- متغيرات البيئة: `APP_URL` لروابط الإيميلات، `CLOUDINARY_*`، `INSTAPAY_LINK`/`WALLET_LINK`، `META_PIXEL_ID`/`META_ACCESS_TOKEN`/`META_TEST_EVENT_CODE` — وإلغاء كل متغيرات Supabase (0.3.2).

### 2. سحب الـ schema الحقيقي وحسم قرارات البيانات (قبل أي migration)
- تصدير الـ schema من Supabase: `supabase db dump --schema-only` (أو `pg_dump`) ومقارنته بالملف — لأن الأنواع والقيود كلها `(inferred)` من الكود (0.3.3, 7.2).
- حسم القرارات الـ 15 في 7.1: توحيد `professional` بدل `pro`، `rejected` بدل `reject`، تصميم `subscriptions` الموحد (إزالة `plan_id/starts_at/expires_at` المكررة)، جدول `settings` بدل ملف JSON، جدول `contact_messages`، جدول أحداث يومي لـ `stats`، جداول `lookups` بـ code ثابت، توحيد صيغة الهاتف، pagination/search/sort كلها server-side، whitelist للحقول (منع mock data).
- ⚠ قرار لازم من المستخدم: مسار الـ onboarding المعتمد — المسار الحالي أم مسار `uncommitted_changes.patch` (تخطي التسجيل للأسعار + اشتراك free نشط تلقائياً)؟ (1.5.6, 2.C#14).
- استبعاد ما هو stale غير المستخدم: `customer_project_requests`, `profiles_*`, `reviews`, `analytics`, `banners` (1.10.1).

### 3. إعداد بيئة الاختبار وCI + الهيكل الأساسي
- Pest/PHPUnit + factories + `RefreshDatabase`، بنية Controllers/Services/Policies/Form Requests/Resource.
- تجميعات أساسية: ApiResponse trait (envelope + أخطاء عربية)، slug generator فريد، helpers التحقق (`isValidEgyptianPhone`, `toEnglishDigits`, `EMAIL_REGEX`) (2.0.5).
- (✅ تست اليوم) مراجعة القرارات المكتوبة مع المستخدم والموافقة عليها قبل البدء في الـ migrations.

---

## اليوم 2 — قاعدة البيانات: Migrations وModels وSeeds

### 4. جداول الهوية والملفات المهنية + PHP Enums
- `users`: uuid PK، `email` unique، `password` bcrypt (ترحيل من `auth.users.encrypted_password`)، `email_verified_at`، `name`، `phone` (؟ unique)، `user_type` enum `customer|engineer|office|exhibition|admin`، `slug` unique، `current_step`، `status`، `selected_plan`، + أعمدة جديدة من metadata: `selected_billing_cycle`, `selected_user_type`, `profile_image`, `rejection_reason` (1.2.1, 1.5.4, 1.5.7).
- `engineers` و`offices`: `UNIQUE(user_id)` + FK؛ عمود `slug` يُملأ server-side = `users.slug`؛ توحيد `workingHours`/`coverImage`/`videoUrl` إلى snake_case؛ ملاحظة أن `offices` لا يخزن phone/email/website (تأتي من الـ view) (1.2.2, 1.2.3).
- `exhibitions`: الجدول الوحيد للمعارض/الموردين — `main_category` (23 فئة)، `logo/banner/profile_image/video`، `brands`، `open_hours`، `count_of_categories`، `whats_app`, `lat/lng` (1.2.4).
- `services`: جدول واحد لثلاثة أغراض (تخصص مهندس/مكتب/تصنيفات منتجات)، `user_id` ليس UNIQUE، `slug` منسوخ للمالك (1.2.6).
- ⛔ جدول `materials` مايتعملش (0.5).
- PHP Enums موحّدة: `AccountType`, `Status` (اختر `rejected`), `PlanType` (`professional` بدل `pro`), `MetricType` (8 قيم) (1.4).

### 5. جداول المحتوى والتفاعل
- `projects` (uuid، `details` jsonb: video/brief/categories/photos، `slug` منسوخ للمالك)، `products` (`details` jsonb) (1.2.7, 1.2.8).
- `favorites` و`rating`: `UNIQUE(user_id, slug)` تُفرض server-side؛ `rating.type` له دلالة مزدوجة — فصل عمودين (نوع الهدف/نوع الكاتب)؛ تقييمات المنصة `slug="t3meer"` (1.2.15, 1.2.16).
- `stats` (عداد واحد لكل `(slug, metric_type)` — upsert atomic)، `analytics_counts` (الكتابة عبر خدمة فقط)، `marketing` (jsonb banner/social)، `materials_prices` (يبقى كما هو)، `interaction_logs` (قرار: يُحذف أو يُعاد كسجل حقيقي) (1.2.17–1.2.21).

### 6. جداول الاشتراك والتوثيق والصلاحيات وطلبات العملاء
- `plans`: `UNIQUE(user_type, plan_type, billing_cycle)` — seeded يدوياً (الأسعار مش موجودة في الكود) (1.2.10, 7.2).
- `subscriptions`: صف واحد لكل مستخدم `UNIQUE(user_id)` — توحيد الأعمدة المكررة (`plan_id/plan_type`, `starts_at/start_date`, `expires_at/end_date`, `plan_price/amount`)؛ حالات `pending|active|expired` (1.2.11).
- `subscription_payments`: Laravel ينشئ صفاً عند موافقة الأدمن (1.2.12, 1.8.4).
- `user_permissions`: قرار — صفوف template بـ `user_id = null` لكل (user_type, plan_type) أم صف لكل مستخدم؛ توحيد حالة `priority_search`؛ قاعدة "غير محدود": `null` أو `-1` أو `>= 99999` (1.2.13).
- `user_validation`: حالات `pending|active|rejected` — **ممنوع** upsert الـ placeholder اللي بيمسح المستندات الحقيقية (1.2.9, 1.8.2).
- `customers`: `customer_type` ∈ guest|need_service، `assigned_provider_id` uuid، 8 قيم `status`؛ ⚠ عمود `notes` يُستخدم كسجل regex — بنية منظمة (1.2.14).
- جداول جديدة: `settings` (key/value JSON)، `contact_messages` (7.1).

### 7. Seeders وقوائم الاختيارات
- `plans` (أسعار حقيقية من الـ DB الحية)، `user_permissions` لكل (نوع × باقة)، `analytics_counts` (7.2).
- Lookups: المحافظات 27، فئات المعارض 23 (+ فرعية)، تخصصات المهندس 16، الخدمات الهندسية 16، قائمة خدمات المعرض الموحدة (14) (2.0.5).
- (✅ تست اليوم) تشغيل migrations + seeds على قاعدة نظيفة ومراجعة الـ schema النهائي مع المستخدم.

---

## اليوم 3 — Views وRPC ونقل البيانات

### 8. إعادة بناء الـ Views كـ query scopes وAPI Resources
- 5 scopes: `public_offices_view`, `public_engineers_view`, `public_exhibitions_view`, `premium_active_view`, `marketing_active_users` — و⛔ `public_materials_view` مش هتتعمل (1.3, 0.5).
- كلها مقيدة بالحسابات النشطة فقط؛ joins: ملف + `users` (phone/email/selected_plan) + `services` array + `projects`/`products` + `user_permissions`.
- حسابات مترابطة: `average_rating` = avg(rating) بـ slug، `reviews_count`، `total_visits` = sum(stats.count) حيث `metric_type='visit'`.
- الترتيب بالوزن صراحة: premium 30 / professional 20 / basic 10 / free 0 (بدل enum order) (1.8.12).
- `premium_active_view`: آخر 100 مشروع ثم واحد لكل مستخدم بحد 15 (1.7.6).

### 9. استبدال الـ RPCs والـ trigger بمنطق Laravel
- `check_user_exists` → خدمة داخلية + إصلاح user enumeration (1.9.6).
- `increment_analytics` → upsert atomic على `analytics_counts` (1.4).
- `get_limited_stats(p_slug)` و`get_all_stats(p_slug)` → query scopes — ⚠ فترة `get_limited_stats` غير معروفة، تُحسم (1.10.3).
- `delete_user_account` → `DELETE /api/v1/auth/account` — قرار ما يُحذف بـ cascade (1.10.3).
- `update_unconfirmed_user_email` → `PATCH /api/v1/auth/email` **بمصادقة فقط** (كان GRANT لـ `anon` = account takeover) (1.9.5).
- trigger `handle_new_user` → منطق داخل register: توليد slug بـ suffix `-1,-2` حتى التفرد، whitelists، بلا `EXCEPTION WHEN OTHERS` الصامت (1.9.16).

### 10. نقل البيانات: دمج الموردين في `exhibition` (القرار 0.5)
- Enum نهائي: `customer, engineer, office, exhibition, admin` — إزالة `material`/`supplier` من كل أعمدة الـ type (favorites, rating, stats, user_validation, plans, subscriptions, marketing, analytics_counts).
- Data migration بالترتيب: `UPDATE users SET user_type='exhibition' WHERE user_type IN ('material','supplier')` (الـ slug مايتغيرش) → نقل `materials` → `exhibitions` → حذف صفوف `plans`/`user_permissions` للمواد → حذف `materials` و`public_materials_view`.
- خريطة الخدمات: `site-delivery`→`delivery-shipping`، `wholesale-prices`→`wholesale`؛ القائمة الموحدة = 8 خدمات المعرض + 6 من المورد (14) — ⚠ قرار: الموحدة أم الأصلية 8.
- حقول المورد الخاصة (`price_list`, `quantities`, `certificates`, `delivery_areas`, `truck_types`...) — تُحذف أم تُضاف jsonb (يعتمد على نتائج الـ dump).
- (✅ تست اليوم) مقارنة نتائج الـ scopes بالـ views القديمة على بيانات حقيقية.

---

## اليوم 4 — المصادقة (Auth)

### 11. تسجيل حساب جديد + دخول + جلسة المستخدم
- `POST /api/v1/auth/register` — public، throttle `6,1`: `account_type in:customer,engineer,office,exhibition`, `name`, `email unique`, `phone regex مصرى + unique`, `password min:6 confirmed` → 201 `{user, token, requires_email_verification}` — منطق `check_user_exists` + توليد slug + `current_step=1, status=pending, selected_plan=free` (2, RegisterForm).
- `POST /api/v1/auth/login` — throttle `10,1` → `{token, user}` — أخطاء: 401 `invalid_credentials`، 403 `email_not_confirmed` (2).
- `GET /api/v1/auth/me` (Bearer) → UserResource: `user_type, current_step, slug, name, phone, selected_plan, selected_billing_cycle, status, profile_image, email_verified_at` + `next_route` (1.5.7, 2.0.1).
- `POST /api/v1/auth/logout` → 204.

### 12. تفعيل البريد + إعادة الإرسال + تغيير البريد غير المؤكد
- Verify link (signed URL): يضبط `email_verified_at` ثم 302 حسب النوع: customer → `/customer/setup`، غيره → `/pricing/{type}` (أو `/validation/{type}` للمهندس/المكتب) (2.V.2).
- `POST /api/v1/auth/email/resend` — throttle `1,1` → 429 عند التجاوز (الفرونت countdown 60 ثانية) (2).
- `PATCH /api/v1/auth/email` — **يُسمح فقط لو `email_verified_at IS NULL`**: `new_email` different + unique، يعيد توليد slug ويبعت رابط تفعيل → `{success, new_email, new_slug, email_sent, message}` (2, 1.6).

### 13. نسيت/تغيير كلمة المرور + حذف الحساب
- `POST /api/v1/auth/password/forgot` — throttle `3,1` → **200 دائم** (عدم كشف وجود الحساب)، رابط `{APP_URL}/reset-password?token=&email=` (2).
- `POST /api/v1/auth/password/reset` — `token, email, password min:6 confirmed` → 200 / 422 لو token منتهٍ (`password_reset_tokens`) (2).
- `PUT /api/v1/me/password` — `current_password` إلزامي (الكود القديم لا يطلبها) → 204 (3.4.5).
- `DELETE /api/v1/me` — `confirm: accepted` → 204: حذف users + البروفايل + projects/products/services/marketing/subscriptions/permissions/favorites/rating + إلغاء كل التوكنات (RPC `delete_user_account`) (3.2.4).
- (✅ تست اليوم) سيناريوهات auth كاملة: رسائل عربية، 401/403/409/422، ومنع user enumeration.

---

## اليوم 5 — Onboarding جزء 1: التوثيق وآلة الحالات

### 14. OnboardingService + آلة حالات `current_step` + Guards
- كل endpoint يتحقق إن `current_step` مناسب ويرجع 409 `invalid_onboarding_step` أو `validation_required` مع `next_route` (2.A, 2.0.7).
- `UserResource.next_route` (2.V.2): step1 engineer/office → `/validation/{type}`، step1 exhibition → `/pricing/{type}`، step1 customer → `/customer/setup`، step2 → `/pricing`، step3 → `/complete-profile`، step≥4 → `/dashboard/{type}` — الفرونت يستهلك `next_route` ولا يكرر `getStepRoute()`.
- الانتقالات server-side كأثر جانبي فقط (اختيار باقة، رفع إيصال، إكمال ملف، موافقة أدم) — لا endpoint عام يقبل أي قيمة (1.5.7).
- إلغاء الكتابة المزدوجة في metadata — مصدر واحد `users` (2.0.2).

### 15. خطوة التوثيق للمهندس والمكتب (2.V)
- `POST /api/v1/onboarding/validation` (Bearer + verified + `user_type ∈ {engineer,office}`) — multipart: `name`, `professional_id regex أرقام`, `graduation_year between:1970,الحالية` (engineer), `specialization in:مدني,معماري`, `tax_number` (office), مستندات `image|mimes|max:10240` (كارنيه للمهندس، سجل تجاري + بطاقة ضريبية للمكتب) (2.V.5).
- منطق السيرفر داخل transaction: رفع `verifications` → `updateOrCreate` على `user_id` → `status=pending` → `users.current_step = max(step,2)` و`users.status` يفضل `pending` — ⛔ **المهندس لا يفعّل حسابه بنفسه** (1.9.3).
- شروط: مسموح عند `current_step==1` أو `status=rejected` وإلا 409 `validation_already_submitted`؛ نوع غير مطلوب توثيقه → 403 (2.V.6).
- **تجاهل** حقول الفرونت `user_id, account_type, status, current_step` (الكود الحالي يرسل `status: active` = bug).
- `GET /api/v1/onboarding/validation` → `{data|null, required, required_documents}`.
- `PUT /api/v1/me/validation` (إعادة إرسال من الداشبورد): `sometimes`، الملف غير المُرسل يحتفظ بنوعه، `status→pending`، **لا يغيّر `current_step`** (2.V.8#4).
- (✅ تست اليوم) state machine: خطوات 0–4، قبول/رفض التوثيق، وكل حالات الـ 409.

---

## اليوم 6 — Onboarding جزء 2: الباقة والدفع وإكمال الملف

### 16. اختيار الباقة — `GET /plans` + `POST /onboarding/plan`
- `GET /api/v1/plans?user_type=&billing_cycle=` (public) من جدول `plans` — `plan_id` alias لـ `plan_type` (2, 1.2.10).
- `POST /api/v1/onboarding/plan`: `plan_type in:basic,professional,premium`, `billing_cycle in:monthly,quarter,half,yearly` — المجانية يحكمها السيرفر من `plans.price == 0` (ليست من `confirmation="free_plan"`).
- مجاني: upsert `subscriptions{status:pending, confirmation:free_plan}` + `current_step=3` → 200 UserResource؛ مدفوع: 422 `payment_required` → `/payment/{type}`.
- ⚠ Guard التوثيق: المهندس/المكتب لازم `current_step>=2` وصف توثيق وإلا 409 `validation_required` (2.V.6).
- فتح صفحة الأسعار **لا** يرفع الخطوة تلقائياً (الكود القديم يرفع — يُصحح).

### 17. تأكيد الاشتراك والدفع — `POST /onboarding/payment`
- multipart: `plan_type`, `billing_cycle`, `receipt required|file|image|max:5120` ("حجم الصورة يجب ألا يتجاوز 5 ميجابايت") (2).
- رفع الإيصال لـ `payments` → upsert `subscriptions` الوحيد (`status=pending`, `confirmation=URL`) → `current_step = max(step,3)` + `selected_plan/selected_billing_cycle` → 200.
- قرار 2.C#8: خزّن snapshot `plan_price` من `plans` وأنشئ `subscription_payments{status:pending}` عند الطلب.
- `GET /api/v1/me/subscription` (صف الاشتراك).
- (✅ تست اليوم) onboarding كامل لكل الأنواع الأربعة + حدود الباقات والتوثيق.

### 18. إكمال الملف المهني: مهندس + مكتب
- `POST /api/v1/onboarding/profile/engineer` (step==3): `profile_image|mimes`, `major in:معماري,مدني`, `experience_years`, `governorate in:27`, `whats_app`, `bio max:500`, `specializations required array min:1 distinct in:16`, `skills`, `lat/lng` — تحويل `specializations[]` لصفوف `services` (delete-then-insert) + `engineers` + `users.name` → `current_step=4` (2).
- `POST /api/v1/onboarding/profile/office`: `governorate`, `experience_years`, `count_of_projects`, `open_hours`, `services required array`, `whats_app`, `bio`, `lat/lng`, `profile_image` → `offices` + `services` → step 4.
- فرض حد `max_services` server-side: `user_permissions.max_services` أولاً وإلا fallback بالباقة (مهندس: basic 1, professional 5, premium/free ∞؛ مكتب: basic 3, professional 7, premium/free ∞) (2.C#12).
- ⛔ إزالة upsert الـ placeholder في `user_validation` (أرقام عشوائية + placehold.co) (2.C#3).

### 19. إكمال ملف المعرض + إعداد العميل
- `POST /api/v1/onboarding/profile/exhibition`: `logo` (العمود logo لا profile_image), `bio`, `whats_app`, `governorate`, `main_category required in:23`, `sub_categories array` (free text مسموح), `count_of_categories`, `open_hours`, `lat/lng` → `exhibitions` + `services` → step 4 — ⛔ لا يوجد `/profile/material` (0.5).
- `POST /api/v1/customer/setup` (customer فقط): `customer_type in:need_service,guest`, `service required_if:need_service` (16 خدمة), `governorate required_if:need_service in:27`, `notes nullable max:500` — `full_name/phone/email` تُشتق من `users`؛ guest → `status=active` بلا خدمة/محافظة؛ need_service → `pending`؛ ثم `current_step=2` و`status=active` (2).
- ⚠ أمان: الصفحة حالياً مستثناة من middleware وبلا guard — إلزام auth + منع إعادة الإرسال (2.C#6).
- (✅ تست اليوم) إكمال ملف كل الأنواع + حدود الخدمات + مسار العميل.

---

## اليوم 7 — لوحة المستخدم جزء 1

### 20. حراسات الداشبورد + الأفاتار + إجراءات عامة
- Middleware لكل endpoints القسم: `auth:sanctum` + role + لغير customer شرط `current_step>=4` وإلا `next_route` (3.0).
- `POST /api/v1/me/avatar` — `{profile_image: url}` (أو multipart عبر `POST /api/v1/uploads`) → تحديث جدول البروفايل حسب النوع (3.2.2).
- `POST /api/v1/auth/logout` → 204 (3.2.3).
- `GET /api/v1/auth/me` يعيد `profile{name, profile_image, major, slug}` (3.0).

### 21. صفحة رئيسية الداشبورد (إحصائيات وحدود الباقة)
- `GET /api/v1/me/dashboard` + `GET /api/v1/me/activities?limit=10` — يجمع server-side: `plan, status, permissions, stats, usage, visits_chart, profile_completion, activities` (3.3).
- تطبيع الحدود: `null` = غير محدود؛ `-1`/`>=99999` تُرجع null؛ الأرقام المقفولة تُرجع null من السيرفر.
- حسابات يُجب تنفيذها server-side: `last_month_visitors` (صفر في الكود)، مخطط الزيارات `SUM(count)` وليس عدّ الصفوف، أيام التفعيل حسب الدورة، نسبة اكتمال الملف، `favorites_count`, `average_rating/reviews_count` (3.3).
- ⚠ إصلاح: اسم الفاعل في الأنشطة يظهر "زائر" دائماً — join الفاعل؛ إزالة `mockActivities`.
- رد العميل: `stats{fav_engineers, fav_offices, my_ratings}` + `activities`.

### 22. تعديل ملف المهندس والمكتب
- `PUT /api/v1/me/profile` (role engineer/office): upsert على `user_id` + `users.name/phone` (3.4.1, 3.4.2).
- engineer: `governorate(27)`, `whats_app`, `bio`, `major(معماري|مدني)`, `experience_years`, `skills array`, `facebook|instagram|youtube`, `lat/lng`.
- office: `governorate`, `whats_app`, `experience_years`, `count_of_projects`, `open_hours`, `bio`, `video`, `lat/lng`.
- ⚠ إصلاح: فحص الهاتف المصري **قبل** الحفظ (كان بعد الحفظ)؛ منع قيم mock كـ fallback؛ رفع فيديو المكتب مشروط بصلاحية `video_projects` (3.4).

### 23. تعديل ملف المعرض (وبدمج المورد فيه)
- `PUT /api/v1/me/profile` (role exhibition): `name`, `main_category` (label عربي من 23), `governorate` إلزامية؛ `bio, whats_app, open_hours, experience_years, count_of_categories, brands, logo/banner/video, social, lat/lng` اختيارية (3.4.3).
- `slug` لا يُقبل من client؛ رفع `logo`→`exhibitions/logos`, `banner`→`exhibitions/banners`, فيديو→`videos` (≤100MB).
- ⛔ `EditMaterialProfile` (جدول materials) لا يُنفَّذ — المورد يستخدم هذا الفورم (0.5, 3.4.4).
- (✅ تست اليوم) مراجعة الداشبورد مع المستخدم: الرئيسية + تعديل الملفات الثلاثة.

---

## اليوم 8 — لوحة المستخدم جزء 2: CRUD المحتوى

### 24. CRUD المشاريع (معرض الأعمال)
- `GET/POST/PUT/DELETE /api/v1/me/projects[/{id}]` (role engineer|office) مع `meta{count, limit, remaining, plan_type}` (3.5).
- Rules: `name, location required max:255`, `area required integer min:1`, `type required in:{16 label}`, `image nullable url`, `details array` مع `categories.* in:{16}|distinct`, `details.video prohibited لو !video_projects`, `photos.* url`.
- فرض حد الباقة server-side عند الإضافة فقط (التعديل بلا حد) — 403/422 عند اللمسة (3.5).
- السيرفر يملأ `user_id, slug, user_type, user_name` — فحص ملكية في PUT/DELETE (كانت RLS فقط) (1.9.4).
- Cloudinary folder `projects` (صورة رئيسية، صور إضافية، فيديو raw).

### 25. CRUD المنتجات (للمعرض/المورد)
- `GET/POST/PUT/DELETE /api/v1/me/products[/{id}]` (role exhibition) مع `meta` (3.6).
- Rules: `name required max:255`, `price required integer min:0`, `category required in:{23}`, `sub_category required string` (نص حر), `image required url`, `details.description nullable`, `details.photos.* url`.
- حد الباقة: نفس عمود `max_projects` (لا يوجد `max_products`) — enforcement عند الإنشاء.

### 26. الخدمات/التخصصات/تصنيفات المنتجات
- `GET /api/v1/me/services` (meta)، `GET /api/v1/services/catalog?user_type=` (static + DISTINCT من DB مع `group`), `POST /api/v1/me/services` → 201، `DELETE /api/v1/me/services/{id}` → 204 (بالـ id لا بالاسم) (3.7).
- Rules: `name required max:150`, `custom boolean`; لو `custom=false` لازم من الكتالوج (السيرفر يحدّد icon/color)؛ `custom=true` للمعرض فقط؛ `Rule::unique(services)->where(user_id)` (3.7).
- حد `max_services` server-side عند الإضافة.
- ⚠ حفظ `icon/color` مع الصف (خدمات الكتالوج لا تُعرض بدونها).
- (✅ تست اليوم) اختبار الحدود والملكية: 403/422 + عدّادات meta.

---

## اليوم 9 — لوحة المستخدم جزء 3: الاشتراك والتسويق والطلبات

### 27. الباقات والاشتراك: طلبات الترقية/التجديد/الإيصال
- `GET /api/v1/me/subscription` (مع fallback من `users.selected_plan`)، `GET /api/v1/plans` (3.8).
- `POST /api/v1/me/subscription-requests`: `plan_type in:basic,professional,premium`, `billing_cycle in:monthly,quarter,half,yearly` (+ engineer/office `not_in:monthly`)، `confirmation required_unless:price,0` — **الأسعار تُحسب server-side فقط** من `plans` (3.8).
- مجاني (`price=0`): `confirmation="free_plan"` وتفعيل فوري؛ `current_step = max(current_step, 3)`.
- `PATCH /api/v1/me/subscription/confirmation` لتحديث الإيصال — **لطلب pending فقط**، لا إعادة `active` إلى `pending`، ومنع الإرسال المكرر (idempotency) (3.8).
- (3.15) نصوص "حدود الباقات" في pricing-static هي تسويقية فقط — الـ enforcement في مهمات الحدود.

### 28. التسويق والظهور: بانر + منشور سوشيال + Facebook Pixel
- `GET /api/v1/me/marketing` → `{banner, meta_pixel, social, permissions}`؛ `PUT/PATCH /api/v1/me/marketing` (3.9).
- Enforcement للأقفال: `banner prohibited` لو لا `directory_ad_banner` ولا (`homepage_ad_banner` و type≠engineer)؛ `meta_pixel prohibited` لو `!facebook_pixel` أو المهندس؛ `social prohibited` لو `!social_media_promo` (3.9).
- Rules: `banner.title max:255`, `banner.description max:1000`, `image urls`, `social.caption max:2200`.
- Cloudinary `banners` و`social_posts`؛ PATCH partial بدل إرسال كل الحقول (الكود القديم زر واحد لثلاثة أقسام).

### 29. طلبات العملاء المسندة للمكتب/المهندس
- `GET /api/v1/me/provider-requests?tab=all|active|refused&search=&page=` (role office|engineer) → `data` + `stats{assigned, active, refused}` (3.10).
- ⚠ استبدال منهجية الكود: لا قراءة كل `customers` ثم مطابقة بالاسم — `assigned_provider_id = auth()->id()` + **جدول history للترشيح/الرفض** بدل parsing `notes` بregex (3.10, 7.1).
- `POST /api/v1/service-requests/{id}/contact-clicks` — `{type: whatsapp|call}` → 204 مع `increment()` ذرّي (3.10).
- إخفاء بيانات التواصل للطلبات المرفوضة؛ الفلاتر server-side.

### 30. طلبات الخدمات للعميل + المفضلة وتقييماتي ونشاطي
- `GET /api/v1/me/service-requests?tab=...&search=` (role customer) → `data[].provider` + `my_review` + `stats` — **join server-side** بدل قراءة الـ views كلها وتعمية client-side (3.11).
- `POST /api/v1/me/service-requests/{id}/response` — `{response: approved|refused, reason required_if:refused}`؛ refused → `status=pending` مع الإبقاء على المزوّد (3.11.1).
- `PUT /api/v1/me/reviews` (upsert): `target_slug`, `target_type in:engineer,office,exhibition`, `rating between:1,5`, `message max:2000` — unique `(user_id, slug)` — ⚠ إصلاح bug: الكود يكتب `type="office"` دائماً (3.11.2).
- `DELETE /api/v1/me/service-requests/{id}` → soft delete `status=deleted` (3.11.3) + `POST` إنشاء من setup (3.11.4).
- `GET /api/v1/me/favorites?type=` + `DELETE /api/v1/me/favorites/{slug}` (3.12)؛ `GET /api/v1/me/reviews?stars=` مع join لاسم الملف + `summary` بـ aggregate SQL (3.13)؛ `GET /api/v1/me/activities` (3.14).
- (✅ تست اليوم) مراجعة: الاشتراك + التسويق + الطلبات الثلاثة + المفضلة/التقييمات.

---

## اليوم 10 — الأدمن جزء 1: الحوكمة والإجراءات

### 31. حوكمة صلاحية الأدمن ونقل الأمان للسيرفر
- `auth:sanctum` + middleware `EnsureUserIsAdmin` (يتشيك `users.user_type='admin'` من DB) على **كل** `/api/v1/admin/*` + Policies/Gate لكل resource (4, 1.9.1).
- ⚠ إصلاح جوهري: صلاحية الأدمن الحالية cookie يكتبها المتصفح + فك JWT بدون توقيع — الدور من `GET /auth/me` فقط (1.9.2).
- Form Requests + validation لكل الـ mutations (حالياً صفر) + whitelist `$fillable` + `DB::transaction()` لكل multi-write + `increment()` للعدّادات (4).
- منع الاعتماد على IDs/إيميل يُرسل من الكلاينت: `userId` في التوثيق، `providerName` في التعيين → route model binding.
- (مقترح) audit log لكل إجراء أدم.

### 32. قبول/رفض طلبات التوثيق
- `POST /api/v1/admin/verifications/{id}/approve` — transaction: `status='active'` + `users.status='active'` + `interaction_logs` + مسح `rejection_reason` (4).
- `POST .../reject` — `{reason required|string|max:1000}` → `rejection_reason` + `users.status='reject'` + log — بدون تغيير `current_step`/`subscriptions`.
- `POST .../reset` لحالة `pending` فعلية — ⚠ bug: خيار "معلق" في الـ UI يتفهم كرفض (4).
- فصل التوثيق عن تفعيل الاشتراك (2.V.3).

### 33. إدارة الاشتراك والتفعيل اليدوي وإعادة تفعيل البريد
- `PATCH /api/v1/admin/subscriptions/{id}/status` — `{status in:active,pending,expired}`؛ عند `active` في transaction: حساب `starts_at/expires_at` حسب الدورة + إنشاء `subscription_payments` + تحديث `users.current_step`/`status` (4, 1.8.4).
- `POST /api/v1/admin/subscriptions/manual` — `user_id uuid exists`, `plan_id in:basic,professional,premium`, `days min:1` — `updateOrCreate(['user_id'])` بدل upsert بلا onConflict (⚠ bug: duplicate key) وكتابة `plan_type` لا `plan_id` (4).
- `POST /api/v1/admin/users/{user}/resend-verification` — السيرفر يقرأ الإيميل من DB (منع إرسال لأي إيميل) — redirect حسب النوع على `APP_URL` (4).
- Scheduled job لانتهاء الاشتراكات (لم يكن هناك أي job) (1.8.4).

### 34. تعديل ملف مهندس/مكتب من الأدمن وحالته
- `PUT /api/v1/admin/engineers/{user}` و`PUT /api/v1/admin/offices/{user}` — JSON أو multipart (4).
- Validation كامل (الكود بلا أي): `name required`, `status in:active,pending,reject`, `rejection_reason required_if:status,reject`, `governorate CSV max:5 من 27`, `experience_years min:0`, `major in:معماري,مدني`, `skills array`, روابط URLs (4).
- Transaction: `users{name, phone, status, rejection_reason}` + `updateOrCreate` على البروفايل؛ `rejection_reason=null` لو `status≠reject`.
- (✅ تست اليوم) صلاحيات: مستخدم عادي يجرب كل admin endpoints → 403.

---

## اليوم 11 — الأدمن جزء 2: الأسعار وطلبات العملاء والإعدادات

### 35. CRUD أسعار مواد البناء
- `GET /api/v1/admin/material-prices` (ترتيب category asc)، `POST` — `category required max:100`, `item_name required`, `unit required`, `price numeric gt:0`, `change_percent nullable numeric` (قد يكون سالب) (4).
- `PUT .../{id}` — السيرفر يتجاهل أي أعمدة خارج الخمسة؛ `DELETE .../{id}` → 204.
- ⚠ الجدول نفسه تقرأه الرئيسية — ضمان توافق الأعمدة.

### 36. تعيين مكتب/مهندس لطلب عميل (وإعادة الترشيح)
- `POST /api/v1/admin/customer-requests/{id}/assign` — `provider_user_id required uuid exists in:office,engineer`، `notes nullable` (4).
- السيرفر يجلب الاسم والنوع من DB — إلغاء منطق resolve بالـ UUID/slug/اسم/RPC (حالياً fuzzy).
- سلوك التعيين: `assigned_provider_id/name` + `assigned_at=now` + تصفير `customer_response/customer_reason` + `status='pending'`.
- ⚠ قرار: `notes` الحالي سجل regex `[رفض سابق: ...]` — (مقترح) جدول مستقل `customer_request_assignments` (7.1).

### 37. حالات وحذف طلبات العملاء + عدّادات التواصل
- `PATCH /api/v1/admin/customer-requests/{id}/status` — `{status in:pending,assigned,approved,completed,cancelled}` + `notes sometimes` (بدون validation حالياً) (4).
- `DELETE .../{id}` — ⚠ قرار: العميل يعمل soft delete (`status=deleted`) والأدمن hard — توحيد السلوك.
- `POST /api/v1/service-requests/{id}/contact-clicks` — مشترك مع لوحات العميل/المكتب، `increment()` ذرّي، يرجع `{whats_count, calls_count}` (4).
- فلتر استبعاد `guest` يشمل NULL — قرار server-side.

### 38. إعدادات ظهور الباقات في الصفحة الرئيسية
- `GET/PUT /api/v1/admin/settings/homepage-display` — body: `offices.{premium,professional,basic,free}` و`engineers.*` كلها `required boolean` (4, 1.6).
- ⚠ إصلاح أمني: النسخة الحالية **بلا أي auth** وتحفظ في ملف JSON على filesystem وترجع success حتى لو الكتابة فشلت — التخزين في جدول `settings` + validation + خطأ عند الفشل (1.9.7).
- تطبيق الفلترة والترتيب **على السيرفر** في الرئيسية العامة: `isPlanAllowed` + وزن الباقات + `normalizePlanTier` (`pro`→`professional`) (1.7.4).
- Cache invalidation للرئيسية بعد كل تعديل؛ Defaults = `free: false` والباقي true.
- (✅ تست اليوم) مراجعة مع المستخدم: الأسعار + طلبات العملاء + إعدادات الظهور.

---

## اليوم 12 — الأدمن جزء 3: القراءات (إحصائيات وقوائم)

### 39. إحصائيات عامة + Funnel التسجيل
- `GET /api/v1/admin/stats/overview`: counts حسب النوع + active من public views + `pendingVerifications` + visits = `SUM(stats.count where visit)` + `SUM(analytics_counts where page)` (4).
- Funnel بـ `current_step` (بدون customer/admin): `awaitingVerification(1)`, `selectingPlan(2)`, `awaitingProfile(3)`, `activeOnboarded(≥4 & active)`, `rejected`.
- ⚠ قرارات: `materials` يُحذف والموردون يُحسبوا `exhibitions`؛ توحيد المنطق (كان يعدّ مرتين)؛ إرجاع raw أم حسابات جاهزة (الكود يضيف +1000 يدوياً).

### 40. دليل المستخدمين + قائمة طلبات التوثيق
- `GET /api/v1/admin/users?page&per_page&search&sort=plan|date` — بحث `name/email/phone` + pagination server-side + ترتيب الباقة **بالكامل في SQL** (`ORDER BY CASE lower(trim(selected_plan))...`) (4).
- `GET /api/v1/admin/verifications?...` — بحث server-side على `name/professional_id/tax_number` + `users.email/phone` + `stats` في نفس الرد (4).
- إرجاع: الحالة المشتقة (`status + current_step + rejection_reason`)، `slug` لروابط الدليل، `documents[]` و`details.graduation_year`.

### 41. سجل الاشتراكات + الإحصائيات المالية
- `GET /api/v1/admin/subscriptions?...` — بحث server-side على join **قبل** pagination (كان يبحث في صفحة الـ20 فقط = bug) + `stats{pending, active, expired, plans}` (4).
- `GET /api/v1/admin/finance/stats`: `activeTotalRevenue/Count`, `totalMonthly/totalYearly`, `chartData[12 شهر]`, `totalsPerPlan`, New vs Renewed (أول payment لكل user), `planBreakdown`, `totalTransactions` (4, 1.8.7).
- ⚠ قرارات: هل chart آخر 12 شهر أم كل السنين؛ هل نفلتر `subscription_payments.status`.

### 42. جداول المهندسين/المكاتب وتفاصيلهم
- `GET /api/v1/admin/engineers|offices?search&only_public` — بحث على `{table} + users` + إحصائيات: `topGovernorates` (تقسيم CSV أول 3), `planStats` (normalize pro→professional), `activeTotal` (4).
- `GET /api/v1/admin/engineers/{user}|offices/{user}` — parallel: users + profile + services + projects + permissions + stats + favorites + analytics محسوبة في SQL (`SUM(CASE metric_type...)`) بدل client-side (4, 1.8.7).
- ⚠ quirk: شرط `slug OR user_id` في stats/favorites غالبًا يجلب صفوف الزائر — مراجعة.

### 43. طلبات العملاء + قايمة المرشحين
- `GET /api/v1/admin/customer-requests?...` — فلاتر status/service/governorate + `stats` + `serviceStats` (group by trim(service), null→"خدمات عامة", يستثني deleted) (4).
- `GET /api/v1/admin/customer-requests/assignable-providers?type&plan&service&search` — ترتيب بالوزن (premium>professional>basic>free) — ⚠ إلغاء N+1 RPC `check_user_exists` لكل مرشح (limit 1000) (4).
- (✅ تست اليوم) مقارنة كل أرقام لوحة الأدمن الجديدة بالأرقام الحالية على نفس البيانات.

---

## اليوم 13 — الموقع العام جزء 1

### 44. الصفحة الرئيسية المجمّعة
- `GET /api/v1/home` (cache 300s + `Cache-Control`): مكاتب/مهندسين مفلترين حسب إعدادات العرض (من `settings` لا من ملف JSON)، مشاريع (آخر 100 → واحد لكل مستخدم → 15)، `stats`, `prices`, `reviews` (`slug="t3meer"`) (5.2, 1.6).
- `GET /api/v1/settings/homepage-display` (public) بنفس shape + `normalizePlanTier`.
- `GET /api/v1/projects/featured` — `limit` و`one_per_owner` (5.2.20).
- `GET /api/v1/stats/public` (cache 300s) — ⚠ معادلة visits: قرار 7.1#8 (عدد صفوف vs SUM(count) + الفرونت يضيف +1000).
- `GET /api/v1/material-prices` — اختياري `category`، ترتيب ثابت.
- Fallbacks الثابتة تفضل في الفرونت.

### 45. بانرات التسويق (الرئيسية + الدليل)
- `GET /api/v1/banners?placement=home|directory&type=` (5.2.1, 5.2.10).
- home: `marketing_active_users` شرط `plan_type='premium' AND user_type='office' AND banner IS NOT NULL`.
- directory: جدول mapping — `engineers→(engineer, premium)`, `offices→(office, professional)`, `showrooms-suppliers→(exhibition, professional)` — ⚠ لو `plan_type` مخزّن `pro` الاستعلام يرجع فاضي (يتراجع قبل التنفيذ).
- الترتيب **تصاعدي** حسب عدد النقرات (الأقل أولاً)؛ `link` جاهز أو `user_type+slug`؛ القيم القديمة `materials`→`showrooms-suppliers`.

### 46. قوائم الأدلة الثلاثة + الفلاتر
- `GET /api/v1/engineers`, `/api/v1/offices`, `/api/v1/exhibitions` (دليل واحد لـ showrooms-suppliers/exhibitions/materials) (5.2.11–5.2.13).
- params: `search` (ILIKE binding), `category` (JSONB containment), `governorate`, `sort ∈ {experience,bestViewers,bestRating,nearMe}` + افتراضي `plan_weight DESC`, `lat/lng` مع nearMe (Haversine server-side), `page`, `per_page=12`, `featured` (5.2.0).
- ⚠ pagination/search/sort كلها server-side (كانت client-side) (7.1#15).
- Scope الظهور: `status='active'` + حد `max_visits` (يتطلب مراجعة تعريف الـ views من الـ dump) (1.3).
- `is_favorited` في كل عنصر لو Bearer (يلغي N+1) + `meta` pagination.
- `GET /api/v1/lookups/directory-filters?type=` → `{categories, governorates, sorts}` (5.2.14).

### 47. صفحات تفاصيل المهندس/المكتب/المعرض
- `GET /api/v1/engineers|offices|exhibitions/{slug}` — Optional auth يرجع `viewer:{is_favorited, has_rated}`؛ 404 لو الملف مخفي (5.2.16–5.2.18).
- response يضم `services[]`, `projects[]` (مع `details`), `products[]` (المعرض), `brands`, `permissions` (max_visits, video_projects, google_maps_location...).
- شروط: فيديو المكتب بشرط `permissions.video_projects !== false`؛ الخريطة بشرط `google_maps_location` وإحداثيات ≠ 0؛ توحيد `total_visits` مع `views_count` (تعارض حالي).
- فتح الصفحة يستدعي `track("visit")` (dedupe) — تنفيذه في مهمة التتبع (يوم 15).
- (✅ تست اليوم) فحص الدليل والتفاصيل مع المستخدم على بيانات حقيقية.

---

## اليوم 14 — الموقع العام جزء 2

### 48. التقييمات: القائمة + الحالة + الإضافة
- `GET /api/v1/reviews?target_slug=` → `data` + `summary{average, count, distribution{5..1}}` — `target_slug=t3meer` لتقييمات المنصة (5.2.19).
- `GET /api/v1/reviews/status?target_slug=` (auth) → `{has_rated}` (أو ضمن `viewer`).
- `POST /api/v1/reviews` (auth): `target_slug`, `target_type in:engineer,office,exhibition,platform`, `rating integer 1-5`, `message required max:2000` (5.3.1, 5.3.2).
- قواعد: `platform` ⇔ `slug='t3meer'`؛ لغير platform لازم `customer` وإلا 403 "إضافة التقييمات متاح للعملاء فقط"؛ unique `(user_id, slug)` → 422 "لقد قمت بتقييم هذا الملف مسبقاً".
- `name`/`image` من البروفايل لا من الـ body؛ إعادة حساب `average_rating/reviews_count` بعد كل إضافة (5.3.1).

### 49. المفضلة: Toggle + حالة مجمّعة
- `POST /api/v1/favorites/toggle` (auth): `target_type in:engineer,office,exhibition`, `target_slug` (5.3.3).
- snapshot (`name, image, phone`) ييجي من جدول الهدف server-side لا من الـ body؛ `user_id = auth()->id()`؛ unique `(user_id, slug)`.
- Response: `{data:{action:"added|removed", is_favorited}, message}` — ⚠ اختيار أسلوب واحد فقط (toggle أم REST) (6.3).
- `GET /api/v1/favorites/status?slugs[]=` (auth) → map batch (يلغي N+1).

### 50. مشاريع الموقع + الصفحات الثابتة + "اتصل بنا"
- `GET /api/v1/projects/{id}`: المشروع + `owner{phone, whats_app, user_type}` + `other_projects` — ⚠ يُطبَّق شروط ظهور المالك (404 لو مخفي) (5.2.20).
- `has_details` لتحديد اللينك: لو `details` فيها brief/video/photos → `/projects/{id}` وإلا صفحة المالك.
- ثابت بدون API (مؤكَّد): `/about`, `/privacy`, `/terms`, Footer, PWA (5.2.22).
- المقالات: قرار static (100 موضوع + bug تضارب IDs) أم جدول `articles` + `GET /api/v1/articles` (5.2.9).
- **جديد** `POST /api/v1/contact-messages` (public, throttle `5,1`): `name/email/subject required max:255`, `phone nullable regex مصرى` (مع normalization), `message required max:5000` → 201 + إشعار إيميل للإدارة (5.3.4).
- (✅ تست اليوم) مراجعة: التقييمات + المفضلة + المشاريع + نموذج التواصل.

---

## اليوم 15 — التكاملات الخارجية

### 51. رفع الملفات (Cloudinary)
- قرار: (أ) استقبال URLs كـ strings + `POST /api/v1/uploads/signature` يعيد signed params، أو (ب) `POST /api/v1/uploads` multipart يرفع ويرجع `{url}` (الأمني أنسب) (0.3.4, 1.7.1).
- التحقق أن الـ URL المرسل تابع لحساب Cloudinary الخاص بالمنصة (1.9.14).
- جدول الفولدرات→الأعمدة كما في 1.7.1 (profiles, engineers/profiles, offices/profiles, exhibitions/{logos,banners}, videos, projects, products, verifications, payments, banners, social_posts) — ⛔ حذف `materials/profiles`.
- حدود: فيديو 100MB (raw), إيصال 5MB؛ preset hard-coded يُصحح إلى env.
- ⚠ إيصالات الدفع ومستندات التوثيق على روابط عامة — قرار: signed URLs أو فولدرات private.
- فهرس مستندات المكتب: index 0 = السجل التجاري، index 1 = البطاقة الضريبية.

### 52. تتبع التفاعلات والتحليلات
- `POST /api/v1/track/metric` (public, optional auth, throttle `60,1`) → 204: `slug`, `metric_type in:8 قيم`, `type nullable`, `device_id ≤64` (5.3.5).
- `visit`: dedupe (slug + device_id|ip + date) مرة واحدة في اليوم؛ باقي الـ metrics بدون dedupe؛ UPSERT atomic `(slug, metric_type)` — ⚠ قرار 7.1#8: صفوف يومية للرسم البياني بدل العداد الواحد.
- `type=showrooms-suppliers`/`material` → `exhibition`.
- `POST /api/v1/track/analytics` (public, throttle `120,1`) → 204 — بديل RPC `increment_analytics`: `stat_type in:page,service,governorate,sort` + whitelisting لـ `stat_key` (5.3.6).
- الأحداث: `page/home` + 5 صفحات دليل (dedupe يومي ينتقل من localStorage إلى server) + تغييرات الفلاتر.

### 53. Meta Conversions API + نقل الـ Route Handlers
- `POST /api/v1/meta/events`: `{eventName, params, eventId, userId, email, phone, testEventCode}` + IP/UA/referer — hash للبريد/الهاتف/external_id (5.3.7, 1.7.2).
- `POST /api/v1/meta/purchase`: fallback لقراءة `subscriptions`/`users` لو ناقص؛ 400 بدون كشف البريد (⚠ الكود الحالي يرجع `params:{subId,uId,uEmail}`)؛ `_fbp/_fbc` من cookies.
- ⚠ أمن: `testEventCode` من env لا من body؛ (مقترح) Laravel يبعت Purchase server-side تلقائياً عند تفعيل الاشتراك.
- الأحداث: CompleteRegistration (signup), ProfileCompleted, AddPaymentInfo/Subscribe (payment), Purchase.
- نقل الـ Next.js route handlers الستة (1.6): homepage-display, analytics/track, homepage, public/stats, meta/event, meta/purchase, auth/update-email (الأخير انتقل لـ PATCH /auth/email).
- (✅ تست اليوم) اختبار التكاملات: رفع ملفات + تتبع + Meta بوضع test.

---

## اليوم 16 — الأمان والاختبارات والتسليم

### 54. إغلاق قائمة المشاكل الأمنية (1.9 + 7.3)
- 16 نقطة إلزامية: أدم server-side (لا cookie)، فك JWT موقّع، منع الكتابة في `status/current_step/selected_plan` من العميل، فحص ملكية على كل update/delete بالـ id، إغلاق `update_unconfirmed_user_email` و`check_user_exists` و`homepage-display` وMeta endpoints، حدود الباقات server-side، إصلاح تسريب PII (طلبات المكتب)، filter injection (parameter binding)، cookies آمنة، عدادات atomic، Cloudinary signed، منع مسح مستندات التوثيق بـ placeholder، بلا trigger صامت.
- جدول تحقق: كل بند من 1.9 + 7.3 = ✓ مع الدليل (اختبار يغطيه).

### 55. Feature Tests الشاملة
- لكل endpoint: auth (401), role (403), validation (422 برسائل عربية), ownership (403), pagination, state machine للـ `current_step`/`status`, فرض حدود الباقة server-side (2.C + 3.18).
- اختبارات Data migration: `material→exhibition`، ومقارنة الـ scopes بالـ views القديمة.
- سيناريوهات E2E بالترتيب: تسجيل → تفعيل → توثيق → باقة → دفع → ملف → داشبورد → أدم.

### 56. توثيق API + ربط الفرونت + التسليم
- توليد OpenAPI (Scribe/Laravel Swagger) لكل endpoints مع أمثلة — المرجع الموحّد: القسم 6 (فهرس الـ Endpoints) و6.3 للموقع العام = checklist التسليم.
- تحويل طبقة `features/**/api/*.api.ts` في الفرونت لتنادي الـ REST بدل Supabase مع إبقاء components وReact Query hooks (7.4 الخطوة 6).
- دليل تكامل لفريق الفرونت: headers، أكواد الأخطاء العربية، `next_route`، تغيير middleware.ts إلى الاعتماد على `/auth/me`.
- (✅ التست النهائي) تسليم مع المستخدم: جدول مطابقة كل endpoint في القسم 6 ✓ + مراجعة 7.1/7.2 كمفتوحة.

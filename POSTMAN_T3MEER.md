# بوست مان — كوليكشن t3meer (مرجع الربط)

> اتربط مع OpenCode عبر **Postman MCP** (`https://mcp.postman.com/mcp` في `~/.config/opencode/opencode.json`).
> **القاعدة:** أي API جديد بينفّذ في المشروع أثناء تنفيذ مهام ClickUp لازم يتضاف في الكوليكشن ده بـ **description و documentation محترمة** — التفاصيل تحت.

## المعرّفات (IDs)

| العنصر | القيمة |
|---|---|
| Workspace | **ahmed_work** — `377b317b-f762-4f3c-959c-def2d8b0a7de` |
| Collection | **t3meer** — `e604f206-7a4e-402c-9f81-56e6d61f7436` |
| Collection UID | `29289143-e604f206-7a4e-402c-9f81-56e6d61f7436` |
| حساب Postman | ahmed (`material-specialist-11565861`) — id `29289143` |

### المجلدات (Folders)

| المجلد | Folder ID |
|---|---|
| 01 · Auth & Account | `29289143-8a40a907-a0bf-4793-bf35-2fda2ffe928b` |
| 02 · Onboarding & Plans | `29289143-56dd7585-5baf-4df8-83a9-e5187b18d31e` |
| 03 · Public Site | `29289143-572a18d3-56c8-4290-83f2-a0f76aff948c` |
| 04 · User Dashboard (/me) | `29289143-9b124357-195d-46ce-821d-78bba041d6a8` |
| 05 · Admin (/admin) | `29289143-cfab2a83-5066-4ea0-abfb-80c9b6a3221c` |

## إعدادات الكوليكشن

- **Variables:** `base_url` = `http://localhost:8000/api/v1` · `token` · `admin_token`
- **Auth:** Bearer `{{token}}` (الـ endpoints الـ Public تتظبط No Auth)

## آلية العمل مع كل مهمة ClickUp (إلزامي)

لما يتنفّذ endpoint جديد في المشروع:

1. حدّد المجلد المناسب (من الـ 5 فوق — حسب **القسم 6** في `T3MEER_BACKEND_API_SPEC_v3.md`).
2. أنشئ request بالشكل:
   - **الاسم:** `METHOD /path — وصف عربي مختصر`
   - **URL:** `{{base_url}}/...` + كل query params بوصف
   - **Auth:** حسب مستوى الـ endpoint (Public / User / Owner / Admin)
   - **Body:** JSON بحقل لكل حقل موثّق
3. **الـ Description لازم يتضمن:**
   - ✅ الوظيفة + مين بيستخدمها (`user_type`)
   - ✅ Auth المطلوب
   - ✅ الـ Body: كل حقل (النوع، إجباري/اختياري، قواعد validation)
   - ✅ Response: مثال نجاح + أمثلة أخطاء شائعة (422/401/403/404)
   - ✅ مرجع المواصفات (رقم القسم في ملف المواصفات) + رقم مهمة ClickUp
4. بعد أول نجاح فعلي: احفظ **Example Response** واكتب tests أساسية.

## روابط سريعة

- مواصفات الـ API: `T3MEER_BACKEND_API_SPEC_v3.md` — **القسم 6 = فهرس الـ endpoints المقترحة**
- خطة التنفيذ: `T3MER_CLICKUP_PLAN.md` · معرّفات المهام: `CLICKUP_IDS.md`
- فتح الكوليكشن: https://www.postman.com/workspace/377b317b-f762-4f3c-959c-def2d8b0a7de/collection/29289143-e604f206-7a4e-402c-9f81-56e6d61f7436

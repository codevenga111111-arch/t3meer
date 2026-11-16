<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * حذف الحساب نهائياً — بديل RPC `delete_user_account`
 * (SPEC 1.4 + 3.2.4 + القرار 1.10.3 + مهمة ClickUp #9).
 *
 * ⛔ القرار المُحسم: **لا يُحذف بالـ cascade في قاعدة البيانات.**
 * الـ RPC القديم كان تعريفه مفقوداً وما كان معروفاً إيه اللي بيتخرب على بعضه
 * (1.10.3)، فالحذف هنا صريح ومضبوط الترتيب داخل transaction واحدة:
 *
 * 1. التوكنات (إلغاء كل الجلسات).
 * 2. سجلات التفاعل + الأدوار (`user_roles`).
 * 3. المفضلة والتقييمات: اللي كتبها المستخدم **واللي عن ملفه** (بيانات شخصية
 *    مخزَّنة cache: اسم/صورة/تليفون).
 * 4. طلبات العملاء: طلباته + تعيينات المزوّد عليه (وتصفير `assigned_provider_id`
 *    في طلبات جاهزة لغيره).
 * 5. المحتوى: الخدمات/المشاريع/المنتجات + البروفايل (engineers/offices/exhibitions).
 * 6. التسويق، التوثيق، الاشتراك ومدفوعاته، الصلاحيات.
 * 7. صف `users` أخيراً (الأبناء قبل الأهل — بدون FK cascade).
 *
 * ⛔ **ما يُحذفش** (مقصود): `stats` و`analytics_counts` (عدّادات مجهولة المصدر
 * مش بيانات شخصية)، و`plans`/`lookups`/`settings` (بيانات مرجعية)، و`customers`
 * التابعة لغيره.
 */
final class AccountDeletionService
{
    /**
     * تنفيذ الحذف الكامل داخل transaction.
     */
    public function delete(User $user): void
    {
        $id = (string) $user->getKey();
        $slug = (string) $user->getAttribute('slug');

        DB::transaction(function () use ($id, $slug, $user): void {
            // 1) الجلسات/التوكنات.
            $user->tokens()->delete();

            // 2) السجلات والأدوار.
            $this->deleteWhere('interaction_logs', ['user_id' => $id]);
            $this->deleteWhere('user_roles', ['user_id' => $id]);

            // 3) المفضلة والتقييمات (كتبها + عن ملفه).
            $this->deleteWhere('favorites', ['user_id' => $id]);
            $this->deleteWhere('favorites', ['slug' => $slug]);
            $this->deleteWhere('rating', ['user_id' => $id]);
            $this->deleteWhere('rating', ['slug' => $slug]);

            // 4) طلبات العملاء (التعيينات بتمسح مع طلبها بـ FK cascade).
            $this->updateWhere('customers', ['assigned_provider_id' => $id], ['assigned_provider_id' => null]);
            $this->deleteWhere('customers', ['user_id' => $id]);
            $this->deleteWhere('customer_request_assignments', ['provider_id' => $id]);

            // 5) المحتوى والملف.
            $this->deleteWhere('services', ['user_id' => $id]);
            $this->deleteWhere('projects', ['user_id' => $id]);
            $this->deleteWhere('products', ['user_id' => $id]);
            $this->deleteWhere('engineers', ['user_id' => $id]);
            $this->deleteWhere('offices', ['user_id' => $id]);
            $this->deleteWhere('exhibitions', ['user_id' => $id]);

            // 6) التسويق والتوثيق والاشتراك والصلاحيات.
            $this->deleteWhere('marketing', ['user_id' => $id]);
            $this->deleteWhere('user_validation', ['user_id' => $id]);
            $this->deleteWhere('subscription_payments', ['user_id' => $id]);
            $this->deleteWhere('subscriptions', ['user_id' => $id]);
            $this->deleteWhere('user_permissions', ['user_id' => $id]);

            // 7) الحساب أخيراً.
            User::query()->whereKey($id)->delete();
        });
    }

    /** حذف صفوف من جدول لو موجود (الحذف صريح — بدون DB cascade). */
    private function deleteWhere(string $table, array $where): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->where($where)->delete();
        }
    }

    /** تحديث عمود في صفوف مطابقة لو الجدول موجود. */
    private function updateWhere(string $table, array $where, array $values): void
    {
        if (Schema::hasTable($table)) {
            DB::table($table)->where($where)->update($values);
        }
    }
}

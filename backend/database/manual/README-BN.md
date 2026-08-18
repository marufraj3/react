# টার্মিনাল ছাড়া ম্যানুয়াল ডাটাবেস আপডেট

এই আপডেটের জন্য `php artisan migrate` চালানো বাধ্যতামূলক নয়। phpMyAdmin থেকে নিচের SQL ফাইলটি একবার import করলেই হবে:

`database/manual/2026_08_17_campaign_builder_and_capture.sql`

## ধাপ

1. cPanel থেকে **phpMyAdmin** খুলুন।
2. বাম পাশ থেকে ওয়েবসাইটের সঠিক database নির্বাচন করুন।
3. আগে **Export** থেকে database-এর একটি backup ডাউনলোড করুন।
4. উপরের **Import** tab খুলুন।
5. `2026_08_17_campaign_builder_and_capture.sql` ফাইলটি নির্বাচন করুন।
6. Format হিসেবে **SQL** রেখে নিচের **Import/Go** button চাপুন।
7. শেষে verification result-এ `campaigns` ও `incomplete_orders`-এর নতুন column-গুলো দেখা গেলে কাজ সম্পন্ন।

## গুরুত্বপূর্ণ

- Script-টি existing campaign, incomplete order এবং reseller data delete করে না।
- একই script ভুল করে একাধিকবার import করলেও existing column/index আবার তৈরি হবে না।
- পরে কখনো terminal access পেলে স্বাভাবিক `php artisan migrate` চালানো নিরাপদ; Laravel migration-টিও schema guard ব্যবহার করে।
- নতুন code upload করার পর File Manager থেকে `storage/framework/views` directory-এর পুরোনো `.php` cache file মুছে দিন। `.gitignore` file থাকলে সেটি রাখুন। নতুন page visit হলে Laravel view cache আবার তৈরি করবে।

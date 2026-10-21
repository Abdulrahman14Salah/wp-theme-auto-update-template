# WP Theme GitHub Auto-Update Template

قالب جاهز لأي ثيم ووردبريس: push على GitHub → Release تلقائي بالـ zip →
الموقع المباشر يكتشف التحديث ويثبّته لوحده (أو بضغطة واحدة).

👉 ابدأ من [SETUP.md](./SETUP.md) — فيه كل الخطوات بالتفصيل بالعربي.

مبني وتم تجربته فعليًا على [a-salah.dev](https://github.com/Abdulrahman14Salah/a-salah.dev).

## الملفات

- `inc/core/config.php` — إعدادات الريبو (اسم المستخدم، اسم الريبو، توكن لو خاص)
- `inc/updates/updater.php` — الكلاس اللي بيتكلم مع GitHub ويعمل التحديث
- `.github/workflows/auto-release.yml` — الـ Action اللي بيعمل Release تلقائي
- `functions.php` / `style.css` — نقاط الدمج الأساسية مع باقي الثيم

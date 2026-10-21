# WP Theme GitHub Auto-Update Template

قالب جاهز لأي ثيم ووردبريس: push على GitHub → Release تلقائي بالـ zip →
الموقع المباشر يكتشف التحديث ويثبّته لوحده (أو بضغطة واحدة).

كود التحديث نفسه بلاجن منفصل: [github-theme-updater](https://github.com/Abdulrahman14Salah/github-theme-updater)
— مش جوة الثيم، عشان ما يحاولش يستبدل الفولدر اللي هو شغال منه.

👉 ابدأ من [SETUP.md](./SETUP.md) — فيه كل الخطوات بالتفصيل بالعربي.

مبني وتم تجربته فعليًا على [a-salah.dev](https://github.com/Abdulrahman14Salah/a-salah.dev).

## الملفات

- `.github/workflows/auto-release.yml` — الـ Action اللي بيعمل Release تلقائي (الشيء الوحيد المطلوب نسخه فعليًا)
- `functions.php` / `style.css` — نموذج بداية لثيم جديد من الصفر (اختياري لو عندك ثيم قائم بالفعل)

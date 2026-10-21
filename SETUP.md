# نظام التحديث التلقائي للثيمات (GitHub → Live Site)

قالب جاهز لربط أي ثيم ووردبريس بريبو GitHub، بحيث أي `push` على `main` يعمل
Release تلقائي بالـ zip، والموقع المباشر يكتشفه ويحدث نفسه تلقائيًا (أو تضغط
"Update now" وتشتغل فورًا) — بدون رفع أي ملفات يدويًا بعد أول مرة.

بُني ونُجرّب فعليًا على a-salah.dev. هنا نسخة عامة تتظبط على أي عميل/موقع.

---

## 1. اختار اسم الفولدر (Slug) بعناية

⚠️ **أهم قاعدة في كل النظام**: اسم فولدر الثيم **لازم ما يحتويش على نقطة**
(`.`). WordPress core بينظّف (sanitize) اسم الثيم بـ regex بيشيل أي حرف مش
بين `A-Za-z0-9_-` في أكتر من مكان (AJAX الـ "Update now"، كرون التحديث
التلقائي). لو الاسم فيه نقطة، التحديثات بتفشل بصمت من غير أي error واضح.

❌ غلط: `client-site.com`, `my-theme.io`
✅ صح: `client-site-theme`, `my-theme`

ده نفس اسم الـ `THEME_SLUG` اللي هتحطه في الخطوة 3 و5.

---

## 2. اعمل الريبو على GitHub

- عام (Public) أسهل وأسرع اعداد — مناسب لمشاريعك الشخصية
- خاص (Private) لازم للعملاء — خطوة إضافية في البند 6

```bash
gh repo create YOUR_GITHUB_USERNAME/YOUR_REPO_NAME --public   # أو --private
```

---

## 3. نسخ ملفات القالب

انسخ كل الملفات دي من القالب لريبو العميل، وحافظ على المسارات بالظبط:

```
functions.php                   (أو دمج محتواه مع functions.php الموجود)
style.css                       (أو حدّث الموجود فعليًا بنفس الـ headers)
inc/core/config.php
inc/updates/updater.php
.github/workflows/auto-release.yml
```

لو عندك ثيم جاهز فعلاً، ضيف بس المجلدات `inc/core` و`inc/updates` و
`.github/workflows`، وادمج محتوى `functions.php` بتاع القالب مع اللي عندك
(الدالة `mytheme_autoload_inc()` + الـ `add_action('after_setup_theme', ...)`
اللي بتشغّل الـ updater).

---

## 4. عدّل `inc/core/config.php`

افتح الملف وغيّر:

```php
define('MYTHEME_NAME', 'My Theme');                    // اسم الثيم
define('MYTHEME_GITHUB_USER', 'YOUR_GITHUB_USERNAME');  // حسابك على GitHub
define('MYTHEME_GITHUB_REPO', 'YOUR_REPO_NAME');        // اسم الريبو
```

وفي آخر الملف، سيب سطر واحد بس Active من الاتنين (public أو private) —
شوف البند 6 تحت.

---

## 5. عدّل `.github/workflows/auto-release.yml`

سطر واحد بس لازم يتغيّر، في أول الملف:

```yaml
env:
  THEME_SLUG: my-theme-slug # <-- غيّره لاسم الفولدر بتاعك (من غير نقطة!)
```

لازم يكون **مطابق تمامًا** لاسم الفولدر اللي هيتثبت بيه الثيم على
الموقع (يعني لو هتسميه `client-x-theme` في Appearance → Themes، يبقى
نفس الاسم هنا بالظبط).

---

## 6. ريبو عام ولا خاص؟

### عام (Public) — الأسهل
سيب `config.php` كما هو (سطر `raw.githubusercontent.com` الفعّال بالفعل).
مفيش أي توكن مطلوب. انتقل للبند 7.

### خاص (Private) — لازم للعملاء
1. اعمل GitHub Personal Access Token (Fine-grained):
   - Settings → Developer settings → Fine-grained tokens → Generate new token
   - Repository access: **Only select repositories** → اختار ريبو العميل بس
   - Permissions: **Contents: Read-only**, **Metadata: Read-only**
   - اعمل Generate وانسخ التوكن (`ghp_...` أو `github_pat_...`)
2. في `inc/core/config.php`، بدّل آخر سطرين (علّم على اللي بـ
   `raw.githubusercontent.com` بـ `//`، وفك التعليق عن اللي بـ
   `api.github.com`):
   ```php
   // define('MYTHEME_UPDATE_API', 'https://raw.githubusercontent.com/' . MYTHEME_GITHUB_USER . '/' . MYTHEME_GITHUB_REPO . '/main/update-manifest.json');
   define('MYTHEME_UPDATE_API', 'https://api.github.com/repos/' . MYTHEME_GITHUB_USER . '/' . MYTHEME_GITHUB_REPO . '/releases/latest');
   ```
3. امسح الخطوة الأخيرة بالكامل (`Publish update-manifest.json...`) من
   `.github/workflows/auto-release.yml` — مش محتاجينها لريبو خاص
4. على موقع العميل الفعلي، افتح `wp-config.php` وضيف **قبل** سطر
   `require_once ABSPATH . 'wp-settings.php';`:
   ```php
   putenv('MYTHEME_GITHUB_TOKEN=ghp_your_token_here');
   ```
   ⚠️ التوكن ده **سري** — ما تحطّوش في أي ملف يترفع على GitHub، ويتحط في
   `wp-config.php` بتاع السيرفر الفعلي بس.

مع توكن مُصادَق عليه، حد الطلبات بيرتفع لـ **5000 طلب/ساعة** — المشكلة
الأصلية (60 طلب/ساعة مشتركة بين كل مواقع الاستضافة) مش موجودة أصلاً هنا.

---

## 7. أول Push واختبار الـ Pipeline

```bash
git add -A
git commit -m "Initial auto-release setup"
git push origin main
```

تأكد الـ Action شغّال ونجح:

```bash
gh run list --limit 3
gh release list --limit 1
```

لازم تلاقي Release جديد (`v1.0.1` أو زي ما يكون) ومعاه zip بنفس اسم
الـ `THEME_SLUG`. لو الريبو عام، تأكد كمان إن `update-manifest.json`
نزل في الريبو ومتاح:

```bash
curl -s https://raw.githubusercontent.com/YOUR_GITHUB_USERNAME/YOUR_REPO_NAME/main/update-manifest.json
```

---

## 8. أول تثبيت على الموقع المباشر (مرة واحدة فقط)

التحديثات التلقائية تفترض الثيم مثبّت بالفعل. أول مرة بس، لازم رفع يدوي:

1. حمّل الـ zip من Release: `https://github.com/.../releases/download/vX.X.X/THEME_SLUG.zip`
2. **Appearance → Themes → Add New Theme → Upload Theme**
3. ارفع وفعّل (Activate)

من هنا، أي push جديد على `main` يطلع Release، والموقع يكتشفه لوحده
ويحدّث نفسه (الموقع لازم يكون فيه الكود اللي بيشغّل الـ updater — يعني
أول نسخة المرفوعة دي لازم تكون فيها كل ملفات `inc/core` و`inc/updates`
و`functions.php` المعدّلة).

---

## 9. فرض التحديث التلقائي الحقيقي (optional لكن مستحسن)

WordPress مش دايمًا بيظهر "Enable auto-updates" للثيمات بشكل موثوق في
الواجهة. لو عايز تضمن إن الموقع يحدّث نفسه من غير أي ضغطة زرار، ضيف في
`updater.php` (موجود بالفعل في القالب ده):

```php
add_filter('auto_update_theme', [$this, 'force_auto_update'], 10, 2);

public function force_auto_update($update, $item)
{
    if (isset($item->theme) && $item->theme === $this->theme_slug) {
        return true;
    }
    return $update;
}
```

---

## ملاحظات إضافية

- **الكاش الداخلي**: الـ updater بيحفظ آخر Release يجيبه من GitHub لمدة
  6 ساعات (`RELEASE_CACHE_TTL`) عشان يقلل الطلبات. لو عملت push وبعدها
  بدقايق زرت صفحة Updates، هيكتشف أحدث نسخة فورًا (أول زيارة بتعمل فحص
  جديد). لكن لو زرت صفحة Updates قبل الـ push بوقت قصير، هيفضل يقول
  "محدث" لحد ما الكاش يخلص (أو تنتظر فحص الكرون التلقائي اللي بيحصل
  مرتين في اليوم تقريبًا).
- **تغيير اسم الفولدر لثيم قائم فعلاً**: لو عندك ثيم شغال بالفعل باسم
  فيه نقطة وعايز تصلحه، لازم تعمل migration لـ Customizer settings
  (`theme_mods_{old_slug}` → `theme_mods_{new_slug}`) عشان ما تضيعش
  إعدادات العميل. شوف مثال فعلي في `a-salah.dev/functions.php`.

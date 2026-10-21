# نظام التحديث التلقائي للثيمات (GitHub → Live Site)

قالب جاهز لربط أي ثيم ووردبريس بريبو GitHub، بحيث أي `push` على `main` يعمل
Release تلقائي بالـ zip، والموقع المباشر يكتشفه ويحدث نفسه تلقائيًا (أو تضغط
"Update now" وتشتغل فورًا) — بدون رفع أي ملفات يدويًا بعد أول مرة.

كود التحديث نفسه **مش جوة الثيم** — لازم يكون خارجها، مش جوة الفولدر اللي
بيتم استبداله وقت التحديث. استخدم بلاجن منفصل:
👉 [github-theme-updater](https://github.com/Abdulrahman14Salah/github-theme-updater)

بُني ونُجرّب فعليًا على a-salah.dev. هنا نسخة عامة تتظبط على أي عميل/موقع.

---

## 1. اختار اسم الفولدر (Slug) بعناية

⚠️ **أهم قاعدة في كل النظام**: اسم فولدر الثيم **لازم ما يحتويش على نقطة**
(`.`). WordPress core بينظّف (sanitize) اسم الثيم بـ regex بيشيل أي حرف مش
بين `A-Za-z0-9_-` في أكتر من مكان (AJAX الـ "Update now"، كرون التحديث
التلقائي). لو الاسم فيه نقطة، التحديثات بتفشل بصمت من غير أي error واضح.

❌ غلط: `client-site.com`, `my-theme.io`
✅ صح: `client-site-theme`, `my-theme`

---

## 2. اعمل الريبو على GitHub

- عام (Public) أسهل وأسرع اعداد — مناسب لمشاريعك الشخصية
- خاص (Private) لازم للعملاء — خطوة توكن إضافية في البند 4

```bash
gh repo create YOUR_GITHUB_USERNAME/YOUR_REPO_NAME --public   # أو --private
```

---

## 3. نسخ ملفات القالب للثيم

انسخ من القالب ده بس:

```
.github/workflows/auto-release.yml
```

وفي `.github/workflows/auto-release.yml`، غيّر سطر واحد فوق:

```yaml
env:
  THEME_SLUG: my-theme-slug # <-- غيّره لاسم فولدر الثيم (من غير نقطة!)
```

لازم يكون **مطابق تمامًا** لاسم الفولدر اللي هيتثبت بيه الثيم على
الموقع (نفس القيمة اللي هتحطها كـ "Theme slug" في إعدادات البلاجن، البند 5).

مفيش أي كود تحديث يتضاف لملفات الثيم نفسها — كل منطق التحديث جوة
البلاجن المنفصل.

---

## 4. ريبو عام ولا خاص؟

### عام (Public) — الأسهل
مفيش أي توكن مطلوب. انتقل للبند 5.

### خاص (Private) — لازم للعملاء
اعمل GitHub Personal Access Token (Fine-grained):
1. Settings → Developer settings → Fine-grained tokens → Generate new token
2. Repository access: **Only select repositories** → اختار ريبو العميل بس
3. Permissions: **Contents: Read-only**, **Metadata: Read-only**
4. اعمل Generate وانسخ التوكن (`ghp_...` أو `github_pat_...`) — هتحطه في
   إعدادات البلاجن في البند 5، لأ في أي ملف بيترفع على GitHub

مع توكن مُصادَق عليه، حد الطلبات بيرتفع لـ **5000 طلب/ساعة** — مشكلة
الـ rate limit على الاستضافة المشتركة مش موجودة أصلاً مع ريبو خاص وتوكن.

---

## 5. تثبيت وضبط بلاجن GitHub Theme Updater

على موقع العميل الفعلي (أو موقعك):

1. حمّل البلاجن من https://github.com/Abdulrahman14Salah/github-theme-updater
2. **Plugins → Add New Plugin → Upload Plugin** وفعّله
3. **Settings → GitHub Theme Updater** واملأ:
   - Theme slug، GitHub username، GitHub repository name
   - Visibility (Public/Private) + التوكن لو خاص
   - فعّل "Force automatic background updates" لو عايز تحديث بدون تدخل

هذا الإعداد **مرة واحدة فقط لكل موقع** — بعدها أي عميل جديد بنفس الخطوات
بس بتغيّر القيم، من غير أي كود جديد.

---

## 6. أول Push واختبار الـ Pipeline

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

لازم تلاقي Release جديد ومعاه zip بنفس اسم الـ `THEME_SLUG`.

---

## 7. أول تثبيت على الموقع المباشر (مرة واحدة فقط)

التحديثات التلقائية تفترض الثيم مثبّت بالفعل. أول مرة بس، لازم رفع يدوي:

1. حمّل الـ zip من Release: `https://github.com/.../releases/download/vX.X.X/THEME_SLUG.zip`
2. **Appearance → Themes → Add New Theme → Upload Theme**
3. ارفع وفعّل (Activate)

من هنا، أي push جديد على `main` يطلع Release، والبلاجن يكتشفه لوحده
ويحدّث الثيم (أو يفرض التحديث التلقائي لو فعّلت الخيار في البند 5).

---

## ملاحظات إضافية

- **الكاش الداخلي**: البلاجن بيحفظ آخر Release يجيبه من GitHub لمدة
  6 ساعات عشان يقلل الطلبات. الضغط على "Check Again" في صفحة Updates
  بيكسر هذا الكاش فورًا (البلاجن معمول فيه هذا التحسين بالفعل)، فمفيش
  داعي للانتظار لو عايز تتأكد فورًا.
- **تغيير اسم الفولدر لثيم قائم فعلاً**: لو عندك ثيم شغال بالفعل باسم
  فيه نقطة وعايز تصلحه، لازم تعمل migration لـ Customizer settings
  (`theme_mods_{old_slug}` → `theme_mods_{new_slug}`) عشان ما تضيعش
  إعدادات العميل. شوف مثال فعلي في `a-salah.dev/functions.php`.

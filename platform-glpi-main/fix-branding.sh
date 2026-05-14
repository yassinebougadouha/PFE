#!/bin/bash
# =====================================================
# fix-branding.sh
# Exécuter UNE SEULE FOIS dans le container Docker :
#   docker exec -it <container> bash fix-branding.sh
# ou depuis le dossier du projet :
#   ./vendor/bin/sail exec laravel.test bash /var/www/html/fix-branding.sh
# =====================================================

VIEWS="/var/www/html/resources/views"
echo "🎨 Correction des couleurs branding..."

find "$VIEWS" -name "*.blade.php" ! -path "*/emails/*" | while read FILE; do
    sed -i \
        -e 's|#667eea 0%, #764ba2 100%|var(--color-primary) 0%, var(--color-secondary) 100%|g' \
        -e 's|#667eea 0%, #64b5f6 100%|var(--color-primary) 0%, var(--color-secondary) 100%|g' \
        -e 's|#a8edea 0%, #667eea 100%|var(--color-primary) 0%, var(--color-secondary) 100%|g' \
        -e 's|#667eea,#764ba2|var(--color-primary),var(--color-secondary)|g' \
        -e 's|#667eea, #764ba2|var(--color-primary), var(--color-secondary)|g' \
        -e 's|#667eea,#64b5f6|var(--color-primary),var(--color-secondary)|g' \
        -e 's|#a8edea,#667eea|var(--color-primary),var(--color-secondary)|g' \
        -e 's|background: #667eea|background: var(--color-primary)|g' \
        -e 's|background:#667eea|background:var(--color-primary)|g' \
        -e 's|color: #667eea|color: var(--color-primary)|g' \
        -e 's|color:#667eea|color:var(--color-primary)|g' \
        -e 's|border-left:4px solid #667eea|border-left:4px solid var(--color-primary)|g' \
        -e 's|border-left:3px solid #667eea|border-left:3px solid var(--color-primary)|g' \
        -e 's|border-left:2px solid #667eea|border-left:2px solid var(--color-primary)|g' \
        -e 's|border:2px dashed #667eea|border:2px dashed var(--color-primary)|g' \
        -e 's|border:3px solid #667eea|border:3px solid var(--color-primary)|g' \
        -e 's|border:2px solid #667eea|border:2px solid var(--color-primary)|g' \
        -e 's|border-color: #667eea|border-color: var(--color-primary)|g' \
        -e 's|inset 4px 0 0 #667eea|inset 4px 0 0 var(--color-primary)|g' \
        -e 's|active bg-gradient-dark text-white|active text-white|g' \
        "$FILE"
done

# Fix JS inline (needs Python for the || character)
python3 - << 'PYEOF'
import os, glob

views = "/var/www/html/resources/views"

for path in glob.glob(f"{views}/**/*.blade.php", recursive=True):
    if "/emails/" in path:
        continue
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()

    original = content

    # JS fallback colors
    content = content.replace(
        "|| '#667eea'",
        "|| getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()"
    )
    content = content.replace(
        '|| "#667eea"',
        "|| getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()"
    )
    # JS style assignments
    content = content.replace("= '2px solid #667eea'", "= '2px solid ' + getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()")
    content = content.replace("= \"2px solid #667eea\"", "= '2px solid ' + getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()")

    # Chart.js hardcoded hex in arrays
    content = content.replace(
        "'#667eea'",
        "getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()"
    )
    content = content.replace(
        '"#667eea"',
        "getComputedStyle(document.documentElement).getPropertyValue('--color-primary').trim()"
    )

    if content != original:
        with open(path, "w", encoding="utf-8") as f:
            f.write(content)
        print(f"  ✅ {path.replace(views+'/', '')}")

PYEOF

# Fix emails — inject dynamic color variables at top
python3 - << 'PYEOF'
import os, glob

emails_dir = "/var/www/html/resources/views/emails"
header = '@php\n$_emailPrimary   = \\App\\Models\\Setting::get("primary_color", "#667eea");\n$_emailSecondary = \\App\\Models\\Setting::get("secondary_color", "#764ba2");\n@endphp\n'

replacements = [
    ("linear-gradient(135deg,#667eea 0%,#764ba2 100%)", "linear-gradient(135deg,{{ $_emailPrimary }} 0%,{{ $_emailSecondary }} 100%)"),
    ("linear-gradient(135deg,#667eea,#764ba2)",          "linear-gradient(135deg,{{ $_emailPrimary }},{{ $_emailSecondary }})"),
    ("background:#667eea",                               "background:{{ $_emailPrimary }}"),
    ("background: #667eea",                              "background: {{ $_emailPrimary }}"),
    ("border-left:4px solid #667eea",                    "border-left:4px solid {{ $_emailPrimary }}"),
    ("border:1px solid #667eea",                         "border:1px solid {{ $_emailPrimary }}"),
    ("color:#667eea",                                    "color:{{ $_emailPrimary }}"),
    ("color: #667eea",                                   "color: {{ $_emailPrimary }}"),
]

for path in glob.glob(f"{emails_dir}/*.blade.php"):
    with open(path, "r", encoding="utf-8", errors="ignore") as f:
        content = f.read()

    if "_emailPrimary" not in content:
        content = header + content

    for old, new in replacements:
        content = content.replace(old, new)

    with open(path, "w", encoding="utf-8") as f:
        f.write(content)
    print(f"  ✅ emails/{os.path.basename(path)}")

PYEOF

# Clear view cache
php /var/www/html/artisan view:clear

echo ""
echo "✅ Fix terminé! La couleur dans Paramètres → Branding s'applique maintenant partout."
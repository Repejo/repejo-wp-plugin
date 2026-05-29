# Repejo WP Plugin

Gör att Repejo-checkouten kan länkas med givar-id **direkt i adressen** i
stället för som frågeparameter.

```
Före:  https://exempel.se/signera?rp_hrid=abc-123
Efter: https://exempel.se/signera/abc-123
```

Det fina formatet ser tryggare ut för givaren. Tillägget **renderar ingen
checkout** – det gör två saker:

1. Ser till att WordPress **inte svarar 404** på den nya adressen.
2. Lägger givar-id:t i sidans `<head>` som en `<meta>`-tagg, så att den
   Repejo-komponent som redan finns på sidan kan läsa det:

   ```html
   <meta name="repejo-telemarketing-id" content="abc-123" />
   ```

Den gamla `?rp_hrid=`-länken fortsätter fungera parallellt.

---

## Installation (steg för steg)

Den här guiden är skriven så att du ska kunna följa den utan att vara
utvecklare. Räkna med ca 5–10 minuter. Behöver du hjälp under tiden – hör av
dig till oss på Repejo.

> **Bra att veta:** Du behöver vara inloggad i WordPress som
> **administratör** (du som kan installera tillägg). Testa gärna på en
> testsida först om ni har en.

### Steg 1 – Ladda ner rätt fil

1. Öppna sidan med färdiga versioner:
   **<https://github.com/Repejo/repejo-wp-plugin/releases>**
2. Klicka på den **översta (senaste) versionen** i listan.
3. Längst ner under rubriken **"Assets"**, klicka på filen
   **`repejo-wp-plugin.zip`** för att ladda ner den.

> ⚠️ **Viktigt:** Ladda **inte** ner via den gröna **"Code"**-knappen eller
> filen som heter **"Source code (zip)"**. Den saknar delar som behövs för
> att automatiska uppdateringar ska fungera. Använd **endast**
> `repejo-wp-plugin.zip` under **Assets**.

### Steg 2 – Installera och aktivera tillägget

1. Logga in i WordPress (`https://din-sajt.se/wp-admin`).
2. I menyn till vänster: **Tillägg → Lägg till nytt tillägg**.
3. Klicka på knappen **"Ladda upp tillägg"** högst upp.
4. Välj `repejo-wp-plugin.zip` från Steg 1, klicka **"Installera nu"**.
5. När installationen är klar, klicka **"Aktivera tillägg"**.

### Steg 3 – Spara permalänkar (viktigt!)

Det här steget glöms lätt bort, och då fungerar inte de fina länkarna.

1. I menyn: **Inställningar → Permalänkar**.
2. Ändra **ingenting** – klicka bara på knappen **"Spara ändringar"** längst
   ner.

*(Tillägget försöker göra detta automatiskt vid aktivering – men gör steget
ändå, som en garanti.)*

### Steg 4 – Aktivera på sidan

1. Gå till **Sidor** i menyn och öppna (redigera) den sida där Repejo-
   komponenten ligger (t.ex. sidan "Signera").
2. I panelen till höger, hitta rutan **"Repejo-länkformat"** och bocka i
   **"Aktivera Repejo-länkformat för denna sida"**.
3. Klicka **"Uppdatera"** för att spara sidan.

Du behöver **inte** lägga in något block eller någon kod – er befintliga
Repejo-komponent ligger kvar precis som tidigare.

> **Avancerat (oftast inget du behöver röra):** Under **Inställningar →
> Repejo** kan namnet på `<meta>`-taggen ändras. Standard är
> `repejo-telemarketing-id` och fungerar direkt om er komponent läser det namnet.

### Steg 5 – Testa att det fungerar

Öppna i webbläsaren (byt ut `<sidan>` mot sidans adress, t.ex. `signera`):

```
https://din-sajt.se/<sidan>/test-123
```

Kontrollera två saker:

1. **Samma sida visas** (inte en 404-sida), och adressen i adressfältet är
   **fortfarande** `.../test-123` (den ska inte hoppa tillbaka och ta bort
   `test-123`).
2. Högerklicka på sidan → **"Visa sidkälla"** och sök (Ctrl/Cmd+F) efter
   `repejo-telemarketing-id`. Du ska hitta:

   ```html
   <meta name="repejo-telemarketing-id" content="test-123" />
   ```

Testa gärna även den gamla formen
`https://din-sajt.se/<sidan>?rp_hrid=test-123` – meta-taggen ska finnas där
också.

---

## Om något inte stämmer

Tillägget visar **tydliga varningsrutor högst upp i WordPress-adminen** om
något saknas. De vanligaste sakerna:

| Symptom | Orsak och lösning |
| --- | --- |
| **"Sidan kunde inte hittas" / 404** på `.../test-123` | Steg 3 är inte gjort, eller gjordes innan tillägget aktiverades. Gå till **Inställningar → Permalänkar** och klicka **Spara ändringar** igen. |
| Adressen **hoppar tillbaka** och tar bort `test-123` | Sidan är inte ibockad i Steg 4. Öppna sidan, bocka i **"Aktivera Repejo-länkformat för denna sida"** och spara. |
| `<meta>`-taggen finns men **komponenten hittar inte id:t** | Meta-namnet matchar inte vad komponenten letar efter. Stäm av namnet under **Inställningar → Repejo** med er Repejo-kontakt. |
| Röd ruta: **"Permalänkar står på Enkel"** | Gå till **Inställningar → Permalänkar**, välj ett annat alternativ än **"Enkel"** (t.ex. "Inläggsnamn") och spara. De fina länkarna kräver detta. |
| Varning om att en sida har **undersidor som kan döljas** | Sidan du aktiverat har undersidor som kan krocka med länkformatet. Hör av dig till oss så hjälper vi er välja rätt inställning. |

Kommer du inte vidare? Skicka en skärmdump av varningsrutan till Repejo.

## Uppdateringar

När tillägget väl är installerat sköts uppdateringar precis som för andra
WordPress-tillägg: när en ny version finns dyker den upp under **Tillägg**
med en **"Uppdatera nu"**-länk. Du behöver alltså **inte** ladda ner någon
zip igen.

---

## Kontrakt mot er komponent (för utvecklare)

När en aktiverad sida öppnas med ett id (antingen `/<sida>/<id>` eller den
äldre `/<sida>?rp_hrid=<id>`) injiceras exakt:

```html
<meta name="repejo-telemarketing-id" content="<id>" />
```

Komponenten läser det t.ex. så här:

```js
const id = document
  .querySelector('meta[name="repejo-telemarketing-id"]')
  ?.getAttribute('content') || null;
```

- `name` är konfigurerbart (**Inställningar → Repejo** → *Meta-namn*).
  Standard: `repejo-telemarketing-id`.
- Tillåtna tecken i `<id>` styrs av ett regex (standard `[A-Za-z0-9_-]+`).
- Saknas id renderas ingen meta-tagg alls.

## Hur det fungerar

| Del | Ansvar |
| --- | --- |
| `Rewrite` | En rewrite-regel per **markerad** sida: `^<sökväg>/(<id>)/?$` → sidan + query-var `repejo_id`. Self-healing flush när regeluppsättningen ändras (även vid import/WP‑CLI). Aldrig flush på varje request. |
| `PageSettings` | Per-sida-kryssrutan i sidredigeraren. |
| `Canonical` | Hindrar WordPress kanoniska redirect från att kapa bort `id`:t — endast när vår query-var matchat. |
| `MetaTag` | Injicerar `<meta name="…" content="<id>">` i `<head>`. Faller tillbaka till den äldre frågeparametern så komponenten har en enda källa. Renderar ingen checkout. |
| `Diagnostics` | Synliga wp-admin-notiser för de klassiska tysta felen. |
| `Settings` | Meta-namn, äldre frågeparameter (`rp_hrid`), tillåtna tecken i id (regex). |
| `Updater` | Ett-klicks-uppdatering i wp-admin via GitHub Releases. |

## Utveckling

- PHP ≥ 7.4, WordPress ≥ 6.0. Inget byggsteg.
- En vanlig git-checkout fungerar; uppdateringskontrollen (PUC) är avstängd
  tills `vendor/` finns (`composer install`).

## Release

`Updater` använder [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) mot detta repos **GitHub Releases**.

```sh
# Höj Version-headern i repejo-wp-plugin.php och REPEJO_WP_PLUGIN_VERSION, commita.
git tag v0.3.0
git push origin v0.3.0
```

`.github/workflows/release.yml` bygger då en zip **med `vendor/`** och bifogar
den som release-asset. Kundernas WordPress upptäcker releasen och erbjuder
ett-klicks-uppdatering.

## Licens

GPL-2.0-or-later. Se [LICENSE](LICENSE).

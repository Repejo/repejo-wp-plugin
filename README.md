# Repejo WP Plugin

Gör att Repejo-checkouten kan länkas med givar-id **direkt i adressen** i stället för som frågeparameter, och renderar checkouten via shortcode eller block.

```
Före:  https://exempel.se/signera?rp_hrid=abc-123
Efter: https://exempel.se/signera/abc-123
```

Det fina formatet ser tryggare ut för givaren. Pluginet ser till att WordPress inte svarar **404** på den nya adressen och skickar `id`:t vidare till checkouten via den parameter Repejo-backend redan förstår (`rp_hrid` som standard).

---

## Installation (steg för steg)

Den här guiden är skriven så att du ska kunna följa den utan att vara
utvecklare. Räkna med ca 10 minuter. Behöver du hjälp under tiden – hör av
dig till oss på Repejo, vi guidar dig gärna.

> **Bra att veta:** Du behöver vara inloggad i WordPress som
> **administratör** (du som kan installera tillägg). Gör gärna detta på en
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
4. Välj `repejo-wp-plugin.zip` som du laddade ner i Steg 1, klicka
   **"Installera nu"**.
5. När installationen är klar, klicka **"Aktivera tillägg"**.

### Steg 3 – Spara permalänkar (viktigt!)

Det här steget glöms lätt bort, och då fungerar inte de fina länkarna.

1. I menyn: **Inställningar → Permalänkar**.
2. Ändra **ingenting** – klicka bara på knappen **"Spara ändringar"** längst
   ner.

*(Tillägget försöker göra detta automatiskt vid aktivering – men gör steget
ändå, som en garanti.)*

### Steg 4 – Fyll i adressen till checkouten

1. I menyn: **Inställningar → Repejo**.
2. I fältet **"Embed-URL för checkouten"**, klistra in den adress till
   Repejo-checkouten som du fått av oss.
3. De övriga fälten kan oftast lämnas som de är. Klicka **"Spara ändringar"**.

> Vet du inte vilken adress du ska fylla i? Kontakta Repejo, så ger vi dig
> den exakta adressen för er.

### Steg 5 – Aktivera på sidan och lägg in checkouten

1. Gå till **Sidor** i menyn och öppna (redigera) den sida där checkouten
   ska ligga (t.ex. sidan "Signera").
2. I panelen till höger, hitta rutan **"Repejo-länkformat"** och bocka i
   **"Aktivera Repejo-länkformat för denna sida"**.
3. Lägg in själva checkouten i sidans innehåll på **ett** av sätten:
   - **Blockredigeraren (vanligast):** klicka på **+** för att lägga till
     ett block, sök efter **"Repejo Checkout"** och lägg till det.
   - **Klassiska redigeraren / textläge:** skriv in koden
     `[repejo_checkout]` där checkouten ska visas.
4. Klicka **"Uppdatera"** / **"Publicera"** för att spara sidan.

### Steg 6 – Testa att det fungerar

Öppna i webbläsaren (byt ut `<sidan>` mot sidans adress, t.ex. `signera`):

```
https://din-sajt.se/<sidan>/test-123
```

Det ska visa **samma sida med checkouten**, och adressen i adressfältet ska
**fortfarande** vara `.../test-123` (den ska alltså inte hoppa tillbaka och
ta bort `test-123`).

Testa gärna även den gamla formen `https://din-sajt.se/<sidan>?rp_hrid=test-123`
– båda ska fungera parallellt.

---

## Om något inte stämmer

Tillägget visar **tydliga varningsrutor högst upp i WordPress-adminen** om
något saknas. De vanligaste sakerna:

| Symptom | Orsak och lösning |
| --- | --- |
| **"Sidan kunde inte hittas" / 404** på `.../test-123` | Steg 3 är inte gjort, eller gjordes innan tillägget aktiverades. Gå till **Inställningar → Permalänkar** och klicka **Spara ändringar** igen. |
| Adressen **hoppar tillbaka** och tar bort `test-123` | Sidan är inte ibockad i Steg 5. Öppna sidan, bocka i **"Aktivera Repejo-länkformat för denna sida"** och spara. |
| **Inget visas** där checkouten ska vara | Embed-URL saknas (Steg 4). Fyll i den under **Inställningar → Repejo**. |
| Röd ruta: **"Permalänkar står på Enkel"** | Gå till **Inställningar → Permalänkar**, välj ett annat alternativ än **"Enkel"** (t.ex. "Inläggsnamn") och spara. De fina länkarna kräver detta. |
| Varning om att en sida har **undersidor som kan döljas** | Sidan du aktiverat har undersidor som kan krocka med länkformatet. Hör av dig till oss så hjälper vi er välja rätt inställning. |

Kommer du inte vidare? Skicka en skärmdump av varningsrutan till Repejo, så
löser vi det tillsammans.

## Uppdateringar

När tillägget väl är installerat sköts uppdateringar precis som för andra
WordPress-tillägg: när en ny version finns dyker den upp under **Tillägg**
med en **"Uppdatera nu"**-länk. Du behöver alltså **inte** ladda ner någon
zip igen.

## Hur det fungerar

| Del | Ansvar |
| --- | --- |
| `Rewrite` | En rewrite-regel per **markerad** sida: `^<sökväg>/(<id>)/?$` → sidan + `repejo_id`. Self-healing flush när regeluppsättningen ändras (även vid import/WP‑CLI). Aldrig flush på varje request. |
| `PageSettings` | Per-sida-kryssrutan i sidredigeraren. |
| `Canonical` | Hindrar WordPress kanoniska redirect från att kapa bort `id`:t — endast när vår query-var matchat. |
| `Embed` | `[repejo_checkout]`-shortcode + server-renderat block. iframe mot konfigurerad embed-URL, eller helt egen markup via filtret `repejo_wp_plugin_embed_html`. |
| `Diagnostics` | Synliga wp-admin-notiser för de tre klassiska tysta felen. |
| `Settings` | Embed-URL, parameternamn (`rp_hrid`), tillåtna tecken i id (regex), iframe-höjd. |
| `Updater` | Ett-klicks-uppdatering i wp-admin via GitHub Releases. |

### Egen embed-markup

Script-baserad embed i stället för iframe:

```php
add_filter( 'repejo_wp_plugin_embed_html', function ( $html, $id, $base ) {
    return '<div id="repejo" data-rp-hrid="' . esc_attr( $id ) . '"></div>'
         . '<script src="https://checkout.repejo.se/embed.js" async></script>';
}, 10, 3 );
```

## Utveckling

- PHP ≥ 7.4, WordPress ≥ 6.0. Inget byggsteg — blocket är build-free.
- En vanlig git-checkout fungerar; uppdateringskontrollen (PUC) är avstängd
  tills `vendor/` finns (`composer install`).

## Release

`Updater` använder [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker) mot detta repos **GitHub Releases**.

```sh
# Höj versionen i repejo-wp-plugin.php (Version-headern) och commita.
git tag v0.2.0
git push origin v0.2.0
```

`.github/workflows/release.yml` bygger då en zip **med `vendor/`** och bifogar
den som release-asset. Kundernas WordPress upptäcker releasen och erbjuder
ett-klicks-uppdatering.

## Licens

GPL-2.0-or-later. Se [LICENSE](LICENSE).
